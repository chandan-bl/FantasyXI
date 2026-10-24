<?php
/** @var string $active */
$role = Auth::role();
$links = $role === 'super_admin'
    ? [
        'admin/dashboard'  => 'Dashboard',
        'admin/players'    => 'Player Pool',
        'admin/franchises' => 'Franchises',
        'admin/contests'   => 'Contests',
        'admin/managers'   => 'Managers',
        'leaderboard/season' => 'Leaderboard',
    ]
    : [
        'manager/dashboard'  => 'Dashboard',
        'manager/contests'   => 'Join a Contest',
        'manager/mycontests' => 'My Contests',
        'leaderboard/season' => 'Leaderboard',
    ];
?>
<header class="bg-indigo-ink sticky top-0 z-40 shadow-lg shadow-indigo-ink/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
        <a href="/index.php?route=<?= Auth::check() ? e(Auth::dashboardRoute($role)) : 'login' ?>" class="flex items-center gap-2 shrink-0">
            <svg width="26" height="26" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="46" fill="#F0A202"/><path d="M50 4 A46 46 0 0 1 50 96" stroke="#1E1B4B" stroke-width="3" fill="none" stroke-dasharray="6 5"/></svg>
            <span class="font-display text-2xl text-white tracking-wide leading-none">Fantasy<span class="text-saffron">XI</span></span>
        </a>
        <nav class="hidden md:flex items-center gap-1">
            <?php foreach ($links as $route => $label): ?>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="nav-link px-3 py-2 rounded-md text-sm <?= ($active ?? '') === $route ? 'nav-link-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="flex items-center gap-3">
            <span class="hidden sm:block text-xs text-white/60">
                <?= e(Auth::name() ?? '') ?>
                <span class="text-saffron">&middot; <?= $role === 'super_admin' ? 'League Admin' : 'Manager' ?></span>
            </span>
            <form method="POST" action="/index.php?route=logout">
                <?= Csrf::field() ?>
                <button class="btn btn-sm btn-ghost !bg-transparent !border-white/20 !text-white/80 hover:!text-white hover:!border-white/50">Log out</button>
            </form>
        </div>
    </div>
    <nav class="md:hidden flex overflow-x-auto no-scrollbar gap-1 px-4 pb-2">
        <?php foreach ($links as $route => $label): ?>
            <a href="/index.php?route=<?= e($route) ?>"
               class="nav-link px-3 py-1.5 rounded-md text-xs whitespace-nowrap <?= ($active ?? '') === $route ? 'nav-link-active' : '' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
