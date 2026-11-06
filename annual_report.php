<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['unlocked_results']) || !in_array((int)get('id', 0), $_SESSION['unlocked_results'])) {
    set_flash('error', 'Please enter your PIN to view the report.');
    redirect('index.php');
}

$studentId = (int)get('id', 0);
$pdo = db();
$student = null; $errorMsg = '';

$stmt = $pdo->prepare("SELECT s.*, sc.school_name, sc.logo_url, sc.settings_json FROM students s INNER JOIN schools sc ON sc.id=s.school_id WHERE s.id=? LIMIT 1");
$stmt->execute([$studentId]); $student = $stmt->fetch();
if (!$student) { $errorMsg = 'Student not found.'; }

$session = get('session', session_options()[1]); // default current session

// Fetch all 3 terms for the session
$stmt = $pdo->prepare("SELECT * FROM results WHERE student_id=? AND session=? ORDER BY term ASC");
$stmt->execute([$studentId, $session]);
$termly_results = $stmt->fetchAll();

if (count($termly_results) === 0) {
    $errorMsg = 'No results found for this session.';
}

$pageTitle = 'Annual Report'; $navType = 'public';
if ($errorMsg !== '') {
    require __DIR__ . '/includes/header.php';
    echo "<div class='text-center py-20 text-red-600 font-bold'>$errorMsg</div>";
    require __DIR__ . '/includes/footer.php'; exit;
}

$settings = get_school_settings($student['settings_json'] ?? null);
$template = $settings['template_style'] ?? 'classic';
$promotion_limit = $settings['promotion_limit'] ?? 40;

// Calculations
$total_annual_score = 0;
$total_annual_avg = 0;
$subject_totals = []; // To sum up scores per subject across terms

foreach ($termly_results as $res) {
    $total_annual_score += (float)$res['total_score'];
    $total_annual_avg += (float)$res['average'];
    
    $subjs = json_decode($res['subjects_json'], true);
    if (is_array($subjs)) {
        foreach ($subjs as $s) {
            $name = $s['subject'];
            if (!isset($subject_totals[$name])) $subject_totals[$name] = ['total'=>0, 'count'=>0];
            $subject_totals[$name]['total'] += $s['total'];
            $subject_totals[$name]['count']++;
        }
    }
}

$cumulative_average = count($termly_results) > 0 ? round($total_annual_avg / count($termly_results), 2) : 0;
$promotion_status = ($cumulative_average >= $promotion_limit) ? 'PROMOTED TO NEXT CLASS' : 'REPEAT CLASS';
$promotion_color = ($cumulative_average >= $promotion_limit) ? 'text-green-600' : 'text-red-600';

$header_border = $template === 'modern' ? 'border-orange-500' : ($template === 'minimal' ? 'border-slate-900' : 'border-royal-800');
$header_text = $template === 'modern' ? 'text-orange-600' : ($template === 'minimal' ? 'text-slate-900' : 'text-royal-900');
$bg_summary = $template === 'modern' ? 'bg-orange-50' : ($template === 'minimal' ? 'bg-slate-100' : 'bg-royal-50');

require __DIR__ . '/includes/header.php';
?>
<section class="max-w-3xl mx-auto px-4 py-8">
<div class="flex items-center justify-between gap-3 mb-4 no-print">
    <a href="<?= url('index.php') ?>" class="text-sm font-semibold text-royal-700 hover:underline">&larr; Home</a>
    <button type="button" onclick="window.print()" class="rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2.5 shadow">Print Annual Report</button>
</div>

<div class="dossier p-6 sm:p-10 bg-white shadow-xl rounded-xl border border-slate-200">
    <div class="text-center border-b-4 <?= $header_border ?> pb-5">
        <?php if (!empty($student['logo_url'])): ?><img src="<?= e($student['logo_url']) ?>" alt="Logo" class="h-20 mx-auto mb-3 object-contain"><?php endif; ?>
        <h1 class="text-2xl sm:text-3xl font-extrabold uppercase tracking-wide <?= $header_text ?>"><?= e($student['school_name']) ?></h1>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-500 mt-1">Annual Academic Report (<?= e($session) ?>)</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 mt-6 text-sm">
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Student Name</span><span class="font-bold text-slate-800"><?= e($student['surname'] . ' ' . $student['first_name']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Admission No.</span><span class="font-bold text-slate-800 font-mono"><?= e($student['admission_no']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Class</span><span class="font-bold text-slate-800"><?= e($student['class_name']) ?></span></div>
        <div class="flex justify-between border-b border-dashed border-slate-200 py-1"><span class="text-slate-500 font-medium">Session</span><span class="font-bold text-slate-800"><?= e($session) ?></span></div>
    </div>

    <h3 class="mt-8 mb-2 font-bold text-slate-800 text-sm uppercase">Termly Performance Summary</h3>
    <div class="overflow-x-auto">
        <table class="result-table w-full mb-8">
            <thead><tr><th>Term</th><th class="text-center">Total Score</th><th class="text-center">Term Average</th></tr></thead>
            <tbody>
                <?php foreach ($termly_results as $tr): ?>
                <tr><td class="font-medium"><?= e($tr['term']) ?></td><td class="text-center"><?= e(number_format((float)$tr['total_score'], 1)) ?></td><td class="text-center font-bold"><?= e(number_format((float)$tr['average'], 2)) ?>%</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3 class="mt-8 mb-2 font-bold text-slate-800 text-sm uppercase">Subject Cumulative Performance</h3>
    <div class="overflow-x-auto">
        <table class="result-table w-full">
            <thead><tr><th>Subject</th><th class="text-center">Average Score (%)</th><th class="text-center">Grade</th></tr></thead>
            <tbody>
                <?php foreach ($subject_totals as $name => $data): 
                    $subject_avg = $data['count'] > 0 ? round($data['total'] / $data['count'], 2) : 0;
                    // Convert to percentage based on max possible if needed, but here we assume total is already relative to the term max
                    // Actually, max possible per subject per term = ($settings['ca_max'] * $settings['ca_count']) + $settings['exam_max']
                    $max_per_subject = ($settings['ca_max'] * $settings['ca_count']) + $settings['exam_max'];
                    $avg_percentage = $max_per_subject > 0 ? round(($subject_avg / $max_per_subject) * 100, 1) : 0;
                    $g = grade_for($avg_percentage, $settings['grading']);
                ?>
                <tr>
                    <td class="font-medium"><?= e($name) ?></td>
                    <td class="text-center font-bold text-royal-900"><?= e($avg_percentage) ?>%</td>
                    <td class="text-center font-bold"><?= e($g['grade']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">
        <div class="<?= $bg_summary ?> border border-slate-200 rounded-xl px-4 py-4 text-center">
            <p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Cumulative Average</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= e(number_format($cumulative_average, 2)) ?>%</p>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-4 text-center">
            <p class="text-[10px] uppercase tracking-wider text-slate-600 font-bold">Final Status</p>
            <p class="text-lg font-black <?= $promotion_color ?> mt-1 tracking-wide"><?= $promotion_status ?></p>
        </div>
    </div>

    <div class="mt-10 flex items-end justify-between text-xs text-slate-500"><div class="border-t border-slate-400 w-40 pt-1 text-center">Class Teacher</div><div class="border-t border-slate-400 w-40 pt-1 text-center">Principal</div></div>
</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>