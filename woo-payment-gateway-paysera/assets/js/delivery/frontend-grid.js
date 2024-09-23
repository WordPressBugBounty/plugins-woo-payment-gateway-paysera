jQuery(document).ready(function ($) {
    $(document).on('updated_shipping_method updated_checkout cfw_updated_checkout', function () {
        $(
            'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]'
        ).each(function () {
            $(this).parent().addClass('active-paysera-delivery');
        });
    });
});
