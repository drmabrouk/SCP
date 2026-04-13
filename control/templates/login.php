<div class="control-auth-wrapper">
    <div class="control-auth-card">

        <div style="text-align:center; margin-bottom:35px;">
            <?php
                global $wpdb;
                $logo_url = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'");
                $system_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'system_name'") ?: 'Control';

                if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($system_name); ?>">
                <?php else : ?>
                    <h2 style="margin:0; font-size:2rem; font-weight:800; color:#D4AF37;"><?php echo esc_html($system_name); ?></h2>
                <?php endif;
            ?>
            <p style="color:#64748b; margin-top:10px; font-weight:600;"><?php _e('مرحباً بكم في كنترول', 'control'); ?></p>
        </div>

        <!-- Login Form -->
        <div id="control-login-container">
            <form id="control-login-form">
                <div class="control-form-group">
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
                        <input type="tel" name="phone_body" id="login-phone-body" placeholder="<?php _e('رقم الهاتف', 'control'); ?>" required>
                        <input type="hidden" name="phone" id="login-phone-full">
                    </div>
                </div>

                <div class="control-form-group">
                    <input type="password" name="password" required placeholder="<?php _e('كلمة المرور', 'control'); ?>">
                </div>

                <div id="login-error" style="display:none; padding:12px; background:#331111; color:#ff9999; border-radius:8px; margin-bottom:20px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <button type="submit" class="control-btn control-btn-accent" style="width:100%; height:55px; font-size:1.1rem; border-radius:10px;">
                    <?php _e('تسجيل الدخول', 'control'); ?>
                </button>

                <div style="text-align:center; margin-top:25px; padding-top:20px; border-top: 1px solid #222;">
                    <button type="button" id="switch-to-register" style="background:none; border:none; color:#D4AF37; font-weight:700; cursor:pointer; font-size:1rem;">
                        <?php _e('إنشاء حساب جديد', 'control'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Registration Form (Multi-step) -->
        <div id="control-register-container" style="display:none;">
            <form id="control-register-form">
                <div id="reg-step-1" class="reg-step">
                    <div style="display:flex; gap:10px; margin-bottom:20px;">
                        <input type="text" name="first_name" placeholder="<?php _e('الاسم الأول', 'control'); ?>" required>
                        <input type="text" name="last_name" placeholder="<?php _e('اسم العائلة', 'control'); ?>" required>
                    </div>
                </div>

                <div id="reg-step-2" class="reg-step" style="display:none;">
                    <div class="control-form-group">
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
                            <input type="tel" name="phone_body" id="reg-phone-body" placeholder="<?php _e('رقم الهاتف', 'control'); ?>" required>
                        </div>
                    </div>
                </div>

                <div id="reg-step-3" class="reg-step" style="display:none;">
                    <div class="control-form-group">
                        <input type="email" name="email" placeholder="<?php _e('البريد الإلكتروني (اختياري)', 'control'); ?>">
                    </div>
                </div>

                <div id="reg-step-4" class="reg-step" style="display:none;">
                    <div class="control-form-group">
                        <input type="password" name="password" id="reg-password" placeholder="<?php _e('كلمة المرور (8 أحرف على الأقل)', 'control'); ?>" required>
                    </div>
                </div>

                <div id="reg-error" style="display:none; padding:12px; background:#331111; color:#ff9999; border-radius:8px; margin-bottom:20px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="button" id="reg-prev" class="control-btn" style="flex:1; display:none; background:#222; border:none;"><?php _e('السابق', 'control'); ?></button>
                    <button type="button" id="reg-next" class="control-btn control-btn-accent" style="flex:2; border-radius: 8px;"><?php _e('التالي', 'control'); ?></button>
                    <button type="submit" id="reg-submit" class="control-btn control-btn-accent" style="flex:2; display:none; border-radius: 8px;"><?php _e('إتمام التسجيل', 'control'); ?></button>
                </div>

                <div style="text-align:center; margin-top:25px; padding-top:20px; border-top: 1px solid #222;">
                    <button type="button" id="switch-to-login" style="background:none; border:none; color:#64748b; font-weight:700; cursor:pointer; font-size:0.9rem;">
                        <?php _e('العودة لتسجيل الدخول', 'control'); ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
