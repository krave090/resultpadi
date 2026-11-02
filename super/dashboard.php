<?php
require_once __DIR__ . '/../includes/super_auth.php';
require_super(); $pdo = db();
$totalSchools = (int)$pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$activeSchools = (int)$pdo->query("SELECT COUNT(*) FROM schools WHERE status='active'")->fetchColumn();
$totalResults = (int)$pdo->query("SELECT COUNT(*) FROM results")->fetchColumn();
$totalChecks = (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM results")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$recent = $pdo->query("SELECT * FROM schools ORDER BY id DESC LIMIT 6")->fetchAll();
$pageTitle = 'Super Admin'; $navType = 'super';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
<div class="flex justify-between items-center mb-6">
    <div><h1 class="text-2xl font-extrabold text-royal-900">Super Admin Dashboard</h1><p class="text-sm text-slate-500">ResultPadi SaaS overview.</p></div>
    <a href="<?= url('super/pins.php') ?>" class="bg-orange-500 hover:bg-orange-600 text-white font-bold px-4 py-2 rounded-lg text-sm shadow">Generate PINs</a>
</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Total Schools</p><p class="text-3xl font-extrabold text-royal-900 mt-2"><?= e($totalSchools) ?></p></div>
<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Active Schools</p><p class="text-3xl font-extrabold text-green-600 mt-2"><?= e($activeSchools) ?></p></div>
<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Total Students</p><p class="text-3xl font-extrabold text-royal-700 mt-2"><?= e($totalStudents) ?></p></div>
<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Results Checked</p><p class="text-3xl font-extrabold text-orange-500 mt-2"><?= e($totalChecks) ?></p></div>
</div>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mt-6 overflow-hidden">
<div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between"><h2 class="font-bold text-slate-800">Recent Schools</h2><a href="<?= url('super/schools.php') ?>" class="text-xs font-bold text-royal-700 hover:underline">Manage Schools &rarr;</a></div>
<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider"><tr><th class="text-left px-5 py-3">School</th><th class="text-left px-5 py-3">Admin Email</th><th class="text-left px-5 py-3">Status</th><th class="text-left px-5 py-3">Paid Until</th></tr></thead><tbody>
<?php if (empty($recent)): ?><tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No schools yet.</td></tr>
<?php else: foreach ($recent as $s): ?>
<tr class="border-t border-slate-100"><td class="px-5 py-3 font-medium"><?= e($s['school_name']) ?></td><td class="px-5 py-3"><?= e($s['admin_email']) ?></td><td class="px-5 py-3"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $s['status']==='active'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' ?>"><?= e(ucfirst($s['status'])) ?></span></td><td class="px-5 py-3"><?= e(nice_date($s['termly_fee_paid_until'])) ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>