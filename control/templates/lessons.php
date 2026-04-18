<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = Control_Auth::current_user();
$is_admin = Control_Auth::is_admin();
$can_view_all = Control_Auth::has_permission('lessons_view_all');

// Fetch full profile for the current user to ensure PDF generation has all details
global $wpdb;
$user_id = $current_user->id;
if ( strpos($user_id, 'wp_') === 0 ) {
    $wp_u = get_userdata(str_replace('wp_', '', $user_id));
    $user = (object) array(
        'id' => $user_id,
        'name' => $current_user->name,
        'first_name' => $wp_u->first_name ?: $wp_u->display_name,
        'last_name' => $wp_u->last_name,
        'job_title' => 'Administrator',
        'home_country' => '',
        'employer_name' => '',
        'org_logo' => ''
    );
} else {
    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}control_staff WHERE id = %d", $user_id));
    if ($user) {
        $user->name = $user->first_name . ' ' . $user->last_name;
    } else {
        $user = $current_user; // Fallback
    }
}

$lessons = Control_Lessons::get_all_lessons( $can_view_all );
// Enhance lessons with avatar if possible
foreach($lessons as &$lesson) {
    if (isset($lesson->creator_id)) {
        if (strpos($lesson->creator_id, 'wp_') === 0) {
            $wp_uid = str_replace('wp_', '', $lesson->creator_id);
            $lesson->avatar = get_avatar_url($wp_uid, ['size' => 40]);
        } else {
            $staff_data = $wpdb->get_row($wpdb->prepare("SELECT profile_image, gender FROM {$wpdb->prefix}control_staff WHERE id = %d", $lesson->creator_id));
            if ($staff_data && !empty($staff_data->profile_image)) {
                $lesson->avatar = $staff_data->profile_image;
            } else {
                $lesson->avatar = null; // Will use gender-based default in UI
                $lesson->gender = $staff_data->gender ?? 'male';
            }
        }
    }
}
$suggestions = Control_Lessons::get_suggestions();

// Organization Data
global $wpdb;
$org_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_name'") ?: 'Control System';
$org_logo = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'");

// Icons for the library
$sports_icons = array(
    '⚽' => 'كرة القدم',
    '🏀' => 'كرة السلة',
    '🏐' => 'كرة الطائرة',
    '🎾' => 'تنس',
    '🏓' => 'تنس الطاولة',
    '🏸' => 'ريشة طائرة',
    '🏊' => 'سباحة',
    '🏃' => 'جري',
    '🤸' => 'جمباز',
    '🏋️' => 'رفع أثقال',
    '🚴' => 'ركوب دراجات',
    '🥋' => 'كاراتيه/جودو',
    '🏹' => 'رماية بالسهام',
    '🥊' => 'ملاكمة',
    '🧗' => 'تسلق',
    '🧘' => 'يوغا',
    '🏆' => 'كأس',
    '🏅' => 'ميدالية',
    '⏱️' => 'ساعة توقيت',
    '📢' => 'صافرة/مكبر صوت',
    '🪜' => 'سلم تمارين',
    '🧱' => 'حواجز',
    '🟠' => 'أقماع'
);
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h2 style="font-weight:800; font-size:1.4rem; margin:0; color:var(--control-text-dark);"><?php _e('تحضير الدروس والخطط التدريبية', 'control'); ?></h2>
        <p style="color:var(--control-muted); font-size:0.85rem; margin-top:5px;"><?php _e('أدوات احترافية لتخطيط الحصص الرياضية والأنشطة البدنية', 'control'); ?></p>
    </div>
    <div style="display:flex; gap:10px;">
        <?php if($is_admin): ?>
            <button id="manage-suggestions-btn" class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border);">
                <span class="dashicons dashicons-lightbulb" style="margin-left:8px;"></span><?php _e('إدارة المقترحات', 'control'); ?>
            </button>
        <?php endif; ?>
        <button id="create-lesson-btn" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; font-weight:800;">
            <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('بدء تحضير درس جديد', 'control'); ?>
        </button>
    </div>
</div>

<!-- Advanced Search and Filters -->
<div class="control-card" style="padding:15px; margin-bottom:20px; border:none; background:rgba(0,0,0,0.02);">
    <div style="display:flex; gap:12px; align-items: center; flex-wrap: wrap;">
        <div style="flex:1; position:relative; min-width: 250px;">
            <span class="dashicons dashicons-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--control-muted);"></span>
            <input type="text" id="lesson-search-input" placeholder="<?php _e('ابحث باسم الدرس...', 'control'); ?>" style="padding:10px 40px 10px 12px;">
        </div>

        <select id="lesson-grade-filter" style="width:150px; padding:10px;">
            <option value=""><?php _e('جميع الصفوف', 'control'); ?></option>
            <?php for($i=1; $i<=12; $i++): ?>
                <option value="<?php echo "الصف " . $i; ?>"><?php echo "الصف " . $i; ?></option>
            <?php endfor; ?>
            <option value="KINDERGARTEN"><?php _e('رياض الأطفال', 'control'); ?></option>
        </select>

        <select id="lesson-lang-filter" style="width:130px; padding:10px;">
            <option value=""><?php _e('جميع اللغات', 'control'); ?></option>
            <option value="ar"><?php _e('العربية', 'control'); ?></option>
            <option value="en"><?php _e('English', 'control'); ?></option>
        </select>

        <select id="lesson-date-filter" style="width:150px; padding:10px;">
            <option value="newest"><?php _e('الأحدث أولاً', 'control'); ?></option>
            <option value="oldest"><?php _e('الأقدم أولاً', 'control'); ?></option>
        </select>
    </div>
</div>

<!-- Suggestions for users -->
<?php if(!empty($suggestions)): ?>
<div class="control-card dashboard-suggestions-box" style="background:var(--control-accent-soft); border:1px dashed var(--control-accent); padding:15px; margin-bottom:25px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-info" style="color:var(--control-accent);"></span>
            <strong style="font-size:0.85rem; color:var(--control-primary);"><?php _e('عناوين مقترحة من الإدارة:', 'control'); ?></strong>
        </div>
        <div class="suggestion-lang-toggle" style="display:flex; gap:5px; background:#fff; padding:3px; border-radius:20px; border:1px solid var(--control-border);">
            <button type="button" class="sug-lang-btn active" data-lang="ar" style="border:none; background:none; cursor:pointer; font-size:0.8rem; padding:2px 8px; border-radius:15px;">🇪🇬</button>
            <button type="button" class="sug-lang-btn" data-lang="en" style="border:none; background:none; cursor:pointer; font-size:0.8rem; padding:2px 8px; border-radius:15px;">🇺🇸</button>
        </div>
    </div>
    <div class="suggestion-chips-container" style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php foreach($suggestions as $s): ?>
            <span class="suggestion-chip"
                  style="background:#fff; padding:5px 12px; border-radius:30px; font-size:0.75rem; border:1px solid var(--control-border); cursor:pointer; display: <?php echo ($s->lang ?? 'ar') === 'ar' ? 'inline-block' : 'none'; ?>;"
                  data-topic="<?php echo esc_attr($s->topic); ?>"
                  data-lang="<?php echo esc_attr($s->lang ?? 'ar'); ?>">
                <?php echo esc_html($s->topic); ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div id="lesson-library-view">
    <div class="control-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
        <?php if(empty($lessons)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:60px 20px; background:#fff; border-radius:20px; border:1px dashed var(--control-border);">
                <span class="dashicons dashicons-welcome-learn-more" style="font-size:50px; width:50px; height:50px; color:var(--control-muted); margin-bottom:20px;"></span>
                <h3 style="color:var(--control-text-dark);"><?php _e('لا توجد دروس محضرة حالياً', 'control'); ?></h3>
                <p style="color:var(--control-muted);"><?php _e('ابدأ بإنشاء أول درس لك باستخدام المعالج الذكي.', 'control'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach($lessons as $l):
                $data = json_decode($l->lesson_data, true);
                $is_rtl = ($l->lang ?? 'ar') === 'ar';
            ?>
                <div class="control-card lesson-card" data-creator="<?php echo esc_attr($l->first_name . ' ' . $l->last_name); ?>" data-lang="<?php echo esc_attr($l->lang ?? 'ar'); ?>" data-grade="<?php echo esc_attr($l->target_group); ?>" data-date="<?php echo strtotime($l->created_at); ?>" style="padding:0; overflow:hidden; display:flex; flex-direction:column; border-radius: 16px;">
                    <div style="padding:24px; flex:1;">
                        <div class="lesson-metadata-row" style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:18px;">
                            <span class="meta-capsule" title="<?php _e('التاريخ', 'control'); ?>" style="background:rgba(0,0,0,0.03); color:var(--control-muted); padding:3px 10px; border-radius:30px; font-size:0.6rem; font-weight:700; border:1px solid var(--control-border); display:flex; align-items:center; gap:4px;">
                                <span class="dashicons dashicons-calendar-alt" style="font-size:12px; width:12px; height:12px;"></span>
                                <?php echo date_i18n($is_rtl ? 'Y/m/d' : 'm/d/Y', strtotime($l->created_at)); ?>
                            </span>
                            <span class="meta-capsule" title="<?php _e('مدة الدرس', 'control'); ?>" style="background:rgba(0,0,0,0.03); color:var(--control-muted); padding:3px 10px; border-radius:30px; font-size:0.6rem; font-weight:700; border:1px solid var(--control-border); display:flex; align-items:center; gap:4px;">
                                <span class="dashicons dashicons-clock" style="font-size:12px; width:12px; height:12px;"></span>
                                <?php echo esc_html($l->duration); ?> <?php echo $is_rtl ? 'دقيقة' : 'Min'; ?>
                            </span>
                            <span class="meta-capsule" title="<?php _e('اللغة', 'control'); ?>" style="background:rgba(0,0,0,0.03); color:var(--control-muted); padding:3px 10px; border-radius:30px; font-size:0.6rem; font-weight:700; border:1px solid var(--control-border); display:flex; align-items:center; gap:4px;">
                                <span class="dashicons dashicons-translation" style="font-size:12px; width:12px; height:12px;"></span>
                                <?php echo $is_rtl ? 'العربية' : 'English'; ?>
                            </span>
                            <span class="meta-capsule" title="<?php _e('الصف', 'control'); ?>" style="background:rgba(0,0,0,0.03); color:var(--control-muted); padding:3px 10px; border-radius:30px; font-size:0.6rem; font-weight:700; border:1px solid var(--control-border); display:flex; align-items:center; gap:4px;">
                                <span class="dashicons dashicons-welcome-learn-more" style="font-size:12px; width:12px; height:12px;"></span>
                                <?php echo esc_html($l->target_group); ?>
                            </span>
                        </div>

                        <h4 style="margin:0; font-size:1.2rem; font-weight:800; color:var(--control-text-dark); line-height: 1.4;"><?php echo esc_html($l->title); ?></h4>

                        <?php if($can_view_all && isset($l->first_name)): ?>
                            <div style="margin-top:15px; display:flex; align-items:center; gap:10px; padding-top:15px; border-top:1px solid rgba(0,0,0,0.05);">
                                <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:var(--control-bg); flex-shrink:0; border:1px solid var(--control-border);">
                                    <?php if(isset($l->avatar) && !empty($l->avatar)): ?>
                                        <img src="<?php echo esc_url($l->avatar); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <div class="avatar-placeholder <?php echo ($l->gender ?? 'male') === 'female' ? 'avatar-female' : 'avatar-male'; ?>" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">
                                            <?php echo strtoupper(substr($l->first_name, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-size:0.75rem; font-weight:800; color:var(--control-text-dark);"><?php echo esc_html($l->first_name . ' ' . $l->last_name); ?></div>
                                    <div style="font-size:0.6rem; color:var(--control-muted);"><?php _e('معد الدرس', 'control'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-card-actions" style="background:var(--control-bg); padding:10px 20px; border-top:1px solid var(--control-border); display:flex; gap:6px; align-items:center; justify-content: flex-start; flex-wrap: nowrap; overflow-x: auto;">
                        <button class="control-btn view-lesson-pdf" data-id="<?php echo $l->id; ?>" title="<?php _e('معاينة', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:var(--control-primary); border-radius: 6px; display:flex; align-items:center; justify-content:center; border:none;">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button class="control-btn download-lesson-pdf" data-id="<?php echo $l->id; ?>" title="<?php _e('تحميل', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:var(--control-accent); color:var(--control-primary) !important; border-radius: 6px; display:flex; align-items:center; justify-content:center; border:none;">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                        <button class="control-btn print-lesson-btn" data-id="<?php echo $l->id; ?>" title="<?php _e('طباعة', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border); border-radius: 6px; display:flex; align-items:center; justify-content:center;">
                            <span class="dashicons dashicons-printer"></span>
                        </button>
                        <button class="control-btn share-whatsapp-direct" data-id="<?php echo $l->id; ?>" title="<?php _e('واتساب', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:#25D366; color:#fff !important; border:none; border-radius: 6px; display:flex; align-items:center; justify-content:center;">
                            <span class="dashicons dashicons-whatsapp"></span>
                        </button>
                        <button class="control-btn share-email-direct" data-id="<?php echo $l->id; ?>" title="<?php _e('بريد إلكتروني', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:#ea4335; color:#fff !important; border:none; border-radius: 6px; display:flex; align-items:center; justify-content:center;">
                            <span class="dashicons dashicons-email"></span>
                        </button>
                        <button class="control-btn edit-lesson-btn" data-id="<?php echo $l->id; ?>" title="<?php _e('تعديل', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border); border-radius: 6px; display:flex; align-items:center; justify-content:center;">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="control-btn delete-lesson-btn" data-id="<?php echo $l->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="width:34px; height:34px; padding:0; flex-shrink:0; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2; border-radius: 6px; display:flex; align-items:center; justify-content:center;">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Lesson Wizard Modal -->
<div id="lesson-wizard-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10002; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="control-card" style="width:100%; max-width:900px; height:90vh; padding:0; border-radius:24px; overflow:hidden; display:flex; flex-direction:column; box-shadow: 0 40px 100px rgba(0,0,0,0.3);">

        <div style="background:var(--control-primary); color:#fff; padding:20px 35px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:20px;">
                <div>
                    <h3 id="wizard-main-title" style="color:#fff; margin:0; font-size:1.2rem;"><?php _e('معالج تحضير الدروس الذكي', 'control'); ?></h3>
                    <div id="wizard-step-indicator" style="opacity:0.7; font-size:0.8rem; margin-top:4px;"></div>
                </div>
                <div class="lang-selector-wizard" style="display:flex; background:rgba(255,255,255,0.15); padding:4px; border-radius:12px; border:1px solid rgba(255,255,255,0.3); overflow:hidden;">
                    <button type="button" class="wizard-lang-btn active" data-lang="ar" style="border:none; background:none; color:#fff; padding:6px 15px; cursor:pointer; font-size:0.8rem; font-weight:700; border-radius:8px; transition:0.3s;">العربية</button>
                    <button type="button" class="wizard-lang-btn" data-lang="en" style="border:none; background:none; color:#fff; padding:6px 15px; cursor:pointer; font-size:0.8rem; font-weight:700; border-radius:8px; transition:0.3s;">English</button>
                    <input type="hidden" name="lang" id="lesson-lang" value="ar">
                </div>
            </div>
            <div style="display:flex; gap:8px;" id="lesson-wizard-dots">
                <span class="dot active" data-step="1"></span>
                <span class="dot" data-step="2"></span>
                <span class="dot" data-step="3"></span>
                <span class="dot" data-step="4"></span>
                <span class="dot" data-step="5"></span>
            </div>
        </div>

        <div style="flex:1; overflow-y:auto; padding:35px;" id="wizard-content">
            <form id="lesson-wizard-form">
                <input type="hidden" id="lesson-id" name="id" value="0">

                <!-- Step 1: Basic Context -->
                <div class="lesson-step" data-step="1">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                        <h4 style="margin:0; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('سياق الدرس العام', 'control'); ?></h4>
                        <button type="button" id="select-lesson-template" class="control-btn" style="font-size:0.75rem; min-height:34px; background:var(--control-bg); color:var(--control-primary) !important; border:1px solid var(--control-border);">
                            <span class="dashicons dashicons-layout" style="margin-left:5px;"></span><?php _e('استخدام قالب جاهز', 'control'); ?>
                        </button>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label data-i18n="lesson_title"><?php _e('عنوان الدرس', 'control'); ?> *</label>
                            <input type="text" id="lesson-title" name="title" required placeholder="<?php _e('Daily Lesson Planning', 'control'); ?>" value="Daily Lesson Planning">
                        </div>
                        <div class="control-form-group">
                            <label data-i18n="date_day"><?php _e('التاريخ واليوم', 'control'); ?></label>
                            <input type="text" id="lesson-date-formatted" name="date_formatted" readonly value="<?php echo date_i18n('l، j F Y'); ?>">
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label data-i18n="target_group"><?php _e('المجموعة المستهدفة / الصف', 'control'); ?> *</label>
                            <select id="lesson-target" name="target_group" required>
                                <option value="" data-i18n="select_grade"><?php _e('اختر الصف الدراسي...', 'control'); ?></option>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="<?php echo "الصف " . $i; ?>"><?php echo "الصف " . $i; ?></option>
                                <?php endfor; ?>
                                <option value="KINDERGARTEN" data-i18n="kindergarten"><?php _e('رياض الأطفال', 'control'); ?></option>
                                <option value="OTHER" data-i18n="other_group"><?php _e('مجموعة أخرى', 'control'); ?></option>
                            </select>
                        </div>
                        <div class="control-form-group">
                            <label data-i18n="duration_min"><?php _e('مدة الحصة (دقيقة)', 'control'); ?></label>
                            <input type="number" id="lesson-duration" name="duration" placeholder="45" value="45">
                        </div>
                    </div>

                    <div class="control-form-group">
                        <label data-i18n="resources_tools"><?php _e('المصادر والأدوات (اختر أو أضف)', 'control'); ?></label>
                        <div class="multi-select-pills" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                            <?php
                            $default_tools = array('PowerPoint', 'Educational videos', 'Digital presentations', 'Football', 'Basketball', 'Cones', 'Whistle', 'Mats');
                            foreach($default_tools as $tool): ?>
                                <span class="tool-pill" style="padding:4px 12px; border:1px solid var(--control-border); border-radius:20px; font-size:0.75rem; cursor:pointer;" data-value="<?php echo $tool; ?>"><?php echo $tool; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" id="lesson-equipment" name="equipment" placeholder="<?php _e('أدوات أخرى...', 'control'); ?>">
                    </div>
                </div>

                <!-- Step 2: Educational Framework -->
                <div class="lesson-step" data-step="2" style="display:none;">
                    <h4 data-i18n="objectives_tools" style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('الإطار التعليمي والمخرجات', 'control'); ?></h4>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <label data-i18n="learning_outcomes" style="margin:0;"><?php _e('نواتج التعلم (Learning Outcomes)', 'control'); ?></label>
                                <button type="button" class="browse-suggestions-btn" data-category="outcome" data-target="#lesson-outcomes" style="background:none; border:none; color:var(--control-accent); cursor:pointer;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                            <textarea id="lesson-outcomes" name="learning_outcomes" rows="3"></textarea>
                        </div>
                        <div class="control-form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <label data-i18n="objectives" style="margin:0;"><?php _e('الأهداف التعليمية (Objectives)', 'control'); ?></label>
                                <button type="button" class="browse-suggestions-btn" data-category="objective" data-target="#lesson-objectives" style="background:none; border:none; color:var(--control-accent); cursor:pointer;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                            <textarea id="lesson-objectives" name="objectives" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label data-i18n="national_agenda"><?php _e('أجندة الدولة (National Agenda)', 'control'); ?></label>
                            <textarea id="lesson-agenda" name="national_agenda" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <label data-i18n="skills_21st"><?php _e('مهارات القرن 21 (21st Century Skills)', 'control'); ?></label>
                            <textarea id="lesson-21skills" name="skills_21st" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Lesson Flow -->
                <div class="lesson-step" data-step="3" style="display:none;">
                    <h4 data-i18n="activities_exercises" style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('سير الأنشطة والتمارين مع توزيع الوقت', 'control'); ?></h4>

                    <div class="activity-section" style="margin-bottom:30px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h5 data-i18n="warmup" style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin:0; display:flex; align-items:center; gap:10px; flex:1;">
                                <span style="width:10px; height:10px; background:#10b981; border-radius:50%;"></span><?php _e('1. الإحماء (Warm-up)', 'control'); ?>
                            </h5>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="number" class="section-time" data-section="warmup" placeholder="Min" style="width:60px; height:30px; font-size:0.75rem; text-align:center;">
                                <button type="button" class="browse-suggestions-btn" data-category="warmup" style="background:none; border:none; color:var(--control-accent); cursor:pointer; font-weight:700; font-size:0.75rem;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                        </div>
                        <div id="warmup-activities"></div>
                        <button type="button" class="add-activity-btn control-btn" data-container="warmup-activities" style="background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); width:100%; font-size:0.8rem;">
                            <span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة تمرين إحماء', 'control'); ?>
                        </button>
                    </div>

                    <div class="activity-section" style="margin-bottom:30px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h5 data-i18n="main_activities" style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin:0; display:flex; align-items:center; gap:10px; flex:1;">
                                <span style="width:10px; height:10px; background:#3b82f6; border-radius:50%;"></span><?php _e('2. الجزء الرئيسي (Main Activities)', 'control'); ?>
                            </h5>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="number" class="section-time" data-section="main" placeholder="Min" style="width:60px; height:30px; font-size:0.75rem; text-align:center;">
                                <button type="button" class="browse-suggestions-btn" data-category="main" style="background:none; border:none; color:var(--control-accent); cursor:pointer; font-weight:700; font-size:0.75rem;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                        </div>
                        <div id="main-activities"></div>
                        <button type="button" class="add-activity-btn control-btn" data-container="main-activities" style="background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); width:100%; font-size:0.8rem;">
                            <span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة نشاط رئيسي', 'control'); ?>
                        </button>
                    </div>

                    <div class="activity-section">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h5 data-i18n="cooldown" style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin:0; display:flex; align-items:center; gap:10px; flex:1;">
                                <span style="width:10px; height:10px; background:#6366f1; border-radius:50%;"></span><?php _e('3. الختام والتهدئة (Cooldown)', 'control'); ?>
                            </h5>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="number" class="section-time" data-section="cooldown" placeholder="Min" style="width:60px; height:30px; font-size:0.75rem; text-align:center;">
                                <button type="button" class="browse-suggestions-btn" data-category="cooldown" style="background:none; border:none; color:var(--control-accent); cursor:pointer; font-weight:700; font-size:0.75rem;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                        </div>
                        <div id="cooldown-activities"></div>
                        <button type="button" class="add-activity-btn control-btn" data-container="cooldown-activities" style="background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); width:100%; font-size:0.8rem;">
                            <span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة نشاط ختامي', 'control'); ?>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Pedagogical Strategy -->
                <div class="lesson-step" data-step="4" style="display:none;">
                    <h4 data-i18n="assessment_notes" style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('الاستراتيجية البيداغوجية والربط', 'control'); ?></h4>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label data-i18n="teacher_role"><?php _e('دور المعلم', 'control'); ?></label>
                            <textarea id="lesson-teacher-role" name="teacher_role" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <label data-i18n="student_role"><?php _e('دور الطالب', 'control'); ?></label>
                            <textarea id="lesson-student-role" name="student_role" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label data-i18n="real_life_connection"><?php _e('الربط بالواقع (Real-life Connection)', 'control'); ?></label>
                            <textarea id="lesson-real-life" name="real_life" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <label data-i18n="cross_curricular"><?php _e('تكامل المواد (Cross-curricular)', 'control'); ?></label>
                            <textarea id="lesson-cross" name="cross_curricular" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label data-i18n="hots"><?php _e('مهارات التفكير العليا (HOTS)', 'control'); ?></label>
                            <textarea id="lesson-hots" name="hots" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <label data-i18n="assessment_tools" style="margin:0;"><?php _e('التقويم وأدوات القياس', 'control'); ?></label>
                                <button type="button" class="browse-suggestions-btn" data-category="assessment" data-target="#lesson-assessment" style="background:none; border:none; color:var(--control-accent); cursor:pointer;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                            <textarea id="lesson-assessment" name="assessment" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="control-form-group">
                        <label data-i18n="additional_notes"><?php _e('ملاحظات إضافية', 'control'); ?></label>
                        <textarea id="lesson-notes" name="notes" rows="2"></textarea>
                    </div>
                </div>

                <!-- Step 5: Professional Review -->
                <div class="lesson-step" data-step="5" style="display:none;">
                    <h4 data-i18n="confirm_review" style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('مراجعة وتصدير الخطة', 'control'); ?></h4>
                    <div style="background:var(--control-bg); padding:25px; border-radius:16px; border:1px solid var(--control-border); text-align:center;">
                        <span class="dashicons dashicons-media-document" style="font-size:60px; width:60px; height:60px; color:var(--control-primary); margin-bottom:20px;"></span>
                        <h3 data-i18n="ready_to_export" style="margin-bottom:10px;"><?php _e('جاهز للتصدير النهائي', 'control'); ?></h3>
                        <p data-i18n="wizard_desc" style="color:var(--control-muted);"><?php _e('لقد اكتملت كافة الخطوات. سيتم الآن إنشاء ملف PDF احترافي يتضمن كافة البيانات المخطط لها.', 'control'); ?></p>

                        <div style="margin-top:30px; display:flex; justify-content:center; gap:20px;">
                            <div style="text-align:right; font-size:0.8rem;">
                                <div><strong data-i18n="prepared_by"><?php _e('المعد:', 'control'); ?></strong> <?php echo esc_html($user->name); ?></div>
                                <div><strong data-i18n="organization"><?php _e('المنظمة:', 'control'); ?></strong> <?php echo esc_html($org_name); ?></div>
                            </div>
                            <?php if($org_logo): ?>
                                <img src="<?php echo esc_url($org_logo); ?>" style="height:40px; border-radius:4px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div style="padding:20px 35px; background:var(--control-bg); border-top:1px solid var(--control-border); display:flex; justify-content:space-between; align-items:center;">
            <button type="button" id="lesson-wizard-prev" class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border); display:none;"><?php _e('السابق', 'control'); ?></button>
            <div style="flex:1;"></div>
            <div style="display:flex; gap:10px;">
                <button type="button" id="lesson-wizard-next" class="control-btn" style="background:var(--control-primary); border:none; min-width:120px;"><?php _e('التالي', 'control'); ?></button>
                <button type="button" id="lesson-wizard-save" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; min-width:150px; display:none; font-weight:800;"><?php _e('حفظ ومعاينة PDF', 'control'); ?></button>
                <button type="button" class="control-btn close-lesson-modal" style="background:#fff; color:#ef4444 !important; border:1px solid #fee2e2;"><?php _e('إلغاء', 'control'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Icon Selector Modal -->
<div id="icon-selector-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:10005; align-items:center; justify-content:center;">
    <div class="control-card" style="width:350px; padding:20px;">
        <h4 style="margin-top:0;"><?php _e('اختر أيقونة التمرين', 'control'); ?></h4>
        <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:10px; margin:20px 0;">
            <?php foreach($sports_icons as $icon => $label): ?>
                <div class="selectable-icon" data-icon="<?php echo $icon; ?>" title="<?php echo esc_attr($label); ?>" style="font-size:1.5rem; text-align:center; padding:5px; border:1px solid var(--control-border); border-radius:8px; cursor:pointer; transition:0.2s;">
                    <?php echo $icon; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="control-btn" onclick="jQuery('#icon-selector-modal').hide()" style="width:100%; background:var(--control-bg); color:var(--control-text-dark) !important; border:none;"><?php _e('إغلاق', 'control'); ?></button>
    </div>
</div>

<!-- Admin Suggestions Modal -->
<div id="suggestions-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10003; align-items:center; justify-content:center;">
    <div class="control-card" style="width:100%; max-width:700px; padding:0; border-radius:24px; overflow:hidden;">
        <div style="background:var(--control-primary); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0;"><?php _e('إدارة بنك المقترحات', 'control'); ?></h3>
            <button type="button" onclick="jQuery('#suggestions-modal').hide()" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>

        <div style="padding:30px;">
            <form id="add-suggestion-form">
                <input type="hidden" id="edit-suggestion-id" value="0">
                <div class="control-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div class="control-form-group">
                        <label><?php _e('الفئة', 'control'); ?></label>
                        <select id="suggestion-category" required>
                            <option value="title"><?php _e('عنوان الدرس', 'control'); ?></option>
                            <option value="outcome"><?php _e('نواتج تعلم', 'control'); ?></option>
                            <option value="objective"><?php _e('أهداف تعليمية', 'control'); ?></option>
                            <option value="warmup"><?php _e('تمرين إحماء', 'control'); ?></option>
                            <option value="main"><?php _e('نشاط رئيسي', 'control'); ?></option>
                            <option value="cooldown"><?php _e('نشاط ختامي', 'control'); ?></option>
                            <option value="assessment"><?php _e('طريقة تقويم', 'control'); ?></option>
                            <option value="general"><?php _e('عام', 'control'); ?></option>
                        </select>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('اللغة', 'control'); ?></label>
                        <select id="suggestion-lang" required>
                            <option value="ar">العربية (🇪🇬)</option>
                            <option value="en">English (🇺🇸)</option>
                        </select>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('العنوان / الموضوع', 'control'); ?></label>
                        <input type="text" id="suggestion-topic" required>
                    </div>
                </div>
                <div class="control-form-group">
                    <label><?php _e('المحتوى التفصيلي (اختياري)', 'control'); ?></label>
                    <textarea id="suggestion-content" rows="3"></textarea>
                </div>
                <div class="control-form-group">
                    <label><?php _e('الوسوم (مفصولة بفاصلة)', 'control'); ?></label>
                    <input type="text" id="suggestion-tags" placeholder="كرة قدم، لياقة، ابتدائي...">
                </div>
                <button type="submit" class="control-btn" style="width:100%;"><?php _e('حفظ المقترح في البنك', 'control'); ?></button>
            </form>

            <div style="margin-top:30px; border-top:1px solid var(--control-border); padding-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h4 style="margin:0;"><?php _e('المقترحات الحالية', 'control'); ?></h4>
                    <input type="text" id="filter-suggestions" placeholder="<?php _e('تصفية بالاسم أو الوسم...', 'control'); ?>" style="width:200px; padding:5px 10px; font-size:0.8rem;">
                </div>
                <div id="suggestions-list" style="max-height:300px; overflow-y:auto; border-radius:12px;">
                    <table class="control-table" style="font-size:0.8rem;">
                        <thead>
                            <tr style="background:var(--control-bg);">
                                <th><?php _e('الفئة', 'control'); ?></th>
                                <th><?php _e('الموضوع', 'control'); ?></th>
                                <th><?php _e('الوسوم', 'control'); ?></th>
                                <th style="width:80px;"><?php _e('إجراء', 'control'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="suggestions-table-body">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Browser Suggestions Modal (User side) -->
<div id="browse-suggestions-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10007; align-items:center; justify-content:center;">
    <div class="control-card" style="width:100%; max-width:600px; padding:0; border-radius:24px; overflow:hidden;">
        <div style="background:var(--control-accent); color:var(--control-primary); padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;"><?php _e('تصفح بنك المقترحات', 'control'); ?></h3>
            <button type="button" onclick="jQuery('#browse-suggestions-modal').hide()" style="background:none; border:none; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
        <div style="padding:25px; max-height:70vh; overflow-y:auto;" id="browse-suggestions-content">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<!-- Lesson Template Modal -->
<div id="lesson-template-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10005; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="control-card" style="width:100%; max-width:700px; padding:30px; border-radius:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;"><?php _e('اختر قالباً جاهزاً للدرس', 'control'); ?></h3>
            <button type="button" onclick="jQuery('#lesson-template-modal').hide()" style="background:none; border:none; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
        <div id="templates-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; max-height:400px; overflow-y:auto; padding:5px;">
            <!-- Templates injected via JS -->
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div id="lesson-preview-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10006; align-items:center; justify-content:center; backdrop-filter: blur(10px);">
    <div class="control-card" style="width:95%; max-width:1000px; height:90vh; padding:0; border-radius:24px; overflow:hidden; display:flex; flex-direction:column;">
        <div style="background:var(--control-primary); color:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('معاينة وتحميل تحضير الدرس', 'control'); ?></h3>
            <div style="display:flex; gap:15px;">
                <button id="download-preview-pdf" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; height:36px; padding:0 15px; font-size:0.8rem; font-weight:800;">
                    <span class="dashicons dashicons-download" style="margin-left:5px;"></span><?php _e('تحميل PDF الآن', 'control'); ?>
                </button>
                <button type="button" onclick="jQuery('#lesson-preview-modal').hide()" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
        </div>
        <div id="preview-iframe-container" style="flex:1; background:#525659; overflow-y:auto; position:relative;">
             <div id="preview-html-content" style="background:#fff; width:210mm; min-height:297mm; margin:20px auto; padding:20mm; box-shadow:0 0 20px rgba(0,0,0,0.2); direction:rtl; text-align:right; font-family:'Rubik', sans-serif; display:none; position:relative;"></div>
             <div id="preview-loader" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:#fff; text-align:center;">
                <span class="dashicons dashicons-update spin" style="font-size:40px; width:40px; height:40px;"></span>
                <p><?php _e('جاري معالجة المعاينة...', 'control'); ?></p>
             </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-confirmation-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:11000; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
    <div class="control-card" style="width:100%; max-width:400px; padding:30px; border-radius:20px; text-align:center;">
        <div style="width:70px; height:70px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; color:#ef4444;">
            <span class="dashicons dashicons-trash" style="font-size:35px; width:35px; height:35px;"></span>
        </div>
        <h3 style="margin-bottom:10px; color:var(--control-text-dark);"><?php _e('هل أنت متأكد؟', 'control'); ?></h3>
        <p style="color:var(--control-muted); font-size:0.9rem; margin-bottom:25px;"><?php _e('سيتم حذف هذا الدرس نهائياً من النظام ولا يمكن التراجع عن هذه الخطوة.', 'control'); ?></p>
        <div style="display:flex; gap:10px;">
            <button type="button" id="confirm-delete-btn" class="control-btn" style="flex:1; background:#ef4444; border:none;"><?php _e('نعم، احذف', 'control'); ?></button>
            <button type="button" onclick="jQuery('#delete-confirmation-modal').hide()" class="control-btn" style="flex:1; background:var(--control-bg); color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><?php _e('إلغاء', 'control'); ?></button>
        </div>
    </div>
</div>

<!-- Hidden PDF Export Container (Off-screen for rendering) -->
<div id="pdf-export-content" style="position:fixed; left:-9999px; top:-9999px; width:210mm; min-height:297mm; background:#fff; direction:rtl; text-align:right; font-family:'Rubik', sans-serif; padding:15mm; color:#1e293b; z-index:-1;">
    <!-- Populated by JS -->
</div>

<script>
jQuery(document).ready(function($) {
    const controlTranslations = {
        ar: {
            basic_info: 'البيانات الأساسية',
            objectives_tools: 'الأهداف والأدوات',
            activities_exercises: 'الأنشطة والتمارين',
            assessment_notes: 'التقويم والملاحظات',
            confirm_review: 'تأكيد ومراجعة',
            lesson_title: 'عنوان الدرس',
            date_day: 'التاريخ واليوم',
            target_group: 'المجموعة المستهدفة / الصف',
            duration_min: 'مدة الحصة (دقيقة)',
            resources_tools: 'المصادر والأدوات (اختر أو أضف)',
            learning_outcomes: 'نواتج التعلم (Learning Outcomes)',
            objectives: 'الأهداف التعليمية (Objectives)',
            national_agenda: 'أجندة الدولة (National Agenda)',
            skills_21st: 'مهارات القرن 21 (21st Century Skills)',
            warmup: '1. الإحماء (Warm-up)',
            main_activities: '2. الجزء الرئيسي (Main Activities)',
            cooldown: '3. الختام والتهدئة (Cooldown)',
            teacher_role: 'دور المعلم',
            student_role: 'دور الطالب',
            real_life_connection: 'الربط بالواقع (Real-life Connection)',
            cross_curricular: 'تكامل المواد (Cross-curricular)',
            hots: 'مهارات التفكير العليا (HOTS)',
            assessment_tools: 'التقويم وأدوات القياس',
            additional_notes: 'ملاحظات إضافية',
            ready_to_export: 'جاهز للتصدير النهائي',
            wizard_desc: 'لقد اكتملت كافة الخطوات. سيتم الآن إنشاء ملف PDF احترافي يتضمن كافة البيانات المخطط لها.',
            prepared_by: 'المعد:',
            organization: 'المنظمة:',
            next: 'التالي',
            prev: 'السابق',
            save_preview: 'حفظ ومعاينة PDF',
            cancel: 'إلغاء',
            processing: 'جاري المعالجة...',
            warmup_pdf: 'الإحماء والتحضير البدني',
            main_pdf: 'الجزء الرئيسي والمهاري',
            cooldown_pdf: 'الختام والاسترخاء',
            date_pdf: 'التاريخ:',
            grade_pdf: 'الصف:',
            duration_pdf: 'المدة:',
            teacher_pdf: 'المعلم:',
            pedagogical_strategy: 'الاستراتيجية البيداغوجية والربط',
            real_life_pdf: 'الربط بالواقع',
            cross_curricular_pdf: 'تكامل المواد',
            thinking_skills_pdf: 'مهارات التفكير (HOTS)',
            assessment_pdf: 'التقويم',
            coordinator_notes: 'ملاحظات المنسق:',
            official_footer: 'مستند رسمي صادر عبر منصة كنترول الذكية للإدارة الرياضية المتكاملة - www.control.system',
            min: 'دقيقة',
            select_grade: 'اختر الصف الدراسي...',
            kindergarten: 'رياض الأطفال',
            other_group: 'مجموعة أخرى',
            use_template: 'استخدام قالب جاهز',
            other_tools: 'أدوات أخرى...',
            add_warmup: 'إضافة تمرين إحماء',
            add_main: 'إضافة نشاط رئيسي',
            add_cooldown: 'إضافة نشاط ختامي',
            email_prompt: 'أدخل البريد الإلكتروني للمستلم:',
            email_sent: 'تم الإرسال!',
            grade_1: 'الصف الأول', grade_2: 'الصف الثاني', grade_3: 'الصف الثالث', grade_4: 'الصف الرابع',
            grade_5: 'الصف الخامس', grade_6: 'الصف السادس', grade_7: 'الصف السابع', grade_8: 'الصف الثامن',
            grade_9: 'الصف التاسع', grade_10: 'الصف العاشر', grade_11: 'الصف الحادي عشر', grade_12: 'الصف الثاني عشر'
        },
        en: {
            basic_info: 'Basic Information',
            objectives_tools: 'Objectives & Tools',
            activities_exercises: 'Activities & Exercises',
            assessment_notes: 'Assessment & Notes',
            confirm_review: 'Confirm & Review',
            lesson_title: 'Lesson Title',
            date_day: 'Date & Day',
            target_group: 'Target Group / Grade',
            duration_min: 'Duration (Minutes)',
            resources_tools: 'Resources & Tools (Select or Add)',
            learning_outcomes: 'Learning Outcomes',
            objectives: 'Educational Objectives',
            national_agenda: 'National Agenda',
            skills_21st: '21st Century Skills',
            warmup: '1. Warm-up',
            main_activities: '2. Main Activities',
            cooldown: '3. Cooldown',
            teacher_role: "Teacher's Role",
            student_role: "Student's Role",
            real_life_connection: 'Real-life Connection',
            cross_curricular: 'Cross-curricular Integration',
            hots: 'Higher Order Thinking Skills (HOTS)',
            assessment_tools: 'Assessment & Measurement Tools',
            additional_notes: 'Additional Notes',
            ready_to_export: 'Ready for Final Export',
            wizard_desc: 'All steps are completed. A professional PDF file will now be generated with all the planned data.',
            prepared_by: 'Prepared by:',
            organization: 'Organization:',
            next: 'Next',
            prev: 'Previous',
            save_preview: 'Save & Preview PDF',
            cancel: 'Cancel',
            processing: 'Processing...',
            warmup_pdf: 'Warm-up & Physical Preparation',
            main_pdf: 'Main Activities & Skills',
            cooldown_pdf: 'Conclusion & Relaxation',
            date_pdf: 'Date:',
            grade_pdf: 'Grade:',
            duration_pdf: 'Duration:',
            teacher_pdf: 'Teacher:',
            pedagogical_strategy: 'Pedagogical Strategy & Connection',
            real_life_pdf: 'Real-life Connection',
            cross_curricular_pdf: 'Cross-curricular',
            thinking_skills_pdf: 'Thinking Skills (HOTS)',
            assessment_pdf: 'Assessment',
            coordinator_notes: 'Coordinator Notes:',
            official_footer: 'Official document issued via Control Smart Platform for Integrated Sports Management - www.control.system',
            min: 'Min',
            select_grade: 'Select Grade...',
            kindergarten: 'Kindergarten',
            other_group: 'Other Group',
            use_template: 'Use Ready Template',
            other_tools: 'Other tools...',
            add_warmup: 'Add Warm-up Activity',
            add_main: 'Add Main Activity',
            add_cooldown: 'Add Cooldown Activity',
            email_prompt: 'Enter recipient email:',
            email_sent: 'Email sent!',
            grade_1: 'Grade 1', grade_2: 'Grade 2', grade_3: 'Grade 3', grade_4: 'Grade 4',
            grade_5: 'Grade 5', grade_6: 'Grade 6', grade_7: 'Grade 7', grade_8: 'Grade 8',
            grade_9: 'Grade 9', grade_10: 'Grade 10', grade_11: 'Grade 11', grade_12: 'Grade 12'
        }
    };

    let currentStep = 1;
    let activeIconTarget = null;
    let lastGeneratedPDF = null;

    const lessonTemplates = {
        ar: [
            {
                title: "أساسيات كرة القدم",
                grade: "الصف 6",
                objectives: "1. إتقان مهارة التمرير بوجه القدم الداخلي.\n2. التعرف على القوانين الأساسية للملعب.\n3. تنمية الروح الرياضية والعمل الجماعي.",
                equipment: "كرات قدم، أقماع، صافرة، شواخص",
                activities: {
                    warmup: [{icon: "🏃", title: "جري خفيف حول الملعب", desc: "جري لمدة 5 دقائق مع تحريك المفاصل."}],
                    main: [
                        {icon: "⚽", title: "تمرير الكرة في أزواج", desc: "كل طالبين يمرران الكرة لبعضهما من مسافة 5 أمتار."},
                        {icon: "🧱", title: "مراوغة الأقماع", desc: "الجري بالكرة بين الأقماع بسرعة متوسطة."}
                    ],
                    cooldown: [{icon: "🧘", title: "تمارين إطالة", desc: "إطالة لعضلات الرجلين والظهر."}]
                }
            },
            {
                title: "مهارات كرة السلة",
                grade: "الصف 8",
                objectives: "1. تحسين مهارة التنطيط باليدين.\n2. إتقان الرمية الحرة.\n3. زيادة اللياقة القلبية التنفسية.",
                equipment: "كرات سلة، صافرة، لوحة أهداف",
                activities: {
                    warmup: [{icon: "🤸", title: "إحماء سويدي", desc: "تمارين مرونة لجميع أعضاء الجسم."}],
                    main: [
                        {icon: "🏀", title: "التنطيط بالتبادل", desc: "التنطيط باليد اليمنى ثم اليسرى في خط مستقيم."},
                        {icon: "🏆", title: "مسابقة التصويب", desc: "تقسيم الطلاب لفريقين والتصويب من خط الرمية الحرة."}
                    ],
                    cooldown: [{icon: "🧘", title: "استرخاء وتحدث", desc: "تحدث عن أهمية النشاط البدني مع تمارين تنفس."}]
                }
            },
            {
                title: "الجمباز الفني الأساسي",
                grade: "الصف 4",
                objectives: "1. أداء الدحرجة الأمامية بشكل صحيح.\n2. تحسين توازن الجسم على قدم واحدة.\n3. زيادة مرونة العمود الفقري.",
                equipment: "مراتب جمباز، مقاعد خشبية",
                activities: {
                    warmup: [{icon: "🏃", title: "حركات تليين", desc: "دوران الرأس والكتفين والخصر."}],
                    main: [
                        {icon: "🤸", title: "الدحرجة الأمامية", desc: "التدريب على الدحرجة من وضع القرفصاء."},
                        {icon: "⚖️", title: "توازن الميزان", desc: "الوقوف على قدم واحدة ورفع الأخرى للخلف."}
                    ],
                    cooldown: [{icon: "🧘", title: "تنفس عميق", desc: "الجلوس وشهيق/زفير ببطء."}]
                }
            },
            {
                title: "مهارات السباحة (الزحف)",
                grade: "الصف 5",
                objectives: "1. إتقان حركة ضربات الرجلين.\n2. التوافق بين حركة الذراعين والتنفس.\n3. كسر حاجز الخوف من الماء العميق.",
                equipment: "نظارات سباحة، ألواح طفو، زعانف",
                activities: {
                    warmup: [{icon: "🏊", title: "طفو وانزلاق", desc: "الانزلاق على البطن مع مسك حافة الحوض."}],
                    main: [
                        {icon: "🏊", title: "ضربات الرجلين باللوح", desc: "قطع مسافة 25 متر باستخدام لوح الطفو فقط."},
                        {icon: "📢", title: "تبادل الذراعين", desc: "التدريب على سحب الماء بالذراعين مع لف الرأس للتنفس."}
                    ],
                    cooldown: [{icon: "🧘", title: "استرخاء في الماء", desc: "الطفو على الظهر مع تنفس هادئ."}]
                }
            },
            {
                title: "ألعاب القوى (الجري السريع)",
                grade: "الصف 7",
                objectives: "1. تعلم الانطلاق الصحيح من المكعبات.\n2. زيادة السرعة القصوى في المسافات القصيرة.\n3. تحسين تكنيك حركة الذراعين أثناء الجري.",
                equipment: "مكعبات بداية، ساعات توقيت، صافرة",
                activities: {
                    warmup: [{icon: "🏃", title: "إحماء حركي", desc: "رفع الركبتين عالياً ولمس المقعدة بالقدمين أثناء الجري."}],
                    main: [
                        {icon: "⏱️", title: "انطلاقات 30 متر", desc: "التدريب على رد الفعل السريع عند سماع الصافرة."},
                        {icon: "🏃", title: "سباق تتابع مصغر", desc: "تقسيم الطلاب لمجموعات وتدريبهم على تسليم العصا."}
                    ],
                    cooldown: [{icon: "🧘", title: "إطالة ثابتة", desc: "تمارين إطالة لعضلات الفخذ الخلفية والسمانة."}]
                }
            }
        ],
        en: [
            {
                title: "Football Fundamentals",
                grade: "Grade 6",
                objectives: "1. Master internal foot passing technique.\n2. Understand basic field rules.\n3. Promote sportsmanship and teamwork.",
                equipment: "Footballs, cones, whistle, markers",
                activities: {
                    warmup: [{icon: "🏃", title: "Light Jogging", desc: "5-minute jog around the field with joint rotations."}],
                    main: [
                        {icon: "⚽", title: "Partner Passing", desc: "Students pass the ball in pairs from 5 meters apart."},
                        {icon: "🧱", title: "Cone Dribbling", desc: "Dribbling through cones at medium speed."}
                    ],
                    cooldown: [{icon: "🧘", title: "Stretching Exercises", desc: "Leg and back stretches."}]
                }
            },
            {
                title: "Basketball Skills",
                grade: "Grade 8",
                objectives: "1. Improve two-handed dribbling skills.\n2. Master free-throw shooting.\n3. Increase cardiovascular fitness.",
                equipment: "Basketballs, whistle, hoops",
                activities: {
                    warmup: [{icon: "🤸", title: "Swedish Warm-up", desc: "Full-body flexibility exercises."}],
                    main: [
                        {icon: "🏀", title: "Alternate Dribbling", desc: "Dribbling with right then left hand in a straight line."},
                        {icon: "🏆", title: "Shooting Competition", desc: "Split students into teams for a free-throw contest."}
                    ],
                    cooldown: [{icon: "🧘", title: "Relaxation and Talk", desc: "Discuss physical activity benefits with breathing exercises."}]
                }
            },
            {
                title: "Basic Artistic Gymnastics",
                grade: "Grade 4",
                objectives: "1. Perform a forward roll correctly.\n2. Improve one-leg body balance.\n3. Increase spine flexibility.",
                equipment: "Gymnastics mats, benches",
                activities: {
                    warmup: [{icon: "🏃", title: "Loosening Up", desc: "Head, shoulder, and waist rotations."}],
                    main: [
                        {icon: "🤸", title: "Forward Roll", desc: "Practicing the roll from a squat position."},
                        {icon: "⚖️", title: "Scale Balance", desc: "Standing on one leg with the other raised backward."}
                    ],
                    cooldown: [{icon: "🧘", title: "Deep Breathing", desc: "Sitting and slow inhale/exhale."}]
                }
            },
            {
                title: "Swimming Skills (Crawl)",
                grade: "Grade 5",
                objectives: "1. Master leg kick movement.\n2. Coordinate arm strokes with breathing.\n3. Build confidence in deep water.",
                equipment: "Goggles, kickboards, fins",
                activities: {
                    warmup: [{icon: "🏊", title: "Floating and Gliding", desc: "Gliding on front while holding the pool edge."}],
                    main: [
                        {icon: "🏊", title: "Leg Kicks with Board", desc: "Swim 25 meters using only kickboard for support."},
                        {icon: "📢", title: "Alternating Arm Strokes", desc: "Practicing the pull phase with head rotation for breathing."}
                    ],
                    cooldown: [{icon: "🧘", title: "Water Relaxation", desc: "Floating on back with calm breathing."}]
                }
            },
            {
                title: "Athletics (Sprinting)",
                grade: "Grade 7",
                objectives: "1. Learn correct starting block position.\n2. Increase maximum speed over short distances.\n3. Improve arm drive technique.",
                equipment: "Starting blocks, stopwatches, whistle",
                activities: {
                    warmup: [{icon: "🏃", title: "Dynamic Warm-up", desc: "High knees and butt kicks while jogging."}],
                    main: [
                        {icon: "⏱️", title: "30m Explosive Starts", desc: "Training quick reaction to the whistle."},
                        {icon: "🏃", title: "Mini Relay Race", desc: "Group students to practice baton exchange and teamwork."}
                    ],
                    cooldown: [{icon: "🧘", title: "Static Stretching", desc: "Stretching hamstrings and calf muscles."}]
                }
            }
        ]
    };

    function updateWizardLabels() {
        const lang = $('#lesson-lang').val();
        const trans = controlTranslations[lang];
        const isRtl = lang === 'ar';

        // Update Date Localization
        const now = new Date();
        const formattedDate = now.toLocaleDateString(isRtl ? 'ar-SA' : 'en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        $('#lesson-date-formatted').val(formattedDate);

        // Update step titles in the indicators and modal
        $('#wizard-main-title').text(isRtl ? 'معالج تحضير الدروس الذكي' : 'Smart Lesson Builder Wizard');

        // Use data-i18n attributes for robust translation
        $('[data-i18n]').each(function() {
            const key = $(this).data('i18n');
            if (trans[key]) {
                const suffix = ($(this).prop('tagName') === 'LABEL' && $(this).text().includes('*')) ? ' *' : '';

                if ($(this).find('span').length > 0 && (key === 'warmup' || key === 'main_activities' || key === 'cooldown')) {
                    const span = $(this).find('span').detach();
                    $(this).text(trans[key]).prepend(span);
                } else {
                    $(this).text(trans[key] + suffix);
                }
            }
        });

        // Update dynamic components
        $('#select-lesson-template').html(`<span class="dashicons dashicons-layout" style="margin-left:5px;"></span>${isRtl ? 'استخدام قالب جاهز' : 'Use Ready Template'}`);
        $('#lesson-equipment').attr('placeholder', isRtl ? 'أدوات أخرى...' : 'Other tools...');

        $('.add-activity-btn:eq(0)').html(`<span class="dashicons dashicons-plus" style="margin-left:5px;"></span>${isRtl ? 'إضافة تمرين إحماء' : 'Add Warm-up Activity'}`);
        $('.add-activity-btn:eq(1)').html(`<span class="dashicons dashicons-plus" style="margin-left:5px;"></span>${isRtl ? 'إضافة نشاط رئيسي' : 'Add Main Activity'}`);
        $('.add-activity-btn:eq(2)').html(`<span class="dashicons dashicons-plus" style="margin-left:5px;"></span>${isRtl ? 'إضافة نشاط ختامي' : 'Add Cooldown Activity'}`);

        $('#lesson-wizard-prev').text(trans.prev);
        $('#lesson-wizard-next').text(trans.next);
        $('#lesson-wizard-save').text(trans.save_preview);
        $('.close-lesson-modal:last').text(trans.cancel);

        showStep(currentStep); // Refresh current step label
    }

    function showStep(step) {
        $('.lesson-step').hide();
        $(`.lesson-step[data-step="${step}"]`).fadeIn(300);

        $('#lesson-wizard-dots .dot').removeClass('active');
        $(`#lesson-wizard-dots .dot[data-step="${step}"]`).addClass('active');

        $('#lesson-wizard-prev').toggle(step > 1);
        $('#lesson-wizard-next').toggle(step < 5);
        $('#lesson-wizard-save').toggle(step === 5);

        const lang = $('#lesson-lang').val();
        const trans = controlTranslations[lang];
        const stepLabels = {
            1: trans.basic_info,
            2: trans.objectives_tools,
            3: trans.activities_exercises,
            4: trans.assessment_notes,
            5: trans.confirm_review
        };
        $('#wizard-step-indicator').text(stepLabels[step]);
        currentStep = step;
    }

    $('#lesson-wizard-next').on('click', function() {
        if (currentStep === 1 && !$('#lesson-title').val()) {
            alert('<?php _e('يرجى إدخال عنوان الدرس', 'control'); ?>');
            return;
        }
        showStep(currentStep + 1);
    });

    $('#lesson-wizard-prev').on('click', function() { showStep(currentStep - 1); });

    $('#create-lesson-btn').on('click', function() {
        $('#lesson-wizard-form')[0].reset();
        $('#lesson-id').val('0');
        $('#warmup-activities, #main-activities, #cooldown-activities').empty();
        showStep(1);
        $('#lesson-wizard-modal').css('display', 'flex');
    });

    // Handle Prefills from Annual Planning
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('prefill_title')) {
        $('#create-lesson-btn').trigger('click');
        $('#lesson-title').val(urlParams.get('prefill_title'));
        const prefillLang = urlParams.get('prefill_lang') || 'ar';
        $(`.wizard-lang-btn[data-lang="${prefillLang}"]`).trigger('click');

        const prefillDate = urlParams.get('prefill_date');
        if (prefillDate) {
            const d = new Date(prefillDate);
            const formatted = d.toLocaleDateString(prefillLang === 'ar' ? 'ar-SA' : 'en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            $('#lesson-date-formatted').val(formatted);
        }

        // Clear params from URL without reload
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=' + urlParams.get('page') + '&control_view=lessons';
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }

    $('.close-lesson-modal').on('click', function() { $('#lesson-wizard-modal').hide(); });

    $(document).on('click', '.wizard-lang-btn', function() {
        const lang = $(this).data('lang');
        $('.wizard-lang-btn').removeClass('active');
        $(this).addClass('active');
        $('#lesson-lang').val(lang);
        updateWizardLabels();
    });

    // Activity Management
    $(document).on('click', '.add-activity-btn', function() {
        const containerId = $(this).data('container');
        const count = $(`#${containerId} .activity-item`).length + 1;
        const html = `
            <div class="activity-item" style="background:#fff; border:1px solid var(--control-border); padding:20px; border-radius:12px; margin-bottom:15px; position:relative;">
                <button type="button" class="remove-activity" style="position:absolute; top:10px; left:10px; background:none; border:none; color:#ef4444; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
                <div style="display:flex; gap:15px;">
                    <div class="select-icon-trigger" style="width:50px; height:50px; background:var(--control-bg); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; cursor:pointer; border:1px dashed var(--control-border);" title="<?php _e('اختر أيقونة', 'control'); ?>">🏃</div>
                    <div style="flex:1;">
                        <input type="text" class="activity-title" placeholder="<?php _e('عنوان النشاط/التمرين', 'control'); ?>" style="margin-bottom:10px; font-weight:700;">
                        <textarea class="activity-desc" placeholder="<?php _e('وصف موجز لطريقة الأداء والتعليمات...', 'control'); ?>" rows="2"></textarea>
                    </div>
                </div>
            </div>
        `;
        $(`#${containerId}`).append(html);
    });

    $(document).on('click', '.remove-activity', function() { $(this).closest('.activity-item').remove(); });

    $(document).on('click', '.select-icon-trigger', function() {
        activeIconTarget = $(this);
        $('#icon-selector-modal').css('display', 'flex');
    });

    $(document).on('click', '.selectable-icon', function() {
        if (activeIconTarget) {
            activeIconTarget.text($(this).data('icon'));
        }
        $('#icon-selector-modal').hide();
    });

    $('.suggestion-chip').on('click', function() {
        const topic = $(this).data('topic');
        const lang = $(this).data('lang');

        $('#create-lesson-btn').trigger('click');
        $('#lesson-title').val(topic).trigger('change');
        $(`.wizard-lang-btn[data-lang="${lang}"]`).trigger('click');

        $(this).css('background', 'var(--control-accent)').css('color', '#000');
        setTimeout(() => {
            $(this).css('background', '#fff').css('color', 'inherit');
        }, 1000);
    });

    $(document).on('click', '.sug-lang-btn', function() {
        const lang = $(this).data('lang');
        $('.sug-lang-btn').removeClass('active').css('background', 'none');
        $(this).addClass('active').css('background', 'var(--control-bg)');

        $('.suggestion-chip').each(function() {
            if ($(this).data('lang') === lang) $(this).show();
            else $(this).hide();
        });
    });

    // Templates Logic
    $('#select-lesson-template').on('click', function() {
        let html = '';
        const activeLang = $('#lesson-lang').val();
        const currentTemplates = lessonTemplates[activeLang] || lessonTemplates.ar;

        currentTemplates.forEach((tpl, i) => {
            html += `
                <div class="template-card" data-index="${i}" style="background:var(--control-bg); border:1px solid var(--control-border); padding:20px; border-radius:15px; cursor:pointer; transition:0.2s;">
                    <div style="font-weight:800; color:var(--control-primary); margin-bottom:5px;">${tpl.title}</div>
                    <div style="font-size:0.75rem; color:var(--control-muted);">${tpl.grade}</div>
                </div>
            `;
        });
        $('#templates-grid').html(html);
        $('#lesson-template-modal').css('display', 'flex');
    });

    $(document).on('click', '.template-card', function() {
        const activeLang = $('#lesson-lang').val();
        const currentTemplates = lessonTemplates[activeLang] || lessonTemplates.ar;
        const tpl = currentTemplates[$(this).data('index')];
        $('#lesson-title').val(tpl.title);
        $('#lesson-target').val(tpl.grade);
        $('#lesson-objectives').val(tpl.objectives);
        $('#lesson-equipment').val(tpl.equipment);

        $('#warmup-activities, #main-activities, #cooldown-activities').empty();
        populateActivities('warmup-activities', tpl.activities.warmup);
        populateActivities('main-activities', tpl.activities.main);
        populateActivities('cooldown-activities', tpl.activities.cooldown);

        $('#lesson-template-modal').hide();
        if (typeof updateFloatingLabels === 'function') updateFloatingLabels();
    });

    // Tool Pills multi-select logic
    $(document).on('click', '.tool-pill', function() {
        $(this).toggleClass('active');
        let selected = [];
        $('.tool-pill.active').each(function() {
            selected.push($(this).data('value'));
        });
        const currentOther = $('#lesson-equipment').val().split(',').map(s => s.trim()).filter(s => !selected.includes(s) && s !== '');
        $('#lesson-equipment').val([...selected, ...currentOther].join(', '));
    });

    // Save and Preview PDF
    $('#lesson-wizard-save').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php _e('جاري المعالجة...', 'control'); ?>');

        const lessonData = {
            lang: $('#lesson-lang').val(),
            title: $('#lesson-title').val(),
            date_formatted: $('#lesson-date-formatted').val(),
            target_group: $('#lesson-target').val(),
            duration: $('#lesson-duration').val(),
            equipment: $('#lesson-equipment').val(),
            learning_outcomes: $('#lesson-outcomes').val(),
            objectives: $('#lesson-objectives').val(),
            national_agenda: $('#lesson-agenda').val(),
            skills_21st: $('#lesson-21skills').val(),
            teacher_role: $('#lesson-teacher-role').val(),
            student_role: $('#lesson-student-role').val(),
            real_life: $('#lesson-real-life').val(),
            cross_curricular: $('#lesson-cross').val(),
            hots: $('#lesson-hots').val(),
            assessment: $('#lesson-assessment').val(),
            notes: $('#lesson-notes').val(),
            times: {
                warmup: $('.section-time[data-section="warmup"]').val(),
                main: $('.section-time[data-section="main"]').val(),
                cooldown: $('.section-time[data-section="cooldown"]').val()
            },
            activities: {
                warmup: collectActivities('warmup-activities'),
                main: collectActivities('main-activities'),
                cooldown: collectActivities('cooldown-activities')
            }
        };

        $.post(control_ajax.ajax_url, {
            action: 'control_save_lesson',
            id: $('#lesson-id').val(),
            lesson_data: lessonData,
            nonce: control_ajax.nonce
        }, function(res) {
            $btn.prop('disabled', false).text(originalText);
            if (res.success) {
                $('#lesson-wizard-modal').hide();
                preparePDFPreview(lessonData, res.data.id);
            } else {
                alert(res.data || 'Error saving lesson');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text(originalText);
            alert('<?php _e('حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة لاحقاً.', 'control'); ?>');
        });
    });

    function collectActivities(containerId) {
        const activities = [];
        $(`#${containerId} .activity-item`).each(function() {
            activities.push({
                icon: $(this).find('.select-icon-trigger').text(),
                title: $(this).find('.activity-title').val(),
                desc: $(this).find('.activity-desc').val()
            });
        });
        return activities;
    }

    function preparePDFPreview(data, id) {
        if (!data || !data.title) {
            alert('Error: Lesson data is incomplete.');
            return;
        }

        const $modal = $('#lesson-preview-modal');
        const $htmlContainer = $('#preview-html-content');
        const $exportContainer = $('#pdf-export-content');
        const $loader = $('#preview-loader');

        $modal.css('display', 'flex');
        $htmlContainer.hide();
        $loader.show();

        const creator = {
            first_name: '<?php echo esc_js($user->first_name); ?>',
            last_name: '<?php echo esc_js($user->last_name); ?>',
            job_title: '<?php echo esc_js($user->job_title); ?>',
            home_country: '<?php echo esc_js($user->home_country); ?>',
            employer_name: '<?php echo esc_js($user->employer_name ?: $org_name); ?>',
            org_logo: '<?php echo esc_js($org_logo); ?>'
        };

        const htmlBody = renderFormalPDFHtml(data, creator);

        // Update containers
        $htmlContainer.html(htmlBody);
        $exportContainer.html(htmlBody);
        $exportContainer.show(); // Ensure visible for layout calculation

        // Options for high quality rendering
        const opt = {
            margin:       [5, 5, 5, 5],
            filename:     `lesson_${id}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // Generate Blob for preview
        html2pdf().set(opt).from($exportContainer[0]).outputPdf('blob').then(function(pdfBlob) {
            const url = URL.createObjectURL(pdfBlob);
            lastGeneratedPDF = { blob: pdfBlob, url: url, filename: `lesson_${id}.pdf` };

            $loader.hide();
            $htmlContainer.show();
            $exportContainer.hide();

            $('#download-preview-pdf').off('click').on('click', function() {
                const link = document.createElement('a');
                link.href = url;
                link.download = lastGeneratedPDF.filename;
                link.click();
            });
        });
    }

    $(document).on('click', '.print-lesson-btn', function() {
        const id = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            $btn.prop('disabled', false).html(originalHtml);
            if (res.success) {
                const data = res.data.lesson_data;
                const creator = res.data;
                const html = renderFormalPDFHtml(data, creator);

                const printWindow = window.open('', '_blank', 'width=800,height=600');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>${data.title}</title>
                        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;600;700;800&display=swap">
                        <style>
                            body { font-family: 'Rubik', sans-serif; margin: 0; padding: 20px; direction: ${data.lang === 'en' ? 'ltr' : 'rtl'}; }
                            @media print {
                                body { padding: 0; }
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        ${html}
                        <script>
                            window.onload = function() {
                                window.print();
                                window.onafterprint = function() { window.close(); };
                            };
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            } else {
                alert(res.data || 'Error fetching lesson');
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalHtml);
            alert('Error communicating with server');
        });
    });

    $(document).on('click', '.view-lesson-pdf', function() {
        const id = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            $btn.prop('disabled', false).html(originalHtml);
            if (res.success) {
                preparePDFPreview(res.data.lesson_data, res.data.id);
            } else {
                alert(res.data || 'Error fetching lesson');
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalHtml);
            alert('Error communicating with server');
        });
    });

    $(document).on('click', '.edit-lesson-btn', function() {
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).css('opacity', '0.5');

        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            $btn.prop('disabled', false).css('opacity', '1');
            if (res.success) {
                const l = res.data;
                const d = l.lesson_data;
                $('#lesson-id').val(l.id);
                $('#lesson-title').val(d.title);
                $('#lesson-date-formatted').val(d.date_formatted || '');
                $('#lesson-target').val(d.target_group);
                $('#lesson-duration').val(d.duration);
                $('#lesson-equipment').val(d.equipment);
                $('#lesson-outcomes').val(d.learning_outcomes);
                $('#lesson-objectives').val(d.objectives);
                $('#lesson-agenda').val(d.national_agenda);
                $('#lesson-21skills').val(d.skills_21st);
                $('#lesson-teacher-role').val(d.teacher_role);
                $('#lesson-student-role').val(d.student_role);
                $('#lesson-real-life').val(d.real_life);
                $('#lesson-cross').val(d.cross_curricular);
                $('#lesson-hots').val(d.hots);
                $('#lesson-assessment').val(d.assessment);
                $('#lesson-notes').val(d.notes);

                if(d.times) {
                    $('.section-time[data-section="warmup"]').val(d.times.warmup);
                    $('.section-time[data-section="main"]').val(d.times.main);
                    $('.section-time[data-section="cooldown"]').val(d.times.cooldown);
                }

                // Clear and populate activities
                $('#warmup-activities, #main-activities, #cooldown-activities').empty();
                populateActivities('warmup-activities', d.activities.warmup);
                populateActivities('main-activities', d.activities.main);
                populateActivities('cooldown-activities', d.activities.cooldown);

                // Sync pills
                $('.tool-pill').removeClass('active');
                if(d.equipment) {
                    const tools = d.equipment.split(',').map(s => s.trim());
                    $('.tool-pill').each(function() {
                        if(tools.includes($(this).data('value'))) $(this).addClass('active');
                    });
                }

                showStep(1);
                $('#lesson-wizard-modal').css('display', 'flex');
            } else {
                alert(res.data || 'Error loading lesson');
            }
        }).fail(function() {
            $btn.prop('disabled', false).css('opacity', '1');
            alert('Error communicating with server');
        });
    });

    function populateActivities(containerId, list) {
        if (!list) return;
        list.forEach(act => {
            const html = `
                <div class="activity-item" style="background:#fff; border:1px solid var(--control-border); padding:20px; border-radius:12px; margin-bottom:15px; position:relative;">
                    <button type="button" class="remove-activity" style="position:absolute; top:10px; left:10px; background:none; border:none; color:#ef4444; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
                    <div style="display:flex; gap:15px;">
                        <div class="select-icon-trigger" style="width:50px; height:50px; background:var(--control-bg); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; cursor:pointer; border:1px dashed var(--control-border);">${act.icon || '🏃'}</div>
                        <div style="flex:1;">
                            <input type="text" class="activity-title" value="${act.title}" placeholder="<?php _e('عنوان النشاط/التمرين', 'control'); ?>" style="margin-bottom:10px; font-weight:700;">
                            <textarea class="activity-desc" placeholder="<?php _e('وصف موجز لطريقة الأداء والتعليمات...', 'control'); ?>" rows="2">${act.desc}</textarea>
                        </div>
                    </div>
                </div>
            `;
            $(`#${containerId}`).append(html);
        });
    }

    // Search and Filtering
    function filterLessons() {
        const query = $('#lesson-search-input').val().toLowerCase();
        const grade = $('#lesson-grade-filter').val();
        const langFilter = $('#lesson-lang-filter').val();
        const sort = $('#lesson-date-filter').val();

        let visibleCards = $('.lesson-card').filter(function() {
            const card = $(this);
            const title = card.find('h4').text().toLowerCase();
            const creator = card.data('creator').toLowerCase();
            const cardGrade = (card.data('grade') || '').toString().toLowerCase();
            const lang = (card.data('lang') || 'ar').toLowerCase();

            const matchesQuery = !query || title.includes(query) || creator.includes(query);
            const matchesGrade = !grade || cardGrade === grade.toLowerCase();
            const matchesLang = !langFilter || lang === langFilter.toLowerCase();

            return matchesQuery && matchesGrade && matchesLang;
        });

        $('.lesson-card').hide();

        // Sort
        const sortedArray = visibleCards.toArray().sort(function(a, b) {
            const dateA = parseInt($(a).data('date'));
            const dateB = parseInt($(b).data('date'));
            return sort === 'newest' ? dateB - dateA : dateA - dateB;
        });

        $('#lesson-library-view .control-grid').append(sortedArray);
        $(sortedArray).fadeIn(200);
    }

    $('#lesson-search-input, #lesson-grade-filter, #lesson-lang-filter, #lesson-date-filter').on('input change', filterLessons);

    let lessonIdToDelete = null;
    $(document).on('click', '.delete-lesson-btn', function() {
        lessonIdToDelete = $(this).data('id');
        $('#delete-confirmation-modal').css('display', 'flex');
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!lessonIdToDelete) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('جاري الحذف...', 'control'); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_delete_lesson',
            id: lessonIdToDelete,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) location.reload();
            else {
                alert(res.data || 'Error deleting lesson');
                $btn.prop('disabled', false).text('<?php _e('نعم، احذف', 'control'); ?>');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('<?php _e('نعم، احذف', 'control'); ?>');
            alert('Error communicating with server');
        });
    });

    // --- Admin Suggestions Management ---

    let allSuggestions = <?php echo json_encode($suggestions); ?>;

    function renderSuggestionsTable() {
        const query = $('#filter-suggestions').val().toLowerCase();
        let html = '';
        allSuggestions.forEach(s => {
            if (!query || s.topic.toLowerCase().includes(query) || (s.tags && s.tags.toLowerCase().includes(query))) {
                const langIcon = s.lang === 'en' ? '🇺🇸' : '🇪🇬';
                html += `
                    <tr>
                        <td><span class="control-status-indicator indicator-accent">${s.category}</span></td>
                        <td>${langIcon} <strong>${s.topic}</strong></td>
                        <td><small>${s.tags || '---'}</small></td>
                        <td>
                            <div style="display:flex; gap:5px;">
                                <button class="delete-suggestion-btn audit-action-btn" data-id="${s.id}"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
        $('#suggestions-table-body').html(html);
    }

    $('#manage-suggestions-btn').on('click', function() {
        renderSuggestionsTable();
        $('#suggestions-modal').css('display', 'flex');
    });

    $('#filter-suggestions').on('input', renderSuggestionsTable);

    $('#add-suggestion-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('<?php _e('جاري الحفظ...', 'control'); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_save_lesson_suggestion',
            topic: $('#suggestion-topic').val(),
            category: $('#suggestion-category').val(),
            lang: $('#suggestion-lang').val(),
            content: $('#suggestion-content').val(),
            tags: $('#suggestion-tags').val(),
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) location.reload();
            else {
                alert(res.data);
                $btn.prop('disabled', false).text('<?php _e('حفظ المقترح في البنك', 'control'); ?>');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('<?php _e('حفظ المقترح في البنك', 'control'); ?>');
            alert('<?php _e('حدث خطأ أثناء الاتصال بالخادم.', 'control'); ?>');
        });
    });

    $(document).on('click', '.delete-suggestion-btn', function() {
        if (!confirm('<?php _e('حذف هذا المقترح من البنك؟', 'control'); ?>')) return;
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.post(control_ajax.ajax_url, { action: 'control_delete_lesson_suggestion', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) location.reload();
            else {
                alert(res.data || 'Error deleting suggestion');
                $btn.prop('disabled', false);
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            alert('Error communicating with server');
        });
    });

    // --- Browser Suggestions (User side) ---

    let currentTargetContainer = null;
    let currentTargetField = null;

    $(document).on('click', '.browse-suggestions-btn', function() {
        const cat = $(this).data('category');
        const target = $(this).data('target');
        const activeLang = $('#lesson-lang').val();

        currentTargetField = target ? $(target) : null;
        currentTargetContainer = !target ? $(this).closest('.activity-section').find('div[id$="-activities"]') : null;

        let html = '<div class="control-grid" style="grid-template-columns:1fr; gap:15px;">';
        // Strict language filtering for suggestions
        const filtered = allSuggestions.filter(s => {
            const suggestLang = s.lang || 'ar';
            return s.category === cat && suggestLang === activeLang;
        });

        if (filtered.length === 0) {
            html += `<p style="text-align:center; color:var(--control-muted);"><?php _e('لا توجد مقترحات متوفرة لهذه الفئة حالياً.', 'control'); ?></p>`;
        } else {
            filtered.forEach(s => {
                html += `
                    <div class="suggestion-item-box" style="background:var(--control-bg); border:1px solid var(--control-border); padding:20px; border-radius:15px; cursor:pointer; transition:0.2s;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <h4 style="margin:0; color:var(--control-primary);">${s.topic}</h4>
                            <button class="control-btn insert-suggestion-btn" data-topic="${s.topic}" data-content="${s.content || ''}" style="height:30px; font-size:0.7rem; padding:0 12px; background:var(--control-accent); color:var(--control-primary) !important; border:none; font-weight:800;"><?php _e('إدراج', 'control'); ?></button>
                        </div>
                        <p style="font-size:0.8rem; color:var(--control-muted); margin:0;">${s.content || ''}</p>
                    </div>
                `;
            });
        }
        html += '</div>';
        $('#browse-suggestions-content').html(html);
        $('#browse-suggestions-modal').css('display', 'flex');
    });

    $(document).on('click', '.insert-suggestion-btn', function() {
        const topic = $(this).data('topic');
        const content = $(this).data('content');

        if (currentTargetField) {
            const currentVal = currentTargetField.val();
            const newVal = currentVal ? currentVal + "\n" + topic : topic;
            currentTargetField.val(newVal).trigger('change');
            if (typeof updateFloatingLabels === 'function') updateFloatingLabels();
        } else if (currentTargetContainer) {
            const html = `
                <div class="activity-item" style="background:#fff; border:1px solid var(--control-border); padding:20px; border-radius:12px; margin-bottom:15px; position:relative;">
                    <button type="button" class="remove-activity" style="position:absolute; top:10px; left:10px; background:none; border:none; color:#ef4444; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
                    <div style="display:flex; gap:15px;">
                        <div class="select-icon-trigger" style="width:50px; height:50px; background:var(--control-bg); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; cursor:pointer; border:1px dashed var(--control-border);" title="<?php _e('اختر أيقونة', 'control'); ?>">🏃</div>
                        <div style="flex:1;">
                            <input type="text" class="activity-title" value="${topic}" placeholder="<?php _e('عنوان النشاط/التمرين', 'control'); ?>" style="margin-bottom:10px; font-weight:700;">
                            <textarea class="activity-desc" placeholder="<?php _e('وصف موجز لطريقة الأداء والتعليمات...', 'control'); ?>" rows="2">${content}</textarea>
                        </div>
                    </div>
                </div>
            `;
            currentTargetContainer.append(html);
        }

        $('#browse-suggestions-modal').hide();
    });

    $(document).on('click', '.download-lesson-pdf, .share-whatsapp-direct, .share-email-direct', function() {
        const isShare = $(this).hasClass('share-whatsapp-direct');
        const isEmail = $(this).hasClass('share-email-direct');
        const id = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                const data = res.data.lesson_data;
                const creator = res.data;

                if (isShare) {
                    const lang = data.lang || 'ar';
                    const isEn = lang === 'en';
                    const trans = controlTranslations[lang];
                    const dateStr = data.date_formatted || new Date(res.data.created_at).toLocaleDateString(isEn ? 'en-US' : 'ar-SA');
                    const sender = creator.first_name + ' ' + creator.last_name;

                    let msg = isEn ?
                        `📋 *Lesson Plan Sharing*\n\n*Title:* ${data.title}\n*Grade:* ${data.target_group}\n*Duration:* ${data.duration} min\n*Date:* ${dateStr}\n*Prepared by:* ${sender}\n\nGenerated via Control System.` :
                        `📋 *مشاركة تحضير درس*\n\n*العنوان:* ${data.title}\n*الصف:* ${data.target_group}\n*المدة:* ${data.duration} دقيقة\n*التاريخ:* ${dateStr}\n*بواسطة:* ${sender}\n\nتم التوليد عبر نظام كنترول.`;

                    // For real file sharing, we would need to upload the PDF to a server and share the link,
                    // or use the Web Share API if supported and on mobile.
                    if (isEmail) {
                        const recipient = prompt(isEn ? 'Enter recipient email:' : 'أدخل البريد الإلكتروني للمستلم:');
                        if (!recipient) {
                            $btn.prop('disabled', false).html(originalHtml);
                            return;
                        }

                        const handleEmailSend = (pdfBase64 = '') => {
                            $.post(control_ajax.ajax_url, {
                                action: 'control_share_lesson_email',
                                id: id,
                                email: recipient,
                                pdf_base64: pdfBase64,
                                nonce: control_ajax.nonce
                            }, function(emailRes) {
                                $btn.prop('disabled', false).html(originalHtml);
                                alert(emailRes.success ? (isEn ? 'Email sent!' : 'تم الإرسال!') : emailRes.data);
                            });
                        };

                        if (lastGeneratedPDF && lastGeneratedPDF.blob) {
                            const reader = new FileReader();
                            reader.onloadend = function() { handleEmailSend(reader.result); };
                            reader.readAsDataURL(lastGeneratedPDF.blob);
                        } else {
                            // Generate first
                            const $exportContainer = $('#pdf-export-content');
                            $exportContainer.show();
                            const html = renderFormalPDFHtml(data, creator);
                            $exportContainer.html(html);
                            const opt = { margin: [5, 5, 5, 5], filename: `lesson_${id}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } };

                            setTimeout(() => {
                                html2pdf().set(opt).from($exportContainer[0]).outputPdf('blob').then(blob => {
                                    const reader = new FileReader();
                                    reader.onloadend = function() { handleEmailSend(reader.result); };
                                    reader.readAsDataURL(blob);
                                    $exportContainer.hide();
                                });
                            }, 500);
                        }
                    } else {
                        if (navigator.share && lastGeneratedPDF && lastGeneratedPDF.blob) {
                             const file = new File([lastGeneratedPDF.blob], `lesson_${id}.pdf`, { type: 'application/pdf' });
                             navigator.share({
                                 title: data.title,
                                 text: msg,
                                 files: [file]
                             }).catch(err => {
                                 const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
                                 window.open(waUrl, '_blank');
                             });
                        } else {
                             const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
                             window.open(waUrl, '_blank');
                        }
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                } else {
                    generateDirectPDF(data, id, creator, function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    });
                }
            } else {
                alert(res.data || 'Error loading lesson');
                $btn.prop('disabled', false).html(originalHtml);
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalHtml);
            alert('Error communicating with server');
        });
    });

    function generateDirectPDF(data, id, creator, callback) {
        if (!data || !data.title) {
            alert('Error: Lesson data is incomplete.');
            if(callback) callback();
            return;
        }

        const $exportContainer = $('#pdf-export-content');
        $exportContainer.css({
            'display': 'block',
            'visibility': 'visible',
            'position': 'fixed',
            'left': '-9999px',
            'top': '0',
            'width': '210mm'
        });

        const html = renderFormalPDFHtml(data, creator);
        $exportContainer.html(html);

        const opt = {
            margin:       [5, 5, 5, 5],
            filename:     `official_lesson_${id}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false, letterRendering: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // Add a short delay to ensure content is fully rendered in the off-screen DOM
        setTimeout(function() {
            html2pdf().set(opt).from($exportContainer[0]).outputPdf('blob').then(function(pdfBlob) {
                const url = URL.createObjectURL(pdfBlob);
                lastGeneratedPDF = { blob: pdfBlob, url: url, filename: opt.filename };

                // Trigger download
                const link = document.createElement('a');
                link.href = url;
                link.download = opt.filename;
                link.click();

                $exportContainer.hide();
                if(callback) callback();
            }).catch(err => {
                console.error('PDF Generation Error:', err);
                alert('Failed to generate PDF. Please try again.');
                $exportContainer.hide();
                if(callback) callback();
            });
        }, 500);
    }

    function renderFormalPDFHtml(data, creator) {
        const lang = data.lang || 'ar';
        const trans = controlTranslations[lang];
        const isRtl = lang === 'ar';
        const direction = isRtl ? 'rtl' : 'ltr';
        const textAlign = isRtl ? 'right' : 'left';
        const secondaryTextAlign = isRtl ? 'left' : 'right';
        const orgLogoHtml = creator.org_logo ? `<img src="${creator.org_logo}" style="height:50px; object-fit:contain; margin-bottom:5px;">` : '';

        let activitiesHtml = '';
        const sections = [
            { key: 'warmup', label: trans.warmup_pdf, color: '#10b981' },
            { key: 'main', label: trans.main_pdf, color: '#3b82f6' },
            { key: 'cooldown', label: trans.cooldown_pdf, color: '#6366f1' }
        ];

        sections.forEach(s => {
            if (data.activities[s.key] && data.activities[s.key].length > 0) {
                const timeValue = data.times ? (data.times[s.key] || '') : '';
                const time = timeValue ? ` (${timeValue} ${trans.min})` : '';
                activitiesHtml += `
                    <div style="margin-top:15px; page-break-inside: avoid;">
                        <h3 style="background:${s.color}; color:#fff; padding:6px 15px; border-radius:4px; font-size:13px; margin-bottom:8px; font-weight: 800;">${s.label}${time}</h3>
                        <table style="width:100%; border-collapse:collapse; border:1.5px solid #000; direction: ${direction};">
                `;
                data.activities[s.key].forEach(act => {
                    activitiesHtml += `
                        <tr>
                            <td style="padding:10px; width:45px; text-align:center; font-size:24px; background:#f8fafc; border:1px solid #000;">${act.icon}</td>
                            <td style="padding:10px; border:1px solid #000;">
                                <div style="font-weight:800; color:#000; margin-bottom:4px; font-size:12px;">${act.title}</div>
                                <div style="font-size:11px; color:#000; line-height:1.4;">${act.desc}</div>
                            </td>
                        </tr>
                    `;
                });
                activitiesHtml += `</table></div>`;
            }
        });

        return `
            <div style="background:#fff; border:1px solid #000; padding:10mm; border-radius:0; color:#000; font-size:11px; line-height: 1.4; font-family: 'Rubik', sans-serif;">
                <!-- Official Header -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #000; padding-bottom:12px; margin-bottom:15px; flex-direction: ${isRtl ? 'row' : 'row-reverse'};">
                    <div style="flex:1; text-align: ${textAlign};">
                        ${orgLogoHtml}
                        <div style="font-weight:800; font-size:20px; color:#000; margin-bottom:4px;">${data.title}</div>
                        <div style="color:#444; font-size:12px; font-weight:700;">${creator.employer_name || '<?php echo esc_html($org_name); ?>'}</div>
                    </div>
                    <div style="width:250px; text-align:${textAlign}; font-size:10px; color:#1e293b; line-height:1.4; border-${isRtl ? 'right' : 'left'}:1px solid #cbd5e1; padding-${isRtl ? 'right' : 'left'}:10px;">
                        <table style="width:100%; border-collapse:collapse; direction: ${direction};">
                            <tr><td style="font-weight:700; padding:2px 0;">${trans.date_pdf}</td><td style="text-align:${secondaryTextAlign}; padding:2px 0;">${data.date_formatted || new Date().toLocaleDateString(isRtl ? 'ar-SA' : 'en-US')}</td></tr>
                            <tr><td style="font-weight:700; padding:2px 0;">${trans.grade_pdf}</td><td style="text-align:${secondaryTextAlign}; padding:2px 0;">${data.target_group || '---'}</td></tr>
                            <tr><td style="font-weight:700; padding:2px 0;">${trans.duration_pdf}</td><td style="text-align:${secondaryTextAlign}; padding:2px 0;">${data.duration || '---'} ${trans.min}</td></tr>
                            <tr><td style="font-weight:700; padding:2px 0;">${trans.teacher_pdf}</td><td style="text-align:${secondaryTextAlign}; padding:2px 0;">${creator.first_name} ${creator.last_name}</td></tr>
                        </table>
                    </div>
                </div>

                <!-- Framework Table -->
                <table style="width:100%; border-collapse:collapse; margin-bottom:15px; border:1.5px solid #000; direction: ${direction};">
                    <tr style="background:#f8fafc;">
                        <th style="border:1px solid #000; padding:8px; width:50%; text-align:${textAlign}; font-size:12px; font-weight: 800;">${trans.learning_outcomes}</th>
                        <th style="border:1px solid #000; padding:8px; width:50%; text-align:${textAlign}; font-size:12px; font-weight: 800;">${trans.objectives}</th>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:10px; vertical-align:top; min-height:60px;">${data.learning_outcomes || '---'}</td>
                        <td style="border:1px solid #000; padding:10px; vertical-align:top; min-height:60px;">${data.objectives || '---'}</td>
                    </tr>
                    <tr style="background:#f8fafc;">
                        <th style="border:1px solid #000; padding:8px; text-align:${textAlign}; font-size:12px; font-weight: 800;">${trans.national_agenda}</th>
                        <th style="border:1px solid #000; padding:8px; text-align:${textAlign}; font-size:12px; font-weight: 800;">${trans.skills_21st}</th>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:10px; vertical-align:top; min-height:50px;">${data.national_agenda || '---'}</td>
                        <td style="border:1px solid #000; padding:10px; vertical-align:top; min-height:50px;">${data.skills_21st || '---'}</td>
                    </tr>
                </table>

                <!-- Tools -->
                <div style="background:#f8fafc; border:1px solid #0f172a; padding:5px 10px; margin-bottom:10px; text-align: ${textAlign};">
                    <strong style="font-size:11px; color:#0f172a;">${trans.resources_tools}:</strong>
                    <span style="font-size:10px; margin-${isRtl ? 'right' : 'left'}:5px;">${data.equipment || '---'}</span>
                </div>

                <!-- Activities -->
                <div style="margin-bottom: 20px;">
                    ${activitiesHtml}
                </div>

                <!-- Pedagogical Strategy Table -->
                <div style="margin-top:20px; text-align: ${textAlign}; page-break-inside: avoid;">
                    <h3 style="background:#000; color:#fff; padding:6px 15px; border-radius:4px; font-size:14px; margin-bottom:10px; font-weight: 800;">${trans.pedagogical_strategy}</h3>
                    <table style="width:100%; border-collapse:collapse; border:1.5px solid #000; direction: ${direction};">
                        <tr style="background:#f8fafc;">
                            <th style="border:1px solid #000; padding:6px; text-align:${textAlign}; width:33%; font-weight: 800;">${trans.teacher_role}</th>
                            <th style="border:1px solid #000; padding:6px; text-align:${textAlign}; width:33%; font-weight: 800;">${trans.student_role}</th>
                            <th style="border:1px solid #000; padding:6px; text-align:${textAlign}; width:33%; font-weight: 800;">${trans.real_life_pdf}</th>
                        </tr>
                        <tr>
                            <td style="border:1px solid #000; padding:8px; height:50px; vertical-align:top;">${data.teacher_role || '---'}</td>
                            <td style="border:1px solid #000; padding:8px; height:50px; vertical-align:top;">${data.student_role || '---'}</td>
                            <td style="border:1px solid #000; padding:8px; height:50px; vertical-align:top;">${data.real_life || '---'}</td>
                        </tr>
                        <tr style="background:#f8fafc;">
                            <th style="border:1px solid #000; padding:6px; text-align:${textAlign}; font-weight: 800;">${trans.cross_curricular_pdf}</th>
                            <th style="border:1px solid #000; padding:6px; text-align:${textAlign}; font-weight: 800;">${trans.thinking_skills_pdf}</th>
                            <th style="border:1px solid #000; padding:6px; text-align:${textAlign}; font-weight: 800;">${trans.assessment_pdf}</th>
                        </tr>
                        <tr>
                            <td style="border:1px solid #000; padding:8px; height:50px; vertical-align:top;">${data.cross_curricular || '---'}</td>
                            <td style="border:1px solid #000; padding:8px; height:50px; vertical-align:top;">${data.hots || '---'}</td>
                            <td style="border:1px solid #000; padding:8px; height:50px; vertical-align:top;">${data.assessment || '---'}</td>
                        </tr>
                    </table>
                </div>

                <!-- Footer Notes -->
                ${data.notes ? `
                <div style="margin-top:10px; border:1px solid #fde68a; background:#fffbeb; padding:8px; text-align: ${textAlign};">
                    <strong style="color:#92400e;">${trans.coordinator_notes}</strong>
                    <p style="margin:2px 0 0 0; color:#92400e; font-style:italic;">${data.notes}</p>
                </div>` : ''}

                <div style="margin-top:20px; text-align:center; font-size:8px; color:#94a3b8; border-top:1px solid #cbd5e1; padding-top:5px;">
                    ${trans.official_footer}
                </div>
            </div>
        `;
    }
});
</script>

<style>
#lesson-wizard-dots .dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.2); transition: 0.3s; }
#lesson-wizard-dots .dot.active { background: var(--control-accent); transform: scale(1.4); box-shadow: 0 0 15px var(--control-accent); }
.lesson-card { transition: 0.3s; cursor: default; border-radius: 16px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid var(--control-border); background: #fff; }
.lesson-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); border-color: var(--control-accent); }
.suggestion-chip:hover { border-color: var(--control-accent); color: var(--control-accent); }
.selectable-icon:hover { border-color: var(--control-accent); transform: scale(1.1); }
.template-card:hover { border-color: var(--control-accent) !important; transform: translateY(-2px); }
.wizard-lang-btn.active { background: #fff !important; color: var(--control-primary) !important; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.meta-capsule { transition: 0.2s; font-size: 0.6rem !important; padding: 2px 8px !important; }
.lesson-card:hover .meta-capsule { background: #fff !important; border-color: var(--control-accent) !important; color: var(--control-text-dark) !important; }
.avatar-placeholder { border-radius: 50%; color: #fff; }
.avatar-male { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.avatar-female { background: linear-gradient(135deg, #ec4899, #be185d); }
</style>
