<?php
require_once __DIR__ . '/auth.php';
$parent = require_parent(); $pdo = db(); $pid = (int)$parent['id'];
$stmt = $pdo->prepare("SELECT s.id, s.surname, s.first_name, s.class_name, s.admission_no, sc.school_name FROM parent_students ps JOIN students s ON s.id = ps.student_id JOIN schools sc ON sc.id = s.school_id WHERE ps.parent_id = ?");
$stmt->execute([$pid]); $students = $stmt->fetchAll();
$pageTitle = 'My Children'; $navType = 'parent';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-royal-900 mb-6">Welcome, <?= e($parent['name']) ?>!</h1>
    <?php if(empty($students)): ?>
        <div class="bg-white border rounded-xl p-8 text-center shadow-sm"><p class="text-slate-500">You haven't linked any students yet.</p></div>
    <?php endif; ?>
    <div class="space-y-8">
    <?php foreach($students as $s): 
        $res_stmt = $pdo->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY id DESC"); $res_stmt->execute([$s['id']]); $results = $res_stmt->fetchAll();
    ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-royal-50 px-6 py-4 border-b border-royal-100 flex justify-between items-center">
                <div>
                    <h2 class="font-extrabold text-royal-900 text-lg"><?= e($s['surname'] . ' ' . $s['first_name']) ?></h2>
                    <p class="text-xs text-royal-600 font-medium"><?= e($s['school_name']) ?> | <?= e($s['class_name']) ?> | Adm: <?= e($s['admission_no']) ?></p>
                </div>
                <a href="<?= url('index.php') ?>" class="text-xs font-bold text-orange-600 hover:underline">+ Unlock New Term</a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach($results as $r): ?>
                    <div class="border border-slate-200 rounded-lg p-4 flex justify-between items-center hover:bg-slate-50">
                        <div><p class="font-bold text-slate-800"><?= e($r['session']) ?></p><p class="text-sm text-slate-600"><?= e($r['term']) ?></p></div>
                        <a href="<?= url('result.php?id=' . $s['id'] . '&session=' . urlencode($r['session']) . '&term=' . urlencode($r['term'])) ?>" class="bg-royal-600 text-white text-xs font-bold px-3 py-1.5 rounded shadow hover:bg-royal-700">View</a>
                    </div>
                    <?php endforeach; ?>
                    <div class="border border-orange-200 bg-orange-50 rounded-lg p-4 flex justify-between items-center">
                        <div><p class="font-bold text-orange-900">Annual Report</p><p class="text-sm text-orange-700">Cumulative Session View</p></div>
                        <a href="<?= url('annual_report.php?id=' . $s['id']) ?>" class="bg-orange-500 text-white text-xs font-bold px-3 py-1.5 rounded shadow hover:bg-orange-600">View</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>