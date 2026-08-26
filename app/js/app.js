$(function () {

    // Confirm dialog 
    $('[data-confirm]').on('click', function (e) {
        const text = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(text)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // Export dropdown 
    $(document).on('click', '[data-toggle-dropdown]', function (e) {
        e.stopPropagation();
        $(this).closest('.export-dropdown').toggleClass('open');
    });
    $(document).on('click', function () {
        $('.export-dropdown.open').removeClass('open');
    });

    // Send order receipt email to customer
    $(document).on('click', '#send-receipt-email', function () {
        const $btn = $(this);
        const orderId = $btn.data('order-id');
        const $status = $('#send-receipt-status');
        const originalText = $btn.text();

        $btn.prop('disabled', true).text('Sending...');
        $status.text('').removeClass('err ok');

        $.post('send-receipt-email.php', { id: orderId }, function (data) {
            $status.text(data.message).addClass(data.ok ? 'ok' : 'err');
        }, 'json').fail(function () {
            $status.text('Could not send the email. Please try again.').addClass('err');
        }).always(function () {
            $btn.prop('disabled', false).text(originalText);
        });
    });

    // Admin sidebar
    $('#sidebar-toggle').on('click', function () {
        const hidden = $('body').toggleClass('sidebar-hidden').hasClass('sidebar-hidden');
        localStorage.setItem('sidebar-hidden', hidden ? '1' : '0');
    });

    // Show/hide cancel-reason fields 
    function syncCancelReasonFields() {
        const $orderStatus = $('#order_status');
        if (!$orderStatus.length) return;
        $('#cancel-reason-wrap').toggle($orderStatus.val() === 'Cancelled');
        $('#cancel-other-wrap').toggle($('#cancel_reason').val() === 'Other');
    }
    $(document).on('change', '#order_status, #cancel_reason', syncCancelReasonFields);
    syncCancelReasonFields();

    // Update order status 
    $(document).on('submit', '#order-status-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const orderId = $form.closest('#order-update-section').data('order-id');

        $.ajax({
            url: 'detail.php?id=' + orderId,
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function (data) {
            if (data.ok) {
                $('#order-status-cell').text(data.status);
                $('#order-timeline').html(data.timeline_html);
                $('#order-update-section').html(data.update_html);
            } else {
                $.each(data.errors, function (key, msg) {
                    $('#err_' + key).text(msg).addClass('err');
                });
            }
        }).fail(function () {
            alert('Could not update the order status. Please try again.');
        });
    });

    // Navigate to URL
    $('[data-get]').on('click', function () {
        const url = $(this).data('get');
        location.href = url || location.href;
    });

    // Submit a POST request on click ([data-post])
    $('[data-post]').on('click', function () {
        const url = $(this).data('post') || location.href;
        const $form = $('<form>').attr({ method: 'POST', action: url }).appendTo('body');
        $form[0].submit();
    });

    // Autofocus first/error field
    let $target = $('form input, form select, form textarea').not('[type=hidden], [type=button], [type=submit], [data-no-autofocus]').first();
    const $err = $('.err').first();
    if ($err.length && $err.attr('id')) {
        const $field = $('#' + $err.attr('id').replace(/^err_/, ''));
        if ($field.length) $target = $field;
    }
    if ($target && $target.length) {
        $target.trigger('focus');
        // Focusing a pre-filled text field puts the caret at the start by
        // default — move it to the end instead, so typing continues from
        // where the value left off rather than jumping to the front.
        const el = $target[0];
        if (typeof el.setSelectionRange === 'function' && (el.type === 'text' || el.type === 'search')) {
            const len = el.value.length;
            el.setSelectionRange(len, len);
        }
    }

    // Reset button
    $('[type=reset]').on('click', function (e) {
        e.preventDefault();
        location.reload();
    });

    // Live username/email availability check 
    $('[data-check-available]').each(function () {
        const $input = $(this);
        const field = $input.data('check-available');
        const $status = $('#err_' + $input.attr('id'));
        const label = field === 'username' ? 'Username' : 'Email';
        let timer = null;

        $input.on('input', function () {
            clearTimeout(timer);
            const value = $input.val().trim();
            if (!value) { $status.text('').removeClass('err ok'); return; }

            timer = setTimeout(function () {
                $.getJSON('/member/check-available.php', { field: field, value: value }, function (data) {
                    if ($input.val().trim() !== value) return; // stale response, user kept typing
                    if (data.available === true) {
                        $status.text(label + ' is available').removeClass('err').addClass('ok');
                    } else if (data.available === false) {
                        $status.text(label + ' is already taken').removeClass('ok').addClass('err');
                    }
                });
            }, 400);
        });
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

    // Password show/hide toggle. Pages just need an empty
    // <button class="toggle-pw" data-target="fieldId"></button> right
    // after the password <input> — no PHP-generated SVGs or per-page
    // <script> needed anymore.
    const EYE_OPEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
    const EYE_CLOSED = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 5.1A11 11 0 0 1 12 5c7 0 11 7 11 7a13.4 13.4 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2 12 2 12s4 7 11 7c1.4 0 2.6-.2 3.7-.6"/></svg>';

    $('.toggle-pw').each(function () {
        $(this).html(EYE_OPEN);
    });

    $('.toggle-pw').on('click', function () {
        const $btn = $(this);
        const $input = $('#' + $btn.data('target'));
        const isHidden = $input.attr('type') === 'password';
        $input.attr('type', isHidden ? 'text' : 'password');
        $btn.html(isHidden ? EYE_CLOSED : EYE_OPEN);
    });

    // Photo upload
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
            // Avoid re-entering: input.trigger('click') below bubbles back up here
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
