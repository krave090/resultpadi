<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (empty($_SESSION['pending_check'])) redirect('index.php');
$pdo = db(); $check = $_SESSION['pending_check'];
$stmt = $pdo->prepare("SELECT s.*, sc.school_name FROM students s JOIN schools sc ON sc.id=s.school_id WHERE s.id=?"); $stmt->execute([$check['student_id']]); $student = $stmt->fetch();
if (!$student) { unset($_SESSION['pending_check']); redirect('index.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $error = 'Session expired.'; }
    else {
        $pin_code = strtoupper(post('pin_code'));
        $stmt = $pdo->prepare("SELECT * FROM pins WHERE pin_code=? LIMIT 1"); $stmt->execute([$pin_code]); $pin = $stmt->fetch();
        if (!$pin) { $error = 'Invalid PIN.'; }
        elseif ($pin['is_used'] == 1 && $pin['student_id'] != $student['id']) { $error = 'This PIN has already been used for another student.'; }
        else {
            if ($pin['is_used'] == 0) {
                $stmt = $pdo->prepare("SELECT id FROM results WHERE student_id=? ORDER BY id DESC LIMIT 1"); $stmt->execute([$student['id']]); $res_id = $stmt->fetchColumn();
                $pdo->prepare("UPDATE pins SET is_used=1, used_at=NOW(), student_id=?, result_id=? WHERE id=?")->execute([$student['id'], $res_id, $pin['id']]);
            }
            if (empty($_SESSION['parent_id'])) {
                $_SESSION['pending_parent_link'] = $student['id'];
                redirect('parent/register.php');
            } else {
                $pdo->prepare("INSERT INTO parent_students (parent_id, student_id) VALUES (?, ?) ON CONFLICT DO NOTHING")->execute([(int)$_SESSION['parent_id'], $student['id']]);
                unset($_SESSION['pending_check']);
                if ($check['result_type'] === 'annual') { header('Location: ' . url('annual_report.php?id=' . $student['id'])); exit; }
                else {
                    $stmt = $pdo->prepare("SELECT * FROM results WHERE student_id=? ORDER BY id DESC LIMIT 1"); $stmt->execute([$student['id']]); $result = $stmt->fetch();
                    $q = http_build_query(['id'=>$student['id'], 'session'=>$result['session'], 'term'=>$result['term']]);
                    header('Location: ' . url('result.php?' . $q)); exit;
                }
            }
        }
    }
}
$pageTitle = 'Enter PIN'; $navType = 'public';
require __DIR__ . '/includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 p-8 text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange-500 to-royal-600"></div>
        <div class="mx-auto h-14 w-14 rounded-full bg-royal-100 text-royal-700 flex items-center justify-center text-2xl font-black mb-4 border-4 border-white shadow-sm"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>
        <h1 class="text-2xl font-extrabold text-royal-900">Unlock Result</h1>
        <p class="text-sm text-slate-600 mt-2 mb-6">Student: <strong><?= e($student['surname'] . ' ' . $student['first_name']) ?></strong></p>
        <?php if ($error): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 font-semibold"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <div><input name="pin_code" type="text" required placeholder="Enter 12-digit PIN" autocomplete="off" class="w-full text-center tracking-[0.2em] text-xl font-bold rounded-xl border border-slate-300 px-4 py-4 outline-none focus:border-royal-600 uppercase bg-slate-50"></div>
            <button type="submit" class="w-full rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 text-base transition shadow-lg">Verify & View</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>