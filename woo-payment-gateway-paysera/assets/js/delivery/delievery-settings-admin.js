/* global paysera_delivery_settings_admin */
(function ($, paysera_delivery_settings_admin) {
    $(function () {
        $(document.body).on('paysera_delivery_settings_validation', function () {
            let validator = new PayseraDeliverySettingsValidator(
                paysera_delivery_settings_admin.fieldNames,
                paysera_delivery_settings_admin.errors,
                paysera_delivery_settings_admin.options,
            );

            if (validator.validateAll(paysera_delivery_settings_admin.ruleSet)) {
                $('#btn-ok').attr('disabled', false);
            } else {
                $('#btn-ok').attr('disabled', true);
            }
        });
    })

    class PayseraDeliverySettingsValidator {
        errors = {}
        validators = {}
        errorsPatterns = {}
        filedNames = {}
        options = {}

        constructor(filedNames, errorsPatterns, options) {
            this.filedNames = filedNames
            this.errorsPatterns = errorsPatterns
            this.options = options
            this.validators = {
                'min': this.minValue.bind(this),
                'greater-or-equals': this.GreaterOrEquals.bind(this),
                'less-or-equals': this.LessOrEquals.bind(this),
                'is-number': this.IsNumber.bind(this),
            }
        }

        /**
         * @param {Object} ruleSet
         */
        validateAll(ruleSet) {
            if (Object.keys(ruleSet).length === 0) {
                return true;
            }

            let inputs = {};
            this.initInputs(inputs);

            if (Object.keys(inputs).length === 0) {
                return true;
            }

            let data = this.getData(inputs);


            for (const [pattern, rulesString] of Object.entries(ruleSet)) {
                let rules = rulesString.split('|'),
                    input = inputs[pattern] || null;

                if (!input) {
                    continue;
                }

                input.removeClass('paysera-delivery-input-validation-error');
                input.parent().find('.paysera-error-message').remove();

                for (const ruleString of Object.values(rules)) {
                    let [rule, params] = arrayPad(ruleString.split(':', 2), 2, '');
                    params = params.split(',').map((param) => param.trim());

                    if (typeof this.validators[rule] !== 'undefined') {
                        if (!this.validators[rule](input.val(), pattern, params, data)) {
                            input.addClass('paysera-delivery-input-validation-error');
                            this.renderError(input, this.errors[pattern]);

                            break;
                        }
                    }
                }
            }

            return Object.keys(this.errors).length === 0;
        }

        /**
         * @param {String|Float} value
         * @param {String} pattern
         * @param {Array} params
         * @returns {Boolean}
         * @constructor
         */
        minValue(value, pattern, params) {
            let min = params[0],
                result = parseFloat(value) >= parseFloat(min);

            if (!result) {
                this.errors[pattern] = this.errorsPatterns.minVal
                    .replace(':attribute', this.prepareFieldName(pattern))
                    .replace(':min', min);
            }

            return result;
        }


        /**
         * @param {String|Float} value
         * @param {String} pattern
         * @param {Array} params
         * @param {Object} data
         * @returns {Boolean}
         * @constructor
         */
        GreaterOrEquals(value, pattern, params, data) {
            let comparing = (value, lowerBound) => parseFloat(value) >= parseFloat(lowerBound);

            return this.compareNumbers(
                value,
                pattern,
                params,
                data,
                comparing,
                'greaterOrEquals'
            );
        }

        /**
         * @param {String|Float} value
         * @param {String} pattern
         * @param {Array} params
         * @param {Object} data
         * @returns {Boolean}
         * @constructor
         */
        LessOrEquals(value, pattern, params, data) {
            let comparing = (value, lowerBound) => parseFloat(value) <= parseFloat(lowerBound);

            return this.compareNumbers(
                value,
                pattern,
                params,
                data,
                comparing,
                'lessOrEquals'
            );
        }

        /**
         * @param {String|Float} value
         * @param {String} pattern
         * @returns {Boolean}
         * @constructor
         */
        IsNumber(value, pattern) {
            let nonDecimalRegex = '([^0-9\\:separator\\s\\w-]|\\:separator{2,})'
                .replaceAll(':separator', this.options.decimalSeparator)

            if ((new RegExp(nonDecimalRegex, 'gi')).test(value)) {
                this.errors[pattern] = this.errorsPatterns.i18n_decimal_error;

                return false;
            }

            if (!this.isNumeric(value)) {
                this.errors[pattern] = this.errorsPatterns.isNumber.replace(
                    ':attribute',
                    this.prepareFieldName(pattern)
                );

                return false;
            }

            return true;
        }

        /**
         * @param {String|Float} value
         * @param {String} pattern
         * @param {Array} params
         * @param {Object} data
         * @param {Function} comparing
         * @param {String} messageKey
         * @returns {Boolean}
         */
        compareNumbers(value, pattern, params, data, comparing, messageKey) {
            let fieldToCompare = params[0],
                lowerBound = data[fieldToCompare] || null,
                isValid = true;

            if (lowerBound === null || !this.isNumeric(lowerBound)) {
                return true;
            }

            if (!this.isNumeric(value) || !comparing(value, lowerBound)) {
                this.errors[pattern] = this.errorsPatterns[messageKey]
                    .replace(':attribute', this.prepareFieldName(pattern))
                    .replace(':fieldToCompare', this.prepareFieldName(fieldToCompare))
                    .replace(':valueToCompare', lowerBound);
                isValid = false;
            }

            return isValid;
        }

        /**
         * @param {String} name
         * @returns {String}
         */
        prepareFieldName(name) {
            return this.filedNames[name] || name;
        }

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isNumeric(value) {
            let regex = '^(-?(?!0\\d)\\d*(?:\\:separator\\d+)?|0?\\:separator\\d+)$'
                .replaceAll(':separator', this.options.decimalSeparator);

            return ((new RegExp(regex, 'gi')).test(value))
        }

        initInputs(inputs) {
            $(".paysera-delivery-input").each(function(){
                let filedName = $(this).data('name') || null;

                if (filedName !== null) {
                    inputs[filedName] = $(this);
                }
            });
        }

        getData(inputs) {
            let data = {};
            for (const [fieldName, input] of Object.entries(inputs)) {
                data[fieldName] = input.val();
            }

            return data;
        }

        renderError(input, message) {
            var offset = input.position();

            if (input.parent().find('.paysera-error-message').length === 0) {
                input.parent().append(
                    $('<div class="paysera-error-message">'
                    + message
                    + '</div>')
                );
                input.parent().find('.wc_error_tip').fadeIn('100');
            }
        }
    }

    function arrayPad(arr, padSize, padValue) {
        if (padSize < 0) {
            return Array(Math.max(0, -padSize)).fill(padValue).concat(arr);
        } else {
            return arr.concat(Array(Math.max(0, padSize - arr.length)).fill(padValue));
        }
    }

})(jQuery, paysera_delivery_settings_admin);
