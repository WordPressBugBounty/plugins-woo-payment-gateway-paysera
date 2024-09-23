## Checkout SDK
PHP SDK facade for checkout processing

## Code style
[PSR-12](https://www.php-fig.org/psr/psr-12)

## Used
<b>Built with</b>
- [WebToPay](https://github.com/paysera/lib-webtopay)

## Installation (will be updated soon)
While in under developing:
```
{
    "require": {
        "php": ">=7.4",
        "paysera/lib-checkout-sdk-facade": "^0.1",
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/enigma-tm/lib-checkout-sdk-facade"
        }
    ]
}
```

## Use cases
- [Payment methods receiving](docs/PAYMENT_METHODS.md)
- [Redirect url receiving](docs/REDIRECT.md)
- [Callback validated data receiving](docs/VALIDATED_DATA.md)
- [Check merchant oder is paid](docs/MERCHANT_ORDER.md)


## Run tests
```
$ bash run_tests.sh
```
