<div class="control-auth-wrapper">
    <div class="control-auth-card">

        <div style="text-align:center; margin-bottom:40px;">
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
                    <label style="color:#cbd5e1; font-size:0.8rem; margin-bottom:8px; display:block;"><?php _e('رقم الهاتف', 'control'); ?></label>
                    <div class="phone-input-group">
                        <span id="login-flag" class="country-flag-inside">🇪🇬</span>
                        <select id="login-country-code">
                            <option value="+20" data-flag="🇪🇬">+20</option>
                            <option value="+971" data-flag="🇦🇪">+971</option>
                            <option value="+966" data-flag="🇸🇦">+966</option>
                            <option value="+965" data-flag="🇰🇼">+965</option>
                            <option value="+974" data-flag="🇶🇦">+974</option>
                            <option value="+973" data-flag="🇧🇭">+973</option>
                            <option value="+968" data-flag="🇴🇲">+968</option>
                        </select>
                        <input type="tel" name="phone_body" id="login-phone-body" placeholder="000 000 000" required>
                        <input type="hidden" name="phone" id="login-phone-full">
                    </div>
                </div>

                <div class="control-form-group">
                    <label style="color:#cbd5e1; font-size:0.8rem; margin-bottom:8px; display:block;"><?php _e('كلمة المرور', 'control'); ?></label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <div id="login-error" style="display:none; padding:15px; background:rgba(239, 68, 68, 0.1); color:#ef4444; border:1px solid rgba(239, 68, 68, 0.2); border-radius:10px; margin-bottom:25px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <button type="submit" class="control-btn control-btn-accent" style="width:100%; height:55px; font-size:1.1rem; border-radius:12px; font-weight:800; box-shadow: 0 4px 12px rgba(212,175,55,0.2);">
                    <?php _e('تسجيل الدخول', 'control'); ?>
                </button>

                <?php if ($reg_visible !== '0') : ?>
                    <div style="text-align:center; margin-top:35px; padding-top:25px; border-top: 1px solid #1a1a1a;">
                        <p style="color:#64748b; margin-bottom:15px; font-size:0.9rem;"><?php _e('ليس لديك حساب بعد؟', 'control'); ?></p>
                        <button type="button" id="switch-to-register" style="background:none; border:none; color:#D4AF37; font-weight:700; cursor:pointer; font-size:1rem; transition: 0.2s;">
                            <?php _e('إنشاء حساب جديد كعضو', 'control'); ?>
                        </button>
                    </div>
                <?php endif; ?>
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
                                <label style="color:#cbd5e1; font-size:0.8rem; margin-bottom:8px; display:block;"><?php echo $label; ?> <?php echo $req ? '*' : ''; ?></label>

                                <?php if ($id === 'phone') : ?>
                                    <div class="phone-input-group">
                                        <span id="reg-flag" class="country-flag-inside">🇪🇬</span>
                                        <select id="reg-country-code">
                                            <option value="+20" data-flag="🇪🇬">+20</option>
                                            <option value="+971" data-flag="🇦🇪">+971</option>
                                            <option value="+966" data-flag="🇸🇦">+966</option>
                                            <option value="+965" data-flag="🇰🇼">+965</option>
                                            <option value="+974" data-flag="🇶🇦">+974</option>
                                            <option value="+973" data-flag="🇧🇭">+973</option>
                                            <option value="+968" data-flag="🇴🇲">+968</option>
                                        </select>
                                        <input type="tel" name="phone_body" id="reg-phone-body" placeholder="000 000 000" <?php echo $req; ?>>
                                    </div>
                                <?php elseif ($id === 'gender') : ?>
                                    <select name="gender" <?php echo $req; ?>>
                                        <option value="male"><?php _e('ذكر', 'control'); ?></option>
                                        <option value="female"><?php _e('أنثى', 'control'); ?></option>
                                    </select>
                                <?php elseif ($id === 'degree') : ?>
                                    <select name="degree" <?php echo $req; ?>>
                                        <option value="diploma"><?php _e('دبلوم', 'control'); ?></option>
                                        <option value="bachelor"><?php _e('بكالوريوس', 'control'); ?></option>
                                        <option value="master"><?php _e('ماجستير', 'control'); ?></option>
                                        <option value="phd"><?php _e('دكتوراه', 'control'); ?></option>
                                    </select>
                                <?php elseif ($id === 'password') : ?>
                                    <input type="password" name="password" id="reg-password" placeholder="••••••••" required>
                                    <small style="color:#64748b; font-size:0.7rem; margin-top:8px; display:block;"><?php _e('يجب أن تحتوي على 8 أحرف على الأقل.', 'control'); ?></small>
                                <?php elseif ($id === 'address') : ?>
                                    <textarea name="address" placeholder="<?php echo $label; ?>" <?php echo $req; ?> rows="2"></textarea>
                                <?php else : ?>
                                    <input type="<?php echo ($id === 'email' || $id === 'work_email' ? 'email' : 'text'); ?>" name="<?php echo $id; ?>" placeholder="<?php echo $label; ?>" <?php echo $req; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php $step_count++; endforeach; ?>

                <div id="reg-error" style="display:none; padding:15px; background:rgba(239, 68, 68, 0.1); color:#ef4444; border:1px solid rgba(239, 68, 68, 0.2); border-radius:10px; margin-bottom:25px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <div style="display:flex; gap:15px; margin-top:25px;">
                    <button type="button" id="reg-prev" class="control-btn" style="flex:1; display:none; background:#1a1a1a; border:1px solid #333; height:50px; font-weight:700;"><?php _e('السابق', 'control'); ?></button>
                    <button type="button" id="reg-next" class="control-btn control-btn-accent" style="flex:2; border-radius: 12px; height:50px; font-weight:800;"><?php _e('التالي', 'control'); ?></button>
                    <button type="submit" id="reg-submit" class="control-btn control-btn-accent" style="flex:2; display:none; border-radius: 12px; height:50px; font-weight:800;"><?php _e('إكمال التسجيل', 'control'); ?></button>
                </div>

                <div style="text-align:center; margin-top:35px; padding-top:25px; border-top: 1px solid #1a1a1a;">
                    <button type="button" id="switch-to-login" style="background:none; border:none; color:#64748b; font-weight:700; cursor:pointer; font-size:1rem; transition: 0.2s;">
                        <?php _e('العودة لتسجيل الدخول', 'control'); ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
