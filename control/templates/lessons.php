<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = Control_Auth::current_user();
$is_admin = Control_Auth::is_admin();
$can_view_all = Control_Auth::has_permission('lessons_view_all');

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

<!-- Suggestions for users -->
<?php if(!empty($suggestions)): ?>
<div class="control-card" style="background:var(--control-accent-soft); border:1px dashed var(--control-accent); padding:15px; margin-bottom:25px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
        <span class="dashicons dashicons-info" style="color:var(--control-accent);"></span>
        <strong style="font-size:0.85rem; color:var(--control-primary);"><?php _e('عناوين مقترحة من الإدارة:', 'control'); ?></strong>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php foreach($suggestions as $s): ?>
            <span class="suggestion-chip" style="background:#fff; padding:5px 12px; border-radius:30px; font-size:0.75rem; border:1px solid var(--control-border); cursor:pointer;" data-topic="<?php echo esc_attr($s->topic); ?>">
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
                <div class="control-card lesson-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
                    <div style="padding:20px; flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                            <span style="background:var(--control-bg); color:var(--control-primary); padding:4px 10px; border-radius:6px; font-size:0.65rem; font-weight:700;">
                                <?php echo esc_html($l->target_group); ?>
                            </span>
                            <span style="color:var(--control-muted); font-size:0.7rem;">
                                <span class="dashicons dashicons-clock" style="font-size:14px; width:14px; height:14px; vertical-align:middle; margin-left:4px;"></span>
                                <?php echo date_i18n('Y/m/d', strtotime($l->created_at)); ?>
                            </span>
                        </div>
                        <h4 style="margin:0 0 10px 0; font-size:1.05rem; font-weight:800; color:var(--control-text-dark);"><?php echo esc_html($l->title); ?></h4>
                        <div style="display:flex; align-items:center; gap:10px; color:var(--control-muted); font-size:0.75rem;">
                            <span><span class="dashicons dashicons-backup" style="font-size:14px; width:14px; height:14px;"></span> <?php echo esc_html($l->duration); ?></span>
                            <?php if($can_view_all && isset($l->first_name)): ?>
                                <span style="margin-right:auto;"><span class="dashicons dashicons-admin-users" style="font-size:14px; width:14px; height:14px;"></span> <?php echo esc_html($l->first_name . ' ' . $l->last_name); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="background:var(--control-bg); padding:12px 20px; border-top:1px solid var(--control-border); display:flex; gap:10px;">
                        <button class="control-btn view-lesson-pdf" data-id="<?php echo $l->id; ?>" style="flex:1; padding:0; height:34px; font-size:0.75rem; background:var(--control-primary);">
                            <span class="dashicons dashicons-pdf" style="margin-left:5px;"></span><?php _e('عرض PDF', 'control'); ?>
                        </button>
                        <button class="control-btn edit-lesson-btn" data-id="<?php echo $l->id; ?>" style="padding:0; width:34px; height:34px; background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><span class="dashicons dashicons-edit"></span></button>
                        <button class="control-btn delete-lesson-btn" data-id="<?php echo $l->id; ?>" style="padding:0; width:34px; height:34px; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2;"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Lesson Wizard Modal -->
<div id="lesson-wizard-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10002; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="control-card" style="width:100%; max-width:900px; height:90vh; padding:0; border-radius:24px; overflow:hidden; display:flex; flex-direction:column; box-shadow: 0 40px 100px rgba(0,0,0,0.3);">

        <div style="background:var(--control-primary); color:#fff; padding:25px 35px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="color:#fff; margin:0; font-size:1.3rem;"><?php _e('معالج تحضير الدروس الذكي', 'control'); ?></h3>
                <div id="wizard-step-indicator" style="opacity:0.7; font-size:0.8rem; margin-top:6px;"></div>
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

                <!-- Step 1: Basic Info -->
                <div class="lesson-step" data-step="1">
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('معلومات الدرس الأساسية', 'control'); ?></h4>
                    <div class="control-form-group">
                        <label><?php _e('عنوان الدرس', 'control'); ?> *</label>
                        <input type="text" id="lesson-title" name="title" required placeholder="<?php _e('مثال: المهارات الأساسية في كرة القدم', 'control'); ?>">
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('المجموعة المستهدفة / الصف', 'control'); ?></label>
                            <input type="text" id="lesson-target" name="target_group" placeholder="<?php _e('مثال: الصف السادس الابتدائي', 'control'); ?>">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('مدة الحصة (دقيقة)', 'control'); ?></label>
                            <input type="text" id="lesson-duration" name="duration" placeholder="<?php _e('مثال: 45 دقيقة', 'control'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Objectives -->
                <div class="lesson-step" data-step="2" style="display:none;">
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('الأهداف التعليمية والأدوات', 'control'); ?></h4>
                    <div class="control-form-group">
                        <label><?php _e('أهداف الدرس (كل هدف في سطر)', 'control'); ?></label>
                        <textarea id="lesson-objectives" name="objectives" rows="5" placeholder="<?php _e('1. أن يتقن الطالب مهارة التمرير...', 'control'); ?>"></textarea>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('الأدوات والتجهيزات المطلوبة', 'control'); ?></label>
                        <input type="text" id="lesson-equipment" name="equipment" placeholder="<?php _e('كرات، أقماع، صافرة، شواخص...', 'control'); ?>">
                    </div>
                </div>

                <!-- Step 3: Activities Flow -->
                <div class="lesson-step" data-step="3" style="display:none;">
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('سير الأنشطة والتمارين', 'control'); ?></h4>

                    <div class="activity-section" style="margin-bottom:30px;">
                        <h5 style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin-bottom:15px; display:flex; align-items:center; gap:10px;">
                            <span style="width:10px; height:10px; background:#10b981; border-radius:50%;"></span><?php _e('1. الإحماء (Warm-up)', 'control'); ?>
                        </h5>
                        <div id="warmup-activities"></div>
                        <button type="button" class="add-activity-btn control-btn" data-container="warmup-activities" style="background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); width:100%; font-size:0.8rem;">
                            <span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة تمرين إحماء', 'control'); ?>
                        </button>
                    </div>

                    <div class="activity-section" style="margin-bottom:30px;">
                        <h5 style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin-bottom:15px; display:flex; align-items:center; gap:10px;">
                            <span style="width:10px; height:10px; background:#3b82f6; border-radius:50%;"></span><?php _e('2. الجزء الرئيسي (Main Activities)', 'control'); ?>
                        </h5>
                        <div id="main-activities"></div>
                        <button type="button" class="add-activity-btn control-btn" data-container="main-activities" style="background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); width:100%; font-size:0.8rem;">
                            <span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة نشاط رئيسي', 'control'); ?>
                        </button>
                    </div>

                    <div class="activity-section">
                        <h5 style="background:var(--control-bg); padding:10px 15px; border-radius:8px; font-weight:800; color:var(--control-primary); margin-bottom:15px; display:flex; align-items:center; gap:10px;">
                            <span style="width:10px; height:10px; background:#6366f1; border-radius:50%;"></span><?php _e('3. الختام والتهدئة (Cooldown)', 'control'); ?>
                        </h5>
                        <div id="cooldown-activities"></div>
                        <button type="button" class="add-activity-btn control-btn" data-container="cooldown-activities" style="background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); width:100%; font-size:0.8rem;">
                            <span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة نشاط ختامي', 'control'); ?>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Assessment -->
                <div class="lesson-step" data-step="4" style="display:none;">
                    <h4 style="margin-bottom:25px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('التقويم والملاحظات الختامية', 'control'); ?></h4>
                    <div class="control-form-group">
                        <label><?php _e('طرق التقويم وأدوات القياس', 'control'); ?></label>
                        <textarea id="lesson-assessment" name="assessment" rows="4" placeholder="<?php _e('ملاحظة الأداء الفني، اختبارات مهارية...', 'control'); ?>"></textarea>
                    </div>
                    <div class="control-form-group">
                        <label><?php _e('ملاحظات إضافية', 'control'); ?></label>
                        <textarea id="lesson-notes" name="notes" rows="4"></textarea>
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
                <button type="button" id="lesson-wizard-save" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; min-width:150px; display:none; font-weight:800;"><?php _e('حفظ وتوليد PDF', 'control'); ?></button>
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
    <div class="control-card" style="width:100%; max-width:500px; padding:30px;">
        <h3><?php _e('إدارة العناوين والمقترحات', 'control'); ?></h3>
        <form id="add-suggestion-form" style="display:flex; gap:10px; margin-bottom:20px;">
            <input type="text" id="new-suggestion-topic" placeholder="<?php _e('أضف عنواناً مقترحاً جديداً...', 'control'); ?>" required style="flex:1;">
            <button type="submit" class="control-btn"><?php _e('إضافة', 'control'); ?></button>
        </form>
        <div id="suggestions-list" style="max-height:300px; overflow-y:auto; background:var(--control-bg); border-radius:12px; padding:15px;">
            <?php foreach($suggestions as $s): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--control-border);">
                    <span style="font-size:0.9rem; font-weight:600;"><?php echo esc_html($s->topic); ?></span>
                    <button class="delete-suggestion-btn" data-id="<?php echo $s->id; ?>" style="background:none; border:none; color:#ef4444; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:20px; text-align:center;">
            <button type="button" onclick="jQuery('#suggestions-modal').hide()" class="control-btn" style="background:none; color:var(--control-text-dark) !important; border:none;"><?php _e('إغلاق', 'control'); ?></button>
        </div>
    </div>
</div>

<!-- Hidden PDF Export Container -->
<div id="pdf-export-content" style="display:none; direction:rtl; text-align:right; font-family:'Rubik', sans-serif; padding:40px; color:#1e293b;">
    <!-- Populated by JS -->
</div>

<script>
jQuery(document).ready(function($) {
    let currentStep = 1;
    let activeIconTarget = null;

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
        $('#lesson-title').val($(this).data('topic'));
        $(this).css('background', 'var(--control-accent)').css('color', '#000');
    });

    // Save and Generate PDF
    $('#lesson-wizard-save').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php _e('جاري المعالجة...', 'control'); ?>');

        const lessonData = {
            title: $('#lesson-title').val(),
            target_group: $('#lesson-target').val(),
            duration: $('#lesson-duration').val(),
            objectives: $('#lesson-objectives').val(),
            equipment: $('#lesson-equipment').val(),
            assessment: $('#lesson-assessment').val(),
            notes: $('#lesson-notes').val(),
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
            if (res.success) {
                generateLessonPDF(lessonData, res.data.id);
            } else {
                alert(res.data);
                $btn.prop('disabled', false).text(originalText);
            }
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

    function generateLessonPDF(data, id) {
        const container = $('#pdf-export-content');
        const orgLogoHtml = '<?php echo $org_logo ? '<img src="'.esc_url($org_logo).'" style="height:60px; margin-bottom:10px;">' : ''; ?>';

        let activitiesHtml = '';
        const sections = [
            { key: 'warmup', label: '<?php _e('الإحماء', 'control'); ?>', color: '#10b981' },
            { key: 'main', label: '<?php _e('الجزء الرئيسي', 'control'); ?>', color: '#3b82f6' },
            { key: 'cooldown', label: '<?php _e('الختام والتهدئة', 'control'); ?>', color: '#6366f1' }
        ];

        sections.forEach(s => {
            if (data.activities[s.key].length > 0) {
                activitiesHtml += `<h3 style="background:${s.color}; color:#fff; padding:10px 15px; border-radius:8px; margin-top:30px;">${s.label}</h3>`;
                activitiesHtml += `<table style="width:100%; border-collapse:collapse; margin-top:15px;">`;
                data.activities[s.key].forEach((act, i) => {
                    activitiesHtml += `
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:15px; width:50px; text-align:center; font-size:24px;">${act.icon}</td>
                            <td style="padding:15px;">
                                <div style="font-weight:800; color:#0f172a; margin-bottom:5px;">${act.title}</div>
                                <div style="font-size:14px; color:#64748b; line-height:1.5;">${act.desc}</div>
                            </td>
                        </tr>
                    `;
                });
                activitiesHtml += `</table>`;
            }
        });

        const html = `
            <div style="border-bottom:3px solid var(--control-primary); padding-bottom:20px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    ${orgLogoHtml}
                    <h1 style="margin:0; font-size:24px; color:var(--control-primary);">${data.title}</h1>
                    <div style="color:var(--control-muted); font-size:14px; margin-top:5px;"><?php echo esc_html($org_name); ?></div>
                </div>
                <div style="text-align:left; font-size:12px; color:var(--control-muted);">
                    <div><?php _e('تاريخ التحضير:', 'control'); ?> ${new Date().toLocaleDateString('ar-SA')}</div>
                    <div><?php _e('المعد:', 'control'); ?> <?php echo esc_html($user->name); ?></div>
                </div>
            </div>

            <table style="width:100%; border-collapse:collapse; margin-bottom:30px;">
                <tr>
                    <td style="width:50%; vertical-align:top; padding-left:20px;">
                        <h4 style="margin:0 0 10px 0; color:var(--control-primary);"><?php _e('المجموعة المستهدفة', 'control'); ?></h4>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; font-weight:700;">${data.target_group || '---'}</div>
                    </td>
                    <td style="width:50%; vertical-align:top;">
                        <h4 style="margin:0 0 10px 0; color:var(--control-primary);"><?php _e('المدة الزمنية', 'control'); ?></h4>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; font-weight:700;">${data.duration || '---'}</div>
                    </td>
                </tr>
            </table>

            <div style="margin-bottom:30px;">
                <h4 style="margin:0 0 10px 0; color:var(--control-primary);"><?php _e('الأهداف التعليمية', 'control'); ?></h4>
                <div style="background:#f8fafc; padding:20px; border-radius:10px; white-space:pre-wrap; line-height:1.6;">${data.objectives || '---'}</div>
            </div>

            <div style="margin-bottom:30px;">
                <h4 style="margin:0 0 10px 0; color:var(--control-primary);"><?php _e('الأدوات والتجهيزات', 'control'); ?></h4>
                <div style="background:#f1f5f9; padding:15px; border-radius:10px; font-weight:600;">${data.equipment || '---'}</div>
            </div>

            ${activitiesHtml}

            <div style="margin-top:40px; padding:25px; background:var(--control-bg); border-radius:15px; border:1px solid #e2e8f0;">
                <h4 style="margin:0 0 10px 0; color:var(--control-primary);"><?php _e('التقويم والملاحظات', 'control'); ?></h4>
                <div style="font-size:14px; color:#334155; line-height:1.6;">${data.assessment || '---'}</div>
                <div style="font-size:14px; color:#334155; line-height:1.6; margin-top:15px; font-style:italic;">${data.notes || ''}</div>
            </div>

            <div style="margin-top:50px; text-align:center; font-size:10px; color:#94a3b8; border-top:1px solid #f1f5f9; padding-top:20px;">
                <?php _e('تم توليد هذا التحضير آلياً عبر نظام كنترول - الإدارة الرياضية المتكاملة', 'control'); ?>
            </div>
        `;

        container.html(html);

        const opt = {
            margin:       [10, 10],
            filename:     `lesson_${id}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(container[0]).save().then(() => {
            alert('<?php _e('تم حفظ الدرس وتوليد ملف PDF بنجاح!', 'control'); ?>');
            location.reload();
        });
    }

    $(document).on('click', '.view-lesson-pdf', function() {
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                generateLessonPDF(res.data.lesson_data, res.data.id);
            }
        });
    });

    $(document).on('click', '.edit-lesson-btn', function() {
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, { action: 'control_get_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) {
                const l = res.data;
                const d = l.lesson_data;
                $('#lesson-id').val(l.id);
                $('#lesson-title').val(d.title);
                $('#lesson-target').val(d.target_group);
                $('#lesson-duration').val(d.duration);
                $('#lesson-objectives').val(d.objectives);
                $('#lesson-equipment').val(d.equipment);
                $('#lesson-assessment').val(d.assessment);
                $('#lesson-notes').val(d.notes);

                // Clear and populate activities
                $('#warmup-activities, #main-activities, #cooldown-activities').empty();
                populateActivities('warmup-activities', d.activities.warmup);
                populateActivities('main-activities', d.activities.main);
                populateActivities('cooldown-activities', d.activities.cooldown);

                showStep(1);
                $('#lesson-wizard-modal').css('display', 'flex');
            }
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

    $(document).on('click', '.delete-lesson-btn', function() {
        if (!confirm('<?php _e('هل أنت متأكد من حذف هذا الدرس نهائياً؟', 'control'); ?>')) return;
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, { action: 'control_delete_lesson', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) location.reload();
        });
    });

    // Admin Suggestions
    $('#manage-suggestions-btn').on('click', function() { $('#suggestions-modal').css('display', 'flex'); });

    $('#add-suggestion-form').on('submit', function(e) {
        e.preventDefault();
        const topic = $('#new-suggestion-topic').val();
        $.post(control_ajax.ajax_url, { action: 'control_save_lesson_suggestion', topic: topic, nonce: control_ajax.nonce }, function(res) {
            if (res.success) location.reload();
        });
    });

    $(document).on('click', '.delete-suggestion-btn', function() {
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, { action: 'control_delete_lesson_suggestion', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) location.reload();
        });
    });
});
</script>

<style>
#lesson-wizard-dots .dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.2); transition: 0.3s; }
#lesson-wizard-dots .dot.active { background: var(--control-accent); transform: scale(1.4); box-shadow: 0 0 15px var(--control-accent); }
.lesson-card { transition: 0.3s; cursor: default; }
.lesson-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-color: var(--control-accent); }
.suggestion-chip:hover { border-color: var(--control-accent); color: var(--control-accent); }
.selectable-icon:hover { border-color: var(--control-accent); transform: scale(1.1); }
</style>
