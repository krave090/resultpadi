<?php
require_once __DIR__ . '/../config/config.php';

function url(string $p = ''): string { return BASE_URL . ltrim($p, '/'); }
function asset(string $p): string { return url('assets/' . ltrim($p, '/')); }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function post(string $k, $d = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function get(string $k, $d = '') { return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $d; }
function set_flash(string $type, string $msg): void { $_SESSION['flash'] = ['type'=>$type,'message'=>$msg]; }
function get_flash(): ?array { if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; } return null; }
function flash_html(): string {
    $f = get_flash(); if (!$f) return '';
    $map = ['success'=>'bg-green-50 border-green-300 text-green-800','error'=>'bg-red-50 border-red-300 text-red-800','info'=>'bg-blue-50 border-blue-300 text-blue-800'];
    $cls = $map[$f['type']] ?? $map['info'];
    return '<div class="max-w-6xl mx-auto mt-4 px-4 no-print"><div class="border rounded-lg px-4 py-3 text-sm ' . $cls . '">' . e($f['message']) . '</div></div>';
}
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function csrf_ok(): bool { $t = $_POST['csrf_token'] ?? ''; return is_string($t) && $t !== '' && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
function school_is_expired(?string $d): bool { if (!$d || $d === '0000-00-00') return true; return strtotime($d) < strtotime(date('Y-m-d')); }
function nice_date(?string $d): string { if (!$d || $d === '0000-00-00') return 'â€”'; $ts = strtotime($d); return $ts ? date('j M Y', $ts) : 'â€”'; }
function session_options(): array { $y = (int)date('Y'); $l = []; for ($i = -1; $i <= 2; $i++) { $s = $y + $i; $l[] = $s . '/' . ($s + 1); } return $l; }
function term_options(): array { return ['First Term','Second Term','Third Term']; }
function ordinal($number) {
    $number = (int)$number; if ($number <= 0) return '-';
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($number % 100) >= 11 && ($number % 100) <= 13) return $number . 'th';
    return $number . $ends[$number % 10];
}
function get_school_settings(?string $json): array {
    $default = ['ca_count' => 2, 'ca_max' => 20, 'exam_max' => 60, 'auto_sync' => 0, 'template_style' => 'classic', 'grading' => [
        ['grade'=>'A','min'=>70,'remark'=>'Excellent'], ['grade'=>'B','min'=>60,'remark'=>'Very Good'], ['grade'=>'C','min'=>50,'remark'=>'Good'], ['grade'=>'D','min'=>45,'remark'=>'Fair'], ['grade'=>'E','min'=>40,'remark'=>'Pass'], ['grade'=>'F','min'=>0,'remark'=>'Fail']
    ]];
    if (!$json) return $default;
    $data = json_decode($json, true);
    return is_array($data) ? array_merge($default, $data) : $default;
}
function grade_for($score, $grading_scale = null): array {
    if (!$grading_scale) return ['grade'=>'-', 'remark'=>'-'];
    $score = (float)$score;
    foreach ($grading_scale as $g) { if ($score >= $g['min']) return ['grade'=>$g['grade'], 'remark'=>$g['remark']]; }
    return ['grade'=>'-', 'remark'=>'-'];
}
function recalculate_positions(PDO $pdo, int $sid, string $sess, string $term): void {
    $stmt = $pdo->prepare("SELECT r.id, s.class_name FROM results r JOIN students s ON s.id = r.student_id WHERE r.school_id=? AND r.session=? AND r.term=? ORDER BY s.class_name ASC, r.average DESC, r.total_score DESC, r.id ASC");
    $stmt->execute([$sid, $sess, $term]); $results = $stmt->fetchAll();
    $currentClass = null; $pos = 1; $update = $pdo->prepare("UPDATE results SET position=? WHERE id=?");
    foreach ($results as $r) {
        if ($currentClass !== $r['class_name']) { $currentClass = $r['class_name']; $pos = 1; }
        $update->execute([$pos, $r['id']]); $pos++;
    }
}
function compile_student_result(PDO $pdo, int $studentId, int $schoolId, string $session, string $term): bool {
    $stmt = $pdo->prepare("SELECT settings_json FROM schools WHERE id = ?"); $stmt->execute([$schoolId]); $settings = get_school_settings($stmt->fetchColumn());
    $stmt = $pdo->prepare("SELECT subject, ca_json, exam, total, remark FROM teacher_scores WHERE student_id = ? AND session = ? AND term = ?"); $stmt->execute([$studentId, $session, $term]); $scores = $stmt->fetchAll();
    if (empty($scores)) return false;
    $subjects = []; $grandTotal = 0;
    foreach ($scores as $s) {
        $ca = json_decode($s['ca_json'], true);
        $subjectData = ['subject' => $s['subject'], 'exam' => $s['exam'], 'total' => $s['total'], 'remark' => $s['remark']];
        if (is_array($ca)) { foreach ($ca as $k => $v) { $subjectData[$k] = $v; } }
        $subjects[] = $subjectData; $grandTotal += $s['total'];
    }
    $max_possible = ($settings['ca_max'] * $settings['ca_count']) + $settings['exam_max'];
    $average = $max_possible > 0 ? round(($grandTotal / ($max_possible * count($subjects))) * 100, 2) : 0;
    $json = json_encode($subjects, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND session = ? AND term = ?"); $stmt->execute([$studentId, $session, $term]); $rid = $stmt->fetchColumn();
    if ($rid) { $stmt = $pdo->prepare("UPDATE results SET subjects_json = ?, total_score = ?, average = ? WHERE id = ?"); $stmt->execute([$json, $grandTotal, $average, $rid]); }
    else { $stmt = $pdo->prepare("INSERT INTO results (student_id, school_id, session, term, subjects_json, total_score, average) VALUES (?, ?, ?, ?, ?, ?, ?)"); $stmt->execute([$studentId, $schoolId, $session, $term, $json, $grandTotal, $average]); }
    return true;
}
function make_school_slug(PDO $pdo, string $name): string {
    $b = strtolower(trim($name)); $b = preg_replace('/[^a-z0-9]+/', '-', $b); $b = trim((string)$b, '-'); if ($b === '') $b = 'school'; $slug = $b; $i = 2; $c = $pdo->prepare("SELECT COUNT(*) FROM schools WHERE slug=?");
    while (true) { $c->execute([$slug]); if ((int)$c->fetchColumn() === 0) break; $slug = $b . '-' . $i; $i++; if ($i > 1000) { $slug = $b . '-' . time(); break; } }
    return $slug;
}
function generate_admission_no(PDO $pdo, string $school_name): string {
    $clean_name = preg_replace('/[^A-Za-z]/', '', $school_name);
    $initials = strtoupper(substr($clean_name, 0, 4));
    if (strlen($initials) < 4) $initials = str_pad($initials, 4, 'X');
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $suffix = '';
    for ($i = 0; $i < 4; $i++) $suffix .= $characters[rand(0, strlen($characters) - 1)];
    $adm_no = 'RPD' . $initials . $suffix;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE admission_no = ?"); $stmt->execute([$adm_no]);
    if ((int)$stmt->fetchColumn() > 0) return generate_admission_no($pdo, $school_name);
    return $adm_no;
}