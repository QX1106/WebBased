$(function () {

    // Any element with [data-get] navigates to the given URL (or reload) on click
    $('[data-get]').on('click', function () {
        const url = $(this).data('get');
        location.href = url || location.href;
    });

    // Any element with [data-post] submits a POST request on click
    $('[data-post]').on('click', function () {
        const url = $(this).data('post') || location.href;
        const $form = $('<form>').attr({ method: 'POST', action: url }).appendTo('body');
        $form[0].submit();
    });

    // Autofocus first input, or the field matching 
    let $target = $('form input, form select, form textarea').not('[type=hidden], [type=button], [type=submit]').first();
    const $err = $('.err').first();
    if ($err.length && $err.attr('id')) {
        const $field = $('#' + $err.attr('id').replace(/^err_/, ''));
        if ($field.length) $target = $field;
    }
    if ($target && $target.length) $target.trigger('focus');

    // Reset button 
    $('[type=reset]').on('click', function (e) {
        e.preventDefault();
        location.reload();
    });

    // Phone validation
    $('#phone').on('input', function () {
        const $errEl = $('#err_phone');
        if (!$errEl.length) return;

        const digits = this.value.replace(/[\s\-]/g, '');
        const pattern = /^(\+?60|0)[0-9]{8,10}$/;

        if (this.value === '' || pattern.test(digits)) {
            $errEl.text('').removeClass('err');
        } else {
            $errEl.text('Must be a valid Malaysian phone number, e.g. 012-3456789 or +60123456789').addClass('err');
        }
    });

    // Photo preview
    $('label.upload input[type=file]').on('change', function () {
        const file = this.files[0];
        const $img = $(this).closest('label').find('img').first();
        if (!$img.length) return;
        if (!$img.data('src')) $img.data('src', $img.attr('src'));
        if (file && file.type.startsWith('image/')) {
            $img.attr('src', URL.createObjectURL(file));
        } else {
            $img.attr('src', $img.data('src'));
            $(this).val('');
        }
    });
});
