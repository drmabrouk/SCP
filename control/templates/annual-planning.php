<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = Control_Auth::current_user();
$is_admin = Control_Auth::is_admin();

// Fetch full profile for the current user for PDF headers
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
        $user = $current_user;
    }
}

$org_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_name'") ?: 'Control System';
$org_logo = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'");

$plans = Control_Annual_Planning::get_user_plans( $current_user->id );
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h2 style="font-weight:800; font-size:1.4rem; margin:0; color:var(--control-text-dark);"><?php _e('التخطيط السنوي والفصلي', 'control'); ?></h2>
        <p style="color:var(--control-muted); font-size:0.85rem; margin-top:5px;"><?php _e('بناء الخطط الدراسية السنوية وتوزيع المناهج الرياضية', 'control'); ?></p>
    </div>
    <button id="create-annual-plan-btn" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; font-weight:800;">
        <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إنشاء خطة جديدة', 'control'); ?>
    </button>
</div>

<div id="annual-plans-grid" class="control-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
    <?php if(empty($plans)): ?>
        <div style="grid-column: 1/-1; text-align:center; padding:60px 20px; background:#fff; border-radius:20px; border:1px dashed var(--control-border);">
            <span class="dashicons dashicons-calendar-alt" style="font-size:50px; width:50px; height:50px; color:var(--control-muted); margin-bottom:20px;"></span>
            <h3 style="color:var(--control-text-dark);"><?php _e('لا توجد خطط سنوية حالياً', 'control'); ?></h3>
            <p style="color:var(--control-muted);"><?php _e('ابدأ بتنظيم عامك الدراسي من خلال إنشاء أول خطة فصلية أو سنوية.', 'control'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach($plans as $p): ?>
            <div class="control-card plan-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; border-radius:16px;">
                <div style="padding:24px; flex:1;">
                    <div style="display:flex; gap:8px; margin-bottom:15px;">
                        <span class="meta-capsule" style="background:var(--control-primary); color:#fff; padding:4px 10px; border-radius:20px; font-size:0.65rem; font-weight:700;">
                            <?php
                                $types = [
                                    'term_1' => __('الفصل الأول', 'control'),
                                    'term_2' => __('الفصل الثاني', 'control'),
                                    'term_3' => __('الفصل الثالث', 'control'),
                                    'annual' => __('خطة سنوية', 'control')
                                ];
                                echo $types[$p->plan_type] ?? $p->plan_type;
                            ?>
                        </span>
                        <span class="meta-capsule" style="background:rgba(0,0,0,0.05); color:var(--control-muted); padding:4px 10px; border-radius:20px; font-size:0.65rem; font-weight:700; border:1px solid var(--control-border);">
                            <?php echo $p->academic_system === 'three_semesters' ? __('نظام 3 فصول', 'control') : __('نظام فصلين', 'control'); ?>
                        </span>
                    </div>
                    <h4 style="margin:0 0 12px; font-size:1.15rem; font-weight:800; color:var(--control-text-dark);"><?php echo esc_html($p->plan_name); ?></h4>
                    <div style="font-size:0.8rem; color:var(--control-muted); display:flex; flex-direction:column; gap:6px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size:16px; width:16px; height:16px;"></span>
                            <?php echo $p->start_date; ?> - <?php echo $p->end_date; ?>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="dashicons dashicons-clock" style="font-size:16px; width:16px; height:16px;"></span>
                            <?php echo sprintf(__('%d حصة/أسبوع - يوم %s', 'control'), $p->weekly_frequency, $p->lesson_day); ?>
                        </div>
                    </div>
                </div>
                <div style="background:var(--control-bg); padding:12px 20px; border-top:1px solid var(--control-border); display:flex; gap:8px;">
                    <button class="control-btn view-plan-btn" data-id="<?php echo $p->id; ?>" style="flex:1; height:34px; font-size:0.75rem; background:var(--control-primary); border:none;">
                        <span class="dashicons dashicons-visibility" style="margin-left:5px;"></span><?php _e('عرض وتعديل', 'control'); ?>
                    </button>
                    <button class="control-btn download-plan-pdf" data-id="<?php echo $p->id; ?>" style="width:34px; height:34px; padding:0; background:var(--control-accent); color:var(--control-primary) !important; border:none; border-radius: 6px;" title="<?php _e('تحميل PDF', 'control'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <button class="control-btn delete-plan-btn" data-id="<?php echo $p->id; ?>" style="width:34px; height:34px; padding:0; background:#fef2f2; color:#ef4444 !important; border:1px solid #fee2e2;">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Plan Setup Wizard Modal -->
<div id="plan-wizard-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10005; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="control-card" style="width:100%; max-width:800px; max-height:90vh; padding:0; border-radius:24px; overflow:hidden; display:flex; flex-direction:column; box-shadow: 0 40px 100px rgba(0,0,0,0.3);">

        <div style="background:var(--control-primary); color:#fff; padding:20px 35px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:20px;">
                <div>
                    <h3 id="plan-wizard-main-title" style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('إعداد الخطة الدراسية', 'control'); ?></h3>
                    <div id="plan-wizard-step-label" style="opacity:0.7; font-size:0.75rem; margin-top:4px;"><?php _e('تكوين النظام الأكاديمي', 'control'); ?></div>
                </div>
                <div class="lang-selector-wizard" style="display:flex; background:rgba(255,255,255,0.15); padding:3px; border-radius:10px; border:1px solid rgba(255,255,255,0.2);">
                    <button type="button" class="plan-lang-btn active" data-lang="ar" style="border:none; background:none; color:#fff; padding:4px 10px; cursor:pointer; font-size:0.75rem; font-weight:700; border-radius:6px;">العربية</button>
                    <button type="button" class="plan-lang-btn" data-lang="en" style="border:none; background:none; color:#fff; padding:4px 10px; cursor:pointer; font-size:0.75rem; font-weight:700; border-radius:6px;">English</button>
                    <input type="hidden" id="plan-lang" value="ar">
                </div>
            </div>
            <button type="button" class="close-plan-modal" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>

        <div style="flex:1; overflow-y:auto; padding:35px;" id="plan-wizard-content">
            <form id="annual-plan-form">
                <input type="hidden" name="id" id="plan-id" value="0">

                <!-- Step 1: System Config -->
                <div class="plan-step" data-step="1">
                    <h4 style="margin-bottom:20px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('1. هيكل النظام الأكاديمي', 'control'); ?></h4>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                        <div class="config-card system-option" data-value="two_semesters" style="padding:25px; border:2px solid var(--control-border); border-radius:16px; cursor:pointer; text-align:center; transition:0.3s;">
                            <span class="dashicons dashicons-columns" style="font-size:40px; width:40px; height:40px; color:var(--control-muted); margin-bottom:15px;"></span>
                            <h5 style="margin:0;"><?php _e('نظام الفصلين', 'control'); ?></h5>
                            <p style="font-size:0.75rem; color:var(--control-muted); margin-top:8px;"><?php _e('تقسيم السنة الدراسية إلى فصلين دراسيين كبيرين.', 'control'); ?></p>
                        </div>
                        <div class="config-card system-option" data-value="three_semesters" style="padding:25px; border:2px solid var(--control-border); border-radius:16px; cursor:pointer; text-align:center; transition:0.3s;">
                            <span class="dashicons dashicons-grid-view" style="font-size:40px; width:40px; height:40px; color:var(--control-muted); margin-bottom:15px;"></span>
                            <h5 style="margin:0;"><?php _e('نظام الثلاث فصول', 'control'); ?></h5>
                            <p style="font-size:0.75rem; color:var(--control-muted); margin-top:8px;"><?php _e('نظام الفصول الثلاثة المتبع في العديد من المناهج الحديثة.', 'control'); ?></p>
                        </div>
                        <input type="hidden" name="academic_system" id="academic-system-input" value="three_semesters">
                    </div>
                </div>

                <!-- Step 2: Plan Metadata -->
                <div class="plan-step" data-step="2" style="display:none;">
                    <h4 style="margin-bottom:20px; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('2. تفاصيل الفترة والجدول', 'control'); ?></h4>
                    <div class="control-form-group">
                        <label><?php _e('اسم الخطة (مثلاً: الفصل الدراسي الأول - الصف السادس)', 'control'); ?> *</label>
                        <input type="text" name="plan_name" id="plan-name" required>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                        <div class="control-form-group">
                            <label><?php _e('نوع الخطة', 'control'); ?></label>
                            <select name="plan_type" id="plan-type">
                                <option value="term_1"><?php _e('الفصل الأول', 'control'); ?></option>
                                <option value="term_2"><?php _e('الفصل الثاني', 'control'); ?></option>
                                <option value="term_3" class="three-only"><?php _e('الفصل الثالث', 'control'); ?></option>
                                <option value="annual_summary"><?php _e('ملخص الخطة السنوية', 'control'); ?></option>
                            </select>
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('عدد الحصص أسبوعياً', 'control'); ?></label>
                            <input type="number" name="weekly_frequency" id="weekly-frequency" value="1" min="1" max="5">
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('يوم الحصة الأساسي', 'control'); ?></label>
                            <select name="lesson_day" id="lesson-day">
                                <option value="الأحد"><?php _e('الأحد', 'control'); ?></option>
                                <option value="الإثنين"><?php _e('الإثنين', 'control'); ?></option>
                                <option value="الثلاثاء"><?php _e('الثلاثاء', 'control'); ?></option>
                                <option value="الأربعاء"><?php _e('الأربعاء', 'control'); ?></option>
                                <option value="الخميس"><?php _e('الخميس', 'control'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="control-form-group">
                            <label><?php _e('تاريخ البداية', 'control'); ?></label>
                            <input type="date" name="start_date" id="plan-start-date" required>
                        </div>
                        <div class="control-form-group">
                            <label><?php _e('تاريخ النهاية', 'control'); ?></label>
                            <input type="date" name="end_date" id="plan-end-date" required>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Lesson Distribution -->
                <div class="plan-step" data-step="3" style="display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h4 style="margin:0; color:var(--control-primary); border-right:4px solid var(--control-accent); padding-right:15px;"><?php _e('3. توزيع المحتوى الزمني', 'control'); ?></h4>
                        <div id="weeks-counter" style="background:var(--control-bg); padding:5px 15px; border-radius:8px; font-size:0.8rem; font-weight:700; color:var(--control-primary);"></div>
                    </div>
                    <div id="plan-grid-container" style="display:grid; gap:10px;">
                        <!-- Generated by JS -->
                    </div>
                </div>
            </form>
        </div>

        <div id="plan-delete-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:11000; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="control-card" style="width:100%; max-width:400px; padding:30px; border-radius:20px; text-align:center;">
                <div style="width:70px; height:70px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; color:#ef4444;">
                    <span class="dashicons dashicons-trash" style="font-size:35px; width:35px; height:35px;"></span>
                </div>
                <h3 id="delete-modal-title" style="margin-bottom:10px; color:var(--control-text-dark);"><?php _e('هل أنت متأكد؟', 'control'); ?></h3>
                <p id="delete-modal-desc" style="color:var(--control-muted); font-size:0.9rem; margin-bottom:25px;"><?php _e('سيتم حذف هذه الخطة نهائياً من النظام.', 'control'); ?></p>
                <div style="display:flex; gap:10px;">
                    <button type="button" id="confirm-plan-delete-btn" class="control-btn" style="flex:1; background:#ef4444; border:none;"><?php _e('نعم، احذف', 'control'); ?></button>
                    <button type="button" onclick="jQuery('#plan-delete-modal').hide()" class="control-btn" style="flex:1; background:var(--control-bg); color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><?php _e('إلغاء', 'control'); ?></button>
                </div>
            </div>
        </div>

        <div style="padding:20px 35px; background:var(--control-bg); border-top:1px solid var(--control-border); display:flex; justify-content:space-between; align-items:center;">
            <button type="button" id="plan-wizard-prev" class="control-btn" style="background:#fff; color:var(--control-text-dark) !important; border:1px solid var(--control-border); display:none;"><?php _e('السابق', 'control'); ?></button>
            <div style="flex:1;"></div>
            <div style="display:flex; gap:10px;">
                <button type="button" id="plan-wizard-next" class="control-btn" style="background:var(--control-primary); border:none; min-width:120px;"><?php _e('التالي', 'control'); ?></button>
                <button type="button" id="plan-wizard-save" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; min-width:150px; display:none; font-weight:800;"><?php _e('حفظ الخطة النهائية', 'control'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const planTranslations = {
        ar: {
            setup_title: 'إعداد الخطة الدراسية',
            step_1_label: 'تكوين النظام الأكاديمي',
            step_2_label: 'تفاصيل الفترة والجدول',
            step_3_label: 'توزيع المحتوى الزمني',
            system_structure: '1. هيكل النظام الأكاديمي',
            two_semesters: 'نظام الفصلين',
            two_semesters_desc: 'تقسيم السنة الدراسية إلى فصلين دراسيين كبيرين.',
            three_semesters: 'نظام الثلاث فصول',
            three_semesters_desc: 'نظام الفصول الثلاثة المتبع في العديد من المناهج الحديثة.',
            period_details: '2. تفاصيل الفترة والجدول',
            plan_name_label: 'اسم الخطة (مثلاً: الفصل الدراسي الأول - الصف السادس) *',
            plan_type_label: 'نوع الخطة',
            term_1: 'الفصل الأول',
            term_2: 'الفصل الثاني',
            term_3: 'الفصل الثالث',
            annual_full: 'خطة سنوية كاملة',
            frequency_label: 'عدد الحصص أسبوعياً',
            lesson_day_label: 'يوم الحصة الأساسي',
            sunday: 'الأحد', monday: 'الإثنين', tuesday: 'الثلاثاء', wednesday: 'الأربعاء', thursday: 'الخميس',
            start_date_label: 'تاريخ البداية',
            end_date_label: 'تاريخ النهاية',
            content_dist: '3. توزيع المحتوى الزمني',
            available_weeks: 'أسبوعاً دراسياً متاحاً',
            week: 'الأسبوع',
            prepared_by: 'المعد:',
            start_date_label: 'تاريخ البداية',
            frequency_label: 'التكرار الأسبوعي',
            scheduled_lessons: 'حصص مقررة',
            lesson_topic: 'موضوع الدرس...',
            lesson_type: 'النوع',
            lesson_duration: 'المدة',
            prev: 'السابق',
            next: 'التالي',
            save_plan: 'حفظ الخطة النهائية',
            processing: 'جاري الحفظ...',
            confirm_title: 'هل أنت متأكد؟',
            confirm_desc: 'سيتم حذف هذه الخطة نهائياً من النظام.',
            confirm_yes: 'نعم، احذف',
            confirm_no: 'إلغاء'
        },
        en: {
            setup_title: 'Plan Setup Wizard',
            step_1_label: 'Academic System Config',
            step_2_label: 'Period & Schedule Details',
            step_3_label: 'Content Distribution',
            system_structure: '1. Academic System Structure',
            two_semesters: 'Two Semesters System',
            two_semesters_desc: 'Divide academic year into two major terms.',
            three_semesters: 'Three Semesters System',
            three_semesters_desc: 'Three-term system used in modern curricula.',
            period_details: '2. Period & Schedule Details',
            plan_name_label: 'Plan Name (e.g., Term 1 - Grade 6) *',
            plan_type_label: 'Plan Type',
            term_1: 'Term 1',
            term_2: 'Term 2',
            term_3: 'Term 3',
            annual_full: 'Full Annual Plan',
            frequency_label: 'Lessons per Week',
            lesson_day_label: 'Primary Lesson Day',
            sunday: 'Sunday', monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday', thursday: 'Thursday',
            start_date_label: 'Start Date',
            end_date_label: 'End Date',
            content_dist: '3. Time-based Content Distribution',
            available_weeks: 'teaching weeks available',
            week: 'Week',
            scheduled_lessons: 'scheduled lessons',
            lesson_topic: 'Lesson Topic...',
            lesson_type: 'Type',
            lesson_duration: 'Duration',
            prev: 'Previous',
            next: 'Next',
            save_plan: 'Save Final Plan',
            processing: 'Saving...',
            confirm_title: 'Are you sure?',
            confirm_desc: 'This plan will be permanently deleted.',
            confirm_yes: 'Yes, Delete',
            confirm_no: 'Cancel',
            prepared_by: 'Prepared by:',
            start_date_label: 'Start Date',
            frequency_label: 'Weekly Frequency'
        }
    };

    const annualPlanAssistant = {
        getExistingPlans: function() {
            // In a real scenario, we might want to fetch the latest via AJAX
            // but for now we'll use the PHP-injected plans (refreshed on reload)
            return <?php echo json_encode($plans); ?>;
        },
        compile: function() {
            const system = $('#academic-system-input').val();
            const terms = ['term_1', 'term_2'];
            if (system === 'three_semesters') terms.push('term_3');

            let allSlots = [];
            let missingTerms = [];

            const existingPlans = this.getExistingPlans();
            terms.forEach(t => {
                const plan = existingPlans.find(p => p.plan_type === t);
                if (plan) {
                    const data = typeof plan.plan_data === 'string' ? JSON.parse(plan.plan_data) : plan.plan_data;
                    allSlots = allSlots.concat(data.map(s => ({...s, term: t})));
                } else {
                    missingTerms.push(t);
                }
            });

            if (missingTerms.length > 0) {
                const lang = $('#plan-lang').val();
                const termLabels = { term_1: 'الفصل 1', term_2: 'الفصل 2', term_3: 'الفصل 3' };
                const msg = lang === 'ar' ?
                    `تنبيه: لم يتم العثور على خطط لـ (${missingTerms.map(t => termLabels[t]).join(', ')}). سيتم إنشاء ملخص جزئي فقط.` :
                    `Notice: Missing plans for (${missingTerms.join(', ')}). Only partial summary will be created.`;
                alert(msg);
            }
            return allSlots;
        }
    };

    let currentStep = 1;
    let planData = [];

    function updatePlanAssistantUI() {
        const lang = $('#plan-lang').val();
        const system = $('#academic-system-input').val();
        const type = $('#plan-type').val();
        const terms = ['term_1', 'term_2'];
        if (system === 'three_semesters') terms.push('term_3');

        if (type === 'annual_summary') {
            let html = `<div style="background:var(--control-bg); padding:20px; border-radius:15px; border:1px dashed var(--control-accent); margin-bottom:20px;">
                <h5 style="margin:0 0 15px; color:var(--control-primary); font-weight:800;">${lang === 'ar' ? 'حالة الخطط الفصلية' : 'Semester Plans Status'}</h5>
                <div style="display:flex; flex-direction:column; gap:10px;">`;

            const existingPlans = annualPlanAssistant.getExistingPlans();
            const termLabels = {
                term_1: lang === 'ar' ? 'الفصل الدراسي الأول' : 'First Semester',
                term_2: lang === 'ar' ? 'الفصل الدراسي الثاني' : 'Second Semester',
                term_3: lang === 'ar' ? 'الفصل الدراسي الثالث' : 'Third Semester'
            };

            terms.forEach(t => {
                const plan = existingPlans.find(p => p.plan_type === t);
                const statusIcon = plan ? 'dashicons-yes-alt' : 'dashicons-warning';
                const statusColor = plan ? '#10b981' : '#f59e0b';
                const statusText = plan ? (lang === 'ar' ? 'جاهز' : 'Ready') : (lang === 'ar' ? 'مفقود' : 'Missing');

                html += `
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; padding:10px 15px; border-radius:8px; border:1px solid var(--control-border);">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="dashicons ${statusIcon}" style="color:${statusColor};"></span>
                            <span style="font-weight:700; font-size:0.8rem;">${termLabels[t]}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:0.7rem; color:${statusColor}; font-weight:700;">${statusText}</span>
                            ${!plan ? `<button type="button" class="create-missing-term control-btn" data-type="${t}" style="height:24px; padding:0 8px; font-size:0.6rem; background:var(--control-accent); color:var(--control-primary) !important; border:none; border-radius:4px;">${lang === 'ar' ? 'إنشاء الآن' : 'Create Now'}</button>` : ''}
                        </div>
                    </div>
                `;
            });

            html += `</div></div>`;
            $('#plan-grid-container').html(html);
            $('#weeks-counter').hide();
        } else {
            $('#weeks-counter').show();
            generatePlanGrid();
        }
    }

    function updatePlanWizardLabels() {
        const lang = $('#plan-lang').val();
        const trans = planTranslations[lang];
        const isRtl = lang === 'ar';

        $('#plan-wizard-main-title').text(trans.setup_title);
        $('.plan-step[data-step="1"] h4').text(trans.system_structure);
        $('.system-option[data-value="two_semesters"] h5').text(trans.two_semesters);
        $('.system-option[data-value="two_semesters"] p').text(trans.two_semesters_desc);
        $('.system-option[data-value="three_semesters"] h5').text(trans.three_semesters);
        $('.system-option[data-value="three_semesters"] p').text(trans.three_semesters_desc);

        $('.plan-step[data-step="2"] h4').text(trans.period_details);
        $('label[for="plan-name"]').text(trans.plan_name_label);
        $('label[for="plan-type"]').text(trans.plan_type_label);
        $('#plan-type option[value="term_1"]').text(trans.term_1);
        $('#plan-type option[value="term_2"]').text(trans.term_2);
        $('#plan-type option[value="term_3"]').text(trans.term_3);
        $('#plan-type option[value="annual"]').text(trans.annual_full);
        $('label[for="weekly-frequency"]').text(trans.frequency_label);
        $('label[for="lesson-day"]').text(trans.lesson_day_label);

        $('#lesson-day option:eq(0)').text(trans.sunday);
        $('#lesson-day option:eq(1)').text(trans.monday);
        $('#lesson-day option:eq(2)').text(trans.tuesday);
        $('#lesson-day option:eq(3)').text(trans.wednesday);
        $('#lesson-day option:eq(4)').text(trans.thursday);

        $('label[for="plan-start-date"]').text(trans.start_date_label);
        $('label[for="plan-end-date"]').text(trans.end_date_label);

        $('.plan-step[data-step="3"] h4').text(trans.content_dist);

        $('#plan-wizard-prev').text(trans.prev);
        $('#plan-wizard-next').text(trans.next);
        $('#plan-wizard-save').text(trans.save_plan);

        $('#delete-modal-title').text(trans.confirm_title);
        $('#delete-modal-desc').text(trans.confirm_desc);
        $('#confirm-plan-delete-btn').text(trans.confirm_yes);
        $('#plan-delete-modal .control-btn:last').text(trans.confirm_no);

        showStep(currentStep);
    }

    $(document).on('click', '.plan-lang-btn', function() {
        $('.plan-lang-btn').removeClass('active');
        $(this).addClass('active');
        $('#plan-lang').val($(this).data('lang'));
        updatePlanWizardLabels();
        if (currentStep === 3) updatePlanAssistantUI();
    });

    function showStep(step) {
        $('.plan-step').hide();
        $(`.plan-step[data-step="${step}"]`).fadeIn(300);

        $('#plan-wizard-prev').toggle(step > 1);
        $('#plan-wizard-next').toggle(step < 3);
        $('#plan-wizard-save').toggle(step === 3);

        const labels = {
            1: '<?php _e('تكوين النظام الأكاديمي', 'control'); ?>',
            2: '<?php _e('تفاصيل الفترة والجدول', 'control'); ?>',
            3: '<?php _e('توزيع المحتوى الزمني', 'control'); ?>'
        };
        $('#plan-wizard-step-label').text(labels[step]);
        currentStep = step;

        if (step === 3) updatePlanAssistantUI();
    }

    $('.system-option').on('click', function() {
        $('.system-option').css('border-color', 'var(--control-border)').css('background', '#fff');
        $(this).css('border-color', 'var(--control-accent)').css('background', 'rgba(212,175,55,0.05)');
        const val = $(this).data('value');
        $('#academic-system-input').val(val);
        $('.three-only').toggle(val === 'three_semesters');
    });

    $('#create-annual-plan-btn').on('click', function() {
        $('#annual-plan-form')[0].reset();
        $('#plan-id').val('0');
        $('.system-option[data-value="three_semesters"]').trigger('click');
        showStep(1);
        $('#plan-wizard-modal').css('display', 'flex');
    });

    $('.close-plan-modal').on('click', function() { $('#plan-wizard-modal').hide(); });

    $(document).on('click', '.create-missing-term', function() {
        const type = $(this).data('type');
        $('#plan-id').val('0');
        $('#plan-type').val(type).trigger('change');
        showStep(2);
    });

    $('#plan-wizard-next').on('click', function() {
        if (currentStep === 2) {
            if (!$('#plan-name').val() || !$('#plan-start-date').val() || !$('#plan-end-date').val()) {
                alert('<?php _e('يرجى إكمال كافة البيانات المطلوبة', 'control'); ?>');
                return;
            }
        }
        showStep(currentStep + 1);
    });

    $('#plan-wizard-prev').on('click', function() { showStep(currentStep - 1); });

    function generatePlanGrid() {
        const startInput = $('#plan-start-date').val();
        const endInput = $('#plan-end-date').val();
        const selectedDay = $('#lesson-day').val();
        const freq = parseInt($('#weekly-frequency').val());

        if (!startInput || !endInput) return;

        const start = new Date(startInput);
        const end = new Date(endInput);
        const lang = $('#plan-lang').val();
        const trans = planTranslations[lang];

        // Day mapping for calculation
        const dayMap = { 'الأحد': 0, 'الإثنين': 1, 'الثلاثاء': 2, 'الأربعاء': 3, 'الخميس': 4, 'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4 };
        const targetDay = dayMap[selectedDay] ?? 1;

        const diffWeeks = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24 * 7));
        $('#weeks-counter').text(`${diffWeeks} ${trans.available_weeks}`);

        let html = '';
        let currentLessonDate = new Date(start);
        // Adjust to first occurrence of the selected day
        while (currentLessonDate.getDay() !== targetDay && currentLessonDate <= end) {
            currentLessonDate.setDate(currentLessonDate.getDate() + 1);
        }

        for (let i = 1; i <= diffWeeks; i++) {
            const formattedDate = currentLessonDate > end ? '---' : currentLessonDate.toLocaleDateString(lang === 'ar' ? 'ar-SA' : 'en-US', { day: 'numeric', month: 'long', year: 'numeric' });
            const rawDate = currentLessonDate > end ? '' : currentLessonDate.toISOString().split('T')[0];

            html += `
                <div class="week-row" style="background:#f8fafc; border:1px solid var(--control-border); padding:15px; border-radius:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <strong style="color:var(--control-primary); font-size:0.85rem;">${trans.week} ${i}</strong>
                            <span style="font-size:0.75rem; background:var(--control-accent-soft); color:var(--control-primary); padding:2px 10px; border-radius:20px; font-weight:700;">${formattedDate}</span>
                        </div>
                        <span style="font-size:0.7rem; color:var(--control-muted);">${freq} ${trans.scheduled_lessons}</span>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(${freq}, 1fr); gap:10px;">
            `;
            for (let j = 1; j <= freq; j++) {
                const slotId = `week-${i}-lesson-${j}`;
                const existing = planData.find(d => d.slotId === slotId);
                html += `
                    <div class="lesson-slot-box" data-date="${rawDate}" style="background:#fff; border:1px solid #e2e8f0; padding:12px; border-radius:10px; transition: 0.2s;">
                        <input type="text" class="lesson-slot-title" data-slot="${slotId}" placeholder="${trans.lesson_topic}" value="${existing ? existing.title : ''}" style="border:none; background:none; padding:0; font-size:0.85rem; font-weight:800; margin-bottom:8px; width:100%; color:var(--control-text-dark);">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" class="lesson-slot-type" data-slot="${slotId}" placeholder="${trans.lesson_type}" value="${existing ? existing.type : ''}" style="border:none; background:none; padding:0; font-size:0.7rem; color:var(--control-muted); flex:2;">
                            <input type="text" class="lesson-slot-duration" data-slot="${slotId}" placeholder="${trans.lesson_duration}" value="${existing ? (existing.duration || '45 min') : '45 min'}" style="border:none; background:none; padding:0; font-size:0.7rem; color:var(--control-muted); flex:1; text-align:${lang === 'ar' ? 'left' : 'right'};">
                        </div>
                        <div style="margin-top:10px; border-top:1px solid #f1f5f9; padding-top:10px; display:flex; justify-content:flex-end;">
                            <button type="button" class="create-detailed-lesson" data-slot="${slotId}" title="${lang === 'ar' ? 'تحضير درس مفصل' : 'Create Detailed Plan'}" style="background:none; border:none; color:var(--control-accent); cursor:pointer; padding:0;">
                                <span class="dashicons dashicons-welcome-add-page" style="font-size:18px; width:18px; height:18px;"></span>
                            </button>
                        </div>
                    </div>
                `;
            }
            html += `</div></div>`;
            currentLessonDate.setDate(currentLessonDate.getDate() + 7);
        }
        $('#plan-grid-container').html(html);
    }

    $('#plan-wizard-save').on('click', function() {
        const type = $('#plan-type').val();
        let slots = [];

        if (type === 'annual_summary') {
            slots = annualPlanAssistant.compile();
            if (slots.length === 0) {
                alert('No term data found to compile.');
                return;
            }
        } else {
            $('.lesson-slot-title').each(function() {
                const slotId = $(this).data('slot');
                slots.push({
                    slotId: slotId,
                    title: $(this).val(),
                    type: $(`.lesson-slot-type[data-slot="${slotId}"]`).val(),
                    duration: $(`.lesson-slot-duration[data-slot="${slotId}"]`).val(),
                    date: $(this).closest('.lesson-slot-box').data('date')
                });
            });
        }

        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('جاري الحفظ...', 'control'); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_save_annual_plan',
            id: $('#plan-id').val(),
            plan_name: $('#plan-name').val(),
            academic_system: $('#academic-system-input').val(),
            plan_type: $('#plan-type').val(),
            start_date: $('#plan-start-date').val(),
            end_date: $('#plan-end-date').val(),
            weekly_frequency: $('#weekly-frequency').val(),
            lesson_day: $('#lesson-day').val(),
            lang: $('#plan-lang').val(),
            plan_data: JSON.stringify(slots),
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) location.reload();
            else {
                alert(res.data || 'Error saving plan');
                $btn.prop('disabled', false).text(trans.save_plan);
            }
        }).fail(function() {
            alert('Server Error');
            $btn.prop('disabled', false).text(trans.save_plan);
        });
    });

    $(document).on('click', '.view-plan-btn', function() {
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, {
            action: 'control_get_annual_plan',
            id: id,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) {
                const p = res.data;
                $('#plan-id').val(p.id);
                $('#plan-name').val(p.plan_name);
                $('#plan-lang').val(p.lang || 'ar');
                $(`.plan-lang-btn[data-lang="${p.lang || 'ar'}"]`).trigger('click');
                $('#academic-system-input').val(p.academic_system);
                $(`.system-option[data-value="${p.academic_system}"]`).trigger('click');
                $('#plan-type').val(p.plan_type);
                $('#weekly-frequency').val(p.weekly_frequency);
                $('#lesson-day').val(p.lesson_day);
                $('#plan-start-date').val(p.start_date);
                $('#plan-end-date').val(p.end_date);
                planData = p.plan_data;
                showStep(2);
                $('#plan-wizard-modal').css('display', 'flex');
            }
        });
    });

    let planIdToDelete = null;
    $(document).on('click', '.delete-plan-btn', function() {
        planIdToDelete = $(this).data('id');
        $('#plan-delete-modal').css('display', 'flex');
    });

    $('#confirm-plan-delete-btn').on('click', function() {
        if (!planIdToDelete) return;
        const $btn = $(this);
        $btn.prop('disabled', true);
        $.post(control_ajax.ajax_url, { action: 'control_delete_annual_plan', id: planIdToDelete, nonce: control_ajax.nonce }, function(res) {
            if (res.success) location.reload();
        });
    });

    $(document).on('click', '.create-detailed-lesson', function() {
        const slotId = $(this).data('slot');
        const box = $(this).closest('.lesson-slot-box');
        const title = box.find('.lesson-slot-title').val();
        const date = box.data('date');

        if (!title) {
            alert('<?php _e('يرجى إدخال عنوان للدرس أولاً', 'control'); ?>');
            return;
        }

        // Redirect to Lessons view with pre-filled parameters
        const url = new URL(window.location.href);
        url.searchParams.set('control_view', 'lessons');
        url.searchParams.set('prefill_title', title);
        url.searchParams.set('prefill_date', date);
        url.searchParams.set('prefill_lang', $('#plan-lang').val());
        window.location.href = url.toString();
    });

    $(document).on('click', '.download-plan-pdf', function() {
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.post(control_ajax.ajax_url, {
            action: 'control_get_annual_plan',
            id: id,
            nonce: control_ajax.nonce
        }, function(res) {
            $btn.prop('disabled', false);
            if (res.success) {
                generatePlanPDF(res.data);
            }
        });
    });

    function generatePlanPDF(plan) {
        const lang = plan.lang || 'ar';
        const isRtl = lang === 'ar';
        const trans = planTranslations[lang];
        const $container = $(`<div style="padding:15mm; background:#fff; font-family:'Rubik', sans-serif; direction:${isRtl ? 'rtl' : 'ltr'}; text-align:${isRtl ? 'right' : 'left'}; color:#000;"></div>`);

        const types = { term_1: trans.term_1, term_2: trans.term_2, term_3: trans.term_3, annual: trans.annual_full };
        const system = plan.academic_system === 'three_semesters' ? trans.three_semesters : trans.two_semesters;

        const creator = {
            name: '<?php echo esc_js($user->name); ?>',
            job_title: '<?php echo esc_js($user->job_title); ?>',
            org: '<?php echo esc_js($org_name); ?>',
            logo: '<?php echo esc_js($org_logo); ?>'
        };

        let html = `
            <div style="border-bottom:2.5px solid #000; padding-bottom:20px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-start;">
                <div style="flex:1;">
                    ${creator.logo ? `<img src="${creator.logo}" style="height:60px; margin-bottom:10px; object-fit:contain;">` : ''}
                    <h1 style="margin:0; font-size:24px; font-weight:800;">${plan.plan_name}</h1>
                    <div style="font-size:13px; color:#444; margin-top:8px; font-weight:600;">${types[plan.plan_type]} | ${system}</div>
                </div>
                <div style="width:280px; font-size:11px; line-height:1.6; border-${isRtl ? 'right' : 'left'}:1px solid #ddd; padding-${isRtl ? 'right' : 'left'}:15px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr><td style="font-weight:800; width:80px;">${trans.prepared_by}</td><td>${creator.name}</td></tr>
                        <tr><td style="font-weight:800;">${lang === 'ar' ? 'المسمى:' : 'Title:'}</td><td>${creator.job_title}</td></tr>
                        <tr><td style="font-weight:800;">${lang === 'ar' ? 'المؤسسة:' : 'Institution:'}</td><td>${creator.org}</td></tr>
                        <tr><td style="font-weight:800;">${trans.start_date_label}:</td><td>${plan.start_date}</td></tr>
                        <tr><td style="font-weight:800;">${trans.frequency_label}:</td><td>${plan.weekly_frequency}</td></tr>
                    </table>
                </div>
            </div>

            <table style="width:100%; border-collapse:collapse; border:1.5px solid #000; table-layout: fixed;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="border:1.5px solid #000; padding:12px; width:120px; font-size:12px; font-weight:800;">${trans.week} / ${lang === 'ar' ? 'التاريخ' : 'Date'}</th>
                        <th style="border:1.5px solid #000; padding:12px; font-size:12px; font-weight:800;">${trans.content_dist}</th>
                    </tr>
                </thead>
                <tbody>
        `;

        // Group slots by term and week
        const terms = {};
        plan.plan_data.forEach(slot => {
            const term = slot.term || plan.plan_type;
            if (!terms[term]) terms[term] = {};
            const weekNum = slot.slotId.split('-')[1];
            if (!terms[term][weekNum]) terms[term][weekNum] = [];
            terms[term][weekNum].push(slot);
        });

        const termLabels = {
            term_1: lang === 'ar' ? 'الفصل الدراسي الأول' : 'First Semester',
            term_2: lang === 'ar' ? 'الفصل الدراسي الثاني' : 'Second Semester',
            term_3: lang === 'ar' ? 'الفصل الدراسي الثالث' : 'Third Semester',
            annual_summary: trans.annual_full
        };

        Object.keys(terms).forEach(termKey => {
            if (plan.plan_type === 'annual_summary') {
                html += `<tr><td colspan="2" style="background:var(--control-primary); color:#fff; padding:8px 15px; font-weight:800; font-size:14px; text-align:center;">${termLabels[termKey]}</td></tr>`;
            }

            const weeks = terms[termKey];
            Object.keys(weeks).forEach(weekNum => {
            const firstSlot = weeks[weekNum][0];
            const dateStr = firstSlot.date ? new Date(firstSlot.date).toLocaleDateString(lang === 'ar' ? 'ar-SA' : 'en-US', {day:'numeric', month:'short'}) : '---';

            html += `
                <tr style="page-break-inside: avoid;">
                    <td style="border:1px solid #000; padding:12px; text-align:center; background:#f8fafc;">
                        <div style="font-weight:800; font-size:14px; color:var(--control-primary);">${trans.week} ${weekNum}</div>
                        <div style="font-size:11px; color:#666; margin-top:4px; font-weight:700;">${dateStr}</div>
                        <div style="font-size:10px; color:#999; margin-top:2px;">${plan.lesson_day}</div>
                    </td>
                    <td style="border:1px solid #000; padding:12px;">
                        <div style="display:grid; grid-template-columns: repeat(${plan.weekly_frequency}, 1fr); gap:12px;">
            `;
            weeks[weekNum].forEach(slot => {
                html += `
                    <div style="background:#fff; border:1px solid #e2e8f0; padding:10px; border-radius:6px; min-height:60px; display:flex; flex-direction:column; justify-content:center;">
                        <div style="font-weight:800; font-size:12px; color:#000; line-height:1.4; margin-bottom:5px;">${slot.title || '---'}</div>
                        <div style="display:flex; justify-content:space-between; font-size:10px; color:#64748b; font-weight:600;">
                            <span>${slot.type || '---'}</span>
                            <span>${slot.duration || '45 min'}</span>
                        </div>
                    </div>
                `;
            });
            html += `</div></td></tr>`;
            });
        });

        html += `</tbody></table>
            <div style="margin-top:30px; text-align:center; font-size:10px; color:#999; border-top:1px solid #eee; padding-top:10px;">
                مستند رسمي صادر عبر نظام كنترول للتخطيط الرياضي الذكي
            </div>
        `;

        $container.html(html);
        $('body').append($container);

        const opt = {
            margin: 5,
            filename: `${plan.plan_name}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from($container[0]).save().then(() => {
            $container.remove();
        });
    }
});
</script>

<style>
.config-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
.plan-card { transition: 0.3s; }
.plan-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-color: var(--control-accent); }
.lesson-slot-title:focus, .lesson-slot-type:focus { outline: none; border-bottom: 1px solid var(--control-accent) !important; }
</style>
