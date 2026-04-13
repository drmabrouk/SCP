<?php
global $wpdb;
$current_user = Control_Auth::current_user();
if ( Control_Auth::is_admin() ) {
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}control_staff");
    ?>
    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم النظام', 'control'); ?></h2>
    </div>

    <!-- System Metrics -->
    <div class="control-metrics-grid" style="margin-bottom:20px; grid-template-columns: 1fr;">
        <div class="control-metric-card">
            <div class="control-metric-icon" style="background: #f8fafc; color: #D4AF37;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title"><?php _e('إجمالي المستخدمين المسجلين', 'control'); ?></div>
                <div class="control-metric-value"><?php echo number_format($total_users); ?></div>
            </div>
        </div>
    </div>

    <div class="control-grid main-dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="control-card" style="border-top: 4px solid #000; padding: 20px;">
            <h3 style="margin-bottom:15px;"><?php _e('نظرة عامة على النظام', 'control'); ?></h3>
            <p><?php _e('مرحباً بك في نظام كنترول الإداري المتكامل. يمكنك استخدام القائمة الجانبية لإدارة كافة جوانب النظام والمستخدمين.', 'control'); ?></p>
            <div style="margin-top: 20px;">
                <a href="<?php echo add_query_arg('control_view', 'users'); ?>" class="control-btn" style="background:#000; border:none;"><?php _e('انتقل لإدارة المستخدمين', 'control'); ?></a>
            </div>
        </div>

        <div class="control-card" style="padding: 20px;">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin:0; font-size:1rem;"><?php _e('آخر النشاطات والعمليات', 'control'); ?></h3>
                <a href="<?php echo add_query_arg('control_view', 'settings'); ?>#tab-audit" style="font-size:0.7rem; color:#D4AF37; font-weight:700; text-decoration:none;"><?php _e('عرض الكل', 'control'); ?></a>
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php
                $recent_logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}control_activity_logs ORDER BY action_date DESC LIMIT 5");
                if (empty($recent_logs)): ?>
                    <p style="font-size:0.8rem; color:#94a3b8; text-align:center; padding:20px;"><?php _e('لا توجد نشاطات مسجلة حالياً.', 'control'); ?></p>
                <?php else:
                    foreach($recent_logs as $log): ?>
                        <div style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                            <small style="display:block; color:#64748b; font-size:0.65rem;"><?php echo date('H:i - Y/m/d', strtotime($log->action_date)); ?></small>
                            <div style="font-size:0.75rem; font-weight:600;"><?php echo esc_html($log->description); ?></div>
                        </div>
                    <?php endforeach;
                endif; ?>
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
