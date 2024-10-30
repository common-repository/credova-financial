jQuery(function($) {
    var checkoutForm = $('form.checkout');
    checkoutForm.on('click', 'input[name="payment_method"]', function() {
        if ($('#payment_method_credova').prop('checked')) {
            $('#place_order').text('Continue with Credova')
            $(".checkout-credova-slide").slideDown("slow");
        }
    })
})