<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (empty($_SESSION['pending_parent_link'])) redirect('index.php');
$studentId = (int)$_SESSION['pending_parent_link']; $pdo = db();
$student = $pdo->query("SELECT surname, first_name FROM students WHERE id=$studentId")->fetch();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $error = 'Session expired.'; }
    else {
        $name = post('name'); $email = post('email'); $pass = post('password');
        if ($name && $email && $pass) {
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO parents (name, email, password_hash) VALUES (?,?,?) RETURNING id"); $stmt->execute([$name, $email, $hash]);
                $pid = $stmt->fetchColumn();
                $pdo->prepare("INSERT INTO parent_students (parent_id, student_id) VALUES (?, ?)")->execute([$pid, $studentId]);
                $_SESSION['parent_id'] = $pid; unset($_SESSION['pending_parent_link'], $_SESSION['pending_check']);
                set_flash('success', 'Account created! You can now view your child\'s results anytime.');
                redirect('parent/dashboard.php');
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') { $error = 'Email already exists. Please log in instead.'; }
                else { $error = 'Error creating account.'; }
            }
        } else { $error = 'All fields are required.'; }
    }
}
$pageTitle = 'Create Parent Account'; $navType = 'public';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-7">
        <div class="text-center mb-6">
            <div class="mx-auto h-12 w-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl mb-3"><i class="fa-solid fa-check"></i></div>
            <h1 class="text-2xl font-extrabold text-royal-900">PIN Verified!</h1>
            <p class="text-sm text-slate-500 mt-1">Create a free account to save <strong><?= e($student['surname']) ?>'s</strong> result and view it anytime without buying a new PIN.</p>
        </div>
        <?php if ($error): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 text-center"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Your Full Name</label><input name="name" type="text" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label><input name="email" type="email" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Create Password</label><input name="password" type="password" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            <button type="submit" class="w-full rounded-lg bg-green-600 hover:bg-green-700 text-white font-bold py-3 text-sm shadow">Create Account & View Result</button>
        </form>
        <div class="mt-4 text-center"><a href="<?= url('parent/login.php') ?>" class="text-xs text-royal-600 font-bold hover:underline">Already have an account? Log in</a></div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>