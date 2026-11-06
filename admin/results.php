<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];
$settings = get_school_settings($school['settings_json'] ?? null);
$max_possible = ($settings['ca_max'] * $settings['ca_count']) + $settings['exam_max'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { set_flash('error','Session expired.'); redirect('admin/results.php'); }
    $action = post('action', 'save');
    if ($action === 'sync_teachers') {
        $session = post('sync_session'); $term = post('sync_term');
        $stmt = $pdo->prepare("SELECT id FROM students WHERE school_id=?"); $stmt->execute([$sid]); $students = $stmt->fetchAll(PDO::FETCH_COLUMN); $synced = 0;
        foreach($students as $st_id) { if (compile_student_result($pdo, $st_id, $sid, $session, $term)) $synced++; }
        set_flash('success', "Sync complete! $synced student results compiled."); redirect('admin/results.php');
    }
    if ($action === 'delete') {
        $rid = (int)post('result_id'); $stmt = $pdo->prepare("SELECT * FROM results WHERE id=? AND school_id=? LIMIT 1"); $stmt->execute([$rid, $sid]); $row = $stmt->fetch();
        if ($row) { $pdo->prepare("DELETE FROM results WHERE id=?")->execute([$rid]); recalculate_positions($pdo, $sid, $row['session'], $row['term']); set_flash('success','Result deleted.'); }
        redirect('admin/results.php');
    }
    // Manual Save
    $studentId = (int)post('student_id'); $session = post('session'); $term = post('term'); $remark = post('teacher_remark');
    $subjectsIn = $_POST['subjects'] ?? []; $examIn = $_POST['exam'] ?? [];
    $affective = json_encode($_POST['affective'] ?? []);
    $psychomotor = json_encode($_POST['psychomotor'] ?? []);
    
    $stmt = $pdo->prepare("SELECT id FROM students WHERE id=? AND school_id=? LIMIT 1"); $stmt->execute([$studentId, $sid]);
    if (!$stmt->fetch()) { set_flash('error','Select a valid student.'); redirect('admin/results.php'); }
    $subjects = []; $grandTotal = 0.0; $subjectCount = 0;
    if (is_array($subjectsIn)) {
        for ($i = 0; $i < count($subjectsIn); $i++) {
            $name = trim((string)$subjectsIn[$i]); if ($name === '') continue;
            $ca_scores = []; $ca_total = 0;
            for ($c = 1; $c <= $settings['ca_count']; $c++) { $val = isset($_POST["ca{$c}"][$i]) && $_POST["ca{$c}"][$i] !== '' ? (float)$_POST["ca{$c}"][$i] : 0; $ca_scores["ca{$c}"] = $val; $ca_total += $val; }
            $exam = isset($examIn[$i]) && $examIn[$i] !== '' ? (float)$examIn[$i] : 0;
            $total = $ca_total + $exam; $subjectData = ['subject'=>$name, 'exam'=>$exam, 'total'=>$total];
            foreach($ca_scores as $k=>$v) $subjectData[$k] = $v;
            $subjects[] = $subjectData; $grandTotal += $total; $subjectCount++;
        }
    }
    if ($subjectCount === 0) { set_flash('error','Add at least one valid subject.'); redirect('admin/results.php'); }
    $average = $max_possible > 0 ? round(($grandTotal / ($max_possible * $subjectCount)) * 100, 2) : 0;
    $json = json_encode($subjects, JSON_UNESCAPED_UNICODE);
    $pdo->prepare("INSERT INTO results (student_id, school_id, session, term, subjects_json, total_score, average, teacher_remark, affective_json, psychomotor_json) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$studentId,$sid,$session,$term,$json,$grandTotal,$average,$remark,$affective,$psychomotor]);
    recalculate_positions($pdo, $sid, $session, $term); set_flash('success','Result saved manually.'); redirect('admin/results.php');
}
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id=? ORDER BY class_name ASC, surname ASC"); $stmt->execute([$sid]); $students = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT r.*, s.first_name, s.surname FROM results r INNER JOIN students s ON s.id=r.student_id WHERE r.school_id=? ORDER BY r.id DESC LIMIT 20"); $stmt->execute([$sid]); $recent = $stmt->fetchAll();
$pageTitle = 'Results'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div><h1 class="text-2xl font-extrabold text-royal-900">Results Management</h1><p class="text-sm text-slate-500">Sync teacher scores or upload manually.</p></div>
    <div class="bg-orange-50 border border-orange-200 p-3 rounded-xl flex items-center gap-3">
        <form method="post" class="flex items-center gap-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="sync_teachers">
            <select name="sync_session" class="text-xs rounded border px-2 py-1.5"><?php foreach(session_options() as $o): ?><option><?= $o ?></option><?php endforeach; ?></select>
            <select name="sync_term" class="text-xs rounded border px-2 py-1.5"><?php foreach(term_options() as $o): ?><option><?= $o ?></option><?php endforeach; ?></select>
            <button type="submit" class="rounded bg-orange-500 text-white font-bold px-3 py-1.5 text-xs shadow transition">Sync Teachers</button>
        </form>
    </div>
</div>
<?php if (empty($students)): ?>
<div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-5 py-4 mb-6">Add students first. <a href="<?= url('admin/students.php') ?>" class="font-bold underline">Add students</a>.</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-8">
<h2 class="font-bold text-slate-800 mb-4">Manual Result Entry</h2>
<form method="post" action="<?= url('admin/results.php') ?>" class="space-y-4" id="result-form">
<?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" id="max-possible" value="<?= $max_possible ?>"><input type="hidden" id="ca-count" value="<?= $settings['ca_count'] ?>">
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
<div class="sm:col-span-3"><label class="block text-xs font-semibold text-slate-600 mb-1">Student</label><select name="student_id" required class="w-full rounded-lg border px-3 py-2 text-sm"><option value="">- Select -</option><?php foreach ($students as $st): ?><option value="<?= (int)$st['id'] ?>"><?= e($st['surname'].' '.$st['first_name'].' ('.$st['class_name'].')') ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-600 mb-1">Session</label><select name="session" required class="w-full rounded-lg border px-3 py-2 text-sm"><?php foreach (session_options() as $o): ?><option value="<?= e($o) ?>"><?= e($o) ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-600 mb-1">Term</label><select name="term" required class="w-full rounded-lg border px-3 py-2 text-sm"><?php foreach (term_options() as $o): ?><option value="<?= e($o) ?>"><?= e($o) ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-600 mb-1">Total</label><div class="rounded-lg bg-slate-100 border px-3 py-2 text-sm font-bold text-royal-900"><span id="score-total">0</span></div></div>
</div>
<div>
<div class="flex items-center justify-between mb-2"><label class="block text-xs font-semibold text-slate-600">Subjects &amp; Scores</label><button type="button" id="add-subject" class="text-xs font-bold text-orange-600 hover:underline">+ Add Subject</button></div>
<div class="bg-slate-50 rounded-lg p-3 hidden sm:flex gap-2 text-[11px] uppercase font-bold text-slate-500 mb-2"><div class="flex-1">Subject</div><?php for($c=1; $c<=$settings['ca_count']; $c++): ?><div class="w-16 text-center">CA <?= $c ?></div><?php endfor; ?><div class="w-16 text-center">Exam</div><div class="w-16 text-center">Total</div><div class="w-6"></div></div>
<div id="subject-rows" class="space-y-2"></div>
<p class="text-xs text-slate-500 mt-3">Avg: <strong id="score-average" class="text-royal-900">0.00</strong>%</p>
</div>

<!-- Affective & Psychomotor -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
    <div>
        <h3 class="text-sm font-bold text-royal-900 mb-2">Affective Domain (1-5)</h3>
        <div class="space-y-2">
            <?php foreach(['Punctuality', 'Neatness', 'Politeness', 'Honesty', 'Cooperation'] as $trait): ?>
            <div class="flex justify-between items-center"><label class="text-xs text-slate-600"><?= $trait ?></label><select name="affective[<?= strtolower($trait) ?>]" class="border rounded text-sm px-2 py-1 w-16"><option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option></select></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div>
        <h3 class="text-sm font-bold text-royal-900 mb-2">Psychomotor (1-5)</h3>
        <div class="space-y-2">
            <?php foreach(['Handwriting', 'Sports', 'Drawing', 'Music', 'Craft'] as $trait): ?>
            <div class="flex justify-between items-center"><label class="text-xs text-slate-600"><?= $trait ?></label><select name="psychomotor[<?= strtolower($trait) ?>]" class="border rounded text-sm px-2 py-1 w-16"><option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option></select></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div><label class="block text-xs font-semibold text-slate-600 mb-1">Teacher's Remark</label><textarea name="teacher_remark" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="e.g. Excellent performance."></textarea></div>
<button type="submit" class="rounded-lg bg-royal-800 text-white font-bold px-6 py-3 text-sm shadow">Save Manual Result</button>
</form>
<template id="subject-row-template">
<div class="subject-row flex flex-col sm:flex-row items-center gap-2 bg-white sm:bg-transparent p-2 sm:p-0 rounded border sm:border-0 border-slate-200">
<input type="text" name="subjects[]" placeholder="Subject name" class="flex-1 w-full rounded-lg border px-3 py-2 text-sm">
<?php for($c=1; $c<=$settings['ca_count']; $c++): ?><input type="number" name="ca<?= $c ?>[]" max="<?= $settings['ca_max'] ?>" placeholder="CA<?= $c ?>" class="ca-input w-full sm:w-16 rounded-lg border px-2 py-2 text-sm text-center"><?php endfor; ?>
<input type="number" name="exam[]" max="<?= $settings['exam_max'] ?>" placeholder="Exam" class="exam-input w-full sm:w-16 rounded-lg border px-2 py-2 text-sm text-center">
<div class="w-full sm:w-16 text-center font-bold text-sm text-royal-900 bg-slate-100 rounded py-2 border border-slate-200 row-total">0</div>
<button type="button" class="remove-row text-red-500 hover:text-red-700 font-bold px-2 text-xl">&times;</button>
</div>
</template>
</div>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
<div class="px-5 py-4 border-b border-slate-200"><h2 class="font-bold text-slate-800">Recent Results</h2></div>
<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-slate-500 text-[11px] uppercase"><tr><th class="text-left px-4 py-3">Student</th><th class="text-left px-4 py-3">Session / Term</th><th class="text-left px-4 py-3">Avg</th><th class="text-left px-4 py-3">Pos</th><th class="text-right px-4 py-3">Action</th></tr></thead><tbody>
<?php if (empty($recent)): ?><tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No results yet.</td></tr>
<?php else: foreach ($recent as $r): ?>
<tr class="border-t border-slate-100"><td class="px-4 py-3 font-medium"><?= e($r['surname'].' '.$r['first_name']) ?></td><td class="px-4 py-3"><?= e($r['session']) ?> | <?= e($r['term']) ?></td><td class="px-4 py-3"><?= e(number_format((float)$r['average'],2)) ?>%</td><td class="px-4 py-3"><?= $r['position'] ? e(ordinal($r['position'])) : '-' ?></td>
<td class="px-4 py-3 text-right"><form method="post" onsubmit="return confirm('Delete?');" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="result_id" value="<?= (int)$r['id'] ?>"><button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete</button></form></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div>
<?php endif; ?>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rowsBox = document.getElementById('subject-rows'); var template = document.getElementById('subject-row-template');
    var addBtn = document.getElementById('add-subject'); var grandTotalOut = document.getElementById('score-total'); var avgOut = document.getElementById('score-average');
    var maxPossible = parseFloat(document.getElementById('max-possible').value);
    function recalc() {
        if (!rowsBox) return; var rows = rowsBox.querySelectorAll('.subject-row'); var grandTotal = 0; var validSubjects = 0;
        rows.forEach(function (row) {
            var rowTotal = 0; row.querySelectorAll('.ca-input').forEach(function(inp) { rowTotal += parseFloat(inp.value) || 0; });
            var exam = parseFloat(row.querySelector('.exam-input').value) || 0; rowTotal += exam;
            var subjectName = row.querySelector('input[name="subjects[]"]').value.trim();
            row.querySelector('.row-total').textContent = rowTotal.toFixed(1).replace(/\.0$/, '');
            if (subjectName !== '') { grandTotal += rowTotal; validSubjects++; }
        });
        if (grandTotalOut) grandTotalOut.textContent = grandTotal.toFixed(1).replace(/\.0$/, '');
        if (avgOut) avgOut.textContent = validSubjects > 0 ? ((grandTotal / (maxPossible * validSubjects)) * 100).toFixed(2) : '0.00';
    }
    function bindRow(row) {
        var rm = row.querySelector('.remove-row');
        if (rm) rm.addEventListener('click', function () { if (rowsBox.querySelectorAll('.subject-row').length > 1) { row.remove(); recalc(); } });
        row.querySelectorAll('input').forEach(function(input) { input.addEventListener('input', recalc); });
    }
    if (rowsBox && template && addBtn) {
        for (var i = 0; i < 3; i++) rowsBox.appendChild(template.content.cloneNode(true));
        rowsBox.querySelectorAll('.subject-row').forEach(bindRow);
        addBtn.addEventListener('click', function () { rowsBox.appendChild(template.content.cloneNode(true)); var rows = rowsBox.querySelectorAll('.subject-row'); bindRow(rows[rows.length - 1]); });
        recalc();
    }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>