<?php
// 1. استارت سشن
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. تنظیمات
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// 3. چک امنیتی
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 4. هدر
$page_title = 'About List-e-Moon';
include __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-10rem)] flex items-center justify-center p-4">
    
    <div class="bg-slate-800/90 backdrop-blur-xl p-8 rounded-3xl shadow-2xl border border-slate-700/50 max-w-3xl w-full text-center transform transition-all duration-500 hover:shadow-purple-500/10 relative overflow-hidden">
        
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-1 bg-gradient-to-r from-transparent via-purple-500 to-transparent opacity-50"></div>

        <div class="mb-8 relative group inline-block">
            <div class="absolute -inset-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full blur opacity-30 group-hover:opacity-60 transition duration-1000 group-hover:duration-200"></div>
            
            <img src="assets/img/favicon.jpg" 
                 alt="List-e-Moon Logo" 
                 class="relative w-40 h-40 rounded-full border-4 border-slate-800 shadow-2xl object-cover transform transition duration-500 group-hover:scale-105 group-hover:rotate-3">
        </div>

        <h1 class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-purple-300 to-pink-300 mb-2 tracking-tight">
            List-e-Moon
        </h1>

        <h2 class="text-xl font-bold text-indigo-400 mb-6 tracking-wide uppercase opacity-90">
            A Shared Universe for Pop Culture Obsessions
        </h2>
        
        <div class="text-slate-400 text-lg mb-8 leading-relaxed max-w-xl mx-auto space-y-2 border-t border-slate-700/50 pt-6">
            <p class="font-medium text-slate-200">
                Track. Share. Compete.
            </p>
            <p>
                Your private hub to track <span class="text-indigo-400 font-bold">Movies</span>, <span class="text-emerald-400 font-bold">Games</span>, and <span class="text-rose-400 font-bold">Books</span> together.
            </p>
            <p class="text-sm text-slate-500 mt-2">
                Rate your favorites, check friends' activity, and climb the Leaderboard.
            </p>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-10">
            <div class="flex flex-col items-center gap-1 p-4 bg-slate-700/40 hover:bg-slate-700/60 rounded-2xl border border-slate-600/30 transition-colors backdrop-blur-sm group">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">🏰</div>
                <span class="text-slate-200 font-bold text-sm">Private Hub</span>
                <span class="text-slate-400 text-xs">Just for us</span>
            </div>
            
            <div class="flex flex-col items-center gap-1 p-4 bg-slate-700/40 hover:bg-slate-700/60 rounded-2xl border border-slate-600/30 transition-colors backdrop-blur-sm group">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">👀</div>
                <span class="text-slate-200 font-bold text-sm">Social Feed</span>
                <span class="text-slate-400 text-xs">See friends' activity</span>
            </div>
            
            <div class="flex flex-col items-center gap-1 p-4 bg-slate-700/40 hover:bg-slate-700/60 rounded-2xl border border-slate-600/30 transition-colors backdrop-blur-sm group">
                <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">🔥</div>
                <span class="text-slate-200 font-bold text-sm">Competitions</span>
                <span class="text-slate-400 text-xs">Compete for XP</span>
            </div>
        </div>

        <a href="https://github.com/moein8668-git/List-e-Moon" target="_blank" 
           class="inline-flex items-center gap-3 bg-white text-slate-900 hover:bg-indigo-50 font-bold py-3 px-8 rounded-full transition-all duration-300 transform hover:-translate-y-1 shadow-lg hover:shadow-indigo-500/20 group">
            <i class="fab fa-github text-2xl group-hover:rotate-12 transition-transform"></i>
            <span>Star on GitHub</span>
        </a>

        <div class="mt-10 pt-6 border-t border-slate-700/50 text-slate-500 text-sm font-mono flex items-center justify-center gap-1">
            Made with <span class="text-red-500 text-lg animate-pulse">♥</span> by 
            <a href="https://github.com/moein8668-git" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition underline decoration-dotted decoration-indigo-500/30 hover:decoration-indigo-400">Moein</a>
        </div>
    </div>

</div>

<?php
include __DIR__ . '/includes/footer.php';
?>