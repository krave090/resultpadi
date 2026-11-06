<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['parent_id'])) redirect('parent/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $error = 'Session expired.'; }
    else {
        $email = post('email'); $pass = post('password');
        $stmt = db()->prepare("SELECT * FROM parents WHERE email=? LIMIT 1"); $stmt->execute([$email]); $p = $stmt->fetch();
        if ($p && password_verify($pass, $p['password_hash'])) {
            session_regenerate_id(true); $_SESSION['parent_id'] = (int)$p['id']; redirect('parent/dashboard.php');
        } else { $error = 'Invalid email or password.'; }
    }
}
$pageTitle = 'Parent Login'; $navType = 'public';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-7">
        <h1 class="text-2xl font-extrabold text-royal-900 text-center mb-1">Parent Portal</h1>
        <p class="text-sm text-slate-500 text-center mb-6">Log in to view your children's results.</p>
        <?php if ($error): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 text-center"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Email</label><input name="email" type="email" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-semibold text-slate-700 mb-1">Password</label><input name="password" type="password" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            <button type="submit" class="w-full rounded-lg bg-royal-800 hover:bg-royal-900 text-white font-bold py-3 text-sm shadow">Log In</button>
        </form>
        <p class="text-center text-sm mt-4 text-slate-500">Don't have an account? <br><span class="font-bold text-royal-700">Unlock a result with a PIN to create one.</span></p>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>