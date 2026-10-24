<?php
/** @var array $contests */
$pageTitle = 'Join a Contest';
$active = 'manager/contests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-3xl font-bold">Join a Contest</h1>
    <p class="text-muted text-sm mt-1">Pick a fixture, then build your 11-player team from that match's two squads before the deadline.</p>

    <?php if ($contests === []): ?>
        <div class="card p-10 text-center mt-8">
            <p class="text-muted">No open contests right now. Check back once the League Admin schedules a fixture, or see your existing teams under "My Contests".</p>
        </div>
    <?php else: ?>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 mt-6">
        <?php foreach ($contests as $c): ?>
            <div class="card p-5 flex flex-col gap-3">
                <div class="flex justify-center">
                    <?= contest_status_pill($c) ?>
                </div>
                <div class="flex items-center justify-center gap-3 py-2">
                    <?= franchise_chip($c['franchise_a_name'], $c['franchise_a_code']) ?>
                    <span class="font-display text-lg text-muted">vs</span>
                    <?= franchise_chip($c['franchise_b_name'], $c['franchise_b_code']) ?>
                </div>
                <p class="text-center text-sm text-muted"><?= format_datetime($c['match_datetime']) ?></p>
                <?php if (!empty($c['venue'])): ?><p class="text-center text-xs text-muted -mt-2"><?= e($c['venue']) ?></p><?php endif; ?>
                <form method="POST" action="/index.php?route=manager/contests/join" class="mt-1">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="contest_id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-saffron w-full">Join &amp; build team</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
