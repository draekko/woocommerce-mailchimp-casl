<?php

/**************************************************************

    COPYRIGHT 2014, BENOIT TOUCHETTE (DRAEKKO).
    This program comes with ABSOLUTELY NO WARRANTY;
    https://www.gnu.org/licenses/gpl-3.0.html
    https://www.gnu.org/licenses/quick-guide-gplv3.html
    Licensed GPLv3.

 **************************************************************/


/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MC_CASL_TRUE' ) ) {
    define('MC_CASL_TRUE', 1, true );
}
if ( ! defined( 'MC_CASL_FALSE' ) ) {
    define('MC_CASL_FALSE', 0, true );
}

class WC_Integration_MC_CASL extends WC_Integration {

    const VERSION = '1.0.0';

	/******************************************************************************************************************/

	public function __construct() {
		if ( !class_exists( 'MailChimp' ) ) {
			include_once( 'class-MCAPI-v2.php' );
		}

		if ( !class_exists( 'MailChimp_CASL_DB' ) ) {
			include_once( 'class-mc-casl-db.php' );
		}

		$this->id					= 'mailchimp_casl';

		$this->disclaimer           = "<strong>Disclaimer!</strong> This application and documentation and the information in it ";
        $this->disclaimer          .= "does not constitute legal advice. It is also is not a substitute for legal ";
		$this->disclaimer          .= "or other professional advice. Users should consult their own legal counsel ";
        $this->disclaimer          .= "for advice regarding the application of the law and this application as it ";
        $this->disclaimer          .= "applies to you and/or your business. This program is distributed in the hope ";
        $this->disclaimer          .= "that it will be useful, but <strong>WITHOUT ANY WARRANTY; without even the implied ";
        $this->disclaimer          .= "warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE</strong>. Continued ";
        $this->disclaimer          .= "use consitutes agreement of these terms. See the GNU General Public License ";
        $this->disclaimer          .= "for more details. <a target='_blank' href='https://www.gnu.org/licenses/gpl-3.0.html'>https://www.gnu.org/licenses/gpl-3.0.html</a>";
        
		$this->method_title     	= __( 'MailChimp CASL', 'wc_mailchimp_casl' );
		$this->method_description	= __( 'MailChimp is a popular email marketing service.', 'wc_mailchimp_casl' );

		$this->init_settings();
		$this->api_key 				= $this->get_option( 'mc_casl_api_key' );
		$this->attrib_groups_key	= '';
		$this->init_form_fields();

		/* OPTIONS */ 
		$this->enabled        						= $this->get_option( 'mc_casl_enabled' );
		$this->template_enabled 					= $this->get_option( 'mc_casl_template_enabled' );
		$this->displaynamerequest 					= $this->get_option( 'mc_casl_display_name' );
		$this->requirenamerequest 					= $this->get_option( 'mc_casl_require_name' );
		$this->checkdob          					= $this->get_option( 'mc_casl_request_dob' );
		$this->minage          						= $this->get_option( 'mc_casl_minimum_age' );
		$this->logged_in_template_only 				= $this->get_option( 'mc_casl_logged_in_template_only' );
		$this->newsletter_title_label 				= $this->get_option( 'mc_casl_newsletter_title_label' );
		$this->newsletter_email_label 				= $this->get_option( 'mc_casl_newsletter_email_label' );
		$this->newsletter_message_label 			= $this->get_option( 'mc_casl_newsletter_message_label' );
		$this->newsletter_optin_label 				= $this->get_option( 'mc_casl_newsletter_optin_label' );
		$this->newsletter_thankyou_label			= $this->get_option( 'mc_casl_newsletter_thankyou_label' );
		$this->ip_database     						= $this->get_option( 'mc_casl_ip_database' );
		$this->occurs         						= $this->get_option( 'mc_casl_occurs' );
		$this->list           						= $this->get_option( 'mc_casl_list' );
		$this->double_optin   						= $this->get_option( 'mc_casl_double_optin' );
		$this->display_opt_in 						= $this->get_option( 'mc_casl_display_opt_in' );
		$this->opt_in_label   						= $this->get_option( 'mc_casl_opt_in_label' );
		$this->opt_in_checkbox_display_location 	= $this->get_option( 'mc_casl_opt_in_checkbox_display_location' );
		$this->display_user_date_list				= $this->get_option( 'mc_casl_display_user_date_list' );
		$this->server_timezone				        = $this->get_option( 'mc_casl_server_timezone' );

		/* Hooks */
		add_action( 'admin_notices', array( &$this, 'enable_check' ) );
		add_action( 'woocommerce_update_options_integration', array( $this, 'process_admin_options') );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options') ); 

		/* 
		We would use the 'woocommerce_new_order' action but first name, last name and email address 
		(order meta) is not yet available, so instead we use the 'woocommerce_checkout_update_order_meta' 
		action hook which fires after the checkout process on the "thank you" page 
		*/
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'mc_casl_order_status_changed' ), 1000, 1 );

		/* hook into woocommerce order status changed hook to handle the desired subscription event trigger */
		add_action( 'woocommerce_order_status_changed', array( &$this, 'mc_casl_order_status_changed' ), 10, 3 );

		/* Maybe add an "opt-in" field to the checkout */
		add_filter( 'woocommerce_checkout_fields', array( &$this, 'mc_casl_add_checkout_fields' ) );

		/* Maybe save the "opt-in" field on the checkout */
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'mc_casl_save_checkout_fields' ) );

        //add_action( 'admin_enqueue_scripts', array( $this, 'do_style_action' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'do_script_action' ) );
	}

	/******************************************************************************************************************/

    /* HACK TO GET CSS ON ADMIN PAGE */
	public function do_css_admin_init() {
	    $style_id = 'wc_style_mc_casl_options-css';
		$style_path = str_replace( 'classes/', '', plugins_url( 'css/mc_casl_profile.css', __FILE__ ) );
	    echo "<link rel='stylesheet' id='".$style_id."'  href='".$style_path."?ver=".self::VERSION."' type='text/css' media='all' />";
	}

	public function do_style_action() {
	    $style_id = 'wc_style_mc_casl_options-css';
		$style_path = str_replace( 'classes/', '', plugins_url( 'css/mc_casl_profile.css', __FILE__ ) );
        wp_enqueue_style( $style_id, $style_path, false, self::VERSION );
	}
    
	/******************************************************************************************************************/

	public function do_script_action() {
		$script_path = str_replace( 'classes/', '', plugins_url( 'scripts/mailchimp_casl_wc.js', __FILE__ ) );
		wp_enqueue_script('wc_script_mc_casl_download-csv', $script_path, array( 'jquery' ), self::VERSION );
		$this->do_css_admin_init();
	}

	/******************************************************************************************************************/

	public function get_plugin_option( $option ) {
		return $this->get_option($option);
	}

	/******************************************************************************************************************/

	function enable_check() {
		global $woocommerce;

		if ( $this->enabled == 'yes' ) {
			if ( ! $this->api_key ) {
				echo '<div class="error"><p>' . sprintf( __('MailChimp CASL error: Please enter your api key <a href="%s">here</a>', 'wc_mailchimp_casl'), admin_url('admin.php?page=woocommerce&tab=integration&section=mailchimp' ) ) . '</p></div>';
				return;
			}
		}
	}

	/******************************************************************************************************************/

	public function has_list() {
		if ( $this->list ) {
			return true;
		}
		return false;
	}

	/******************************************************************************************************************/

	public function has_api_key() {
		if ( $this->api_key ) {
			return true;
		}
		return false;
	}

	/******************************************************************************************************************/

	public function is_valid() {
		if ( $this->enabled == 'yes' && $this->has_api_key() && $this->has_list() ) {
			return true;
		}
		return false;
	}

	/******************************************************************************************************************/

	function init_form_fields() {

		if ( is_admin() ) {

			$lists = $this->get_lists();
 			if ($lists === false ) {
 				$lists = array ();
 			}

			$this->list = $this->get_option( 'mc_casl_list' );
 			$mailchimp_lists = $this->has_api_key() ? array_merge( array( '' => __('Select a list...', 'wc_mailchimp_casl' ) ), $lists ) : array( '' => __( 'Enter your key and save to see your lists', 'wc_mailchimp_casl' ) );

            $timezones = $this->get_timezone_from_db();

			$this->form_fields = array(
				'mc_casl_enabled' => array(
								'title' 			=> __( 'Enable/Disable', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Enable MailChimp CASL', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> '',
								'default' 		=> 'no'
							),
				'mc_casl_double_optin' => array(
								'title' 			=> __( 'Double Opt-In', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Enable Double Opt-In', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> __( 'If enabled, customers will receive an email prompting them to confirm their subscription to the list above.', 'wc_mailchimp_casl' ),
								'default' 		=> 'no'
							),
				'mc_casl_ip_database' 		=> array(
								'title' 			=> __( 'IP to Country Database', 'wc_mailchimp_casl' ),
								'type' 			=> 'select',
								'description' 	=> __( 'Select which database to use to find what country an ip belongs to. This plugin includes GeoLite2 data created by MaxMind, available from <a href="http://www.maxmind.com">MaxMind</a> as well as data from <a href="http://www.ip2nation.com">Ip2Nation</a>. The web services data is provided byte <a href="http://www.ipinfo.io">IpInfo</a> which has a max call rate of 1000 lookups a day for free, and from <a href="http://www.geoplugin.com/">GeoPlugin</a>. Find information on creating the sqlite database files from the original source at <a href="https://github.com/draekko/databases">https://github.com/draekko/databases</a>.', 'wc_mailchimp_casl' ),
								'default' 		=> 'geolite2',
								'options' 		=> array(
								'geolite2' 	=> __( 'MaxMind GeoLite2 [sqlite]', 'wc_mailchimp_casl' ),
								'ip2nation'	=> __( 'ip2nation [sqlite]', 'wc_mailchimp_casl' ),
								'ipinfo' 	=> __( 'ipinfo.io [web]', 'wc_mailchimp_casl' ),
								'geoplugin'	=> __( 'geoplugin.net [web]', 'wc_mailchimp_casl' ),
								),
							),
				'mc_casl_api_key' => array(
								'title' 			=> __( 'API Key', 'wc_mailchimp_casl' ),
								'type' 			=> 'text',
								'description' 	=> __( '<a href="https://login.mailchimp.com/" target="_blank">Login to mailchimp</a> to look up your api key.', 'wc_mailchimp_casl' ),
								'default' 		=> ''
							),
				'mc_casl_list' => array(
								'title' 			=> __( 'Main List', 'wc_mailchimp_casl' ),
								'type' 			=> 'select',
								'description' 	=> __( 'All customers will be added to this list.', 'wc_mailchimp_casl' ),
								'default' 		=> '',
								'options' 		=> $mailchimp_lists,
							),
				'mc_casl_request_dob' => array(
								'title' 			=> __( 'Request date of birth', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Enable to request date of birth when subscribing to list', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> '',
								'default' 		=> 'no'
							),
				'mc_casl_minimum_age' => array(
								'title'			=> __( 'Minimum Age required', 'wc_mailchimp_casl' ),
								'type'			=> 'text',
								'description'	=> __( 'Set minimum age to sign up for emails, set to 0 to disable.', 'wc_mailchimp_casl' ),
								'default'		=> __( '0', 'wc_mailchimp_casl' ),
							),
				'mc_casl_display_name' => array(
								'title' 			=> __( 'Display name request', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Enable to ask display asking for name when subscribing to list', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> '',
								'default' 		=> 'no'
							),
				'mc_casl_require_name' => array(
								'title' 			=> __( 'Require name', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Enable to request name when subscribing to list', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> 'Set name request to either optional or required.',
								'default' 		=> 'no'
							),
				'mc_casl_template_enabled' => array(
								'title' 			=> __( 'Template Integration', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Enable MailChimp for template', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> '',
								'default' 		=> 'no'
							),
				'mc_casl_logged_in_template_only' => array(
								'title' 			=> __( 'Allow Template Use Logged In Only', 'wc_mailchimp_casl' ),
								'label' 			=> __( 'Only enable mailing list for logged in users', 'wc_mailchimp_casl' ),
								'type' 			=> 'checkbox',
								'description' 	=> '',
								'default' 		=> 'no'
							),
				'mc_casl_newsletter_title_label' => array(
								'title'			=> __( 'Subscription Title Label', 'wc_mailchimp_casl' ),
								'type'			=> 'textarea',
								'description'	=> __( '', 'wc_mailchimp_casl' ),
								'default'		=> __( 'Email Subscription', 'wc_mailchimp_casl' ),
							),
				'mc_casl_newsletter_email_label' => array(
								'title'			=> __( 'Subscription Email Label', 'wc_mailchimp_casl' ),
								'type'			=> 'textarea',
								'description'	=> __( '', 'wc_mailchimp_casl' ),
								'default'		=> __( 'Enter your email address', 'wc_mailchimp_casl' ),
							),
				'mc_casl_newsletter_message_label' => array(
								'title'			=> __( 'Subscription Message Label', 'wc_mailchimp_casl' ),
								'type'			=> 'textarea',
								'description'	=> __( '', 'wc_mailchimp_casl' ),
								'default'		=> __( 'Keep up with latest news, receive updates, and marketing messages. Read our latest newsletter.', 'wc_mailchimp_casl' ),
							),
				'mc_casl_newsletter_optin_label' => array(
								'title'			=> __( 'Subscription Opt-In Message Label', 'wc_mailchimp_casl' ),
								'type'			=> 'textarea',
								'description'	=> __( '', 'wc_mailchimp_casl' ),
								'default'		=> __( 'I agree to receive newsletters as well as other documentation, and information related to products and services offered on this site. I agree to be contacted until I withdraw consent by unsubscribing or opting out from the link provided with each message. Refer to our privacy and terms of use policies or contact us for more details.', 'wc_mailchimp_casl' ),
							),
				'mc_casl_newsletter_thankyou_label' => array(
								'title'			=> __( 'Subscription \'Thank You\' Message Label', 'wc_mailchimp_casl' ),
								'type'			=> 'textarea',
								'description'	=> __( '', 'wc_mailchimp_casl' ),
								'default'		=> __( 'Thank you for subscribing and have a great day.', 'wc_mailchimp_casl' ),
							),
				'mc_casl_display_opt_in' => array(
								'title'			=> __( 'Display Opt-In Field', 'wc_mailchimp_casl' ),
								'label'			=> __( 'Display an Opt-In Field on Checkout', 'wc_mailchimp_casl' ),
								'type'			=> 'checkbox',
								'description'	=> __( 'If enabled, customers will be presented with a "Opt-in" checkbox during checkout and will only be added to the list above if they opt-in.', 'wc_mailchimp_casl' ),
								'default'		=> 'no',
							),
				'mc_casl_opt_in_label' => array(
								'title'			=> __( 'Opt-In Field Label', 'wc_mailchimp_casl' ),
								'type'			=> 'textarea',
								'description'	=> __( 'Optional: customize the label displayed next to the opt-in checkbox.', 'wc_mailchimp_casl' ),
								'default'		=> __( 'Add me to the newsletter (we will never share your email).', 'wc_mailchimp_casl' ),
							),
				'mc_casl_occurs' => array(
								'title'			=> __( 'Subscribe Event', 'wc_mailchimp_casl' ),
								'type'			=> 'select',
								'description'	=> __( 'When should customers be subscribed to lists?', 'wc_mailchimp_casl' ),
								'default'		=> 'pending',
								'options'		=> array(
								'pending'		=> __( 'Order Created', 'wc_mailchimp_casl' ),
								'completed'		=> __( 'Order Completed', 'wc_mailchimp_casl' ),
								),
							),
				'mc_casl_opt_in_checkbox_display_location' => array(
								'title'			=> __( 'Opt-In Checkbox Display Location', 'wc_mailchimp_casl' ),
								'type'			=> 'select',
								'description'	=> __( 'Where to display the opt-in checkbox on the checkout page (under Billing info or Order info).', 'wc_mailchimp_casl' ),
								'default'		=> 'billing',
								'options'		=> array( 'billing' => __( 'Billing', 'wc_mailchimp_casl' ), 'order' => __( 'Order', 'wc_mailchimp_casl' ) )
							),
				'mc_casl_display_user_date_list' => array(
								'title'			=> __( 'Display user data for admin only', 'wc_mailchimp_casl' ),
								'label'			=> __( 'Enable/Disable profile users data.', 'wc_mailchimp_casl' ),
								'type'			=> 'checkbox',
								'description'	=> 'This will toggle showing dates of subscriptions for admin only if enabled.',
								'default'		=> 'yes',
							),
				'mc_casl_server_timezone' => array(
								'title' 			=> __( 'Set Timezone', 'wc_mailchimp_casl' ),
								'type' 			=> 'select',
								'description' 	=> __( 'Select timezone for reporting time and date (default is server timezone).', 'wc_mailchimp_casl' ),
								'default' 		=> '',
								'options' 		=>$timezones,
							),
				'mc_casl_csv_export' => array(
								'title' 			=> __( 'Export DB to CSV', 'wc_mailchimp_casl' ),
								'type' 			=> 'button',
								'name' 			=> 'mc_casl_csv_export',
								'value' 		=> 'Download DB',
								'description' 	=> 'Save data to a csv file for download.',
								'default' 		=> '',
							),
			);

			$this->wc_enqueue_js("
				jQuery('#woocommerce_mailchimp_display_opt_in').change(function(){

					jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').hide('fast');

					if ( jQuery(this).prop('checked') == true ) {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').show('fast');
					} else {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').hide('fast');
					}

				}).change();
			");
		}
	}

	/******************************************************************************************************************/

	private function get_timezone_from_db() {
        $tz_rows = array();
        $i = 0;
		$timezones = array();
		$plugin_path = str_replace( 'classes/', '', plugin_dir_path( __FILE__ ) ) . 'db/timezone.db';
		$db = new SQLite3( $plugin_path );
        $query = $db->query( 'SELECT zone_name FROM zone ORDER BY zone_name ASC;' );
        if ( $query ) {
            while ( $res = $query->fetchArray(SQLITE3_ASSOC) ) {
	            $tz_rows[$res['zone_name']] = $res['zone_name'];
	            $i++;
            }
        }
		$db->close();
        $timezones = array_merge( array( '' => __('Server default time zone', 'wc_mailchimp_casl' ) ), $tz_rows );
        return $timezones;
    }	   

	/******************************************************************************************************************/

	private function wc_enqueue_js( $code ) {
		if ( function_exists( 'wc_enqueue_js' ) ) {
			wc_enqueue_js( $code );
		} else {
			global $woocommerce;
			$woocommerce->add_inline_js( $code );
		}
	}

	/******************************************************************************************************************/

	function mc_casl_add_checkout_fields( $checkout_fields ) {
		$opt_in_checkbox_display_location = $this->opt_in_checkbox_display_location;

		if ( empty( $opt_in_checkbox_display_location ) ) {
			$opt_in_checkbox_display_location = 'billing';
		}
		
		if ( 'yes' == $this->display_opt_in ) {
			$checkout_fields[$opt_in_checkbox_display_location]['mailchimp_casl_opt_in_subscription'] = array(
				'type'    => 'checkbox',
				'class'   => array('form-row-wide', 'shipping_address'),
				'label'   => esc_attr( $this->opt_in_label ),
				'default' => 0,
			);
		}
		
		return $checkout_fields;
	}

	/******************************************************************************************************************/

	function mc_casl_save_checkout_fields( $order_id ) {
		if ( 'yes' == $this->display_opt_in ) {
			$opt_in = isset( $_POST['mailchimp_casl_opt_in_subscription'] ) ? 'yes' : 'no';
			update_post_meta( $order_id, 'mailchimp_casl_opt_in_subscription', $opt_in );
		}
	}

	/******************************************************************************************************************/

	public function mc_casl_order_status_changed( $id, $status = 'new', $new_status = 'pending' ) {
		echo "<br><h2>process mailing list [".$new_status."] [".$this->occurs."] [".$this->is_valid()."]</h2><br>";
		if ( $this->is_valid() && $new_status == $this->occurs ) {
			$order = new WC_Order( $id );

			$mailchimp_casl_opt_in_subscription = get_post_meta( $id, 'mailchimp_casl_opt_in_subscription', true );

			if ( ! isset( $mailchimp_casl_opt_in_subscription ) || empty( $mailchimp_casl_opt_in_subscription ) || 'yes' == $mailchimp_casl_opt_in_subscription ) {
				$this->mailchimp_casl_subscribe( $id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->list );
			} 
		}
	}

	/******************************************************************************************************************/

	public function mailchimp_casl_subscribe( $order_id, $first_name, $last_name, $email, $listid = 'false' ) {
	    wp_debug_mode();
	    $retstatus = true;
	    
		/* Email is required */
		if ( ! $email ) {
			return false; 
		}

		$MailChimp = new MailChimpApi( $this->api_key );

		if ( $listid == 'false' ) {
			$listid = $this->list;
		}

		$double_optin = ( $this->double_optin == 'yes' ? mc_casl_true : mc_casl_false );
		$update_existing = mc_casl_true;
		$replace_interests = mc_casl_false;
		$send_welcome = mc_casl_true;

		$merge_vars = array('FNAME' => $first_name, 'LNAME'=>$last_name);

        $subscribe_data = array(
			'id'                => $listid,
			'email'             => array('email' => $email),
			'merge_vars'        => $merge_vars,
			'double_optin'      => $double_optin,
			'update_existing'   => $update_existing,
			'replace_interests' => $replace_interests,
			'send_welcome'      => $send_welcome,
		);

		$result = $MailChimp->call('lists/subscribe', $subscribe_data);
		if ( $result['status'] == 'error' ) {
			do_action( 'wc_mailchimp_casl_subscribed', $email );
			wp_mail( get_option('admin_email'), __( 'WooCommerce MailChimp subscription failed', 'wc_mailchimp_casl' ), '(' . $retval['code'] . ') ' . $retval['error'] );
			//return false;
			$retstatus = false;
		}

        $loggedin = is_user_logged_in() ? mc_casl_true : mc_casl_false;

        $timezone = $this->get_option( 'mc_casl_server_timezone' );
        if ($timezone == '' || empty($timezone)) {
            $timezone = date_default_timezone_get();
        } else {
            date_default_timezone_set($timezone);
        }
        $current_time     = date('H:i:s', time());
        $current_date     = date('Y-m-d', time());
        $current_ip       = mc_casl_client_ip();

        $ip_127 = filter_var($current_ip, FILTER_CALLBACK, array('options' => 'FILTER_FLAG_NO_LOOPBACK_RANGE')) &&
        $ip_10_192_172  = filter_var($current_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
        if ($ip_10_192_172 && $ip_127) {
            $merge_vars['optin_ip'] = $current_ip;
        }

        $current_country  = ucwords( strtolower( mc_casl_client_country( $current_ip ) ) );
        $language = $merge_vars['mc_language'] = 'en';
        $merge_vars['optin_time'] = $current_date . ' ' . $current_time;

        $useragent = $_SERVER['HTTP_USER_AGENT'];

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
        if ( is_user_logged_in() ) {
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

        $status = mc_casl_true;
        if ( $retstatus == false ) {
            $status = mc_casl_false;
        }

        $db_data = array(
            'language'      => $language,
            'ip'            => $current_ip,
            'country'       => $current_country,
            'loggedin'      => $loggedin,
            'frontform'     => mc_casl_false,
            'checkoutform'  => mc_casl_true,
            'email'         => $email,
            'status'        => $status,
            'date'          => $current_date,
            'time'          => $current_time,
            'timestamp'     => $timestamp,
            'dob'           => '',
            'fname'         => $first_name,
            'lname'         => $last_name,
            'useremail'     => $useremail,
            'userphone'     => $userphone,
            'usercompany'   => $usercompany,
            'userfname'     => $userfname,
            'userlname'     => $userlname,
            'username'      => $username,
            'useragent'     => $useragent,
            'userid'        => $userid,
        );

        $uld = wp_upload_dir();
		$storagepath = $uld['basedir'] . "/mailchimp_casl";
		$dbfile = 'mailchimp_casl.db';
		$dbfilepath = $storagepath . "/" . $dbfile;
   		$db = new MailChimp_CASL_DB( $dbfilepath, false );
        $err = $db->savedata( $db_data );
        $db->closedb();

		return $retstatus;
	}

	/******************************************************************************************************************/

	public function get_lists() {
		if ( ! $mailchimp_lists = get_transient( 'wc_mailchimp_casl_list_' . md5( $this->api_key ) ) ) {
			$mailchimp_lists 	= array();
			$mailchimp_casl	= new MailChimpApi( $this->api_key );
			$retval          	= $mailchimp_casl->call('lists/list');

			if ( !isset( $retval ) || !is_array( $retval ) ) {
				echo '<div class="error"><p>Unable to load lists() from MailChimp CASL: (-999) Unknown error wc_mailchimp_casl</p></div>';
				return false;
			} else if ( isset( $retval['status'] ) && $retval['status'] == 'error' ) {
				echo '<div class="error"><p>' . sprintf( __( 'Unable to load lists() from MailChimp CASL: (%s) %s', 'wc_mailchimp_casl' ), $retval['code'], $retval['error'] ) . '</p></div>';
				return false;
			} else {
				foreach ( $retval['data'] as $list ) {
					$mailchimp_lists[ $list['id'] ] = $list['name'];
				}
				if ( sizeof( $mailchimp_lists ) > 0 ) {
					set_transient( 'wc_mailchimp_casl_list_' . md5( $this->api_key ), $mailchimp_lists, 60*60*1 );
				}
			}
		}
		return $mailchimp_lists;
	}

	/******************************************************************************************************************/

	static function log( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}

	/******************************************************************************************************************/

	function admin_options() { ?>
            <div class='mc_casl_admin_options'>
            <h3><?php _e( 'MailChimp CASL', 'wc_mailchimp_casl' ); ?></h3>
            <p><?php _e( 'Enter your MailChimp CASL settings below to control how your theme and WooCommerce integration with your MailChimp lists.', 'wc_mailchimp_casl' ); ?></p>
            <?php echo "<p class='mc_casl_disclaimer'>".$this->disclaimer."</p>"; ?>
            <table class="form-table">
                <?php $this->mc_casl_generate_settings_html(); ?>
            </table>
        </div>
		<?php
	}

	/*******************************************************************************************************************/
	/** 															WOOCOMMERCE HACKS																	**/
	/*******************************************************************************************************************/

	public function mc_casl_generate_settings_html( $form_fields = false ) {
		$html = '';
        if ( ! $form_fields ) {
			$form_fields = $this->get_form_fields();
        }
		foreach ( $form_fields as $k => $v ) {
			if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) {
				$v['type'] = 'text'; 
			}

			if ( method_exists( $this, 'mc_casl_generate_' . $v['type'] . '_html' ) ) {
				$html .= $this->{'mc_casl_generate_' . $v['type'] . '_html'}( $k, $v );
			} else {
				$html .= $this->{'mc_casl_generate_text_html'}( $k, $v );
			}
		}
		echo $html;
	}

	/******************************************************************************************************************/

	public function mc_casl_generate_checkbox_html( $key, $data ) {
		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'title'             => '',
			'label'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array()
		);

		$data = wp_parse_args( $data, $defaults );
		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}
	
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<label for="<?php echo esc_attr( $field ); ?>">
					<input <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="checkbox" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="1" <?php checked( $this->get_option( $key ), 'yes' ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?> /> <?php echo wp_kses_post( $data['label'] ); ?></label><br/>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/******************************************************************************************************************/

	public function mc_casl_generate_text_html( $key, $data ) {
		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array()
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?> />
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/******************************************************************************************************************/

	public function mc_casl_generate_select_html( $key, $data ) {
    	$field    = $this->plugin_id . $this->id . '_' . $key;
    	$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array()
		);
        
		$data = wp_parse_args( $data, $defaults );
	
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>>
						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : 
								$selected = selected( $option_key, esc_attr( $this->get_option( $key ) ) );
								$attrib_key = esc_attr( $option_key );
								$attrib_value = esc_attr( $option_value );
								/*if ($key == 'mc_casl_list_groupings') {
									if ($selected != '') {
										$this->attrib_groups_key = $option_key; 
									}
								}*/
							?>
							<option value="<?php echo $attrib_key; ?>" <?php echo $selected; ?>><?php echo $attrib_value; ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/******************************************************************************************************************/

	public function mc_casl_generate_textarea_html( $key, $data ) {
    	$field    = $this->plugin_id . $this->id . '_' . $key;
    	$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array()
		);
        
		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<textarea rows="3" cols="80" class="input-text wide-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo esc_textarea( $this->get_option( $key ) ); ?></textarea>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/******************************************************************************************************************/

	public function mc_casl_generate_button_html( $key, $data ) {
    	$field    = $this->plugin_id . $this->id . '_' . $key;
    	$defaults = array(
			'title'             => '',
			'name'              => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'value'             => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array()
		);
        
		$data = wp_parse_args( $data, $defaults );
		ob_start();
    ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
                    <input name="<?php echo wp_kses_post( $data['name'] ); ?>" id="<?php echo wp_kses_post( $data['name'] ); ?>" class="button-secondary" type="button" value="<?php echo wp_kses_post( $data['value'] ); ?>">
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
			</td>
		</tr>
    <?php
		return ob_get_clean();
    }
}

?>