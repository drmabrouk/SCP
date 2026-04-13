<?php
global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_settings", OBJECT_K );
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="font-weight:800; font-size:1.3rem; margin:0; color:var(--control-text-dark);"><?php _e('إعدادات النظام', 'control'); ?></h2>
</div>

<div class="control-settings-wrapper">

    <div class="control-tabs" style="display:flex; gap:8px; margin-bottom:20px; background:#fff; padding:6px; border-radius:var(--control-radius); border:1px solid var(--control-border); width:fit-content; box-shadow:var(--control-shadow-sm);">
        <button class="control-tab-btn active" data-tab="tab-identity"><?php _e('هوية النظام', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-pwa"><?php _e('تطبيق الجوال', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-backup"><?php _e('النسخ الاحتياطي', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-audit"><?php _e('سجل النشاطات', 'control'); ?></button>
    </div>

    <div class="control-tab-content-container">
        <!-- Section 1: System Identity -->
        <div id="tab-identity" class="control-tab-content active">
            <div class="control-card" style="border-top: 4px solid var(--control-accent); padding: 25px;">
                <div style="margin-bottom:25px; border-bottom:1px solid var(--control-bg); padding-bottom:15px;">
                    <h3 style="margin:0; font-size:1.1rem; color:var(--control-text-dark);"><?php _e('هوية النظام والشركة', 'control'); ?></h3>
                    <div style="color:var(--control-muted); font-size:0.8rem; margin-top:5px;"><?php _e('تخصيص معلومات النظام الأساسية والشعار لتظهر في التقارير والواجهة.', 'control'); ?></div>
                </div>

                <form id="control-identity-form" class="control-system-settings-form">
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('اسم النظام', 'control'); ?></label>
                            <input type="text" name="system_name" value="<?php echo esc_attr($settings['system_name']->setting_value ?? 'Control'); ?>" placeholder="Control">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('اسم الشركة / المؤسسة', 'control'); ?></label>
                            <input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name']->setting_value ?? 'Control'); ?>" placeholder="Control Co.">
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('رقم الهاتف للتواصل', 'control'); ?></label>
                            <input type="text" name="company_phone" value="<?php echo esc_attr($settings['company_phone']->setting_value ?? ''); ?>" placeholder="+000 000 000">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('البريد الإلكتروني', 'control'); ?></label>
                            <input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email']->setting_value ?? ''); ?>" placeholder="info@example.com">
                        </div>
                    </div>

                    <div class="control-form-group">
                        <label><?php _e('العنوان بالتفصيل (للتقارير)', 'control'); ?></label>
                        <textarea name="company_address" rows="2" placeholder="العنوان هنا..."><?php echo esc_textarea($settings['company_address']->setting_value ?? ''); ?></textarea>
                    </div>

                    <div class="control-form-group" style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border); margin-top:15px;">
                        <label style="margin-bottom:12px; display:block; font-weight:700; color:var(--control-text-dark);"><?php _e('شعار الشركة (الرسمي)', 'control'); ?></label>
                        <div style="display:flex; gap:25px; align-items: center;">
                            <div id="logo-preview-container" style="background:#fff; border:1px solid var(--control-border); border-radius:8px; padding:10px; width:140px; height:70px; display:flex; align-items:center; justify-content:center; overflow:hidden; box-shadow:var(--control-shadow-sm);">
                                <?php if(!empty($settings['company_logo']->setting_value)): ?>
                                    <img id="logo-preview" src="<?php echo esc_url($settings['company_logo']->setting_value); ?>" style="max-height:100%; object-fit:contain;">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image" style="color:var(--control-muted); font-size:32px;"></span>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <input type="hidden" name="company_logo" id="company-logo-url" value="<?php echo esc_attr($settings['company_logo']->setting_value ?? ''); ?>">
                                <button type="button" class="control-upload-btn control-btn" style="background:var(--control-primary); border:none;"><?php _e('تغيير الشعار', 'control'); ?></button>
                                <p style="margin:8px 0 0 0; font-size:0.7rem; color:var(--control-muted);"><?php _e('يفضل خلفية شفافة PNG بجودة عالية.', 'control'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:35px; border-top:1px solid var(--control-bg); padding-top:20px;">
                        <button type="submit" class="control-btn control-btn-accent" style="height:48px; border-radius:8px; font-weight:800; min-width:220px;"><?php _e('حفظ كافة التغييرات', 'control'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 2: PWA & Mobile App Settings -->
        <div id="tab-pwa" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid var(--control-primary); padding: 25px;">
                <div style="margin-bottom:25px; border-bottom:1px solid var(--control-bg); padding-bottom:15px;">
                    <h3 style="margin:0; font-size:1.1rem; color:var(--control-text-dark);"><?php _e('إعدادات تطبيق الجوال (PWA)', 'control'); ?></h3>
                    <div style="color:var(--control-muted); font-size:0.8rem; margin-top:5px;"><?php _e('تثبيت النظام كاختصار تطبيق على الشاشة الرئيسية للهواتف الذكية.', 'control'); ?></div>
                </div>

                <form class="control-system-settings-form">
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('اسم التطبيق الكامل', 'control'); ?></label>
                            <input type="text" name="pwa_app_name" value="<?php echo esc_attr($settings['pwa_app_name']->setting_value ?? 'Control'); ?>">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('الاسم المختصر', 'control'); ?></label>
                            <input type="text" name="pwa_short_name" value="<?php echo esc_attr($settings['pwa_short_name']->setting_value ?? 'Control'); ?>">
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('لون السمة الرئيسي', 'control'); ?></label>
                            <input type="color" name="pwa_theme_color" value="<?php echo esc_attr($settings['pwa_theme_color']->setting_value ?? '#000000'); ?>" style="height:45px; padding:4px;">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('لون الخلفية', 'control'); ?></label>
                            <input type="color" name="pwa_bg_color" value="<?php echo esc_attr($settings['pwa_bg_color']->setting_value ?? '#ffffff'); ?>" style="height:45px; padding:4px;">
                        </div>
                    </div>

                    <div class="control-form-group" style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border); margin-top:15px;">
                        <label style="margin-bottom:12px; display:block; font-weight:700; color:var(--control-text-dark);"><?php _e('أيقونة التطبيق (512x512)', 'control'); ?></label>
                        <div style="display:flex; gap:25px; align-items: center;">
                            <div id="pwa-icon-preview-container" style="background:#fff; border:1px solid var(--control-border); border-radius:12px; padding:5px; width:90px; height:90px; display:flex; align-items:center; justify-content:center; overflow:hidden; box-shadow:var(--control-shadow-sm);">
                                <?php if(!empty($settings['pwa_icon_url']->setting_value)): ?>
                                    <img id="pwa-icon-preview" src="<?php echo esc_url($settings['pwa_icon_url']->setting_value); ?>" style="max-height:100%; border-radius:8px;">
                                <?php else: ?>
                                    <span class="dashicons dashicons-smartphone" style="color:var(--control-muted); font-size:36px;"></span>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <input type="hidden" name="pwa_icon_url" id="pwa-icon-url" value="<?php echo esc_attr($settings['pwa_icon_url']->setting_value ?? ''); ?>">
                                <button type="button" class="control-upload-btn control-btn" style="background:var(--control-primary); border:none;"><?php _e('تحديث الأيقونة', 'control'); ?></button>
                                <p style="margin:8px 0 0 0; font-size:0.7rem; color:var(--control-muted);"><?php _e('يجب أن تكون الأيقونة مربعة وبحجم لا يقل عن 512 بكسل.', 'control'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:35px; border-top:1px solid var(--control-bg); padding-top:20px;">
                        <button type="submit" class="control-btn control-btn-accent" style="height:45px; border-radius:8px; font-weight:700; min-width:200px;"><?php _e('تحديث إعدادات التطبيق', 'control'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 3: Backup & Restore -->
        <div id="tab-backup" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid #10b981; padding: 25px;">
                <div style="margin-bottom:25px; border-bottom:1px solid var(--control-bg); padding-bottom:15px;">
                    <h3 style="margin:0; font-size:1.1rem; color:var(--control-text-dark);"><?php _e('إدارة النسخ الاحتياطي والاستعادة', 'control'); ?></h3>
                    <div style="color:var(--control-muted); font-size:0.8rem; margin-top:5px;"><?php _e('تأمين بيانات النظام عبر تصديرها واستعادتها للحفاظ على استمرارية العمل.', 'control'); ?></div>
                </div>

                <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:25px;">
                    <div style="background:#f0fdf4; border:1px solid #d1fae5; padding:25px; border-radius:16px; display:flex; flex-direction:column; justify-content:space-between; height:100%;">
                        <div>
                            <div style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                                <div style="width:48px; height:48px; background:#fff; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow:var(--control-shadow-sm);">
                                    <span class="dashicons dashicons-cloud-save" style="color:#059669; font-size:28px; width:28px; height:28px;"></span>
                                </div>
                                <h4 style="margin:0; color:#064e3b; font-size:1rem;"><?php _e('توليد نسخة احتياطية', 'control'); ?></h4>
                            </div>
                            <p style="font-size:0.8rem; color:#065f46; line-height:1.6; margin-bottom:25px;"><?php _e('سيتم تجميع كافة بيانات الكوادر، الإعدادات، وسجلات العمليات في ملف JSON مشفر للتحميل والاحتفاظ به.', 'control'); ?></p>
                        </div>
                        <button id="control-generate-backup" class="control-btn" style="background:#059669; border:none; width:100%; height:48px; font-weight:800; font-size:0.9rem;">
                            <?php _e('بدء النسخ الاحتياطي الآن', 'control'); ?>
                        </button>
                    </div>

                    <div style="background:#fffbeb; border:1px solid #fef3c7; padding:25px; border-radius:16px; display:flex; flex-direction:column; justify-content:space-between; height:100%;">
                        <div>
                            <div style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                                <div style="width:48px; height:48px; background:#fff; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow:var(--control-shadow-sm);">
                                    <span class="dashicons dashicons-cloud-upload" style="color:#d97706; font-size:28px; width:28px; height:28px;"></span>
                                </div>
                                <h4 style="margin:0; color:#78350f; font-size:1rem;"><?php _e('استعادة بيانات النظام', 'control'); ?></h4>
                            </div>
                            <p style="font-size:0.8rem; color:#92400e; line-height:1.6; margin-bottom:25px;"><strong><?php _e('تحذير:', 'control'); ?></strong> <?php _e('سيتم استبدال كافة البيانات الحالية في قاعدة البيانات بالبيانات الموجودة في ملف النسخ المرفوع.', 'control'); ?></p>
                        </div>
                        <button id="control-restore-trigger" class="control-btn" style="background:#d97706; border:none; width:100%; height:48px; font-weight:800; font-size:0.9rem;">
                            <?php _e('رفع واستعادة ملف نسخة', 'control'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Activity Audit Log -->
        <div id="tab-audit" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid var(--control-text-dark); padding:0; overflow:hidden;">
                <div style="display:flex; justify-content: space-between; align-items: center; padding:20px 25px; background:#f8fafc; border-bottom:1px solid var(--control-border);">
                    <div>
                        <h3 style="margin:0; color:var(--control-text-dark); font-size:1.1rem;"><?php _e('سجل النشاطات وعمليات النظام', 'control'); ?></h3>
                        <div style="color:var(--control-muted); font-size:0.75rem; margin-top:3px;"><?php _e('تتبع كافة التغييرات والعمليات التي تمت بواسطة مديري النظام.', 'control'); ?></div>
                    </div>
                    <button id="control-export-audit-pdf" class="control-btn" style="background:var(--control-primary); border:none; padding:8px 15px; font-size:0.8rem; height:38px;"><span class="dashicons dashicons-media-document" style="margin-left:8px;"></span><?php _e('تصدير PDF', 'control'); ?></button>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <table class="control-table" style="font-size:0.85rem;">
                        <thead style="background:#f8fafc; position:sticky; top:0; z-index:10; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                            <tr>
                                <th style="padding:15px 25px; border-bottom:1px solid var(--control-border);"><?php _e('المسؤول', 'control'); ?></th>
                                <th style="padding:15px 25px; border-bottom:1px solid var(--control-border);"><?php _e('العملية', 'control'); ?></th>
                                <th style="padding:15px 25px; border-bottom:1px solid var(--control-border);"><?php _e('التفاصيل', 'control'); ?></th>
                                <th style="padding:15px 25px; border-bottom:1px solid var(--control-border);"><?php _e('التاريخ', 'control'); ?></th>
                                <th style="padding:15px 25px; border-bottom:1px solid var(--control-border); text-align:left;"><?php _e('إجراءات', 'control'); ?></th>
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
                                <tr style="border-bottom:1px solid var(--control-bg);">
                                    <td style="padding:12px 25px;"><strong><?php echo esc_html($log->user_id); ?></strong></td>
                                    <td style="padding:12px 25px;"><span class="control-status-indicator indicator-accent" style="font-size:0.7rem;"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span></td>
                                    <td style="padding:12px 25px;"><small style="color:var(--control-text);"><?php echo esc_html($log->description); ?></small></td>
                                    <td style="padding:12px 25px; white-space:nowrap; color:var(--control-muted); font-size:0.75rem;"><?php echo date('Y-m-d | H:i', strtotime($log->action_date)); ?></td>
                                    <td style="padding:12px 25px; text-align:left;">
                                        <?php if($log->action_type === 'delete_user'): ?>
                                            <button class="control-btn undo-action" title="<?php _e('استعادة البيانات المحذوفة', 'control'); ?>" data-id="<?php echo $log->id; ?>" style="padding:4px 10px; font-size:0.7rem; background:#ecfdf5; color:#059669 !important; border:1px solid #d1fae5; border-radius:4px;"><span class="dashicons dashicons-undo" style="font-size:14px; margin-left:4px;"></span> <?php _e('استعادة', 'control'); ?></button>
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
