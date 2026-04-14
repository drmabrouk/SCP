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
        <button class="control-tab-btn" data-tab="tab-design"><?php _e('تخصيص التصميم', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-pwa"><?php _e('تطبيق الجوال', 'control'); ?></button>
        <button class="control-tab-btn" data-tab="tab-notifications"><?php _e('التنبيهات والبريد', 'control'); ?></button>
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

        <!-- Section 2: Design Customization -->
        <div id="tab-design" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid #8b5cf6; padding: 25px;">
                <div style="margin-bottom:25px; border-bottom:1px solid var(--control-bg); padding-bottom:15px;">
                    <h3 style="margin:0; font-size:1.1rem; color:var(--control-text-dark);"><?php _e('تخصيص تصميم النظام', 'control'); ?></h3>
                    <div style="color:var(--control-muted); font-size:0.8rem; margin-top:5px;"><?php _e('تحكم في الألوان، الخطوط، والمظهر العام للنظام.', 'control'); ?></div>
                </div>

                <form id="control-design-form" class="control-system-settings-form">
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:25px;">
                        <!-- Sidebar & Navigation -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem;"><?php _e('شريط التنقل الجانبي', 'control'); ?></h4>
                            <div class="control-form-group">
                                <label><?php _e('لون خلفية الشريط الجانبي', 'control'); ?></label>
                                <input type="color" name="design_sidebar_bg" value="<?php echo esc_attr($settings['design_sidebar_bg']->setting_value ?? '#0f172a'); ?>" style="height:40px;">
                            </div>
                        </div>

                        <!-- Buttons & Actions -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem;"><?php _e('الأزرار والإجراءات', 'control'); ?></h4>
                            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                                <div class="control-form-group">
                                    <label><?php _e('اللون الرئيسي', 'control'); ?></label>
                                    <input type="color" name="design_btn_primary" value="<?php echo esc_attr($settings['design_btn_primary']->setting_value ?? '#0f172a'); ?>">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('اللون الثانوي', 'control'); ?></label>
                                    <input type="color" name="design_btn_secondary" value="<?php echo esc_attr($settings['design_btn_secondary']->setting_value ?? '#1e293b'); ?>">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('لون التمييز', 'control'); ?></label>
                                    <input type="color" name="design_accent" value="<?php echo esc_attr($settings['design_accent']->setting_value ?? '#D4AF37'); ?>">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('لون التحويم (Hover)', 'control'); ?></label>
                                    <input type="color" name="design_btn_hover" value="<?php echo esc_attr($settings['design_btn_hover']->setting_value ?? '#1e293b'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:25px; margin-top:20px;">
                        <!-- Color Schemes -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem;"><?php _e('مخطط ألوان الواجهة', 'control'); ?></h4>
                            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                                <div class="control-form-group">
                                    <label><?php _e('لون النص الأساسي', 'control'); ?></label>
                                    <input type="color" name="design_text_main" value="<?php echo esc_attr($settings['design_text_main']->setting_value ?? '#334155'); ?>">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('لون الخلفية العامة', 'control'); ?></label>
                                    <input type="color" name="design_bg_main" value="<?php echo esc_attr($settings['design_bg_main']->setting_value ?? '#f8fafc'); ?>">
                                </div>
                            </div>
                        </div>
                        <!-- Typography -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem;"><?php _e('تنسيق الخطوط (Typography)', 'control'); ?></h4>
                            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                                <div class="control-form-group">
                                    <label><?php _e('حجم الخط الأساسي (px)', 'control'); ?></label>
                                    <input type="number" name="design_font_size" value="<?php echo esc_attr($settings['design_font_size']->setting_value ?? '14'); ?>" min="12" max="18">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('وزن الخط للعناوين', 'control'); ?></label>
                                    <select name="design_font_weight_bold">
                                        <option value="600" <?php selected($settings['design_font_weight_bold']->setting_value ?? '700', '600'); ?>>Medium (600)</option>
                                        <option value="700" <?php selected($settings['design_font_weight_bold']->setting_value ?? '700', '700'); ?>>Bold (700)</option>
                                        <option value="800" <?php selected($settings['design_font_weight_bold']->setting_value ?? '700', '800'); ?>>Extra Bold (800)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Accessibility -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem;"><?php _e('إمكانية الوصول والتباين', 'control'); ?></h4>
                            <div class="control-form-group">
                                <label><?php _e('تباين النص المرتفع', 'control'); ?></label>
                                <select name="design_high_contrast">
                                    <option value="no" <?php selected($settings['design_high_contrast']->setting_value ?? 'no', 'no'); ?>><?php _e('افتراضي', 'control'); ?></option>
                                    <option value="yes" <?php selected($settings['design_high_contrast']->setting_value ?? 'no', 'yes'); ?>><?php _e('تباين عالي', 'control'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:35px; border-top:1px solid var(--control-bg); padding-top:20px;">
                        <button type="submit" class="control-btn control-btn-accent" style="height:48px; border-radius:8px; font-weight:800; min-width:220px;"><?php _e('حفظ إعدادات التصميم', 'control'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 3: PWA & Mobile App Settings -->
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

        <!-- Section 4: Notifications & Email -->
        <div id="tab-notifications" class="control-tab-content" style="display:none;">
            <div class="control-card" style="border-top: 4px solid #f59e0b; padding: 25px;">
                <div style="margin-bottom:25px; border-bottom:1px solid var(--control-bg); padding-bottom:15px;">
                    <h3 style="margin:0; font-size:1.1rem; color:var(--control-text-dark);"><?php _e('إدارة التنبيهات والبريد الإلكتروني', 'control'); ?></h3>
                    <div style="color:var(--control-muted); font-size:0.8rem; margin-top:5px;"><?php _e('تخصيص نظام الإشعارات التلقائية وإعدادات خادم الإرسال SMTP.', 'control'); ?></div>
                </div>

                <form id="control-notifications-form" class="control-system-settings-form">
                    <div class="control-grid" style="grid-template-columns: 240px 1fr; gap:25px;">

                        <!-- Notifications Sidebar -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border); height: fit-content;">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem; color:var(--control-primary);"><?php _e('قوالب البريد', 'control'); ?></h4>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <button type="button" class="tpl-nav-btn active" data-tpl="welcome_email"><?php _e('رسالة الترحيب', 'control'); ?></button>
                                <button type="button" class="tpl-nav-btn" data-tpl="engagement_reminder"><?php _e('تذكير التفاعل', 'control'); ?></button>
                            </div>

                            <h4 style="margin:25px 0 15px 0; font-size:0.9rem; color:var(--control-primary);"><?php _e('سمة البريد (Theme)', 'control'); ?></h4>
                            <select name="email_theme" style="margin-bottom:10px;">
                                <option value="modern" <?php selected($settings['email_theme']->setting_value ?? 'modern', 'modern'); ?>>Modern (Gradient)</option>
                                <option value="classic" <?php selected($settings['email_theme']->setting_value ?? 'modern', 'classic'); ?>>Classic (Clean)</option>
                            </select>

                            <div style="margin-top:30px; padding:15px; background:#fff; border-radius:8px; border:1px solid var(--control-border);">
                                <small style="display:block; font-weight:700; color:var(--control-muted); margin-bottom:5px;"><?php _e('نصيحة:', 'control'); ?></small>
                                <p style="font-size:0.7rem; color:var(--control-muted); margin:0; line-height:1.4;">
                                    <?php _e('استخدم محرر HTML لتنسيق الرسائل بشكل احترافي. يمكنك إضافة صور، روابط، وجداول.', 'control'); ?>
                                </p>
                            </div>
                        </div>

                        <div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:25px; margin-bottom:25px;">
                        <!-- SMTP Settings -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem; color:var(--control-primary);"><?php _e('إعدادات خادم الإرسال (SMTP)', 'control'); ?></h4>
                            <div class="control-form-group">
                                <label><?php _e('خادم SMTP', 'control'); ?></label>
                                <input type="text" name="smtp_host" value="<?php echo esc_attr($settings['smtp_host']->setting_value ?? ''); ?>" placeholder="smtp.example.com">
                            </div>
                            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                                <div class="control-form-group">
                                    <label><?php _e('المنفذ (Port)', 'control'); ?></label>
                                    <input type="text" name="smtp_port" value="<?php echo esc_attr($settings['smtp_port']->setting_value ?? '587'); ?>">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('التشفير', 'control'); ?></label>
                                    <select name="smtp_encryption">
                                        <option value="tls" <?php selected($settings['smtp_encryption']->setting_value ?? 'tls', 'tls'); ?>>TLS</option>
                                        <option value="ssl" <?php selected($settings['smtp_encryption']->setting_value ?? 'tls', 'ssl'); ?>>SSL</option>
                                        <option value="none" <?php selected($settings['smtp_encryption']->setting_value ?? 'tls', 'none'); ?>><?php _e('بدون', 'control'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="control-form-group">
                                <label><?php _e('اسم المستخدم', 'control'); ?></label>
                                <input type="text" name="smtp_user" value="<?php echo esc_attr($settings['smtp_user']->setting_value ?? ''); ?>">
                            </div>
                            <div class="control-form-group">
                                <label><?php _e('كلمة المرور', 'control'); ?></label>
                                <input type="password" name="smtp_pass" value="<?php echo esc_attr($settings['smtp_pass']->setting_value ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Sender Identity -->
                        <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                            <h4 style="margin:0 0 15px 0; font-size:0.9rem; color:var(--control-primary);"><?php _e('هوية المرسل', 'control'); ?></h4>
                            <div class="control-form-group">
                                <label><?php _e('اسم المرسل الظاهر', 'control'); ?></label>
                                <input type="text" name="sender_name" value="<?php echo esc_attr($settings['sender_name']->setting_value ?? 'Control System'); ?>">
                            </div>
                            <div class="control-form-group">
                                <label><?php _e('بريد الإرسال الرسمي', 'control'); ?></label>
                                <input type="email" name="sender_email" value="<?php echo esc_attr($settings['sender_email']->setting_value ?? get_option('admin_email')); ?>">
                            </div>
                            <div style="margin-top:20px; padding:15px; background:#fff; border:1px dashed #fbbf24; border-radius:8px;">
                                <p style="font-size:0.75rem; color:#92400e; margin:0;">
                                    <?php _e('سيتم استخدام هذه البيانات كمرسل رسمي لكافة المراسلات الصادرة من النظام لضمان الاحترافية.', 'control'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div style="background:var(--control-bg); padding:20px; border-radius:12px; border:1px solid var(--control-border);">
                        <h4 style="margin:0 0 20px 0; font-size:0.9rem; color:var(--control-primary); border-bottom:1px solid var(--control-border); padding-bottom:10px;"><?php _e('محرر محتوى القالب', 'control'); ?></h4>

                        <?php
                        $templates_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}control_email_templates", OBJECT_K);
                        $tpl_labels = array(
                            'welcome_email' => array('label' => __('رسالة الترحيب', 'control')),
                            'engagement_reminder' => array('label' => __('تذكير التفاعل', 'control'))
                        );
                        foreach($tpl_labels as $key => $info):
                            $tpl = $templates_data[$key] ?? (object) array('subject' => '', 'content' => '');
                        ?>
                            <div id="tpl-section-<?php echo $key; ?>" class="tpl-content-section" style="<?php echo $key === 'welcome_email' ? '' : 'display:none;'; ?>">
                                <div class="control-form-group">
                                    <label><?php _e('عنوان البريد (Subject)', 'control'); ?></label>
                                    <input type="text" name="tpl_subject_<?php echo $key; ?>" value="<?php echo esc_attr($tpl->subject); ?>" style="font-weight:700;">
                                </div>
                                <div class="control-form-group">
                                    <label><?php _e('هيكل ومحتوى الرسالة (HTML/CSS Editor)', 'control'); ?></label>
                                    <textarea name="tpl_content_<?php echo $key; ?>" rows="12" style="font-family:monospace; font-size:0.85rem; line-height:1.5; background:#1e293b; color:#cbd5e1; border:none; padding:15px;"><?php echo esc_textarea($tpl->content); ?></textarea>
                                    <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                                        <small style="color:var(--control-muted);"><?php _e('المتغيرات:', 'control'); ?></small>
                                        <code class="tpl-tag">{user_name}</code>
                                        <code class="tpl-tag">{system_name}</code>
                                        <code class="tpl-tag">{site_url}</code>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                    </div>

                    <div style="margin-top:30px; border-top:1px solid var(--control-bg); padding-top:20px;">
                        <button type="submit" class="control-btn control-btn-accent" style="height:48px; border-radius:8px; font-weight:800; min-width:220px;"><?php _e('حفظ إعدادات التنبيهات', 'control'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 5: Backup & Restore -->
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
                    <table class="control-table" style="font-size:0.8rem; border-collapse: separate; border-spacing: 0;">
                        <thead style="background:#f8fafc; position:sticky; top:0; z-index:10; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                            <tr>
                                <th style="padding:10px 15px; border-bottom:1px solid var(--control-border); width: 120px;"><?php _e('المسؤول', 'control'); ?></th>
                                <th style="padding:10px 15px; border-bottom:1px solid var(--control-border); width: 100px;"><?php _e('العملية', 'control'); ?></th>
                                <th style="padding:10px 15px; border-bottom:1px solid var(--control-border);"><?php _e('التفاصيل والسياق', 'control'); ?></th>
                                <th style="padding:10px 15px; border-bottom:1px solid var(--control-border); width: 130px;"><?php _e('التاريخ والوقت', 'control'); ?></th>
                                <th style="padding:10px 15px; border-bottom:1px solid var(--control-border); text-align:left; width: 150px;"><?php _e('الإجراءات', 'control'); ?></th>
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
                            foreach($audit_logs as $log):
                                $meta = json_decode($log->meta_data, true);
                            ?>
                                <tr style="border-bottom:1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:8px 15px;">
                                        <div style="font-weight:700; color:var(--control-text-dark);"><?php echo esc_html($log->user_id); ?></div>
                                        <div style="font-size:0.65rem; color:var(--control-muted);"><?php echo esc_html($log->ip_address); ?></div>
                                    </td>
                                    <td style="padding:8px 15px;">
                                        <span class="control-status-indicator indicator-accent" style="font-size:0.65rem; padding:2px 6px;"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span>
                                    </td>
                                    <td style="padding:8px 15px;">
                                        <div style="font-weight:600; color:var(--control-text-dark); margin-bottom:2px;"><?php echo esc_html($log->description); ?></div>
                                        <?php if($meta): ?>
                                            <div style="font-size:0.65rem; color:var(--control-muted); font-style: italic;">
                                                <?php echo esc_html(substr(json_encode($meta, JSON_UNESCAPED_UNICODE), 0, 100)) . (strlen(json_encode($meta)) > 100 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:8px 15px; white-space:nowrap; color:var(--control-muted); font-size:0.7rem;">
                                        <div style="font-weight:600;"><?php echo date('Y/m/d', strtotime($log->action_date)); ?></div>
                                        <div><?php echo date('H:i:s', strtotime($log->action_date)); ?></div>
                                    </td>
                                    <td style="padding:8px 15px; text-align:left;">
                                        <div style="display:flex; gap:5px; justify-content: flex-end;">
                                            <button class="audit-action-btn view-log-info" title="<?php _e('تفاصيل كاملة', 'control'); ?>" data-log='<?php echo json_encode($log, JSON_UNESCAPED_UNICODE); ?>'><span class="dashicons dashicons-info"></span></button>

                                            <?php if($log->action_type === 'delete_user'): ?>
                                                <button class="audit-action-btn undo-action" title="<?php _e('استعادة (تراجع)', 'control'); ?>" data-id="<?php echo $log->id; ?>" style="color:#059669;"><span class="dashicons dashicons-undo"></span></button>
                                            <?php endif; ?>

                                            <button class="audit-action-btn delete-log-entry" title="<?php _e('حذف السجل', 'control'); ?>" data-id="<?php echo $log->id; ?>" style="color:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                                        </div>
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
