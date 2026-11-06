<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) redirect('admin/classes.php');
    if (post('action') === 'add') {
        $name = post('name');
        if ($name) {
            try { $pdo->prepare("INSERT INTO classes (school_id, name) VALUES (?, ?)")->execute([$sid, $name]); set_flash('success','Class added.'); }
            catch(PDOException $e) { set_flash('error','Class already exists.'); }
        }
        redirect('admin/classes.php');
    }
    if (post('action') === 'delete') {
        $pdo->prepare("DELETE FROM classes WHERE id=? AND school_id=?")->execute([(int)post('class_id'), $sid]);
        set_flash('success','Class deleted.'); redirect('admin/classes.php');
    }
}
$classes = $pdo->query("SELECT * FROM classes WHERE school_id=$sid ORDER BY name ASC")->fetchAll();
$pageTitle = 'Classes'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900 mb-6">Manage Classes (Class Arms)</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="font-bold mb-4">Add New Class</h2>
            <form method="post" class="space-y-3">
                <?= csrf_field() ?><input type="hidden" name="action" value="add">
                <div><label class="block text-xs font-semibold mb-1">Class Name</label><input name="name" required placeholder="e.g. JSS 1A" class="w-full rounded border px-3 py-2 text-sm"></div>
                <button class="w-full bg-royal-800 text-white font-bold py-2 rounded text-sm">Add Class</button>
            </form>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="font-bold mb-4">Existing Classes</h2>
            <ul class="space-y-2">
                <?php foreach($classes as $c): ?>
                <li class="flex justify-between items-center p-2 bg-slate-50 border rounded text-sm">
                    <span class="font-medium"><?= e($c['name']) ?></span>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="class_id" value="<?= $c['id'] ?>"><button class="text-red-500 text-xs hover:underline">Delete</button></form>
                </li>
                <?php endforeach; ?>
                <?php if(empty($classes)) echo "<p class='text-sm text-slate-500'>No classes added yet.</p>"; ?>
            </ul>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>