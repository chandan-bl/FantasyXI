<?php
/** @var array|null $contest */
/** @var array $franchises */
$contest = $contest ?? null;
$pageTitle = $contest ? 'Edit Contest' : 'New Contest';
$active = 'admin/contests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';

$dtValue = '';
if ($contest) {
    $ts = strtotime($contest['match_datetime']);
    $dtValue = $ts ? date('Y-m-d\TH:i', $ts) : '';
}
?>
<main class="flex-1 max-w-xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=admin/contests" class="text-sm text-indigo font-semibold hover:underline">&larr; Back to Contests</a>
    <h1 class="text-3xl font-bold mt-2"><?= $contest ? 'Edit Contest' : 'New Contest' ?></h1>
    <p class="text-muted text-sm mt-1">Pick the two competing franchises and the match date/time. That date/time also becomes this contest's team-lock deadline.</p>

    <form method="POST" action="/index.php?route=admin/contests/<?= $contest ? 'update' : 'store' ?>" class="card p-6 mt-6 space-y-4">
        <?= Csrf::field() ?>
        <?php if ($contest): ?><input type="hidden" name="id" value="<?= (int)$contest['id'] ?>"><?php endif; ?>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Franchise A</label>
                <select name="franchise_a_id" required class="w-full rounded-lg border border-line px-3 py-2">
                    <?php foreach ($franchises as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= (int)($contest['franchise_a_id'] ?? 0) === (int)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Franchise B</label>
                <select name="franchise_b_id" required class="w-full rounded-lg border border-line px-3 py-2">
                    <?php foreach ($franchises as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= (int)($contest['franchise_b_id'] ?? 0) === (int)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Match date &amp; time (also the team-lock deadline)</label>
            <input type="datetime-local" name="match_datetime" required class="w-full rounded-lg border border-line px-3 py-2" value="<?= e($dtValue) ?>">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Venue <span class="text-muted font-normal">(optional)</span></label>
            <input name="venue" class="w-full rounded-lg border border-line px-3 py-2" value="<?= e($contest['venue'] ?? '') ?>">
        </div>

        <div class="rounded-lg bg-indigo-50 text-xs text-indigo-dark px-3 py-2">
            Only franchises whose combined active player pool can fill every quota slot (1 wicketkeeper, 3-6 batters, 1-4 all-rounders, 3-6 bowlers) within the 7-per-franchise cap will be accepted -- this is checked server-side on save.
        </div>

        <div class="pt-2 flex gap-3">
            <button class="btn btn-saffron"><?= $contest ? 'Save changes' : 'Create contest' ?></button>
            <a href="/index.php?route=admin/contests" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
