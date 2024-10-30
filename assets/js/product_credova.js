var apiusername = product_credova_params.credova_details.api_username;
var enabled = product_credova_params.credova_details.enabled;
var testmode = product_credova_params.credova_details.testmode;
var aslowaslist = product_credova_params.credova_details.aslowaslist;
var min_finance_amount = product_credova_params.credova_details.min_finance_amount;
var max_finance_amount = product_credova_params.credova_details.max_finance_amount;
var myajaxurl = myAjax.ajaxurl;
var flow_type = product_credova_params.credova_details.flow_type;
var popup_type = product_credova_params.credova_details.popup_type;
if (enabled == 'yes' && apiusername) {
    if (testmode == 0 || testmode == 'no' || testmode == 'NO') {
        CRDV.plugin.config({
            environment: CRDV.Environment.Production,
            store: apiusername,
            minAmount: min_finance_amount,
            maxAmount: max_finance_amount,
            type: popup_type
        });
    } else {
        CRDV.plugin.config({
            environment: CRDV.Environment.Sandbox,
            store: apiusername,
            minAmount: min_finance_amount,
            maxAmount: max_finance_amount,
            type: popup_type
        });
    }
    /* For product Page (Some Stores) */
    if (jQuery('.single-product').length > 0 && jQuery('.sidebar .cart-form .woocommerce-Price-amount').length > 0) {
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: myajaxurl,
            data: {
                action: "credova_list_table"
            },
            success: function(response) {
                var credova_not_display = response;
                for (i = 0; i < credova_not_display.length; i++) {
                    var credova_prd_class = credova_not_display[i];
                    jQuery(".type-product." + credova_prd_class).addClass("credova_not_display");
                }
            }
        });
        var __PRICE = jQuery('.sidebar .cart-form .woocommerce-Price-amount').first().text().replace(/\$,?/g, "");
        __PRICE = __PRICE.replace(/\,?/g, "");
        var __FINAL_PRICE = parseInt(__PRICE);
        setTimeout(function() {
            if (__FINAL_PRICE >= min_finance_amount && __FINAL_PRICE <= max_finance_amount) {
                jQuery('.cart-form .price').after('<p class="credova-response-amount"></p>');
                jQuery(".credova-response-amount").attr('data-amount', __FINAL_PRICE);
                jQuery(".credova-response-amount").attr('data-type', 'popup');
                jQuery('.credova_not_display .credova-response-amount').remove();
                CRDV.plugin.addEventListener(function(e) {
                    if (e.eventName === CRDV.EVENT_USER_WAS_APPROVED) {
                        localStorage.setItem('credovaPublicId', e.eventArgs.publicId)
                        jQuery.cookieStorage.set('credovaPublicId', e.eventArgs.publicId);
                    }
                });
                CRDV.plugin.inject("credova-response-amount");
            }
        }, 1000);

        //Change Variation
        jQuery(".variations_form select, .variations_form input").change(function(){
            var __PRICE = jQuery('.woocommerce-Price-amount').first().text().replace(/\$,?/g, "");
                __PRICE = __PRICE.replace(/\,?/g, "");
            var __FINAL_PRICE = parseInt(__PRICE);
            var selector = (jQuery(".credova-button").length == 1) ? "credova-button" : "credova-response-amount";
            jQuery("."+selector).attr('data-amount', __FINAL_PRICE);
            jQuery(".crdv-button").remove();
            CRDV.plugin.inject(selector);
        });
    }
    CRDV.plugin.inject("credova-response-amount");
}