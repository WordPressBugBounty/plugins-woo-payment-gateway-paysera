function setupPayseraLogos() {
    const shippingLogos = window.data.shippingLogos || [];

    if (shippingLogos.length === 0) {
        return;
    }

    const style = document.createElement('style');

    document.getElementsByTagName('head')[0].appendChild(style);

    function applyCSS(extraCSS) {
        style.innerHTML = `${extraCSS}`;

        for (const shippingOption of Object.keys(shippingLogos)) {
            style.innerHTML += `
                .wc-block-components-radio-control__label[id*="${shippingOption}"]::before {
                    background-image: url('${shippingLogos[shippingOption].url}');
                }
            `;
        }
    }

    function applySingleShippingLogoStyles() {
        setInterval(function () {
            const elShippingPackage = document.querySelector('.wc-block-components-shipping-rates-control__package');

            if (elShippingPackage === null) {
                return;
            }

            if (elShippingPackage.querySelectorAll('.wc-block-components-radio-control__option').length > 0) {
                return;
            }

            const elShippingLabel = elShippingPackage.querySelector('.wc-block-components-radio-control__label');

            if (elShippingLabel === null) {
                return;
            }

            for (const shippingOption of Object.keys(shippingLogos)) {
                if (!elShippingLabel.innerText.startsWith(shippingLogos[shippingOption].name)) {
                    continue;
                }

                applyCSS(`
                    .wc-block-components-shipping-rates-control__package .wc-block-components-radio-control__label::before {
                        content: "";
                        display: block;
                        width: 100px;
                        padding-top: 10%;
                        background: url('${shippingLogos[shippingOption].url}') no-repeat center / contain;
                    }
                `);
            }
        }, 1000);
    }

    applyCSS('')
    applySingleShippingLogoStyles()
}

setupPayseraLogos()
