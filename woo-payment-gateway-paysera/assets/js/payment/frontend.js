jQuery(document).ready(function ($) {
    $('.country_select').on('click', function () {
        let id, countryBilling, countryOption;

        $('.payment-countries').hide('slow');
        countryBilling = $('#billing_country');
        countryOption = countryBilling.find('option');
        id = countryBilling.val().toLowerCase();

        if (
            typeof countryBilling.val() === 'undefined' ||
            countryBilling.val() === null
        ) {
            id = countryOption.eq(1).val();
        }

        let idCheck = $('#' + id).attr('class');

        if (!idCheck) {
            id = 'other';
            idCheck = $('#' + id).attr('class');

            if (!idCheck) {
                id = countryOption.eq(1).val();
            }
        }

        countryOption.attr('selected', false);
        $('#paysera_country')
            .find('option[value="' + id + '"]')
            .attr('selected', true);
        $('#' + id).show('slow');
    });

    $(document).on('change', '#paysera_country', function () {
        $('.payment-countries').hide('slow');
        $('input[name="payment[pay_type]"]').prop('checked', false);
        $('#' + $('#paysera_country').val()).show('slow');
    });

    $(document).on('change', 'input[name="payment[pay_type]"]', function () {
        $('.paysera-payment-method-label').removeClass(
            'paysera-payment-active'
        );
        $(this).parent().parent().addClass('paysera-payment-active');
    });
});
