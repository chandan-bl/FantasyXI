<?php
/** @var array $franchises */
$pageTitle = 'Franchises';
$active = 'admin/franchises';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-3xl font-bold">Franchises</h1>
    <p class="text-muted text-sm mt-1">The 10 current IPL franchises. Deactivating a franchise won't delete its players, but its players can no longer be entered into new contests.</p>

    <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div class="card p-5">
            <h2 class="font-bold mb-3">Add a franchise</h2>
            <form method="POST" action="/index.php?route=admin/franchises/store" class="space-y-3">
                <?= Csrf::field() ?>
                <input name="name" required placeholder="Franchise name" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                <input name="short_code" required maxlength="10" placeholder="Short code (e.g. MI)" class="w-full rounded-lg border border-line px-3 py-2 text-sm uppercase">
                <button class="btn btn-saffron w-full">Add franchise</button>
            </form>
        </div>

        <div class="card p-5 md:col-span-1">
            <h2 class="font-bold mb-3">All franchises</h2>
            <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
            <?php foreach ($franchises as $f): $c = franchise_colors($f['name']); ?>
                <details class="rounded-lg border border-line px-3 py-2">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="flex items-center gap-2">
                            <span class="franchise-chip" style="background:<?= e($c['bg']) ?>;color:<?= e($c['fg']) ?>"><?= e($f['short_code']) ?></span>
                            <span class="font-semibold text-sm"><?= e($f['name']) ?></span>
                        </span>
                        <span class="pill <?= $f['status'] === 'active' ? 'pill-open' : 'pill-locked' ?>"><?= e(ucfirst($f['status'])) ?></span>
                    </summary>
                    <form method="POST" action="/index.php?route=admin/franchises/update" class="flex gap-2 mt-3">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <input name="name" value="<?= e($f['name']) ?>" class="flex-1 rounded-lg border border-line px-2 py-1 text-sm">
                        <input name="short_code" value="<?= e($f['short_code']) ?>" class="w-20 rounded-lg border border-line px-2 py-1 text-sm uppercase">
                        <button class="btn btn-sm btn-ghost">Save</button>
                    </form>
                    <form method="POST" action="/index.php?route=admin/franchises/status" class="mt-2">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <input type="hidden" name="status" value="<?= $f['status'] === 'active' ? 'inactive' : 'active' ?>">
                        <button class="text-xs font-semibold <?= $f['status'] === 'active' ? 'text-rose-600' : 'text-emerald-600' ?> hover:underline"><?= $f['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </details>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
