=== WooCommerce MailChimp CASL ===
Contributors: Draekko
Tags: woocommerce, mailchimp, casl
Requires at least: 3.9.1
Tested up to: 3.9.1
Stable tag: 1.0.0
License: GPLv3

MailChimp integration for WooCommerce with CASL support.

== Description ==

WooCommerce MailChimp CASL provides MailChimp integration for 
WooCommerce with CASL compliance and theme integration.

Automatically subscribe customers to a designated MailChimp list upon 
order creation or order completion. This can be done quietly or based 
on the user's consent with several opt-in settings that support 
canadian and international opt-in laws.

### Features ###

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

== Installation ==

1. Upload or extract the `woocommerce-mailchimp-casl` folder to your site's 
   `/wp-content/plugins/` directory. You can also use the *Add new* 
   option found in the *Plugins* menu in WordPress.  
2. Enable the plugin from the *Plugins* menu in WordPress.

= Usage =

1. Go to WooCommerce > Settings > Integration > MailChimp CASL
2. First, enable the plugin and set your MailChimp API Key and hit save.
3. Select whether you want customers to be subscribed to your MailChimp 
   list after order creation or order completion (there's a difference 
   in WooCommerce).
4. Next, select your MailChimp list and hit save.
5. That's it, now customers who purchase products from your WooCommerce 
   store will automatically be subscribed 
   to the selected list (and optional interest groups) in MailChimp!
6. Optionally you can also add theme integration by display a request
   to sign up. To turn on enable template integration and hit save.
7. Once turned on add the following function in your theme/template
   in the appropriate sectio of code  :

    if ( function_exists( 'mailchimp_casl_template' ) ) {
        mailchimp_casl_template();
    }

8. Optionally modify theme integration by copying the contents of the
   template directory to your theme/template and changing those files 
   there.

== Changelog ==

= 1.0.0 =
* This is the first public release.

