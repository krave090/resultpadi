<?php
require_once __DIR__ . '/../includes/super_auth.php';
require_super(); $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { set_flash('error','Session expired.'); redirect('super/schools.php'); }
    $action = post('action');
    if ($action === 'add') {
        $name = post('school_name'); $email = post('admin_email'); $pass = post('password');
        if ($name && $email && $pass) {
            try {
                $slug = make_school_slug($pdo, $name); $hash = password_hash($pass, PASSWORD_DEFAULT); $paidUntil = date('Y-m-d', strtotime('+90 days'));
                $pdo->prepare("INSERT INTO schools (school_name, slug, admin_email, password_hash, status, termly_fee_paid_until, pin_price) VALUES (?,?,?,?,'active',?,1000.00)")->execute([$name,$slug,$email,$hash,$paidUntil]);
                set_flash('success', 'School created. Admin: '.$email.' | Pass: '.$pass);
            } catch (PDOException $e) { set_flash('error', $e->getCode()==='23000'?'Email/slug exists.':'Error: '.$e->getMessage()); }
        } else { set_flash('error','All fields required.'); }
        redirect('super/schools.php');
    }
    if ($action === 'update_price') {
        $sid = (int)post('school_id'); $price = (float)post('pin_price');
        $pdo->prepare("UPDATE schools SET pin_price=? WHERE id=?")->execute([$price, $sid]);
        set_flash('success', 'PIN price updated to N'.$price.'.'); redirect('super/schools.php');
    }
    if ($action === 'renew') {
        $sid = (int)post('school_id'); $stmt = $pdo->prepare("SELECT termly_fee_paid_until FROM schools WHERE id=?"); $stmt->execute([$sid]); $current = $stmt->fetchColumn();
        $base = $current && strtotime($current) > time() ? $current : date('Y-m-d'); $new = date('Y-m-d', strtotime($base . ' +90 days'));
        $pdo->prepare("UPDATE schools SET termly_fee_paid_until=?, status='active' WHERE id=?")->execute([$new, $sid]);
        set_flash('success','Renewed until ' . nice_date($new) . '.'); redirect('super/schools.php');
    }
    if ($action === 'suspend') { $pdo->prepare("UPDATE schools SET status='suspended' WHERE id=?")->execute([(int)post('school_id')]); set_flash('success','Suspended.'); redirect('super/schools.php'); }
    if ($action === 'activate') { $pdo->prepare("UPDATE schools SET status='active' WHERE id=?")->execute([(int)post('school_id')]); set_flash('success','Activated.'); redirect('super/schools.php'); }
}
$schools = $pdo->query("SELECT * FROM schools ORDER BY id DESC")->fetchAll();
$pageTitle = 'Schools'; $navType = 'super';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
<h1 class="text-2xl font-extrabold text-royal-900">Schools Management</h1>
<p class="text-sm text-slate-500 mb-6">Manage schools, subscriptions, and PIN pricing.</p>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-1"><div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
<h2 class="font-bold text-slate-800 mb-4">Add New School</h2>
<form method="post" class="space-y-3">
<?= csrf_field() ?><input type="hidden" name="action" value="add">
<div><label class="block text-xs font-semibold text-slate-600 mb-1">School Name</label><input name="school_name" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
<div><label class="block text-xs font-semibold text-slate-600 mb-1">Admin Email</label><input name="admin_email" type="email" required class="w-full rounded-lg border px-3 py-2 text-sm"></div>
<div><label class="block text-xs font-semibold text-slate-600 mb-1">Admin Password</label><input name="password" type="text" required value="school123" class="w-full rounded-lg border px-3 py-2 text-sm"></div>
<button type="submit" class="w-full rounded-lg bg-royal-800 text-white font-bold py-2.5 text-sm">Create School</button>
</form></div></div>
<div class="lg:col-span-2"><div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
<div class="px-5 py-4 border-b border-slate-200 flex justify-between items-center"><h2 class="font-bold text-slate-800">All Schools</h2><a href="<?= url('super/pins.php') ?>" class="text-xs font-bold text-orange-600 hover:underline">Generate PINs &rarr;</a></div>
<div class="overflow-x-auto"><table class="w-full text-sm">
<thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider"><tr><th class="text-left px-4 py-3">School</th><th class="text-left px-4 py-3">Status</th><th class="text-left px-4 py-3">PIN Price</th><th class="text-right px-4 py-3">Actions</th></tr></thead>
<tbody>
<?php foreach ($schools as $s): ?>
<tr class="border-t border-slate-100">
<td class="px-4 py-3"><p class="font-medium"><?= e($s['school_name']) ?></p><p class="text-xs text-slate-500"><?= e($s['admin_email']) ?></p></td>
<td class="px-4 py-3"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $s['status']==='active'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
<td class="px-4 py-3">
<form method="post" class="flex items-center gap-1">
<?= csrf_field() ?><input type="hidden" name="action" value="update_price"><input type="hidden" name="school_id" value="<?= $s['id'] ?>">
<span class="text-xs">N</span><input type="number" name="pin_price" value="<?= e($s['pin_price'] ?? 1000) ?>" class="w-16 text-xs border rounded px-1 py-1">
<button type="submit" class="text-[10px] bg-slate-200 hover:bg-slate-300 px-2 py-1 rounded">Save</button>
</form>
</td>
<td class="px-4 py-3 text-right space-y-1">
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="school_id" value="<?= $s['id'] ?>"><input type="hidden" name="action" value="renew"><button class="text-xs text-orange-600 hover:underline">Renew</button></form>
<?php if ($s['status']==='active'): ?>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="school_id" value="<?= $s['id'] ?>"><input type="hidden" name="action" value="suspend"><button class="text-xs text-red-600 hover:underline">Suspend</button></form>
<?php else: ?>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="school_id" value="<?= $s['id'] ?>"><input type="hidden" name="action" value="activate"><button class="text-xs text-green-600 hover:underline">Activate</button></form>
<?php endif; ?>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>