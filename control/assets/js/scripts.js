jQuery(document).ready(function($) {

    // --- Backup & Restore System ---

    $(document).on('click', '#control-generate-backup', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('جاري توليد النسخة الاحتياطية...');

        $.post(control_ajax.ajax_url, { action: 'control_create_backup', nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                const blob = new Blob([res.data.json], { type: 'application/json' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", res.data.filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                alert('تم إنشاء النسخة الاحتياطية بنجاح.');
            } else {
                alert('فشل إنشاء النسخة الاحتياطية: ' + res.data);
            }
            $btn.prop('disabled', false).text(originalText);
        });
    });

    $(document).on('click', '#control-restore-trigger', function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        input.onchange = e => {
            const file = e.target.files[0];
            const reader = new FileReader();
            reader.onload = event => {
                const backupData = event.target.result;
                if (confirm('تحذير هام: سيتم استبدال كافة البيانات الحالية. هل تريد الاستمرار؟')) {
                    $.post(control_ajax.ajax_url, { action: 'control_restore_backup', backup_data: backupData, nonce: control_ajax.nonce }, function(res) {
                        if (res.success) {
                            alert('تمت استعادة النظام بنجاح. سيتم إعادة تحميل الصفحة.');
                            location.reload();
                        } else {
                            alert('خطأ في الاستعادة: ' + res.data);
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

    let regCurrentStep = 1;
    const regTotalSteps = 4;

    $('#reg-next').on('click', function() {
        if (validateRegStep(regCurrentStep)) {
            $(`#reg-step-${regCurrentStep}`).hide();
            regCurrentStep++;
            $(`#reg-step-${regCurrentStep}`).fadeIn(300);
            updateRegButtons();
        }
    });

    $('#reg-prev').on('click', function() {
        $(`#reg-step-${regCurrentStep}`).hide();
        regCurrentStep--;
        $(`#reg-step-${regCurrentStep}`).fadeIn(300);
        updateRegButtons();
    });

    function updateRegButtons() {
        $('#reg-prev').toggle(regCurrentStep > 1);
        $('#reg-next').toggle(regCurrentStep < regTotalSteps);
        $('#reg-submit').toggle(regCurrentStep === regTotalSteps);
    }

    function validateRegStep(step) {
        let valid = true;
        $(`#reg-step-${step} input[required]`).each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#ef4444');
                valid = false;
            } else {
                $(this).css('border-color', '#333');
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
                $btn.prop('disabled', false).text('إتمتم التسجيل');
                $('#reg-error').text(res.data.message || 'حدث خطأ').show();
            }
        });
    });

    // --- Settings System & Real-time Design Preview ---

    $('#control-design-form input, #control-design-form select').on('input change', function() {
        const name = $(this).attr('name');
        const val = $(this).val();
        const root = document.documentElement;

        switch(name) {
            case 'design_sidebar_bg': root.style.setProperty('--control-sidebar-bg', val); break;
            case 'design_btn_primary': root.style.setProperty('--control-primary', val); break;
            case 'design_btn_secondary': root.style.setProperty('--control-primary-soft', val); break;
            case 'design_accent': root.style.setProperty('--control-accent', val); break;
            case 'design_text_main': root.style.setProperty('--control-text', val); break;
            case 'design_bg_main': root.style.setProperty('--control-bg', val); break;
            case 'design_font_size': root.style.setProperty('font-size', val + 'px', 'important'); break;
            case 'design_font_weight_bold': root.style.setProperty('--control-font-weight-bold', val); break;
            case 'design_high_contrast':
                if(val === 'yes') $('body').css('filter', 'contrast(1.1) saturate(1.1)');
                else $('body').css('filter', 'none');
                break;
        }
    });

    $('.control-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.control-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.control-tab-content').hide();
        $('#' + tab).fadeIn(200);
    });

    $('.control-system-settings-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> جاري الحفظ...');

        $.post(control_ajax.ajax_url, $(this).serialize() + '&action=control_save_settings&nonce=' + control_ajax.nonce, function(res) {
            if (res.success) {
                alert('تم حفظ الإعدادات بنجاح');
                location.reload();
            } else {
                alert('خطأ أثناء الحفظ');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
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
            const target = btn.parent().find('input[type="hidden"]');
            if (target.length) {
                target.val(attachment.url);
                const previewImg = btn.closest('.control-form-group').find('img');
                if (previewImg.length) {
                    previewImg.attr('src', attachment.url).show();
                    btn.closest('.control-form-group').find('.dashicons').hide();
                }
            }
        });
    });

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.controlInstallPrompt = e;
        $('#control-install-banner').fadeIn(300);
    });

    // --- Audit & UI Extras ---

    $(document).on('click', '#control-export-audit-pdf', function() {
        const element = document.getElementById('control-audit-logs-body').closest('table');
        const opt = {
            margin:       10,
            filename:     'control_audit_log.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
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
