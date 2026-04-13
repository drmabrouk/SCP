jQuery(document).ready(function($) {
    // --- CSV Import / Export System ---

    $(document).on('click', '.control-export-btn', function() {
        const type = $(this).data('type');
        const $btn = $(this);
        const originalContent = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

        $.post(control_ajax.ajax_url, { action: 'control_export_data', type: type, nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                const blob = new Blob([res.data.csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", res.data.filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                $btn.prop('disabled', false).html(originalContent);
            } else {
                alert('خطأ في التصدير: ' + res.data);
                $btn.prop('disabled', false).html(originalContent);
            }
        });
    });

    $(document).on('click', '.control-import-trigger', function() {
        const type = $(this).data('type');
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.csv';
        input.onchange = e => {
            const file = e.target.files[0];
            const reader = new FileReader();
            reader.onload = event => {
                const csvData = event.target.result;
                if (confirm('هل أنت متأكد من استيراد هذه البيانات؟ سيتم تجاوز السجلات المكررة.')) {
                    $.post(control_ajax.ajax_url, { action: 'control_import_data', type: type, csv_data: csvData, nonce: control_ajax.nonce }, function(res) {
                        if (res.success) {
                            alert(res.data);
                            location.reload();
                        } else {
                            alert('خطأ في الاستيراد: ' + res.data);
                        }
                    });
                }
            };
            reader.readAsText(file);
        };
        input.click();
    });

    // --- Auth Toggling & Multi-step Registration ---

    $('#switch-to-register').on('click', function() {
        $('#control-login-container').fadeOut(200, function() {
            $('#control-register-container').fadeIn(200);
        });
    });

    $('#switch-to-login').on('click', function() {
        $('#control-register-container').fadeOut(200, function() {
            $('#control-login-container').fadeIn(200);
        });
    });

    // Country Flag Toggling
    $('#login-country-code, #reg-country-code').on('change', function() {
        const flag = $(this).find(':selected').data('flag');
        const target = $(this).attr('id') === 'login-country-code' ? '#login-flag' : '#reg-flag';
        $(target).text(flag);
    });

    let currentStep = 1;
    const totalSteps = 4;

    $('#reg-next').on('click', function() {
        if (validateStep(currentStep)) {
            $(`#reg-step-${currentStep}`).hide();
            currentStep++;
            $(`#reg-step-${currentStep}`).fadeIn(300);
            updateRegButtons();
        }
    });

    $('#reg-prev').on('click', function() {
        $(`#reg-step-${currentStep}`).hide();
        currentStep--;
        $(`#reg-step-${currentStep}`).fadeIn(300);
        updateRegButtons();
    });

    function updateRegButtons() {
        $('#reg-prev').toggle(currentStep > 1);
        $('#reg-next').toggle(currentStep < totalSteps);
        $('#reg-submit').toggle(currentStep === totalSteps);
    }

    function validateStep(step) {
        let valid = true;
        $(`#reg-step-${step} input[required]`).each(function() {
            if (!$(this).val()) {
                alert('يرجى ملء الحقول المطلوبة');
                valid = false;
                return false;
            }
        });
        return valid;
    }

    // --- Authentication Actions ---

    $('#control-login-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const phoneFull = $('#login-country-code').val() + $('#login-phone-body').val();
        $('#login-phone-full').val(phoneFull);

        $btn.prop('disabled', true).text('جاري الدخول...');
        $('#login-error').hide();

        $.post(control_ajax.ajax_url, $(this).serialize() + '&action=control_login&nonce=' + control_ajax.nonce, function(res) {
            if (res.success) {
                window.location.reload();
            } else {
                $btn.prop('disabled', false).text('تسجيل الدخول');
                $('#login-error').text(res.data.message || 'حدث خطأ').show();
            }
        });
    });

    $('#control-register-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#reg-submit');
        const phoneFull = $('#reg-country-code').val() + $('#reg-phone-body').val();

        if ($('#reg-password').val().length < 8) {
            $('#reg-error').text('كلمة المرور يجب أن لا تقل عن 8 أحرف').show();
            return;
        }

        $btn.prop('disabled', true).text('جاري التسجيل...');
        $('#reg-error').hide();

        const formData = $(this).serializeArray();
        formData.push({ name: 'phone', value: phoneFull });
        formData.push({ name: 'action', value: 'control_register' });
        formData.push({ name: 'nonce', value: control_ajax.nonce });

        $.post(control_ajax.ajax_url, formData, function(res) {
            if (res.success) {
                window.location.reload();
            } else {
                $btn.prop('disabled', false).text('إتمام التسجيل');
                $('#reg-error').text(res.data.message || 'حدث خطأ').show();
            }
        });
    });

    // --- User Management ---

    $('#control-user-form').on('submit', function(e) {
        e.preventDefault();
        const action = $('#user-id').val() ? 'control_save_user' : 'control_add_user';
        $.post(control_ajax.ajax_url, $(this).serialize() + '&action=' + action + '&nonce=' + control_ajax.nonce, function(res) {
            if (res.success) {
                alert('تم حفظ بيانات المستخدم بنجاح');
                location.reload();
            }
            else alert(res.data || 'حدث خطأ أثناء الحفظ');
        });
    });

    $(document).on('click', '.control-delete-user', function(e) {
        if (!confirm('حذف؟')) return;
        $.post(control_ajax.ajax_url, { action: 'control_delete_user', id: $(this).data('id'), nonce: control_ajax.nonce }, () => location.reload());
    });

    // --- Other Shared Utilities ---

    const syncLoader = $('#control-sync-loader');
    function showSync(text = 'جارٍ تحميل البيانات...') { syncLoader.find('.loader-text').text(text); syncLoader.fadeIn(200); }
    function hideSync() { syncLoader.find('.loader-text').text('تم التحديث بنجاح'); setTimeout(() => syncLoader.fadeOut(400), 1000); }

    $(document).ajaxStart(function() { showSync(); });
    $(document).ajaxStop(function() { hideSync(); });

    $('#control-refresh-btn, #control-mobile-refresh-btn').on('click', function() {
        showSync('جاري مسح التخزين المؤقت وتحديث البيانات...');
        localStorage.clear();
        sessionStorage.clear();
        setTimeout(() => { window.location.reload(true); }, 500);
    });


    $('#control-logout-btn, #control-mobile-logout-btn').on('click', function() {
        $.post(control_ajax.ajax_url, { action: 'control_logout', nonce: control_ajax.nonce }, () => location.reload());
    });

    $(document).on('click', '.control-upload-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const frame = wp.media({ title: 'اختر صورة', multiple: false }).open();
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const target = btn.parent().find('input[type="text"]');
            if (target.length) {
                target.val(attachment.url);
                if (target.attr('id') === 'company-logo-url') {
                    $('#logo-preview').attr('src', attachment.url);
                    $('#logo-preview-container').fadeIn();
                }
            }
        });
    });

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.controlInstallPrompt = e;
        $('#control-install-banner').fadeIn(300);
    });

    // Tab switching for settings
    $('.control-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.control-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.control-tab-content').hide();
        $('#' + tab).fadeIn(200);
    });

    // Mobile Header Logout (Red Pill)
    $('#control-header-logout').on('click', function() {
        $.post(control_ajax.ajax_url, { action: 'control_logout', nonce: control_ajax.nonce }, () => location.reload());
    });

    // Expandable Panels
    $(document).on('click', '.control-collapse-trigger', function() {
        $(this).next('.control-collapse-content').slideToggle(200);
    });
});
