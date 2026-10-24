<?php
// Standalone unit-style check for Scoring + TeamValidator, no DB needed.
require __DIR__ . '/../core/helpers.php';
require __DIR__ . '/../app/models/Scoring.php';
require __DIR__ . '/../app/models/TeamValidator.php';

function assertEqual($expected, $actual, string $label): void
{
    if ($expected == $actual) {
        echo "PASS  $label => $actual\n";
    } else {
        echo "FAIL  $label => expected $expected, got $actual\n";
        exit(1);
    }
}

// Century + 2 fours + 3 sixes + played
assertEqual(4 + 100 + 8 + 6 + 16, Scoring::pointsFor([
    'played' => true, 'runs' => 100, 'fours' => 8, 'sixes' => 3,
], 'batter'), 'Century knock (batter)');

// Duck, out, batter -> -2 duck penalty + 4 played
assertEqual(4 - 2, Scoring::pointsFor(['played' => true, 'runs' => 0, 'is_out' => true], 'batter'), 'Duck (out) batter');

// Duck but NOT out (not dismissed) -> no penalty
assertEqual(4, Scoring::pointsFor(['played' => true, 'runs' => 0, 'is_out' => false], 'batter'), 'Not-out duck, no penalty');

// Duck for a specialist bowler -> no penalty (role restriction)
assertEqual(4, Scoring::pointsFor(['played' => true, 'runs' => 0, 'is_out' => true], 'bowler'), 'Duck (out) bowler exempt');

// 5-wicket haul, 2 bowled/lbw, 1 maiden
assertEqual(4 + (5 * 25) + (2 * 8) + 16 + 4, Scoring::pointsFor([
    'played' => true, 'wickets' => 5, 'wickets_bowled_lbw' => 2, 'maidens' => 1,
], 'bowler'), '5-wicket haul');

// 3 catches -> 8*3 + 4 bonus + played
assertEqual(4 + 24 + 4, Scoring::pointsFor(['played' => true, 'catches' => 3], 'allrounder'), '3-catch bonus');

// Did not play at all -> zero
assertEqual(0, Scoring::pointsFor(['played' => false], 'batter'), 'Did not play');

// breakdownFor() line items must sum to exactly what pointsFor() returns,
// for every scenario above -- otherwise the "explainable" breakdown would
// lie to the manager about their own total.
$scenarios = [
    ['played' => true, 'runs' => 100, 'fours' => 8, 'sixes' => 3],
    ['played' => true, 'runs' => 0, 'is_out' => true],
    ['played' => true, 'wickets' => 5, 'wickets_bowled_lbw' => 2, 'maidens' => 1],
    ['played' => true, 'catches' => 3],
    ['played' => false],
];
foreach ($scenarios as $i => $s) {
    $total = Scoring::pointsFor($s, 'allrounder');
    $breakdownSum = array_sum(array_column(Scoring::breakdownFor($s, 'allrounder'), 'points'));
    assertEqual($total, $breakdownSum, "Breakdown sums to total (scenario $i)");
}

// ---- TeamValidator ----
$eligible = [];
for ($i = 1; $i <= 22; $i++) {
    $eligible[$i] = true;
}

// Build a deliberately valid 11: 1 wk, 4 bat, 2 ar, 4 bowler, 9.0 credits each = 99 <= 100, franchise split 6/5
$roles = array_merge(
    ['wicketkeeper'],
    array_fill(0, 4, 'batter'),
    array_fill(0, 2, 'allrounder'),
    array_fill(0, 4, 'bowler')
);
$selected = [];
foreach ($roles as $i => $role) {
    $id = $i + 1;
    $selected[] = ['id' => $id, 'role' => $role, 'credit_value' => 9.0, 'franchise_id' => $id <= 6 ? 1 : 2];
}
$errors = TeamValidator::validate($selected, $eligible, 1, 2);
if ($errors === []) {
    echo "PASS  Valid team accepted\n";
} else {
    echo "FAIL  Valid team rejected: " . implode(' | ', $errors) . "\n";
    exit(1);
}

// Over-budget: bump every credit to 10.5 -> 11 * 10.5 = 115.5 > 100
$overBudget = array_map(fn($p) => [...$p, 'credit_value' => 10.5], $selected);
$errors = TeamValidator::validate($overBudget, $eligible, 1, 2);
assertEqual(true, str_contains(implode('|', $errors), 'budget'), 'Over-budget team rejected');

// Franchise cap: put 8 players in franchise 1
$overFranchise = $selected;
foreach ($overFranchise as &$p) {
    $p['franchise_id'] = 1;
}
unset($p);
$errors = TeamValidator::validate($overFranchise, $eligible, 1, 2);
assertEqual(true, str_contains(implode('|', $errors), 'single franchise'), 'Franchise cap rejected');

// Bad quota: 2 wicketkeepers
$badQuota = $selected;
$badQuota[1]['role'] = 'wicketkeeper';
$errors = TeamValidator::validate($badQuota, $eligible, 1, 2);
assertEqual(true, str_contains(implode('|', $errors), 'Wicketkeeper'), 'Bad WK quota rejected');

// Same captain and VC
$errors = TeamValidator::validate($selected, $eligible, 1, 1);
assertEqual(true, str_contains(implode('|', $errors), 'two different players'), 'Same captain/VC rejected');

// Fixture feasibility: thin pool missing bowlers
$thinPool = [
    ['role' => 'wicketkeeper'], ['role' => 'batter'], ['role' => 'batter'], ['role' => 'batter'],
    ['role' => 'allrounder'], ['role' => 'bowler'], ['role' => 'bowler'],
];
assertEqual(false, TeamValidator::fixtureIsFeasible($thinPool), 'Thin fixture (2 bowlers) infeasible');

$goodPool = array_merge($thinPool, [
    ['role' => 'bowler'], ['role' => 'batter'], ['role' => 'batter'], ['role' => 'batter'], ['role' => 'bowler'],
]);
assertEqual(true, TeamValidator::fixtureIsFeasible($goodPool), 'Fixture with enough of every role feasible');

// Franchise-cap feasibility: a 3-player franchise can never reach 11 when
// paired with anyone, since the partner is capped at 7 (7+3=10 < 11).
$tinyFranchisePool = [];
for ($i = 0; $i < 3; $i++) {
    $tinyFranchisePool[] = ['role' => ['bowler', 'allrounder', 'batter'][$i], 'franchise_id' => 7];
}
$roleCycle = ['wicketkeeper', 'batter', 'batter', 'batter', 'allrounder', 'bowler', 'bowler', 'bowler', 'bowler', 'bowler'];
foreach ($roleCycle as $role) {
    $tinyFranchisePool[] = ['role' => $role, 'franchise_id' => 1];
}
assertEqual(false, TeamValidator::fixtureIsFeasible($tinyFranchisePool), '3-player franchise vs anyone infeasible (cap math)');

echo "\nAll scoring/validator checks passed.\n";
