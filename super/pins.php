<?php
require_once __DIR__ . '/../includes/super_auth.php';
require_super(); $pdo = db();
$generated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { set_flash('error','Session expired.'); redirect('super/pins.php'); }
    $qty = max(1, min(500, (int)post('quantity')));
    
    $pdo->beginTransaction();
    try {
        for ($i = 0; $i < $qty; $i++) {
            $pin = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12)); // 12 char PIN
            $pdo->prepare("INSERT INTO pins (pin_code) VALUES (?)")->execute([$pin]);
            $generated[] = $pin;
        }
        $pdo->commit();
        set_flash('success', "$qty Universal PINs generated successfully.");
    } catch (Exception $e) {
        $pdo->rollBack(); set_flash('error', 'Error generating PINs.');
    }
}
$pageTitle = 'Generate Universal PINs'; $navType = 'super';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-4xl mx-auto px-4 py-8">
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-royal-900">Universal Scratchcard PINs</h1>
        <p class="text-sm text-slate-500">Generate PINs that work for ANY school on ResultPadi.</p>
    </div>
    <a href="<?= url('super/dashboard.php') ?>" class="text-sm font-bold text-royal-700 hover:underline">&larr; Back to Dashboard</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
<h2 class="font-bold text-slate-800 mb-4">Generate PINs</h2>
<form method="post" class="space-y-4">
<?= csrf_field() ?>
<div><label class="block text-xs font-semibold text-slate-600 mb-1">Quantity (Max 500)</label>
<input type="number" name="quantity" value="50" min="1" max="500" required class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:border-royal-600"></div>
<button type="submit" class="w-full rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 text-sm shadow transition">Generate PINs</button>
</form></div>

<?php if(!empty($generated)): ?>
<div class="bg-white rounded-xl border border-green-300 shadow-sm p-5">
<h2 class="font-bold text-green-800 mb-4">Generated PINs (Copy these)</h2>
<div class="bg-slate-50 p-4 rounded border text-sm font-mono h-64 overflow-y-auto space-y-1">
<?php foreach($generated as $p): ?><p><?= $p ?></p><?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>