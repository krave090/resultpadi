<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['unlocked_results'])) $_SESSION['unlocked_results'] = [];
$studentId = (int)get('id', 0); $session = get('session'); $term = get('term');
if (!in_array($studentId, $_SESSION['unlocked_results'])) { $_SESSION['pending_check'] = ['student_id' => $studentId, 'school_id' => 0]; set_flash('error', 'Please enter your PIN.'); redirect('pin.php'); }

$pdo = db(); $student = null; $result = null; $subjects = []; $errorMsg = '';
$stmt = $pdo->prepare("SELECT s.*, sc.school_name, sc.logo_url, sc.settings_json FROM students s INNER JOIN schools sc ON sc.id=s.school_id WHERE s.id=? LIMIT 1"); $stmt->execute([$studentId]); $student = $stmt->fetch();
if (!$student) { $errorMsg = 'Student not found.'; } else {
    if ($session !== '' && $term !== '') { $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id=? AND session=? AND term=? ORDER BY id DESC LIMIT 1"); $stmt->execute([$studentId, $session, $term]); }
    else { $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id=? ORDER BY id DESC LIMIT 1"); $stmt->execute([$studentId]); }
    $result = $stmt->fetch(); if (!$result) $errorMsg = 'No result found.';
}
$pageTitle = 'Result Dossier'; $navType = 'public';
if ($errorMsg !== '') { require __DIR__ . '/includes/header.php'; echo "<div class='text-center py-20 text-red-600'>$errorMsg</div>"; require __DIR__ . '/includes/footer.php'; exit; }

try { $pdo->prepare("UPDATE results SET views=views+1 WHERE id=?")->execute([(int)$result['id']]); } catch (PDOException $e) {}
$settings = get_school_settings($student['settings_json'] ?? null);
$template = $settings['template_style'] ?? 'classic';
$affective = json_decode($result['affective_json'], true) ?: [];
$psychomotor = json_decode($result['psychomotor_json'], true) ?: [];

$decoded = json_decode($result['subjects_json'], true); $hasCA = false;
if (is_array($decoded)) { foreach ($decoded as $it) { if (!is_array($it)) continue; for($c=1; $c<=$settings['ca_count']; $c++) { if(isset($it["ca{$c}"]) && $it["ca{$c}"] > 0) $hasCA = true; } $subjects[] = $it; } }
$position = $result['position'] ? ordinal($result['position']) : null;

$header_border = $template === 'modern' ? 'border-orange-500' : ($template === 'minimal' ? 'border-slate-900' : 'border-royal-800');
$header_text = $template === 'modern' ? 'text-orange-600' : ($template === 'minimal' ? 'text-slate-900' : 'text-royal-900');
$bg_summary = $template === 'modern' ? 'bg-orange-50' : ($template === 'minimal' ? 'bg-slate-100' : 'bg-royal-50');

require __DIR__ . '/includes/header.php';
?>
<section class="max-w-3xl mx-auto px-4 py-8">
<div class="flex items-center justify-between gap-3 mb-4 no-print"><a href="<?= url('index.php') ?>" class="text-sm font-semibold text-royal-700 hover:underline">&larr; Check another result</a><button type="button" onclick="window.print()" class="rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2.5 shadow">Print Dossier</button></div>
<div class="dossier p-6 sm:p-10 bg-white shadow-xl rounded-xl border border-slate-200">
    <div class="text-center border-b-4 <?= $header_border ?> pb-5"><?php if (!empty($student['logo_url'])): ?><img src="<?= e($student['logo_url']) ?>" alt="Logo" class="h-20 mx-auto mb-3 object-contain"><?php endif; ?><h1 class="text-2xl sm:text-3xl font-extrabold uppercase tracking-wide <?= $header_text ?>"><?= e($student['school_name']) ?></h1><p class="text-xs uppercase tracking-[0.25em] text-slate-500 mt-1">Termly Result Dossier</p></div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 mt-6 text-sm">
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Student Name</span><span class="font-bold text-slate-800"><?= e($student['surname'] . ' ' . $student['first_name']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Admission No.</span><span class="font-bold text-slate-800 font-mono"><?= e($student['admission_no']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Class</span><span class="font-bold text-slate-800"><?= e($student['class_name']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Session</span><span class="font-bold text-slate-800"><?= e($result['session']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Term</span><span class="font-bold text-slate-800"><?= e($result['term']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Class Position</span><span class="font-bold text-slate-800"><?= $position ? e($position) : '-' ?></span></div>
    </div>

    <div class="mt-7 overflow-x-auto">
        <table class="result-table w-full">
            <thead><tr><th style="width:42px">#</th><th>Subject</th><?php if ($hasCA): for($c=1; $c<=$settings['ca_count']; $c++): ?><th style="width:50px" class="text-center">CA<?= $c ?></th><?php endfor; endif; ?><th style="width:50px" class="text-center">Exam</th><th style="width:50px" class="text-center font-bold">Total</th><th style="width:50px">Grade</th><th>Subject Remark</th></tr></thead>
            <tbody>
            <?php if (empty($subjects)): ?><tr><td colspan="8" class="text-center text-slate-500 py-6">No subjects recorded.</td></tr>
            <?php else: foreach ($subjects as $i => $s): $g = grade_for($s['total'], $settings['grading']); ?>
            <tr><td><?= $i+1 ?></td><td class="font-medium"><?= e($s['subject']) ?></td>
            <?php if ($hasCA): for($c=1; $c<=$settings['ca_count']; $c++): ?><td class="text-center"><?= e(isset($s["ca{$c}"]) && $s["ca{$c}"] > 0 ? number_format($s["ca{$c}"],1) : '-') ?></td><?php endfor; endif; ?>
            <td class="text-center"><?= e(number_format($s['exam'],1)) ?></td><td class="text-center font-bold text-royal-900"><?= e(number_format($s['total'],1)) ?></td><td class="font-bold text-center"><?= e($g['grade']) ?></td>
            <td class="text-slate-600 text-xs italic"><?= e($s['remark'] ?? '') ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
        <div class="<?= $bg_summary ?> border border-slate-200 rounded-xl px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Term Total</p><p class="text-lg font-extrabold text-slate-800"><?= e(number_format((float)$result['total_score'],1)) ?></p></div>
        <div class="<?= $bg_summary ?> border border-slate-200 rounded-xl px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Term Avg</p><p class="text-lg font-extrabold text-slate-800"><?= e(number_format((float)$result['average'],2)) ?>%</p></div>
        <div class="<?= $bg_summary ?> border border-slate-200 rounded-xl px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Position</p><p class="text-lg font-extrabold text-slate-800"><?= $position ? e($position) : '-' ?></p></div>
        <div class="<?= $bg_summary ?> border border-slate-200 rounded-xl px-3 py-3 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Subjects</p><p class="text-lg font-extrabold text-slate-800"><?= count($subjects) ?></p></div>
    </div>

    <!-- Affective & Psychomotor Display -->
    <?php if(!empty($affective) || !empty($psychomotor)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 border-t pt-6">
        <?php if(!empty($affective)): ?>
        <div>
            <h3 class="text-xs font-bold text-royal-900 uppercase mb-2">Affective Domain</h3>
            <table class="w-full text-xs text-slate-700">
                <?php foreach($affective as $k=>$v): ?><tr><td class="capitalize py-1 border-b border-dashed"><?= $k ?></td><td class="text-right font-bold border-b border-dashed"><?= $v ?></td></tr><?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        <?php if(!empty($psychomotor)): ?>
        <div>
            <h3 class="text-xs font-bold text-royal-900 uppercase mb-2">Psychomotor</h3>
            <table class="w-full text-xs text-slate-700">
                <?php foreach($psychomotor as $k=>$v): ?><tr><td class="capitalize py-1 border-b border-dashed"><?= $k ?></td><td class="text-right font-bold border-b border-dashed"><?= $v ?></td></tr><?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Class Teacher's Remark</p><p class="text-sm text-slate-800 italic"><?= $result['teacher_remark'] !== '' && $result['teacher_remark'] !== null ? e($result['teacher_remark']) : 'No remark recorded.' ?></p></div>
    <div class="mt-10 flex items-end justify-between text-xs text-slate-500"><div class="border-t border-slate-400 w-40 pt-1 text-center">Class Teacher</div><div class="border-t border-slate-400 w-40 pt-1 text-center">Principal</div></div>
    <p class="mt-8 text-center text-[10px] text-slate-400">Generated by <?= e(APP_NAME) ?> &middot; <?= e(date('j M Y, g:i A')) ?></p>
</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>