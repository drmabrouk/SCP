<?php
global $wpdb;
$current_user = Control_Auth::current_user();
if ( Control_Auth::is_admin() ) {
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}control_staff");
    ?>
    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم النظام', 'control'); ?></h2>
    </div>

    <!-- Enhanced System Metrics -->
    <div class="control-metrics-grid" style="margin-bottom:25px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
        <div class="control-metric-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border: none;">
            <div class="control-metric-icon" style="background: rgba(255,255,255,0.1); color: #D4AF37;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title" style="color: rgba(255,255,255,0.7);"><?php _e('إجمالي المستخدمين', 'control'); ?></div>
                <div class="control-metric-value" style="color: #fff;"><?php echo number_format($total_users); ?></div>
            </div>
        </div>

        <?php
        $roles_count = $wpdb->get_results("SELECT role, COUNT(*) as count FROM {$wpdb->prefix}control_staff GROUP BY role", OBJECT_K);
        $role_labels = array(
            'admin'       => 'مديري النظام',
            'coach'       => 'المدربين',
            'therapist'   => 'الأخصائيين',
            'nutritionist' => 'خبراء التغذية',
            'pe_teacher'  => 'المعلمين',
            'researcher'  => 'الباحثين'
        );

        $primary_role = 'coach'; // Default highlight
        $primary_count = $roles_count[$primary_role]->count ?? 0;
        ?>
        <div class="control-metric-card">
            <div class="control-metric-icon" style="background: var(--control-accent-soft); color: var(--control-accent);">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title"><?php echo $role_labels[$primary_role]; ?></div>
                <div class="control-metric-value"><?php echo number_format($primary_count); ?></div>
            </div>
        </div>

        <div class="control-metric-card">
            <div class="control-metric-icon" style="background: #f0fdf4; color: #10b981;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title"><?php _e('الحسابات النشطة', 'control'); ?></div>
                <div class="control-metric-value"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}control_staff WHERE is_restricted = 0"); ?></div>
            </div>
        </div>
    </div>

    <div class="control-grid main-dashboard-grid" style="grid-template-columns: 1.6fr 1fr; gap: 25px;">
        <div class="control-dashboard-main-column">
            <!-- System Overview Card -->
            <div class="control-card" style="padding: 30px; border-top: 5px solid var(--control-accent);">
                <div style="display:flex; align-items:center; gap:20px; margin-bottom:25px;">
                    <div style="width:60px; height:60px; background:var(--control-bg); border-radius:15px; display:flex; align-items:center; justify-content:center;">
                        <span class="dashicons dashicons-performance" style="font-size:30px; color:var(--control-accent); width:30px; height:30px;"></span>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:1.2rem;"><?php _e('نظرة عامة على أداء النظام', 'control'); ?></h3>
                        <p style="margin:5px 0 0 0; color:var(--control-muted); font-size:0.85rem;"><?php _e('ملخص سريع لحالة الكوادر والعمليات الإدارية الجارية.', 'control'); ?></p>
                    </div>
                </div>

                <div class="control-grid" style="grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <?php foreach($role_labels as $role_key => $label):
                        if ($role_key == $primary_role) continue;
                        $count = $roles_count[$role_key]->count ?? 0;
                        ?>
                        <div style="background:var(--control-bg); padding:15px; border-radius:12px; text-align:center; border:1px solid var(--control-border);">
                            <div style="font-size:0.75rem; color:var(--control-muted); margin-bottom:5px;"><?php echo $label; ?></div>
                            <div style="font-size:1.1rem; font-weight:800; color:var(--control-text-dark);"><?php echo number_format($count); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--control-border); display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.85rem; color:var(--control-muted);"><?php _e('يمكنك إدارة كافة هذه الكوادر من خلال وحدة إدارة المستخدمين.', 'control'); ?></span>
                    <a href="<?php echo add_query_arg('control_view', 'users'); ?>" class="control-btn" style="background:var(--control-primary); border:none;"><?php _e('إدارة الكوادر', 'control'); ?></a>
                </div>
            </div>

            <!-- Quick Links / Modular Section -->
            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="control-card" style="padding: 20px;">
                    <h4 style="margin:0 0 15px 0; font-size:0.9rem; font-weight:800;"><?php _e('روابط سريعة', 'control'); ?></h4>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <a href="<?php echo add_query_arg('control_view', 'settings'); ?>" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--control-text); font-size:0.85rem; padding:10px; background:var(--control-bg); border-radius:8px;">
                            <span class="dashicons dashicons-admin-generic"></span> <?php _e('إعدادات النظام', 'control'); ?>
                        </a>
                        <a href="<?php echo add_query_arg('control_view', 'settings'); ?>#tab-backup" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--control-text); font-size:0.85rem; padding:10px; background:var(--control-bg); border-radius:8px;">
                            <span class="dashicons dashicons-cloud-save"></span> <?php _e('النسخ الاحتياطي', 'control'); ?>
                        </a>
                    </div>
                </div>
                <div class="control-card" style="padding: 20px;">
                    <h4 style="margin:0 0 15px 0; font-size:0.9rem; font-weight:800;"><?php _e('إحصائيات التقارير', 'control'); ?></h4>
                    <div style="text-align:center; padding:10px 0;">
                        <span class="dashicons dashicons-chart-bar" style="font-size:40px; color:var(--control-border); width:40px; height:40px; margin-bottom:10px;"></span>
                        <p style="font-size:0.75rem; color:var(--control-muted); margin:0;"><?php _e('سيتم تفعيل الرسوم البيانية في التحديث القادم.', 'control'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="control-dashboard-side-column">
            <div class="control-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid var(--control-border); display:flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size:0.95rem;"><?php _e('آخر النشاطات', 'control'); ?></h3>
                    <a href="<?php echo add_query_arg('control_view', 'settings'); ?>#tab-audit" style="font-size:0.7rem; color:var(--control-accent); font-weight:700; text-decoration:none;"><?php _e('عرض الكل', 'control'); ?></a>
                </div>
                <div style="max-height: 500px; overflow-y: auto; padding: 10px 20px;">
                    <?php
                    $recent_logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}control_activity_logs ORDER BY action_date DESC LIMIT 8");
                    if (empty($recent_logs)): ?>
                        <div style="text-align:center; padding:40px 20px;">
                            <span class="dashicons dashicons-info" style="font-size:30px; color:var(--control-border); width:30px; height:30px;"></span>
                            <p style="font-size:0.8rem; color:#94a3b8; margin-top:10px;"><?php _e('لا توجد نشاطات مسجلة حالياً.', 'control'); ?></p>
                        </div>
                    <?php else:
                        foreach($recent_logs as $log): ?>
                            <div style="padding: 15px 0; border-bottom: 1px solid #f1f5f9; display:flex; gap:12px;">
                                <div style="width:8px; height:8px; border-radius:50%; background:var(--control-accent); margin-top:5px; flex-shrink:0;"></div>
                                <div style="flex:1;">
                                    <div style="font-size:0.8rem; font-weight:600; color:var(--control-text-dark); line-height:1.4;"><?php echo esc_html($log->description); ?></div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
                                        <small style="color:var(--control-muted); font-size:0.65rem;"><?php echo date('H:i - Y/m/d', strtotime($log->action_date)); ?></small>
                                        <small style="background:var(--control-bg); padding:2px 6px; border-radius:4px; font-size:0.6rem; color:var(--control-muted);"><?php echo esc_html($log->user_id); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:#1e293b;"><?php _e('لوحة المعلومات', 'control'); ?></h2>
    </div>
    <div class="control-card" style="border-radius: 12px; padding: 20px;">
        <p><?php _e('أهلاً بك في نظام كنترول الإداري.', 'control'); ?></p>
        <p style="color:#64748b; font-size:0.9rem;"><?php _e('برجاء التواصل مع الإدارة إذا كنت بحاجة إلى صلاحيات إضافية للوصول إلى لوحة التحكم.', 'control'); ?></p>
    </div>
    <?php
}
?>
