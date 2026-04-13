<?php
    global $wpdb;
    $logo_url = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'");
    $system_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'system_name'") ?: 'Control';
?>
<div class="control-dashboard" id="control-system-root">
    <div class="control-mobile-header" style="display:none; background:#000; padding:10px 15px; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:10005; border-bottom:1px solid #1a1a1a; direction: rtl;">
        <div class="mobile-header-logo" style="flex: 1; text-align: right;">
            <?php if ( $logo_url ) : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Control" style="max-height:30px; width:auto; object-fit:contain; display:block;">
            <?php else : ?>
                <h2 style="color:#D4AF37; margin:0; font-size:1rem; letter-spacing:1px; font-weight: 800;">CONTROL</h2>
            <?php endif; ?>
        </div>
        <div style="display:flex; align-items:center; gap: 10px;">
            <button id="control-header-logout" class="control-pill-logout" style="background:#ef4444; color:#fff; border:none; border-radius:30px; padding:8px 16px; font-size:0.75rem; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:6px;">
                <span class="dashicons dashicons-no-alt" style="font-size:16px; width:16px; height:16px;"></span>
                <span>Logout</span>
            </button>
        </div>
    </div>

    <aside class="control-sidebar" id="control-sidebar-main">
        <div class="control-sidebar-logo">
            <?php if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($system_name); ?>" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                <?php else : ?>
                    <h2><?php echo esc_html($system_name); ?></h2>
                <?php endif;
            ?>
        </div>
        <nav class="control-sidebar-nav">
            <a href="<?php echo add_query_arg('control_view', 'dashboard'); ?>" class="<?php echo (!isset($_GET['control_view']) || $_GET['control_view'] == 'dashboard') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span> <?php _e('لوحة المعلومات', 'control'); ?>
            </a>


            <?php if ( Control_Auth::is_admin() ) : ?>
                <a href="<?php echo add_query_arg('control_view', 'users'); ?>" class="<?php echo (isset($_GET['control_view']) && $_GET['control_view'] == 'users') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> <?php _e('إدارة المستخدمين', 'control'); ?>
                </a>
                <a href="<?php echo add_query_arg('control_view', 'settings'); ?>" class="<?php echo (isset($_GET['control_view']) && $_GET['control_view'] == 'settings') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> <?php _e('الإعدادات', 'control'); ?>
                </a>
            <?php endif; ?>
        </nav>

        <div class="control-sidebar-footer" style="padding: 10px; border-top: 1px solid #334155; margin-top: auto;">
            <div class="control-sidebar-controls" style="display: flex; justify-content: space-around; align-items: center; gap: 2px;">
                <button id="control-refresh-btn" class="sidebar-ctrl-icon" style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:5px; flex:1; display:flex; flex-direction:column; align-items:center;">
                    <span class="dashicons dashicons-update" style="font-size:18px; width:18px; height:18px;"></span>
                    <small style="font-size:0.6rem; margin-top:2px;"><?php _e('تحديث', 'control'); ?></small>
                </button>
                <button id="control-logout-btn" class="sidebar-ctrl-icon logout" title="<?php _e('خروج', 'control'); ?>" style="background:none; border:none; color:#ef4444 !important; cursor:pointer; padding:5px; flex:1; display:flex; flex-direction:column; align-items:center;">
                    <span class="dashicons dashicons-no-alt" style="font-size:18px; width:18px; height:18px;"></span>
                    <small style="font-size:0.6rem; margin-top:2px;"><?php _e('خروج', 'control'); ?></small>
                </button>
            </div>
        </div>
    </aside>

    <!-- Mobile Bottom Bar (Fixed) -->
    <div class="control-mobile-bottom-bar" style="display:none;">
        <a href="<?php echo add_query_arg('control_view', 'dashboard'); ?>" class="mobile-nav-item <?php echo (!isset($_GET['control_view']) || $_GET['control_view'] == 'dashboard') ? 'active' : ''; ?>">
            <span class="dashicons dashicons-performance"></span>
            <small><?php _e('الرئيسية', 'control'); ?></small>
        </a>
        <div class="mobile-divider"></div>
        <?php if ( Control_Auth::is_admin() ) : ?>
            <a href="<?php echo add_query_arg('control_view', 'users'); ?>" class="mobile-nav-item <?php echo (isset($_GET['control_view']) && $_GET['control_view'] == 'users') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-users"></span>
                <small><?php _e('المستخدمين', 'control'); ?></small>
            </a>
            <div class="mobile-divider"></div>
            <a href="<?php echo add_query_arg('control_view', 'settings'); ?>" class="mobile-nav-item <?php echo (isset($_GET['control_view']) && $_GET['control_view'] == 'settings') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-generic"></span>
                <small><?php _e('الإعدادات', 'control'); ?></small>
            </a>
            <div class="mobile-divider"></div>
        <?php endif; ?>
        <button id="control-mobile-refresh-btn" class="mobile-nav-item" style="background:none; border:none; cursor:pointer;">
            <span class="dashicons dashicons-update"></span>
            <small><?php _e('تحديث', 'control'); ?></small>
        </button>
    </div>

    <div id="control-sync-loader" style="display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); background:#000000; color:#fff; padding:10px 20px; border-radius:30px; z-index:10000; box-shadow:0 4px 12px rgba(0,0,0,0.2); font-weight:600;">
        <span class="dashicons dashicons-update spin" style="margin-left:8px; vertical-align:middle;"></span>
        <span class="loader-text"><?php _e('جارٍ تحميل البيانات...', 'control'); ?></span>
    </div>

    <main class="control-main-content">
        <div id="control-install-banner" style="display:none; background:#D4AF37; color:#000; padding:15px 25px; border-radius:12px; margin-bottom:25px; align-items:center; justify-content:space-between; font-weight:700; box-shadow:0 4px 12px rgba(212,175,55,0.3);">
            <div style="display:flex; align-items:center; gap:15px;">
                <span class="dashicons dashicons-smartphone" style="font-size:24px; width:24px; height:24px;"></span>
                <span><?php _e('تثبيت تطبيق كنترول على هاتفك للوصول السريع', 'control'); ?></span>
            </div>
            <button onclick="window.controlInstallPrompt.prompt()" class="control-btn" style="background:#000; border:none; padding:8px 20px; font-size:0.85rem;"><?php _e('تثبيت الآن', 'control'); ?></button>
        </div>
        <div class="control-content-inner">
