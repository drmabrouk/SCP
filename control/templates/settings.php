<?php
global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_settings", OBJECT_K );
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:#1e293b;"><?php _e('إعدادات النظام', 'control'); ?></h2>
</div>

<div class="control-settings-wrapper">

    <div class="control-tabs" style="display:flex; gap:5px; margin-bottom:15px; background:#fff; padding:5px; border-radius:10px; border:1px solid #e2e8f0; width:fit-content;">
        <button class="control-tab-btn active" data-tab="tab-identity"><?php _e('هوية النظام', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-pwa"><?php _e('تطبيق الجوال', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-backup"><?php _e('النسخ الاحتياطي', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-audit"><?php _e('سجل النشاطات', 'control'); ?></button>
    </div>

    <div class="control-tab-content-container">
        <!-- Section 1: System Identity -->
        <div id="tab-identity" class="control-tab-content active">
            <div class="control-card" style="border-top: 4px solid #D4AF37; padding: 25px;">
                <div style="margin-bottom:20px;">
                    <h3 style="margin:0; font-size:1.1rem; color:#000;"><?php _e('هوية النظام والشركة', 'control'); ?></h3>
                    <small style="color:#64748b;"><?php _e('تخصيص معلومات النظام الأساسية والشعار لتظهر في التقارير والواجهة.', 'control'); ?></small>
                </div>

                <form id="control-identity-form" class="control-system-settings-form">
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="control-form-group">
                            <label><?php _e('اسم النظام', 'control'); ?></label>
                            <input type="text" name="system_name" value="<?php echo esc_attr($settings['system_name']->setting_value ?? 'Control'); ?>">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('اسم الشركة / المؤسسة', 'control'); ?></label>
                            <input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name']->setting_value ?? 'Control'); ?>">
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="control-form-group">
                            <label><?php _e('رقم الهاتف للتواصل', 'control'); ?></label>
                            <input type="text" name="company_phone" value="<?php echo esc_attr($settings['company_phone']->setting_value ?? ''); ?>">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('البريد الإلكتروني', 'control'); ?></label>
                            <input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email']->setting_value ?? ''); ?>">
                        </div>
                    </div>

                    <div class="control-form-group">
                        <label><?php _e('العنوان بالتفصيل (للتقارير)', 'control'); ?></label>
                        <textarea name="company_address" rows="2"><?php echo esc_textarea($settings['company_address']->setting_value ?? ''); ?></textarea>
                    </div>

                    <div class="control-form-group" style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0; margin-top:10px;">
                        <label style="margin-bottom:10px; display:block;"><?php _e('شعار الشركة (الرسمي)', 'control'); ?></label>
                        <div style="display:flex; gap:20px; align-items: center;">
                            <div id="logo-preview-container" style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px; width:120px; height:60px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if(!empty($settings['company_logo']->setting_value)): ?>
                                    <img id="logo-preview" src="<?php echo esc_url($settings['company_logo']->setting_value); ?>" style="max-height:100%; object-fit:contain;">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image" style="color:#cbd5e1; font-size:30px;"></span>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <input type="hidden" name="company_logo" id="company-logo-url" value="<?php echo esc_attr($settings['company_logo']->setting_value ?? ''); ?>">
                                <button type="button" class="control-upload-btn control-btn" style="background:#000; border:none;"><?php _e('تغيير الشعار', 'control'); ?></button>
                                <p style="margin:5px 0 0 0; font-size:0.65rem; color:#94a3b8;"><?php _e('يفضل خلفية شفافة PNG.', 'control'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:30px;">
                        <button type="submit" class="control-btn control-btn-accent" style="height:48px; border-radius:8px; font-weight:800; min-width:200px;"><?php _e('حفظ التغييرات', 'control'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 2: PWA & Mobile App Settings -->
        <div id="tab-pwa" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid #000; padding: 25px;">
                <div style="margin-bottom:20px;">
                    <h3 style="margin:0; font-size:1.1rem; color:#000;"><?php _e('إعدادات تطبيق الجوال (PWA)', 'control'); ?></h3>
                    <small style="color:#64748b;"><?php _e('تحويل النظام إلى تطبيق ويب تقدمي يمكن تثبيته على هواتف الأندرويد والآيفون.', 'control'); ?></small>
                </div>

                <form class="control-system-settings-form">
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="control-form-group">
                            <label><?php _e('اسم التطبيق الكامل', 'control'); ?></label>
                            <input type="text" name="pwa_app_name" value="<?php echo esc_attr($settings['pwa_app_name']->setting_value ?? 'Control'); ?>">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('الاسم المختصر', 'control'); ?></label>
                            <input type="text" name="pwa_short_name" value="<?php echo esc_attr($settings['pwa_short_name']->setting_value ?? 'Control'); ?>">
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="control-form-group">
                            <label><?php _e('لون السمة الرئيسي', 'control'); ?></label>
                            <input type="color" name="pwa_theme_color" value="<?php echo esc_attr($settings['pwa_theme_color']->setting_value ?? '#000000'); ?>" style="height:42px; padding:2px;">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('لون الخلفية', 'control'); ?></label>
                            <input type="color" name="pwa_bg_color" value="<?php echo esc_attr($settings['pwa_bg_color']->setting_value ?? '#ffffff'); ?>" style="height:42px; padding:2px;">
                        </div>
                    </div>

                    <div class="control-form-group" style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0; margin-top:10px;">
                        <label style="margin-bottom:10px; display:block;"><?php _e('أيقونة التطبيق (512x512)', 'control'); ?></label>
                        <div style="display:flex; gap:20px; align-items: center;">
                            <div id="pwa-icon-preview-container" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:5px; width:80px; height:80px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if(!empty($settings['pwa_icon_url']->setting_value)): ?>
                                    <img id="pwa-icon-preview" src="<?php echo esc_url($settings['pwa_icon_url']->setting_value); ?>" style="max-height:100%; border-radius:8px;">
                                <?php else: ?>
                                    <span class="dashicons dashicons-smartphone" style="color:#cbd5e1; font-size:30px;"></span>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <input type="hidden" name="pwa_icon_url" id="pwa-icon-url" value="<?php echo esc_attr($settings['pwa_icon_url']->setting_value ?? ''); ?>">
                                <button type="button" class="control-upload-btn control-btn" style="background:#000; border:none;"><?php _e('تحديث الأيقونة', 'control'); ?></button>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:30px;">
                        <button type="submit" class="control-btn control-btn-accent" style="height:45px; border-radius:8px; font-weight:700;"><?php _e('تحديث إعدادات التطبيق', 'control'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 3: Backup & Restore -->
        <div id="tab-backup" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid #10b981; padding: 25px;">
                <div style="margin-bottom:20px;">
                    <h3 style="margin:0; font-size:1.1rem; color:#000;"><?php _e('إدارة النسخ الاحتياطي', 'control'); ?></h3>
                    <small style="color:#64748b;"><?php _e('تأمين بيانات النظام عبر تصديرها واستعادتها عند الضرورة.', 'control'); ?></small>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                    <div style="background:#f0fdf4; border:1px solid #d1fae5; padding:20px; border-radius:12px;">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
                            <span class="dashicons dashicons-cloud-save" style="color:#059669; font-size:30px;"></span>
                            <h4 style="margin:0;"><?php _e('توليد نسخة احتياطية', 'control'); ?></h4>
                        </div>
                        <p style="font-size:0.8rem; color:#065f46; margin-bottom:20px;"><?php _e('سيتم تجميع كافة المستخدمين، الإعدادات، وسجلات العمليات في ملف مشفر للتحميل.', 'control'); ?></p>
                        <button id="control-generate-backup" class="control-btn" style="background:#059669; border:none; width:100%; height:45px; font-weight:700;">
                            <?php _e('بدء النسخ الاحتياطي الآن', 'control'); ?>
                        </button>
                    </div>

                    <div style="background:#fefce8; border:1px solid #fef08a; padding:20px; border-radius:12px;">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
                            <span class="dashicons dashicons-cloud-upload" style="color:#854d0e; font-size:30px;"></span>
                            <h4 style="margin:0;"><?php _e('استعادة النظام', 'control'); ?></h4>
                        </div>
                        <p style="font-size:0.8rem; color:#854d0e; margin-bottom:20px;"><?php _e('تحذير: سيتم استبدال كافة البيانات الحالية بالبيانات الموجودة في ملف النسخ الاحتياطي.', 'control'); ?></p>
                        <button id="control-restore-trigger" class="control-btn" style="background:#854d0e; border:none; width:100%; height:45px; font-weight:700;">
                            <?php _e('تحميل واستعادة ملف', 'control'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Activity Audit Log -->
        <div id="tab-audit" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid #000; padding:0; overflow:hidden;">
                <div style="display:flex; justify-content: space-between; align-items: center; padding:15px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <h3 style="margin:0; color:#000; font-size:0.95rem;"><?php _e('سجل النشاطات وعمليات النظام', 'control'); ?></h3>
                    <button id="control-export-audit-pdf" class="control-btn" style="background:#000; border:none; border-radius: 6px; padding:6px 12px; font-size:0.75rem;"><span class="dashicons dashicons-media-document" style="margin-left:5px;"></span><?php _e('تصدير التقرير PDF', 'control'); ?></button>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <table class="control-table" style="font-size:0.8rem;">
                        <thead style="background:#f8fafc; position:sticky; top:0; z-index:10; box-shadow:0 2px 4px rgba(0,0,0,0.05);">
                            <tr>
                                <th style="padding:12px 15px;"><?php _e('المستخدم المسئول', 'control'); ?></th>
                                <th style="padding:12px 15px;"><?php _e('نوع العملية', 'control'); ?></th>
                                <th style="padding:12px 15px;"><?php _e('تفاصيل الإجراء', 'control'); ?></th>
                                <th style="padding:12px 15px;"><?php _e('التاريخ والوقت', 'control'); ?></th>
                                <th style="padding:12px 15px; text-align:left;"><?php _e('إجراءات', 'control'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="control-audit-logs-body">
                            <?php
                            $action_map = array(
                                'login' => 'تسجيل دخول',
                                'failed_login' => 'فشل دخول',
                                'add_user' => 'إضافة كادر',
                                'edit_user' => 'تعديل كادر',
                                'delete_user' => 'حذف كادر',
                                'toggle_restriction' => 'تغيير حالة حساب',
                                'restore_backup' => 'استعادة النظام'
                            );
                            $audit_logs = Control_Audit::get_logs();
                            foreach($audit_logs as $log): ?>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:10px 15px;"><strong><?php echo esc_html($log->user_id); ?></strong></td>
                                    <td style="padding:10px 15px;"><span class="control-status-indicator indicator-accent" style="font-size:0.65rem;"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span></td>
                                    <td style="padding:10px 15px;"><small style="color:#475569;"><?php echo esc_html($log->description); ?></small></td>
                                    <td style="padding:10px 15px; white-space:nowrap; color:#64748b; font-size:0.7rem;"><?php echo date('Y-m-d | H:i', strtotime($log->action_date)); ?></td>
                                    <td style="padding:10px 15px; text-align:left;">
                                        <?php if($log->action_type === 'delete_user'): ?>
                                            <button class="control-btn undo-action" title="<?php _e('استعادة المحذوف', 'control'); ?>" data-id="<?php echo $log->id; ?>" style="padding:2px 8px; font-size:0.65rem; background:#ecfdf5; color:#059669 !important; border:none; border-radius:4px;"><span class="dashicons dashicons-undo" style="font-size:14px;"></span> <?php _e('استعادة', 'control'); ?></button>
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
