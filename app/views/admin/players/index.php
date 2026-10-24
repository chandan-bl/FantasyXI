<?php
/** @var array $players */
/** @var array $franchises */
$pageTitle = 'Player Pool';
$active = 'admin/players';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold">Player Pool</h1>
            <p class="text-muted text-sm mt-1"><?= count($players) ?> players &middot; credit values drive every contest's 100-credit budget cap.</p>
        </div>
        <a href="/index.php?route=admin/players/create" class="btn btn-saffron">+ Add Player</a>
    </div>

    <form method="GET" action="/index.php" class="flex flex-wrap gap-3 mt-6">
        <input type="hidden" name="route" value="admin/players">
        <select name="role" class="rounded-lg border border-line px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">All roles</option>
            <?php foreach (['wicketkeeper', 'batter', 'allrounder', 'bowler'] as $r): ?>
                <option value="<?= $r ?>" <?= ($_GET['role'] ?? '') === $r ? 'selected' : '' ?>><?= role_label($r) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="franchise_id" class="rounded-lg border border-line px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">All franchises</option>
            <?php foreach ($franchises as $f): ?>
                <option value="<?= (int)$f['id'] ?>" <?= (string)($_GET['franchise_id'] ?? '') === (string)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (($_GET['role'] ?? '') !== '' || ($_GET['franchise_id'] ?? '') !== ''): ?>
            <a href="/index.php?route=admin/players" class="btn btn-sm btn-ghost">Clear filters</a>
        <?php endif; ?>
    </form>

    <div class="card mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted border-b border-line">
                    <th class="py-3 px-4">Player</th>
                    <th class="py-3 px-4">Franchise</th>
                    <th class="py-3 px-4">Role</th>
                    <th class="py-3 px-4 text-right">Credits</th>
                    <th class="py-3 px-4 text-right">Runs</th>
                    <th class="py-3 px-4 text-right">Wkts</th>
                    <th class="py-3 px-4 text-right">Matches</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $p): ?>
                    <tr class="border-b border-line last:border-0 <?= $p['status'] === 'inactive' ? 'opacity-50' : '' ?>">
                        <td class="py-2.5 px-4 font-semibold"><?= e($p['name']) ?></td>
                        <td class="py-2.5 px-4"><?= franchise_chip($p['franchise_name'], $p['franchise_code']) ?></td>
                        <td class="py-2.5 px-4"><?= role_badge($p['role']) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums font-semibold text-indigo"><?= format_credits($p['credit_value']) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-muted"><?= (int)$p['career_runs'] ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-muted"><?= (int)$p['career_wickets'] ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-muted"><?= (int)$p['career_matches'] ?></td>
                        <td class="py-2.5 px-4">
                            <span class="pill <?= $p['status'] === 'active' ? 'pill-open' : 'pill-locked' ?>"><?= e(ucfirst($p['status'])) ?></span>
                        </td>
                        <td class="py-2.5 px-4 text-right whitespace-nowrap">
                            <a class="text-indigo text-xs font-semibold hover:underline" href="/index.php?route=admin/players/edit&id=<?= (int)$p['id'] ?>">Edit</a>
                            <form method="POST" action="/index.php?route=admin/players/status" class="inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="status" value="<?= $p['status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button class="text-xs font-semibold ml-2 <?= $p['status'] === 'active' ? 'text-rose-600' : 'text-emerald-600' ?> hover:underline"><?= $p['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($players === []): ?>
                    <tr><td colspan="9" class="py-8 text-center text-muted">No players match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
