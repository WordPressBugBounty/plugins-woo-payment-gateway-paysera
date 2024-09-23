jQuery(document).ready(function ($) {
    $('.paysera-configurable-select2').each(function () {
        $(this).select2({
            width: $(this).data('width'),
            placeholder: $(this).data('placeholder'),
        });
    });
});
