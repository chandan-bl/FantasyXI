<?php
/** @var array $entry */
/** @var array $contest */
/** @var array $players */
/** @var array $breakdowns */
$pageTitle = 'Points Breakdown';
$active = 'manager/mycontests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=manager/mycontests" class="text-sm text-indigo font-semibold hover:underline">&larr; My Contests</a>

    <div class="stat-card scoreboard-texture p-6 mt-3">
        <div class="flex items-center justify-center gap-4">
            <?= franchise_chip($contest['franchise_a_name'], $contest['franchise_a_code']) ?>
            <span class="font-display text-2xl text-saffron">VS</span>
            <?= franchise_chip($contest['franchise_b_name'], $contest['franchise_b_code']) ?>
        </div>
        <p class="text-center text-sm text-white/70 mt-2"><?= format_datetime($contest['match_datetime']) ?></p>
        <div class="text-center mt-4">
            <p class="stat-label">Total Team Points</p>
            <p class="font-display text-5xl text-saffron"><?= format_points($entry['total_points']) ?></p>
        </div>
    </div>

    <p class="text-muted text-sm mt-6">Every scoring rule that fired for each of your 11 players, per the fixed points rubric. Captain gets 2x, Vice-Captain 1.5x, applied to the base total below.</p>

    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <?php foreach ($players as $p):
            $b = $breakdowns[(int)$p['player_id']];
            $lines = $b['lines'];
            $multiplier = $b['multiplier'];
            $base = $lines === null ? null : array_sum(array_column($lines, 'points'));
            $final = $base === null ? null : round($base * $multiplier, 1);
        ?>
            <div class="ledger scoreboard-texture">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold"><?= e($p['name']) ?></p>
                        <p class="text-xs text-white/60"><?= role_label($p['role']) ?> &middot; <?= e($p['franchise_code']) ?></p>
                    </div>
                    <?php if ($p['is_captain']): ?>
                        <span class="pill" style="background:#F0A202;color:#1E1B4B">CAPTAIN &middot; 2x</span>
                    <?php elseif ($p['is_vice_captain']): ?>
                        <span class="pill" style="background:#DEDAF6;color:#1E1B4B">VICE-CAPTAIN &middot; 1.5x</span>
                    <?php endif; ?>
                </div>

                <div class="mt-3">
                <?php if ($lines === null): ?>
                    <p class="text-sm text-white/50 italic py-3">Match stats haven't been entered for this contest yet.</p>
                <?php else: ?>
                    <?php foreach ($lines as $line): ?>
                        <div class="ledger-row">
                            <span><?= e($line['label']) ?></span>
                            <span class="<?= $line['points'] < 0 ? 'pts-neg' : 'pts-pos' ?>"><?= format_points($line['points']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>

                <?php if ($final !== null): ?>
                <div class="ledger-total">
                    <span class="text-xs text-white/60">Base <?= format_points($base) ?> &times; <?= rtrim(rtrim(number_format($multiplier, 1), '0'), '.') ?></span>
                    <span class="value"><?= format_points($final) ?></span>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
