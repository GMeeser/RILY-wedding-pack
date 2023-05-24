jQuery(document).ready( function() {
    if (typeof BlackbirdSDK == "undefined") {
      console.error( "Couldn't find Blackbird SDK" );
      showYocoError( yoco_params.frontendResourcesError );
      return;
    }

    var blackbirdWeb = new window.BlackbirdSDK({
      publicKey:  yoco_params.publicKey,
      id: yoco_params.paymentId
    });

    jQuery( 'body' ).addClass( '.overlay' );
    jQuery( 'header' ).addClass( 'pop-up-close' );
    var configuration = jQuery.extend({},
      yoco_params.popUpConfiguration,
      {
      onClose: function () {
        window.location.href = yoco_params.checkout_url
      },
      callback: function (result) {
          jQuery('body').addClass('processing')
            .block({
              message: '<div class="yoco_woocommerce_loader"></div>',
              blockMsgClass: 'yoco_woocommerce_block_msg',
              overlayCSS: {
                background: '#000000',
                opacity: 0.79,
              },
            });

          if (result.error) {
            showYocoError( result.error );
            console.error( result.error );
            return;
          }

          var payload = {
            'action': 'ajax_bb_verify_payment',
            'id': result.id,
            'order_id': yoco_params.order_id,
            'nonce': yoco_params.nonce,
          };

          if (
            result.hasOwnProperty('paymentMethod') &&
            result.hasOwnProperty('source') &&
            result.source.hasOwnProperty('card') &&
            result.source.card.hasOwnProperty('expiryMonth') &&
            result.source.card.hasOwnProperty('expiryYear') &&
            result.source.card.hasOwnProperty('maskedCard') &&
            result.source.card.hasOwnProperty('scheme')
          ) {
            payload.token = result.paymentMethod;
            payload.card_expiry_month = result.source.card.expiryMonth;
            payload.card_expiry_year = result.source.card.expiryYear;
            payload.card_last_4_digits = result.source.card.maskedCard.substr(-4);
            payload.card_type = result.source.card.scheme;
          }

          jQuery.ajax({
              url: yoco_params.url,
              type: "post",
              data: payload,
              success: function (data) {
                  if (data.redirect) {
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
                showYocoError( yoco_params.frontendNetworkError );
              }
          });
    }})

    blackbirdWeb.showPopup(configuration)
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
    yoco_params.frontendErrorAction + '</a>';
  jQuery('.woocommerce .order_details').before( div_error );
  jQuery('#yoco_retry_button').click( function () {
    location.reload();
  });
}
