<?php
/**
 * One-off dev script: populates realistic contest_entries / match_stats for
 * the demo contests seeded in schema.sql, using the SAME model classes the
 * live app uses (TeamValidator, ContestEntry, MatchStat, ScoringRun) -- so
 * this run is itself a real integration exercise of team-building validation
 * and the scoring pipeline, not just hand-typed SQL.
 *
 * Run once against the freshly-imported schema, then `mysqldump` the result
 * back into database/schema.sql so the shipped file is the exact state this
 * script produced and verified.
 *
 * Usage: php scripts/seed_demo_entries.php
 */
declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');

require __DIR__ . '/../core/helpers.php';
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../app/models/Player.php';
require __DIR__ . '/../app/models/Contest.php';
require __DIR__ . '/../app/models/ContestEntry.php';
require __DIR__ . '/../app/models/MatchStat.php';
require __DIR__ . '/../app/models/Scoring.php';
require __DIR__ . '/../app/models/ScoringRun.php';
require __DIR__ . '/../app/models/TeamValidator.php';

mt_srand(42); // reproducible seed data

function buildValidTeam(array $pool, int $franchiseAId, int $franchiseBId): ?array
{
    $eligibleIds = [];
    foreach ($pool as $p) {
        $eligibleIds[(int)$p['id']] = true;
    }

    $byRole = ['wicketkeeper' => [], 'batter' => [], 'allrounder' => [], 'bowler' => []];
    foreach ($pool as $p) {
        $byRole[$p['role']][] = $p;
    }

    for ($attempt = 0; $attempt < 300; $attempt++) {
        $batCount = random_int(3, min(6, count($byRole['batter'])));
        $arCount = random_int(1, min(4, count($byRole['allrounder'])));
        $bowlCount = 11 - 1 - $batCount - $arCount;
        if ($bowlCount < 3 || $bowlCount > min(6, count($byRole['bowler']))) {
            continue;
        }
        if (count($byRole['wicketkeeper']) < 1) {
            return null;
        }

        $selected = [];
        $selected[] = $byRole['wicketkeeper'][array_rand($byRole['wicketkeeper'])];
        $selected = array_merge($selected, arraySampleUnique($byRole['batter'], $batCount));
        $selected = array_merge($selected, arraySampleUnique($byRole['allrounder'], $arCount));
        $selected = array_merge($selected, arraySampleUnique($byRole['bowler'], $bowlCount));

        if (count($selected) !== 11) {
            continue;
        }

        $captain = $selected[array_rand($selected)];
        do {
            $viceCaptain = $selected[array_rand($selected)];
        } while ($viceCaptain['id'] === $captain['id']);

        $errors = TeamValidator::validate($selected, $eligibleIds, (int)$captain['id'], (int)$viceCaptain['id']);
        if ($errors === []) {
            return ['players' => $selected, 'captain' => $captain, 'vice_captain' => $viceCaptain];
        }
    }
    return null;
}

function arraySampleUnique(array $items, int $n): array
{
    if ($n <= 0) {
        return [];
    }
    $keys = array_rand($items, min($n, count($items)));
    if (!is_array($keys)) {
        $keys = [$keys];
    }
    return array_map(fn($k) => $items[$k], $keys);
}

function randomStatsFor(array $player): array
{
    $role = $player['role'];
    $played = random_int(1, 100) <= 92; // occasional player rested/not in XI
    $s = ['played' => $played ? 1 : 0];
    if (!$played) {
        return $s + array_fill_keys(
            ['runs','fours','sixes','is_out','wickets','wickets_bowled_lbw','maidens','catches','stumpings','run_outs_direct','run_outs_assisted'],
            0
        );
    }

    $runs = 0; $fours = 0; $sixes = 0; $isOut = 1;
    if (in_array($role, ['batter', 'wicketkeeper', 'allrounder'], true)) {
        $runs = (int)round(max(0, min(120, random_int(-10, 65) + random_int(0, 45))));
        $fours = min((int)floor($runs / 8), random_int(0, 6));
        $sixes = min((int)floor($runs / 20), random_int(0, 4));
        $isOut = random_int(1, 100) <= 78 ? 1 : 0;
    } else {
        // specialist bowler batting briefly
        $runs = random_int(0, 15);
        $isOut = random_int(1, 100) <= 60 ? 1 : 0;
    }

    $wickets = 0; $bowledLbw = 0; $maidens = 0;
    if (in_array($role, ['bowler', 'allrounder'], true)) {
        $wickets = max(0, random_int(-2, 3));
        $bowledLbw = $wickets > 0 ? random_int(0, $wickets) : 0;
        $maidens = random_int(0, 100) <= 20 ? 1 : 0;
    }

    $catches = random_int(1, 100) <= 25 ? random_int(1, 2) : 0;
    $stumpings = ($role === 'wicketkeeper' && random_int(1, 100) <= 15) ? 1 : 0;
    $runOutDirect = random_int(1, 100) <= 6 ? 1 : 0;
    $runOutAssisted = random_int(1, 100) <= 6 ? 1 : 0;

    return [
        'played' => 1, 'runs' => $runs, 'fours' => $fours, 'sixes' => $sixes, 'is_out' => $isOut,
        'wickets' => $wickets, 'wickets_bowled_lbw' => $bowledLbw, 'maidens' => $maidens,
        'catches' => $catches, 'stumpings' => $stumpings,
        'run_outs_direct' => $runOutDirect, 'run_outs_assisted' => $runOutAssisted,
    ];
}

// contest_id => [manager ids to join, whether to enter stats + score]
$plan = [
    1 => ['managers' => [2, 3, 4, 5], 'score' => true],
    2 => ['managers' => [3, 4, 6, 7], 'score' => true],
    3 => ['managers' => [2, 5], 'score' => false], // locked, pending stats entry
    4 => ['managers' => [4, 8], 'score' => false],  // open
    5 => ['managers' => [2], 'score' => false],     // open
];

foreach ($plan as $contestId => $cfg) {
    $contest = Contest::find($contestId);
    if (!$contest) {
        fwrite(STDERR, "Contest $contestId not found\n");
        continue;
    }
    $pool = Player::byFranchises((int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
    echo "Contest $contestId ({$contest['franchise_a_name']} vs {$contest['franchise_b_name']}), pool size " . count($pool) . "\n";

    foreach ($cfg['managers'] as $managerId) {
        $team = buildValidTeam($pool, (int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
        if ($team === null) {
            fwrite(STDERR, "  Could not build a valid team for manager $managerId in contest $contestId\n");
            continue;
        }
        $playerIds = array_map(fn($p) => (int)$p['id'], $team['players']);
        $creditSum = array_sum(array_map(fn($p) => (float)$p['credit_value'], $team['players']));
        ContestEntry::save($contestId, $managerId, $playerIds, (int)$team['captain']['id'], (int)$team['vice_captain']['id'], round($creditSum, 1), null);
        echo "  Manager $managerId joined with " . count($playerIds) . " players, {$creditSum} credits, captain {$team['captain']['name']}\n";
    }

    if ($cfg['score']) {
        foreach ($pool as $p) {
            MatchStat::upsert($contestId, (int)$p['id'], randomStatsFor($p));
        }
        ScoringRun::run($contestId);
        echo "  Scoring run complete for contest $contestId\n";
    }
}

echo "\nDone.\n";
