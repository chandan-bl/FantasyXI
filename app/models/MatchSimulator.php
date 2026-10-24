<?php

/**
 * Generates a plausible set of T20 match_stats rows for a contest's player
 * pool, so admins don't have to hand-type every number for a demo/viva.
 * Everything is derived from two bounded per-innings totals (team runs, team
 * wickets) so individual figures never run away to unrealistic extremes.
 */
class MatchSimulator
{
    private const WICKET_WEIGHTS = [0 => 1, 1 => 2, 2 => 4, 3 => 6, 4 => 8, 5 => 10, 6 => 10, 7 => 9, 8 => 7, 9 => 5, 10 => 3];

    private const BATTING_ROLE_WEIGHT = [
        'batter' => 3.0, 'wicketkeeper' => 2.6, 'allrounder' => 2.0, 'bowler' => 0.5,
    ];

    private const BOWLING_ROLE_WEIGHT = [
        'bowler' => 3.0, 'allrounder' => 2.0, 'wicketkeeper' => 0.15, 'batter' => 0.3,
    ];

    /** @return array<int, array> player_id => match_stats field array */
    public static function simulate(array $pool, int $franchiseAId, int $franchiseBId): array
    {
        $byFranchise = [];
        foreach ($pool as $p) {
            $byFranchise[(int)$p['franchise_id']][] = $p;
        }

        $rows = [];
        foreach ($pool as $p) {
            $rows[(int)$p['id']] = self::blankRow();
        }

        $squadA = $byFranchise[$franchiseAId] ?? [];
        $squadB = $byFranchise[$franchiseBId] ?? [];
        $xiA = self::pickPlayingXI($squadA);
        $xiB = self::pickPlayingXI($squadB);

        if ($xiA) {
            self::simulateInnings($xiA, $xiB, $rows);
        }
        if ($xiB) {
            self::simulateInnings($xiB, $xiA, $rows);
        }

        return $rows;
    }

    private static function blankRow(): array
    {
        return [
            'played' => 0, 'runs' => 0, 'fours' => 0, 'sixes' => 0, 'is_out' => 0,
            'wickets' => 0, 'wickets_bowled_lbw' => 0, 'maidens' => 0,
            'catches' => 0, 'stumpings' => 0, 'run_outs_direct' => 0, 'run_outs_assisted' => 0,
        ];
    }

    /** Up to 11 players; guarantees a wicketkeeper is included when the squad has one. */
    private static function pickPlayingXI(array $squad): array
    {
        if (count($squad) <= 11) {
            return $squad;
        }
        shuffle($squad);
        $xi = array_slice($squad, 0, 11);
        if (!self::findKeeper($xi)) {
            $keeperIdx = null;
            foreach ($squad as $i => $p) {
                if ($p['role'] === 'wicketkeeper') {
                    $keeperIdx = $i;
                    break;
                }
            }
            if ($keeperIdx !== null) {
                $xi[array_rand($xi)] = $squad[$keeperIdx];
            }
        }
        return $xi;
    }

    private static function findKeeper(array $xi): ?array
    {
        foreach ($xi as $p) {
            if ($p['role'] === 'wicketkeeper') {
                return $p;
            }
        }
        return null;
    }

    private static function simulateInnings(array $battingXI, array $bowlingXI, array &$rows): void
    {
        foreach ($battingXI as $p) {
            $rows[(int)$p['id']]['played'] = 1;
        }
        foreach ($bowlingXI as $p) {
            $rows[(int)$p['id']]['played'] = 1;
        }

        $n = count($battingXI);
        if ($n === 0) {
            return;
        }

        $teamRuns = mt_rand(110, 205);
        $teamWickets = self::distributeBatting($battingXI, $teamRuns, $rows);
        self::distributeBowlingAndFielding($bowlingXI, $teamWickets, $rows);
    }

    /**
     * Distributes the team's runs and dismissals among whoever actually got a
     * chance to bat. Tailend bowlers usually don't -- a T20 innings ends
     * (bowled out, or overs run out) before the last couple of specialist
     * bowlers ever come to the crease, so each one independently rolls a
     * chance of a "did not bat" line: 0 runs, not out, untouched blank row.
     * Returns the team's wickets-lost count (needed by the bowling side).
     */
    private static function distributeBatting(array $xi, int $teamRuns, array &$rows): int
    {
        $topOrder = array_values(array_filter($xi, fn($p) => $p['role'] !== 'bowler'));
        $tailBowlers = array_values(array_filter($xi, fn($p) => $p['role'] === 'bowler'));

        if (empty($topOrder)) {
            // No recognized top order at all in this squad -- everyone bats as usual.
            $battedPool = $xi;
        } else {
            $battedPool = $topOrder;
            foreach ($tailBowlers as $b) {
                if (mt_rand(1, 100) <= 40) {
                    $battedPool[] = $b;
                }
            }
            // Still need at least a crease pair to give the runs to.
            while (count($battedPool) < min(2, count($xi))) {
                $pulledIn = false;
                foreach ($tailBowlers as $b) {
                    if (!in_array($b, $battedPool, true)) {
                        $battedPool[] = $b;
                        $pulledIn = true;
                        break;
                    }
                }
                if (!$pulledIn) {
                    break;
                }
            }
        }

        $m = count($battedPool);
        $maxWickets = min(10, $m - 1 >= 0 ? $m - 1 : 0);
        // At most 2 of the players who batted finish not out -- everyone else must be dismissed.
        $minWickets = max(0, $m - 2);
        $teamWickets = self::weightedPick(self::WICKET_WEIGHTS, $minWickets, $maxWickets);

        $weights = [];
        foreach ($battedPool as $p) {
            $base = self::BATTING_ROLE_WEIGHT[$p['role']] ?? 1.0;
            $weights[] = $base * (mt_rand(40, 160) / 100);
        }
        $runsByIndex = self::distributeProportionally($teamRuns, $weights);

        $outIndices = $teamWickets > 0
            ? array_flip((array)array_rand(range(0, $m - 1), $teamWickets))
            : [];

        foreach ($battedPool as $i => $p) {
            $runs = $runsByIndex[$i];
            [$fours, $sixes] = self::deriveBoundaries($runs);
            $row =& $rows[(int)$p['id']];
            $row['runs'] = $runs;
            $row['fours'] = $fours;
            $row['sixes'] = $sixes;
            $row['is_out'] = isset($outIndices[$i]) ? 1 : 0;
            unset($row);
        }

        return $teamWickets;
    }

    private static function deriveBoundaries(int $runs): array
    {
        if ($runs <= 0) {
            return [0, 0];
        }
        $boundaryRuns = (int)floor($runs * (mt_rand(55, 78) / 100));
        $sixes = (int)round(($boundaryRuns * (mt_rand(12, 35) / 100)) / 6);
        $sixes = max(0, $sixes);
        $remaining = max(0, $boundaryRuns - $sixes * 6);
        $fours = (int)floor($remaining / 4);
        while ($fours * 4 + $sixes * 6 > $runs) {
            $fours > 0 ? $fours-- : $sixes--;
            if ($fours <= 0 && $sixes <= 0) {
                break;
            }
        }
        return [max(0, $fours), max(0, $sixes)];
    }

    private static function distributeBowlingAndFielding(array $xi, int $teamWicketsLost, array &$rows): void
    {
        if ($teamWicketsLost === 0 || empty($xi)) {
            return;
        }

        $runOuts = 0;
        for ($i = 0; $i < $teamWicketsLost; $i++) {
            if (mt_rand(1, 100) <= 6) {
                $runOuts++;
            }
        }
        $runOuts = min($runOuts, 2, $teamWicketsLost);
        $bowlerWicketsTotal = $teamWicketsLost - $runOuts;

        $candidates = array_values(array_filter($xi, fn($p) => in_array($p['role'], ['bowler', 'allrounder'], true)));
        if (count($candidates) < 3) {
            $candidates = $xi;
        }

        $weights = [];
        foreach ($candidates as $p) {
            $weights[] = (self::BOWLING_ROLE_WEIGHT[$p['role']] ?? 0.3) * (mt_rand(50, 150) / 100);
        }
        $wicketsByIndex = self::distributeCapped($bowlerWicketsTotal, $weights, 5);

        $caughtOrStumpedPool = 0;
        foreach ($candidates as $i => $p) {
            $w = $wicketsByIndex[$i];
            if ($w <= 0) {
                if (mt_rand(1, 100) <= 8) {
                    $rows[(int)$p['id']]['maidens'] = 1;
                }
                continue;
            }
            $bowledLbw = (int)round($w * (mt_rand(30, 60) / 100));
            $bowledLbw = min($bowledLbw, $w);
            $caughtOrStumpedPool += ($w - $bowledLbw);

            $row =& $rows[(int)$p['id']];
            $row['wickets'] = $w;
            $row['wickets_bowled_lbw'] = $bowledLbw;
            if (mt_rand(1, 100) <= 8) {
                $row['maidens'] = 1;
            }
            unset($row);
        }

        $keeper = self::findKeeper($xi);
        $stumpings = 0;
        if ($keeper && $caughtOrStumpedPool > 0 && mt_rand(1, 100) <= 25) {
            $stumpings = 1;
            $rows[(int)$keeper['id']]['stumpings'] += 1;
        }

        $catchPool = $caughtOrStumpedPool - $stumpings;
        self::sprinkleAmong($xi, $catchPool, $rows, 'catches');

        if ($runOuts > 0) {
            for ($i = 0; $i < $runOuts; $i++) {
                $fielder = $xi[array_rand($xi)];
                $field = mt_rand(1, 100) <= 65 ? 'run_outs_direct' : 'run_outs_assisted';
                $rows[(int)$fielder['id']][$field] += 1;
            }
        }
    }

    /** Hands out $count 1-catch credits (occasionally 2 to one fielder) across the XI. */
    private static function sprinkleAmong(array $xi, int $count, array &$rows, string $field): void
    {
        for ($i = 0; $i < $count; $i++) {
            $fielder = $xi[array_rand($xi)];
            $rows[(int)$fielder['id']][$field] += 1;
        }
    }

    private static function weightedPick(array $weights, int $minKey, int $maxKey): int
    {
        $pool = array_filter($weights, fn($w, $k) => $k >= $minKey && $k <= $maxKey, ARRAY_FILTER_USE_BOTH);
        if (!$pool) {
            return max(0, $minKey);
        }
        $total = array_sum($pool);
        $r = mt_rand(1, (int)($total * 100)) / 100;
        $acc = 0;
        foreach ($pool as $k => $w) {
            $acc += $w;
            if ($r <= $acc) {
                return $k;
            }
        }
        return array_key_last($pool);
    }

    /** Splits $total across weighted slots using largest-remainder rounding, so the sum matches exactly. */
    private static function distributeProportionally(int $total, array $weights): array
    {
        $sumW = array_sum($weights) ?: 1;
        $raw = array_map(fn($w) => $total * $w / $sumW, $weights);
        $floors = array_map('intval', $raw);
        $remainder = $total - array_sum($floors);
        $remainders = [];
        foreach ($raw as $i => $v) {
            $remainders[$i] = $v - floor($v);
        }
        arsort($remainders);
        foreach (array_keys($remainders) as $i) {
            if ($remainder <= 0) {
                break;
            }
            $floors[$i]++;
            $remainder--;
        }
        return $floors;
    }

    /** Like distributeProportionally, but no slot may exceed $cap; overflow is reassigned to remaining capacity. */
    private static function distributeCapped(int $total, array $weights, int $cap): array
    {
        $n = count($weights);
        if ($n === 0) {
            return [];
        }
        $result = array_fill(0, $n, 0);
        $remaining = $total;
        $available = array_fill(0, $n, $cap);

        $guard = 0;
        while ($remaining > 0 && $guard < 50) {
            $guard++;
            $activeWeights = [];
            foreach ($weights as $i => $w) {
                $activeWeights[$i] = $available[$i] > 0 ? $w : 0;
            }
            if (array_sum($activeWeights) <= 0) {
                break;
            }
            $chunk = self::distributeProportionally($remaining, $activeWeights);
            $progress = false;
            foreach ($chunk as $i => $add) {
                $add = min($add, $available[$i]);
                if ($add > 0) {
                    $result[$i] += $add;
                    $available[$i] -= $add;
                    $remaining -= $add;
                    $progress = true;
                }
            }
            if (!$progress) {
                break;
            }
        }
        return $result;
    }
}
