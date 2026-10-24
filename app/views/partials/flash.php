<?php
$success = flash_get('success');
$error = flash_get('error');
?>
<?php if ($success): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium"><?= e($success) ?></div>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
        <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-medium"><?= e($error) ?></div>
    </div>
<?php endif; ?>
