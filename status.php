<?php
// status.php - Developer Context Generator
// Save this in C:\xampp\htdocs\app\status.php and open it in the browser.

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$ignore = ['.', '..', 'status.php', '.git']; // Don't include this file itself

function get_files($dir, $base, $ignore) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if (in_array($item, $ignore)) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $relPath = str_replace($base . DIRECTORY_SEPARATOR, '', $path);
        
        if (is_dir($path)) {
            $files = array_merge($files, get_files($path, $base, $ignore));
        } else {
            // Only include text-based files
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'css', 'js', 'sql', 'md', 'json'])) {
                $files[] = str_replace('\\', '/', $relPath);
            }
        }
    }
    return $files;
}

$allFiles = get_files($root, $root, $ignore);
sort($allFiles);

echo "# ResultPadi - Full Project Status & Codebase Dump\n\n";
echo "This document contains the complete state of the ResultPadi project. You can copy and paste this entire output into a new AI conversation to seamlessly continue development without losing context.\n\n";

echo "## 1. Project Overview & Architecture\n";
echo "- **Project Name:** ResultPadi (SaaS for Nigerian Schools)\n";
echo "- **Tech Stack:** PHP (PDO), MySQL, Tailwind CSS (CDN), Vanilla JS.\n";
echo "- **Core Workflow:** Super Admin generates PINs and manages schools -> School Admin manages students/teachers/settings -> Teachers input CA & Exam scores -> System Auto-Syncs/Compiles results -> Parents access results via Admission Number + PIN.\n\n";

echo "## 2. Database Schema\n";
if (file_exists($root . '/database.sql')) {
    echo "```sql\n" . file_get_contents($root . '/database.sql') . "\n```\n\n";
} else {
    echo "*database.sql not found. Refer to install.php for schema.*\n\n";
}

echo "## 3. Project File Tree\n";
echo "```text\n";
foreach ($allFiles as $file) {
    echo $file . "\n";
}
echo "```\n\n";

echo "## 4. Full Source Code\n";
foreach ($allFiles as $file) {
    if ($file === 'database.sql') continue; // Already output above
    
    $content = file_get_contents($root . DIRECTORY_SEPARATOR . $file);
    echo "### File: {$file}\n";
    echo "```" . pathinfo($file, PATHINFO_EXTENSION) . "\n";
    echo $content;
    echo "\n```\n\n";
}