<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $sid = (int)$school['id'];
$settings = get_school_settings($school['settings_json'] ?? null);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id=?"); $stmt->execute([$sid]); $totalStudents = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM results WHERE school_id=?"); $stmt->execute([$sid]); $totalResults = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE school_id=?"); $stmt->execute([$sid]); $totalTeachers = (int)$stmt->fetchColumn();

// Fetch Teacher Submission Status
$current_session = session_options()[1]; $current_term = 'First Term';
$stmt = $pdo->prepare("
    SELECT t.name, t.class_assigned, t.subject_assigned, 
    (SELECT COUNT(DISTINCT ts.student_id) FROM teacher_scores ts WHERE ts.teacher_id = t.id AND ts.session = ? AND ts.term = ?) as graded_students,
    (SELECT COUNT(*) FROM students s WHERE s.school_id = t.school_id AND s.class_name = t.class_assigned) as total_class_students
    FROM teachers t WHERE t.school_id = ?
");
$stmt->execute([$current_session, $current_term, $sid]);
$teacher_status = $stmt->fetchAll();

$expired = school_is_expired($school['termly_fee_paid_until']);
$pageTitle = 'Dashboard'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
    <?php if ($expired): ?><div class="rounded-xl border border-red-300 bg-red-50 text-red-800 px-5 py-4 mb-6"><p class="font-bold">Your subscription has expired.</p></div><?php else: ?><div class="rounded-xl border border-green-300 bg-green-50 text-green-800 px-5 py-4 mb-6 text-sm">Subscription active until <strong><?= e(nice_date($school['termly_fee_paid_until'])) ?></strong>.</div><?php endif; ?>
    
    <h1 class="text-2xl font-extrabold text-royal-900"><?= e($school['school_name']) ?></h1>
    <p class="text-sm text-slate-500 mb-6">School administration dashboard</p>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Students</p><p class="text-3xl font-extrabold text-royal-900 mt-2"><?= e($totalStudents) ?></p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Teachers</p><p class="text-3xl font-extrabold text-royal-700 mt-2"><?= e($totalTeachers) ?></p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Published Results</p><p class="text-3xl font-extrabold text-orange-500 mt-2"><?= e($totalResults) ?></p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm"><p class="text-xs uppercase tracking-wider text-slate-500 font-bold">Auto-Sync Status</p><p class="text-lg font-extrabold mt-2 <?= !empty($settings['auto_sync']) ? 'text-green-600' : 'text-amber-500' ?>"><?= !empty($settings['auto_sync']) ? 'ON' : 'MANUAL' ?></p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 class="font-bold text-slate-800">Teacher Submission Status (<?= $current_term ?>)</h2>
                <a href="<?= url('admin/results.php') ?>" class="text-xs font-bold text-royal-600 hover:underline">Go to Sync &rarr;</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider"><tr><th class="text-left px-4 py-3">Teacher</th><th class="text-left px-4 py-3">Class/Subject</th><th class="text-left px-4 py-3">Progress</th></tr></thead>
                <tbody>
                <?php if (empty($teacher_status)): ?><tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">No teachers added yet.</td></tr>
                <?php else: foreach($teacher_status as $t): 
                    $progress = $t['total_class_students'] > 0 ? round(($t['graded_students'] / $t['total_class_students']) * 100) : 0;
                    $color = $progress == 100 ? 'bg-green-500' : ($progress > 0 ? 'bg-amber-400' : 'bg-slate-300');
                ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium"><?= e($t['name']) ?></td>
                        <td class="px-4 py-3 text-xs"><strong><?= e($t['class_assigned']) ?></strong><br><?= e($t['subject_assigned']) ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-200 rounded-full h-2"><div class="<?= $color ?> h-2 rounded-full" style="width: <?= $progress ?>%"></div></div>
                                <span class="text-xs font-bold"><?= $t['graded_students'] ?>/<?= $t['total_class_students'] ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="lg:col-span-1 space-y-4">
            <a href="<?= url('admin/students.php') ?>" class="block bg-white hover:bg-royal-50 rounded-xl border border-slate-200 p-5 shadow-sm"><p class="font-bold text-royal-900">Manage Students &rarr;</p><p class="text-sm text-slate-500 mt-1">Add or import students.</p></a>
            <a href="<?= url('admin/teachers.php') ?>" class="block bg-white hover:bg-orange-50 rounded-xl border border-slate-200 p-5 shadow-sm"><p class="font-bold text-orange-600">Manage Teachers &rarr;</p><p class="text-sm text-slate-500 mt-1">Generate secure login links.</p></a>
            <a href="<?= url('admin/results.php') ?>" class="block bg-white hover:bg-green-50 rounded-xl border border-slate-200 p-5 shadow-sm"><p class="font-bold text-green-700">Publish Results &rarr;</p><p class="text-sm text-slate-500 mt-1">Sync and approve results.</p></a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>