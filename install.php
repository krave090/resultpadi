<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/config/config.php';
$messages = [];
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages[] = "Database `" . DB_NAME . "` ready.";
        $root->exec("USE `" . DB_NAME . "`");

        $root->exec("CREATE TABLE IF NOT EXISTS super_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $root->exec("CREATE TABLE IF NOT EXISTS schools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_name VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL UNIQUE,
            logo_url VARCHAR(255) DEFAULT NULL,
            admin_email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active','suspended') NOT NULL DEFAULT 'active',
            termly_fee_paid_until DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $root->exec("CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL,
            admission_no VARCHAR(60) NOT NULL,
            surname VARCHAR(80) NOT NULL,
            first_name VARCHAR(80) NOT NULL,
            class_name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_school_adm (school_id, admission_no),
            KEY idx_school (school_id),
            CONSTRAINT fk_students_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $root->exec("CREATE TABLE IF NOT EXISTS results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            school_id INT NOT NULL,
            session VARCHAR(20) NOT NULL,
            term VARCHAR(20) NOT NULL,
            subjects_json LONGTEXT NOT NULL,
            total_score DECIMAL(8,2) NOT NULL DEFAULT 0,
            average DECIMAL(6,2) NOT NULL DEFAULT 0,
            position INT DEFAULT NULL,
            teacher_remark TEXT DEFAULT NULL,
            views INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_student (student_id),
            KEY idx_school_session (school_id, session, term),
            CONSTRAINT fk_results_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            CONSTRAINT fk_results_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
        $messages[] = "All tables created.";

        $check = $root->prepare("SELECT COUNT(*) FROM super_admins WHERE email=?");
        $check->execute(['admin@resultpadi.com']);
        if ((int)$check->fetchColumn() === 0) {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $root->prepare("INSERT INTO super_admins (email, password_hash) VALUES (?,?)")->execute(['admin@resultpadi.com', $hash]);
            $messages[] = "Super Admin seeded: admin@resultpadi.com / password123";
        } else {
            $messages[] = "Super Admin already exists.";
        }
        $done = true;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install ResultPadi</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
<div class="max-w-lg w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
<h1 class="text-2xl font-extrabold text-royal-900">Install ResultPadi</h1>
<p class="text-sm text-slate-500 mt-1 mb-6">Click below to create the database, tables, and seed the Super Admin.</p>
<?php if ($error): ?>
<div class="mb-4 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3"><strong>Error:</strong> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($done): ?>
<div class="mb-4 rounded-lg border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3"><strong>Installation complete!</strong></div>
<ul class="text-sm text-slate-700 space-y-1 mb-6">
<?php foreach ($messages as $m): ?><li class="flex items-start gap-2"><span class="text-green-600 font-bold">&#10003;</span><span><?= htmlspecialchars($m) ?></span></li><?php endforeach; ?>
</ul>
<div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm space-y-2">
<p><strong>Super Admin:</strong> admin@resultpadi.com / password123</p>
<p><strong>Parent portal:</strong> <a class="text-royal-700 underline" href="<?= htmlspecialchars(url('index.php')) ?>"><?= htmlspecialchars(url('index.php')) ?></a></p>
<p><strong>School admin:</strong> <a class="text-royal-700 underline" href="<?= htmlspecialchars(url('admin/login.php')) ?>"><?= htmlspecialchars(url('admin/login.php')) ?></a></p>
</div>
<div class="mt-5 text-center">
<a href="<?= htmlspecialchars(url('index.php')) ?>" class="inline-block rounded-lg bg-royal-800 hover:bg-royal-900 text-white font-bold px-6 py-3 text-sm">Go to Homepage</a>
</div>
<p class="text-xs text-slate-400 mt-4 text-center">For security, delete <strong>install.php</strong> after installation.</p>
<?php else: ?>
<form method="post"><button type="submit" class="w-full rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 text-sm">Run Installation</button></form>
<?php endif; ?>
</div></body></html>