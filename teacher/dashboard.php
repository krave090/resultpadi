<?php
require_once __DIR__ . '/../includes/teacher_auth.php';
$teacher = require_teacher(); $pdo = db(); $sid = (int)$teacher['school_id'];
$settings = get_school_settings($teacher['settings_json'] ?? null);
$current_session = post('session', session_options()[1] ?? date('Y').'/'.(date('Y')+1));
$current_term = post('term', 'First Term');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save') {
    $studentIds = $_POST['student_ids'] ?? [];
    $remarks = $_POST['remarks'] ?? [];
    foreach ($studentIds as $st_id) {
        $ca_total = 0; $ca_data = [];
        for ($c = 1; $c <= $settings['ca_count']; $c++) {
            $val = (float)($_POST["ca{$c}"][$st_id] ?? 0); $ca_data["ca{$c}"] = $val; $ca_total += $val;
        }
        $exam = (float)($_POST["exam"][$st_id] ?? 0); $total = $ca_total + $exam;
        $remark = $remarks[$st_id] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO teacher_scores (school_id, student_id, teacher_id, subject, session, term, ca_json, exam, total, remark) 
                               VALUES (?,?,?,?,?,?,?,?,?,?) ON CONFLICT (student_id, subject, session, term) DO UPDATE SET ca_json=EXCLUDED.ca_json, exam=EXCLUDED.exam, total=EXCLUDED.total, remark=EXCLUDED.remark");
        $stmt->execute([$sid, $st_id, $teacher['id'], $teacher['subject_assigned'], $current_session, $current_term, json_encode($ca_data), $exam, $total, $remark]);
    }
    set_flash('success', 'Marks and remarks saved.');
    redirect('teacher/dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id=? AND class_name=? ORDER BY surname ASC"); $stmt->execute([$sid, $teacher['class_assigned']]); $students = $stmt->fetchAll();
$existing = [];
$stmt = $pdo->prepare("SELECT * FROM teacher_scores WHERE teacher_id=? AND session=? AND term=?"); $stmt->execute([$teacher['id'], $current_session, $current_term]);
while ($row = $stmt->fetch()) { $existing[$row['student_id']] = $row; }

$pageTitle = 'Teacher Dashboard'; $navType = 'teacher';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900"><?= e($teacher['name']) ?></h1>
    <p class="text-sm text-slate-600 mb-6">Class: <strong><?= e($teacher['class_assigned']) ?></strong> | Subject: <strong><?= e($teacher['subject_assigned']) ?></strong></p>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save">
            <div class="flex gap-4 mb-6">
                <select name="session" class="rounded border px-3 py-2 text-sm" onchange="this.form.submit()"><?php foreach(session_options() as $o): ?><option <?= $o===$current_session?'selected':'' ?>><?= $o ?></option><?php endforeach; ?></select>
                <select name="term" class="rounded border px-3 py-2 text-sm" onchange="this.form.submit()"><?php foreach(term_options() as $o): ?><option <?= $o===$current_term?'selected':'' ?>><?= $o ?></option><?php endforeach; ?></select>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="text-left p-2">Student</th><?php for($c=1; $c<=$settings['ca_count']; $c++): ?><th class="p-2 w-16 text-center">CA<?= $c ?></th><?php endfor; ?><th class="p-2 w-16 text-center">Exam</th><th class="p-2 text-left">Subject Remark</th><th class="p-2 w-16 text-center font-bold">Total</th></tr></thead>
                <tbody>
                    <?php foreach($students as $s): 
                        $saved = $existing[$s['id']] ?? ['ca_json'=>'[]','exam'=>0,'total'=>0,'remark'=>''];
                        $ca_data = json_decode($saved['ca_json'], true);
                    ?>
                    <tr class="border-t hover:bg-slate-50">
                        <td class="p-2 font-medium"><?= e($s['surname'].' '.$s['first_name']) ?><input type="hidden" name="student_ids[]" value="<?= $s['id'] ?>"></td>
                        <?php for($c=1; $c<=$settings['ca_count']; $c++): ?><td class="p-2"><input type="number" name="ca<?= $c ?>[<?= $s['id'] ?>]" value="<?= e($ca_data["ca{$c}"] ?? '') ?>" class="ca-input w-full rounded border px-2 py-1 text-center"></td><?php endfor; ?>
                        <td class="p-2"><input type="number" name="exam[<?= $s['id'] ?>]" value="<?= e($saved['exam']) ?>" class="exam-input w-full rounded border px-2 py-1 text-center"></td>
                        <td class="p-2"><input type="text" name="remarks[<?= $s['id'] ?>]" value="<?= e($saved['remark']) ?>" placeholder="Remark..." class="w-full rounded border px-2 py-1 text-sm"></td>
                        <td class="p-2 text-center"><span class="row-total font-bold text-royal-900"><?= e($saved['total']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="mt-6 rounded-lg bg-orange-500 text-white font-bold px-6 py-2.5">Save Marks & Remarks</button>
        </form>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tbody tr').forEach(row => {
        row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', function() {
            let total = 0; row.querySelectorAll('.ca-input').forEach(i => total += parseFloat(i.value)||0); total += parseFloat(row.querySelector('.exam-input').value)||0;
            row.querySelector('.row-total').textContent = total.toFixed(1).replace(/\.0$/, '');
        }));
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>