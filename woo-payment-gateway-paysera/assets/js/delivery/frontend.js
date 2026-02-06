jQuery(document).ready(function ($) {

    const payseraShippingSelectedClass = 'paysera-shipping-terminal-selected';

    $('.paysera-delivery-terminal-city-selection').select2();
    $('.paysera-delivery-terminal-country-selection').select2();
    $('.paysera-delivery-terminal-location-selection').select2();

    $(document).on(
        'added_to_cart removed_from_cart updated_cart_totals',
        function () {
            $('.paysera-delivery-error').each(function () {
                $(this).parent().parent().children().attr('disabled', true);
            });
        }
    );

    $(document).on('updated_shipping_method updated_checkout cfw_updated_checkout', function () {
        $('ul#shipping_method li').each(function () {
            if ($(this).find('input').val().includes('paysera')) {
                $(this).addClass('paysera_shipping_method');
            }
        });
    });

    let isBillingCityOrStateChanged = false;

    $('#billing_city,#billing_state').on('input', function () {
        isBillingCityOrStateChanged = true;
    });

    $(document).on('updated_checkout cfw_updated_checkout', function () {
        let shippingCountryId,
            shippingCountry,
            shippingCity,
            shippingCountryOption,
            shippingState;

        let isShippingToAnotherAddressEnabled = $(
            '#ship-to-different-address-checkbox'
        ).is(':checked');

        $('.paysera-delivery-terminal-country').hide();
        $('.paysera-delivery-terminal-city').hide();
        $('.paysera-delivery-terminal-location').hide();

        shippingCountry = $('#billing_country');
        shippingCity = $('#billing_city').val();
        shippingState = $('#billing_state').val();

        if (isShippingToAnotherAddressEnabled === true) {
            shippingCountry = $('#shipping_country');
            shippingCity = $('#shipping_city').val();
            shippingState = $('#shipping_state').val();
        }

        if (typeof shippingState === 'undefined') {
            shippingState = '';
        }

        $('.paysera-delivery-error').each(function () {
            $(this).parent().parent().children().attr('disabled', true);
        });

        shippingCountryOption = shippingCountry.find('option');
        shippingCountryId = shippingCountry.val();

        if (
            typeof shippingCountry.val() === 'undefined' ||
            shippingCountry.val() === null
        ) {
            shippingCountryId = shippingCountryOption.eq(1).val();
        }

        let paysera_terminal_country = $('.paysera-delivery-terminal-country');
        let paysera_terminal_country_selection = $(
            '.paysera-delivery-terminal-country-selection'
        );
        let shipping_methods = {};

        $(
            'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]'
        ).each(function () {
            shipping_methods[$(this).data('index')] = $(this).val();
        });

        if (Object.keys(shipping_methods).length > 0) {
            let shipping_methods_keys = Object.keys(shipping_methods);
            let shipping_method = $.trim(
                shipping_methods[shipping_methods_keys[0]]
            );
            let paysera_delivery_method = 'paysera_delivery';

            if (shipping_method.indexOf(paysera_delivery_method) !== -1) {
                paysera_terminal_country_selection.val('default');
                paysera_terminal_country_selection.trigger('change');
                let paysera_delivery_terminal_method = 'terminal';

                if (shipping_method.indexOf(paysera_delivery_terminal_method) !== -1) {
                    document.body.classList.add(payseraShippingSelectedClass);
                    paysera_terminal_country_selection.empty();
                    $.ajax({
                        type: 'POST',
                        url: ajax_object.ajaxurl,
                        data: {
                            action: 'change_paysera_method',
                            shipping_method: shipping_method,
                        },
                        success: function (response) {
                            let countries = JSON.parse(response);
                            let newOption = new Option(
                                countries['default'],
                                'default',
                                true,
                                true
                            );
                            paysera_terminal_country_selection
                                .append(newOption)
                                .trigger('change');

                            $.each(countries, function (index, item) {
                                if (index === 'default') {
                                    return;
                                }

                                let newCountryOption;

                                if (index === shippingCountryId) {
                                    newCountryOption = new Option(
                                        item,
                                        index,
                                        false,
                                        true
                                    );

                                    let city_container = $(
                                        '.paysera-delivery-terminal-city'
                                    );

                                    $.ajax({
                                        type: 'POST',
                                        url: ajax_object.ajaxurl,
                                        data: {
                                            action: 'change_paysera_country',
                                            country: shippingCountryId,
                                            shipping_method: shipping_method,
                                        },
                                        success: function (response) {
                                            let foundCity = null;
                                            let data = JSON.parse(response);
                                            let cities = data.cities;
                                            let sessionTerminal =
                                                data.session_terminal;
                                            let newOption = new Option(
                                                cities['default'],
                                                'default',
                                                true,
                                                true
                                            );

                                            $(
                                                '.paysera-delivery-terminal-city-selection'
                                            )
                                                .append(newOption)
                                                .trigger('change');
                                            if (
                                                !isBillingCityOrStateChanged &&
                                                sessionTerminal.city
                                            ) {
                                                foundCity = Object.keys(
                                                    cities
                                                ).find(
                                                    (city) =>
                                                        removeDiacritics(
                                                            city
                                                        ).toLowerCase() ===
                                                        removeDiacritics(
                                                            sessionTerminal.city
                                                        ).toLowerCase()
                                                );
                                            }

                                            if (!foundCity) {
                                                foundCity = Object.keys(
                                                    cities
                                                ).find((city) => {
                                                    city =
                                                        removeDiacritics(
                                                            city
                                                        ).toLowerCase();
                                                    shippingCity =
                                                        removeDiacritics(
                                                            shippingCity
                                                        ).toLowerCase();
                                                    shippingState =
                                                        removeDiacritics(
                                                            shippingState
                                                        ).toLowerCase();

                                                    return (
                                                        city === shippingCity ||
                                                        city ===
                                                        shippingState ||
                                                        city ===
                                                        shippingState +
                                                        ' apskr.'
                                                    );
                                                });
                                                isBillingCityOrStateChanged = false;
                                            }

                                            generateCityOptions(
                                                cities,
                                                shipping_method,
                                                foundCity,
                                                sessionTerminal.location
                                            );
                                            city_container.show();
                                        },
                                        error: function () {
                                            alert(
                                                'There was an error while fetching the data.'
                                            );
                                        },
                                    });
                                } else {
                                    newCountryOption = new Option(
                                        item,
                                        index,
                                        false,
                                        false
                                    );
                                }

                                paysera_terminal_country_selection
                                    .append(newCountryOption)
                                    .trigger('change');
                            });
                        },
                        error: function () {
                            alert(
                                'There was an error while fetching the data.'
                            );
                        },
                    });

                    document.body.classList.add(payseraShippingSelectedClass);
                    paysera_terminal_country.show();
                } else {
                    document.body.classList.remove(payseraShippingSelectedClass);
                    paysera_terminal_country.hide();
                }
            } else {
                document.body.classList.remove(payseraShippingSelectedClass);
                paysera_terminal_country.hide();
            }
            $('.paysera-delivery-terminal-city').hide();
            $('.paysera-delivery-terminal-location').hide();
            $('.paysera-delivery-terminal-city-selection').empty();
            $('.paysera-delivery-terminal-location-selection').empty();
        }
    });

    function generateCityOptions(
        cities,
        shippingMethod,
        selectedCity,
        selectedLocation = null
    ) {
        const elTerminalCitySelection = $(
            '.paysera-delivery-terminal-city-selection'
        );
        let newOption = new Option(cities['default'], 'default', true, true);

        elTerminalCitySelection.empty();
        elTerminalCitySelection.append(newOption).trigger('change');

        $.each(cities, function (index, item) {
            if (index === 'default') {
                return;
            }

            let newCityOption = new Option(item, index, false, false);

            if (item === selectedCity) {
                let locationContainer = $(
                    '.paysera-delivery-terminal-location'
                );

                newCityOption.selected = true;

                $.ajax({
                    type: 'POST',
                    url: ajax_object.ajaxurl,
                    data: {
                        action: 'change_paysera_city',
                        country: $(
                            '.paysera-delivery-terminal-country-selection'
                        ).select2('data')[0]['id'],
                        city: selectedCity,
                        shipping_method: shippingMethod,
                    },
                    success: function (response) {
                        let terminals = JSON.parse(response);
                        let newOption = new Option(
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

                            newOption = new Option(
                                item,
                                index,
                                false,
                                selectedLocation === index
                            );

                            $('.paysera-delivery-terminal-location-selection')
                                .append(newOption)
                                .trigger('change');
                        });

                        locationContainer.show();
                    },
                    error: function () {
                        alert('There was an error while fetching the data.');
                    },
                });
            }

            elTerminalCitySelection.append(newCityOption).trigger('change');
        });
    }

    function removeDiacritics(str) {
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
});

if (payseraDeliveryFrontEndData.isTestModeEnabled === "1") {
    jQuery(document).ready(function ($) {
        const noticeBox = $('.paysera-delivery-testmode-notice');
        function checkSelectedMethods() {
            const selectedShipping = $('input[name^="shipping_method"]:checked').val();
            const selectedPayment = $('input[name="payment_method"]:checked').val();

            if (
                selectedShipping?.includes('paysera_delivery') &&
                selectedPayment !== 'paysera'
            ) {
                noticeBox.show();
            } else {
                noticeBox.hide();
            }
        }

        checkSelectedMethods();
        $(document.body).on('updated_checkout', function() {
            checkSelectedMethods();
        });
        $('form.checkout').on('change', 'input[name="payment_method"]', checkSelectedMethods);
    });
}
