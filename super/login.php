<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['super_admin_id'])) redirect('super/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $error = 'Session expired. Try again.'; }
    else {
        $email = post('email'); $password = post('password');
        if ($email === '' || $password === '') $error = 'Email and password required.';
        else {
            $stmt = db()->prepare("SELECT * FROM super_admins WHERE email=? LIMIT 1");
            $stmt->execute([$email]); $a = $stmt->fetch();
            if (!$a || !password_verify($password, $a['password_hash'])) $error = 'Invalid email or password.';
            else {
                session_regenerate_id(true);
                $_SESSION['super_admin_id'] = (int)$a['id'];
                $_SESSION['super_admin_email'] = $a['email'];
                set_flash('success', 'Welcome back, Super Admin.');
                redirect('super/dashboard.php');
            }
        }
    }
}
$pageTitle = 'Super Admin Login'; $navType = 'public';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-14">
<div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-7">
<h1 class="text-xl font-extrabold text-royal-900">Super Admin Login</h1>
<p class="text-sm text-slate-500 mt-1 mb-6">ResultPadi SaaS owner portal.</p>
<?php if ($error): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= url('super/login.php') ?>" class="space-y-4">
<?= csrf_field() ?>
<div><label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
<input name="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200"></div>
<div><label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
<input name="password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200"></div>
<button type="submit" class="w-full rounded-lg bg-royal-800 hover:bg-royal-900 text-white font-bold py-3 text-sm transition">Log In</button>
</form>
<p class="text-center text-xs text-slate-400 mt-5"><a href="<?= url('index.php') ?>" class="hover:underline">Back to parent portal</a></p>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>