<?php
/** @var array $old */
$pageTitle = 'Create account';
require __DIR__ . '/../partials/header.php';
$old = $old ?? [];
?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md card p-8 sm:p-10">
        <h1 class="text-2xl font-bold">Create your manager account</h1>
        <p class="text-muted text-sm mt-1">Sign up to join contests and build your XI.</p>

        <?php require __DIR__ . '/../partials/flash.php'; ?>

        <form method="POST" action="/index.php?route=register" class="mt-6 space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-semibold mb-1" for="name">Full name</label>
                <input class="w-full rounded-lg border border-line px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo" type="text" id="name" name="name" value="<?= old($old, 'name') ?>" required autofocus>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="email">Email</label>
                <input class="w-full rounded-lg border border-line px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo" type="email" id="email" name="email" value="<?= old($old, 'email') ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="password">Password</label>
                <input class="w-full rounded-lg border border-line px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo" type="password" id="password" name="password" minlength="8" required>
                <p class="text-xs text-muted mt-1">At least 8 characters.</p>
            </div>
            <button class="btn btn-saffron w-full" type="submit">Create account</button>
        </form>

        <p class="text-sm text-muted mt-6">Already have an account? <a class="text-indigo font-semibold hover:underline" href="/index.php?route=login">Log in</a></p>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
