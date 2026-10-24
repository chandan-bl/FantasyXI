<?php
/** @var array $contest */
/** @var array $pool */
/** @var array $existingStats */
$pageTitle = 'Match Stats';
$active = 'admin/contests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
$locked = is_contest_locked($contest['match_datetime']);
?>
<main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=admin/contests/view&id=<?= (int)$contest['id'] ?>" class="text-sm text-indigo font-semibold hover:underline">&larr; Back to Contest</a>
    <div class="flex items-center justify-between flex-wrap gap-3 mt-2">
        <div>
            <h1 class="text-3xl font-bold">Match stats &amp; scoring</h1>
            <p class="text-muted text-sm mt-1">
                <?= franchise_chip($contest['franchise_a_name'], $contest['franchise_a_code']) ?>
                vs
                <?= franchise_chip($contest['franchise_b_name'], $contest['franchise_b_code']) ?>
                &middot; <?= format_datetime($contest['match_datetime']) ?>
            </p>
        </div>
        <?= contest_status_pill($contest) ?>
    </div>

    <?php if (!$locked): ?>
        <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-sm px-4 py-3">
            This contest's deadline hasn't passed yet -- stats can't be entered or scored until it locks.
        </div>
    <?php else: ?>

    <form method="POST" action="/index.php?route=admin/contests/stats/simulate" class="mt-6" onsubmit="return confirm('Simulate realistic stats for every player in this contest? This overwrites any stats already saved below.');">
        <?= Csrf::field() ?>
        <input type="hidden" name="contest_id" value="<?= (int)$contest['id'] ?>">
        <button class="btn btn-ghost">Simulate match stats</button>
        <p class="text-xs text-muted mt-2">Randomly fills in a realistic scorecard (bounded team totals, per-role run/wicket splits, capped bowling figures) so you don't have to hand-type every field for a demo.</p>
    </form>

    <form method="POST" action="/index.php?route=admin/contests/stats/save" class="mt-6">
        <?= Csrf::field() ?>
        <input type="hidden" name="contest_id" value="<?= (int)$contest['id'] ?>">

        <div class="card overflow-x-auto">
        <table class="w-full text-xs min-w-[900px]">
            <thead>
                <tr class="text-left text-muted border-b border-line">
                    <th class="py-2 px-2 sticky left-0 bg-white">Player</th>
                    <th class="py-2 px-2 text-center">Played</th>
                    <th class="py-2 px-2 text-center">Runs</th>
                    <th class="py-2 px-2 text-center">4s</th>
                    <th class="py-2 px-2 text-center">6s</th>
                    <th class="py-2 px-2 text-center">Out?</th>
                    <th class="py-2 px-2 text-center">Wkts</th>
                    <th class="py-2 px-2 text-center">Bowled/LBW</th>
                    <th class="py-2 px-2 text-center">Maidens</th>
                    <th class="py-2 px-2 text-center">Catches</th>
                    <th class="py-2 px-2 text-center">St</th>
                    <th class="py-2 px-2 text-center">RO (direct)</th>
                    <th class="py-2 px-2 text-center">RO (assist)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pool as $p):
                    $s = $existingStats[(int)$p['id']] ?? null;
                    $val = fn($f, $def = 0) => e((string)($s[$f] ?? $def));
                    $checked = fn($f) => !empty($s[$f]) ? 'checked' : '';
                    $prefix = "stat[{$p['id']}]";
                ?>
                <tr class="border-b border-line last:border-0">
                    <td class="py-1.5 px-2 sticky left-0 bg-white whitespace-nowrap">
                        <span class="font-semibold"><?= e($p['name']) ?></span>
                        <?= role_badge($p['role']) ?>
                        <?= franchise_chip($p['franchise_name'], $p['franchise_code']) ?>
                    </td>
                    <td class="text-center"><input type="checkbox" name="<?= $prefix ?>[played]" value="1" <?= $checked('played') ?>></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[runs]" value="<?= $val('runs') ?>" class="w-14 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[fours]" value="<?= $val('fours') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[sixes]" value="<?= $val('sixes') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td class="text-center"><input type="checkbox" name="<?= $prefix ?>[is_out]" value="1" <?= $checked('is_out') ?>></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[wickets]" value="<?= $val('wickets') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[wickets_bowled_lbw]" value="<?= $val('wickets_bowled_lbw') ?>" class="w-14 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[maidens]" value="<?= $val('maidens') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[catches]" value="<?= $val('catches') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[stumpings]" value="<?= $val('stumpings') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[run_outs_direct]" value="<?= $val('run_outs_direct') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                    <td><input type="number" min="0" name="<?= $prefix ?>[run_outs_assisted]" value="<?= $val('run_outs_assisted') ?>" class="w-12 rounded border border-line px-1 py-1"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Save match stats</button>
        </div>
    </form>

    <form method="POST" action="/index.php?route=admin/contests/score" class="mt-4" onsubmit="return confirm('Run scoring for this contest? This recomputes every joined manager\'s points from the saved stats.');">
        <?= Csrf::field() ?>
        <input type="hidden" name="contest_id" value="<?= (int)$contest['id'] ?>">
        <button class="btn btn-saffron">Run scoring &amp; publish leaderboard</button>
        <p class="text-xs text-muted mt-2">Save stats first. Running scoring is safe to repeat -- it always recomputes from whatever stats are currently saved.</p>
    </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
