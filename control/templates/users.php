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

function control_get_time_ago($timestamp) {
    if (!$timestamp) return 'غير نشط';

    // Use WordPress local time for consistency with current_time('mysql')
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $now = current_time('timestamp');
    $diff = $now - $time;

    if ($diff < 0) $diff = 0; // Prevent negative values from timezone mismatches
    if ($diff < 60) return 'الآن';

    $units = array(
        31536000 => 'سنة',
        2592000  => 'شهر',
        604800   => 'أسبوع',
        86400    => 'يوم',
        3600     => 'ساعة',
        60       => 'دقيقة'
    );

    foreach ($units as $unit => $label) {
        if ($diff < $unit) continue;
        $count = floor($diff / $unit);

        // Simple Arabic pluralization for common cases
        if ($label == 'ساعة') {
            if ($count == 1) return 'منذ ساعة';
            if ($count == 2) return 'منذ ساعتين';
            if ($count <= 10) return 'منذ ' . $count . ' ساعات';
            return 'منذ ' . $count . ' ساعة';
        }
        if ($label == 'يوم') {
            if ($count == 1) return 'منذ يوم';
            if ($count == 2) return 'منذ يومين';
            if ($count <= 10) return 'منذ ' . $count . ' أيام';
            return 'منذ ' . $count . ' يوم';
        }
        if ($label == 'دقيقة') {
            if ($count == 1) return 'منذ دقيقة';
            if ($count == 2) return 'منذ دقيقتين';
            if ($count <= 10) return 'منذ ' . $count . ' دقائق';
            return 'منذ ' . $count . ' دقيقة';
        }

        return 'منذ ' . $count . ' ' . $label;
    }

    return 'منذ فترة';
}
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:var(--control-text-dark);"><?php _e('إدارة الكوادر البشرية', 'control'); ?></h2>
    <div style="display:flex; gap:10px;">
        <?php if($can_manage): ?>
            <button id="control-add-user-btn" class="control-btn" style="background:var(--control-primary); border:none;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:5px;"></span><?php _e('إضافة كادر', 'control'); ?>
            </button>
            <div class="control-dropdown" style="position:relative;">
                <button class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border);" onclick="jQuery('#user-tools-dropdown').toggle()">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
                <div id="user-tools-dropdown" style="display:none; position:absolute; left:0; top:110%; background:#fff; border:1px solid var(--control-border); border-radius:var(--control-radius-sm); box-shadow:var(--control-shadow); z-index:100; min-width:140px; padding:5px;">
                    <button class="control-btn control-export-btn" data-type="users" style="width:100%; justify-content:flex-start; background:none; color:var(--control-text-dark) !important; border:none; padding:8px 12px; font-size:0.8rem;">
                        <span class="dashicons dashicons-download" style="margin-left:8px;"></span><?php _e('تصدير CSV', 'control'); ?>
                    </button>
                    <button class="control-btn control-import-trigger" data-type="users" style="width:100%; justify-content:flex-start; background:none; color:var(--control-text-dark) !important; border:none; padding:8px 12px; font-size:0.8rem;">
                        <span class="dashicons dashicons-upload" style="margin-left:8px;"></span><?php _e('استيراد بيانات', 'control'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="control-card" style="padding:15px; margin-bottom:20px; border:none; background:rgba(0,0,0,0.02);">
    <div style="display:flex; gap:12px; align-items: center;">
        <div style="flex:1; position:relative;">
            <span class="dashicons dashicons-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--control-muted);"></span>
            <input type="text" id="user-search-input" placeholder="<?php _e('ابحث بالاسم، الهاتف، أو التخصص...', 'control'); ?>" style="padding:10px 40px 10px 12px;">
        </div>
        <select id="user-role-filter" style="width:200px; padding:10px;">
            <option value=""><?php _e('جميع التخصصات', 'control'); ?></option>
            <?php foreach($role_labels as $val => $label): ?>
                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div id="control-users-grid" class="control-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
    <?php foreach($users as $u): ?>
        <div class="control-card user-card-item" data-user='<?php echo json_encode($u); ?>' data-role="<?php echo $u->role; ?>" data-search="<?php echo esc_attr(strtolower($u->name . ' ' . $u->phone . ' ' . ($role_labels[$u->role] ?? ''))); ?>" style="padding:0; display:flex; flex-direction:column;">

            <!-- Activity Badge -->
            <div class="user-activity-badge" title="آخر ظهور">
                <?php echo control_get_time_ago($u->last_activity); ?>
            </div>

            <div style="padding:20px; flex:1;">
                <div style="display:flex; gap:15px; align-items:flex-start;">
                    <div style="position:relative;">
                        <div style="width:64px; height:64px; background:var(--control-bg); border-radius:16px; overflow:hidden; border:1px solid var(--control-border); display:flex; align-items:center; justify-content:center;">
                            <?php if($u->profile_image): ?>
                                <img src="<?php echo esc_url($u->profile_image); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <span style="font-size:1.6rem; font-weight:800; color:var(--control-muted);"><?php echo strtoupper(substr($u->name, 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if(!$u->is_restricted): ?>
                            <div style="position:absolute; bottom:-4px; left:-4px; width:16px; height:16px; background:#10b981; border:3px solid #fff; border-radius:50%;"></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:800; color:var(--control-text-dark); font-size:1.05rem; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo esc_html($u->name); ?></div>

                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="color:var(--control-muted); font-size:0.75rem; font-weight:600;">
                                <?php echo $role_labels[$u->role] ?? $u->role; ?>
                            </span>
                            <?php if($u->employer_name): ?>
                                <span style="width:1px; height:10px; background:var(--control-border);"></span>
                                <span style="color:var(--control-muted); font-size:0.75rem; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:120px;">
                                    <?php echo esc_html($u->employer_name); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="color:var(--control-muted); font-size:0.8rem; margin-top:8px; display:flex; align-items:center; gap:5px;">
                            <span class="dashicons dashicons-phone" style="font-size:16px; width:16px; height:16px;"></span> <?php echo esc_html($u->phone); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background:var(--control-bg); padding:12px 20px; border-top:1px solid var(--control-border); border-radius:0 0 var(--control-radius) var(--control-radius); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                    <?php if($u->employer_name): ?>
                        <div title="<?php echo esc_attr($u->employer_name); ?>" style="width:28px; height:28px; background:#fff; border:1px solid var(--control-border); border-radius:6px; overflow:hidden; flex-shrink:0;">
                            <?php if($u->org_logo): ?>
                                <img src="<?php echo esc_url($u->org_logo); ?>" style="width:100%; height:100%; object-fit:contain;">
                            <?php else: ?>
                                <span class="dashicons dashicons-building" style="font-size:18px; color:var(--control-muted); display:flex; align-items:center; justify-content:center; height:100%;"></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display:flex; gap:8px; flex-shrink:0;">
                    <?php if($can_manage): ?>
                        <button class="control-btn control-edit-user" title="<?php _e('تعديل', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fff; color:var(--control-muted) !important; border:1px solid var(--control-border);"><span class="dashicons dashicons-edit"></span></button>
                        <?php if($u->username !== 'admin' && $u->phone !== '1234567890'): ?>
                            <button class="control-btn control-restrict-user" data-id="<?php echo $u->id; ?>" title="<?php echo $u->is_restricted ? __('إلغاء التقييد', 'control') : __('تقييد', 'control'); ?>" style="padding:0; width:34px; height:34px; background:<?php echo $u->is_restricted ? '#ecfdf5' : '#fff7ed'; ?>; color:<?php echo $u->is_restricted ? '#059669' : '#d97706'; ?> !important; border:1px solid <?php echo $u->is_restricted ? '#d1fae5' : '#ffedd5'; ?>;">
                                <span class="dashicons <?php echo $u->is_restricted ? 'dashicons-unlock' : 'dashicons-lock'; ?>"></span>
                            </button>
                            <button class="control-btn control-delete-user" data-id="<?php echo $u->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2;"><span class="dashicons dashicons-trash"></span></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="control-delete-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10002; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:400px; padding:30px; text-align:center; border-radius:20px;">
        <div style="width:70px; height:70px; background:#fef2f2; color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <span class="dashicons dashicons-trash" style="font-size:35px; width:35px; height:35px;"></span>
        </div>
        <h3 style="margin-bottom:10px;"><?php _e('تأكيد الحذف', 'control'); ?></h3>
        <p style="color:var(--control-muted); font-size:0.9rem; margin-bottom:25px;"><?php _e('هل أنت متأكد من حذف هذا الكادر نهائياً؟ لا يمكن التراجع عن هذه العملية لاحقاً بشكل مباشر.', 'control'); ?></p>
        <div style="display:flex; gap:15px;">
            <button id="confirm-delete-btn" class="control-btn" style="flex:1; background:#ef4444; border:none;"><?php _e('نعم، احذف الآن', 'control'); ?></button>
            <button type="button" class="control-btn close-delete-modal" style="flex:1; background:var(--control-bg); color:var(--control-text) !important; border:none;"><?php _e('إلغاء', 'control'); ?></button>
        </div>
    </div>
</div>

<!-- Restrict Confirmation Modal -->
<div id="control-restrict-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10002; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:450px; padding:30px; border-radius:20px;">
        <div style="width:70px; height:70px; background:#fff7ed; color:#d97706; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <span class="dashicons dashicons-lock" style="font-size:35px; width:35px; height:35px;"></span>
        </div>
        <h3 style="text-align:center; margin-bottom:10px;"><?php _e('تقييد حساب الكادر', 'control'); ?></h3>
        <p style="text-align:center; color:var(--control-muted); font-size:0.9rem; margin-bottom:25px;"><?php _e('سيتم منع هذا المستخدم من تسجيل الدخول إلى النظام خلال فترة التقييد.', 'control'); ?></p>

        <form id="control-restrict-form">
            <input type="hidden" name="id" id="restrict-user-id">
            <div class="control-form-group">
                <label><?php _e('سبب التقييد', 'control'); ?></label>
                <select name="reason" id="restrict-reason" required>
                    <option value="violating_terms"><?php _e('مخالفة الشروط والأحكام', 'control'); ?></option>
                    <option value="inactive_account"><?php _e('حساب غير نشط', 'control'); ?></option>
                    <option value="admin_decision"><?php _e('قرار إداري', 'control'); ?></option>
                    <option value="under_investigation"><?php _e('قيد التحقيق الإداري', 'control'); ?></option>
                    <option value="other"><?php _e('أسباب أخرى', 'control'); ?></option>
                </select>
            </div>
            <div class="control-form-group">
                <label><?php _e('مدة التقييد (بالأيام)', 'control'); ?></label>
                <input type="number" name="duration" id="restrict-duration" value="30" min="1" required>
                <small style="color:var(--control-muted);"><?php _e('سيتم رفع التقييد تلقائياً بعد انتهاء هذه المدة.', 'control'); ?></small>
            </div>

            <div style="display:flex; gap:15px; margin-top:25px;">
                <button type="submit" class="control-btn" style="flex:1; background:#d97706; border:none;"><?php _e('تأكيد التقييد', 'control'); ?></button>
                <button type="button" class="control-btn close-restrict-modal" style="flex:1; background:var(--control-bg); color:var(--control-text) !important; border:none;"><?php _e('إلغاء', 'control'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- User Wizard Modal -->
<div id="control-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.4); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:650px; padding:0; border-radius:20px; overflow:hidden; box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.25);">

        <div style="background:var(--control-primary); color:#fff; padding:25px 30px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 id="modal-title" style="color:#fff; margin:0; font-size:1.2rem;"><?php _e('ملف العضو الكادر', 'control'); ?></h3>
                <div id="wizard-step-label" style="opacity:0.7; font-size:0.8rem; margin-top:5px;"><?php _e('المعلومات الشخصية الأساسية', 'control'); ?></div>
            </div>
            <div style="display:flex; gap:10px;" id="wizard-dots">
                <span class="dot active" data-step="1"></span>
                <span class="dot" data-step="2"></span>
                <span class="dot" data-step="3"></span>
            </div>
        </div>

        <form id="control-user-form" style="padding:30px;">
            <input type="hidden" name="id" id="user-id">

            <!-- Step 1: Personal Info -->
            <div id="user-step-1" class="user-wizard-step">
                <div style="display:flex; gap:25px; margin-bottom:25px; align-items:center; background:var(--control-bg); padding:20px; border-radius:16px; border:1px solid var(--control-border);">
                    <div id="profile-image-preview" style="width:90px; height:90px; background:#fff; border:2px dashed var(--control-border); border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer; position:relative; flex-shrink:0;">
                        <span class="dashicons dashicons-camera" style="font-size:32px; color:var(--control-muted);"></span>
                        <img src="" style="display:none; width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;">
                    </div>
                    <div style="flex:1;">
                        <button type="button" id="upload-profile-image" class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border); padding:6px 15px; font-size:0.8rem; min-height:36px;"><?php _e('رفع صورة الهوية', 'control'); ?></button>
                        <input type="hidden" name="profile_image" id="user-profile-image">
                        <p style="margin:8px 0 0 0; font-size:0.7rem; color:var(--control-muted);"><?php _e('الصورة الشخصية تظهر في التقارير والواجهة.', 'control'); ?></p>
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1.5fr 1fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('الاسم بالكامل', 'control'); ?> *</label>
                        <input type="text" name="name" id="user-name" required placeholder="مثال: أحمد محمد علي">
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
                    <label><?php _e('رقم الهاتف للتواصل', 'control'); ?> *</label>
                    <div style="display:flex; direction:ltr;">
                        <select id="user-phone-country" style="width:110px; border-radius:8px 0 0 8px; border-right:none; background:#f8fafc;">
                            <?php foreach($countries as $code => $data): ?>
                                <option value="<?php echo $code; ?>"><?php echo $data['flag'] . ' ' . $code; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" id="user-phone-body" required style="border-radius:0 8px 8px 0; flex:1;" placeholder="000 000 000">
                        <input type="hidden" name="phone" id="user-phone">
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1.5fr 1fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('البريد الإلكتروني', 'control'); ?></label>
                        <input type="email" name="email" id="user-email" placeholder="email@example.com">
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('اسم المستخدم', 'control'); ?></label>
                        <input type="text" name="username" id="user-username" placeholder="username">
                    </div>
                </div>
            </div>

            <!-- Step 2: Academic Info -->
            <div id="user-step-2" class="user-wizard-step" style="display:none;">
                <div class="control-form-group">
                    <label><?php _e('المؤهل العلمي / التخصص الأكاديمي', 'control'); ?></label>
                    <input type="text" name="degree" id="user-degree" placeholder="مثال: بكالوريوس تربية رياضية">
                </div>
                <div class="control-form-group">
                    <label><?php _e('الجامعة / المؤسسة المانحة للمؤهل', 'control'); ?></label>
                    <input type="text" name="institution" id="user-institution" placeholder="مثال: جامعة القاهرة">
                </div>
                <div class="control-form-group">
                    <label><?php _e('سنة الحصول على المؤهل', 'control'); ?></label>
                    <input type="text" name="graduation_year" id="user-graduation-year" placeholder="YYYY">
                </div>
                <div class="control-form-group" style="margin-top:25px; padding-top:20px; border-top:1px dashed var(--control-border);">
                    <label><?php _e('التخصص الوظيفي (الصلاحية)', 'control'); ?> *</label>
                    <select name="role" id="user-role" required style="background:var(--control-accent-soft); font-weight:700;">
                        <?php foreach($role_labels as $val => $label): ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Step 3: Employment Info -->
            <div id="user-step-3" class="user-wizard-step" style="display:none;">
                <div style="display:flex; gap:20px; margin-bottom:25px; align-items:center; background:var(--control-bg); padding:15px; border-radius:12px; border:1px solid var(--control-border);">
                    <div id="org-logo-preview" style="width:60px; height:60px; background:#fff; border:1px solid var(--control-border); border-radius:8px; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer; flex-shrink:0;">
                        <span class="dashicons dashicons-building" style="color:var(--control-muted); font-size:24px;"></span>
                        <img src="" style="display:none; width:100%; height:100%; object-fit:contain;">
                    </div>
                    <div style="flex:1;">
                        <button type="button" id="upload-org-logo" class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border); padding:5px 12px; font-size:0.75rem; min-height:32px;"><?php _e('رفع شعار جهة العمل', 'control'); ?></button>
                        <input type="hidden" name="org_logo" id="user-org-logo">
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('جهة العمل الحالية', 'control'); ?></label>
                        <input type="text" name="employer_name" id="user-employer" placeholder="اسم النادي أو المؤسسة">
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('المسمى الوظيفي', 'control'); ?></label>
                        <input type="text" name="job_title" id="user-job-title" placeholder="المسمى الحالي">
                    </div>
                </div>

                <div class="control-form-group">
                    <label><?php _e('دولة المقر الوظيفي', 'control'); ?></label>
                    <select name="employer_country" id="user-employer-country">
                        <option value=""><?php _e('اختر دولة المقر...', 'control'); ?></option>
                        <?php foreach($countries as $code => $data): ?>
                            <option value="<?php echo $data['name']; ?>"><?php echo $data['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1.5fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('رقم هاتف العمل', 'control'); ?></label>
                        <input type="text" name="work_phone" id="user-work-phone">
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('البريد الإلكتروني المهني', 'control'); ?></label>
                        <input type="email" name="work_email" id="user-work-email">
                    </div>
                </div>

                <div class="control-form-group" style="margin-top:15px; padding:15px; background:#fff7ed; border:1px solid #ffedd5; border-radius:10px;">
                    <label style="color:#c2410c;"><?php _e('تحديث كلمة المرور', 'control'); ?></label>
                    <input type="password" name="password" id="user-password" placeholder="••••••••" style="background:#fff;">
                    <small style="color:#9a3412; font-size:0.7rem; margin-top:5px; display:block;"><?php _e('اتركها فارغة إذا كنت لا ترغب في تغيير كلمة المرور الحالية.', 'control'); ?></small>
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-top:30px; border-top:1px solid var(--control-border); padding-top:25px;">
                <button type="button" id="wizard-prev" class="control-btn" style="flex:1; background:var(--control-bg); color:var(--control-text) !important; border:none; display:none;"><?php _e('السابق', 'control'); ?></button>
                <button type="button" id="wizard-next" class="control-btn" style="flex:2; background:var(--control-primary); border:none;"><?php _e('التالي', 'control'); ?></button>
                <button type="submit" id="wizard-submit" class="control-btn" style="flex:2; background:var(--control-accent); color:var(--control-primary-soft) !important; border:none; display:none; font-weight:800;"><?php _e('تأكيد وحفظ البيانات', 'control'); ?></button>
                <button type="button" class="control-btn close-user-modal" style="flex:1; background:var(--control-muted); border:none;"><?php _e('إلغاء', 'control'); ?></button>
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
        $(`#user-step-${step}`).fadeIn(300);

        $('#wizard-dots .dot').removeClass('active');
        $(`#wizard-dots .dot[data-step="${step}"]`).addClass('active');

        $('#wizard-prev').toggle(step > 1);
        $('#wizard-next').toggle(step < 3);
        $('#wizard-submit').toggle(step === 3);

        const labels = {
            1: '<?php _e("المعلومات الشخصية الأساسية", "control"); ?>',
            2: '<?php _e("المؤهل العلمي والتخصص", "control"); ?>',
            3: '<?php _e("المعلومات المهنية وجهة العمل", "control"); ?>'
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
                $(this).css('border-color', 'var(--control-border)');
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
        $('#user-work-phone').val(u.work_phone);
        $('#user-work-email').val(u.work_email);
        $('#user-password').val('').prop('required', false);

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

        $('#modal-title').text('<?php _e('تعديل ملف الكادر', 'control'); ?>');
        showStep(1);
        modal.css('display', 'flex');
    });

    $('.close-user-modal').on('click', function() { modal.hide(); });

    $('#control-user-form').on('submit', function(e) {
        e.preventDefault();
        const phoneFull = $('#user-phone-country').val() + $('#user-phone-body').val();
        $('#user-phone').val(phoneFull);

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

    let userToDelete = null;
    $(document).on('click', '.control-delete-user', function() {
        userToDelete = $(this).data('id');
        $('#control-delete-modal').css('display', 'flex');
    });

    $('.close-delete-modal').on('click', function() { $('#control-delete-modal').hide(); });

    $('#confirm-delete-btn').on('click', function() {
        if (!userToDelete) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e("جاري الحذف...", "control"); ?>');

        $.post(control_ajax.ajax_url, { action: 'control_delete_user', id: userToDelete, nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'حدث خطأ');
                $btn.prop('disabled', false).text('<?php _e("نعم، احذف الآن", "control"); ?>');
            }
        });
    });

    $(document).on('click', '.control-restrict-user', function() {
        const id = $(this).data('id');
        const card = $(this).closest('.user-card-item');
        const u = card.data('user');

        if (u.is_restricted == 1) {
            if (confirm('<?php _e("هل أنت متأكد من إلغاء تقييد هذا الحساب؟", "control"); ?>')) {
                $.post(control_ajax.ajax_url, { action: 'control_toggle_user_restriction', id: id, nonce: control_ajax.nonce }, () => location.reload());
            }
        } else {
            $('#restrict-user-id').val(id);
            $('#control-restrict-modal').css('display', 'flex');
        }
    });

    $('.close-restrict-modal').on('click', function() { $('#control-restrict-modal').hide(); });

    $('#control-restrict-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('<?php _e("جاري التنفيذ...", "control"); ?>');

        const formData = $(this).serialize() + '&action=control_toggle_user_restriction&nonce=' + control_ajax.nonce;
        $.post(control_ajax.ajax_url, formData, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'حدث خطأ');
                $btn.prop('disabled', false).text('<?php _e("تأكيد التقييد", "control"); ?>');
            }
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.control-dropdown').length) {
            $('#user-tools-dropdown').hide();
        }
    });
});
</script>

<style>
#wizard-dots .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    transition: var(--control-transition);
}
#wizard-dots .dot.active {
    background: var(--control-accent);
    transform: scale(1.3);
}

.user-activity-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #fef9c3; /* Pastel Yellow */
    color: #854d0e;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    z-index: 5;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.user-card-item:hover .user-activity-badge {
    background: #fef3c7;
}
</style>
