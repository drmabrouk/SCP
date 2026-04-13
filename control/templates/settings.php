<?php
global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_settings", OBJECT_K );
$fullscreen_pass = $settings['fullscreen_password']->setting_value ?? '123456789';
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إعدادات النظام', 'control'); ?></h2>
</div>

<div class="control-settings-wrapper">

    <div class="control-tabs" style="display:flex; gap:10px; margin-bottom:25px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
        <button class="control-tab-btn active" data-tab="tab-identity"><?php _e('هوية النظام', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-pwa"><?php _e('تطبيق الجوال', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-audit"><?php _e('سجل النشاطات', 'control'); ?></button>
    </div>

    <div class="control-tab-content-container">
        <!-- Section 1: System Identity -->
        <div id="tab-identity" class="control-tab-content active">
        <div class="control-card" style="border-top: 5px solid #D4AF37;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px; color: #000;">
                <span class="dashicons dashicons-id-alt"></span> <?php _e('هوية النظام والشركة', 'control'); ?>
            </h3>
            <form id="control-identity-form" class="control-system-settings-form">
                <div class="control-form-group">
                    <input type="text" name="system_name" value="<?php echo esc_attr($settings['system_name']->setting_value ?? 'Control'); ?>" placeholder="<?php _e('اسم النظام (يظهر في القائمة)', 'control'); ?>">
                </div>
                <div class="control-form-group">
                    <input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name']->setting_value ?? 'Control'); ?>" placeholder="<?php _e('اسم الشركة / المؤسسة', 'control'); ?>">
                </div>
                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="control-form-group">
                        <input type="text" name="company_phone" value="<?php echo esc_attr($settings['company_phone']->setting_value ?? ''); ?>" placeholder="<?php _e('رقم الهاتف', 'control'); ?>">
                    </div>
                    <div class="control-form-group">
                        <input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email']->setting_value ?? ''); ?>" placeholder="<?php _e('البريد الإلكتروني', 'control'); ?>">
                    </div>
                </div>
                <div class="control-form-group">
                    <textarea name="company_address" rows="2" placeholder="<?php _e('العنوان بالتفصيل ليظهر في التقارير', 'control'); ?>"><?php echo esc_textarea($settings['company_address']->setting_value ?? ''); ?></textarea>
                </div>
                <div class="control-form-group">
                    <div style="display:flex; gap:10px; align-items: center;">
                        <input type="text" name="company_logo" id="company-logo-url" value="<?php echo esc_attr($settings['company_logo']->setting_value ?? ''); ?>" placeholder="<?php _e('رابط شعار الشركة', 'control'); ?>" style="flex:1;">
                        <button type="button" class="control-upload-btn control-btn" style="background:#000; border:none;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                    <div id="logo-preview-container" style="margin-top: 15px; text-align: center; <?php echo empty($settings['company_logo']->setting_value) ? 'display:none;' : ''; ?>">
                        <img id="logo-preview" src="<?php echo esc_url($settings['company_logo']->setting_value ?? ''); ?>" style="max-height: 80px; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; object-fit: contain;">
                    </div>
                </div>
                <div class="control-form-group">
                    <div style="display:flex; gap:10px; align-items: center;">
                        <input type="text" name="pwa_icon_url" id="pwa-icon-url" value="<?php echo esc_attr($settings['pwa_icon_url']->setting_value ?? ''); ?>" placeholder="<?php _e('رابط أيقونة التطبيق (PWA Icon)', 'control'); ?>" style="flex:1;">
                        <button type="button" class="control-upload-btn control-btn" style="background:#000; border:none;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                </div>
                <button type="submit" class="control-btn control-btn-accent" style="width:100%; height:50px; border-radius: 8px; font-weight: 800;"><?php _e('حفظ كافة التعديلات', 'control'); ?></button>
            </form>
        </div>
        </div>

        <!-- Section 2: PWA & Mobile App Settings -->
        <div id="tab-pwa" class="control-tab-content" style="display:none;">
        <div class="control-card" style="border-top: 5px solid #000;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:25px; color:#000;">
                <span class="dashicons dashicons-smartphone"></span> <?php _e('إعدادات تطبيق الجوال', 'control'); ?>
            </h3>
            <form class="control-system-settings-form">
                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="control-form-group">
                        <input type="text" name="pwa_app_name" value="<?php echo esc_attr($settings['pwa_app_name']->setting_value ?? 'Control'); ?>" placeholder="<?php _e('اسم التطبيق', 'control'); ?>">
                    </div>
                    <div class="control-form-group">
                        <input type="text" name="pwa_short_name" value="<?php echo esc_attr($settings['pwa_short_name']->setting_value ?? 'Control'); ?>" placeholder="<?php _e('الاسم المختصر', 'control'); ?>">
                    </div>
                </div>
                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="control-form-group">
                        <input type="color" name="pwa_theme_color" value="<?php echo esc_attr($settings['pwa_theme_color']->setting_value ?? '#000000'); ?>" style="height:45px; cursor: pointer;">
                    </div>
                    <div class="control-form-group">
                        <input type="color" name="pwa_bg_color" value="<?php echo esc_attr($settings['pwa_bg_color']->setting_value ?? '#ffffff'); ?>" style="height:45px; cursor: pointer;">
                    </div>
                </div>
                <button type="submit" class="control-btn control-btn-accent" style="width:100%; height:45px; border-radius: 8px;"><?php _e('تحديث إعدادات التطبيق', 'control'); ?></button>
            </form>
        </div>
        </div>

        <!-- Section 3: Activity Audit Log -->
        <div id="tab-audit" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 5px solid #000; padding:15px;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:15px;">
                    <h3 style="margin:0; color:#000; font-size:1rem;"><?php _e('سجل نشاطات النظام وتتبع العمليات', 'control'); ?></h3>
                    <button id="control-export-audit-pdf" class="control-btn" style="background:#000; border:none; border-radius: 6px; padding:5px 15px; font-size:0.8rem;"><span class="dashicons dashicons-media-document" style="margin-left:5px;"></span><?php _e('تصدير PDF', 'control'); ?></button>
                </div>
                <div style="max-height:600px; overflow-y:auto; border:1px solid #eee; border-radius:8px;">
                    <table class="control-table" style="font-size:0.8rem;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="padding:10px;"><?php _e('المستخدم', 'control'); ?></th>
                                <th style="padding:10px;"><?php _e('العملية', 'control'); ?></th>
                                <th style="padding:10px;"><?php _e('التفاصيل', 'control'); ?></th>
                                <th style="padding:10px;"><?php _e('التاريخ والوقت', 'control'); ?></th>
                                <th style="padding:10px; text-align:left;"><?php _e('إجراءات', 'control'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="control-audit-logs-body">
                            <?php
                            $action_map = array(
                                'login' => 'تسجيل دخول',
                                'failed_login' => 'فشل دخول',
                                'add_user' => 'إضافة مستخدم',
                                'edit_user' => 'تعديل مستخدم',
                                'delete_user' => 'حذف مستخدم',
                                'add_property' => 'إضافة عقار',
                                'edit_property' => 'تعديل عقار',
                                'delete_property' => 'حذف عقار',
                                'save_room' => 'إدارة وحدة',
                                'toggle_restriction' => 'تقييد حساب'
                            );
                            $audit_logs = Control_Audit::get_logs();
                            foreach($audit_logs as $log): ?>
                                <tr>
                                    <td style="padding:8px 10px;"><strong><?php echo esc_html($log->user_id); ?></strong></td>
                                    <td style="padding:8px 10px;"><span class="control-status-indicator indicator-accent"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span></td>
                                    <td style="padding:8px 10px;"><small style="color:#475569;"><?php echo esc_html($log->description); ?></small></td>
                                    <td style="padding:8px 10px; white-space:nowrap; color:#64748b;"><?php echo date('Y-m-d | H:i', strtotime($log->action_date)); ?></td>
                                    <td style="padding:8px 10px; text-align:left;">
                                        <?php if(in_array($log->action_type, array('delete_user', 'delete_property'))): ?>
                                            <button class="control-btn undo-action" title="<?php _e('استعادة المحذوف', 'control'); ?>" data-id="<?php echo $log->id; ?>" style="padding:4px 8px; font-size:0.7rem; background:#059669; border:none; border-radius:4px;"><span class="dashicons dashicons-undo"></span> <?php _e('استعادة', 'control'); ?></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
