<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];
$classes = $pdo->query("SELECT name FROM classes WHERE school_id=$sid ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) redirect('admin/teachers.php');
    if (post('action') === 'add') {
        $name = post('name'); $email = post('email'); $pass = post('password'); $class = post('class_assigned'); $subject = post('subject_assigned');
        if ($name && $email && $pass && $class && $subject) {
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO teachers (school_id, name, email, password_hash, class_assigned, subject_assigned) VALUES (?,?,?,?,?,?)")->execute([$sid, $name, $email, $hash, $class, $subject]);
                set_flash('success', 'Teacher added.');
            } catch(PDOException $e) { set_flash('error', 'Email exists.'); }
        }
        redirect('admin/teachers.php');
    }
    if (post('action') === 'generate_link') {
        $tid = (int)post('teacher_id'); $token = bin2hex(random_bytes(16));
        $pdo->prepare("UPDATE teachers SET magic_token=? WHERE id=? AND school_id=?")->execute([$token, $tid, $sid]);
        set_flash('success', 'New link generated.'); redirect('admin/teachers.php');
    }
    if (post('action') === 'delete') {
        $pdo->prepare("DELETE FROM teachers WHERE id=? AND school_id=?")->execute([(int)post('teacher_id'), $sid]);
        set_flash('success', 'Teacher removed.'); redirect('admin/teachers.php');
    }
}
$teachers = $pdo->query("SELECT * FROM teachers WHERE school_id=$sid ORDER BY name ASC")->fetchAll();
$pageTitle = 'Teachers'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900 mb-6">Manage Teachers</h1>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h2 class="font-bold mb-4">Add Teacher</h2>
                <form method="post" class="space-y-3">
                    <?= csrf_field() ?><input type="hidden" name="action" value="add">
                    <div><label class="block text-xs font-semibold mb-1">Name</label><input name="name" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold mb-1">Email</label><input name="email" type="email" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold mb-1">Password</label><input name="password" value="teacher123" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Class Assigned</label>
                        <select name="class_assigned" required class="w-full rounded border px-3 py-2 text-sm bg-white">
                            <option value="">Select Class</option>
                            <?php foreach($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="block text-xs font-semibold mb-1">Subject</label><input name="subject_assigned" required placeholder="e.g. Mathematics" class="w-full rounded border px-3 py-2 text-sm"></div>
                    <button class="w-full bg-royal-800 text-white font-bold py-2 rounded text-sm">Add</button>
                </form>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Teacher</th><th class="text-left px-4 py-3">Class/Subj</th><th class="text-left px-4 py-3">Link</th><th class="text-right px-4 py-3">Action</th></tr></thead>
                    <tbody>
                    <?php foreach($teachers as $t): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3"><p class="font-medium"><?= e($t['name']) ?></p><p class="text-xs text-slate-500"><?= e($t['email']) ?></p></td>
                            <td class="px-4 py-3 text-xs"><?= e($t['class_assigned']) ?><br><?= e($t['subject_assigned']) ?></td>
                            <td class="px-4 py-3">
                                <?php if($t['magic_token']): ?><input type="text" readonly value="<?= url('teacher/login.php?token='.$t['magic_token']) ?>" class="w-40 text-[10px] bg-slate-50 border rounded px-1" onclick="this.select()"><?php endif; ?>
                                <form method="post" class="mt-1"><?= csrf_field() ?><input type="hidden" name="action" value="generate_link"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>"><button class="text-[10px] text-orange-600 font-bold hover:underline">Generate Link</button></form>
                            </td>
                            <td class="px-4 py-3 text-right"><form method="post" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>"><button class="text-red-500 text-xs hover:underline">Delete</button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>