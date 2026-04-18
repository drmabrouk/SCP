<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_admin = Control_Auth::is_admin();
global $wpdb;
$grading_config = json_decode($wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'grading_config'"), true);
$org_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_name'") ?: 'Control System';
$org_logo = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'");
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h2 style="font-weight:800; font-size:1.4rem; margin:0; color:var(--control-text-dark);"><?php _e('إدارة درجات الطلاب', 'control'); ?></h2>
        <p style="color:var(--control-muted); font-size:0.85rem; margin-top:5px;"><?php _e('رصد أداء الطلاب البدني والمهاري والمشاركة', 'control'); ?></p>
    </div>
    <div style="display:flex; gap:10px;">
        <?php if($is_admin): ?>
        <button id="open-grading-config" class="control-btn" style="background:var(--control-bg); color:var(--control-text-dark) !important; border:1px solid var(--control-border); padding:0 12px;" title="<?php _e('إعدادات توزيع الدرجات', 'control'); ?>">
            <span class="dashicons dashicons-admin-generic"></span>
        </button>
        <?php endif; ?>
        <button id="import-excel-btn" class="control-btn" style="background:var(--control-bg); color:var(--control-primary) !important; border:1px solid var(--control-border);">
            <span class="dashicons dashicons-upload" style="margin-left:8px;"></span><?php _e('استيراد من Excel', 'control'); ?>
        </button>
        <button id="add-student-btn" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; font-weight:800;">
            <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة طالب', 'control'); ?>
        </button>
    </div>
</div>

<!-- Filters & Search -->
<div class="control-card" style="padding:20px; margin-bottom:25px; border:none; background:rgba(0,0,0,0.02);">
    <div style="display:flex; gap:15px; align-items: center; flex-wrap: wrap;">
        <div style="flex:1; position:relative; min-width: 250px;">
            <span class="dashicons dashicons-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--control-muted);"></span>
            <input type="text" id="student-search-input" placeholder="<?php _e('ابحث باسم الطالب...', 'control'); ?>" style="padding:10px 40px 10px 12px;">
        </div>

        <select id="grade-filter" style="width:160px; padding:10px;">
            <option value=""><?php _e('جميع الصفوف', 'control'); ?></option>
            <?php for($i=1; $i<=12; $i++): ?>
                <option value="<?php echo "الصف " . $i; ?>"><?php echo "الصف " . $i; ?></option>
            <?php endfor; ?>
        </select>

        <select id="section-filter" style="width:140px; padding:10px;">
            <option value=""><?php _e('جميع الشعب', 'control'); ?></option>
            <?php foreach(['A', 'B', 'C', 'D', 'E', '1', '2', '3'] as $s): ?>
                <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
            <?php endforeach; ?>
        </select>

        <button id="filter-students-btn" class="control-btn" style="background:var(--control-primary); border:none;">
            <?php _e('تحديث القائمة', 'control'); ?>
        </button>
    </div>
</div>

<!-- Grades Bulk Table -->
<div class="control-card" style="padding:0; overflow:hidden; border-radius:16px;">
    <div style="padding:20px; background:var(--control-primary); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <h4 style="margin:0; color:#fff; font-size:1rem;"><?php _e('رصد الدرجات الجماعي', 'control'); ?></h4>
        <div style="display:flex; gap:10px;">
            <button id="print-class-list" class="control-btn" style="background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); font-size:0.75rem; height:32px;">
                <span class="dashicons dashicons-printer" style="margin-left:5px;"></span><?php _e('طباعة الكشف', 'control'); ?>
            </button>
            <button id="save-all-grades" class="control-btn" style="background:var(--control-accent); color:var(--control-primary) !important; border:none; font-weight:800; font-size:0.75rem; height:32px;">
                <span class="dashicons dashicons-saved" style="margin-left:5px;"></span><?php _e('حفظ كافة الدرجات', 'control'); ?>
            </button>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="control-table" id="grades-bulk-table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th style="width:60px;"><?php _e('م', 'control'); ?></th>
                    <th><?php _e('اسم الطالب', 'control'); ?></th>
                    <?php foreach($grading_config as $cat): ?>
                        <th style="width:100px;"><?php echo $cat['label']; ?> (<?php echo $cat['weight']; ?>)</th>
                    <?php endforeach; ?>
                    <th style="width:100px; background:rgba(212,175,55,0.1); font-weight:800;"><?php _e('المجموع (100)', 'control'); ?></th>
                    <th style="width:80px;"><?php _e('إجراء', 'control'); ?></th>
                </tr>
            </thead>
            <tbody id="grades-table-body">
                <tr>
                    <td colspan="10" style="text-align:center; padding:50px; color:var(--control-muted);">
                        <?php _e('يرجى اختيار الصف والشعبة لعرض الطلاب...', 'control'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Student Modal -->
<div id="student-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10005; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="control-card" style="width:100%; max-width:600px; border-radius:24px; padding:0; overflow:hidden;">
        <div style="background:var(--control-primary); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('بيانات الطالب', 'control'); ?></h3>
            <button type="button" onclick="jQuery('#student-modal').hide()" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
        <form id="student-form" style="padding:30px;">
            <input type="hidden" name="id" id="student-id" value="0">
            <div class="control-form-group">
                <label><?php _e('الاسم الكامل', 'control'); ?> *</label>
                <input type="text" name="name" id="student-name" required>
            </div>
            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="control-form-group">
                    <label><?php _e('الصف', 'control'); ?></label>
                    <select name="grade" id="student-grade">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?php echo "الصف " . $i; ?>"><?php echo "الصف " . $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="control-form-group">
                    <label><?php _e('الشعبة', 'control'); ?></label>
                    <input type="text" name="section" id="student-section">
                </div>
            </div>
            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="control-form-group">
                    <label><?php _e('الجنسية', 'control'); ?></label>
                    <input type="text" name="nationality" id="student-nationality">
                </div>
                <div class="control-form-group">
                    <label><?php _e('الرقم الوطني / الهوية', 'control'); ?></label>
                    <input type="text" name="national_id" id="student-national-id">
                </div>
            </div>
            <div class="control-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="control-form-group">
                    <label><?php _e('البريد الإلكتروني', 'control'); ?></label>
                    <input type="email" name="email" id="student-email">
                </div>
                <div class="control-form-group">
                    <label><?php _e('رقم الهاتف', 'control'); ?></label>
                    <input type="text" name="phone" id="student-phone">
                </div>
            </div>
            <button type="submit" class="control-btn" style="width:100%; height:45px; margin-top:10px;"><?php _e('حفظ بيانات الطالب', 'control'); ?></button>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10005; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="control-card" style="width:100%; max-width:500px; padding:30px; border-radius:20px; text-align:center;">
        <h3 style="margin-bottom:20px;"><?php _e('استيراد قائمة الطلاب', 'control'); ?></h3>
        <p style="font-size:0.85rem; color:var(--control-muted); margin-bottom:25px;">
            <?php _e('يرجى اختيار ملف Excel لاستيراد البيانات (يجب أن تكون الأعمدة بالترتيب: الاسم، الصف، الشعبة، الجنسية، البريد، الهاتف، الهوية).', 'control'); ?>
        </p>

        <div style="border: 2px dashed var(--control-border); padding: 40px 20px; border-radius: 15px; margin-bottom: 25px; background: rgba(0,0,0,0.01); transition: 0.3s;" id="excel-drop-zone">
            <span class="dashicons dashicons-upload" style="font-size: 40px; width: 40px; height: 40px; color: var(--control-primary); margin-bottom: 10px;"></span>
            <p style="margin: 10px 0; font-weight: 700; color: var(--control-text-dark);"><?php _e('اسحب الملف هنا أو انقر للاختيار', 'control'); ?></p>
            <input type="file" id="excel-file-input" accept=".xlsx, .xls, .csv" style="display: none;">
            <button type="button" class="control-btn" onclick="jQuery('#excel-file-input').click()" style="background: var(--control-bg); color: var(--control-primary) !important; border: 1px solid var(--control-border); font-size: 0.8rem; height: 32px;"><?php _e('اختيار ملف', 'control'); ?></button>
            <div id="selected-file-name" style="margin-top: 15px; font-size: 0.8rem; color: var(--control-accent); font-weight: 700; display: none;"></div>
        </div>

        <div id="import-progress-container" style="display:none; margin-bottom:20px;">
            <div style="width:100%; height:8px; background:#f1f5f9; border-radius:10px; overflow:hidden; margin-bottom:10px;">
                <div id="import-progress-bar" style="width:0%; height:100%; background:var(--control-primary); transition:0.3s;"></div>
            </div>
            <p id="import-progress-text" style="font-size:0.75rem; color:var(--control-primary); font-weight:700;">0%</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button id="process-import-btn" class="control-btn" style="flex:1; background:var(--control-primary); border:none;"><?php _e('تحليل واستيراد', 'control'); ?></button>
            <button type="button" onclick="jQuery('#import-modal').hide()" class="control-btn" style="flex:1; background:var(--control-bg); color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><?php _e('إلغاء', 'control'); ?></button>
        </div>
    </div>
</div>

<!-- Grading Config Modal -->
<div id="grading-config-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10006; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="control-card" style="width:100%; max-width:750px; border-radius:24px; padding:0; overflow:hidden;">
        <div style="background:var(--control-primary); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0; font-size:1.1rem;"><?php _e('إعدادات توزيع الدرجات', 'control'); ?></h3>
            <button type="button" onclick="jQuery('#grading-config-modal').hide()" style="background:none; border:none; color:#fff; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
        <div style="padding:30px;">
            <div id="config-items-container" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:25px;">
                <!-- Config items injected via JS -->
            </div>
            <div style="background:var(--control-bg); padding:15px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <strong style="color:var(--control-primary);"><?php _e('المجموع الكلي:', 'control'); ?></strong>
                <span id="config-total-weight" style="font-weight:800; font-size:1.2rem; color:var(--control-accent);">100</span>
            </div>
            <button id="add-config-item" class="control-btn" style="width:100%; background:none; color:var(--control-primary) !important; border:1px dashed var(--control-border); margin-bottom:15px;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة فئة جديدة', 'control'); ?>
            </button>
            <button id="save-grading-config" class="control-btn" style="width:100%; height:45px; background:var(--control-accent); color:var(--control-primary) !important; border:none; font-weight:800;">
                <?php _e('حفظ التعديلات وتطبيقها', 'control'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const gradingConfig = <?php echo json_encode($grading_config); ?>;

    function fetchStudents() {
        const grade = $('#grade-filter').val();
        const section = $('#section-filter').val();
        const $tbody = $('#grades-table-body');

        $tbody.html('<tr><td colspan="' + (gradingConfig.length + 4) + '" style="text-align:center; padding:50px;"><span class="dashicons dashicons-update spin"></span></td></tr>');

        $.post(control_ajax.ajax_url, {
            action: 'control_get_students',
            grade: grade,
            section: section,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success && res.data.length > 0) {
                let html = '';
                res.data.forEach((s, index) => {
                    html += `<tr class="student-grade-row" data-id="${s.id}">
                        <td>${index + 1}</td>
                        <td><strong class="student-name">${s.name}</strong><br><small style="color:#94a3b8;">${s.nationality || ''} | ${s.national_id || ''}</small></td>`;

                    gradingConfig.forEach(cat => {
                        const val = s[cat.id] || 0;
                        html += `<td><input type="number" class="grade-input" data-field="${cat.id}" data-weight="${cat.weight}" value="${val}" min="0" max="${cat.weight}"></td>`;
                    });

                    html += `<td style="background:rgba(212,175,55,0.05);"><strong class="total-score-val">${s.total_score || 0}</strong></td>
                        <td>
                            <div style="display:flex; gap:5px;">
                                <button class="edit-student-btn" data-id='${JSON.stringify(s)}' style="background:none; border:none; cursor:pointer; color:var(--control-primary);"><span class="dashicons dashicons-edit"></span></button>
                                <button class="delete-student-btn" data-id="${s.id}" style="background:none; border:none; cursor:pointer; color:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        </td>
                    </tr>`;
                });
                $tbody.html(html);
            } else {
                $tbody.html('<tr><td colspan="' + (gradingConfig.length + 4) + '" style="text-align:center; padding:50px; color:var(--control-muted);"><?php _e('لا يوجد طلاب مضافين لهذا الصف/الشعبة.', 'control'); ?></td></tr>');
            }
        });
    }

    $('#filter-students-btn').on('click', fetchStudents);

    $('#add-student-btn').on('click', function() {
        $('#student-form')[0].reset();
        $('#student-id').val('0');
        $('#student-modal').css('display', 'flex');
    });

    $(document).on('click', '.edit-student-btn', function() {
        const s = JSON.parse($(this).attr('data-id'));
        $('#student-id').val(s.id);
        $('#student-name').val(s.name);
        $('#student-grade').val(s.grade);
        $('#student-section').val(s.section);
        $('#student-nationality').val(s.nationality);
        $('#student-national-id').val(s.national_id);
        $('#student-email').val(s.email);
        $('#student-phone').val(s.phone);
        $('#student-modal').css('display', 'flex');
    });

    $('#student-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('<?php _e('جاري الحفظ...', 'control'); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_save_student',
            nonce: control_ajax.nonce,
            ...$(this).serializeArray().reduce((obj, item) => ({...obj, [item.name]: item.value}), {})
        }, function(res) {
            if (res.success) {
                $('#student-modal').hide();
                fetchStudents();
            }
            $btn.prop('disabled', false).text('<?php _e('حفظ بيانات الطالب', 'control'); ?>');
        });
    });

    $(document).on('click', '.delete-student-btn', function() {
        if (!confirm('<?php _e('هل أنت متأكد من حذف هذا الطالب نهائياً؟', 'control'); ?>')) return;
        const id = $(this).data('id');
        $.post(control_ajax.ajax_url, { action: 'control_delete_student', id: id, nonce: control_ajax.nonce }, function(res) {
            if (res.success) fetchStudents();
        });
    });

    $('#import-excel-btn').on('click', function() {
        $('#import-modal').css('display', 'flex');
        $('#excel-file-input').val('');
        $('#selected-file-name').hide();
    });

    $('#excel-file-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            $('#selected-file-name').text(file.name).show();
            $('#excel-drop-zone').css('border-color', 'var(--control-accent)');
        }
    });

    $('#process-import-btn').on('click', function() {
        const fileInput = $('#excel-file-input')[0];
        if (!fileInput.files.length) {
            alert('<?php _e('يرجى اختيار ملف أولاً.', 'control'); ?>');
            return;
        }

        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const data = e.target.result;
            // Since we don't have a full Excel library like SheetJS here without CDNs,
            // we will simulate the parsing if it's CSV or use the existing line-based logic
            // if it's treated as text (copy-paste backend logic remains similar).
            // For a robust implementation, a backend parser in PHP is preferred.

            let students = [];
            const text = data.toString();
            const lines = text.split(/\r?\n/);

            lines.forEach((line, index) => {
                if (index === 0 && line.toLowerCase().includes('name')) return; // skip header
                const cols = line.split(/[\t,]/); // Support Tab or Comma
                if (cols.length >= 1 && cols[0].trim() !== '') {
                    students.push({
                        name: (cols[0] || '').trim().replace(/^["']|["']$/g, ''),
                        grade: (cols[1] || '').trim().replace(/^["']|["']$/g, ''),
                        section: (cols[2] || '').trim().replace(/^["']|["']$/g, ''),
                        nationality: (cols[3] || '').trim().replace(/^["']|["']$/g, ''),
                        email: (cols[4] || '').trim().replace(/^["']|["']$/g, ''),
                        phone: (cols[5] || '').trim().replace(/^["']|["']$/g, ''),
                        national_id: (cols[6] || '').trim().replace(/^["']|["']$/g, '')
                    });
                }
            });

            if (students.length === 0) {
                alert('<?php _e('لم يتم العثور على بيانات صالحة في الملف.', 'control'); ?>');
                return;
            }

            executeImport(students);
        };

        reader.readAsText(file);
    });

    function executeImport(students) {
        const $btn = $('#process-import-btn');
        const $progress = $('#import-progress-container');
        const $bar = $('#import-progress-bar');
        const $text = $('#import-progress-text');

        $btn.prop('disabled', true);
        $progress.fadeIn();

        // Process in chunks for large datasets
        const chunkSize = 50;
        let processed = 0;
        let totalImported = 0;
        let totalUpdated = 0;
        let totalFailed = [];

        function sendChunk() {
            const chunk = students.slice(processed, processed + chunkSize);
            if (chunk.length === 0) {
                $progress.fadeOut();
                alert(`اكتمل الاستيراد:\n- تمت إضافة: ${totalImported}\n- تم تحديث: ${totalUpdated}\n- فشل: ${totalFailed.length}`);
                if(totalFailed.length > 0) console.log('Failures:', totalFailed);
                $('#import-modal').hide();
                fetchStudents();
                $btn.prop('disabled', false);
                return;
            }

            $.post(control_ajax.ajax_url, {
                action: 'control_import_students',
                students: chunk,
                nonce: control_ajax.nonce
            }, function(res) {
                if (res.success) {
                    totalImported += res.data.imported;
                    totalUpdated += res.data.updated;
                    totalFailed = totalFailed.concat(res.data.failed);
                }
                processed += chunk.length;
                const percent = Math.round((processed / students.length) * 100);
                $bar.css('width', percent + '%');
                $text.text(percent + '%');
                sendChunk();
            });
        }

        sendChunk();
    });

    $(document).on('input', '.grade-input', function() {
        const $input = $(this);
        const max = parseFloat($input.attr('max'));
        let val = parseFloat($input.val());

        if (val > max) { $input.val(max); val = max; }
        if (val < 0 || isNaN(val)) { $input.val(0); val = 0; }

        const row = $input.closest('tr');
        let total = 0;
        row.find('.grade-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        row.find('.total-score-val').text(total.toFixed(1));

        // Instant Auto-save for inline editing
        const studentId = row.data('id');
        const grades = {};
        grades[studentId] = {};
        row.find('.grade-input').each(function() {
            grades[studentId][$(this).data('field')] = $(this).val();
        });
        grades[studentId]['total_score'] = total.toFixed(1);

        $.post(control_ajax.ajax_url, {
            action: 'control_save_grades',
            grades: grades,
            nonce: control_ajax.nonce
        });
    });

    $('#save-all-grades').on('click', function() {
        const grades = {};
        $('.student-grade-row').each(function() {
            const id = $(this).data('id');
            grades[id] = {};
            $(this).find('.grade-input').each(function() {
                grades[id][$(this).data('field')] = $(this).val();
            });
            grades[id]['total_score'] = $(this).find('.total-score-val').text();
        });

        if ($.isEmptyObject(grades)) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('جاري الحفظ...', 'control'); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_save_grades',
            grades: grades,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) {
                $btn.text('<?php _e('تم الحفظ بنجاح!', 'control'); ?>').css('background', '#10b981');
                setTimeout(() => {
                    $btn.prop('disabled', false).text('<?php _e('حفظ كافة الدرجات', 'control'); ?>').css('background', 'var(--control-accent)');
                }, 2000);
            }
        });
    });

    $('#student-search-input').on('input', function() {
        const query = $(this).val().toLowerCase();
        $('.student-grade-row').each(function() {
            const name = $(this).find('.student-name').text().toLowerCase();
            $(this).toggle(name.includes(query));
        });
    });

    $('#print-class-list').on('click', function() {
        const grade = $('#grade-filter').val() || 'جميع الصفوف';
        const section = $('#section-filter').val() || 'جميع الشعب';

        // Prepare data for printing (clean from inputs)
        let tableHtml = `<table style="width:100%; border-collapse:collapse; font-size:11px; margin-top:20px; border:1px solid #000;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="border:1px solid #000; padding:10px;">م</th>
                    <th style="border:1px solid #000; padding:10px;">اسم الطالب</th>`;

        gradingConfig.forEach(cat => {
            tableHtml += `<th style="border:1px solid #000; padding:10px; width:60px;">${cat.label}<br>(${cat.weight})</th>`;
        });

        tableHtml += `<th style="border:1px solid #000; padding:10px; width:70px; background:#e2e8f0; font-weight:800;">المجموع<br>(100)</th></tr></thead><tbody>`;

        $('.student-grade-row:visible').each(function(i) {
            const name = $(this).find('.student-name').text();
            tableHtml += `<tr>
                <td style="border:1px solid #000; padding:8px; text-align:center;">${i+1}</td>
                <td style="border:1px solid #000; padding:8px; font-weight:700;">${name}</td>`;

            gradingConfig.forEach(cat => {
                const val = $(this).find(`input[data-field="${cat.id}"]`).val();
                tableHtml += `<td style="border:1px solid #000; padding:8px; text-align:center;">${val}</td>`;
            });

            const total = $(this).find('.total-score-val').text();
            tableHtml += `<td style="border:1px solid #000; padding:8px; text-align:center; font-weight:800; background:#f8fafc;">${total}</td></tr>`;
        });

        tableHtml += `</tbody></table>`;

        const win = window.open('', '_blank');
        win.document.write(`
            <html>
                <head>
                    <title>تقرير درجات: ${grade} - ${section}</title>
                    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700;800&display=swap">
                    <style>
                        body { font-family: 'Rubik', sans-serif; direction: rtl; padding: 15mm; margin: 0; color: #000; }
                        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2.5px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
                        .org-info { flex: 1; }
                        .org-logo { height: 60px; margin-bottom: 10px; }
                        .doc-title { font-size: 20px; font-weight: 800; }
                        .meta { font-size: 11px; line-height: 1.6; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="org-info">
                            <?php if($org_logo): ?><img src="<?php echo $org_logo; ?>" class="org-logo"><?php endif; ?>
                            <div class="doc-title">كشف رصد درجات الطلاب</div>
                            <div style="font-weight:700; margin-top:5px;"><?php echo $org_name; ?></div>
                        </div>
                        <div class="meta">
                            <div><strong>الصف:</strong> ${grade}</div>
                            <div><strong>الشعبة:</strong> ${section}</div>
                            <div><strong>التاريخ:</strong> ${new Date().toLocaleDateString('ar-SA')}</div>
                            <div><strong>المادة:</strong> التربية الرياضية</div>
                        </div>
                    </div>
                    ${tableHtml}
                    <div style="margin-top:40px; display:flex; justify-content:space-between; font-size:12px;">
                        <div style="text-align:center;">توقيع مدرس المادة<br><br>................................</div>
                        <div style="text-align:center;">توقيع منسق القسم<br><br>................................</div>
                        <div style="text-align:center;">اعتماد الإدارة<br><br>................................</div>
                    </div>
                </body>
            </html>
        `);
        setTimeout(() => { win.print(); }, 500);
    });

    // --- Grading Configuration Logic ---

    $('#open-grading-config').on('click', function() {
        renderConfigItems();
        $('#grading-config-modal').css('display', 'flex');
    });

    function renderConfigItems() {
        let html = '';
        gradingConfig.forEach((item, index) => {
            html += `
                <div class="config-row" style="display:flex; gap:10px; align-items:center; background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--control-border);">
                    <input type="text" class="config-label" value="${item.label}" placeholder="Category" style="flex:3; height:36px; font-size:0.85rem; font-weight:700; width: 100%;">
                    <input type="number" class="config-weight" value="${item.weight}" placeholder="Wt" style="flex:1; height:36px; text-align:center; min-width: 50px;">
                    <button class="remove-config-item" style="background:none; border:none; color:#ef4444; cursor:pointer; flex-shrink: 0;"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
            `;
        });
        $('#config-items-container').html(html);
        updateConfigTotal();
    }

    $(document).on('click', '.remove-config-item', function() {
        $(this).closest('.config-row').remove();
        updateConfigTotal();
    });

    $('#add-config-item').on('click', function() {
        const html = `
            <div class="config-row" style="display:flex; gap:10px; align-items:center; background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--control-border);">
                <input type="text" class="config-label" placeholder="Category" style="flex:3; height:36px; font-size:0.85rem; font-weight:700; width: 100%;">
                <input type="number" class="config-weight" value="0" placeholder="Wt" style="flex:1; height:36px; text-align:center; min-width: 50px;">
                <button class="remove-config-item" style="background:none; border:none; color:#ef4444; cursor:pointer; flex-shrink: 0;"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
        `;
        $('#config-items-container').append(html);
    });

    $(document).on('input', '.config-weight', updateConfigTotal);

    function updateConfigTotal() {
        let total = 0;
        $('.config-weight').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#config-total-weight').text(total);
        if(total !== 100) $('#config-total-weight').css('color', '#ef4444');
        else $('#config-total-weight').css('color', '#10b981');
    }

    $('#save-grading-config').on('click', function() {
        const total = parseFloat($('#config-total-weight').text());
        if(total !== 100) {
            alert('يجب أن يكون مجموع الدرجات مساوياً لـ 100 بالضبط.');
            return;
        }

        const config = [];
        $('.config-row').each(function() {
            const label = $(this).find('.config-label').val();
            const weight = parseFloat($(this).find('.config-weight').val());
            if(label && weight >= 0) {
                config.push({
                    id: label.replace(/\s+/g, '_').toLowerCase(),
                    label: label,
                    weight: weight
                });
            }
        });

        const $btn = $(this);
        $btn.prop('disabled', true).text('جاري الحفظ...');

        $.post(control_ajax.ajax_url, {
            action: 'control_save_settings',
            settings: { grading_config: JSON.stringify(config) },
            nonce: control_ajax.nonce
        }, function(res) {
            if(res.success) location.reload();
        });
    });
});
</script>

<style>
.grade-input { width: 100%; border: 1px solid var(--control-border); padding: 5px; border-radius: 6px; text-align: center; font-weight: 700; color: var(--control-primary); }
.grade-input:focus { border-color: var(--control-accent); outline: none; background: rgba(212,175,55,0.05); }
.total-score-val { color: var(--control-primary); font-size: 1rem; }
</style>
