jQuery(document).ready(function ($) {
    $('.paysera-delivery-terminal-country-selection').on(
        'select2:select',
        function (e) {
            const data = e.params.data;
            const country = data['id'];
            const responseContainer = $('.paysera-delivery-terminal-city');

            $('.paysera-delivery-terminal-location').hide();
            $('.paysera-delivery-terminal-location-selection').empty();

            if (country === 'default') {
                responseContainer.hide();
                return;
            }

            $('.paysera-delivery-terminal-city-selection').empty();

            const shippingMethod = getShippingMethod();

            $.ajax({
                type: 'POST',
                url: ajax_object.ajaxurl,
                data: {
                    action: 'change_paysera_country',
                    country: data['id'],
                    shipping_method: shippingMethod,
                },
                success: function (response) {
                    const cities = JSON.parse(response).cities;
                    const newOption = new Option(
                        cities['default'],
                        'default',
                        true,
                        true
                    );

                    $('.paysera-delivery-terminal-city-selection')
                        .append(newOption)
                        .trigger('change');

                    $.each(cities, function (index, item) {
                        if (index === 'default') {
                            return;
                        }

                        const newOption = new Option(item, index, false, false);

                        $('.paysera-delivery-terminal-city-selection')
                            .append(newOption)
                            .trigger('change');
                    });

                    responseContainer.show();
                },
                error: function () {
                    alert('There was an error while fetching the data.');
                },
            });
        }
    );

    $('.paysera-delivery-terminal-city-selection').on(
        'select2:select',
        function (e) {
            const data = e.params.data;
            const city = data['id'];
            const responseContainer = $('.paysera-delivery-terminal-location');

            if (city === 'default') {
                responseContainer.hide();
                return;
            }

            $('.paysera-delivery-terminal-location-selection').empty();

            const shippingMethod = getShippingMethod();

            $.ajax({
                type: 'POST',
                url: ajax_object.ajaxurl,
                data: {
                    action: 'change_paysera_city',
                    country: $(
                        '.paysera-delivery-terminal-country-selection'
                    ).select2('data')[0]['id'],
                    city: data['id'],
                    shipping_method: shippingMethod,
                },
                success: function (response) {
                    const terminals = JSON.parse(response);
                    const newOption = new Option(
                        terminals['default'],
                        'default',
                        true,
                        true
                    );

                    $('.paysera-delivery-terminal-location-selection')
                        .append(newOption)
                        .trigger('change');

                    $.each(terminals, function (index, item) {
                        if (index === 'default') {
                            return;
                        }

                        const newOption = new Option(item, index, false, false);

                        $('.paysera-delivery-terminal-location-selection')
                            .append(newOption)
                            .trigger('change');
                    });

                    responseContainer.show();
                },
                error: function () {
                    alert('There was an error while fetching the data.');
                },
            });
        }
    );

    $('.paysera-delivery-terminal-location-selection').on(
        'select2:select',
        function (e) {
            const data = e.params.data;
            const terminal = data['id'];

            if (terminal === 'default') {
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajax_object.ajaxurl,
                data: {
                    action: 'change_paysera_terminal_location',
                    terminal: terminal,
                },
                error: function () {
                    alert('There was an error while fetching the data.');
                },
            });
        }
    );

    function getShippingMethod() {
        let shippingMethods = {};

        $(
            'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]'
        ).each(function () {
            shippingMethods[$(this).data('index')] = $(this).val();
        });

        const shippingMethodsKeys = Object.keys(shippingMethods);

        return $.trim(shippingMethods[shippingMethodsKeys[0]]);
    }
});
