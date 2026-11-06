<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];
$classes = $pdo->query("SELECT name FROM classes WHERE school_id=$sid ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { set_flash('error','Session expired.'); redirect('admin/promote.php'); }
    $from = post('from_class'); $to = post('to_class');
    
    if ($from && $to && $from !== $to) {
        $stmt = $pdo->prepare("UPDATE students SET class_name = ? WHERE school_id = ? AND class_name = ?");
        $stmt->execute([$to, $sid, $from]);
        $count = $stmt->rowCount();
        set_flash('success', "Successfully promoted $count students from $from to $to.");
    } else {
        set_flash('error', 'Invalid class selection.');
    }
    redirect('admin/promote.php');
}

$pageTitle = 'Bulk Promotion'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900 mb-2">Bulk Student Promotion</h1>
    <p class="text-sm text-slate-500 mb-6">Move an entire class to the next class at the end of the session.</p>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <?php if(empty($classes)): ?>
            <p class="text-red-500 text-sm">Please create classes first.</p>
        <?php else: ?>
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Promote From:</label>
                    <select name="from_class" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">- Select Current Class -</option>
                        <?php foreach($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="text-center font-bold text-2xl text-royal-400 hidden sm:block">&rarr;</div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Promote To:</label>
                    <select name="to_class" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">- Select Next Class -</option>
                        <?php foreach($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="pt-4 border-t">
                <button type="submit" onclick="return confirm('Are you sure? This will move ALL students in the selected class to the new class.');" class="w-full rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 text-sm shadow">
                    Execute Bulk Promotion
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>