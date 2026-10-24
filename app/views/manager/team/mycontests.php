<?php
/** @var array $entries */
$pageTitle = 'My Contests';
$active = 'manager/mycontests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-3xl font-bold">My Contests</h1>
    <p class="text-muted text-sm mt-1">Every contest you've joined, and the team you built for each.</p>

    <?php if ($entries === []): ?>
        <div class="card p-10 text-center mt-8">
            <p class="text-muted mb-4">You haven't joined any contests yet.</p>
            <a href="/index.php?route=manager/contests" class="btn btn-saffron">Browse open contests</a>
        </div>
    <?php else: ?>
    <div class="space-y-3 mt-6">
        <?php foreach ($entries as $e):
            $contest = ['status' => $e['contest_status'], 'match_datetime' => $e['match_datetime']];
            $locked = is_contest_locked($e['match_datetime']);
        ?>
            <div class="card p-4 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <?= franchise_chip($e['franchise_a_name'], $e['franchise_a_code']) ?>
                    <span class="text-muted text-sm">vs</span>
                    <?= franchise_chip($e['franchise_b_name'], $e['franchise_b_code']) ?>
                    <span class="text-xs text-muted"><?= format_datetime($e['match_datetime']) ?></span>
                </div>
                <div class="flex items-center gap-4">
                    <?= contest_status_pill($contest) ?>
                    <span class="font-display text-2xl text-indigo tabular-nums"><?= format_points($e['total_points']) ?></span>
                    <a href="/index.php?route=manager/team/breakdown&entry_id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-ghost">Breakdown</a>
                    <?php if (!$locked): ?>
                        <a href="/index.php?route=manager/team/build&contest_id=<?= (int)$e['contest_id'] ?>" class="btn btn-sm btn-primary">Edit team</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
