<?php
require_once __DIR__ . '/../includes/auth.php';

$school   = require_school();
$pdo      = db();
$schoolId = (int) $school['id'];

/* ---------------- Handle CSV Template Download ---------------- */
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="resultpadi_student_template.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['First Name', 'Surname', 'Class', 'Admission No (Optional)']);
    fputcsv($output, ['John', 'Doe', 'JSS 1A', 'RPD/001']);
    fputcsv($output, ['Jane', 'Smith', 'JSS 2B', '']); // Example of auto-generation
    fclose($output);
    exit;
}

/* ---------------- Handle POST Actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('admin/students.php');
    }

    $action = post('action');

    /* ---- Add Single Student ---- */
    if ($action === 'add') {
        $firstName = post('first_name');
        $surname   = post('surname');
        $admission = post('admission_no');
        $className = post('class_name');

        if ($firstName === '' || $surname === '' || $className === '') {
            set_flash('error', 'First name, surname, and class are required.');
            redirect('admin/students.php');
        }

        // Auto-generate admission number if left blank
        if ($admission === '') {
            $admission = 'RPD-' . strtoupper(substr(uniqid(), -6));
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO students (school_id, admission_no, surname, first_name, class_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$schoolId, $admission, $surname, $firstName, $className]);
            set_flash('success', 'Student added successfully. Adm No: ' . $admission);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                set_flash('error', 'That admission number already exists in your school.');
            } else {
                set_flash('error', 'Could not add student: ' . $ex->getMessage());
            }
        }
        redirect('admin/students.php');
    }

    /* ---- Bulk Upload CSV ---- */
    if ($action === 'bulk_upload') {
        if (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Please select a valid CSV file.');
            redirect('admin/students.php');
        }

        $fileInfo = pathinfo($_FILES['csv_file']['name']);
        if (strtolower($fileInfo['extension']) !== 'csv') {
            set_flash('error', 'Only .csv files are allowed.');
            redirect('admin/students.php');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle === false) {
            set_flash('error', 'Could not open the uploaded file.');
            redirect('admin/students.php');
        }

        $successCount = 0;
        $failCount = 0;
        $rowNum = 0;

        $pdo->beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNum++;
                if ($rowNum === 1) continue; // Skip header row

                // Basic validation: requires at least 3 columns
                if (count($row) < 3 || trim($row[0]) === '' || trim($row[1]) === '' || trim($row[2]) === '') {
                    $failCount++;
                    continue;
                }

                $firstName = trim($row[0]);
                $surname   = trim($row[1]);
                $className = trim($row[2]);
                $admission = isset($row[3]) ? trim($row[3]) : '';

                if ($admission === '') {
                    $admission = 'RPD-' . strtoupper(substr(uniqid(), -6));
                }

                try {
                    $stmt = $pdo->prepare("INSERT INTO students (school_id, admission_no, surname, first_name, class_name) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$schoolId, $admission, $surname, $firstName, $className]);
                    $successCount++;
                } catch (PDOException $e) {
                    // Skip duplicates
                    if ($e->getCode() === '23000') {
                        $failCount++;
                    } else {
                        throw $e; // Re-throw fatal errors
                    }
                }
            }
            $pdo->commit();
            set_flash('success', "Bulk upload complete: {$successCount} added, {$failCount} skipped (duplicates or missing data).");
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Bulk upload failed: ' . $e->getMessage());
        }
        fclose($handle);
        redirect('admin/students.php');
    }

    /* ---- Delete Student ---- */
    if ($action === 'delete') {
        $studentId = (int) post('student_id');
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ? AND school_id = ?");
        $stmt->execute([$studentId, $schoolId]);
        set_flash('success', 'Student and all related results removed.');
        redirect('admin/students.php');
    }
}

/* ---------------- Load students ---------------- */
$stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? ORDER BY class_name ASC, surname ASC, first_name ASC");
$stmt->execute([$schoolId]);
$students = $stmt->fetchAll();

$pageTitle = 'Students';
$navType   = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-royal-900">Students</h1>
            <p class="text-sm text-slate-500">Add students individually or upload a CSV file.</p>
        </div>
        <a href="?download_template=1" class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-sm font-bold px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download CSV Template
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Add / Upload Forms -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Bulk Upload -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h2 class="font-bold text-slate-800 mb-1">Bulk Upload (CSV)</h2>
                <p class="text-xs text-slate-500 mb-4">Upload multiple students at once using the CSV template.</p>
                <form method="post" action="<?= url('admin/students.php') ?>" enctype="multipart/form-data" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="bulk_upload">
                    <div>
                        <input type="file" name="csv_file" accept=".csv" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 text-sm transition">
                        Upload File
                    </button>
                </form>
            </div>

            <!-- Add Single -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h2 class="font-bold text-slate-800 mb-4">Add Single Student</h2>
                <form method="post" action="<?= url('admin/students.php') ?>" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input name="first_name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Surname <span class="text-red-500">*</span></label>
                        <input name="surname" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Class <span class="text-red-500">*</span></label>
                        <input name="class_name" type="text" required placeholder="e.g. JSS 1A" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Admission Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <input name="admission_no" type="text" placeholder="Leave blank to auto-generate" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200">
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-royal-800 hover:bg-royal-900 text-white font-bold py-2.5 text-sm transition">
                        Add Student
                    </button>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-bold text-slate-800">Student Register</h2>
                    <span class="text-xs text-slate-500"><?= count($students) ?> total</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="text-left px-4 py-3">Admission No.</th>
                                <th class="text-left px-4 py-3">Name</th>
                                <th class="text-left px-4 py-3">Class</th>
                                <th class="text-right px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No students yet. Add your first student or upload a CSV.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3 font-mono text-xs"><?= e($student['admission_no']) ?></td>
                                    <td class="px-4 py-3 font-medium"><?= e($student['surname'] . ' ' . $student['first_name']) ?></td>
                                    <td class="px-4 py-3"><?= e($student['class_name']) ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="post" action="<?= url('admin/students.php') ?>" onsubmit="return confirm('Delete this student and all their results?');" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800 hover:underline">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>