<?php
global $wpdb;
$roles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_roles" );

$available_permissions = array(
    'dashboard'     => array( 'label' => 'عرض لوحة التحكم', 'category' => 'النظام' ),
    'users_view'    => array( 'label' => 'عرض قائمة الكوادر', 'category' => 'الكوادر' ),
    'users_manage'  => array( 'label' => 'إضافة وتعديل الكوادر', 'category' => 'الكوادر' ),
    'users_delete'  => array( 'label' => 'حذف الكوادر', 'category' => 'الكوادر' ),
    'roles_manage'  => array( 'label' => 'إدارة الصلاحيات والأدوار', 'category' => 'النظام' ),
    'settings_manage' => array( 'label' => 'إدارة إعدادات النظام', 'category' => 'النظام' ),
    'audit_view'    => array( 'label' => 'عرض سجل النشاطات', 'category' => 'النظام' ),
    'backup_manage' => array( 'label' => 'إدارة النسخ الاحتياطي', 'category' => 'النظام' ),
);
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:var(--control-text-dark);"><?php _e('الأدوار وصلاحيات النظام', 'control'); ?></h2>
    <button id="add-role-btn" class="control-btn" style="background:var(--control-primary); border:none;">
        <span class="dashicons dashicons-plus-alt" style="margin-left:5px;"></span><?php _e('إضافة دور جديد', 'control'); ?>
    </button>
</div>

<div class="control-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <?php foreach($roles as $role):
        $perms = json_decode($role->permissions, true) ?: array();
        $perm_count = count($perms);
    ?>
        <div class="control-card role-card" data-role='<?php echo json_encode($role); ?>'>
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                <div>
                    <h3 style="margin:0;"><?php echo esc_html($role->role_name); ?></h3>
                    <code style="font-size:0.7rem; color:var(--control-muted);"><?php echo esc_html($role->role_key); ?></code>
                </div>
                <?php if($role->is_system): ?>
                    <span class="control-status-indicator indicator-accent" style="font-size:0.6rem;"><?php _e('أساسي', 'control'); ?></span>
                <?php endif; ?>
            </div>

            <div style="font-size:0.8rem; color:var(--control-text); margin-bottom:20px;">
                <strong><?php echo $perm_count; ?></strong> <?php _e('صلاحيات مفعلة', 'control'); ?>
            </div>

            <div style="display:flex; gap:10px; border-top:1px solid var(--control-border); padding-top:15px;">
                <button class="control-btn edit-role-btn" style="flex:1; background:var(--control-bg); color:var(--control-text) !important; border:none; font-size:0.8rem;">
                    <span class="dashicons dashicons-edit" style="margin-left:5px;"></span><?php _e('تعديل', 'control'); ?>
                </button>
                <?php if(!$role->is_system): ?>
                    <button class="control-btn delete-role-btn" data-id="<?php echo $role->id; ?>" style="background:#fef2f2; color:#ef4444 !important; border:none; width:42px; padding:0;">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Role Modal -->
<div id="role-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:600px; padding:0; border-radius:20px; overflow:hidden;">
        <div style="background:var(--control-primary); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 id="role-modal-title" style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('إعدادات الدور والصلاحيات', 'control'); ?></h3>
            <button class="close-role-modal" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>

        <form id="role-form" style="padding:30px;">
            <input type="hidden" name="id" id="role-db-id">

            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:25px;">
                <div class="control-form-group">
                    <label><?php _e('اسم الدور (بالعربية)', 'control'); ?></label>
                    <input type="text" name="role_name" id="role-name-input" required placeholder="مثال: مدير تقني">
                </div>
                <div class="control-form-group">
                    <label><?php _e('مفتاح الدور (English Key)', 'control'); ?></label>
                    <input type="text" name="role_key" id="role-key-input" required placeholder="example_role">
                </div>
            </div>

            <h4 style="margin:0 0 15px 0; font-size:0.95rem; border-bottom:1px solid var(--control-border); padding-bottom:10px;"><?php _e('مصفوفة الصلاحيات', 'control'); ?></h4>

            <div style="max-height:300px; overflow-y:auto; padding-right:5px;">
                <?php
                $categories = array();
                foreach($available_permissions as $key => $p) {
                    $categories[$p['category']][$key] = $p['label'];
                }

                foreach($categories as $cat => $perms): ?>
                    <div style="margin-bottom:20px;">
                        <div style="font-weight:800; font-size:0.75rem; color:var(--control-accent); text-transform:uppercase; margin-bottom:10px;"><?php echo $cat; ?></div>
                        <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                            <?php foreach($perms as $k => $l): ?>
                                <label style="display:flex; align-items:center; gap:10px; background:var(--control-bg); padding:10px; border-radius:8px; cursor:pointer; font-size:0.85rem;">
                                    <input type="checkbox" name="permissions[<?php echo $k; ?>]" value="1" class="perm-checkbox">
                                    <?php echo $l; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex; gap:15px; margin-top:30px; border-top:1px solid var(--control-border); padding-top:20px;">
                <button type="submit" class="control-btn" style="flex:2; background:var(--control-primary); border:none; font-weight:800;"><?php _e('حفظ التغييرات', 'control'); ?></button>
                <button type="button" class="control-btn close-role-modal" style="flex:1; background:var(--control-bg); color:var(--control-text) !important; border:none;"><?php _e('إلغاء', 'control'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#role-modal');

    $('#add-role-btn').on('click', function() {
        $('#role-form')[0].reset();
        $('#role-db-id').val('');
        $('#role-key-input').prop('readonly', false);
        $('#role-modal-title').text('<?php _e("إضافة دور جديد", "control"); ?>');
        modal.css('display', 'flex');
    });

    $(document).on('click', '.edit-role-btn', function() {
        const r = $(this).closest('.role-card').data('role');
        $('#role-db-id').val(r.id);
        $('#role-name-input').val(r.role_name);
        $('#role-key-input').val(r.role_key).prop('readonly', r.is_system == 1);

        $('#role-form .perm-checkbox').prop('checked', false);
        const perms = JSON.parse(r.permissions);
        if (perms) {
            for (const [key, val] of Object.entries(perms)) {
                if (val) $(`.perm-checkbox[name="permissions[${key}]"]`).prop('checked', true);
            }
        }

        $('#role-modal-title').text('<?php _e("تعديل الدور", "control"); ?>');
        modal.css('display', 'flex');
    });

    $('.close-role-modal').on('click', function() { modal.hide(); });

    $('#role-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=control_save_role&nonce=' + control_ajax.nonce;
        $.post(control_ajax.ajax_url, formData, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'حدث خطأ');
            }
        });
    });

    $(document).on('click', '.delete-role-btn', function() {
        if (!confirm('<?php _e("هل أنت متأكد من حذف هذا الدور؟ سيتم فقدان كافة الصلاحيات المرتبطة به.", "control"); ?>')) return;
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, { action: 'control_delete_role', id: id, nonce: control_ajax.nonce }, () => location.reload());
    });
});
</script>
