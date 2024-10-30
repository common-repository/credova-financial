window.addEventListener("DOMContentLoaded", () => {
    const PAYMENT_METHOD_RADIO_ID = "radio-control-wc-payment-method-options-credova";
    const WIDGET_BOX_ID = "credova-widget-box";
    let intervalId;

    const settings = window.wc.wcSettings.getSetting('credova_data', {});

    // Function to update widget visibility
    function updateWidgetVisibility() {
        const widgetBox = document.getElementById(WIDGET_BOX_ID);
        const credovaRadio = document.getElementById(PAYMENT_METHOD_RADIO_ID);
        if (widgetBox && credovaRadio) {
            // Only hide the widget if Credova is not an available payment method
            widgetBox.style.display = credovaRadio.disabled ? "none" : "block";
        }
    }

    // Listen for checkout updates
    jQuery(document.body).on("updated_checkout", updateWidgetVisibility);

    // Function to initialize Credova widget
    function initCredovaWidget() {
        if (typeof CRDV !== 'undefined') {
            CRDV.plugin.config({ 
                environment: settings.testmode ? CRDV.Environment.Sandbox : CRDV.Environment.Production, 
                store: settings.api_username 
            });
            CRDV.plugin.inject("credova-response-amount");
        }
    }

    // Function to create and insert the widget
    function createAndInsertWidget() {
        const totalElement = document.querySelector('.wp-block-woocommerce-checkout-order-summary-block .wc-block-components-totals-footer-item');
        if (!totalElement || document.getElementById(WIDGET_BOX_ID)) {
            return;
        }

        const widgetBox = document.createElement("div");
        widgetBox.id = WIDGET_BOX_ID;
        
        widgetBox.innerHTML = `
            <div class="credova-widget-content">
                <p class="credova-response-amount" data-amount="${settings.cart_total}" data-type="text"></p>
            </div>
        `;

        totalElement.after(widgetBox);
        
        initCredovaWidget();
        updateWidgetVisibility(); // Call this to set initial visibility

        if (intervalId) {
            clearInterval(intervalId);
        }
    }

    // Try to create and insert the widget periodically until successful
    intervalId = setInterval(createAndInsertWidget, 200);
});