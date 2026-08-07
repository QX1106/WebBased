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

    // Autofocus first input, or first input after an error
    let $target = $('form input, form select, form textarea').not('[type=hidden], [type=button], [type=submit]').first();
    const $err = $('.err').first();
    if ($err.length) {
        const $prev = $err.prev();
        $target = $prev.is('input, select, textarea') ? $prev : $prev.find('input, select, textarea').first();
    }
    if ($target && $target.length) $target.trigger('focus');

    // Reset button reloads the page (clears sticky form + errors)
    $('[type=reset]').on('click', function (e) {
        e.preventDefault();
        location.href = location.pathname;
    });

    // Photo preview on file input inside label.upload
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
