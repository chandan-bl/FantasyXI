<?php
/** @var array $old */
$pageTitle = 'Log in';
require __DIR__ . '/../partials/header.php';
$old = $old ?? [];
?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-4xl grid md:grid-cols-2 rounded-2xl overflow-hidden shadow-xl shadow-indigo-ink/10">
        <div class="stat-card scoreboard-texture hidden md:flex flex-col justify-between p-10">
            <div>
                <div class="flex items-center gap-2">
                    <svg width="30" height="30" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="46" fill="#F0A202"/></svg>
                    <span class="font-display text-3xl tracking-wide">Fantasy<span class="text-saffron">XI</span></span>
                </div>
                <p class="text-white/70 mt-6 text-sm leading-relaxed">Pick your XI from the real IPL squads playing each fixture. Budget-capped, role-quota'd, captain-multiplied -- built the way the real thing works.</p>
            </div>
            <div class="grid grid-cols-3 gap-4 mt-10">
                <div><p class="stat-value">100</p><p class="stat-label">Credit Cap</p></div>
                <div><p class="stat-value">2x</p><p class="stat-label">Captain</p></div>
                <div><p class="stat-value">88</p><p class="stat-label">Real Players</p></div>
            </div>
        </div>
        <div class="bg-white p-8 sm:p-10">
            <h1 class="text-2xl font-bold">Welcome back</h1>
            <p class="text-muted text-sm mt-1">Log in to build your team or manage the league.</p>

            <?php require __DIR__ . '/../partials/flash.php'; ?>

            <form method="POST" action="/index.php?route=login" class="mt-6 space-y-4">
                <?= Csrf::field() ?>
                <div>
                    <label class="block text-sm font-semibold mb-1" for="email">Email</label>
                    <input class="w-full rounded-lg border border-line px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo" type="email" id="email" name="email" value="<?= old($old, 'email') ?>" required autofocus>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1" for="password">Password</label>
                    <input class="w-full rounded-lg border border-line px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo" type="password" id="password" name="password" required>
                </div>
                <button class="btn btn-primary w-full" type="submit">Log in</button>
            </form>

            <p class="text-sm text-muted mt-6">New manager? <a class="text-indigo font-semibold hover:underline" href="/index.php?route=register">Create an account</a></p>

            <div class="mt-8 pt-6 border-t border-line text-xs text-muted space-y-1">
                <p class="font-semibold text-ink">Demo credentials (password: <code class="bg-indigo-50 px-1 rounded">password123</code>)</p>
                <p>League Admin &middot; admin@fantasyxi.test</p>
                <p>Manager &middot; aarav@fantasyxi.test</p>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
