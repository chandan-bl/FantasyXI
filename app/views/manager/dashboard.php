<?php
/** @var array $stats */
/** @var array $recentEntries */
/** @var array $upcomingOpen */
$pageTitle = 'My Dashboard';
$active = 'manager/dashboard';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
require __DIR__ . '/../partials/flash.php';
?>
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold">Hey, <?= e(explode(' ', Auth::name() ?? '')[0]) ?> 👋</h1>
            <p class="text-muted text-sm mt-1">Here's where your teams stand.</p>
        </div>
        <a href="/index.php?route=manager/contests" class="btn btn-saffron">Join a Contest</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= format_points($stats['season_points']) ?></p><p class="stat-label">Season Points</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= $stats['season_rank'] ? '#' . $stats['season_rank'] : '-' ?></p><p class="stat-label">Season Rank <?= $stats['season_total'] ? 'of ' . $stats['season_total'] : '' ?></p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['contests_joined'] ?></p><p class="stat-label">Contests Joined</p></div>
        <div class="stat-card scoreboard-texture p-4"><p class="stat-value"><?= (int)$stats['open_contests'] ?></p><p class="stat-label">Open To Join</p></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-8">
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold">My recent teams</h2>
                <a href="/index.php?route=manager/mycontests" class="text-sm text-indigo font-semibold hover:underline">View all &rarr;</a>
            </div>
            <?php if ($recentEntries === []): ?>
                <p class="text-muted text-sm py-4">You haven't joined a contest yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                <?php foreach ($recentEntries as $entry): $entryContest = ['status' => $entry['contest_status'], 'match_datetime' => $entry['match_datetime']]; ?>
                    <a href="/index.php?route=manager/team/breakdown&entry_id=<?= (int)$entry['id'] ?>" class="flex items-center justify-between border border-line rounded-lg px-3 py-2.5 hover:border-indigo transition">
                        <span class="text-sm font-semibold"><?= e($entry['franchise_a_code']) ?> vs <?= e($entry['franchise_b_code']) ?></span>
                        <span class="flex items-center gap-2">
                            <?= contest_status_pill($entryContest) ?>
                            <span class="tabular-nums font-bold text-indigo"><?= format_points($entry['total_points']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold">Open contests</h2>
                <a href="/index.php?route=manager/contests" class="text-sm text-indigo font-semibold hover:underline">Browse all &rarr;</a>
            </div>
            <?php if ($upcomingOpen === []): ?>
                <p class="text-muted text-sm py-4">No open contests right now -- check back soon.</p>
            <?php else: ?>
                <div class="space-y-3">
                <?php foreach ($upcomingOpen as $c): ?>
                    <a href="/index.php?route=manager/team/build&contest_id=<?= (int)$c['id'] ?>" class="flex items-center justify-between border border-line rounded-lg px-3 py-2.5 hover:border-indigo transition">
                        <span class="text-sm font-semibold"><?= e($c['franchise_a_code']) ?> vs <?= e($c['franchise_b_code']) ?></span>
                        <?= contest_status_pill($c) ?>
                    </a>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
