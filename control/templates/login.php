<div class="control-auth-wrapper">
    <div class="control-auth-card">

        <div style="text-align:center; margin-bottom:40px; <?php echo ($wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_logo_visible'") === '0') ? 'display:none;' : ''; ?>">
            <?php
                global $wpdb;
                $logo_url = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'");
                $system_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'system_name'") ?: 'Control';

                if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($system_name); ?>" style="max-height: 80px; margin-bottom: 20px;">
                <?php else : ?>
                    <h2 style="margin:0 0 10px 0; font-size:2.4rem; font-weight:800; color:#D4AF37;"><?php echo esc_html($system_name); ?></h2>
                <?php endif;
            ?>
            <p style="color:#94a3b8; font-size: 1.1rem; font-weight:500;"><?php _e('نظام الإدارة المتكامل', 'control'); ?></p>
        </div>

        <?php
        $login_enabled = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_login_enabled'");
        $login_visible = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_login_form_visible'");
        $reg_enabled   = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_registration_enabled'");
        $reg_visible   = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_registration_form_visible'");
        $reg_fields    = json_decode($wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_registration_fields'"), true) ?: array();
        ?>

        <!-- Login Form -->
        <div id="control-login-container" style="<?php echo ($login_visible === '0') ? 'display:none;' : ''; ?>">
            <form id="control-login-form">
                <div class="control-form-group">
                    <label><?php _e('رقم الهاتف', 'control'); ?></label>
                    <div class="auth-phone-row" style="display:flex; direction:ltr; gap:0;">
                        <select id="login-country-code" style="width:100px; border-radius:12px 0 0 12px; border-right:none;">
                            <option value="+20" data-flag="🇪🇬">+20</option>
                            <option value="+971" data-flag="🇦🇪">+971</option>
                            <option value="+966" data-flag="🇸🇦">+966</option>
                            <option value="+965" data-flag="🇰🇼">+965</option>
                            <option value="+974" data-flag="🇶🇦">+974</option>
                            <option value="+973" data-flag="🇧🇭">+973</option>
                            <option value="+968" data-flag="🇴🇲">+968</option>
                        </select>
                        <input type="tel" name="phone_body" id="login-phone-body" placeholder="000 000 000" style="border-radius:0 12px 12px 0; flex:1;" required>
                        <input type="hidden" name="phone" id="login-phone-full">
                    </div>
                </div>

                <div class="control-form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label style="margin:0;"><?php _e('كلمة المرور', 'control'); ?></label>
                        <button type="button" id="switch-to-forgot" style="background:none; border:none; color:var(--control-accent); font-size:0.75rem; cursor:pointer; font-weight:700;"><?php _e('نسيت كلمة المرور؟', 'control'); ?></button>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" style="border-radius:12px;">
                </div>

                <div id="login-error" class="auth-feedback-box error" style="display:none;"></div>

                <button type="submit" class="control-btn control-btn-accent auth-submit-btn">
                    <?php _e('تسجيل الدخول للنظام', 'control'); ?>
                </button>

                <?php if ($reg_visible !== '0') : ?>
                    <div class="auth-footer-toggle">
                        <p><?php _e('ليس لديك حساب بعد؟', 'control'); ?></p>
                        <button type="button" id="switch-to-register" class="auth-toggle-link"><?php _e('إنشاء حساب جديد كعضو', 'control'); ?></button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Forgot Password Form -->
        <div id="control-forgot-container" style="display:none;">
            <div style="margin-bottom:25px; text-align:center;">
                <h3 style="color:#fff; margin-bottom:10px;"><?php _e('استعادة كلمة المرور', 'control'); ?></h3>
                <p style="color:#94a3b8; font-size:0.85rem;"><?php _e('أدخل رقم هاتفك المسجل وسنقوم بإرسال تعليمات الاستعادة.', 'control'); ?></p>
            </div>
            <form id="control-forgot-form">
                <div class="control-form-group">
                    <label><?php _e('رقم الهاتف', 'control'); ?></label>
                    <div class="auth-phone-row" style="display:flex; direction:ltr; gap:0;">
                        <select id="forgot-country-code" style="width:100px; border-radius:12px 0 0 12px; border-right:none;">
                            <option value="+20" data-flag="🇪🇬">+20</option>
                            <option value="+971" data-flag="🇦🇪">+971</option>
                            <option value="+966" data-flag="🇸🇦">+966</option>
                            <option value="+965" data-flag="🇰🇼">+965</option>
                            <option value="+974" data-flag="🇶🇦">+974</option>
                            <option value="+973" data-flag="🇧🇭">+973</option>
                            <option value="+968" data-flag="🇴🇲">+968</option>
                        </select>
                        <input type="tel" name="phone_body" id="forgot-phone-body" placeholder="000 000 000" style="border-radius:0 12px 12px 0; flex:1;" required>
                    </div>
                </div>
                <div id="forgot-feedback" class="auth-feedback-box" style="display:none;"></div>
                <button type="submit" class="control-btn control-btn-accent auth-submit-btn"><?php _e('إرسال طلب الاستعادة', 'control'); ?></button>
                <div class="auth-footer-toggle">
                    <button type="button" id="switch-to-login-from-forgot" class="auth-toggle-link"><?php _e('العودة لتسجيل الدخول', 'control'); ?></button>
                </div>
            </form>
        </div>

        <?php if ($login_visible === '0' && $reg_visible === '0') : ?>
            <div style="text-align:center; padding:40px 20px; background:rgba(255,255,255,0.05); border-radius:15px; border:1px dashed #333;">
                <span class="dashicons dashicons-lock" style="font-size:40px; width:40px; height:40px; color:#64748b; margin-bottom:15px;"></span>
                <p style="color:#cbd5e1; font-weight:700;"><?php _e('النظام في وضع الصيانة حالياً. الدخول والاشتراك معطلان.', 'control'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Registration Form (Dynamic Wizard) -->
        <div id="control-register-container" style="display:none;">
            <div id="reg-wizard-header" style="text-align:center; margin-bottom:25px;">
                <h3 style="color:#fff; margin-bottom:5px;"><?php _e('عضوية جديدة', 'control'); ?></h3>
                <div id="reg-step-indicator" style="display:flex; justify-content:center; gap:8px; margin-top:10px;"></div>
            </div>
            <form id="control-register-form">
                <?php
                $grouped_fields = array();
                foreach ($reg_fields as $f) {
                    if (!($f['enabled'] ?? true)) continue;
                    $s = $f['step'] ?? 1;
                    $grouped_fields[$s][] = $f;
                }
                ksort($grouped_fields);

                $step_keys = array_keys($grouped_fields);
                $step_count = 1;
                foreach ($grouped_fields as $step_num => $fields) : ?>
                    <div id="reg-step-<?php echo $step_count; ?>" class="reg-step" style="<?php echo $step_count > 1 ? 'display:none;' : ''; ?>">
                        <?php foreach($fields as $field):
                            $req = ($field['required'] ?? true) ? 'required' : '';
                            $id = $field['id'];
                            $label = $field['label'];
                        ?>
                            <div class="control-form-group">
                                <label><?php echo $label; ?> <?php echo $req ? '*' : ''; ?></label>

                                <?php if ($id === 'phone') : ?>
                                    <div class="auth-phone-row" style="display:flex; direction:ltr; gap:0;">
                                        <select id="reg-country-code" style="width:100px; border-radius:12px 0 0 12px; border-right:none;">
                                            <option value="+20" data-flag="🇪🇬">+20</option>
                                            <option value="+971" data-flag="🇦🇪">+971</option>
                                            <option value="+966" data-flag="🇸🇦">+966</option>
                                            <option value="+965" data-flag="🇰🇼">+965</option>
                                            <option value="+974" data-flag="🇶🇦">+974</option>
                                            <option value="+973" data-flag="🇧🇭">+973</option>
                                            <option value="+968" data-flag="🇴🇲">+968</option>
                                        </select>
                                        <input type="tel" name="phone_body" id="reg-phone-body" placeholder="000 000 000" style="border-radius:0 12px 12px 0; flex:1;" <?php echo $req; ?>>
                                    </div>
                                <?php elseif ($id === 'gender') : ?>
                                    <select name="gender" <?php echo $req; ?> style="border-radius:12px;">
                                        <option value="male"><?php _e('ذكر', 'control'); ?></option>
                                        <option value="female"><?php _e('أنثى', 'control'); ?></option>
                                    </select>
                                <?php elseif ($id === 'degree') : ?>
                                    <select name="degree" <?php echo $req; ?> style="border-radius:12px;">
                                        <option value="diploma"><?php _e('دبلوم', 'control'); ?></option>
                                        <option value="bachelor"><?php _e('بكالوريوس', 'control'); ?></option>
                                        <option value="master"><?php _e('ماجستير', 'control'); ?></option>
                                        <option value="phd"><?php _e('دكتوراه', 'control'); ?></option>
                                    </select>
                                <?php elseif ($id === 'password') : ?>
                                    <input type="password" name="password" id="reg-password" placeholder="••••••••" style="border-radius:12px;" required>
                                    <small style="color:#64748b; font-size:0.7rem; margin-top:8px; display:block;"><?php _e('يجب أن تحتوي على 8 أحرف على الأقل.', 'control'); ?></small>
                                <?php elseif ($id === 'address') : ?>
                                    <textarea name="address" placeholder="<?php echo $label; ?>" <?php echo $req; ?> rows="2" style="border-radius:12px;"></textarea>
                                <?php else : ?>
                                    <input type="<?php echo ($id === 'email' || $id === 'work_email' ? 'email' : 'text'); ?>" name="<?php echo $id; ?>" placeholder="<?php echo $label; ?>" <?php echo $req; ?> style="border-radius:12px;">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php $step_count++; endforeach; ?>

                <div id="reg-error" class="auth-feedback-box error" style="display:none;"></div>

                <div style="display:flex; gap:15px; margin-top:25px;">
                    <button type="button" id="reg-prev" class="control-btn auth-wizard-btn prev-btn" style="display:none;"><?php _e('السابق', 'control'); ?></button>
                    <button type="button" id="reg-next" class="control-btn control-btn-accent auth-wizard-btn next-btn"><?php _e('التالي', 'control'); ?></button>
                    <button type="submit" id="reg-submit" class="control-btn control-btn-accent auth-wizard-btn next-btn" style="display:none;"><?php _e('إكمال التسجيل', 'control'); ?></button>
                </div>

                <div class="auth-footer-toggle">
                    <button type="button" id="switch-to-login-from-reg" class="auth-toggle-link"><?php _e('العودة لتسجيل الدخول', 'control'); ?></button>
                </div>
            </form>
        </div>

    </div>
</div>
