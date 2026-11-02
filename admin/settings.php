<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];
$settings = get_school_settings($school['settings_json'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { set_flash('error','Session expired.'); redirect('admin/settings.php'); }
    $ca_count = max(1, min(5, (int)post('ca_count')));
    $ca_max = max(1, (int)post('ca_max'));
    $exam_max = max(1, (int)post('exam_max'));
    $auto_sync = post('auto_sync') === '1' ? 1 : 0;
    
    $grading = [];
    $grades = $_POST['grade_letter'] ?? []; $mins = $_POST['grade_min'] ?? []; $remarks = $_POST['grade_remark'] ?? [];
    for ($i = 0; $i < count($grades); $i++) {
        if (trim($grades[$i]) === '') continue;
        $grading[] = ['grade' => strtoupper(trim($grades[$i])), 'min' => (float)$mins[$i], 'remark' => trim($remarks[$i])];
    }
    usort($grading, function($a, $b) { return $b['min'] <=> $a['min']; });
    
    $new_settings = json_encode(['ca_count' => $ca_count, 'ca_max' => $ca_max, 'exam_max' => $exam_max, 'auto_sync' => $auto_sync, 'grading' => $grading]);
    $pdo->prepare("UPDATE schools SET settings_json=? WHERE id=?")->execute([$new_settings, $sid]);
    set_flash('success', 'Settings updated successfully.');
    redirect('admin/settings.php');
}

$pageTitle = 'Grading Settings'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900 mb-2">Grading Settings</h1>
    <p class="text-sm text-slate-500 mb-6">Customize how results are calculated and graded.</p>

    <form method="post" class="space-y-6">
        <?= csrf_field() ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 mb-4">Assessment Structure & Automation</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Number of CAs</label><select name="ca_count" class="w-full rounded-lg border px-3 py-2 text-sm"><?php for($i=1; $i<=4; $i++): ?><option value="<?= $i ?>" <?= $settings['ca_count'] == $i ? 'selected' : '' ?>><?= $i ?> CA<?= $i>1?'s':'' ?></option><?php endfor; ?></select></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Max Score per CA</label><input type="number" name="ca_max" value="<?= e($settings['ca_max']) ?>" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Max Exam Score</label><input type="number" name="exam_max" value="<?= e($settings['exam_max']) ?>" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_sync" value="1" <?= !empty($settings['auto_sync']) ? 'checked' : '' ?> class="w-5 h-5 text-royal-600 rounded">
                    <div>
                        <span class="block text-sm font-bold text-blue-900">Auto-Publish Results</span>
                        <span class="block text-xs text-blue-700">When checked, results are instantly calculated and pushed to the parent portal as soon as teachers submit them. If unchecked, you must manually click "Sync" on the Results page.</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4"><h2 class="font-bold text-slate-800">Grading Scale</h2><button type="button" onclick="addGradeRow()" class="text-xs font-bold text-orange-600 hover:underline">+ Add Grade</button></div>
            <div class="space-y-2" id="grade-rows">
                <?php foreach($settings['grading'] as $g): ?>
                <div class="flex gap-2 items-center">
                    <input type="text" name="grade_letter[]" value="<?= e($g['grade']) ?>" placeholder="A" class="w-16 text-center rounded border px-2 py-1 text-sm">
                    <span class="text-xs text-slate-500">Min:</span><input type="number" name="grade_min[]" value="<?= e($g['min']) ?>" placeholder="70" class="w-20 text-center rounded border px-2 py-1 text-sm">
                    <span class="text-xs text-slate-500">Remark:</span><input type="text" name="grade_remark[]" value="<?= e($g['remark']) ?>" placeholder="Excellent" class="flex-1 rounded border px-2 py-1 text-sm">
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 font-bold px-2">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="rounded-lg bg-royal-800 hover:bg-royal-900 text-white font-bold px-6 py-3 text-sm transition">Save Settings</button>
    </form>
</section>
<script>
function addGradeRow() {
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-center mt-2';
    div.innerHTML = `<input type="text" name="grade_letter[]" placeholder="A" class="w-16 text-center rounded border px-2 py-1 text-sm"><span class="text-xs text-slate-500">Min:</span><input type="number" name="grade_min[]" placeholder="70" class="w-20 text-center rounded border px-2 py-1 text-sm"><span class="text-xs text-slate-500">Remark:</span><input type="text" name="grade_remark[]" placeholder="Excellent" class="flex-1 rounded border px-2 py-1 text-sm"><button type="button" onclick="this.parentElement.remove()" class="text-red-500 font-bold px-2">&times;</button>`;
    document.getElementById('grade-rows').appendChild(div);
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>