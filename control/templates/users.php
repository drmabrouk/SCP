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
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:#1e293b;"><?php _e('إدارة المستخدمين', 'control'); ?></h2>
    <div style="display:flex; gap:8px;">
        <?php if($can_manage): ?>
            <button id="control-add-user-btn" class="control-btn" style="background:#000; border:none; border-radius: 6px; padding: 8px 16px; font-size: 0.85rem;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:5px; font-size: 18px;"></span><?php _e('إضافة مستخدم', 'control'); ?>
            </button>
            <div class="control-dropdown" style="position:relative; display:inline-block;">
                <button class="control-btn" style="background:#fff; color:#333 !important; border:1px solid #cbd5e1; padding:8px 12px; border-radius: 6px;" onclick="jQuery('#user-tools-dropdown').toggle()">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
                <div id="user-tools-dropdown" style="display:none; position:absolute; left:0; top:110%; background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); z-index:100; min-width:140px; padding:5px;">
                    <button class="control-btn control-export-btn" data-type="users" style="width:100%; justify-content:flex-start; background:none; color:#333 !important; border:none; padding:8px 12px; font-size:0.8rem;">
                        <span class="dashicons dashicons-download" style="margin-left:8px;"></span><?php _e('تصدير CSV', 'control'); ?>
                    </button>
                    <button class="control-btn control-import-trigger" data-type="users" style="width:100%; justify-content:flex-start; background:none; color:#333 !important; border:none; padding:8px 12px; font-size:0.8rem;">
                        <span class="dashicons dashicons-upload" style="margin-left:8px;"></span><?php _e('استيراد من ملف', 'control'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="control-card" style="padding:15px; margin-bottom:15px; border:none; background:#f8fafc;">
    <div style="display:flex; gap:12px; align-items: center;">
        <div style="flex:1; position:relative;">
            <span class="dashicons dashicons-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></span>
            <input type="text" id="user-search-input" placeholder="<?php _e('ابحث بالاسم أو الهاتف...', 'control'); ?>" style="padding:8px 35px 8px 12px; font-size:0.9rem; border-radius:6px; border:1px solid #e2e8f0;">
        </div>
        <select id="user-role-filter" style="width:200px; padding:8px; font-size:0.9rem; border-radius:6px; border:1px solid #e2e8f0;">
            <option value=""><?php _e('كل الصلاحيات', 'control'); ?></option>
            <?php foreach($role_labels as $val => $label): ?>
                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="control-card" style="padding:0; overflow:hidden;">
    <div class="control-table-container">
    <table class="control-table" id="control-user-table" style="font-size:0.9rem;">
        <thead>
            <tr style="background:#f1f5f9; border-bottom:1px solid #e2e8f0;">
                <th style="padding:12px 15px;"><?php _e('الاسم والمعلومات', 'control'); ?></th>
                <th style="padding:12px 15px;"><?php _e('الدور / التخصص', 'control'); ?></th>
                <th style="padding:12px 15px;"><?php _e('الحالة', 'control'); ?></th>
                <th style="padding:12px 15px; text-align:left;"><?php _e('إجراءات', 'control'); ?></th>
            </tr>
        </thead>
        <tbody id="control-users-table-body">
            <?php foreach($users as $u): ?>
                <tr data-user='<?php echo json_encode($u); ?>' data-role="<?php echo $u->role; ?>" data-search="<?php echo esc_attr(strtolower($u->name . ' ' . $u->phone)); ?>" style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 15px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:36px; height:36px; background:#e2e8f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#64748b; font-weight:700;">
                                <?php echo strtoupper(substr($u->name, 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight:700; color:#1e293b;"><?php echo esc_html($u->name); ?></div>
                                <div style="font-size:0.75rem; color:#64748b;"><?php echo esc_html($u->phone); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:10px 15px;">
                        <span class="control-capsule" style="background:#e0f2fe; color:#0369a1; font-weight:600; font-size:0.75rem;">
                            <?php echo $role_labels[$u->role] ?? $u->role; ?>
                        </span>
                    </td>
                    <td style="padding:10px 15px;">
                        <?php if($u->is_restricted): ?>
                            <span class="control-status-indicator indicator-danger" style="padding:2px 6px; font-size:0.7rem;"><span class="dashicons dashicons-lock" style="font-size:12px; width:12px; height:12px;"></span> <?php _e('مقيد', 'control'); ?></span>
                        <?php else: ?>
                            <span class="control-status-indicator indicator-success" style="padding:2px 6px; font-size:0.7rem;"><?php _e('نشط', 'control'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 15px; text-align:left;">
                        <div style="display:flex; gap:6px; justify-content: flex-end;">
                            <?php if($can_manage): ?>
                                <button class="control-btn control-edit-user" title="<?php _e('تعديل', 'control'); ?>" style="width:32px; height:32px; padding:0; background:#f1f5f9; color:#475569 !important; border:none; border-radius:6px;"><span class="dashicons dashicons-edit" style="font-size:16px;"></span></button>
                                <?php if($u->username !== 'admin' && $u->phone !== '1234567890'): ?>
                                    <button class="control-btn control-restrict-user" data-id="<?php echo $u->id; ?>" title="<?php echo $u->is_restricted ? __('إلغاء التقييد', 'control') : __('تقييد الحساب', 'control'); ?>" style="width:32px; height:32px; padding:0; background:<?php echo $u->is_restricted ? '#ecfdf5' : '#fff7ed'; ?>; color:<?php echo $u->is_restricted ? '#059669' : '#d97706'; ?> !important; border:none; border-radius:6px;">
                                        <span class="dashicons <?php echo $u->is_restricted ? 'dashicons-unlock' : 'dashicons-lock'; ?>" style="font-size:16px;"></span>
                                    </button>
                                    <button class="control-btn control-delete-user" data-id="<?php echo $u->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="width:32px; height:32px; padding:0; background:#fef2f2; color:#ef4444 !important; border:none; border-radius:6px;"><span class="dashicons dashicons-trash" style="font-size:16px;"></span></button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- User Modal -->
<div id="control-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.4); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(2px);">
    <div class="control-card" style="width:100%; max-width:480px; padding:25px; border-radius:12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <h3 id="modal-title" style="font-size:1.1rem; margin-bottom:20px; color: #000; border-bottom:1px solid #f1f5f9; padding-bottom:15px;"><?php _e('بيانات المستخدم', 'control'); ?></h3>
        <form id="control-user-form">
            <input type="hidden" name="id" id="user-id">
            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="control-form-group">
                    <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:5px;"><?php _e('الاسم بالكامل', 'control'); ?></label>
                    <input type="text" name="name" id="user-name" required style="padding:8px 12px;">
                </div>
                <div class="control-form-group">
                    <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:5px;"><?php _e('رقم الهاتف', 'control'); ?></label>
                    <input type="text" name="phone" id="user-phone" required style="padding:8px 12px;">
                </div>
            </div>
            <div class="control-grid" style="grid-template-columns: 1.5fr 1fr; gap: 12px;">
                <div class="control-form-group">
                    <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:5px;"><?php _e('البريد الإلكتروني', 'control'); ?></label>
                    <input type="email" name="email" id="user-email" style="padding:8px 12px;">
                </div>
                <div class="control-form-group">
                    <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:5px;"><?php _e('اسم المستخدم', 'control'); ?></label>
                    <input type="text" name="username" id="user-username" style="padding:8px 12px;">
                </div>
            </div>
            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="control-form-group">
                    <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:5px;"><?php _e('كلمة المرور', 'control'); ?></label>
                    <input type="password" name="password" id="user-password" style="padding:8px 12px;">
                </div>
                <div class="control-form-group">
                    <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:5px;"><?php _e('صلاحية النظام', 'control'); ?></label>
                    <select name="role" id="user-role" style="padding:8px 12px;">
                        <?php foreach($role_labels as $val => $label): ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px; border-top:1px solid #f1f5f9; padding-top:20px;">
                <button type="submit" class="control-btn" style="flex:2; background:#000; color:#fff; border:none; border-radius: 6px; height:42px; font-weight:700;"><?php _e('حفظ التعديلات', 'control'); ?></button>
                <button type="button" class="control-btn close-user-modal" style="flex:1; background:#f1f5f9; color:#475569 !important; border:none; border-radius: 6px; height:42px;"><?php _e('إلغاء', 'control'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#control-user-modal');

    $('#control-add-user-btn').on('click', function() {
        $('#control-user-form')[0].reset();
        $('#user-id').val('');
        $('#user-password').prop('required', true);
        $('#modal-title').text('<?php _e('إضافة مستخدم جديد', 'control'); ?>');
        modal.css('display', 'flex');
    });

    $(document).on('click', '.control-edit-user', function() {
        const u = $(this).closest('tr').data('user');
        $('#user-id').val(u.id);
        $('#user-username').val(u.username);
        $('#user-phone').val(u.phone);
        $('#user-name').val(u.name);
        $('#user-email').val(u.email);
        $('#user-role').val(u.role);
        $('#user-password').val('').prop('required', false);
        $('#modal-title').text('<?php _e('تعديل بيانات المستخدم', 'control'); ?>');
        modal.css('display', 'flex');
    });

    $('.close-user-modal').on('click', function() { modal.hide(); });

    // Client-side search and filter
    $('#user-search-input, #user-role-filter').on('keyup change', function() {
        const query = $('#user-search-input').val().toLowerCase();
        const role = $('#user-role-filter').val();

        $('#control-users-table-body tr').each(function() {
            const tr = $(this);
            const searchVal = tr.data('search');
            const userRole = tr.data('role');

            const matchesSearch = !query || searchVal.includes(query);
            const matchesRole = !role || userRole === role;

            if (matchesSearch && matchesRole) tr.show();
            else tr.hide();
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
