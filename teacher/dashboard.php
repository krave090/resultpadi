<?php
require_once __DIR__ . '/../includes/teacher_auth.php';
$teacher = require_teacher(); $pdo = db(); $sid = (int)$teacher['school_id'];
$settings = get_school_settings($teacher['settings_json'] ?? null);
$max_possible = ($settings['ca_max'] * $settings['ca_count']) + $settings['exam_max'];

$current_session = post('session', session_options()[1] ?? date('Y').'/'.(date('Y')+1));
$current_term = post('term', 'First Term');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save') {
    $studentIds = $_POST['student_ids'] ?? [];
    foreach ($studentIds as $st_id) {
        $ca_total = 0; $ca_data = [];
        for ($c = 1; $c <= $settings['ca_count']; $c++) {
            $val = (float)($_POST["ca{$c}"][$st_id] ?? 0);
            $ca_data["ca{$c}"] = $val; $ca_total += $val;
        }
        $exam = (float)($_POST["exam"][$st_id] ?? 0);
        $total = $ca_total + $exam;
        
        $stmt = $pdo->prepare("INSERT INTO teacher_scores (school_id, student_id, teacher_id, subject, session, term, ca_json, exam, total) 
                               VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ca_json=VALUES(ca_json), exam=VALUES(exam), total=VALUES(total)");
        $stmt->execute([$sid, $st_id, $teacher['id'], $teacher['subject_assigned'], $current_session, $current_term, json_encode($ca_data), $exam, $total]);
        
        // AUTO-SYNC LOGIC
        if (!empty($settings['auto_sync'])) {
            compile_student_result($pdo, $st_id, $sid, $current_session, $current_term);
        }
    }
    if (!empty($settings['auto_sync'])) {
        recalculate_positions($pdo, $sid, $current_session, $current_term);
        set_flash('success', 'Marks saved and Auto-Synced to Parent Portal!');
    } else {
        set_flash('success', 'Marks saved. Waiting for Admin approval to publish.');
    }
    redirect('teacher/dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id=? AND class_name=? ORDER BY surname ASC");
$stmt->execute([$sid, $teacher['class_assigned']]);
$students = $stmt->fetchAll();

$existing_scores = [];
$stmt = $pdo->prepare("SELECT * FROM teacher_scores WHERE teacher_id=? AND session=? AND term=?");
$stmt->execute([$teacher['id'], $current_session, $current_term]);
while ($row = $stmt->fetch()) {
    $ca_data = json_decode($row['ca_json'], true);
    $existing_scores[$row['student_id']] = ['ca' => is_array($ca_data) ? $ca_data : [], 'exam' => $row['exam']];
}

$pageTitle = 'Teacher Dashboard'; $navType = 'teacher';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-royal-900"><?= e($teacher['name']) ?></h1>
            <p class="text-sm text-slate-600">Class: <strong><?= e($teacher['class_assigned']) ?></strong> | Subject: <strong><?= e($teacher['subject_assigned']) ?></strong></p>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="bg-white rounded-xl border border-amber-300 shadow-sm p-8 text-center">
            <h2 class="text-lg font-bold text-amber-800">No Students Found</h2>
            <p class="text-sm text-amber-700 mt-2">There are currently no students registered in class <strong><?= e($teacher['class_assigned']) ?></strong>.</p>
        </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <div class="flex gap-4 mb-6">
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Session</label>
                    <select name="session" class="rounded border px-3 py-2 text-sm outline-none focus:border-royal-600" onchange="this.form.submit()"><?php foreach(session_options() as $o): ?><option <?= $o === $current_session ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select>
                </div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Term</label>
                    <select name="term" class="rounded border px-3 py-2 text-sm outline-none focus:border-royal-600" onchange="this.form.submit()"><?php foreach(term_options() as $o): ?><option <?= $o === $current_term ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select>
                </div>
            </div>
            
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left p-2">Student</th>
                        <?php for($c=1; $c<=$settings['ca_count']; $c++): ?><th class="p-2 w-20 text-center">CA<?= $c ?> (<?= $settings['ca_max'] ?>)</th><?php endfor; ?>
                        <th class="p-2 w-20 text-center">Exam (<?= $settings['exam_max'] ?>)</th>
                        <th class="p-2 w-20 text-center font-bold text-royal-800">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): 
                        $saved = $existing_scores[$s['id']] ?? ['ca'=>[], 'exam'=>''];
                        $initial_total = (float)array_sum($saved['ca']) + (float)$saved['exam'];
                    ?>
                    <tr class="border-t hover:bg-slate-50">
                        <td class="p-2 font-medium"><?= e($s['surname'].' '.$s['first_name']) ?><input type="hidden" name="student_ids[]" value="<?= $s['id'] ?>"></td>
                        <?php for($c=1; $c<=$settings['ca_count']; $c++): ?>
                            <td class="p-2"><input type="number" name="ca<?= $c ?>[<?= $s['id'] ?>]" value="<?= e($saved['ca']["ca{$c}"] ?? '') ?>" max="<?= $settings['ca_max'] ?>" class="ca-input w-full rounded border px-2 py-1 outline-none text-center focus:border-royal-600"></td>
                        <?php endfor; ?>
                        <td class="p-2"><input type="number" name="exam[<?= $s['id'] ?>]" value="<?= e($saved['exam']) ?>" max="<?= $settings['exam_max'] ?>" class="exam-input w-full rounded border px-2 py-1 outline-none text-center focus:border-royal-600"></td>
                        <td class="p-2 text-center"><span class="row-total font-bold text-royal-900 text-base"><?= e($initial_total) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="mt-6 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold px-6 py-2.5 shadow transition">Save Marks</button>
        </form>
    </div>
    <?php endif; ?>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rows = document.querySelectorAll('tbody tr');
    function recalcRow(row) {
        var total = 0;
        row.querySelectorAll('.ca-input').forEach(function(inp) { total += parseFloat(inp.value) || 0; });
        total += parseFloat(row.querySelector('.exam-input').value) || 0;
        row.querySelector('.row-total').textContent = total.toFixed(1).replace(/\.0$/, '');
    }
    rows.forEach(function(row) {
        row.querySelectorAll('input').forEach(function(inp) { inp.addEventListener('input', function() { recalcRow(row); }); });
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>