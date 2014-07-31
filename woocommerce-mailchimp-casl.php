<?php
/**
 * Plugin Name: WooCommerce MailChimp CASL
 * Plugin URI: http://github.com/draekko/woocommerce-mailchimp-casl
 * Description: WooCommerce MailChimp CASL provides basic MailChimp support with Canadian Anti Spam Law compliance.
 * Author: Draekko	
 * Author URI: http://github.com/draekko/mailchimp_casl
 * Version: 1.0.0
 * 
 * Copyright: © 2014 Draekko
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'MC_CASL_TRUE' ) ) {
    define('MC_CASL_TRUE', 1, true );
}
if ( ! defined( 'MC_CASL_FALSE' ) ) {
    define('MC_CASL_FALSE', 0, true );
}

global $verify_newsletter_login, $use_newsletter_template, $mc_casl_integration, $mc_casl_integration_path, $mc_casl_integration_url_path;

/* Verify that Woocommerce is active */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	/* Prevent another instance of class */	
	if (!class_exists('WoocommerceMailChimpCasl')) {

		class WoocommerceMailChimpCasl {
			public $status = 0;
			public $message = '';
			public $mc_casl_plugin = true;
			private $db;

            /***************************************************************/

			public function __construct() {
				add_action('plugins_loaded', array(&$this, 'init'), 0);
			}

            /***************************************************************/

			public function init() {
				include_once( 'classes/class-mc-casl.php' );
				include_once( 'classes/class-mc-casl-db.php' );

				if ( ! class_exists( 'SQLite3' ) ) {
					$this->status = 1;
					return;	
				}
				if ( ! class_exists( 'WC_Integration' ) ) {
					$this->status = 2;
					return;	
				}
				if ( ! class_exists( 'WC_Integration_MC_CASL' ) ) {
					$this->status = 3;
					return;	
				}
				if ( ! class_exists( 'MailChimp_CASL_DB' ) ) {
					$this->status = 4;
					return;	
				}

				/* create temp folder */
                $uld = wp_upload_dir();
				$temppath = $uld['basedir'] . "/mailchimp_casl/temp";
				if ( !is_dir( $temppath ) ) {
					if ( !mkdir ( $temppath, 0755, true ) ) {
					    $this->status = 5;
					    return;
					}
			    }

				add_filter( 'woocommerce_integrations', array(&$this, 'add_options') );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(&$this, 'add_action_link') );
				add_action( 'init', array($this, 'load_plugin_textdomain' ) );
				add_action( 'personal_options_update', array( $this, 'mc_casl_user_profile_update' ) );
 		    	add_action( 'personal_options', array( $this, 'mc_casl_user_profile' ) );
				add_action( 'wp_ajax_mailchimp_casl_subscribe', array($this, 'mailchimp_casl_subscribe' ) );
				add_action( 'wp_ajax_nopriv_mailchimp_casl_subscribe', array($this, 'mailchimp_casl_subscribe' ) );
				add_action( 'wp_ajax_mailchimp_casl_download_csv', array($this, 'mailchimp_casl_download_csv' ) );
				//add_action( 'wp_ajax_nopriv_mailchimp_casl_download_csv', array($this, 'mailchimp_casl_download_csv' ) );

				$this->create_sqlite_database();
			}

            /***************************************************************/

			public function mailchimp_casl_download_csv() {
                if ( isset( $_POST ) ) {
                    if ( isset( $_POST['mc_casl_download_csv'] ) && $_POST['mc_casl_download_csv'] == true ) {
				        $dbfilepath = $this->mc_casl_get_db_path_file();
                        if ( !isset( $this->db ) ) {
                            $this->db = new MailChimp_CASL_DB( $dbfilepath, false );
                        } else {
                            $this->db->opendb( $dbfilepath, false );
                        }
                        $tempfile = $this->db->savedb_to_csv();
                        $this->db->closedb();
                        if (file_exists($tempfile)) {
                            $download = plugins_url( '', __FILE__ ) . "/download-csv.php?dl=" . basename( $tempfile );
                            echo trim( $download );
                        } else {
                            echo "ERROR";
                        }
                        die();
                    }
                }
            }

            /***************************************************************/

			public function mailchimp_casl_subscribe() {
                global $mc_casl_integration;

                if ( !isset ( $mc_casl_integration ) ) {
                    $mc_casl_integration = new WC_Integration_MC_CASL();
                }

                $mailchimp_api_key = $mc_casl_integration->get_option( 'mc_casl_api_key' );
                $mailchimp_list_id = $mc_casl_integration->get_option( 'mc_casl_list' );
                $mailchimp_double_opt_in = $mc_casl_integration->get_option( 'mc_casl_double_optin' );
                $timezone = $mc_casl_integration->get_option( 'mc_casl_server_timezone' );

                $update_existing   = mc_casl_true;
                $replace_interests = mc_casl_false;
                $send_welcome      = mc_casl_true;
                $double_opt_in     = mc_casl_false;
                if ( $mailchimp_double_opt_in == 'yes' ) {
                    $double_opt_in = mc_casl_true;
                }

                $loggedin = is_user_logged_in() ? mc_casl_true : mc_casl_false;

                if ( $mailchimp_list_id == '' || $mailchimp_api_key == '' ) {
			        do_action( 'wc_mailchimp_casl_subscribed', '' );
			        wp_mail( get_option('admin_email'), __( 'WooCommerce MailChimp subscription failed, No API key or list id.', 'wc_mailchimp_casl' ), '' );
			        echo "API: [".$mailchimp_api_key."] <br>";
			        echo "LST: [".$mailchimp_list_id."] <br>";
                    echo 'ERROR: No API key or list id';
                    die();
                } else if ( isset( $_POST ) ) {
                    if ( $mailchimp_api_key <> '' ) {
                        $MailChimp = new MailChimp($mailchimp_api_key);
                    }

                    if ( isset( $_POST['mc_casl_email'] ) && !empty( $_POST['mc_casl_email'] ) ) {
                        $subscriber_email = $_POST['mc_casl_email'];
                        if (!filter_var($subscriber_email, FILTER_VALIDATE_EMAIL)) {
                            echo 'ERROR: Invalid email found';
                            die();
                        }
                    } else {
                        echo 'ERROR: No email found';
                        die();
                    }

                    $merge_vars = array();

                    /* Date of Birth */
                    $birthday = '';
                    if (isset($_POST['mc_casl_dob']) && !empty($_POST['mc_casl_dob'])) {
                        if ($_POST['mc_casl_dob'] != '0-0-0') {
                            $merge_vars['date'] = $_POST['mc_casl_dob'];
                            $merge_vars['DATE'] = $_POST['mc_casl_dob'];
                            $birthday = $_POST['mc_casl_dob'];
                        }
                    }

                    /* Birthday */
                    if (isset($_POST['mc_casl_bday']) && !empty($_POST['mc_casl_bday'])) {
                        if ($_POST['mc_casl_bday'] != '0/0') {
                            $merge_vars['birthday'] = $_POST['mc_casl_bday'];
                            $merge_vars['BIRTHDAY'] = $_POST['mc_casl_bday'];
                        }
                    }

                    $fname = '';
                    if (isset($_POST['mc_casl_fname']) && !empty($_POST['mc_casl_fname'])) {
                        if ($_POST['mc_casl_fname'] != '') {
                            $merge_vars['FNAME'] = $_POST['mc_casl_fname'];
                            $fname = ucwords( strtolower( $_POST['mc_casl_fname'] ) );
                        }
                    }

                    $lname = '';
                    if (isset($_POST['mc_casl_lname']) && !empty($_POST['mc_casl_lname'])) {
                        if ($_POST['mc_casl_lname'] != '') {
                            $merge_vars['LNAME'] = $_POST['mc_casl_lname'];
                            $lname = ucwords( strtolower( $_POST['mc_casl_lname'] ) );
                        }
                    }

                    if ($timezone == '' || empty($timezone)) {
                        $timezone = date_default_timezone_get();
                    } else {
                        date_default_timezone_set($timezone);
                    }
                    $current_time     = date('H:i:s', time());
                    $current_date     = date('Y-m-d', time());
                    $current_ip       = mc_casl_client_ip();
                    $current_country  = ucwords( strtolower( mc_casl_client_country($current_ip) ) );

                    $ip_127 = filter_var($current_ip, FILTER_CALLBACK, array('options' => 'FILTER_FLAG_NO_LOOPBACK_RANGE')) &&
                    $ip_10_192_172  = filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
                    if ($ip_10_192_172 && $ip_127) {
                        $merge_vars['optin_ip'] = $current_ip;
                    }

                    $language = $merge_vars['mc_language'] = 'en';
                    $merge_vars['optin_time'] = $current_date . ' ' . $current_time;

                    $mailchimp_subscribe = array(
                        'id'                => $mailchimp_list_id,
                        'email'             => array( 'email' => $subscriber_email ),
                        'merge_vars'        => $merge_vars,
                        'double_optin'      => $double_opt_in,
                        'update_existing'   => $update_existing,
                        'replace_interests' => $replace_interests,
                        'send_welcome'      => $send_welcome,
                    );

                    $result = $MailChimp->call('lists/subscribe', $mailchimp_subscribe);

                    $useremail = '';
                    $userphone = '';
                    $usercompany = '';
                    $userfname = '';
                    $userlname = '';
                    $username = '';
                    $userid = '';
                    $tnd = $current_date . " " . $current_time;
                    $timestamp = strtotime($tnd);
                    $loggedin = mc_casl_false;
                    if (is_user_logged_in()) {
                        $userinfo = wp_get_current_user();
                        $loggedin = mc_casl_true;
                        $useremail = $userinfo->user_email;
                        $userphone = get_user_meta( $userinfo->ID, 'billing_phone', true );
                        $usercompany = get_user_meta( $userinfo->ID, 'billing_company', true );
                        $userfname = $userinfo->user_firstname;
                        $userlname = $userinfo->user_lastname;
                        $username = $userinfo->user_login;
                        $userid = $userinfo->ID;
                    }

                    $db_data = array(
                        'status'        => mc_casl_true,
                        'language'      => $language,
                        'ip'            => $current_ip,
                        'country'       => $current_country,
                        'loggedin'      => $loggedin,
                        'frontform'     => mc_casl_true,
                        'checkoutform'  => mc_casl_false,
                        'email'         => $subscriber_email,
                        'date'          => $current_date,
                        'time'          => $current_time,
                        'timestamp'     => $timestamp,
                        'dob'           => $birthday,
                        'fname'         => $fname,
                        'lname'         => $lname,
                        'useremail'     => $useremail,
                        'userphone'     => $userphone,
                        'usercompany'   => $usercompany,
                        'userfname'     => $userfname,
                        'userlname'     => $userlname,
                        'username'      => $username,
                        'userid'        => $userid,
                    );
  	
                    if ( $result <> '' ) {
                        if ( isset( $result[ 'status' ] ) and $result[ 'status' ] == 'error' ) {
                            do_action( 'wc_mailchimp_casl_subscribed', $subscriber_email );
                            wp_mail( get_option('admin_email'), __( 'WooCommerce MailChimp subscription failed.', 'wc_mailchimp_casl' ), '(' . $result['code'] . ') ' . $result['error'] );
                            echo "ERROR: [" . $result['error'] . "]";
                            $db_data['status'] = mc_casl_false;
                        } else {
                            if ( $mailchimp_double_opt_in == 'yes' ) {
                                echo 'You should soon be receiving an email to confirm your subscription. ';
                            }
                            $mailchimp_thankyou = $mc_casl_integration->get_option( 'mc_casl_newsletter_thankyou_label' );
                            echo $mailchimp_thankyou;
                            $db_data['status'] = mc_casl_true;
                        }

				        $uld = wp_upload_dir();
				        $storagepath = $uld['basedir'] . "/mailchimp_casl";
				        $dbfile = 'mailchimp_casl.db';
				        $dbfilepath = $storagepath . "/" . $dbfile;
   					    if ( !isset( $this->db ) ) {
   					        $this->db = new MailChimp_CASL_DB($storagepath . "/" . $dbfile, $this->password);
   					    } else {
                            $this->db->opendb( $dbfilepath, false );
   					    }
                        $this->db->savedata( $db_data );
                        $this->db->closedb();
                        
                    } else {
                        do_action( 'wc_mailchimp_casl_subscribed', $subscriber_email );
                        wp_mail( get_option('admin_email'), __( 'WooCommerce MailChimp subscription failed. Server result empty return.', 'wc_mailchimp_casl' ), '(' . $retval['code'] . ') ' . $retval['error'] );
                        echo "ERROR: [Server result empty return. Try again later]";
                    }
                } else {
			        do_action( 'wc_mailchimp_casl_subscribed', $subscriber_email );
			        wp_mail( get_option('admin_email'), __( 'WooCommerce MailChimp subscription failed, unknown error[2].', 'wc_mailchimp_casl' ), '(' . $retval['code'] . ') ' . $retval['error'] );
                    echo 'ERROR: Unknown error!';	 
                }

                die();
			}

            /***************************************************************/

			public function load_plugin_textdomain() {
				$language_path = dirname(plugin_basename(__FILE__)) . '/languages/';
				if (is_dir($language_path)) {
					load_plugin_textdomain('mc_casl_domain', FALSE, $language_path);
				}
			}

            /***************************************************************/

			private function create_sqlite_database() {
				$this->password = false;
				$uld = wp_upload_dir();
				$storagepath = $uld['basedir'] . "/mailchimp_casl";
				$dbfile = 'mailchimp_casl.db';
				$dbfilepath = $storagepath . "/" . $dbfile;
				$createdb = false;
				if ( !is_dir( $storagepath ) ) {
					if ( mkdir ( $storagepath, 0755, true ) ) {
    					$have_dir = true;
    					if ( !is_file( $dbfilepath ) ) {
							$createdb = true;
    					} else {
							$createdb = false;
    					}
					} else {
						$this->status = 1;
						$this->message = "ERROR: Unable to create database directory.";
						return false;
					}
    			} else {
                    $have_dir = true;
					if ( !is_file( $dbfilepath ) ) {
						$createdb = true;
    				} else {
						$createdb = false;
    				}
    			}

    			if ( $have_dir == true ) {
    				if ( $createdb == true ) {
   					    if ( !isset( $this->db ) ) $this->db = new MailChimp_CASL_DB($storagepath . "/" . $dbfile, $this->password);
    					if ( !$this->db->createtable() ) {
							$this->status = 96;
							$this->message = "ERROR: Unable to create database tables.";
							return false;
   					} else {
							$this->status = 98;
							$this->message = "Database was created.";
                            $this->db->closedb();
    					}
    				} else {
    					if (is_file( $dbfilepath ) && filesize( $dbfilepath ) == 0) {
    						if ( !isset( $this->db ) ) $this->db = new MailChimp_CASL_DB($storagepath . "/" . $dbfile, '', $this->password);
    						if ( !$this->db->createtable() ) {
								$this->status = 96;
								$this->message = "ERROR: Unable to create database tables.";
								return false;
    						} else {
								$this->status = 98;
								$this->message = "Database was created.";
                                $this->db->closedb();
    						}
    					}
    				}
    			} else {
					$this->status = 96;
					$this->message = "ERROR: Unable to create database or directory.";
					return false;
    			}

    			if ( filesize($dbfilepath) == 0 ) {
					$this->status = 96;
					$this->message = "ERROR: Unable to create database.";
					return false;
    			}

				return true;
			}

            /***************************************************************/

			public function add_options($methods) {
    			$methods[] = 'WC_Integration_MC_CASL';
				return $methods;
			}

            /***************************************************************/

			public function add_action_link( $links ) {
				global $woocommerce;
				$settings_url = admin_url( 'admin.php?page=wc-settings&tab=integration&section=mailchimp_casl' );
				$plugin_links = array( '<a href="' . $settings_url . '">' . __( 'Settings', 'wc_mailchimp_casl' ) . '</a>', );
				return array_merge( $plugin_links, $links );
			}

            /***************************************************************/
			/* USER PROFILE */
            /***************************************************************/

			public function mc_casl_user_profile() {
				global $user_id, $is_profile_page, $mc_casl_integration;
				$has_dates = false;
				$dbfilepath = $this->mc_casl_get_db_path_file();
                if ( !isset( $this->db ) ) {
                    $this->db = new MailChimp_CASL_DB( $dbfilepath, false );
                } else {
                    $this->db->opendb( $dbfilepath, false );
                }
                $items = $this->db->getrowcount();
                $this->db->closedb();
                if ( $items > 0 ) $has_dates = true;

				if ( !is_admin() ) {
                    if ( !isset($mc_casl_integration) ) $mc_casl_integration = new WC_Integration_MC_CASL();
    				$admin_display = $mc_casl_integration->get_option( 'mc_casl_display_user_date_list' );
				    if ( $admin_display == 'yes' ) {
				        return;
				    }
			    }

		    	wp_enqueue_style('mc_casl_profile_style', plugins_url('css/mc_casl_profile.css', __FILE__));

				echo "<table class=\"form-table\">\n";
				echo "<thead>\n";
				echo "<tr>\n";
				echo "<h3>".__( '<h3>Email Subscription<h3>', 'mc_casl_domain' )."</h3>\n";
				echo "</tr>\n";
				echo "</thead>\n";
				echo "<tbody>\n";
				if (is_admin()) {
					echo "<tr>\n";
					echo "<th scope=\"row\">Subscription date(s)</th>\n";
					echo "<td class=\"td_box\">\n";
					echo "<div class=\"ul_profile_box\">";
					echo "<ul class=\"ul_profile\">";
					if ($has_dates) {
						$this->mc_casl_display_all_dates();
					} else {
						echo "<li class=\"li_profile odd_bg\">Never Subscribed.</li>";
                    }
					echo "</ul>";
					echo "</div>";
					echo "</td>\n";
					echo "</tr>\n";
				} else {
					echo "<tr>\n";
					echo "<th scope=\"row\">Last subscription</th>\n";
					echo "<td>\n";
					if ($has_dates) {
						$this->mc_casl_display_last_date();
					} else {
						echo "Never Subscribed.";
					}
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "</tbody></table>\n";
			}

            /***************************************************************/

			public function mc_casl_user_profile_update() {
				global $user_id;
				// save user settings here
			}

            /***************************************************************/

			public function mc_casl_display_last_date() {
				echo "Never Subscribed.";
			}

            /***************************************************************/

			public function mc_casl_get_db_path_file() {
				$uld = wp_upload_dir();
				$storagepath = $uld['basedir'] . "/mailchimp_casl";
				$dbfile = 'mailchimp_casl.db';
				return $storagepath . "/" . $dbfile;
			}

            /***************************************************************/

			public function mc_casl_display_all_dates() {
                global $user_id;
			    $counter = 0;
			    $items = 0;

				$dbfilepath = $this->mc_casl_get_db_path_file();
                if ( !isset( $this->db ) ) {
                    $this->db = new MailChimp_CASL_DB( $dbfilepath, false );
                } else {
                    $this->db->opendb( $dbfilepath, false );
                }

                $items = $this->db->getrowcount();
                $userinfo = get_userdata($user_id);
                $useremail = $userinfo->user_email;
                //$userphone = get_user_meta( $user_id, 'billing_phone', true );
                //$usercompany = get_user_meta( $user_id, 'billing_company', true );
                //$userfname = $userinfo->user_firstname;
                //$userlname = $userinfo->user_lastname;
                //$username = $userinfo->user_login;

                $dbsearch=array();
                if ( isset( $useremail ) ) {
                    $dbsearch['email'] = $useremail;
                    //$dbsearch['useremail'] = $useremail;
                }
                /* if ( isset( $usercompany ) ) {
                    $dbsearch['usercompany'] = $usercompany;
                }
                if ( isset( $userphone ) ) {
                    $dbsearch['userphone'] = $userphone;
                }
                if ( isset( $userfname ) &&  isset( $userlname ) ) {
                    $dbsearch['userfname'] = $userfname;
                    $dbsearch['userlname'] = $userlname;
                }
                if ( isset( $username ) ) {
                    $dbsearch['username'] = $username;
                }
                $dbsearch['userid'] = strval( $user_id ); */

                $rowcounter = 0;
                if ( $items > 0 ) {
                    $rows = $this->db->getrows($dbsearch);
                    while ( $row = $this->db->fetcharray( $rows )  ) {
                        $rows_data[$rowcounter] = $row;
                        $rowcounter++;
                    }
                }

			    if ($rowcounter == 0) {
				    echo "<li class=\"li_profile odd_bg\">Never Subscribed.</li>";
			    } else {
			        $row = 'Never Subscribed.';
			        for($counter = 0; $counter < $rowcounter; $counter++) {
                        if (0 == $counter % 2) {
                            $is = 'even';
                        } else {
                            $is = 'odd';
                        }
				        echo "<li class=\"li_profile ".$is."_bg\">Registered on ".$rows_data[$counter]['date']." at ".$rows_data[$counter]['time']."</li>";
                    }
			    }

                $this->db->closedb();
			}
		}
	}

    /***************************************************************/

	/* instantiate */
	$mc_casl_woo_plugin = new WoocommerceMailChimpCasl();

	/* FILTER IP for LOOPBACK */
	if ( ! function_exists( 'is_mc_casl_plugin' ) ) {
	   function FILTER_FLAG_NO_LOOPBACK_RANGE($value) {
           return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $value :
                (((ip2long($value) & 0xff000000) == 0x7f000000) ? FALSE : $value);
       }
    }

    /***************************************************************/

	if ( ! function_exists( 'is_mc_casl_plugin' ) ) {
		function is_mc_casl_plugin() {
			global $mc_casl_woo_plugin;
			$screen = get_current_screen();
			$page = isset($_GET['page']) ? $_GET['page'] : '';
			$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
			$section = isset($_GET['section']) ? $_GET['section'] : '';
			if ( ( $screen->parent_base == 'woocommerce' ) &&
				  ( $page == 'wc-settings' && $tab == 'integration') &&
				  ( $section == 'mailchimp_casl' ) ) {
				return true;
			}

			if ( ( $screen->parent_base == 'woocommerce' ) &&
				  ( $page == 'wc-settings' && $tab == 'integration')
				  && $mc_casl_woo_plugin->mc_casl_plugin == true ) {
				return true;
			}

			return false;
		}
	}

    /***************************************************************/

	if ( ! function_exists( 'mc_casl_client_hostname' ) ) {
		function mc_casl_client_hostname($client_ip = '8.8.8.8') {
			$client_hostname = gethostbyaddr ( $client_ip );
			$client_hostname = ($client_hostname === false) ? '8.8.8.8' : $client_hostname;
			return $client_hostname;
		}
	}

    /***************************************************************/

	if ( ! function_exists( 'mc_casl_client_country' ) ) {
		function mc_casl_client_country($client_ip = '8.8.8.8') {
			global $verify_newsletter_login, $use_newsletter_template, $mc_casl_integration, $mc_casl_integration_path, $mc_casl_integration_url_path;

            if ( !isset($mc_casl_integration) ) $mc_casl_integration = new WC_Integration_MC_CASL();
			$country_select = $mc_casl_integration->get_option( 'mc_casl_ip_database' );

			switch ($country_select) {
				default:
				case 'geoplugin':
					$geopluginURL='http://www.geoplugin.net/php.gp?ip='.$client_ip;
					$addrDetailsArr = unserialize(file_get_contents($geopluginURL));
					$client_country = $addrDetailsArr['geoplugin_countryName'];
					break;
				case 'ipinfo':
					$ipinfoURL = file_get_contents("http://ipinfo.io/".$client_ip);
					$addrDetailsArr = json_decode($ipinfoURL);
					$client_country = $addrDetailsArr->country;
					$cc_code = strtoupper ( substr ( trim ( $addrDetailsArr->country ), 0, 2 ) );
					$client_country = mc_casl_db_country( $cc_code );
					break;
				case 'ip2nation':
					$client_country = mc_casl_db_ip2nation( $client_ip );
					break;
				case 'geolite2':
					$client_country = mc_casl_db_geolite2( $client_ip );
					break;
			}

			if ($client_country == '') {
				$client_country = 'Earth';
			}

			return $client_country;
		}
    }

    /***************************************************************/

	if ( ! function_exists( 'mc_casl_db_country' ) ) {
		function mc_casl_db_country($cc) {
			$db = new SQLite3(plugin_dir_path( __FILE__ ) . 'db/countries.db');
			$query = "SELECT * FROM countries WHERE code = '" . $cc . "';";
			$results = $db->query($query);
			if (!$results) {
				$db->close();
    			return '';
			} else {
				$row = $results->fetchArray(SQLITE3_ASSOC);
				$db->close();
				return $row['name'];
			}
		}
	}

    /***************************************************************/

	if ( ! function_exists( 'mc_casl_db_ip2nation' ) ) {
		function mc_casl_db_ip2nation($client_ip = '8.8.8.8') {
			$db = new SQLite3(plugin_dir_path( __FILE__ ) . 'db/ip2nation.db');
			$ip = ip2long($client_ip);
			$query = "SELECT c.country, c.code FROM ip2nationCountries c, ip2nation i WHERE i.ip < '".$ip."' AND c.code = i.country ORDER BY i.ip DESC LIMIT 0,1";
			$results = $db->query($query);
			if (!$results) {
				$db->close();
    			return '';
			} else {
				$row = $results->fetchArray(SQLITE3_ASSOC);
				$db->close();
				return $row['country'];
			}
		}
	}

    /***************************************************************/

	if ( ! function_exists( 'mc_casl_db_geolite2' ) ) {
		function mc_casl_db_geolite2($client_ip = '8.8.8.8') {
			$db = new SQLite3(plugin_dir_path( __FILE__ ) . 'db/geolite2.db');
			$ip = ip2long($client_ip);
			$query = "SELECT c.country_name, c.geoname_id FROM locations c, blocks i WHERE i.network_start_ip < '".$ip."' AND c.geoname_id = i.geoname_id ORDER BY i.network_start_ip DESC LIMIT 0,1";
			$results = $db->query($query);
			if (!$results) {
				$db->close();
    			return '';
			} else {
				$row = $results->fetchArray(SQLITE3_ASSOC);
				$db->close();
				return $row['country_name'];
			}
		}
	}

    /***************************************************************/

	if ( ! function_exists( 'mc_casl_client_ip' ) ) {
		function mc_casl_client_ip() {
			$client_ip = '8.8.8.8';

			if (isset($_SERVER)) {
        		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            	$client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        		} else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            	$client_ip = $_SERVER["HTTP_CLIENT_IP"];
        		} else {
        			$client_ip = $_SERVER["REMOTE_ADDR"];
        		}
    		} else if (getenv('HTTP_X_FORWARDED_FOR')) {
        		$client_ip = getenv('HTTP_X_FORWARDED_FOR');
        		if (strstr($client_ip, ', ')) {
    				$ips = explode(', ', $client_ip);
    				$client_ip = $ips[0];
				}
			} else if (getenv('HTTP_CLIENT_IP')) {
      		$client_ip = getenv('HTTP_CLIENT_IP');
    		} else {
    			$client_ip = getenv('REMOTE_ADDR');
        		if (strstr($client_ip, ', ')) {
    				$ips = explode(', ', $client_ip);
    				$client_ip = $ips[0];
				}
    		}

			$client_ip = "215.22.43.120";
			$client_ip = filter_var($client_ip, FILTER_VALIDATE_IP);
			$client_ip = ($client_ip === false) ? '8.8.8.8' : $client_ip;

			$long = ip2long($client_ip);
			if ( $long == -1 || $long === FALSE ) {
				$client_ip = '8.8.8.8';
			}

    		return $client_ip;
		}
	}

	/******************************************************************************************************************/

	function mailchimp_casl_add_box() {
	    global $mc_casl_integration;

        if ( !isset($mc_casl_integration) ) $mc_casl_integration = new WC_Integration_MC_CASL();

        $mailchimp_thankyou = $mc_casl_integration->get_option( 'mc_casl_newsletter_thankyou_label' );
        $mailchimp_double_opt_in = $mc_casl_integration->get_option( 'mc_casl_double_optin' );
        $thislabel = $mc_casl_integration->get_option( 'mc_casl_newsletter_optin_label' );
        $userinfo = wp_get_current_user();
        $ip =  mc_casl_client_ip();
        $country = mc_casl_client_country( $ip );

        echo "<div class=\"mc_casl_agreement_checkout\">";
        echo "    <div class=\"mc_casl_agreement_box_checkout\">";
        echo "        <label class=\"mc_casl_agreement_text_checkout\">";
        echo "            <input type=\"checkbox\" id=\"mc_casl_agreement_check_checkout\" name=\"mc_casl_agreement_check\" class=\"mc_casl_agreement_checkmark\" value=\"checked\" />"; 
        echo "            " . $thislabel; 
        echo "        </label>";
        echo "        <label class=\"mc_casl_agreement_text_checkout\">";
        echo "            <div id=\"mc_casl_agreement_email\">You may contact me at " . $userinfo->user_email . "</div>";
        echo "        </label>";
        echo "        <label class=\"mc_casl_client_info_checkout lineheight_fix\">";
        echo "            " . $ip;
        echo "            <br>" . $country;
        echo "        </label>";
        echo "        <input type=\"button\" name=\"checkout_cancel\" id=\"mc_casl_cancel_checkout_button\" class=\"mailchimp_casl_checkout_button agreement_fix\" value=\"CANCEL\" />";
        echo "        <input type=\"button\" id=\"mailchimp_casl_checkout_button\" name=\"checkout_submit\" class=\"mailchimp_casl_checkout_submit agreement_fix\" value=\"I AGREE\"  />";
        echo "    </div>";
        echo "    <div class=\"mc_casl_error_box_checkout\">";
        echo "        <label class=\"mc_casl_agreement_error_checkout\">";
        echo "            <div id=\"mc_casl_agreement_error_msg_checkout\">ERROR: You must agree by clicking the checkbox.</div>";
        echo "        </label>";
		echo "        <input type=\"button\" name=\"checkout_cancel\" id=\"mc_casl_cancel_checkout_error_button\" class=\"mailchimp_casl_checkout_button agreement_fix\" value=\"OK\" />";
        echo "    </div>";
        echo "    <div class=\"mc_casl_thankyou_box_checkout\">";
        echo "        <label class=\"mc_casl_agreement_thankyou_checkout\">";
        echo "            <div id=\"mc_casl_agreement_thankyou_msg_checkout\">";
        if ( $mailchimp_double_opt_in == 'yes' ) {
            echo 'You should soon be receiving an email to confirm your subscription once the order is processed. ';
        }
        echo $mailchimp_thankyou;
        echo "            </div>";
        echo "        </label>";
		echo "        <input type=\"button\" name=\"checkout_cancel\" id=\"mc_casl_cancel_checkout_thankyou_button\" class=\"mailchimp_casl_checkout_button agreement_fix\" value=\"OK\" />";
        echo "    </div>";
        echo "</div>";
	}

    /***************************************************************/

	if ( ! function_exists( 'mailchimp_casl_template' ) ) {
		function mailchimp_casl_template() {
			global $verify_newsletter_login, $use_newsletter_template, $mc_casl_integration, $mc_casl_integration_path, $mc_casl_integration_url_path;

			if ( class_exists( 'WC_Integration_MC_CASL' ) ) {
				$mc_casl_integration = new WC_Integration_MC_CASL();
				$sub_at_checkout = $mc_casl_integration->get_option( 'mc_casl_display_opt_in' );
				if ( isset ( $sub_at_checkout ) && $sub_at_checkout == 'yes') {
				    mailchimp_casl_add_box();
				}
				$do_mailchimp = $mc_casl_integration->get_option( 'mc_casl_enabled' );
				if ( $do_mailchimp == 'yes' ) {
					$use_newsletter_template = $mc_casl_integration->get_option( 'mc_casl_template_enabled' );
					if ( $use_newsletter_template == 'yes' ) {
						$verify_newsletter_login = $mc_casl_integration->get_option( 'mc_casl_logged_in_template_only' );
						echo "<script>\n";
						if ( $verify_newsletter_login == 'yes' ) {
							echo "var mc_casl_integration_verify_login=true;\n";
						}	else {
							echo "var mc_casl_integration_verify_login=false;\n";
						}
						if ( is_user_logged_in() ) {
							echo "var mc_casl_integration_user_logged_in=true;\n";
						} else {
							echo "var mc_casl_integration_user_logged_in=false;\n";
						}
						$displayname = $mc_casl_integration->get_option( 'mc_casl_display_name' );
						$requirename = 'false';
						if ($displayname == 'yes') {
							$getname = $mc_casl_integration->get_option( 'mc_casl_require_name' );
							if ( $getname == 'yes' ) $requirename = 'true';
						}
						echo "var requirename=".$requirename.";";
						$getage = $mc_casl_integration->get_option( 'mc_casl_request_dob' );
						$minage = $mc_casl_integration->get_option( 'mc_casl_minimum_age' );
						//$minage = intval(preg_replace("/[^\d]+/","",$age));
						if ($minage == '') $minage = 0;
						echo "var minage=".$minage.";";
						$checkage = 'false';
						if ( $getage == 'yes' ) $checkage = 'true';
						echo "var checkage=".$checkage.";";
						echo "</script>\n";
						$mc_casl_integration_url_path = '';
						if ( '' != locate_template( 'newsletter.php' ) ) {
							global $mc_casl_integration_url_path, $mc_casl_integration_path;
							$mc_casl_integration_url_path = get_template_directory_uri();
							$mc_casl_integration_path = get_template_directory();
							$newsletter = $mc_casl_integration_path . '/mailchimp_casl.php';
						} else {
							global $mc_casl_integration_url_path, $mc_casl_integration_path;
							$mc_casl_integration_url_path = plugins_url( 'templates/', __FILE__ );
							$mc_casl_integration_path = plugin_dir_path( __FILE__ ) . 'templates/';
							$newsletter = $mc_casl_integration_path . 'mailchimp_casl.php';
						}
						include_once( $newsletter );
					}
				}
  			}			
		}
	}
	add_shortcode( 'woocommerce_mailchimp_casl_template', 'mailchimp_casl_template' );

    /***************************************************************/

	function msg_admin_notice(){
		if (!is_mc_casl_plugin()) return;
		global $mc_casl_woo_plugin;
		$code = 0;
		$msg = '';
		$status = $mc_casl_woo_plugin->status;
		if ($status == 99) {
			$code = 1;
   		$msg = '[MC CASL] ERROR: Unknown error.';
		}
		if ($status == 98) {
			$code = 3;
   		$msg = '[MC CASL] ' . $mc_casl_woo_plugin->message;
		}
		if ($status == 97 ) {
			$code = 2;
   		$msg = '[MC CASL] ' . $mc_casl_woo_plugin->message;
		}
		if ($status == 96) {
			$code = 1;
   		$msg = '[MC CASL] ' . $mc_casl_woo_plugin->message;
		}
		if ($status == 1) {
			$code = 1;
   		$msg = '[MC CASL] ERROR: Missing SQLite3 exetension.';
		}
		if ($status == 2) {
			$code = 1;
   		$msg = '[MC CASL] ERROR: Missing WC_Integration class. Make sure woocommerce is working.';
		}
		if ($status == 3) {
			$code = 1;
   		$msg = '[MC CASL] ERROR: Missing WC_Integration_MC_CASL class. Trying reinstalling this plugin.';
		}
		if ($status == 4) {
			$code = 1;
   		$msg = '[MC CASL] ERROR: Missing MailChimpCASL_DB class. Trying reinstalling this plugin.';
		}
		if ($status == 5) {
			$code = 1;
   		$msg = '[MC CASL] ERROR: Cannot create temp folder. Make sure file permissions are properly set.';
		}

		switch($code) {
			case 1: /* ERROR */
				echo '<div class="error"><p><b>' . $msg . '</b></p></div>';
				break;

			case 2: /* WARNING */
				echo '<div class="update-nag"><p><b>' . $msg . '</b></p></div>';
				break;

			case 3: /* UPDATES */
				echo '<div class="updated"><p><b>' . $msg . '</b></p></div>';
				break;

			default:
				break;
		}

		$mc_casl_woo_plugin->status = 0;
		$mc_casl_woo_plugin->message = '';
	}
	add_action('admin_notices', 'msg_admin_notice');
}
