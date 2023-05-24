jQuery( document ).ready( function() {
    if ( typeof BlackbirdSDK == 'undefined' ) {
        console.error( "Couldn't find Blackbird SDK" );
        showYocoError( wc_yoco_eft_params.frontendResourcesError );
        return;
    }

    jQuery( 'body' ).addClass( 'processing' )
        .block({
            message: '<div class="yoco_woocommerce_loader"></div>',
            blockMsgClass: 'yoco_woocommerce_block_msg',
            overlayCSS: {
                background: '#000000',
                opacity: 0.79,
            },
        });

	const blackbirdWeb = new window.BlackbirdSDK({
		publicKey :wc_yoco_eft_params.publicKey
	});

	blackbirdWeb.submit({
		id: wc_yoco_eft_params.popUpConfiguration.paymentId,
		paymentType: 'EFT'
	})
	.catch( function ( error ) {
		showYocoError( error );
	})
	.then( function ( result ) {
		if ( result.error ) {
			console.error( result.error );
			showYocoError( result.error.message );
			return;
		}

		if ( result.status && result.status === "cancelled"  ) {
			// user dismissed pop up
			window.location.href = wc_yoco_eft_params.checkout_url;
			return;
		}

		var payload = {
			'action'    	: 'ajax_bb_verify_payment',
			'id'        	: result.id,
			'order_id'  	: wc_yoco_eft_params.order_id,
			'nonce'     	: wc_yoco_eft_params.nonce,
			'paymentType'	: 'yoco_eft',
		};
		jQuery.ajax({
			url: wc_yoco_eft_params.payment_verification_url,
			type: 'post',
			data: payload,
			success: function ( data ) {
				if ( data.redirect ) {
					window.location = data.redirect;
					return;
				}
				showYocoError( data );
				jQuery( 'body' ).removeClass( 'processing' ).unblock();
			},
			error: function ( _, __, error ) {
				jQuery( 'body' ).removeClass( 'processing' ).unblock();
				if ( error ) {
					showYocoError( error );
					return;
				}
				showYocoError( wc_yoco_eft_params.frontendNetworkError );
			}
		});
	});
});

/**
 * Show a notice and retry button in the event of a fatal error.
 */
function showYocoError( message ) {
    var div_error = document.createElement( 'div' );
    div_error.className = 'row';
    var ul_error = document.createElement( 'ul' );
    ul_error.className = 'woocommerce-error';
    var li_error = document.createElement( 'li' );
    jQuery( li_error ).text( message );
    ul_error.append( li_error );
    div_error.append( ul_error );
    div_error.innerHTML +=
        '<a href="javascript:void(0);" class="yoco_button" id="yoco_retry_button" role="button">' +
        wc_yoco_eft_params.frontendErrorAction + '</a>';
    jQuery('.woocommerce .order_details').before( div_error );
    jQuery('#yoco_retry_button').click( function () {
        location.reload();
    });
}
