<?php
/** @var array $managers */
$pageTitle = 'Managers';
$active = 'admin/managers';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';
?>
<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-3xl font-bold">Managers</h1>
    <p class="text-muted text-sm mt-1"><?= count($managers) ?> registered team owners.</p>

    <div class="card mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted border-b border-line">
                    <th class="py-3 px-4">Name</th>
                    <th class="py-3 px-4">Email</th>
                    <th class="py-3 px-4">Joined</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($managers as $m): ?>
                    <tr class="border-b border-line last:border-0">
                        <td class="py-2.5 px-4 font-semibold"><?= e($m['name']) ?></td>
                        <td class="py-2.5 px-4 text-muted"><?= e($m['email']) ?></td>
                        <td class="py-2.5 px-4 text-muted"><?= format_datetime($m['created_at']) ?></td>
                        <td class="py-2.5 px-4"><span class="pill <?= $m['status'] === 'active' ? 'pill-open' : 'pill-locked' ?>"><?= e(ucfirst($m['status'])) ?></span></td>
                        <td class="py-2.5 px-4 text-right">
                            <form method="POST" action="/index.php?route=admin/managers/status">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                <input type="hidden" name="status" value="<?= $m['status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button class="text-xs font-semibold <?= $m['status'] === 'active' ? 'text-rose-600' : 'text-emerald-600' ?> hover:underline"><?= $m['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
