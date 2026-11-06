<?php
require_once __DIR__ . '/../includes/auth.php';
$school = require_school(); $pdo = db(); $schoolId = (int) $school['id'];
$classes = $pdo->query("SELECT name FROM classes WHERE school_id=$schoolId ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) redirect('admin/students.php');
    $action = post('action');
    if ($action === 'add') {
        $firstName = post('first_name'); $surname = post('surname'); $admission = post('admission_no'); $className = post('class_name');
        if ($firstName && $surname && $className) {
            if ($admission === '') $admission = generate_admission_no($pdo, $school['school_name']);
            try {
                $pdo->prepare("INSERT INTO students (school_id, admission_no, surname, first_name, class_name) VALUES (?, ?, ?, ?, ?)")->execute([$schoolId, $admission, $surname, $firstName, $className]);
                set_flash('success', 'Student added. Adm No: ' . $admission);
            } catch (PDOException $ex) { set_flash('error', 'Admission No exists.'); }
        }
        redirect('admin/students.php');
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM students WHERE id=? AND school_id=?")->execute([(int)post('student_id'), $schoolId]);
        set_flash('success', 'Student removed.'); redirect('admin/students.php');
    }
    if ($action === 'bulk_upload') {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r'); $rowNum = 0; $success = 0;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if ($rowNum++ == 0) continue; 
                if (count($row) < 3) continue;
                $fn = trim($row[0]); $sn = trim($row[1]); $cn = trim($row[2]); $adm = isset($row[3]) ? trim($row[3]) : '';
                if ($fn && $sn && $cn) {
                    if ($adm === '') $adm = generate_admission_no($pdo, $school['school_name']);
                    try { $pdo->prepare("INSERT INTO students (school_id, admission_no, surname, first_name, class_name) VALUES (?,?,?,?,?)")->execute([$schoolId, $adm, $sn, $fn, $cn]); $success++; } catch(PDOException $e) {}
                }
            }
            fclose($handle); set_flash('success', "$success students uploaded.");
        }
        redirect('admin/students.php');
    }
}
$students = $pdo->query("SELECT * FROM students WHERE school_id=$schoolId ORDER BY class_name ASC, surname ASC")->fetchAll();
$pageTitle = 'Students'; $navType = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-extrabold text-royal-900">Students</h1>
        <a href="?download_template=1" class="bg-slate-200 px-4 py-2 rounded text-sm font-bold">Download CSV</a>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h2 class="font-bold mb-4">Bulk Upload (CSV)</h2>
                <form method="post" enctype="multipart/form-data" class="space-y-3"><?= csrf_field() ?><input type="hidden" name="action" value="bulk_upload"><input type="file" name="csv_file" accept=".csv" required class="w-full text-sm"><button class="w-full bg-orange-500 text-white font-bold py-2 rounded text-sm">Upload</button></form>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h2 class="font-bold mb-4">Add Student</h2>
                <form method="post" class="space-y-3">
                    <?= csrf_field() ?><input type="hidden" name="action" value="add">
                    <div><label class="block text-xs font-semibold mb-1">First Name *</label><input name="first_name" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-semibold mb-1">Surname *</label><input name="surname" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Class *</label>
                        <select name="class_name" required class="w-full rounded border px-3 py-2 text-sm bg-white">
                            <option value="">Select Class</option>
                            <?php foreach($classes as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                        </select>
                        <?php if(empty($classes)): ?><p class="text-xs text-red-500 mt-1">Add classes first!</p><?php endif; ?>
                    </div>
                    <div><label class="block text-xs font-semibold mb-1">Admission No (Opt)</label><input name="admission_no" class="w-full rounded border px-3 py-2 text-sm"></div>
                    <button class="w-full bg-royal-800 text-white font-bold py-2 rounded text-sm">Add</button>
                </form>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Adm No</th><th class="text-left px-4 py-3">Name</th><th class="text-left px-4 py-3">Class</th><th class="text-right px-4 py-3">Action</th></tr></thead>
                    <tbody>
                    <?php foreach($students as $s): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 font-mono text-xs"><?= e($s['admission_no']) ?></td>
                            <td class="px-4 py-3 font-medium"><?= e($s['surname'].' '.$s['first_name']) ?></td>
                            <td class="px-4 py-3"><?= e($s['class_name']) ?></td>
                            <td class="px-4 py-3 text-right"><form method="post" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= $s['id'] ?>"><button class="text-red-500 text-xs hover:underline">Delete</button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>