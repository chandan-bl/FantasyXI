<?php
/** @var array $contest */
/** @var array $leaderboard */
$pageTitle = 'Contest Leaderboard';
$active = 'leaderboard/season';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
require __DIR__ . '/../partials/flash.php';
$myId = Auth::role() === 'manager' ? Auth::id() : null;
?>
<main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=leaderboard/season" class="text-sm text-indigo font-semibold hover:underline">&larr; Season Leaderboard</a>

    <div class="stat-card scoreboard-texture p-6 mt-3">
        <div class="flex items-center justify-center gap-4">
            <?= franchise_chip($contest['franchise_a_name'], $contest['franchise_a_code']) ?>
            <span class="font-display text-2xl text-saffron">VS</span>
            <?= franchise_chip($contest['franchise_b_name'], $contest['franchise_b_code']) ?>
        </div>
        <p class="text-center text-sm text-white/70 mt-2"><?= format_datetime($contest['match_datetime']) ?></p>
        <div class="flex justify-center mt-2"><?= contest_status_pill($contest) ?></div>
    </div>

    <div class="card mt-6 overflow-x-auto">
        <?php if ($leaderboard === []): ?>
            <p class="p-6 text-muted text-sm">No managers have joined this contest yet.</p>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-muted border-b border-line"><th class="py-3 px-4">#</th><th class="py-3 px-4">Manager</th><th class="py-3 px-4 text-right">Credits used</th><th class="py-3 px-4 text-right">Points</th></tr></thead>
            <tbody>
            <?php foreach ($leaderboard as $i => $row): $isMe = $myId !== null && (int)$row['manager_id'] === $myId; ?>
                <tr class="border-b border-line last:border-0 <?= $isMe ? 'bg-saffron-50' : '' ?>">
                    <td class="py-3 px-4 font-display text-xl <?= $i < 3 ? 'text-saffron' : 'text-indigo' ?>"><?= $i + 1 ?></td>
                    <td class="py-3 px-4 font-semibold"><?= e($row['manager_name']) ?><?= $isMe ? ' <span class="text-xs text-indigo font-normal">(you)</span>' : '' ?></td>
                    <td class="py-3 px-4 text-right tabular-nums text-muted"><?= format_credits($row['total_credits_used']) ?></td>
                    <td class="py-3 px-4 text-right tabular-nums font-bold text-indigo text-lg"><?= format_points($row['total_points']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
