/*
  ==================================================
  		mailchimp casl wc js script
  		Copyright 2014 Draekko, All rights reserved.
  		Licensed GPLv3.
  ==================================================
 */

window.onload=function(){
    (function($) {
        $('input[type=button]#mc_casl_csv_export.button-secondary').off('click').click( function(e) {
            var data = {
                'action': 'mailchimp_casl_download_csv',
                'mc_casl_download_csv': true
            };

            $.post( ajaxurl, data, function( response ) {
                if ( response.indexOf('ERROR') > -1 ) {
                    alert('ERROR: Unable to get file for download.');
                } else {
                    document.location = response.trim();
                }
            });
        });
    }(jQuery));
};
