<?php
$users = Control_Auth::get_all_users();
$can_manage = Control_Auth::is_admin();
$role_labels = Control_Auth::get_roles();

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
        <?php if(Control_Auth::has_permission('users_manage')): ?>
            <button id="control-add-user-btn" class="control-btn" style="background:var(--control-primary); border:none;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:5px;"></span><?php _e('إضافة كادر', 'control'); ?>
            </button>
        <?php endif; ?>
        <?php if(Control_Auth::has_permission('users_view')): ?>
            <div class="control-dropdown" style="position:relative;">
                <button class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border);" onclick="jQuery('#user-data-mgmt-modal').css('display', 'flex')">
                    <span class="dashicons dashicons-database" style="margin-left:5px;"></span><?php _e('إدارة البيانات', 'control'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="control-card" style="padding:15px; margin-bottom:20px; border:none; background:rgba(0,0,0,0.02);">
    <div style="display:flex; gap:12px; align-items: center; flex-wrap: wrap;">
        <div style="display:flex; align-items:center; gap:10px; background:#fff; padding:5px 12px; border-radius:8px; border:1px solid var(--control-border);">
            <input type="checkbox" id="select-all-users" style="width:18px; height:18px; cursor:pointer;">
            <label for="select-all-users" style="font-size:0.8rem; font-weight:700; cursor:pointer; color:var(--control-muted);"><?php _e('الكل', 'control'); ?></label>
        </div>

        <div style="flex:1; position:relative; min-width: 250px;">
            <span class="dashicons dashicons-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--control-muted);"></span>
            <input type="text" id="user-search-input" placeholder="<?php _e('ابحث بالاسم، الهاتف، أو التخصص...', 'control'); ?>" style="padding:10px 40px 10px 12px;">
        </div>

        <select id="user-status-filter" style="width:140px; padding:10px;">
            <option value=""><?php _e('كل الحالات', 'control'); ?></option>
            <option value="active"><?php _e('نشط', 'control'); ?></option>
            <option value="restricted"><?php _e('مقيد', 'control'); ?></option>
        </select>

        <select id="user-role-filter" style="width:180px; padding:10px;">
            <option value=""><?php _e('جميع التخصصات', 'control'); ?></option>
            <?php foreach($role_labels as $val => $label): ?>
                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>

        <select id="user-sort-filter" style="width:160px; padding:10px;">
            <option value="newest"><?php _e('الأحدث تسجيلاً', 'control'); ?></option>
            <option value="oldest"><?php _e('الأقدم تسجيلاً', 'control'); ?></option>
            <option value="name_asc"><?php _e('الاسم (أ-ي)', 'control'); ?></option>
        </select>
    </div>
</div>

<!-- Bulk Actions Toolbar -->
<div id="bulk-actions-toolbar" style="display:none; background:var(--control-primary-soft); color:#fff; padding:12px 25px; border-radius:15px; margin-bottom:20px; align-items:center; justify-content:space-between; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);">
    <div style="display:flex; align-items:center; gap:15px;">
        <span id="selected-count" style="font-weight:800; font-size:1.1rem; background:var(--control-accent); color:var(--control-primary); padding:2px 12px; border-radius:8px; min-width:40px; text-align:center;">0</span>
        <span style="font-size:0.95rem; font-weight:700;"><?php _e('كوادر مختارة للعمليات الجماعية', 'control'); ?></span>
    </div>
    <div style="display:flex; gap:12px;">
        <?php if(Control_Auth::has_permission('users_manage')): ?>
            <button id="bulk-restrict-btn" class="control-btn" style="background:#f59e0b; border:none; height:40px; padding:0 20px; font-weight:700;"><span class="dashicons dashicons-lock" style="margin-left:8px;"></span><?php _e('تقييد/تعليق', 'control'); ?></button>
        <?php endif; ?>
        <?php if(Control_Auth::has_permission('users_delete')): ?>
            <button id="bulk-delete-btn" class="control-btn" style="background:#ef4444; border:none; height:40px; padding:0 20px; font-weight:700;"><span class="dashicons dashicons-trash" style="margin-left:8px;"></span><?php _e('حذف نهائي', 'control'); ?></button>
        <?php endif; ?>
        <button id="cancel-bulk-btn" class="control-btn" style="background:rgba(255,255,255,0.15); border:none; height:40px; padding:0 20px; font-weight:700;"><?php _e('إلغاء', 'control'); ?></button>
    </div>
</div>

<div id="control-users-grid" class="control-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
    <?php foreach($users as $u):
        $u_public = (array) $u;
        // Security: Only expose raw password to those who can manage users
        if ( ! Control_Auth::has_permission('users_manage') ) {
            unset($u_public['raw_password']);
        }
        $country_code = '';
        if (preg_match('/^\+(20|971|966|965|974|973|968)/', $u->phone, $matches)) {
            $country_code = $matches[0];
        }
        $country_info = $countries[$country_code] ?? null;
    ?>
        <div class="control-card user-card-item" data-user='<?php echo json_encode($u_public); ?>' data-role="<?php echo $u->role; ?>" data-status="<?php echo $u->is_restricted ? 'restricted' : 'active'; ?>" data-date="<?php echo strtotime($u->created_at); ?>" data-name="<?php echo esc_attr($u->name); ?>" data-search="<?php echo esc_attr(strtolower($u->name . ' ' . $u->phone . ' ' . ($role_labels[$u->role] ?? ''))); ?>" style="padding:0; display:flex; flex-direction:column;">

            <div class="user-card-select-overlay">
                <input type="checkbox" class="user-bulk-select" value="<?php echo $u->id; ?>">
            </div>

            <!-- Badges Row -->
            <div style="position: absolute; top: 12px; left: 12px; display: flex; flex-direction: column; gap: 5px; align-items: flex-end; z-index: 5;">
                <div class="user-card-badge activity-badge" title="آخر ظهور">
                    <span class="dashicons dashicons-clock" style="font-size:12px; width:12px; height:12px; margin-left:4px;"></span>
                    <?php echo control_get_time_ago($u->last_activity); ?>
                </div>
                <?php if($country_info): ?>
                    <div class="user-card-badge country-badge" title="<?php echo esc_attr($country_info['name']); ?>">
                        <span style="margin-left:5px;"><?php echo $country_info['flag']; ?></span>
                        <?php echo $country_info['name']; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="padding:20px; flex:1; padding-top: 35px;">
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
                    <?php if(Control_Auth::has_permission('users_view')): ?>
                        <button class="control-btn control-view-user" title="<?php _e('تفاصيل الحساب', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fff; color:var(--control-muted) !important; border:1px solid var(--control-border);"><span class="dashicons dashicons-visibility"></span></button>
                    <?php endif; ?>
                    <?php if(Control_Auth::has_permission('users_manage')): ?>
                        <button class="control-btn control-edit-user" title="<?php _e('تعديل', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fff; color:var(--control-muted) !important; border:1px solid var(--control-border);"><span class="dashicons dashicons-edit"></span></button>
                        <?php if($u->username !== 'admin' && $u->phone !== '1234567890'): ?>
                            <button class="control-btn control-restrict-user" data-id="<?php echo $u->id; ?>" title="<?php echo $u->is_restricted ? __('إلغاء التقييد', 'control') : __('تقييد', 'control'); ?>" style="padding:0; width:34px; height:34px; background:<?php echo $u->is_restricted ? '#ecfdf5' : '#fff7ed'; ?>; color:<?php echo $u->is_restricted ? '#059669' : '#d97706'; ?> !important; border:1px solid <?php echo $u->is_restricted ? '#d1fae5' : '#ffedd5'; ?>;">
                                <span class="dashicons <?php echo $u->is_restricted ? 'dashicons-unlock' : 'dashicons-lock'; ?>"></span>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if(Control_Auth::has_permission('users_delete') && $u->username !== 'admin' && $u->phone !== '1234567890'): ?>
                        <button class="control-btn control-delete-user" data-id="<?php echo $u->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2;"><span class="dashicons dashicons-trash"></span></button>
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

<!-- Data Management Modal -->
<div id="user-data-mgmt-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10002; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:800px; padding:0; border-radius:20px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div style="background:var(--control-primary); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('إدارة بيانات المستخدمين (استيراد / تصدير)', 'control'); ?></h3>
            <button onclick="jQuery('#user-data-mgmt-modal').hide()" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>

        <div class="control-tabs" style="display:flex; background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:0 20px;">
            <button class="mgmt-tab-btn active" data-tab="mgmt-export"><?php _e('تصدير البيانات', 'control'); ?></button>
            <button class="mgmt-tab-btn" data-tab="mgmt-import"><?php _e('استيراد بيانات جديدة', 'control'); ?></button>
        </div>

        <div style="padding:30px; max-height:70vh; overflow-y:auto;">
            <!-- Export Section -->
            <div id="mgmt-export" class="mgmt-tab-content">
                <div style="text-align:center; margin-bottom:30px;">
                    <div style="width:60px; height:60px; background:#eff6ff; color:#3b82f6; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                        <span class="dashicons dashicons-download" style="font-size:30px; width:30px; height:30px;"></span>
                    </div>
                    <h4><?php _e('تحميل قاعدة بيانات المستخدمين', 'control'); ?></h4>
                    <p style="color:var(--control-muted); font-size:0.85rem;"><?php _e('قم بتصدير كافة المستخدمين مع بياناتهم الشخصية، الوظيفية، والأكاديمية.', 'control'); ?></p>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                    <div style="border:2px solid var(--control-border); padding:20px; border-radius:12px; text-align:center; cursor:pointer; transition:0.2s;" class="export-format-card" data-format="csv">
                        <div style="font-weight:800; font-size:1.2rem; color:var(--control-text-dark); margin-bottom:5px;">CSV</div>
                        <div style="font-size:0.75rem; color:var(--control-muted);"><?php _e('متوافق مع Excel و Google Sheets', 'control'); ?></div>
                    </div>
                    <div style="border:2px solid var(--control-border); padding:20px; border-radius:12px; text-align:center; cursor:pointer; transition:0.2s;" class="export-format-card" data-format="json">
                        <div style="font-weight:800; font-size:1.2rem; color:var(--control-text-dark); margin-bottom:5px;">JSON</div>
                        <div style="font-size:0.75rem; color:var(--control-muted);"><?php _e('متوافق مع الأنظمة التقنية (API Ready)', 'control'); ?></div>
                    </div>
                </div>

                <div style="margin-top:30px; text-align:center;">
                    <button id="execute-export-btn" class="control-btn" style="min-width:200px; height:48px; border:none; font-weight:800;"><?php _e('بدء التصدير الآن', 'control'); ?></button>
                </div>
            </div>

            <!-- Import Section -->
            <div id="mgmt-import" class="mgmt-tab-content" style="display:none;">
                <div id="import-step-upload">
                    <div style="border:2px dashed var(--control-border); padding:40px; border-radius:15px; text-align:center; background:#f8fafc;">
                        <span class="dashicons dashicons-upload" style="font-size:40px; color:var(--control-muted); width:40px; height:40px; margin-bottom:15px;"></span>
                        <h4><?php _e('اختر ملف البيانات للرفع', 'control'); ?></h4>
                        <p style="color:var(--control-muted); font-size:0.8rem; margin-bottom:20px;"><?php _e('يدعم الملفات بتنسيق CSV أو JSON فقط.', 'control'); ?></p>
                        <input type="file" id="import-file-input" accept=".csv,.json" style="display:none;">
                        <button onclick="jQuery('#import-file-input').click()" class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><?php _e('اختيار الملف', 'control'); ?></button>
                    </div>
                </div>

                <div id="import-step-preview" style="display:none;">
                    <h4 style="margin-bottom:15px;"><?php _e('معاينة البيانات وفحص الصحة', 'control'); ?></h4>
                    <div style="overflow-x:auto; background:#fff; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:20px;">
                        <table class="control-table" style="font-size:0.75rem; min-width:600px;">
                            <thead>
                                <tr style="background:#f1f5f9;">
                                    <th><?php _e('الاسم', 'control'); ?></th>
                                    <th><?php _e('الهاتف', 'control'); ?></th>
                                    <th><?php _e('الدور', 'control'); ?></th>
                                    <th><?php _e('الحالة', 'control'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="import-preview-body"></tbody>
                        </table>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <p id="import-summary" style="font-size:0.85rem; font-weight:700;"></p>
                        <div style="display:flex; gap:10px;">
                            <button id="confirm-import-btn" class="control-btn" style="background:#10b981; border:none;"><?php _e('تأكيد الاستيراد النهائي', 'control'); ?></button>
                            <button onclick="location.reload()" class="control-btn" style="background:var(--control-bg); color:var(--control-text) !important; border:none;"><?php _e('إلغاء', 'control'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.mgmt-tab-btn { background:none; border:none; padding:15px 25px; cursor:pointer; font-weight:700; color:var(--control-muted); border-bottom:3px solid transparent; transition:0.2s; }
.mgmt-tab-btn.active { color:var(--control-primary); border-bottom-color:var(--control-accent); }
.export-format-card.active { border-color:var(--control-accent) !important; background:var(--control-accent-soft); }
</style>

<!-- User Details Modal -->
<div id="control-details-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10002; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:550px; padding:0; border-radius:20px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div style="background:var(--control-primary); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('تفاصيل حساب الكادر', 'control'); ?></h3>
            <button onclick="jQuery('#control-details-modal').hide()" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
        <div style="padding:30px;">
            <div style="display:flex; gap:25px; align-items:center; margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid var(--control-border);">
                <div id="detail-avatar" style="width:80px; height:80px; background:var(--control-bg); border-radius:15px; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid var(--control-border);">
                    <span class="dashicons dashicons-admin-users" style="font-size:40px; color:var(--control-muted);"></span>
                    <img src="" style="display:none; width:100%; height:100%; object-fit:cover;">
                </div>
                <div>
                    <h2 id="detail-name" style="margin:0 0 5px 0; font-size:1.3rem; color:var(--control-text-dark);"></h2>
                    <span id="detail-role-badge" class="control-status-indicator indicator-accent"></span>
                </div>
            </div>

            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('اسم المستخدم', 'control'); ?></label>
                    <div id="detail-username" style="font-weight:600; color:var(--control-text-dark);"></div>
                </div>
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('البريد الإلكتروني', 'control'); ?></label>
                    <div id="detail-email" style="font-weight:600; color:var(--control-text-dark);"></div>
                </div>
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('رقم الهاتف', 'control'); ?></label>
                    <div id="detail-phone" style="font-weight:600; color:var(--control-text-dark);"></div>
                </div>
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('الدولة', 'control'); ?></label>
                    <div id="detail-country" style="font-weight:600; color:var(--control-text-dark);"></div>
                </div>
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('تاريخ الإنشاء', 'control'); ?></label>
                    <div id="detail-created" style="font-weight:600; color:var(--control-text-dark);"></div>
                </div>
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('آخر نشاط', 'control'); ?></label>
                    <div id="detail-last-activity" style="font-weight:600; color:var(--control-text-dark);"></div>
                </div>
                <div class="info-group">
                    <label style="display:block; font-size:0.75rem; color:var(--control-muted); margin-bottom:5px; font-weight:700;"><?php _e('كلمة المرور (للمدير)', 'control'); ?></label>
                    <div id="detail-password" style="font-weight:800; color:#ef4444; font-family:monospace; font-size:1rem;"></div>
                </div>
            </div>

            <div style="margin-top:30px; text-align:center;">
                <button onclick="jQuery('#control-details-modal').hide()" class="control-btn" style="min-width:150px; background:var(--control-bg); color:var(--control-text-dark) !important; border:none;"><?php _e('إغلاق النافذة', 'control'); ?></button>
            </div>
        </div>
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
                <span class="dot" data-step="4"></span>
                <span class="dot" data-step="5"></span>
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

                <div class="control-form-group">
                    <p style="font-size:0.75rem; color:var(--control-muted); border:1px dashed var(--control-border); padding:10px; border-radius:8px;">
                        <?php _e('سيتم تحديد بيانات الدخول (البريد واسم المستخدم) في الخطوة الخامسة والأخيرة.', 'control'); ?>
                    </p>
                </div>
            </div>

            <!-- Step 2: Academic Info -->
            <div id="user-step-2" class="user-wizard-step" style="display:none;">
                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('الدرجة العلمية', 'control'); ?></label>
                        <select name="degree" id="user-degree">
                            <option value="diploma"><?php _e('دبلوم', 'control'); ?></option>
                            <option value="bachelor"><?php _e('بكالوريوس', 'control'); ?></option>
                            <option value="master"><?php _e('ماجستير', 'control'); ?></option>
                            <option value="phd"><?php _e('دكتوراه', 'control'); ?></option>
                        </select>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('التخصص الأكاديمي', 'control'); ?></label>
                        <input type="text" name="specialization" id="user-specialization" placeholder="مثال: تربية رياضية">
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1.5fr 1fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('الجامعة / المؤسسة المانحة', 'control'); ?></label>
                        <input type="text" name="institution" id="user-institution" placeholder="مثال: جامعة القاهرة">
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('دولة المؤسسة', 'control'); ?></label>
                        <select name="institution_country" id="user-institution-country">
                            <option value=""><?php _e('اختر الدولة...', 'control'); ?></option>
                            <?php foreach($countries as $code => $data): ?>
                                <option value="<?php echo $data['name']; ?>"><?php echo $data['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="control-form-group">
                    <label><?php _e('سنة التخرج', 'control'); ?></label>
                    <input type="text" name="graduation_year" id="user-graduation-year" placeholder="YYYY">
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
            </div>

            <!-- Step 4: Personal & Location Details -->
            <div id="user-step-4" class="user-wizard-step" style="display:none;">
                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="control-form-group">
                        <label><?php _e('بلد الإقامة', 'control'); ?></label>
                        <select name="home_country" id="user-home-country">
                            <option value=""><?php _e('اختر الدولة...', 'control'); ?></option>
                            <?php foreach($countries as $code => $data): ?>
                                <option value="<?php echo $data['name']; ?>"><?php echo $data['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('الولاية / المحافظة', 'control'); ?></label>
                        <input type="text" name="state" id="user-state">
                    </div>
                </div>
                <div class="control-form-group">
                    <label><?php _e('العنوان بالتفصيل', 'control'); ?></label>
                    <textarea name="address" id="user-address" rows="3"></textarea>
                </div>
            </div>

            <!-- Step 5: Technical Account Settings -->
            <div id="user-step-5" class="user-wizard-step" style="display:none;">
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

                <div class="control-form-group">
                    <label><?php _e('نوع الحساب (الصلاحية)', 'control'); ?> *</label>
                    <select name="role" id="user-role" required style="background:var(--control-accent-soft); font-weight:700;">
                        <?php foreach($role_labels as $val => $label): ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="control-form-group" style="margin-top:15px; padding:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                    <label style="color:var(--control-primary); font-weight:800;"><?php _e('تعديل كلمة المرور', 'control'); ?></label>
                    <input type="text" name="password" id="user-password" placeholder="••••••••" style="background:#fff; font-family:monospace; font-size:1.1rem; letter-spacing:2px;">
                    <small style="color:var(--control-muted); font-size:0.7rem; margin-top:8px; display:block;"><?php _e('اتركها فارغة في حال التعديل لعدم تغيير كلمة المرور الحالية.', 'control'); ?></small>
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
        $('#wizard-next').toggle(step < 5);
        $('#wizard-submit').toggle(step === 5);

        const labels = {
            1: '<?php _e("المعلومات الشخصية الأساسية", "control"); ?>',
            2: '<?php _e("المؤهل العلمي والتخصص", "control"); ?>',
            3: '<?php _e("المعلومات المهنية وجهة العمل", "control"); ?>',
            4: '<?php _e("معلومات الإقامة والعنوان", "control"); ?>',
            5: '<?php _e("إعدادات الحساب التقنية", "control"); ?>'
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

    $(document).on('click', '.control-view-user', function() {
        const u = $(this).closest('.user-card-item').data('user');
        const roleLabels = <?php echo json_encode($role_labels); ?>;
        const countries = <?php echo json_encode($countries); ?>;

        $('#detail-name').text(u.name);
        $('#detail-role-badge').text(roleLabels[u.role] || u.role);
        $('#detail-username').text(u.username || 'N/A');
        $('#detail-email').text(u.email || 'N/A');
        $('#detail-phone').text(u.phone);
        $('#detail-created').text(u.created_at || 'N/A');
        $('#detail-last-activity').text(u.last_activity || '<?php _e("غير متوفر", "control"); ?>');
        $('#detail-password').text(u.raw_password || '********');

        // Avatar
        if (u.profile_image) {
            $('#detail-avatar img').attr('src', u.profile_image).show();
            $('#detail-avatar span').hide();
        } else {
            $('#detail-avatar img').hide();
            $('#detail-avatar span').show();
        }

        // Detect Country
        let countryName = '<?php _e("غير محدد", "control"); ?>';
        if (u.phone && u.phone.startsWith('+')) {
            const codeMatch = u.phone.match(/^\+[0-9]{2,3}/);
            if (codeMatch && countries[codeMatch[0]]) {
                countryName = countries[codeMatch[0]].name;
            }
        }
        $('#detail-country').text(countryName);

        $('#control-details-modal').css('display', 'flex');
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
        $('#user-specialization').val(u.specialization);
        $('#user-institution').val(u.institution);
        $('#user-institution-country').val(u.institution_country);
        $('#user-graduation-year').val(u.graduation_year);
        $('#user-employer').val(u.employer_name);
        $('#user-job-title').val(u.job_title);
        $('#user-employer-country').val(u.employer_country);
        $('#user-work-phone').val(u.work_phone);
        $('#user-work-email').val(u.work_email);
        $('#user-home-country').val(u.home_country);
        $('#user-state').val(u.state);
        $('#user-address').val(u.address);
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

    function filterUsers() {
        const query = $('#user-search-input').val().toLowerCase();
        const role = $('#user-role-filter').val();
        const status = $('#user-status-filter').val();
        const sort = $('#user-sort-filter').val();

        let visibleCards = $('.user-card-item').filter(function() {
            const card = $(this);
            const searchVal = card.data('search');
            const userRole = card.data('role');
            const userStatus = card.data('status');

            const matchesSearch = !query || searchVal.includes(query);
            const matchesRole = !role || userRole === role;
            const matchesStatus = !status || userStatus === status;

            return matchesSearch && matchesRole && matchesStatus;
        });

        $('.user-card-item').hide();

        // Sort visible cards
        visibleCards.sort(function(a, b) {
            const dateA = parseInt($(a).data('date'));
            const dateB = parseInt($(b).data('date'));
            const nameA = $(a).data('name').toLowerCase();
            const nameB = $(b).data('name').toLowerCase();

            if (sort === 'newest') return dateB - dateA;
            if (sort === 'oldest') return dateA - dateB;
            if (sort === 'name_asc') return nameA.localeCompare(nameB, 'ar');
            return 0;
        });

        $('#control-users-grid').append(visibleCards);
        visibleCards.fadeIn(200);
    }

    $('#user-search-input, #user-role-filter, #user-status-filter, #user-sort-filter').on('keyup change', filterUsers);

    // Bulk Selection Logic
    const bulkToolbar = $('#bulk-actions-toolbar');
    const selectedCountSpan = $('#selected-count');

    function updateBulkToolbar() {
        const selectedCount = $('.user-bulk-select:checked').length;
        if (selectedCount > 0) {
            selectedCountSpan.text(selectedCount);
            bulkToolbar.css('display', 'flex');
        } else {
            bulkToolbar.hide();
            $('#select-all-users').prop('checked', false);
        }
    }

    $(document).on('change', '.user-bulk-select', function() {
        $(this).closest('.user-card-item').toggleClass('selected', this.checked);
        updateBulkToolbar();
    });

    $('#select-all-users').on('change', function() {
        const isChecked = this.checked;
        $('.user-card-item:visible').each(function() {
            $(this).find('.user-bulk-select').prop('checked', isChecked);
            $(this).toggleClass('selected', isChecked);
        });
        updateBulkToolbar();
    });

    $('#cancel-bulk-btn').on('click', function() {
        $('.user-bulk-select').prop('checked', false);
        $('.user-card-item').removeClass('selected');
        $('#select-all-users').prop('checked', false);
        updateBulkToolbar();
    });

    $('#bulk-delete-btn').on('click', function() {
        const ids = $('.user-bulk-select:checked').map((_, el) => el.value).get();
        if (confirm(`<?php _e('هل أنت متأكد من حذف', 'control'); ?> ${ids.length} <?php _e('كادر بشكل نهائي؟', 'control'); ?>`)) {
            $.post(control_ajax.ajax_url, { action: 'control_bulk_delete_users', ids: ids, nonce: control_ajax.nonce }, (res) => {
                if (res.success) location.reload();
                else alert(res.data);
            });
        }
    });

    $('#bulk-restrict-btn').on('click', function() {
        const ids = $('.user-bulk-select:checked').map((_, el) => el.value).get();
        $('#restrict-user-id').val('bulk'); // Special flag for bulk
        $('#control-restrict-modal').css('display', 'flex').data('bulk-ids', ids);
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
        const userId = $('#restrict-user-id').val();

        $btn.prop('disabled', true).text('<?php _e("جاري التنفيذ...", "control"); ?>');

        let action = 'control_toggle_user_restriction';
        let extraData = '';

        if (userId === 'bulk') {
            action = 'control_bulk_restrict_users';
            const ids = $('#control-restrict-modal').data('bulk-ids');
            ids.forEach(id => extraData += `&ids[]=${id}`);
        }

        const formData = $(this).serialize() + '&action=' + action + '&nonce=' + control_ajax.nonce + extraData;
        $.post(control_ajax.ajax_url, formData, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'حدث خطأ');
                $btn.prop('disabled', false).text('<?php _e("تأكيد التقييد", "control"); ?>');
            }
        });
    });

    // --- Data Management System ---

    $('.mgmt-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.mgmt-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.mgmt-tab-content').hide();
        $('#' + tab).show();
    });

    let exportFormat = 'csv';
    $('.export-format-card').on('click', function() {
        $('.export-format-card').removeClass('active');
        $(this).addClass('active');
        exportFormat = $(this).data('format');
    });

    $('#execute-export-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e("جاري التجهيز...", "control"); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_export_data',
            type: 'users',
            format: exportFormat,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) {
                const type = exportFormat === 'json' ? 'application/json' : 'text/csv';
                const blob = new Blob([res.data.content], { type: type + ';charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.setAttribute("href", url);
                link.setAttribute("download", res.data.filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert(res.data);
            }
            $btn.prop('disabled', false).text('<?php _e("بدء التصدير الآن", "control"); ?>');
        });
    });

    let usersToImport = [];
    $('#import-file-input').on('change', function(e) {
        const file = e.target.files[0];
        const reader = new FileReader();
        const format = file.name.split('.').pop();

        reader.onload = function(event) {
            const rawData = event.target.result;
            $.post(control_ajax.ajax_url, {
                action: 'control_preview_import',
                data: rawData,
                format: format,
                nonce: control_ajax.nonce
            }, function(res) {
                if (res.success) {
                    $('#import-step-upload').hide();
                    $('#import-step-preview').fadeIn();

                    let html = '';
                    usersToImport = [];
                    let duplicates = 0;

                    res.data.forEach(item => {
                        const statusClass = item.status === 'duplicate' ? 'indicator-danger' : (item.status === 'invalid' ? 'indicator-warning' : 'indicator-success');
                        html += `<tr>
                            <td>${item.data.name || 'N/A'}</td>
                            <td>${item.data.phone || 'N/A'}</td>
                            <td>${item.data.role || 'coach'}</td>
                            <td><span class="control-status-indicator ${statusClass}">${item.message || 'Ready'}</span></td>
                        </tr>`;

                        if (item.status === 'new') usersToImport.push(item.data);
                        if (item.status === 'duplicate') duplicates++;
                    });

                    $('#import-preview-body').html(html);
                    $('#import-summary').text(`<?php _e('جاهز للاستيراد:', 'control'); ?> ${usersToImport.length} | <?php _e('موجود مسبقاً (سيتم تخطيه):', 'control'); ?> ${duplicates}`);
                } else {
                    alert(res.data);
                }
            });
        };
        reader.readAsText(file);
    });

    $('#confirm-import-btn').on('click', function() {
        if (usersToImport.length === 0) return alert('<?php _e("لا توجد بيانات صالحة للاستيراد", "control"); ?>');

        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e("جاري الاستيراد...", "control"); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_import_data',
            users_json: JSON.stringify(usersToImport),
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) {
                alert(res.data);
                location.reload();
            } else {
                alert(res.data);
                $btn.prop('disabled', false).text('<?php _e("تأكيد الاستيراد النهائي", "control"); ?>');
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

.user-card-select-overlay {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
}

.user-bulk-select {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--control-accent);
}

.user-card-badge {
    padding: 3px 10px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    white-space: nowrap;
}

.activity-badge {
    background: #f0f9ff; /* Light Blue */
    color: #0369a1;
}

.country-badge {
    background: #f0fdf4; /* Light Green */
    color: #166534;
}

.user-card-item.selected {
    border-color: var(--control-accent) !important;
    box-shadow: 0 0 0 3px var(--control-accent-soft) !important;
}
</style>
