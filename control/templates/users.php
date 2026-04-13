<?php
$users = Control_Auth::get_all_users();
$can_manage = Control_Auth::is_admin();
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إدارة مستخدمي النظام', 'control'); ?></h2>
    <div style="display:flex; gap:10px;">
        <?php if($can_manage): ?>
            <button class="control-btn control-export-btn" data-type="users" style="background:#fff; color:#000 !important; border:1px solid #ddd; padding:8px 15px;">
                <span class="dashicons dashicons-download" style="margin-left:5px;"></span><?php _e('تصدير', 'control'); ?>
            </button>
            <button class="control-btn control-import-trigger" data-type="users" style="background:#fff; color:#000 !important; border:1px solid #ddd; padding:8px 15px;">
                <span class="dashicons dashicons-upload" style="margin-left:5px;"></span><?php _e('استيراد', 'control'); ?>
            </button>
            <button id="control-add-user-btn" class="control-btn" style="background:#000; border:none; border-radius: 8px;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة مستخدم جديد', 'control'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- User Search & Filter Engine -->
<div class="control-card" style="margin-bottom:25px; border-top:3px solid #000;">
    <div style="display:flex; gap:15px; flex-wrap: wrap;">
        <div class="control-form-group" style="flex:2; margin-bottom:0; min-width:200px;">
            <input type="text" id="user-search-input" placeholder="<?php _e('البحث بالاسم أو رقم الهاتف...', 'control'); ?>" style="padding:10px 15px;">
        </div>
        <div class="control-form-group" style="flex:1; margin-bottom:0; min-width:150px;">
            <select id="user-role-filter">
                <option value=""><?php _e('تصفية حسب الدور', 'control'); ?></option>
                <option value="admin"><?php _e('مدير نظام', 'control'); ?></option>
                <option value="owner"><?php _e('مالك عقار', 'control'); ?></option>
                <option value="investor"><?php _e('مستثمر', 'control'); ?></option>
                <option value="tenant"><?php _e('مستأجر', 'control'); ?></option>
                <option value="employee"><?php _e('موظف', 'control'); ?></option>
            </select>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="control-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:550px; padding:40px; border-radius:12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <h3 id="modal-title" style="font-size:1.4rem; margin-bottom:30px; color: #000;"><?php _e('بيانات المستخدم', 'control'); ?></h3>
        <form id="control-user-form">
            <input type="hidden" name="id" id="user-id">
            <div class="control-form-group">
                <input type="text" name="username" id="user-username" placeholder="<?php _e('اسم المستخدم', 'control'); ?>" style="width:100%;">
            </div>
            <div class="control-form-group">
                <input type="text" name="phone" id="user-phone" placeholder="<?php _e('رقم الهاتف', 'control'); ?>" required style="width:100%;">
            </div>
            <div class="control-form-group">
                <input type="text" name="name" id="user-name" placeholder="<?php _e('الاسم بالكامل', 'control'); ?>" required style="width:100%;">
            </div>
            <div class="control-form-group">
                <input type="email" name="email" id="user-email" placeholder="<?php _e('البريد الإلكتروني', 'control'); ?>" style="width:100%;">
            </div>
            <div class="control-form-group">
                <input type="password" name="password" id="user-password" placeholder="<?php _e('كلمة المرور', 'control'); ?>" style="width:100%;">
            </div>
            <div class="control-form-group">
                <select name="role" id="user-role" style="width:100%;">
                    <option value="admin"><?php _e('مدير نظام', 'control'); ?></option>
                    <option value="owner"><?php _e('مالك عقار', 'control'); ?></option>
                    <option value="investor"><?php _e('مستثمر', 'control'); ?></option>
                    <option value="tenant"><?php _e('مستأجر', 'control'); ?></option>
                    <option value="employee"><?php _e('موظف', 'control'); ?></option>
                </select>
            </div>
            <div style="display:flex; gap:15px; margin-top:30px;">
                <button type="submit" class="control-btn" style="flex:1; background:#000; border:none; border-radius: 8px;"><?php _e('حفظ البيانات', 'control'); ?></button>
                <button type="button" class="control-btn close-user-modal" style="flex:1; background:#64748b; border:none; border-radius: 8px;"><?php _e('إلغاء', 'control'); ?></button>
            </div>
        </form>
    </div>
</div>

<div style="background:#fff; border:1px solid #e2e8f0; border-radius: 12px; overflow:hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
    <div class="control-table-container">
    <table class="control-table" id="control-user-table">
        <thead>
            <tr>
                <th><?php _e('المستخدم', 'control'); ?></th>
                <th><?php _e('الهاتف', 'control'); ?></th>
                <th><?php _e('الاسم', 'control'); ?></th>
                <th><?php _e('الصلاحية', 'control'); ?></th>
                <th><?php _e('الحالة', 'control'); ?></th>
                <th><?php _e('إجراءات', 'control'); ?></th>
            </tr>
        </thead>
        <tbody id="control-users-table-body">
            <?php foreach($users as $u): ?>
                <tr data-user='<?php echo json_encode($u); ?>' data-role="<?php echo $u->role; ?>" data-search="<?php echo esc_attr(strtolower($u->name . ' ' . $u->phone)); ?>">
                    <td><strong><?php echo esc_html($u->username ?: '-'); ?></strong></td>
                    <td><?php echo esc_html($u->phone); ?></td>
                    <td><?php echo esc_html($u->name); ?></td>
                    <td><span class="control-capsule capsule-accent"><?php
                        $roles = array('admin' => 'مدير نظام', 'owner' => 'مالك', 'investor' => 'مستثمر', 'tenant' => 'مستأجر', 'employee' => 'موظف');
                        echo $roles[$u->role] ?? $u->role;
                    ?></span></td>
                    <td>
                        <?php if($u->is_restricted): ?>
                            <span class="control-status-indicator indicator-danger"><span class="dashicons dashicons-lock" style="font-size:14px; width:14px; height:14px;"></span> <?php _e('مقيد', 'control'); ?></span>
                        <?php else: ?>
                            <span class="control-status-indicator indicator-success"><?php _e('نشط', 'control'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:left;">
                        <div style="display:flex; gap:5px; justify-content: flex-end;">
                            <?php if($can_manage): ?>
                                <button class="control-btn control-edit-user" title="<?php _e('تعديل', 'control'); ?>" style="padding:4px 8px; font-size:0.75rem; background:#000; border:none; border-radius: 4px;"><span class="dashicons dashicons-edit"></span></button>
                                <?php if($u->username !== 'admin' && $u->phone !== '1234567890'): ?>
                                    <button class="control-btn control-restrict-user" data-id="<?php echo $u->id; ?>" title="<?php echo $u->is_restricted ? __('إلغاء التقييد', 'control') : __('تقييد الحساب', 'control'); ?>" style="padding:4px 8px; background:<?php echo $u->is_restricted ? '#059669' : '#d97706'; ?>; border:none; border-radius: 4px;">
                                        <span class="dashicons <?php echo $u->is_restricted ? 'dashicons-unlock' : 'dashicons-lock'; ?>"></span>
                                    </button>
                                    <button class="control-btn control-delete-user" data-id="<?php echo $u->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="padding:4px 8px; font-size:0.75rem; background:#333; border:none; border-radius: 4px;"><span class="dashicons dashicons-trash"></span></button>
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
});
</script>
