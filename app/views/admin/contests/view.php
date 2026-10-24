<?php
/** @var array $contest */
/** @var array $leaderboard */
$pageTitle = 'Contest Details';
$active = 'admin/contests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
$locked = is_contest_locked($contest['match_datetime']);
?>
<main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=admin/contests" class="text-sm text-indigo font-semibold hover:underline">&larr; Back to Contests</a>

    <div class="stat-card scoreboard-texture p-6 mt-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <?= contest_status_pill($contest) ?>
            <span class="text-xs text-white/60"><?= (int)$contest['venue'] ? e($contest['venue']) : '' ?></span>
        </div>
        <div class="flex items-center justify-center gap-4 py-4">
            <div class="text-center">
                <?= franchise_chip($contest['franchise_a_name'], $contest['franchise_a_code']) ?>
                <p class="text-xs text-white/60 mt-1"><?= e($contest['franchise_a_name']) ?></p>
            </div>
            <span class="font-display text-3xl text-saffron">VS</span>
            <div class="text-center">
                <?= franchise_chip($contest['franchise_b_name'], $contest['franchise_b_code']) ?>
                <p class="text-xs text-white/60 mt-1"><?= e($contest['franchise_b_name']) ?></p>
            </div>
        </div>
        <p class="text-center text-sm text-white/70"><?= format_datetime($contest['match_datetime']) ?></p>
    </div>

    <div class="flex flex-wrap gap-3 mt-6">
        <?php if (!$locked): ?>
            <a href="/index.php?route=admin/contests/edit&id=<?= (int)$contest['id'] ?>" class="btn btn-ghost">Edit fixture</a>
        <?php endif; ?>
        <?php if ($locked): ?>
            <a href="/index.php?route=admin/contests/stats&id=<?= (int)$contest['id'] ?>" class="btn btn-saffron">Match stats &amp; scoring</a>
        <?php endif; ?>
        <a href="/index.php?route=leaderboard/contest&id=<?= (int)$contest['id'] ?>" class="btn btn-ghost">Full leaderboard</a>
    </div>

    <div class="card p-5 mt-6">
        <h2 class="font-bold mb-3">Leaderboard <span class="text-muted font-normal text-sm">(<?= count($leaderboard) ?> managers joined)</span></h2>
        <?php if ($leaderboard === []): ?>
            <p class="text-muted text-sm">No managers have joined this contest yet.</p>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-muted border-b border-line"><th class="py-2">#</th><th class="py-2">Manager</th><th class="py-2 text-right">Credits used</th><th class="py-2 text-right">Points</th></tr></thead>
            <tbody>
            <?php foreach ($leaderboard as $i => $row): ?>
                <tr class="border-b border-line last:border-0">
                    <td class="py-2 font-display text-lg text-indigo w-10"><?= $i + 1 ?></td>
                    <td class="py-2 font-semibold"><?= e($row['manager_name']) ?></td>
                    <td class="py-2 text-right tabular-nums text-muted"><?= format_credits($row['total_credits_used']) ?></td>
                    <td class="py-2 text-right tabular-nums font-bold text-indigo"><?= format_points($row['total_points']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
