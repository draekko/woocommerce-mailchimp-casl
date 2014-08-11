=== WooCommerce MailChimp CASL ===
Contributors: Ben Touchette (Draekko)
Tags: woocommerce mailchimp casl wordpress
Donate link: http://draekko.com
Requires at least: 3.9.1
Tested up to: 3.9.2
Stable tag: 1.0.1
License: GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WooCommerce MailChimp CASL provides MailChimp integration for 
WooCommerce with CASL compliance and theme integration.

== Description ==

WooCommerce MailChimp CASL provides MailChimp integration for 
WooCommerce with CASL compliance and theme integration.

Automatically subscribe customers to a designated MailChimp list and, 
optionally, MailChimp interest groups upon order creation or order 
completion. This can be done quietly or based on the user's consent 
with several opt-in settings that support international opt-in laws.

= Disclaimer =

This application and documentation and the information in it does not 
constitute legal advice. It is also is not a substitute for legal or 
other professional advice. Users should consult their own legal counsel 
for advice regarding the application of the law and this application as 
it applies to you and/or your business. This program is distributed in 
the hope that it will be useful, but WITHOUT ANY WARRANTY; without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
Continued use consitutes agreement of these terms. See the GNU General 
Public License for more details. 

https://www.gnu.org/licenses/gpl-3.0.html

#### WordPress Theme integration ####

- Subscribe customers to MailChimp through your WordPress theme by
  adding the appropriate hook to your theme.

#### WooCommerce Event Selection ####

- Subscribe customers to MailChimp after order creation
- Subscribe customers to MailChimp after order completion

#### Opt-In Settings ####

- MailChimp double opt-in support (control whether a double opt-in email 
  is sent to the customer)
- Optionally, display an opt-in checkbox on the checkout page (this is 
  required in some countries)
- Control the label displayed next to the opt-in checkbox
- Control whether or not the opt-in checkbox is checked or unchecked 
  by default
- Control the placement of the opt-in checkbox on the checkout page 
  (under billing info or order info)

#### DB information ####

- Option to download the database as a .csv file viewable in Calc 
  (LibreOffice) or Excel (MS Office).

== Installation ==
1. Upload or extract the `woocommerce-mailchimp-casl` folder to your site\'s 
   `/wp-content/plugins/` directory. You can also use the *Add new* 
   option found in the *Plugins* menu in WordPress.  
2. Enable the plugin from the *Plugins* menu in WordPress.
3. In WooCommerce -> Integration -> MailChimp CASL to enable the options.

= Usage =

1. Go to WooCommerce > Settings > Integration > MailChimp CASL
2. First, enable the plugin and set your MailChimp API Key and hit save.
3. Select whether you want customers to be subscribed to your MailChimp 
   list after order creation or order completion (there's a difference 
   in WooCommerce).
4. Next, select your MailChimp list and set any interest group settings 
   (optional) and hit save.
5. That's it, now customers who purchase products from your WooCommerce 
   store will automatically be subscribed 
   to the selected list (and optional interest groups) in MailChimp!

= Theme Integration =

Add one of these sections of lines to your theme to make 'template 
integration' work:

    if ( function_exists( 'mailchimp_casl_template' ) ) {
        mailchimp_casl_template();
    }

    or

    <?php if ( function_exists( 'mailchimp_casl_template' ) ) {
        mailchimp_casl_template();
    } ?>

Note: Usually located at the bottom in the footer (added to footer.php 
      in theme).

== Changelog ==

= 1.0.2 =
* Fixed the bug preventing from templates in theme from showing up.

= 1.0.1 =
* Fixed bug in database code.
* Renamed MailChimp class to avoid conflicts .
* Removed unused function .

= 1.0.0 =
* This is the first public release.

== Frequently Asked Questions ==

N/A

== Upgrade Notice ==

N/A

== Screenshots ==

N/A


