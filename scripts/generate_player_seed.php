<?php
/**
 * One-off dev script: reads the reference IPL dataset and emits SQL INSERT
 * statements for `franchises` and `players`, with a computed credit_value
 * per player (documented formula, see README "Credit value formula").
 *
 * Usage: php generate_player_seed.php > seed_players.sql
 * Not part of the running app -- output is pasted into database/schema.sql.
 */

$sourcePath = 'M:/cross-bat/cricket-box2box/server/data/squad-builder.json';
$json = json_decode(file_get_contents($sourcePath), true);
$players = $json['players'];

// The source data includes two now-defunct IPL franchises (Gujarat Lions,
// Rising Pune Supergiant, both 2016-17 only). FEATURES.md calls for the 10
// CURRENT IPL franchises, so the handful of players tagged with a defunct
// franchise are remapped to a current franchise they plausibly represent
// (both actually played for these franchises in other IPL seasons).
$franchiseRemap = [
    'Gujarat Lions'          => 'Delhi Capitals',       // IK Pathan played for Delhi Daredevils 2013-15
    'Rising Pune Supergiant' => 'Chennai Super Kings',  // JA (Albie) Morkel played for CSK 2008-13
];

$currentFranchises = [
    'Mumbai Indians', 'Chennai Super Kings', 'Royal Challengers Bengaluru',
    'Kolkata Knight Riders', 'Delhi Capitals', 'Punjab Kings',
    'Rajasthan Royals', 'Sunrisers Hyderabad', 'Gujarat Titans', 'Lucknow Super Giants',
];

$shortCodes = [
    'Mumbai Indians' => 'MI', 'Chennai Super Kings' => 'CSK', 'Royal Challengers Bengaluru' => 'RCB',
    'Kolkata Knight Riders' => 'KKR', 'Delhi Capitals' => 'DC', 'Punjab Kings' => 'PBKS',
    'Rajasthan Royals' => 'RR', 'Sunrisers Hyderabad' => 'SRH', 'Gujarat Titans' => 'GT',
    'Lucknow Super Giants' => 'LSG',
];

/**
 * Credit value formula: each player gets a role-specific "form score"
 * (runs/match for batters & wicketkeepers, wickets/match for bowlers, a
 * blended runs+wickets/match score for all-rounders). Players are then
 * ranked by form score WITHIN their own role group and their percentile
 * rank is linearly mapped onto the 6.5-10.5 credit band, rounded to the
 * nearest 0.5 -- so credits reflect standing relative to peers in the same
 * role, not an arbitrary absolute cutoff (this also guarantees the full
 * 6.5-10.5 range gets used regardless of how strong the overall player
 * pool is). Ties share the same percentile. Documented in README.
 */
function formScore(array $p): float
{
    $matches = max(1, (int)$p['matches']);
    $rpm = $p['runs'] / $matches;
    $wpm = $p['wickets'] / $matches;

    return match ($p['role']) {
        'batter', 'wicketkeeper' => $rpm,
        'bowler'                 => $wpm,
        'allrounder'             => $rpm + ($wpm * 25),
        default                  => 0.0,
    };
}

function assignCredits(array $players): array
{
    $byRole = [];
    foreach ($players as $i => $p) {
        $byRole[$p['role']][] = $i;
    }

    $credits = [];
    foreach ($byRole as $role => $indexes) {
        $scores = array_map(fn($i) => formScore($players[$i]), $indexes);
        $sorted = $scores;
        sort($sorted);
        $n = count($sorted);

        foreach ($indexes as $idx) {
            $score = formScore($players[$idx]);
            // Percentile = fraction of the group scoring <= this player.
            $rank = 0;
            foreach ($sorted as $s) {
                if ($s <= $score) {
                    $rank++;
                }
            }
            $percentile = $n > 1 ? ($rank - 1) / ($n - 1) : 1.0;
            $credit = 6.5 + $percentile * 4.0;
            $credit = round($credit * 2) / 2;
            $credits[$idx] = max(6.5, min(10.5, $credit));
        }
    }

    return $credits;
}

$out = [];
$out[] = "-- Franchises (10 current IPL franchises)";
foreach ($currentFranchises as $i => $name) {
    $code = $shortCodes[$name];
    $out[] = sprintf(
        "INSERT INTO franchises (id, name, short_code, status) VALUES (%d, %s, %s, 'active');",
        $i + 1,
        pdoQuote($name),
        pdoQuote($code)
    );
}

$franchiseIdByName = array_flip(array_map(fn($i) => $i, array_keys($currentFranchises)));
$nameToId = [];
foreach ($currentFranchises as $i => $name) {
    $nameToId[$name] = $i + 1;
}

$out[] = "";
$out[] = "-- Players (88 real IPL players, seeded from reference dataset; credit_value computed, see README)";
$id = 1;
$creditsByIndex = assignCredits($players);
$credits = [];
foreach ($players as $pi => $p) {
    $franchiseName = $franchiseRemap[$p['team']] ?? $p['team'];
    if (!isset($nameToId[$franchiseName])) {
        fwrite(STDERR, "Unknown franchise: {$p['team']} for {$p['name']}\n");
        continue;
    }
    $franchiseId = $nameToId[$franchiseName];
    $credit = $creditsByIndex[$pi];
    $credits[] = $credit;

    $out[] = sprintf(
        "INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (%d, %s, %d, %s, %s, %d, %d, %d, %d, 'active');",
        $id,
        pdoQuote($p['name']),
        $franchiseId,
        pdoQuote($p['role']),
        number_format($credit, 1, '.', ''),
        $p['runs'],
        $p['wickets'],
        $p['matches'],
        $p['trophies']
    );
    $id++;
}

function pdoQuote(string $s): string
{
    return "'" . str_replace("'", "''", $s) . "'";
}

fwrite(STDERR, sprintf(
    "Player count: %d | credit min=%.1f max=%.1f avg=%.2f\n",
    count($credits), min($credits), max($credits), array_sum($credits) / count($credits)
));

echo implode("\n", $out) . "\n";
