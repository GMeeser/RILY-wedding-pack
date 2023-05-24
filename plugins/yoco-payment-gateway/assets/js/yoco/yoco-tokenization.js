jQuery( document ).ready( function() {
    if ( typeof BlackbirdSDK == 'undefined' ) {
        console.error( "Couldn't find Blackbird SDK" );
        showYocoError( wc_yoco_tokenization_params.frontendResourcesError );
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

    var blackbirdWeb = new window.BlackbirdSDK({
        publicKey:  wc_yoco_tokenization_params.publicKey,
    });

    blackbirdWeb.submit({
        ...wc_yoco_tokenization_params.popUpConfiguration,
        callback: function ( result ) {
            var str = {
                'action'    : 'ajax_bb_verify_payment',
                'id'        : result.id,
                'order_id'  : wc_yoco_tokenization_params.order_id,
                'nonce'     : wc_yoco_tokenization_params.nonce,
            };
            if ( result.error ) {
              showYocoError( result.error );
              console.error( result.error );
              return;
            }
            jQuery.ajax({
                url: wc_yoco_tokenization_params.url,
                type: 'post',
                data: str,
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
                    showYocoError( wc_yoco_tokenization_params.frontendNetworkError );
                }
            });
        }
    })
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
        wc_yoco_tokenization_params.frontendErrorAction + '</a>';
    jQuery('.woocommerce .order_details').before( div_error );
    jQuery('#yoco_retry_button').click( function () {
        location.reload();
    });
}
