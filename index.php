<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
try { $schools = $pdo->query("SELECT id, school_name FROM schools WHERE status='active' ORDER BY school_name ASC")->fetchAll(); }
catch (PDOException $e) { $schools = []; }
$errMap = ['invalid'=>'No student matched those details.','missing'=>'Please enter all fields.','noresult'=>'Result not ready.'];
$errCode = get('error'); $errorMsg = $errMap[$errCode] ?? '';
$pageTitle = 'Check Your Result'; $navType = 'public';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-gradient-to-br from-royal-900 via-royal-800 to-royal-600 text-white">
<div class="max-w-6xl mx-auto px-4 py-16 text-center">
<span class="inline-block bg-orange-500 text-white text-[11px] font-bold uppercase tracking-widest px-3 py-1 rounded-full">ResultPadi</span>
<h1 class="mt-5 text-3xl sm:text-5xl font-extrabold leading-tight">Your School's Result Partner.</h1>
<p class="mt-4 text-blue-100 max-w-2xl mx-auto text-sm sm:text-base">View and print your child's termly result dossier from the comfort of your home.</p>
</div></section>
<section class="max-w-xl mx-auto px-4 -mt-10 pb-16">
<div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-8">
<h2 class="text-lg font-bold text-royal-900">Check Result</h2>
<p class="text-sm text-slate-500 mt-1 mb-5">Select your school and enter the student's details.</p>
<?php if ($errorMsg): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3"><?= e($errorMsg) ?></div><?php endif; ?>
<?php if (empty($schools)): ?>
<div class="rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-sm px-4 py-3">No school is currently active.</div>
<?php else: ?>
<form method="post" action="<?= url('check.php') ?>" class="space-y-4">
<?= csrf_field() ?>
<div>
<label for="school_id" class="block text-sm font-semibold text-slate-700 mb-1">School Name</label>
<select id="school_id" name="school_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-base outline-none focus:border-royal-600">
<option value="">â€” Select your school â€”</option>
<?php foreach ($schools as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['school_name']) ?></option><?php endforeach; ?>
</select></div>
<div>
<label for="admission_no" class="block text-sm font-semibold text-slate-700 mb-1">Admission Number</label>
<input id="admission_no" name="admission_no" type="text" required placeholder="e.g. RPD/2025/001" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-base outline-none focus:border-royal-600">
</div>
<div>
<label for="result_type" class="block text-sm font-semibold text-slate-700 mb-1">Result Type</label>
<select id="result_type" name="result_type" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-base outline-none focus:border-royal-600">
<option value="termly">Termly Result</option>
<option value="annual">Annual / Cumulative Report</option>
</select>
</div>
<button type="submit" class="w-full rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold py-3.5 text-base transition shadow-lg">Proceed to PIN</button>
</form>
<?php endif; ?>
</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>