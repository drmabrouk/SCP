<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_admin = Control_Auth::is_admin();
?>

<div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h2 style="font-weight:800; font-size:1.4rem; margin:0; color:var(--control-text-dark);"><?php _e('إدارة درجات الطلاب', 'control'); ?></h2>
        <p style="color:var(--control-muted); font-size:0.85rem; margin-top:5px;"><?php _e('رصد أداء الطلاب البدني والمهاري والمشاركة', 'control'); ?></p>
    </div>
    <div style="display:flex; gap:10px;">
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
                    <th style="width:100px;"><?php _e('اللياقة (20)', 'control'); ?></th>
                    <th style="width:100px;"><?php _e('الانضباط (10)', 'control'); ?></th>
                    <th style="width:100px;"><?php _e('الشفهي (10)', 'control'); ?></th>
                    <th style="width:100px;"><?php _e('المهاري (30)', 'control'); ?></th>
                    <th style="width:100px;"><?php _e('السلوك (15)', 'control'); ?></th>
                    <th style="width:100px;"><?php _e('المشاركة (15)', 'control'); ?></th>
                    <th style="width:100px; background:rgba(212,175,55,0.1);"><?php _e('المجموع (100)', 'control'); ?></th>
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
            <?php _e('يرجى لصق البيانات من Excel مباشرة (يجب أن تكون الأعمدة بالترتيب: الاسم، الصف، الشعبة، الجنسية، البريد، الهاتف، الهوية).', 'control'); ?>
        </p>
        <textarea id="excel-paste-area" style="width:100%; height:200px; margin-bottom:20px; font-size:0.8rem; direction:ltr;" placeholder="Paste Excel data here..."></textarea>
        <div style="display:flex; gap:10px;">
            <button id="process-import-btn" class="control-btn" style="flex:1; background:var(--control-primary); border:none;"><?php _e('تحليل واستيراد', 'control'); ?></button>
            <button type="button" onclick="jQuery('#import-modal').hide()" class="control-btn" style="flex:1; background:var(--control-bg); color:var(--control-text-dark) !important; border:1px solid var(--control-border);"><?php _e('إلغاء', 'control'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function fetchStudents() {
        const grade = $('#grade-filter').val();
        const section = $('#section-filter').val();
        const $tbody = $('#grades-table-body');

        $tbody.html('<tr><td colspan="10" style="text-align:center; padding:50px;"><span class="dashicons dashicons-update spin"></span></td></tr>');

        $.post(control_ajax.ajax_url, {
            action: 'control_get_students',
            grade: grade,
            section: section,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success && res.data.length > 0) {
                let html = '';
                res.data.forEach((s, index) => {
                    html += `
                        <tr class="student-grade-row" data-id="${s.id}">
                            <td>${index + 1}</td>
                            <td><strong class="student-name">${s.name}</strong><br><small style="color:#94a3b8;">${s.nationality || ''} | ${s.national_id || ''}</small></td>
                            <td><input type="number" class="grade-input" data-field="physical_fitness" value="${s.physical_fitness || 0}" min="0" max="20"></td>
                            <td><input type="number" class="grade-input" data-field="discipline" value="${s.discipline || 0}" min="0" max="10"></td>
                            <td><input type="number" class="grade-input" data-field="oral_questioning" value="${s.oral_questioning || 0}" min="0" max="10"></td>
                            <td><input type="number" class="grade-input" data-field="practical_skills" value="${s.practical_skills || 0}" min="0" max="30"></td>
                            <td><input type="number" class="grade-input" data-field="behavior" value="${s.behavior || 0}" min="0" max="15"></td>
                            <td><input type="number" class="grade-input" data-field="participation" value="${s.participation || 0}" min="0" max="15"></td>
                            <td style="background:rgba(212,175,55,0.05);"><strong class="total-score-val">${s.total_score || 0}</strong></td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <button class="edit-student-btn" data-id='${JSON.stringify(s)}' style="background:none; border:none; cursor:pointer; color:var(--control-primary);"><span class="dashicons dashicons-edit"></span></button>
                                    <button class="delete-student-btn" data-id="${s.id}" style="background:none; border:none; cursor:pointer; color:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                $tbody.html(html);
            } else {
                $tbody.html('<tr><td colspan="10" style="text-align:center; padding:50px; color:var(--control-muted);"><?php _e('لا يوجد طلاب مضافين لهذا الصف/الشعبة.', 'control'); ?></td></tr>');
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

    $('#import-excel-btn').on('click', function() { $('#import-modal').css('display', 'flex'); });

    $('#process-import-btn').on('click', function() {
        const text = $('#excel-paste-area').val();
        if (!text) return;

        const lines = text.split("\n");
        const students = [];
        lines.forEach(line => {
            const cols = line.split("\t");
            if (cols.length >= 2) {
                students.push({
                    name: cols[0],
                    grade: cols[1] || '',
                    section: cols[2] || '',
                    nationality: cols[3] || '',
                    email: cols[4] || '',
                    phone: cols[5] || '',
                    national_id: cols[6] || ''
                });
            }
        });

        if (students.length === 0) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('جاري الاستيراد...', 'control'); ?>');

        $.post(control_ajax.ajax_url, {
            action: 'control_import_students',
            students: students,
            nonce: control_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#import-modal').hide();
                alert(`Successfully imported ${res.data.imported} students.`);
                fetchStudents();
            }
            $btn.prop('disabled', false).text('<?php _e('تحليل واستيراد', 'control'); ?>');
        });
    });

    $(document).on('input', '.grade-input', function() {
        const row = $(this).closest('tr');
        let total = 0;
        row.find('.grade-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        row.find('.total-score-val').text(total.toFixed(1));
    });

    $('#save-all-grades').on('click', function() {
        const grades = {};
        $('.student-grade-row').each(function() {
            const id = $(this).data('id');
            grades[id] = {
                physical_fitness: $(this).find('[data-field="physical_fitness"]').val(),
                discipline: $(this).find('[data-field="discipline"]').val(),
                oral_questioning: $(this).find('[data-field="oral_questioning"]').val(),
                practical_skills: $(this).find('[data-field="practical_skills"]').val(),
                behavior: $(this).find('[data-field="behavior"]').val(),
                participation: $(this).find('[data-field="participation"]').val(),
                total_score: $(this).find('.total-score-val').text()
            };
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
        const grade = $('#grade-filter').val();
        const section = $('#section-filter').val();
        const content = $('#grades-bulk-table')[0].outerHTML;

        const win = window.open('', '_blank');
        win.document.write(`
            <html>
                <head>
                    <title>كشف درجات: ${grade} - ${section}</title>
                    <style>
                        body { font-family: 'Rubik', sans-serif; direction: rtl; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: right; }
                        th { background: #f1f5f9; }
                        input { border: none; width: 40px; text-align: center; font-family: inherit; }
                        .edit-student-btn, .delete-student-btn, #grades-bulk-table th:last-child, #grades-bulk-table td:last-child { display: none; }
                    </style>
                </head>
                <body>
                    <h2 style="text-align:center;">كشف درجات مادة التربية الرياضية</h2>
                    <p style="text-align:center;">${grade} - شعبة ${section}</p>
                    ${content}
                    <div style="margin-top:50px; display:flex; justify-content:space-between;">
                        <div>توقيع المعلم: ............................</div>
                        <div>توقيع المنسق: ............................</div>
                    </div>
                </body>
            </html>
        `);
        win.print();
    });
});
</script>

<style>
.grade-input { width: 100%; border: 1px solid var(--control-border); padding: 5px; border-radius: 6px; text-align: center; font-weight: 700; color: var(--control-primary); }
.grade-input:focus { border-color: var(--control-accent); outline: none; background: rgba(212,175,55,0.05); }
.total-score-val { color: var(--control-primary); font-size: 1rem; }
</style>
