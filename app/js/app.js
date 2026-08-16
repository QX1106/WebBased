$(function () {

    // Any element with [data-confirm] shows a confirm dialog before its
    // default action (form submit, link navigation, etc.) is allowed to proceed
    $('[data-confirm]').on('click', function (e) {
        const text = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(text)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // Sidebar hamburger toggle (Admin layout only) — lives in its own
    // fixed top strip, outside both the sidebar and the main content, so
    // it never overlaps either one. State is remembered across page loads
    // via localStorage (applied early, in _head.php, to avoid a flash of
    // the wrong state before this script runs).
    $('#sidebar-toggle').on('click', function () {
        const hidden = $('body').toggleClass('sidebar-hidden').hasClass('sidebar-hidden');
        localStorage.setItem('sidebar-hidden', hidden ? '1' : '0');
    });

    // Order status update (order/detail.php only, no-op elsewhere): picking
    // "Cancelled" reveals the reason dropdown, and picking "Other" within
    // that reveals the free-text field.
    function syncCancelReasonFields() {
        const $orderStatus = $('#order_status');
        if (!$orderStatus.length) return;
        $('#cancel-reason-wrap').toggle($orderStatus.val() === 'Cancelled');
        $('#cancel-other-wrap').toggle($('#cancel_reason').val() === 'Other');
    }
    $('#order_status, #cancel_reason').on('change', syncCancelReasonFields);
    syncCancelReasonFields();

    // Any element with [data-get] navigates to the given URL (or reload) on click
    $('[data-get]').on('click', function () {
        const url = $(this).data('get');
        location.href = url || location.href;
    });

    // Any element with [data-print] opens the browser print dialog (used for
    // "Download Receipt" — user picks "Save as PDF" as the destination)
    $('[data-print]').on('click', function () {
        window.print();
    });

    // Any element with [data-post] submits a POST request on click
    $('[data-post]').on('click', function () {
        const url = $(this).data('post') || location.href;
        const $form = $('<form>').attr({ method: 'POST', action: url }).appendTo('body');
        $form[0].submit();
    });

    // Autofocus first input, or the field matching the error (if any).
    // [data-no-autofocus] opts a field out of the *default* first-field pick
    // (e.g. a form that isn't the main point of the page) without blocking
    // the error-jump below — seeing the actual error still matters more.
    let $target = $('form input, form select, form textarea').not('[type=hidden], [type=button], [type=submit], [data-no-autofocus]').first();
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

    // Photo upload (register/edit member): click anywhere in the zone or
    // drag & drop a file onto it — the native file input is hidden and only
    // triggered programmatically, so there's one clear target instead of a
    // separate "Choose File" button. "Clear selection" undoes just the photo
    // pick (back to the existing photo, if editing) without resetting the
    // rest of the form the way the page Reset button would.
    $('.photo-drop').each(function () {
        const $zone = $(this);
        const $input = $zone.find('input[type=file]');
        const $img = $zone.find('img').first();
        const $hint = $zone.find('.photo-drop-hint');
        const $clear = $zone.find('.photo-drop-clear');
        const $err = $zone.closest('form').find('#err_photo');
        const originalSrc = $img.attr('src') || '';
        const maxBytes = 3 * 1024 * 1024;

        function setError(msg) {
            if (!$err.length) return;
            $err.text(msg || '').toggleClass('err', !!msg);
        }

        function accept(file) {
            if (!file.type.startsWith('image/')) { setError('Must be an image file'); return false; }
            if (file.size > maxBytes) { setError('Max size 3MB'); return false; }
            setError('');
            return true;
        }

        function showFile(file) {
            $img.attr('src', URL.createObjectURL(file)).show();
            $hint.hide();
            $clear.show();
        }

        function clearSelection() {
            $input.val('');
            setError('');
            if (originalSrc) {
                $img.attr('src', originalSrc).show();
            } else {
                $img.hide();
                $hint.show();
            }
            $clear.hide();
        }

        $input.on('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (accept(file)) showFile(file); else $input.val('');
        });

        $zone.on('click', function (e) {
            // e.target is the input itself when this fires because our own
            // input.trigger('click') below bubbled back up — without this
            // check that re-enters this same handler forever
            if (e.target === $input[0]) return;
            if ($(e.target).closest('.photo-drop-clear').length) return;
            $input.trigger('click');
        });
        $zone.on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $input.trigger('click');
            }
        });

        $zone.on('dragover', function (e) {
            e.preventDefault();
            $zone.addClass('dragover');
        });
        $zone.on('dragleave', function () {
            $zone.removeClass('dragover');
        });
        $zone.on('drop', function (e) {
            e.preventDefault();
            $zone.removeClass('dragover');
            const file = e.originalEvent.dataTransfer.files[0];
            if (!file || !accept(file)) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            $input[0].files = dt.files;
            showFile(file);
        });

        $clear.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            clearSelection();
        });
    });
});
