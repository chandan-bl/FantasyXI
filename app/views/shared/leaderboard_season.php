<?php
/** @var array $season */
/** @var array $contests */
$pageTitle = 'Season Leaderboard';
$active = 'leaderboard/season';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
require __DIR__ . '/../partials/flash.php';
$myId = Auth::role() === 'manager' ? Auth::id() : null;
?>
<main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-3xl font-bold">Season Leaderboard</h1>
    <p class="text-muted text-sm mt-1">Cumulative points across every contest a manager has joined.</p>

    <div class="card mt-6 overflow-x-auto">
        <?php if ($season === []): ?>
            <p class="p-6 text-muted text-sm">No scored contests yet -- once a contest's match stats are entered and scoring is run, standings will appear here.</p>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-muted border-b border-line"><th class="py-3 px-4">#</th><th class="py-3 px-4">Manager</th><th class="py-3 px-4 text-right">Contests joined</th><th class="py-3 px-4 text-right">Season points</th></tr></thead>
            <tbody>
            <?php foreach ($season as $i => $row): $isMe = $myId !== null && (int)$row['manager_id'] === $myId; ?>
                <tr class="border-b border-line last:border-0 <?= $isMe ? 'bg-saffron-50' : '' ?>">
                    <td class="py-3 px-4 font-display text-xl <?= $i < 3 ? 'text-saffron' : 'text-indigo' ?>"><?= $i + 1 ?></td>
                    <td class="py-3 px-4 font-semibold"><?= e($row['manager_name']) ?><?= $isMe ? ' <span class="text-xs text-indigo font-normal">(you)</span>' : '' ?></td>
                    <td class="py-3 px-4 text-right tabular-nums text-muted"><?= (int)$row['contests_joined'] ?></td>
                    <td class="py-3 px-4 text-right tabular-nums font-bold text-indigo text-lg"><?= format_points($row['season_points']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <h2 class="text-xl font-bold mt-10">Per-contest leaderboards</h2>
    <div class="grid sm:grid-cols-2 gap-3 mt-4">
        <?php foreach ($contests as $c): ?>
            <a href="/index.php?route=leaderboard/contest&id=<?= (int)$c['id'] ?>" class="card p-4 flex items-center justify-between hover:border-indigo transition">
                <span class="text-sm font-semibold"><?= e($c['franchise_a_code']) ?> vs <?= e($c['franchise_b_code']) ?></span>
                <?= contest_status_pill($c) ?>
            </a>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
