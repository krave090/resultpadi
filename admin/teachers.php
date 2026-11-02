<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { set_flash('error','Session expired.'); redirect('admin/teachers.php'); }
    
    if (post('action') === 'add') {
        $name = post('name'); $email = post('email'); $pass = post('password');
        $class = post('class_assigned'); $subject = post('subject_assigned');
        if ($name && $email && $pass && $class && $subject) {
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO teachers (school_id, name, email, password_hash, class_assigned, subject_assigned) VALUES (?,?,?,?,?,?)")
                    ->execute([$sid, $name, $email, $hash, $class, $subject]);
                set_flash('success', 'Teacher added successfully.');
            } catch (PDOException $e) { set_flash('error', 'Email already exists.'); }
        } else { set_flash('error', 'All fields are required.'); }
        redirect('admin/teachers.php');
    }
    
    if (post('action') === 'delete') {
        $pdo->prepare("DELETE FROM teachers WHERE id=? AND school_id=?")->execute([(int)post('teacher_id'), $sid]);
        set_flash('success', 'Teacher removed.');
        redirect('admin/teachers.php');
    }

    if (post('action') === 'generate_link') {
        $tid = (int)post('teacher_id');
        $expiry = post('expiry'); 
        $token = bin2hex(random_bytes(16));
        $expires_at = null;
        if ($expiry === '1_day') $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));
        if ($expiry === '7_days') $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        if ($expiry === '30_days') $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

        $pdo->prepare("UPDATE teachers SET magic_token=?, token_expires_at=? WHERE id=? AND school_id=?")
            ->execute([$token, $expires_at, $tid, $sid]);
        set_flash('success', 'New secure link generated.');
        redirect('admin/teachers.php');
    }
}
$teachers = $pdo->query("SELECT * FROM teachers WHERE school_id=$sid ORDER BY name ASC")->fetchAll();
$pageTitle = 'Teachers'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900">Manage Teachers</h1>
    <p class="text-sm text-slate-500 mb-6">Create accounts and generate secure login links for staff.</p>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h2 class="font-bold text-slate-800 mb-4">Add New Teacher</h2>
                <form method="post" class="space-y-3">
                    <?= csrf_field() ?><input type="hidden" name="action" value="add">
                    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label><input name="name" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Email</label><input name="email" type="email" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Password</label><input name="password" type="text" required value="teacher123" class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Class Assigned</label><input name="class_assigned" required placeholder="e.g. SS1A" class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Subject Assigned</label><input name="subject_assigned" required placeholder="e.g. Mathematics" class="w-full rounded border px-3 py-2 text-sm"></div>
                    <button type="submit" class="w-full rounded bg-royal-800 text-white font-bold py-2 text-sm">Add Teacher</button>
                </form>
            </div>
        </div>
        
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider">
                        <tr><th class="text-left px-4 py-3">Teacher</th><th class="text-left px-4 py-3">Class/Subject</th><th class="text-left px-4 py-3">Secure Link</th><th class="text-right px-4 py-3">Delete</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($teachers as $t): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <p class="font-medium"><?= e($t['name']) ?></p>
                                <p class="text-xs text-slate-500"><?= e($t['email']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p><?= e($t['class_assigned']) ?></p>
                                <p class="text-slate-500"><?= e($t['subject_assigned']) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <?php if($t['magic_token']): ?>
                                    <div class="flex items-center gap-1">
                                        <input type="text" readonly value="<?= url('teacher/login.php?token=' . $t['magic_token']) ?>" class="w-48 text-[10px] bg-slate-50 border border-slate-300 rounded px-2 py-1 outline-none" onclick="this.select()">
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Expires: <?= $t['token_expires_at'] ? e(date('d M Y, H:i', strtotime($t['token_expires_at']))) : 'Never' ?></p>
                                <?php else: ?>
                                    <span class="text-xs text-red-500">No link generated</span>
                                <?php endif; ?>
                                <form method="post" class="mt-2 flex gap-1">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="generate_link"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                    <select name="expiry" class="text-[10px] border rounded px-1 py-1">
                                        <option value="never">No Expiry</option>
                                        <option value="1_day">1 Day</option>
                                        <option value="7_days">7 Days</option>
                                        <option value="30_days">30 Days</option>
                                    </select>
                                    <button type="submit" class="text-[10px] bg-royal-600 hover:bg-royal-700 text-white font-bold px-2 py-1 rounded">Generate</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" onsubmit="return confirm('Delete teacher?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>"><button class="text-xs text-red-600 hover:underline">Delete</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>