<?php
/** @var array $contest */
/** @var array $pool */
/** @var array|null $existingEntry */
/** @var array $existingPlayers */
/** @var bool $locked */
/** @var array $existingIds */
/** @var int|null $captainId */
/** @var int|null $vcId */
$pageTitle = 'Build Team';
$active = 'manager/contests';
require __DIR__ . '/../../partials/header.php';
require __DIR__ . '/../../partials/navbar.php';
require __DIR__ . '/../../partials/flash.php';

$readOnly = $locked; // existing entry + past deadline -> view only
?>
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <a href="/index.php?route=manager/contests" class="text-sm text-indigo font-semibold hover:underline">&larr; Contests</a>

    <div class="flex items-center justify-between flex-wrap gap-3 mt-2">
        <div>
            <h1 class="text-3xl font-bold">
                <?= e($contest['franchise_a_code']) ?> <span class="text-muted font-normal">vs</span> <?= e($contest['franchise_b_code']) ?>
            </h1>
            <p class="text-muted text-sm mt-1">
                Deadline <?= format_datetime($contest['match_datetime']) ?> &middot; pick exactly 11 players, 100-credit budget, max 7 from one franchise.
            </p>
        </div>
        <?= contest_status_pill($contest) ?>
    </div>

    <?php if ($readOnly): ?>
        <div class="mt-4 rounded-lg bg-slate-100 text-slate-700 text-sm px-4 py-3">
            This contest is locked, so your team is view-only below.
        </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?route=manager/team/save" id="team-form" data-readonly="<?= $readOnly ? '1' : '0' ?>" class="mt-6 grid lg:grid-cols-3 gap-6">
        <?= Csrf::field() ?>
        <input type="hidden" name="contest_id" value="<?= (int)$contest['id'] ?>">
        <input type="hidden" name="captain_id" id="captain_id_input" value="<?= (int)$captainId ?>">
        <input type="hidden" name="vice_captain_id" id="vc_id_input" value="<?= (int)$vcId ?>">

        <div class="lg:col-span-2 card p-4">
            <div class="flex flex-wrap gap-2 mb-3">
                <input type="text" id="search" placeholder="Search player..." class="rounded-lg border border-line px-3 py-1.5 text-sm flex-1 min-w-[160px]">
                <select id="role-filter" class="rounded-lg border border-line px-3 py-1.5 text-sm">
                    <option value="">All roles</option>
                    <option value="wicketkeeper">Wicketkeeper</option>
                    <option value="batter">Batter</option>
                    <option value="allrounder">All-rounder</option>
                    <option value="bowler">Bowler</option>
                </select>
            </div>
            <div class="overflow-x-auto max-h-[32rem] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white">
                    <tr class="text-left text-muted border-b border-line">
                        <th class="py-2 px-2"></th>
                        <th class="py-2 px-2">Player</th>
                        <th class="py-2 px-2">Franchise</th>
                        <th class="py-2 px-2">Role</th>
                        <th class="py-2 px-2 text-right">Credits</th>
                        <th class="py-2 px-2 text-right">Runs</th>
                        <th class="py-2 px-2 text-right">Wkts</th>
                    </tr>
                </thead>
                <tbody id="pool-body">
                <?php foreach ($pool as $p): $checked = in_array((int)$p['id'], $existingIds, true); ?>
                    <tr class="player-row border-b border-line last:border-0" data-name="<?= e(strtolower($p['name'])) ?>" data-role="<?= e($p['role']) ?>">
                        <td class="py-2 px-2">
                            <input type="checkbox" class="player-cb" name="player_ids[]" value="<?= (int)$p['id'] ?>"
                                   data-id="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-role="<?= e($p['role']) ?>"
                                   data-credit="<?= e((string)$p['credit_value']) ?>" data-franchise="<?= (int)$p['franchise_id'] ?>"
                                   <?= $checked ? 'checked' : '' ?> <?= $readOnly ? 'disabled' : '' ?>>
                        </td>
                        <td class="py-2 px-2 font-semibold"><?= e($p['name']) ?></td>
                        <td class="py-2 px-2"><?= franchise_chip($p['franchise_name'], $p['franchise_code']) ?></td>
                        <td class="py-2 px-2"><?= role_badge($p['role']) ?></td>
                        <td class="py-2 px-2 text-right tabular-nums font-semibold text-indigo"><?= format_credits($p['credit_value']) ?></td>
                        <td class="py-2 px-2 text-right tabular-nums text-muted"><?= (int)$p['career_runs'] ?></td>
                        <td class="py-2 px-2 text-right tabular-nums text-muted"><?= (int)$p['career_wickets'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-4">
                <h2 class="font-bold text-sm mb-2">Budget</h2>
                <div class="flex items-baseline justify-between mb-1">
                    <span id="credit-used" class="font-display text-2xl text-indigo">0.0</span>
                    <span class="text-xs text-muted">/ 100.0 credits</span>
                </div>
                <div class="credit-gauge-track"><div id="credit-fill" class="credit-gauge-fill" style="width:0%"></div></div>
            </div>

            <div class="card p-4">
                <h2 class="font-bold text-sm mb-2">Squad (<span id="squad-count">0</span>/11)</h2>
                <ul class="text-sm space-y-1">
                    <li class="flex justify-between"><span>Wicketkeeper</span><span id="count-wicketkeeper" class="tabular-nums font-semibold">0</span><span class="text-muted text-xs">need 1</span></li>
                    <li class="flex justify-between"><span>Batters</span><span id="count-batter" class="tabular-nums font-semibold">0</span><span class="text-muted text-xs">3-6</span></li>
                    <li class="flex justify-between"><span>All-rounders</span><span id="count-allrounder" class="tabular-nums font-semibold">0</span><span class="text-muted text-xs">1-4</span></li>
                    <li class="flex justify-between"><span>Bowlers</span><span id="count-bowler" class="tabular-nums font-semibold">0</span><span class="text-muted text-xs">3-6</span></li>
                </ul>
                <p class="text-xs text-muted mt-2">Max 7 players from one franchise.</p>
            </div>

            <div class="card p-4">
                <h2 class="font-bold text-sm mb-2">Captain &amp; Vice-Captain</h2>
                <label class="block text-xs font-semibold mb-1">Captain (2x points)</label>
                <select id="captain-select" class="w-full rounded-lg border border-line px-2 py-1.5 text-sm mb-3" <?= $readOnly ? 'disabled' : '' ?>>
                    <option value="">Select from your XI</option>
                </select>
                <label class="block text-xs font-semibold mb-1">Vice-Captain (1.5x points)</label>
                <select id="vc-select" class="w-full rounded-lg border border-line px-2 py-1.5 text-sm" <?= $readOnly ? 'disabled' : '' ?>>
                    <option value="">Select from your XI</option>
                </select>
            </div>

            <?php if (!$readOnly): ?>
                <button class="btn btn-saffron w-full" type="submit">Save team</button>
            <?php endif; ?>
        </div>
    </form>
</main>

<script>
(function () {
    const checkboxes = () => Array.from(document.querySelectorAll('.player-cb'));
    const captainSelect = document.getElementById('captain-select');
    const vcSelect = document.getElementById('vc-select');
    const captainInput = document.getElementById('captain_id_input');
    const vcInput = document.getElementById('vc_id_input');
    const wantedCaptain = captainInput.value;
    const wantedVc = vcInput.value;
    const isReadOnly = document.getElementById('team-form').dataset.readonly === '1';

    function recalc() {
        const checked = checkboxes().filter(cb => cb.checked);
        const creditUsed = checked.reduce((sum, cb) => sum + parseFloat(cb.dataset.credit), 0);
        document.getElementById('credit-used').textContent = creditUsed.toFixed(1);
        const pct = Math.min(100, (creditUsed / 100) * 100);
        const fill = document.getElementById('credit-fill');
        fill.style.width = pct + '%';
        fill.classList.toggle('over', creditUsed > 100);

        document.getElementById('squad-count').textContent = checked.length;
        ['wicketkeeper', 'batter', 'allrounder', 'bowler'].forEach(role => {
            const n = checked.filter(cb => cb.dataset.role === role).length;
            document.getElementById('count-' + role).textContent = n;
        });

        // Soft cap: once 11 are checked, disable the rest so a manager can't
        // even try to over-select client-side (server re-validates regardless).
        // Skipped entirely in read-only mode, where every box is meant to
        // stay permanently disabled.
        if (!isReadOnly) {
            const atCap = checked.length >= 11;
            checkboxes().forEach(cb => {
                if (!cb.checked) {
                    cb.disabled = atCap;
                }
            });
        }

        // Rebuild captain/VC dropdowns from currently checked players.
        const prevCaptain = captainSelect.value || wantedCaptain;
        const prevVc = vcSelect.value || wantedVc;
        captainSelect.innerHTML = '<option value="">Select from your XI</option>';
        vcSelect.innerHTML = '<option value="">Select from your XI</option>';
        checked.forEach(cb => {
            const opt1 = document.createElement('option');
            opt1.value = cb.dataset.id; opt1.textContent = cb.dataset.name;
            if (cb.dataset.id === prevCaptain) opt1.selected = true;
            captainSelect.appendChild(opt1);

            const opt2 = document.createElement('option');
            opt2.value = cb.dataset.id; opt2.textContent = cb.dataset.name;
            if (cb.dataset.id === prevVc) opt2.selected = true;
            vcSelect.appendChild(opt2);
        });
        captainInput.value = captainSelect.value || '';
        vcInput.value = vcSelect.value || '';
    }

    checkboxes().forEach(cb => cb.addEventListener('change', recalc));
    captainSelect.addEventListener('change', () => { captainInput.value = captainSelect.value; });
    vcSelect.addEventListener('change', () => { vcInput.value = vcSelect.value; });

    document.getElementById('search').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.player-row').forEach(row => {
            row.style.display = row.dataset.name.includes(q) ? '' : 'none';
        });
    });
    document.getElementById('role-filter').addEventListener('change', function () {
        const role = this.value;
        document.querySelectorAll('.player-row').forEach(row => {
            row.style.display = (!role || row.dataset.role === role) ? '' : 'none';
        });
    });

    recalc();
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
