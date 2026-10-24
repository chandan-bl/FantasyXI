<?php
/** @var array|null $player */
/** @var array $franchises */
$player = $player ?? null;
$pageTitle = $player ? 'Edit Player' : 'Add Player';
$active = 'admin/players';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-2xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=admin/players" class="text-sm text-indigo font-semibold hover:underline">&larr; Back to Player Pool</a>
    <h1 class="text-3xl font-bold mt-2"><?= $player ? 'Edit ' . e($player['name']) : 'Add a Player' ?></h1>

    <form method="POST" action="/index.php?route=admin/players/<?= $player ? 'update' : 'store' ?>" class="card p-6 mt-6 space-y-4">
        <?= Csrf::field() ?>
        <?php if ($player): ?><input type="hidden" name="id" value="<?= (int)$player['id'] ?>"><?php endif; ?>

        <div>
            <label class="block text-sm font-semibold mb-1">Name</label>
            <input name="name" required class="w-full rounded-lg border border-line px-3 py-2" value="<?= e($player['name'] ?? '') ?>">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Franchise</label>
                <select name="franchise_id" required class="w-full rounded-lg border border-line px-3 py-2">
                    <?php foreach ($franchises as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= (int)($player['franchise_id'] ?? 0) === (int)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Role</label>
                <select name="role" required class="w-full rounded-lg border border-line px-3 py-2">
                    <?php foreach (['wicketkeeper', 'batter', 'allrounder', 'bowler'] as $r): ?>
                        <option value="<?= $r ?>" <?= ($player['role'] ?? '') === $r ? 'selected' : '' ?>><?= role_label($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Credit value <span class="text-muted font-normal">(6.5 - 10.5, in steps of 0.5)</span></label>
            <input type="number" name="credit_value" min="6.5" max="10.5" step="0.5" required class="w-full rounded-lg border border-line px-3 py-2" value="<?= e((string)($player['credit_value'] ?? '8.0')) ?>">
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Career runs</label>
                <input type="number" min="0" name="career_runs" class="w-full rounded-lg border border-line px-3 py-2" value="<?= (int)($player['career_runs'] ?? 0) ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Career wickets</label>
                <input type="number" min="0" name="career_wickets" class="w-full rounded-lg border border-line px-3 py-2" value="<?= (int)($player['career_wickets'] ?? 0) ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Career matches</label>
                <input type="number" min="0" name="career_matches" class="w-full rounded-lg border border-line px-3 py-2" value="<?= (int)($player['career_matches'] ?? 0) ?>">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Career trophies</label>
            <input type="number" min="0" name="career_trophies" class="w-40 rounded-lg border border-line px-3 py-2" value="<?= (int)($player['career_trophies'] ?? 0) ?>">
        </div>

        <div class="pt-2 flex gap-3">
            <button class="btn btn-saffron"><?= $player ? 'Save changes' : 'Add player' ?></button>
            <a href="/index.php?route=admin/players" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
