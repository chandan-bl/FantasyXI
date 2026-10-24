<?php
/** @var array $contests */
$pageTitle = 'Contests';
$active = 'admin/contests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold">Contests</h1>
            <p class="text-muted text-sm mt-1">One contest per real IPL fixture. The match date/time is also the team-lock deadline.</p>
        </div>
        <a href="/index.php?route=admin/contests/create" class="btn btn-saffron">+ New Contest</a>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 mt-6">
        <?php foreach ($contests as $c): $locked = is_contest_locked($c['match_datetime']); ?>
            <div class="card p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <?= contest_status_pill($c) ?>
                    <span class="text-xs text-muted"><?= (int)$c['entry_count'] ?> joined</span>
                </div>
                <div class="flex items-center justify-center gap-3 py-2">
                    <?= franchise_chip($c['franchise_a_name'], $c['franchise_a_code']) ?>
                    <span class="font-display text-lg text-muted">vs</span>
                    <?= franchise_chip($c['franchise_b_name'], $c['franchise_b_code']) ?>
                </div>
                <p class="text-center text-sm text-muted"><?= format_datetime($c['match_datetime']) ?></p>
                <?php if (!empty($c['venue'])): ?><p class="text-center text-xs text-muted -mt-2"><?= e($c['venue']) ?></p><?php endif; ?>
                <div class="flex gap-2 pt-2 border-t border-line mt-1">
                    <a href="/index.php?route=admin/contests/view&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-ghost flex-1">View</a>
                    <?php if ($locked && $c['status'] !== 'completed'): ?>
                        <a href="/index.php?route=admin/contests/stats&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-saffron flex-1">Enter stats</a>
                    <?php elseif (!$locked): ?>
                        <a href="/index.php?route=admin/contests/edit&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-ghost flex-1">Edit</a>
                    <?php else: ?>
                        <a href="/index.php?route=admin/contests/stats&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-ghost flex-1">Stats</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
