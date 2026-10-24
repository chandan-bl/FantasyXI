<?php
/** @var array $stats */
/** @var array $recentContests */
$pageTitle = 'Admin Dashboard';
$active = 'admin/dashboard';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
require __DIR__ . '/../partials/flash.php';
?>
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold">League control room</h1>
            <p class="text-muted text-sm mt-1">The full state of FantasyXI at a glance.</p>
        </div>
        <div class="flex gap-2">
            <a href="/index.php?route=admin/contests/create" class="btn btn-saffron">+ New Contest</a>
            <a href="/index.php?route=admin/players/create" class="btn btn-ghost">+ Add Player</a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-6">
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['players'] ?></p><p class="stat-label">Players</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['franchises'] ?></p><p class="stat-label">Franchises</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['managers'] ?></p><p class="stat-label">Managers</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['open'] ?></p><p class="stat-label">Open Contests</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value text-rose-300"><?= (int)$stats['pending'] ?></p><p class="stat-label">Pending Scoring</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['completed'] ?></p><p class="stat-label">Completed</p></div>
    </div>

    <?php if ($stats['pending'] > 0): ?>
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm text-amber-900"><strong><?= (int)$stats['pending'] ?> contest(s)</strong> have passed their deadline and are waiting on match stats + a scoring run.</p>
            <a href="/index.php?route=admin/contests" class="btn btn-sm btn-saffron">Enter stats</a>
        </div>
    <?php endif; ?>

    <div class="mt-8 card p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Recent contests</h2>
            <a href="/index.php?route=admin/contests" class="text-sm text-indigo font-semibold hover:underline">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted border-b border-line">
                    <th class="py-2 pr-4">Fixture</th>
                    <th class="py-2 pr-4">Match date</th>
                    <th class="py-2 pr-4">Entries</th>
                    <th class="py-2 pr-4">Status</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentContests as $c): ?>
                    <tr class="border-b border-line last:border-0">
                        <td class="py-3 pr-4 whitespace-nowrap">
                            <?= franchise_chip($c['franchise_a_name'], $c['franchise_a_code']) ?>
                            <span class="text-muted text-xs mx-1">vs</span>
                            <?= franchise_chip($c['franchise_b_name'], $c['franchise_b_code']) ?>
                        </td>
                        <td class="py-3 pr-4 text-muted"><?= format_datetime($c['match_datetime']) ?></td>
                        <td class="py-3 pr-4"><?= (int)$c['entry_count'] ?></td>
                        <td class="py-3 pr-4"><?= contest_status_pill($c) ?></td>
                        <td class="py-3 text-right">
                            <a class="text-indigo text-sm font-semibold hover:underline" href="/index.php?route=admin/contests/view&id=<?= (int)$c['id'] ?>">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
