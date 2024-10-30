(()=>{
    "use strict";
    const { createElement, Fragment, useEffect, useRef } = window.wp.element;
    const { __ } = window.wp.i18n;
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { decodeEntities } = window.wp.htmlEntities;

    const PAYMENT_METHOD_NAME = 'credova';
    const settings = window.wc.wcSettings.getSetting('credova_data', {});

    const placeOrderButtonLabel = decodeEntities(settings.placeOrderButtonLabel) || __('Continue with Credova', 'woocommerce-gateway-credova');

    const initializeCredova = () => {
        if (typeof CRDV !== 'undefined') {
            CRDV.plugin.config({ 
                environment: settings.testmode ? CRDV.Environment.Sandbox : CRDV.Environment.Production, 
                store: settings.api_username 
            });
            CRDV.plugin.inject("credova-response-amount");
        }
    };

    const Label = () => {
        const alaRef = useRef(null);
    
        useEffect(() => {
            if (alaRef.current) {
                initializeCredova();
            }
        }, []);
    
        return createElement('div', { 
            className: 'credova-label-container',
            style: {
                display: 'flex',
                width: '100%'
            }
        },
            createElement('p', { 
                ref: alaRef,
                className: 'credova-response-amount', 
                'data-amount': settings.cart_total,  
                'data-type': 'text',
                style: {
                    margin: '0',
                    padding: '10px 0'
                }
            })
        );
    };

    const Content = () => {
        return createElement('div', { className: 'checkout-credova-slide' },
            createElement('div', { 
                id: 'as-low-as-more-info-disclaimer', 
                style: {
                    textAlign: 'center',
                    padding: '0px 19px 10px',
                    fontSize: '14px'
                }
            }, __('You will open Credova for the payment after placing your order. Your order will not be shipped until you complete the process with Credova.', 'woocommerce-gateway-credova'))
        );
    };

    const paymentMethod = {
        name: PAYMENT_METHOD_NAME,
        label: createElement(Label),
        content: createElement(Content),
        edit: createElement(Content),
        canMakePayment: () => true,
        paymentMethodId: PAYMENT_METHOD_NAME,
        placeOrderButtonLabel: placeOrderButtonLabel,
        ariaLabel: __('Credova', 'woocommerce-gateway-credova'),
        supports: {
            features: settings?.supports ?? []
        }
    };

    registerPaymentMethod(paymentMethod);
})();