<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['teacher_id'])) redirect('teacher/dashboard.php');

$token = get('token');
$error = '';

// 1. Verify Token Exists
if (!$token) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Access Denied</h2><p>Invalid or missing access link. Please contact your administrator.</p></div>");
}

// 2. Verify Token is Valid and Not Expired
$stmt = db()->prepare("SELECT t.*, s.settings_json FROM teachers t JOIN schools s ON s.id=t.school_id WHERE t.magic_token=? LIMIT 1");
$stmt->execute([$token]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Access Denied</h2><p>Invalid access link.</p></div>");
}

if ($teacher['token_expires_at'] && strtotime($teacher['token_expires_at']) < time()) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Link Expired</h2><p>This secure link has expired. Please ask your administrator to generate a new one.</p></div>");
}

// 3. Process Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $error = 'Session expired. Try again.'; }
    else {
        $email = post('email'); $password = post('password');
        
        // SECURITY: Ensure the email matches the teacher assigned to this token!
        if (strtolower($email) !== strtolower($teacher['email'])) {
            $error = 'These credentials do not match this access link.';
        } elseif (password_verify($password, $teacher['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['teacher_id'] = (int)$teacher['id'];
            redirect('teacher/dashboard.php');
        } else { 
            $error = 'Invalid email or password.'; 
        }
    }
}

$pageTitle = 'Teacher Login'; $navType = 'public';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-14">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-7">
        <h1 class="text-xl font-extrabold text-royal-900">Secure Teacher Login</h1>
        <p class="text-sm text-slate-500 mt-1 mb-6">Welcome, <?= e($teacher['name']) ?>. Please enter your credentials.</p>
        <?php if ($error): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Email</label><input name="email" type="email" required class="w-full rounded border px-3 py-2 text-sm" value="<?= e($teacher['email']) ?>" readonly></div>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Password</label><input name="password" type="password" required class="w-full rounded border px-3 py-2 text-sm"></div>
            <button type="submit" class="w-full rounded bg-royal-800 text-white font-bold py-3 text-sm">Log In</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>