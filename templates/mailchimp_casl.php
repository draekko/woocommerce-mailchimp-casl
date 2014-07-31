<?php
/*
  ================================================== 
  		newsletter php script
  		Copyright 2014 Draekko, All rights reserved.
  		Licensed GPLv3.
  ================================================== 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $current_user, $woocommerce, $verify_newsletter_login, $use_newsletter_template, $mc_casl_integration, $mc_casl_integration_path, $mc_casl_integration_url_path;

wp_register_script('mailchimp-casl_js',  $mc_casl_integration_url_path . '/scripts/mailchimp_casl.js');
wp_enqueue_script('mailchimp-casl_js');

wp_register_style('mailchimp-casl_css',  $mc_casl_integration_url_path . '/css/mailchimp_casl.css');
wp_enqueue_style('mailchimp-casl_css');

$months = array(
	'1' 	=> 'January',
	'2' 	=> 'February',
	'3' 	=> 'March',
	'4' 	=> 'April',
	'5' 	=> 'May',
	'6' 	=> 'June',
	'7' 	=> 'July',
	'8' 	=> 'August',
	'9' 	=> 'September',
	'10' 	=> 'October',
	'11' 	=> 'November',
	'12' 	=> 'December' );

$short_months = array(
	'1' 	=> 'Jan',
	'2' 	=> 'Feb',
	'3' 	=> 'Mar',
	'4' 	=> 'Apr',
	'5' 	=> 'May',
	'6' 	=> 'Jun',
	'7' 	=> 'Jul',
	'8' 	=> 'Aug',
	'9' 	=> 'Sep',
	'10' 	=> 'Oct',
	'11' 	=> 'Nov',
	'12' 	=> 'Dec' );

if ($mc_casl_integration->get_option('mc_casl_display_name') == 'yes') {
	$required_name = ' (optional)';
	if ($mc_casl_integration->get_option('mc_casl_require_name') == 'yes') {
		$required_name = ' (required)';
	}
}

if (is_user_logged_in()) {
}
?>
	<div class="mc_casl_newsletter">
        <div class="mc_casl_container">
            <h4 class="mc_casl_section_title"><?php echo $mc_casl_integration->get_option('mc_casl_newsletter_title_label'); ?></h4>
            <form action="javascript:cs_mailchimp_submit('<?php echo get_template_directory_uri()?>','<?php echo $counter; ?>')" id="mcform" method="post">
					<div id="newsletter_mess_<?php echo $counter;?>" style="display:none"></div>
					<div>
						<div style="width:100%;display:inline-block;">
							<?php if ($mc_casl_integration->get_option('mc_casl_display_name') == 'yes') { ?>
							<input id="mc_casl_first_name" type="text" name="mc_casl_first_name" class="mailchimp_casl_newsletter_fname" value="" placeholder="First name<?php echo $required_name; ?>" />
							<input id="mc_casl_last_name" type="text" name="mc_casl_last_name" class="mailchimp_casl_newsletter_lname" value="" placeholder="Last name<?php echo $required_name; ?>" />
							<?php } ?>

							<?php if ($mc_casl_integration->get_option('mc_casl_request_dob') == 'yes') { ?>
							<fieldset style="width:100%;display:inline-block;">
     						<label class="birthday">Date of Birth:</label>
							<div class="form-row-casl form-row-wide birthday-field" id="birthday-wrapper">
   							<select name="birthday-year" class="year_select" id="birthday-year">
   								<?php
   								$curyear = idate('Y');
   								for($year=1900; $year<=$curyear; $year+=1) {
   									$selected='';
   									if ($year == $curyear) $selected = 'selected="selected" ';
   									echo '<option ' . $selected . 'value="' . $year . '">' . $year . '</option>';
   								}
   								?>
    							</select>
   							<select name="birthday-month" class="month_select" id="birthday-month">
   								<?php
   								$counter = 1;
   								$curmonth = idate('m');
   								foreach ( (array)$months as $key => $month) {
   									$selected='';
   									if ($curmonth == $counter) $selected = 'selected="selected" ';
    									echo '<option ' . $selected . 'value="' . $counter . '">' . __($month, 'woocomerce') . '</option>';
    									$counter++;
   								} ?>
    							</select>
   							<select name="birthday-day" class="day_select" id="birthday-day">
   								<?php
   								$curday = idate('d');
   								for($day=1; $day<=31; $day+=1) {
   									$selected='';
   									if ($day == $curday) $selected = 'selected="selected" ';
   									echo '<option ' . $selected . 'value="' . $day . '">' . $day . '</option>';
   								}
   								?>
    							</select>
							</div>
							</fieldset>
							<?php } ?>

							<div style="display:block">
								<input id="mc_casl_email" type="text" name="mc_casl_email" class="mailchimp_casl_newsletter_fix" value="" placeholder="<?php echo $mc_casl_integration->get_option('mc_casl_newsletter_email_label'); ?>" />
								<input type="button" name="button" id="mc_casl_submit_button" class="mailchimp_casl_newsletter_button with_name_fix" value="submit" />
							</div>
						</div>
						<label class="mc_casl_bottom_message">
							<em class="fa fa-envelope-o"></em>
							<?php echo $mc_casl_integration->get_option('mc_casl_newsletter_message_label'); ?>
                	</label>
                </div>
                <!-- NEXT -->
                <div class="mc_casl_agreement">
                		<div class="mc_casl_agreement_box">
                			<label class="mc_casl_agreement_text">
                    			<input type="checkbox" id="mc_casl_agreement_check" name="mc_casl_agreement_check" class="mc_casl_agreement_checkmark" value="checked" /> 
                				<?php echo $mc_casl_integration->get_option('mc_casl_newsletter_optin_label'); ?>
                			</label>
                			<label class="mc_casl_agreement_text">
                				<div id="mc_casl_agreement_email"></div>
                			</label>
                			<label class="mc_casl_client_info lineheight_fix">
                				<?php $ip = mc_casl_client_ip(); echo $ip; ?>
                				<br><?php echo mc_casl_client_country($ip); ?>
                			</label>
								<input type="button" name="cancel" id="mc_casl_cancel_button" class="mailchimp_casl_newsletter_button agreement_fix" value="cancel" />
                    		<input type="submit" id="mailchimp_casl_button" name="submit" class="mailchimp_casl_newsletter_submit agreement_fix" value="I AGREE"  /> 
                		</div>
                		<div class="mc_casl_error_box">
                			<label class="mc_casl_agreement_error">
                				<div id="mc_casl_agreement_error_msg"></div>
                			</label>
								<input type="button" name="cancel" id="mc_casl_cancel_error_button" class="mailchimp_casl_newsletter_button agreement_fix" value="OK" />
                		</div>
                </div>
                <div class="mc_casl_spinner_agreement">
                		<div class="mc_casl_spinner_box">
                            <div class="mc_casl_spinner"></div>
                		</div>
                </div>
            </form>
         </div>
     </div>

