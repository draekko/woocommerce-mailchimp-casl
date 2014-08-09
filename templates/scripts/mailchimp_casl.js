/*
  ==================================================
  		mailchimp casl js script
  		Copyright 2014 Draekko, All rights reserved.
  		Licensed GPLv3.
  ==================================================
 */


jQuery(document).ready( function () {

(function($) {

    function isValidEmailAddress(emailAddress) {
        var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
        return pattern.test(emailAddress);
    };

    function checkKey(e) {
    	if (e.keyCode == 13) {
    		if (!e) var e = window.event;
    		e.cancelBubble = true;
    		e.returnValue = false;
    		if (e.stopPropagation) {
    			e.stopPropagation();
    			e.preventDefault();
    		}
    	}
    }

    function getEmail() {
        console.log('email');
        email = $('#mc_casl_email').val();
    	return email;
    }

    function getDOB() {
        console.log('dob');
        if ($('#birthday-day').length > 0) {
    	   var dy = $('#birthday-day').val();
    	   var mt = $('#birthday-month').val();
    	   var yr = $('#birthday-year').val();
    	   var d = parseInt(dy);
    	   if (d < 10) {
    	       dy = "0"+dy;
    	   }
    	   var m = parseInt(mt);
    	   if (m < 10) {
    	       mt = "0"+mt;
        	   }
    	   return yr+"-"+mt+"-"+dy;
        }
    	return '';
    }

    function getBDay() {
        console.log('bday');
        if ($('#birthday-day').length > 0) {
    	   var dy = $('#birthday-day').val();
    	   var mt = $('#birthday-month').val();
    	   var d = parseInt(dy);
    	   if (d < 10) {
    	       dy = "0"+dy;
    	   }
    	   var m = parseInt(mt);
    	   if (m < 10) {
    	       mt = "0"+mt;
    	   }
    	   return mt+"/"+dy;
        }
    	return '';
    }

    function getFirstName() {
        console.log('fname');
        if ($('#mc_casl_first_name').length > 0) {
            var fname = $('#mc_casl_first_name').val();
            return fname;
        }
    	return '';
    }

    function getLastName() {
        console.log('lname');
        if ($('#mc_casl_last_name').length > 0) {
            var lname = $('#mc_casl_last_name').val();
            return lname;
        }
    	return '';
    }

    function getAge() {
        console.log('age');
        if ($('#birthday-day').length > 0) {
    	    var day = $('#birthday-day').val();
    	    var month = $('#birthday-month').val();
    	    var year = $('#birthday-year').val();

    	    var then = new Date(year + "/" + month + "/" + day);
            var now = new Date();

            function isLeap(year) {
                    return year % 4 == 0 && (year % 100 != 0 || year % 400 == 0);
            }

            var days = Math.floor((now.getTime() - then.getTime())/1000/60/60/24);
            var age = 0;

            for (var y = then.getFullYear(); y <= now.getFullYear(); y++){
                var daysInYear = isLeap(y) ? 366 : 365;
                if (days >= daysInYear){
                    days -= daysInYear;
                    age++;
                }
            }
            return age;
        }
        return 0;
    }

    function futureDate() {
        console.log('futuredate');
        if ($('#birthday-day').length > 0) {
            var day = $('#birthday-day').val();
            var month = $('#birthday-month').val();
            var year = $('#birthday-year').val();
            var input_time = new Date(year + "/" + month + "/" + day).getTime();
            var current_time = Math.round((new Date).getTime() / 86400000) * 86400000;
            var status_time = (input_time >= current_time)
            return status_time;
        }
        return false;
    }

    function errorDialog(errormsg) {
    	$('#mc_casl_email').text('');
    	$(".mc_casl_agreement").css("display", "block");
    	$(".mc_casl_agreement_box").css("display", "none");
        $(".mc_casl_spinner_agreement").css("display", "none");
    	$(".mc_casl_error_box").css("display", "block");

    	wz = ($(window).width() - $('.mc_casl_error_box').outerWidth())/2,
    	hz = ($(window).height() - $('.mc_casl_error_box').outerHeight())/2

    	$('.mc_casl_error_box').css({
       	position : 'fixed',
          margin: 0,
          left: wz,
          top: hz
    	});

    	$('#mc_casl_agreement_error_msg').text("ERROR: "+errormsg);
    }

    function msgDialog(dlgmsg) {
    	$('#mc_casl_email').text('');
    	$(".mc_casl_agreement").css("display", "block");
    	$(".mc_casl_agreement_box").css("display", "none");
    	$(".mc_casl_error_box").css("display", "block");
        $(".mc_casl_spinner_agreement").css("display", "none");

    	wz = ($(window).width() - $('.mc_casl_error_box').outerWidth())/2,
    	hz = ($(window).height() - $('.mc_casl_error_box').outerHeight())/2

    	$('.mc_casl_error_box').css({
           	position : 'fixed',
          margin: 0,
          left: wz,
          top: hz
    	});

    	$('#mc_casl_agreement_error_msg').text(dlgmsg);
    }

    /* CHECKOUT CODE */
    $(document).on('click', '#mailchimp_casl_opt_in_subscription', function(e) {
        e.stopPropagation();
        if($(this).is(":checked")) {
            $(".mc_casl_agreement_checkout").css('display', 'block');
            $(".mc_casl_agreement_box_checkout").css('display', 'block');
            $(".mc_casl_error_box_checkout").css('display', 'none');
            $(".mc_casl_thankyou_box_checkout").css('display', 'none');

            $('#mc_casl_agreement_check_checkout').attr("checked", false);

            wz = ($(window).width() - $('.mc_casl_agreement_box_checkout').outerWidth())/2,
            hz = ($(window).height() - $('.mc_casl_agreement_box_checkout').outerHeight())/2

            $('.mc_casl_agreement_box_checkout').css({
                position : 'fixed',
                margin: 0,
                left: wz,
                top: hz
            });
        }
    });

    $(document).on('click', '#mc_casl_cancel_checkout_thankyou_button', function(e) {
            $(".mc_casl_agreement_checkout").css('display', 'none');
            $(".mc_casl_agreement_box_checkout").css('display', 'none');
            $(".mc_casl_error_box_checkout").css('display', 'none');
            $(".mc_casl_thankyou_box_checkout").css('display', 'none');
    });

    $(document).on('click', '#mc_casl_cancel_checkout_error_button', function(e) {
            $(".mc_casl_agreement_box_checkout").css('display', 'block');
            $(".mc_casl_error_box_checkout").css('display', 'none');
            $(".mc_casl_thankyou_box_checkout").css('display', 'none');

			var wz = ($(window).width() - $('.mc_casl_agreement_box_checkout').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_agreement_box_checkout').outerHeight())/2;

			$('.mc_casl_agreement_box_checkout').css({
   			    position : 'fixed',
      		    margin: 0,
      		    left: wz,
      		    top: hz
			});
    });

    $(document).on('click', '#mailchimp_casl_checkout_button', function(e) {
        if($('#mc_casl_agreement_check_checkout').is(":checked")) {
            //$(".mc_casl_agreement_checkout").css('display', 'none');
            $(".mc_casl_agreement_box_checkout").css('display', 'none');
            $(".mc_casl_error_box_checkout").css('display', 'none');
            $(".mc_casl_thankyou_box_checkout").css('display', 'block');
        } else {
            $(".mc_casl_agreement_box_checkout").css('display', 'none');
            $(".mc_casl_error_box_checkout").css('display', 'block');
            $(".mc_casl_thankyou_box_checkout").css('display', 'none');
			var wz = ($(window).width() - $('.mc_casl_error_box_checkout').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_error_box_checkout').outerHeight())/2;

			$('.mc_casl_error_box_checkout').css({
   			    position : 'fixed',
      		    margin: 0,
      		    left: wz,
      		    top: hz
			});
        }
    });

    $(document).on('click', '#mc_casl_cancel_checkout_button', function(e) {
        e.stopPropagation();
	    $('input[type=checkbox]#mc_casl_agreement_check.mc_casl_agreement_checkmark').attr("checked", false);
        $('input[type=checkbox]#mailchimp_casl_opt_in_subscription.input-checkbox').attr("checked", false);
        $('#mc_casl_agreement_check_checkout').attr("checked", false);
        $(".mc_casl_agreement_checkout").css('display', 'none');
	});

    /* TEMPLATE CODE */

	$('input[type=text]#mc_email.mailchimp_casl_newsletter_fix').keydown(checkKey);

    $(document).on('click', 'input[type=button]#mc_casl_submit_button.mailchimp_casl_newsletter_button', function(e) {
		if ( mc_casl_integration_verify_login == true && mc_casl_integration_user_logged_in == false ) {
			errorDialog('Only users that are signed in may subscribe! You may create an account by going to the login page.');
		} else if ((requirename == true) && (getFirstName() == '')) {
			errorDialog('You must enter your name.');
		} else if ((requirename == true) && (getLastName() == '')) {
			errorDialog('You must enter your name.');
		} else if ( ( getAge() < minage ) && ( minage > 0 ) && ( checkage == true ) ) {
			errorDialog('Unfortunately you are below the required minimum age to sign up.');
		} else if ( futureDate() == true ) {
			errorDialog('Unfortunately time travelers cannot register for emails.');
		} else {
			var str_email = $('#mc_casl_email').val();
			if (str_email.length > 127) {
				errorDialog('Email is to long!');
			} else if (str_email == '') {
				errorDialog('An email is required!');
			} else {
				if (isValidEmailAddress(str_email))  {
					$('#mc_casl_agreement_email').text("Contact me at "+str_email);
					$(".mc_casl_agreement").css("display", "block");
					$(".mc_casl_agreement_box").css("display", "block");
					$(".mc_casl_error_box").css("display", "none");
	                $(".mc_casl_spinner_agreement").css("display", "none");

					$('#mc_casl_agreement_error_msg').text("");

                    wz = ($(window).width() - $('.mc_casl_agreement_box').outerWidth())/2,
                    hz = ($(window).height() - $('.mc_casl_agreement_box').outerHeight())/2

                    $('.mc_casl_agreement_box').css({
                        position : 'fixed',
                        margin: 0,
                        left: wz,
                        top: hz
    				});

					$('input[type=button].mailchimp_casl_newsletter_button.agreement_fix').css({
                        position : 'relative',
                        margin: '55px 5px 5px 0',
                        left: '0px',
                        top: '0px'
                    });

    				$('#mc_casl_agreement_check').attr('checked', false);

	                $('input[type=submit].mailchimp_casl_newsletter_submit.agreement_fix').css({
                        position : 'relative',
                        margin: '55px 5px 0 0' });

					$('input[type=submit]#mailchimp_casl_button.mailchimp_casl_newsletter_submit.agreement_fix').off('click').click( function(e) {

						if ( $( '#mc_casl_agreement_check' ).is( ":checked" ) ) {

			                var email = getEmail();
			                var dob = getDOB();
			                var bday = getBDay();
			                var fname = getFirstName();
			                var lname = getLastName();

			                $(".mc_casl_spinner_agreement").css("display", "block");

                            var data = {
		                        'action': 'mailchimp_casl_subscribe',
		                        'mc_casl_email': email,
		                        'mc_casl_dob': dob,
		                        'mc_casl_bday': bday,
		                        'mc_casl_fname' : fname,
		                        'mc_casl_lname' : lname
	                        };

                            $.post( mc_casl_ajaxurl, data, function( response ) {
                                console(response);
                                    alert(response);
                                if ( response.indexOf('ERROR') > -1 ) {
                                    errorDialog( response );
					               $(".mc_casl_spinner_agreement").css("display", "none");
                                } else {
                                    msgDialog( response );
					               $(".mc_casl_spinner_box_agreement").css("display", "none");
                                }
			                    $('input[type=button].mailchimp_casl_newsletter_button.agreement_fix').css({
                                    position : 'relative',
                          	  		margin: '55px 160px 5px 0' });
	                       	    clearSubscriptionForm();
	                        });

	                       	$( ".mc_casl_agreement" ).css( "display", "none" );

	                       	clearSubscriptionForm();
						} else {
							errorDialog( 'You must check the agreement box, thereby signaling your intent to receive further communications from this site.' );
						}
					});

				} else {
					errorDialog('A valid email is required!');
				}
			}
		}
	});

    $(document).on('click', 'input[type=button].mailchimp_casl_newsletter_button.agreement_fix', function(e) {
		$(".mc_casl_agreement").css("display", "none");
	});

	$(window).resize(function() {
		if ($('.mc_casl_agreement_box_checkout').css('display') == 'block') {
			var wz = ($(window).width() - $('.mc_casl_agreement_box_checkout').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_agreement_box_checkout').outerHeight())/2;

			$('.mc_casl_agreement_box_checkout').css({
   			position : 'fixed',
      		margin: 0,
      		left: wz,
      		top: hz
			});
		}

		if ($('.mc_casl_agreement_box').css('display') == 'block') {
			var wz = ($(window).width() - $('.mc_casl_agreement_box').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_agreement_box').outerHeight())/2;

			$('.mc_casl_agreement_box').css({
   			position : 'fixed',
      		margin: 0,
      		left: wz,
      		top: hz
			});
		}

		if ($('.mc_casl_error_box_checkout').css('display') == 'block') {
			var wz = ($(window).width() - $('.mc_casl_error_box_checkout').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_error_box_checkout').outerHeight())/2;

			$('.mc_casl_error_box_checkout').css({
   			position : 'fixed',
      		margin: 0,
      		left: wz,
      		top: hz
			});
		}

		if ($('.mc_casl_thankyou_box_checkout').css('display') == 'block') {
			var wz = ($(window).width() - $('.mc_casl_thankyou_box_checkout').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_thankyou_box_checkout').outerHeight())/2;

			$('.mc_casl_thankyou_box_checkout').css({
   			position : 'fixed',
      		margin: 0,
      		left: wz,
      		top: hz
			});
		}

		if ($('.mc_casl_error_box').css('display') == 'block') {
			var wz = ($(window).width() - $('.mc_casl_error_box').outerWidth())/2;
			var hz = ($(window).height() - $('.mc_casl_error_box').outerHeight())/2;

			$('.mc_casl_error_box').css({
   			position : 'fixed',
      		margin: 0,
      		left: wz,
      		top: hz
			});
		}
	});

    function clearSubscriptionForm() {
        var today = new Date();
    	var dd = today.getDate();
        var mm = today.getMonth()+1;
        var yy = today.getFullYear();
    	$('#mc_casl_email').val('');
    	$('#mc_casl_first_name').val('');
    	$('#mc_casl_last_name').val('');
    	$('#birthday-day').val(dd).change();
    	$('#birthday-month').val(mm).change();
    	$('#birthday-year').val(yy).change();
    }

}(jQuery, window, document));

});
