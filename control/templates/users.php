<?php
$users = Control_Auth::get_all_users();
$can_manage = Control_Auth::is_admin();

$role_labels = array(
    'admin'       => 'مدير نظام',
    'coach'       => 'مدرب رياضي',
    'therapist'   => 'أخصائي علاج رياضي',
    'nutritionist' => 'أخصائي تغذية رياضية',
    'pe_teacher'  => 'معلم تربية بدنية',
    'researcher'  => 'باحث رياضي'
);

$countries = array(
    '+20' => array('flag' => '🇪🇬', 'name' => 'مصر'),
    '+971' => array('flag' => '🇦🇪', 'name' => 'الإمارات'),
    '+966' => array('flag' => '🇸🇦', 'name' => 'السعودية'),
    '+965' => array('flag' => '🇰🇼', 'name' => 'الكويت'),
    '+974' => array('flag' => '🇶🇦', 'name' => 'قطر'),
    '+973' => array('flag' => '🇧🇭', 'name' => 'البحرين'),
    '+968' => array('flag' => '🇴🇲', 'name' => 'عمان'),
);
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:#1e293b;"><?php _e('إدارة الكوادر البشرية', 'control'); ?></h2>
    <div style="display:flex; gap:10px;">
        <?php if($can_manage): ?>
            <button id="control-add-user-btn" class="control-btn" style="background:#000; border:none; border-radius: 6px; padding: 8px 16px; font-size: 0.85rem;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:5px; font-size: 18px;"></span><?php _e('إضافة كادر جديد', 'control'); ?>
            </button>
            <div class="control-dropdown" style="position:relative; display:inline-block;">
                <button class="control-btn" style="background:#fff; color:#333 !important; border:1px solid #cbd5e1; padding:8px 12px; border-radius: 6px;" onclick="jQuery('#user-tools-dropdown').toggle()">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
                <div id="user-tools-dropdown" style="display:none; position:absolute; left:0; top:110%; background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); z-index:100; min-width:140px; padding:5px;">
                    <button class="control-btn control-export-btn" data-type="users" style="width:100%; justify-content:flex-start; background:none; color:#333 !important; border:none; padding:8px 12px; font-size:0.8rem;">
                        <span class="dashicons dashicons-download" style="margin-left:8px;"></span><?php _e('تصدير البيانات', 'control'); ?>
                    </button>
                    <button class="control-btn control-import-trigger" data-type="users" style="width:100%; justify-content:flex-start; background:none; color:#333 !important; border:none; padding:8px 12px; font-size:0.8rem;">
                        <span class="dashicons dashicons-upload" style="margin-left:8px;"></span><?php _e('استيراد بيانات', 'control'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="control-card" style="padding:15px; margin-bottom:20px; border:none; background:#f8fafc;">
    <div style="display:flex; gap:12px; align-items: center;">
        <div style="flex:1; position:relative;">
            <span class="dashicons dashicons-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></span>
            <input type="text" id="user-search-input" placeholder="<?php _e('ابحث بالاسم، الهاتف، أو التخصص...', 'control'); ?>" style="padding:8px 35px 8px 12px; font-size:0.9rem; border-radius:6px; border:1px solid #e2e8f0;">
        </div>
        <select id="user-role-filter" style="width:200px; padding:8px; font-size:0.9rem; border-radius:6px; border:1px solid #e2e8f0;">
            <option value=""><?php _e('جميع التخصصات', 'control'); ?></option>
            <?php foreach($role_labels as $val => $label): ?>
                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div id="control-users-grid" class="control-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
    <?php foreach($users as $u): ?>
        <div class="control-card user-card-item" data-user='<?php echo json_encode($u); ?>' data-role="<?php echo $u->role; ?>" data-search="<?php echo esc_attr(strtolower($u->name . ' ' . $u->phone . ' ' . ($role_labels[$u->role] ?? ''))); ?>" style="padding:15px; margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between; position:relative;">
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <div style="position:relative;">
                    <div style="width:60px; height:60px; background:#f1f5f9; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center;">
                        <?php if($u->profile_image): ?>
                            <img src="<?php echo esc_url($u->profile_image); ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:1.5rem; font-weight:800; color:#cbd5e1;"><?php echo strtoupper(substr($u->name, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if(!$u->is_restricted): ?>
                        <div style="position:absolute; bottom:-3px; left:-3px; width:14px; height:14px; background:#10b981; border:2px solid #fff; border-radius:50%;" title="نشط"></div>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800; color:#1e293b; font-size:1rem; margin-bottom:2px;"><?php echo esc_html($u->name); ?></div>
                    <div style="color:#64748b; font-size:0.75rem; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                        <span class="dashicons dashicons-phone" style="font-size:14px; width:14px; height:14px;"></span> <?php echo esc_html($u->phone); ?>
                    </div>
                    <span class="control-capsule" style="background:#f0f9ff; color:#0369a1; font-weight:700; font-size:0.7rem; padding:3px 10px; border-radius:30px;">
                        <?php echo $role_labels[$u->role] ?? $u->role; ?>
                    </span>
                </div>
            </div>

            <div style="margin-top:15px; padding-top:12px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <?php if($u->employer_name): ?>
                        <div title="<?php echo esc_attr($u->employer_name); ?>" style="width:24px; height:24px; background:#fff; border:1px solid #eee; border-radius:4px; overflow:hidden;">
                            <?php if($u->org_logo): ?>
                                <img src="<?php echo esc_url($u->org_logo); ?>" style="width:100%; height:100%; object-fit:contain;">
                            <?php else: ?>
                                <span class="dashicons dashicons-building" style="font-size:16px; color:#94a3b8;"></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.7rem; color:#64748b; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo esc_html($u->job_title ?: $u->employer_name); ?></div>
                    <?php endif; ?>
                </div>
                <div style="display:flex; gap:8px;">
                    <?php if($can_manage): ?>
                        <button class="control-btn control-edit-user" title="<?php _e('تعديل', 'control'); ?>" style="padding:0; width:32px; height:32px; background:#f8fafc; color:#475569 !important; border:1px solid #e2e8f0; border-radius:6px;"><span class="dashicons dashicons-edit"></span></button>
                        <?php if($u->username !== 'admin' && $u->phone !== '1234567890'): ?>
                            <button class="control-btn control-restrict-user" data-id="<?php echo $u->id; ?>" title="<?php echo $u->is_restricted ? __('إلغاء التقييد', 'control') : __('تقييد', 'control'); ?>" style="padding:0; width:32px; height:32px; background:<?php echo $u->is_restricted ? '#ecfdf5' : '#fff7ed'; ?>; color:<?php echo $u->is_restricted ? '#059669' : '#d97706'; ?> !important; border:1px solid <?php echo $u->is_restricted ? '#d1fae5' : '#ffedd5'; ?>; border-radius:6px;">
                                <span class="dashicons <?php echo $u->is_restricted ? 'dashicons-unlock' : 'dashicons-lock'; ?>"></span>
                            </button>
                            <button class="control-btn control-delete-user" data-id="<?php echo $u->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="padding:0; width:32px; height:32px; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2; border-radius:6px;"><span class="dashicons dashicons-trash"></span></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- User Wizard Modal -->
<div id="control-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.4); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(3px);">
    <div class="control-card" style="width:100%; max-width:600px; padding:0; border-radius:16px; overflow:hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">

        <div style="background:#000; color:#fff; padding:20px 25px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 id="modal-title" style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('بيانات العضو', 'control'); ?></h3>
                <small id="wizard-step-label" style="opacity:0.7; font-size:0.75rem;"><?php _e('الخطوة 1: المعلومات الشخصية', 'control'); ?></small>
            </div>
            <div style="display:flex; gap:8px;" id="wizard-dots">
                <span class="dot active" data-step="1"></span>
                <span class="dot" data-step="2"></span>
                <span class="dot" data-step="3"></span>
            </div>
        </div>

        <form id="control-user-form" style="padding:25px;">
            <input type="hidden" name="id" id="user-id">

            <!-- Step 1: Personal Info -->
            <div id="user-step-1" class="user-wizard-step">
                <div style="display:flex; gap:20px; margin-bottom:20px; align-items:center; background:#f8fafc; padding:15px; border-radius:12px;">
                    <div id="profile-image-preview" style="width:80px; height:80px; background:#fff; border:2px dashed #cbd5e1; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer; position:relative;">
                        <span class="dashicons dashicons-camera" style="font-size:30px; color:#94a3b8;"></span>
                        <img src="" style="display:none; width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;">
                    </div>
                    <div style="flex:1;">
                        <button type="button" id="upload-profile-image" class="control-btn" style="background:#fff; color:#000 !important; border:1px solid #ddd; padding:5px 15px; font-size:0.75rem; min-height:32px;"><?php _e('رفع صورة شخصية', 'control'); ?></button>
                        <input type="hidden" name="profile_image" id="user-profile-image">
                        <p style="margin:5px 0 0 0; font-size:0.65rem; color:#94a3b8;"><?php _e('يفضل صورة مربعة بحجم 400x400 بكسل.', 'control'); ?></p>
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="control-form-group">
                        <label><?php _e('الاسم بالكامل', 'control'); ?> *</label>
                        <input type="text" name="name" id="user-name" required>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('الجنس', 'control'); ?></label>
                        <select name="gender" id="user-gender">
                            <option value="male"><?php _e('ذكر', 'control'); ?></option>
                            <option value="female"><?php _e('أنثى', 'control'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="control-form-group">
                    <label><?php _e('رقم الهاتف الشخصي', 'control'); ?> *</label>
                    <div style="display:flex; direction:ltr;">
                        <select id="user-phone-country" style="width:100px; border-radius:6px 0 0 6px; border-right:none;">
                            <?php foreach($countries as $code => $data): ?>
                                <option value="<?php echo $code; ?>"><?php echo $data['flag'] . ' ' . $code; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" id="user-phone-body" required style="border-radius:0 6px 6px 0; flex:1;">
                        <input type="hidden" name="phone" id="user-phone">
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1.5fr 1fr; gap: 15px;">
                    <div class="control-form-group">
                        <label><?php _e('البريد الإلكتروني', 'control'); ?></label>
                        <input type="email" name="email" id="user-email">
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('اسم المستخدم', 'control'); ?></label>
                        <input type="text" name="username" id="user-username">
                    </div>
                </div>
            </div>

            <!-- Step 2: Academic Info -->
            <div id="user-step-2" class="user-wizard-step" style="display:none;">
                <div class="control-form-group">
                    <label><?php _e('المؤهل العلمي / الدرجة', 'control'); ?></label>
                    <input type="text" name="degree" id="user-degree" placeholder="مثال: دكتوراه في علوم الرياضة">
                </div>
                <div class="control-form-group">
                    <label><?php _e('الجامعة / المؤسسة التعليمية', 'control'); ?></label>
                    <input type="text" name="institution" id="user-institution">
                </div>
                <div class="control-form-group">
                    <label><?php _e('سنة التخرج', 'control'); ?></label>
                    <input type="text" name="graduation_year" id="user-graduation-year" placeholder="YYYY">
                </div>
                <div class="control-form-group" style="margin-top:20px;">
                    <label><?php _e('التخصص الوظيفي في النظام', 'control'); ?> *</label>
                    <select name="role" id="user-role" required>
                        <?php foreach($role_labels as $val => $label): ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Step 3: Employment Info -->
            <div id="user-step-3" class="user-wizard-step" style="display:none;">
                <div style="display:flex; gap:15px; margin-bottom:20px; align-items:center; background:#f8fafc; padding:12px; border-radius:8px;">
                    <div id="org-logo-preview" style="width:50px; height:50px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer;">
                        <span class="dashicons dashicons-building" style="color:#cbd5e1;"></span>
                        <img src="" style="display:none; width:100%; height:100%; object-fit:contain;">
                    </div>
                    <div style="flex:1;">
                        <button type="button" id="upload-org-logo" class="control-btn" style="background:#fff; color:#000 !important; border:1px solid #ddd; padding:4px 12px; font-size:0.7rem; min-height:28px;"><?php _e('شعار جهة العمل', 'control'); ?></button>
                        <input type="hidden" name="org_logo" id="user-org-logo">
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="control-form-group">
                        <label><?php _e('جهة العمل / المؤسسة', 'control'); ?></label>
                        <input type="text" name="employer_name" id="user-employer">
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('المسمى الوظيفي', 'control'); ?></label>
                        <input type="text" name="job_title" id="user-job-title">
                    </div>
                </div>

                <div class="control-form-group">
                    <label><?php _e('دولة جهة العمل', 'control'); ?></label>
                    <select name="employer_country" id="user-employer-country">
                        <option value=""><?php _e('اختر الدولة...', 'control'); ?></option>
                        <?php foreach($countries as $code => $data): ?>
                            <option value="<?php echo $data['name']; ?>"><?php echo $data['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="control-form-group">
                    <label><?php _e('هاتف العمل', 'control'); ?></label>
                    <div style="display:flex; direction:ltr;">
                        <select id="user-work-phone-country" style="width:100px; border-radius:6px 0 0 6px; border-right:none;">
                            <?php foreach($countries as $code => $data): ?>
                                <option value="<?php echo $code; ?>"><?php echo $data['flag'] . ' ' . $code; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" id="user-work-phone-body" style="border-radius:0 6px 6px 0; flex:1;">
                        <input type="hidden" name="work_phone" id="user-work-phone">
                    </div>
                </div>

                <div class="control-form-group">
                    <label><?php _e('البريد الإلكتروني للعمل', 'control'); ?></label>
                    <input type="email" name="work_email" id="user-work-email">
                </div>

                <div class="control-form-group" style="margin-top:10px;">
                    <label><?php _e('كلمة المرور (اتركها فارغة لعدم التغيير)', 'control'); ?></label>
                    <input type="password" name="password" id="user-password">
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top:25px; border-top:1px solid #f1f5f9; padding-top:20px;">
                <button type="button" id="wizard-prev" class="control-btn" style="flex:1; background:#f1f5f9; color:#475569 !important; border:none; display:none;"><?php _e('السابق', 'control'); ?></button>
                <button type="button" id="wizard-next" class="control-btn" style="flex:2; background:#000; border:none;"><?php _e('التالي', 'control'); ?></button>
                <button type="submit" id="wizard-submit" class="control-btn" style="flex:2; background:#D4AF37; color:#000 !important; border:none; display:none; font-weight:800;"><?php _e('حفظ كافة البيانات', 'control'); ?></button>
                <button type="button" class="control-btn close-user-modal" style="flex:1; background:#64748b; border:none;"><?php _e('إلغاء', 'control'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentStep = 1;
    const modal = $('#control-user-modal');

    function showStep(step) {
        $('.user-wizard-step').hide();
        $(`#user-step-${step}`).fadeIn(200);

        $('#wizard-dots .dot').removeClass('active');
        $(`#wizard-dots .dot[data-step="${step}"]`).addClass('active');

        $('#wizard-prev').toggle(step > 1);
        $('#wizard-next').toggle(step < 3);
        $('#wizard-submit').toggle(step === 3);

        const labels = {
            1: '<?php _e("الخطوة 1: المعلومات الشخصية", "control"); ?>',
            2: '<?php _e("الخطوة 2: المعلومات الأكاديمية", "control"); ?>',
            3: '<?php _e("الخطوة 3: المعلومات الوظيفية", "control"); ?>'
        };
        $('#wizard-step-label').text(labels[step]);
        currentStep = step;
    }

    $('#wizard-next').on('click', function() {
        if (validateCurrentStep()) {
            showStep(currentStep + 1);
        }
    });

    $('#wizard-prev').on('click', function() {
        showStep(currentStep - 1);
    });

    function validateCurrentStep() {
        let valid = true;
        $(`#user-step-${currentStep} [required]`).each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#ef4444');
                valid = false;
            } else {
                $(this).css('border-color', '#e2e8f0');
            }
        });
        return valid;
    }

    $('#control-add-user-btn').on('click', function() {
        $('#control-user-form')[0].reset();
        $('#user-id').val('');
        $('#user-password').prop('required', true);
        $('#profile-image-preview img').hide();
        $('#profile-image-preview span').show();
        $('#org-logo-preview img').hide();
        $('#org-logo-preview span').show();
        $('#modal-title').text('<?php _e('إضافة كادر جديد', 'control'); ?>');
        showStep(1);
        modal.css('display', 'flex');
    });

    $(document).on('click', '.control-edit-user', function() {
        const u = $(this).closest('.user-card-item').data('user');
        $('#user-id').val(u.id);
        $('#user-username').val(u.username);
        $('#user-name').val(u.name);
        $('#user-email').val(u.email);
        $('#user-role').val(u.role);
        $('#user-gender').val(u.gender || 'male');
        $('#user-degree').val(u.degree);
        $('#user-institution').val(u.institution);
        $('#user-graduation-year').val(u.graduation_year);
        $('#user-employer').val(u.employer_name);
        $('#user-job-title').val(u.job_title);
        $('#user-employer-country').val(u.employer_country);
        $('#user-work-email').val(u.work_email);
        $('#user-password').val('').prop('required', false);

        // Handle images
        if (u.profile_image) {
            $('#profile-image-preview img').attr('src', u.profile_image).show();
            $('#profile-image-preview span').hide();
            $('#user-profile-image').val(u.profile_image);
        } else {
            $('#profile-image-preview img').hide();
            $('#profile-image-preview span').show();
        }

        if (u.org_logo) {
            $('#org-logo-preview img').attr('src', u.org_logo).show();
            $('#org-logo-preview span').hide();
            $('#user-org-logo').val(u.org_logo);
        } else {
            $('#org-logo-preview img').hide();
            $('#org-logo-preview span').show();
        }

        // Handle primary phone
        if (u.phone && u.phone.startsWith('+')) {
            const codeMatch = u.phone.match(/^\+[0-9]{2,3}/);
            if (codeMatch) {
                const code = codeMatch[0];
                $('#user-phone-country').val(code);
                $('#user-phone-body').val(u.phone.replace(code, ''));
            }
        } else {
            $('#user-phone-body').val(u.phone);
        }

        // Handle work phone
        if (u.work_phone && u.work_phone.startsWith('+')) {
            const codeMatch = u.work_phone.match(/^\+[0-9]{2,3}/);
            if (codeMatch) {
                const code = codeMatch[0];
                $('#user-work-phone-country').val(code);
                $('#user-work-phone-body').val(u.work_phone.replace(code, ''));
            }
        } else {
            $('#user-work-phone-body').val(u.work_phone);
        }

        $('#modal-title').text('<?php _e('تعديل بيانات الكادر', 'control'); ?>');
        showStep(1);
        modal.css('display', 'flex');
    });

    $('.close-user-modal').on('click', function() { modal.hide(); });

    $('#control-user-form').on('submit', function(e) {
        e.preventDefault();

        const phoneFull = $('#user-phone-country').val() + $('#user-phone-body').val();
        $('#user-phone').val(phoneFull);

        const workPhoneFull = $('#user-work-phone-country').val() + $('#user-work-phone-body').val();
        $('#user-work-phone').val(workPhoneFull);

        const action = $('#user-id').val() ? 'control_save_user' : 'control_add_user';
        const formData = $(this).serialize() + '&action=' + action + '&nonce=' + control_ajax.nonce;

        $.post(control_ajax.ajax_url, formData, function(res) {
            if (res.success) {
                alert('تم حفظ البيانات بنجاح');
                location.reload();
            } else {
                alert(res.data || 'حدث خطأ أثناء الحفظ');
            }
        });
    });

    // Image Upload Handlers
    $('#upload-profile-image, #profile-image-preview').on('click', function(e) {
        e.preventDefault();
        const frame = wp.media({ title: 'اختر صورة شخصية', multiple: false }).open();
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#user-profile-image').val(attachment.url);
            $('#profile-image-preview img').attr('src', attachment.url).show();
            $('#profile-image-preview span').hide();
        });
    });

    $('#upload-org-logo, #org-logo-preview').on('click', function(e) {
        e.preventDefault();
        const frame = wp.media({ title: 'اختر شعار جهة العمل', multiple: false }).open();
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#user-org-logo').val(attachment.url);
            $('#org-logo-preview img').attr('src', attachment.url).show();
            $('#org-logo-preview span').hide();
        });
    });

    // Search and filter logic
    $('#user-search-input, #user-role-filter').on('keyup change', function() {
        const query = $('#user-search-input').val().toLowerCase();
        const role = $('#user-role-filter').val();

        $('.user-card-item').each(function() {
            const card = $(this);
            const searchVal = card.data('search');
            const userRole = card.data('role');

            const matchesSearch = !query || searchVal.includes(query);
            const matchesRole = !role || userRole === role;

            if (matchesSearch && matchesRole) card.fadeIn(200);
            else card.hide();
        });
    });

    $(document).on('click', '.control-restrict-user', function() {
        const id = $(this).data('id');
        if (!confirm('هل أنت متأكد من تغيير حالة تقييد هذا الحساب؟')) return;
        $.post(control_ajax.ajax_url, { action: 'control_toggle_user_restriction', id: id, nonce: control_ajax.nonce }, () => location.reload());
    });

    // Close dropdown on click outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.control-dropdown').length) {
            $('#user-tools-dropdown').hide();
        }
    });
});
</script>

<style>
#wizard-dots .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transition: 0.3s;
}
#wizard-dots .dot.active {
    background: #D4AF37;
    transform: scale(1.3);
}
.user-wizard-step label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 5px;
    font-weight: 600;
}
</style>
