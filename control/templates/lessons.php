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

        <select id="lesson-grade-filter" style="width:180px; padding:10px;">
            <option value=""><?php _e('جميع الصفوف', 'control'); ?></option>
            <?php for($i=1; $i<=12; $i++): ?>
                <option value="<?php echo "الصف " . $i; ?>"><?php echo "الصف " . $i; ?></option>
            <?php endfor; ?>
            <option value="KINDERGARTEN"><?php _e('رياض الأطفال', 'control'); ?></option>
        </select>

        <select id="lesson-date-filter" style="width:160px; padding:10px;">
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
            ?>
                <div class="control-card lesson-card" data-grade="<?php echo esc_attr($l->target_group); ?>" data-date="<?php echo strtotime($l->created_at); ?>" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
                    <div style="padding:20px; flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                            <span class="grade-badge" style="background:var(--control-bg); color:var(--control-primary); padding:4px 10px; border-radius:6px; font-size:0.65rem; font-weight:700;">
                                <?php echo esc_html($l->target_group); ?>
                            </span>
                            <span style="color:var(--control-muted); font-size:0.7rem;">
                                <span class="dashicons dashicons-clock" style="font-size:14px; width:14px; height:14px; vertical-align:middle; margin-left:4px;"></span>
                                <span class="date-text"><?php echo date_i18n('Y/m/d', strtotime($l->created_at)); ?></span>
                            </span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <h4 style="margin:0; font-size:1.05rem; font-weight:800; color:var(--control-text-dark);"><?php echo esc_html($l->title); ?></h4>
                            <span class="lang-badge" style="font-size:0.6rem; padding:2px 8px; border-radius:10px; background:var(--control-bg); color:var(--control-muted); border:1px solid var(--control-border);">
                                <?php echo ($l->lang ?? 'ar') === 'ar' ? '🇪🇬 العربية' : '🇺🇸 English'; ?>
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; color:var(--control-muted); font-size:0.75rem;">
                            <span><span class="dashicons dashicons-backup" style="font-size:14px; width:14px; height:14px;"></span> <?php echo esc_html($l->duration); ?></span>
                            <?php if($can_view_all && isset($l->first_name)): ?>
                                <span style="margin-right:auto;"><span class="dashicons dashicons-admin-users" style="font-size:14px; width:14px; height:14px;"></span> <?php echo esc_html($l->first_name . ' ' . $l->last_name); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="background:var(--control-bg); padding:12px 20px; border-top:1px solid var(--control-border); display:flex; gap:8px;">
                        <button class="control-btn view-lesson-pdf" data-id="<?php echo $l->id; ?>" title="<?php _e('معاينة', 'control'); ?>" style="flex:1; padding:0; height:34px; font-size:0.75rem; background:var(--control-primary);">
                            <span class="dashicons dashicons-visibility" style="margin-left:5px;"></span><?php _e('معاينة', 'control'); ?>
                        </button>
                        <button class="control-btn download-lesson-pdf" data-id="<?php echo $l->id; ?>" title="<?php _e('تحميل', 'control'); ?>" style="flex:1; padding:0; height:34px; font-size:0.75rem; background:var(--control-accent); color:var(--control-primary) !important; border:none;">
                            <span class="dashicons dashicons-download" style="margin-left:5px;"></span><?php _e('تحميل', 'control'); ?>
                        </button>
                        <button class="control-btn share-whatsapp-direct" data-id="<?php echo $l->id; ?>" title="<?php _e('مشاركة واتساب', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#25D366; color:#fff !important; border:none;">
                            <span class="dashicons dashicons-whatsapp"></span>
                        </button>
                        <button class="control-btn edit-lesson-btn" data-id="<?php echo $l->id; ?>" title="<?php _e('تعديل', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><span class="dashicons dashicons-edit"></span></button>
                        <button class="control-btn delete-lesson-btn" data-id="<?php echo $l->id; ?>" title="<?php _e('حذف', 'control'); ?>" style="padding:0; width:34px; height:34px; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2;"><span class="dashicons dashicons-trash"></span></button>
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
                <div class="lang-selector-wizard" style="display:flex; background:rgba(255,255,255,0.1); padding:4px; border-radius:30px; border:1px solid rgba(255,255,255,0.2);">
                    <button type="button" class="wizard-lang-btn active" data-lang="ar" title="العربية">🇪🇬</button>
                    <button type="button" class="wizard-lang-btn" data-lang="en" title="English">🇺🇸</button>
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
                            <label><?php _e('عنوان الدرس', 'control'); ?> *</label>
                            <input type="text" id="lesson-title" name="title" required placeholder="<?php _e('Daily Lesson Planning', 'control'); ?>" value="Daily Lesson Planning">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('التاريخ واليوم', 'control'); ?></label>
                            <input type="text" id="lesson-date-formatted" name="date_formatted" readonly value="<?php echo date_i18n('l، j F Y'); ?>">
                        </div>
                    </div>

                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('المجموعة المستهدفة / الصف', 'control'); ?> *</label>
                            <select id="lesson-target" name="target_group" required>
                                <option value=""><?php _e('اختر الصف الدراسي...', 'control'); ?></option>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="<?php echo "الصف " . $i; ?>"><?php echo "الصف " . $i; ?></option>
                                <?php endfor; ?>
                                <option value="KINDERGARTEN"><?php _e('رياض الأطفال', 'control'); ?></option>
                                <option value="OTHER"><?php _e('مجموعة أخرى', 'control'); ?></option>
                            </select>
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('مدة الحصة (دقيقة)', 'control'); ?></label>
                            <input type="number" id="lesson-duration" name="duration" placeholder="45" value="45">
                        </div>
                    </div>

                    <div class="control-form-group">
                        <label><?php _e('المصادر والأدوات (اختر أو أضف)', 'control'); ?></label>
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
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('الإطار التعليمي والمخرجات', 'control'); ?></h4>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <label style="margin:0;"><?php _e('نواتج التعلم (Learning Outcomes)', 'control'); ?></label>
                                <button type="button" class="browse-suggestions-btn" data-category="outcome" data-target="#lesson-outcomes" style="background:none; border:none; color:var(--control-accent); cursor:pointer;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                            <textarea id="lesson-outcomes" name="learning_outcomes" rows="3"></textarea>
                        </div>
                        <div class="control-form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <label style="margin:0;"><?php _e('الأهداف التعليمية (Objectives)', 'control'); ?></label>
                                <button type="button" class="browse-suggestions-btn" data-category="objective" data-target="#lesson-objectives" style="background:none; border:none; color:var(--control-accent); cursor:pointer;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                            <textarea id="lesson-objectives" name="objectives" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('أجندة الدولة (National Agenda)', 'control'); ?></label>
                            <textarea id="lesson-agenda" name="national_agenda" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('مهارات القرن 21 (21st Century Skills)', 'control'); ?></label>
                            <textarea id="lesson-21skills" name="skills_21st" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Lesson Flow -->
                <div class="lesson-step" data-step="3" style="display:none;">
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('سير الأنشطة والتمارين مع توزيع الوقت', 'control'); ?></h4>

                    <div class="activity-section" style="margin-bottom:30px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h5 style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin:0; display:flex; align-items:center; gap:10px; flex:1;">
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
                            <h5 style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin:0; display:flex; align-items:center; gap:10px; flex:1;">
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
                            <h5 style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin:0; display:flex; align-items:center; gap:10px; flex:1;">
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
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('الاستراتيجية البيداغوجية والربط', 'control'); ?></h4>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('دور المعلم', 'control'); ?></label>
                            <textarea id="lesson-teacher-role" name="teacher_role" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('دور الطالب', 'control'); ?></label>
                            <textarea id="lesson-student-role" name="student_role" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('الربط بالواقع (Real-life Connection)', 'control'); ?></label>
                            <textarea id="lesson-real-life" name="real_life" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('تكامل المواد (Cross-curricular)', 'control'); ?></label>
                            <textarea id="lesson-cross" name="cross_curricular" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('مهارات التفكير العليا (HOTS)', 'control'); ?></label>
                            <textarea id="lesson-hots" name="hots" rows="2"></textarea>
                        </div>
                        <div class="control-form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <label style="margin:0;"><?php _e('التقويم وأدوات القياس', 'control'); ?></label>
                                <button type="button" class="browse-suggestions-btn" data-category="assessment" data-target="#lesson-assessment" style="background:none; border:none; color:var(--control-accent); cursor:pointer;"><span class="dashicons dashicons-lightbulb"></span></button>
                            </div>
                            <textarea id="lesson-assessment" name="assessment" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('ملاحظات إضافية', 'control'); ?></label>
                        <textarea id="lesson-notes" name="notes" rows="2"></textarea>
                    </div>
                </div>

                <!-- Step 5: Professional Review -->
                <div class="lesson-step" data-step="5" style="display:none;">
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('مراجعة وتصدير الخطة', 'control'); ?></h4>
                    <div style="background:var(--control-bg); padding:25px; border-radius:16px; border:1px solid var(--control-border); text-align:center;">
                        <span class="dashicons dashicons-media-document" style="font-size:60px; width:60px; height:60px; color:var(--control-primary); margin-bottom:20px;"></span>
                        <h3 style="margin-bottom:10px;"><?php _e('جاهز للتصدير النهائي', 'control'); ?></h3>
                        <p style="color:var(--control-muted);"><?php _e('لقد اكتملت كافة الخطوات. سيتم الآن إنشاء ملف PDF احترافي يتضمن كافة البيانات المخطط لها.', 'control'); ?></p>

                        <div style="margin-top:30px; display:flex; justify-content:center; gap:20px;">
                            <div style="text-align:right; font-size:0.8rem;">
                                <div><strong><?php _e('المعد:', 'control'); ?></strong> <?php echo esc_html($user->name); ?></div>
                                <div><strong><?php _e('المنظمة:', 'control'); ?></strong> <?php echo esc_html($org_name); ?></div>
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

<!-- Hidden PDF Export Container (Off-screen for rendering) -->
<div id="pdf-export-content" style="position:fixed; left:-9999px; top:-9999px; width:210mm; min-height:297mm; background:#fff; direction:rtl; text-align:right; font-family:'Rubik', sans-serif; padding:15mm; color:#1e293b; z-index:-1;">
    <!-- Populated by JS -->
</div>

<script>
jQuery(document).ready(function($) {
    let currentStep = 1;
    let activeIconTarget = null;
    let lastGeneratedPDF = null;

    const lessonTemplates = [
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
        }
    ];

    function showStep(step) {
        $('.lesson-step').hide();
        $(`.lesson-step[data-step="${step}"]`).fadeIn(300);

        $('#lesson-wizard-dots .dot').removeClass('active');
        $(`#lesson-wizard-dots .dot[data-step="${step}"]`).addClass('active');

        $('#lesson-wizard-prev').toggle(step > 1);
        $('#lesson-wizard-next').toggle(step < 5);
        $('#lesson-wizard-save').toggle(step === 5);

        const stepLabels = {
            1: '<?php _e('البيانات الأساسية', 'control'); ?>',
            2: '<?php _e('الأهداف والأدوات', 'control'); ?>',
            3: '<?php _e('الأنشطة والتمارين', 'control'); ?>',
            4: '<?php _e('التقويم والملاحظات', 'control'); ?>',
            5: '<?php _e('تأكيد ومراجعة', 'control'); ?>'
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

    $('.close-lesson-modal').on('click', function() { $('#lesson-wizard-modal').hide(); });

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
        lessonTemplates.forEach((tpl, i) => {
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
        const tpl = lessonTemplates[$(this).data('index')];
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
        const sort = $('#lesson-date-filter').val();

        let visibleCards = $('.lesson-card').filter(function() {
            const card = $(this);
            const title = card.find('h4').text().toLowerCase();
            const cardGrade = card.find('.grade-badge').text().trim();

            const matchesQuery = !query || title.includes(query);
            const matchesGrade = !grade || cardGrade === grade;

            return matchesQuery && matchesGrade;
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

    $('#lesson-search-input, #lesson-grade-filter, #lesson-date-filter').on('input change', filterLessons);

    $(document).on('click', '.delete-lesson-btn', function() {
        if (!confirm('<?php _e('هل أنت متأكد من حذف هذا الدرس نهائياً؟', 'control'); ?>')) return;
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).css('opacity', '0.5');

        $.post(control_ajax.ajax_url, { action: 'control_delete_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) location.reload();
            else {
                alert(res.data || 'Error deleting lesson');
                $btn.prop('disabled', false).css('opacity', '1');
            }
        }).fail(function() {
            $btn.prop('disabled', false).css('opacity', '1');
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
        const filtered = allSuggestions.filter(s => s.category === cat && (s.lang || 'ar') === activeLang);

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

    $(document).on('click', '.download-lesson-pdf, .share-whatsapp-direct', function() {
        const isShare = $(this).hasClass('share-whatsapp-direct');
        const id = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                const data = res.data.lesson_data;
                const creator = res.data;

                if (isShare) {
                    const isEn = data.lang === 'en';
                    const dateStr = data.date_formatted || new Date(res.data.created_at).toLocaleDateString(isEn ? 'en-US' : 'ar-SA');
                    const sender = creator.first_name + ' ' + creator.last_name;

                    let msg = isEn ?
                        `📋 *Lesson Plan Sharing*\n\n*Title:* ${data.title}\n*Date:* ${dateStr}\n*By:* ${sender}\n\nGenerated via Control System.` :
                        `📋 *مشاركة تحضير درس*\n\n*العنوان:* ${data.title}\n*التاريخ:* ${dateStr}\n*بواسطة:* ${sender}\n\nتم التوليد عبر نظام كنترول.`;

                    const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
                    window.open(waUrl, '_blank');
                    $btn.prop('disabled', false).html(originalHtml);
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
        const $exportContainer = $('#pdf-export-content');
        $exportContainer.show(); // Briefly show to ensure layout is calculated

        const html = renderFormalPDFHtml(data, creator);
        $exportContainer.html(html);

        const opt = {
            margin:       [5, 5, 5, 5], // Reduced margins
            filename:     `official_lesson_${id}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from($exportContainer[0]).save().then(function() {
            $exportContainer.hide();
            if(callback) callback();
        });
    }

    function renderFormalPDFHtml(data, creator) {
        const orgLogoHtml = creator.org_logo ? `<img src="${creator.org_logo}" style="height:50px; object-fit:contain; margin-bottom:5px;">` : '';

        let activitiesHtml = '';
        const sections = [
            { key: 'warmup', label: '<?php _e('الإحماء والتحضير البدني', 'control'); ?>', color: '#10b981' },
            { key: 'main', label: '<?php _e('الجزء الرئيسي والمهاري', 'control'); ?>', color: '#3b82f6' },
            { key: 'cooldown', label: '<?php _e('الختام والاسترخاء', 'control'); ?>', color: '#6366f1' }
        ];

        sections.forEach(s => {
            if (data.activities[s.key] && data.activities[s.key].length > 0) {
                const time = data.times ? (data.times[s.key] ? ` (${data.times[s.key]} min)` : '') : '';
                activitiesHtml += `
                    <div style="margin-top:10px;">
                        <h3 style="background:${s.color}; color:#fff; padding:5px 12px; border-radius:4px; font-size:12px; margin-bottom:5px;">${s.label}${time}</h3>
                        <table style="width:100%; border-collapse:collapse; border:1px solid #cbd5e1;">
                `;
                data.activities[s.key].forEach(act => {
                    activitiesHtml += `
                        <tr style="border-bottom:1px solid #cbd5e1;">
                            <td style="padding:8px; width:35px; text-align:center; font-size:20px; background:#f1f5f9; border-left:1px solid #cbd5e1;">${act.icon}</td>
                            <td style="padding:8px;">
                                <div style="font-weight:800; color:#0f172a; margin-bottom:2px; font-size:11px;">${act.title}</div>
                                <div style="font-size:10px; color:#334155; line-height:1.3;">${act.desc}</div>
                            </td>
                        </tr>
                    `;
                });
                activitiesHtml += `</table></div>`;
            }
        });

        return `
            <div style="background:#fff; border:1px solid #94a3b8; padding:10px; border-radius:0; color:#1e293b; font-size:10px;">
                <!-- Official Header -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1.5px solid #0f172a; padding-bottom:10px; margin-bottom:15px;">
                    <div style="flex:1;">
                        ${orgLogoHtml}
                        <div style="font-weight:800; font-size:18px; color:#0f172a;">${data.title}</div>
                        <div style="color:#475569; font-size:11px; font-weight:700;">${creator.employer_name || '<?php echo esc_html($org_name); ?>'}</div>
                    </div>
                    <div style="width:250px; text-align:left; font-size:10px; color:#1e293b; line-height:1.4; border-right:1px solid #cbd5e1; padding-right:10px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr><td style="font-weight:700;"><?php _e('التاريخ:', 'control'); ?></td><td style="text-align:left;">${data.date_formatted || new Date().toLocaleDateString('ar-SA')}</td></tr>
                            <tr><td style="font-weight:700;"><?php _e('الصف:', 'control'); ?></td><td style="text-align:left;">${data.target_group || '---'}</td></tr>
                            <tr><td style="font-weight:700;"><?php _e('المدة:', 'control'); ?></td><td style="text-align:left;">${data.duration || '---'} min</td></tr>
                            <tr><td style="font-weight:700;"><?php _e('المعلم:', 'control'); ?></td><td style="text-align:left;">${creator.first_name} ${creator.last_name}</td></tr>
                        </table>
                    </div>
                </div>

                <!-- Framework Table -->
                <table style="width:100%; border-collapse:collapse; margin-bottom:10px; border:1px solid #0f172a;">
                    <tr style="background:#f1f5f9;">
                        <th style="border:1px solid #0f172a; padding:5px; width:50%; text-align:right; font-size:11px;"><?php _e('نواتج التعلم (Learning Outcomes)', 'control'); ?></th>
                        <th style="border:1px solid #0f172a; padding:5px; width:50%; text-align:right; font-size:11px;"><?php _e('الأهداف التعليمية (Objectives)', 'control'); ?></th>
                    </tr>
                    <tr>
                        <td style="border:1px solid #0f172a; padding:5px; vertical-align:top; height:50px;">${data.learning_outcomes || '---'}</td>
                        <td style="border:1px solid #0f172a; padding:5px; vertical-align:top; height:50px;">${data.objectives || '---'}</td>
                    </tr>
                    <tr style="background:#f1f5f9;">
                        <th style="border:1px solid #0f172a; padding:5px; text-align:right; font-size:11px;"><?php _e('أجندة الدولة (National Agenda)', 'control'); ?></th>
                        <th style="border:1px solid #0f172a; padding:5px; text-align:right; font-size:11px;"><?php _e('مهارات القرن 21 (21st Century Skills)', 'control'); ?></th>
                    </tr>
                    <tr>
                        <td style="border:1px solid #0f172a; padding:5px; vertical-align:top; height:40px;">${data.national_agenda || '---'}</td>
                        <td style="border:1px solid #0f172a; padding:5px; vertical-align:top; height:40px;">${data.skills_21st || '---'}</td>
                    </tr>
                </table>

                <!-- Tools -->
                <div style="background:#f8fafc; border:1px solid #0f172a; padding:5px 10px; margin-bottom:10px;">
                    <strong style="font-size:11px; color:#0f172a;"><?php _e('المصادر والأدوات:', 'control'); ?></strong>
                    <span style="font-size:10px; margin-right:5px;">${data.equipment || '---'}</span>
                </div>

                <!-- Activities -->
                ${activitiesHtml}

                <!-- Pedagogical Strategy Table -->
                <div style="margin-top:15px;">
                    <h3 style="background:#0f172a; color:#fff; padding:5px 12px; border-radius:4px; font-size:12px; margin-bottom:5px;"><?php _e('الاستراتيجية البيداغوجية والربط', 'control'); ?></h3>
                    <table style="width:100%; border-collapse:collapse; border:1px solid #0f172a;">
                        <tr style="background:#f1f5f9;">
                            <th style="border:1px solid #0f172a; padding:4px; text-align:right; width:33%;"><?php _e('دور المعلم', 'control'); ?></th>
                            <th style="border:1px solid #0f172a; padding:4px; text-align:right; width:33%;"><?php _e('دور الطالب', 'control'); ?></th>
                            <th style="border:1px solid #0f172a; padding:4px; text-align:right; width:33%;"><?php _e('الربط بالواقع', 'control'); ?></th>
                        </tr>
                        <tr>
                            <td style="border:1px solid #0f172a; padding:5px; height:40px; vertical-align:top;">${data.teacher_role || '---'}</td>
                            <td style="border:1px solid #0f172a; padding:5px; height:40px; vertical-align:top;">${data.student_role || '---'}</td>
                            <td style="border:1px solid #0f172a; padding:5px; height:40px; vertical-align:top;">${data.real_life || '---'}</td>
                        </tr>
                        <tr style="background:#f1f5f9;">
                            <th style="border:1px solid #0f172a; padding:4px; text-align:right;"><?php _e('تكامل المواد', 'control'); ?></th>
                            <th style="border:1px solid #0f172a; padding:4px; text-align:right;"><?php _e('مهارات التفكير (HOTS)', 'control'); ?></th>
                            <th style="border:1px solid #0f172a; padding:4px; text-align:right;"><?php _e('التقويم', 'control'); ?></th>
                        </tr>
                        <tr>
                            <td style="border:1px solid #0f172a; padding:5px; height:40px; vertical-align:top;">${data.cross_curricular || '---'}</td>
                            <td style="border:1px solid #0f172a; padding:5px; height:40px; vertical-align:top;">${data.hots || '---'}</td>
                            <td style="border:1px solid #0f172a; padding:5px; height:40px; vertical-align:top;">${data.assessment || '---'}</td>
                        </tr>
                    </table>
                </div>

                <!-- Footer Notes -->
                ${data.notes ? `
                <div style="margin-top:10px; border:1px solid #fde68a; background:#fffbeb; padding:8px;">
                    <strong style="color:#92400e;"><?php _e('ملاحظات المنسق:', 'control'); ?></strong>
                    <p style="margin:2px 0 0 0; color:#92400e; font-style:italic;">${data.notes}</p>
                </div>` : ''}

                <div style="margin-top:20px; text-align:center; font-size:8px; color:#94a3b8; border-top:1px solid #cbd5e1; padding-top:5px;">
                    <?php _e('مستند رسمي صادر عبر منصة كنترول الذكية للإدارة الرياضية المتكاملة - www.control.system', 'control'); ?>
                </div>
            </div>
        `;
    }
});
</script>

<style>
#lesson-wizard-dots .dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.2); transition: 0.3s; }
#lesson-wizard-dots .dot.active { background: var(--control-accent); transform: scale(1.4); box-shadow: 0 0 15px var(--control-accent); }
.lesson-card { transition: 0.3s; cursor: default; }
.lesson-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-color: var(--control-accent); }
.suggestion-chip:hover { border-color: var(--control-accent); color: var(--control-accent); }
.selectable-icon:hover { border-color: var(--control-accent); transform: scale(1.1); }
.template-card:hover { border-color: var(--control-accent) !important; transform: translateY(-2px); }
</style>
