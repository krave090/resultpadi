<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
$studentId = (int)get('id', 0);
$session = get('session'); $term = get('term');
$student = null; $result = null; $subjects = []; $errorMsg = '';

if ($studentId <= 0) { $errorMsg = 'No student selected.'; }
else {
    $stmt = $pdo->prepare("SELECT s.*, sc.school_name, sc.logo_url, sc.status AS school_status, sc.settings_json FROM students s INNER JOIN schools sc ON sc.id=s.school_id WHERE s.id=? LIMIT 1");
    $stmt->execute([$studentId]); $student = $stmt->fetch();
    if (!$student) { $errorMsg = 'Student not found.'; }
    else {
        if ($session !== '' && $term !== '') {
            $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id=? AND session=? AND term=? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$studentId, $session, $term]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id=? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$studentId]);
        }
        $result = $stmt->fetch();
        if (!$result) $errorMsg = 'No result found for this student.';
    }
}
$pageTitle = 'Result Dossier'; $navType = 'public';
if ($errorMsg !== '') {
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="max-w-xl mx-auto px-4 py-16"><div class="bg-white border border-red-200 rounded-2xl shadow-sm p-8 text-center"><div class="mx-auto h-12 w-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl font-black">!</div><h1 class="mt-4 text-lg font-bold text-slate-800">Result Unavailable</h1><p class="mt-2 text-sm text-slate-600"><?= e($errorMsg) ?></p><a href="<?= url('index.php') ?>" class="inline-block mt-6 rounded-lg bg-royal-800 hover:bg-royal-900 text-white text-sm font-semibold px-5 py-2.5">Back to Home</a></div></section>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}
try { $pdo->prepare("UPDATE results SET views=views+1 WHERE id=?")->execute([(int)$result['id']]); } catch (PDOException $e) {}

$settings = get_school_settings($student['settings_json'] ?? null);
$decoded = json_decode($result['subjects_json'], true); $hasCA = false;

if (is_array($decoded)) {
    foreach ($decoded as $it) {
        if (!is_array($it)) continue;
        for($c=1; $c<=$settings['ca_count']; $c++) { if(isset($it["ca{$c}"]) && $it["ca{$c}"] > 0) $hasCA = true; }
        $subjects[] = $it;
    }
}
$position = $result['position'] ? ordinal($result['position']) : null;
require __DIR__ . '/includes/header.php';
?>
<section class="max-w-3xl mx-auto px-4 py-8">
<div class="flex items-center justify-between gap-3 mb-4 no-print"><a href="<?= url('index.php') ?>" class="text-sm font-semibold text-royal-700 hover:underline">&larr; Check another result</a><button type="button" onclick="window.print()" class="rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2.5 shadow">Print Dossier</button></div>
<div class="dossier p-6 sm:p-10">
<div class="text-center border-b-2 border-royal-800 pb-5">
<?php if (!empty($student['logo_url'])): ?><img src="<?= e($student['logo_url']) ?>" alt="Logo" class="h-16 mx-auto mb-2 object-contain"><?php endif; ?>
<h1 class="text-xl sm:text-2xl font-extrabold uppercase tracking-wide text-royal-900"><?= e($student['school_name']) ?></h1>
<p class="text-[11px] uppercase tracking-[0.25em] text-slate-500 mt-1">Termly Result Dossier</p>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 mt-6 text-sm">
<div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Student Name</span><span class="font-bold text-slate-800"><?= e($student['surname'] . ' ' . $student['first_name']) ?></span></div>
<div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Admission No.</span><span class="font-bold text-slate-800"><?= e($student['admission_no']) ?></span></div>
<div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Class</span><span class="font-bold text-slate-800"><?= e($student['class_name']) ?></span></div>
<div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Session</span><span class="font-bold text-slate-800"><?= e($result['session']) ?></span></div>
<div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Term</span><span class="font-bold text-slate-800"><?= e($result['term']) ?></span></div>
<div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Position</span><span class="font-bold text-slate-800"><?= $position ? e($position) : '-' ?></span></div>
</div>
<div class="mt-7 overflow-x-auto">
<table class="result-table">
<thead><tr><th style="width:42px">#</th><th>Subject</th>
<?php if ($hasCA): for($c=1; $c<=$settings['ca_count']; $c++): ?><th style="width:50px" class="text-center">CA<?= $c ?></th><?php endfor; endif; ?>
<th style="width:50px" class="text-center">Exam</th><th style="width:50px" class="text-center font-bold">Total</th><th style="width:50px">Grade</th><th style="width:100px">Remark</th></tr></thead>
<tbody>
<?php if (empty($subjects)): ?><tr><td colspan="8" class="text-center text-slate-500 py-6">No subjects recorded.</td></tr>
<?php else: foreach ($subjects as $i => $s): $g = grade_for($s['total'], $settings['grading']); ?>
<tr><td><?= $i+1 ?></td><td class="font-medium"><?= e($s['subject']) ?></td>
<?php if ($hasCA): for($c=1; $c<=$settings['ca_count']; $c++): ?><td class="text-center"><?= e(isset($s["ca{$c}"]) && $s["ca{$c}"] > 0 ? number_format($s["ca{$c}"],1) : '-') ?></td><?php endfor; endif; ?>
<td class="text-center"><?= e(number_format($s['exam'],1)) ?></td><td class="text-center font-bold text-royal-900"><?= e(number_format($s['total'],1)) ?></td><td class="font-bold"><?= e($g['grade']) ?></td><td class="text-slate-600"><?= e($g['remark']) ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
<div class="rounded-xl bg-royal-50 border border-royal-200 px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-royal-700 font-bold">Total Score</p><p class="text-lg font-extrabold text-royal-900"><?= e(number_format((float)$result['total_score'],1)) ?></p></div>
<div class="rounded-xl bg-orange-50 border border-orange-200 px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-orange-700 font-bold">Average</p><p class="text-lg font-extrabold text-orange-700"><?= e(number_format((float)$result['average'],2)) ?>%</p></div>
<div class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Position</p><p class="text-lg font-extrabold text-slate-800"><?= $position ? e($position) : '-' ?></p></div>
<div class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Subjects</p><p class="text-lg font-extrabold text-slate-800"><?= count($subjects) ?></p></div>
</div>
<div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Teacher's Remark</p><p class="text-sm text-slate-800 italic"><?= $result['teacher_remark'] !== '' && $result['teacher_remark'] !== null ? e($result['teacher_remark']) : 'No remark recorded.' ?></p></div>
<div class="mt-10 flex items-end justify-between text-xs text-slate-500"><div class="border-t border-slate-400 w-40 pt-1 text-center">Class Teacher</div><div class="border-t border-slate-400 w-40 pt-1 text-center">Principal</div></div>
<p class="mt-8 text-center text-[10px] text-slate-400">Generated by <?= e(APP_NAME) ?> &middot; <?= e(date('j M Y, g:i A')) ?></p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
