<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$navType = $navType ?? 'public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{royal:{50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554'}}}}}</script>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">
<header class="bg-royal-900 text-white shadow-lg no-print">
<div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
<a href="<?= url('index.php') ?>" class="flex items-center gap-2 font-extrabold text-lg">
<span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500 text-white font-black">R</span>
<span>Result<span class="text-orange-400">Padi</span></span>
</a>
<nav class="flex flex-wrap items-center justify-end gap-1 text-sm">
<?php if ($navType === 'admin'): ?>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/dashboard.php') ?>">Dashboard</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/classes.php') ?>">Classes</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/students.php') ?>">Students</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/promote.php') ?>">Promote</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/teachers.php') ?>">Teachers</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/results.php') ?>">Results</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/settings.php') ?>">Settings</a>
<a class="px-3 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 font-semibold" href="<?= url('admin/logout.php') ?>">Logout</a>
<?php elseif ($navType === 'super'): ?>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('super/dashboard.php') ?>">Dashboard</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('super/schools.php') ?>">Schools</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('super/pins.php') ?>">Generate PINs</a>
<a class="px-3 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 font-semibold" href="<?= url('super/logout.php') ?>">Logout</a>
<?php elseif ($navType === 'teacher'): ?>
<span class="px-3 py-2 font-bold text-orange-400">Teacher Portal</span>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('index.php') ?>">Parent Portal</a>
<a class="px-3 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 font-semibold" href="<?= url('teacher/logout.php') ?>">Logout</a>
<?php else: ?>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('index.php') ?>">Check Result</a>
<a class="px-3 py-2 rounded-lg hover:bg-royal-800" href="<?= url('admin/login.php') ?>">School Login</a>
<a class="px-3 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 font-semibold" href="<?= url('super/login.php') ?>">Super Admin</a>
<?php endif; ?>
</nav>
</div>
</header>
<main class="flex-1">
<?= flash_html() ?>