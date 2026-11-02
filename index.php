<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
try { $schools = $pdo->query("SELECT id, school_name FROM schools WHERE status='active' ORDER BY school_name ASC")->fetchAll(); }
catch (PDOException $e) { $schools = []; }
$errMap = ['invalid'=>'No student matched those details. Please check with your school.','missing'=>'Please enter all fields.','noresult'=>'Result is not yet ready.'];
$errCode = get('error'); $errorMsg = $errMap[$errCode] ?? '';
$pageTitle = 'ResultPadi - Your School\'s Result Partner'; $navType = 'public';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-gradient-to-br from-royal-900 via-royal-800 to-royal-600 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
    <div class="max-w-6xl mx-auto px-4 py-20 text-center relative z-10">
        <span class="inline-block bg-orange-500 text-white text-[11px] font-bold uppercase tracking-widest px-4 py-1.5 rounded-full shadow-lg mb-4">ResultPadi</span>
        <h1 class="text-4xl sm:text-6xl font-extrabold leading-tight">Your School's Result Partner.</h1>
        <p class="mt-6 text-blue-100 max-w-2xl mx-auto text-base sm:text-lg">ResultPadi is a lightweight, secure platform that allows schools to manage results, teachers to input scores, and parents to instantly access and print termly dossiers from the comfort of their homes.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-12 h-12 bg-royal-100 text-royal-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">1</div>
                <h3 class="font-bold text-lg text-royal-900 mb-2">For Schools</h3>
                <p class="text-sm text-slate-600">Easily manage student registers, assign teachers, and compile results automatically with our dynamic grading system.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">2</div>
                <h3 class="font-bold text-lg text-royal-900 mb-2">For Teachers</h3>
                <p class="text-sm text-slate-600">Enter CA and Exam scores securely from your phone or laptop. The system calculates totals and positions instantly.</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">3</div>
                <h3 class="font-bold text-lg text-royal-900 mb-2">For Parents</h3>
                <p class="text-sm text-slate-600">Check your child's results securely using a Scratch Card PIN. Print clean, professional result dossiers instantly.</p>
            </div>
        </div>
    </div>
</section>

<section id="check-result" class="py-16 bg-slate-100">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 p-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-royal-600 to-orange-500"></div>
            <h2 class="text-2xl font-extrabold text-royal-900 mb-1 text-center">Check Your Result</h2>
            <p class="text-sm text-slate-500 mb-6 text-center">Enter your school and admission number to proceed.</p>
            
            <?php if ($errorMsg): ?><div class="mb-5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3"><?= e($errorMsg) ?></div><?php endif; ?>
            
            <?php if (empty($schools)): ?>
                <div class="rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-sm px-4 py-3 text-center">No school is currently active.</div>
            <?php else: ?>
            <form method="post" action="<?= url('check.php') ?>" class="space-y-5">
                <?= csrf_field() ?>
                
                <div class="relative">
                    <label for="school_search" class="block text-sm font-semibold text-slate-700 mb-1">School Name</label>
                    <input type="text" id="school_search" required placeholder="Start typing your school name..." autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200 transition">
                    <input type="hidden" name="school_id" id="school_id" required>
                    
                    <div id="school_dropdown" class="absolute z-10 w-full bg-white border border-slate-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto hidden"></div>
                </div>

                <div>
                    <label for="admission_no" class="block text-sm font-semibold text-slate-700 mb-1">Admission Number</label>
                    <input id="admission_no" name="admission_no" type="text" required autocomplete="off" placeholder="e.g. RPD/2025/001"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base outline-none focus:border-royal-600 focus:ring-2 focus:ring-royal-200 transition">
                </div>

                <button type="submit" class="w-full rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold py-3.5 text-base tracking-wide transition shadow-lg shadow-orange-500/30">
                    Proceed to PIN
                </button>
            </form>
            
            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500 mb-2">Don't have a PIN?</p>
                <button disabled class="inline-flex items-center gap-2 rounded-lg bg-slate-200 text-slate-400 font-bold py-2.5 px-5 text-sm cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Buy PIN (Coming Soon)
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const schools = <?= json_encode($schools) ?>;
    const searchInput = document.getElementById('school_search');
    const hiddenInput = document.getElementById('school_id');
    const dropdown = document.getElementById('school_dropdown');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        dropdown.innerHTML = '';
        hiddenInput.value = '';
        if (query.length < 2) { dropdown.classList.add('hidden'); return; }
        const matches = schools.filter(s => s.school_name.toLowerCase().includes(query));
        if (matches.length === 0) { dropdown.innerHTML = '<div class="p-3 text-sm text-slate-500">No schools found</div>'; dropdown.classList.remove('hidden'); return; }
        dropdown.classList.remove('hidden');
        matches.forEach(match => {
            const div = document.createElement('div');
            div.className = 'p-3 cursor-pointer hover:bg-royal-50 text-sm font-medium text-slate-700 border-b border-slate-100 last:border-0';
            div.textContent = match.school_name;
            div.onclick = function() {
                searchInput.value = match.school_name;
                hiddenInput.value = match.id;
                dropdown.classList.add('hidden');
            };
            dropdown.appendChild(div);
        });
    });
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('hidden');
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>