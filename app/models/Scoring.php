<?php
/**
 * Pure, stateless fantasy-points rubric. Documented in full in README
 * "Points rubric". Takes one player's raw match_stats row and returns the
 * base fantasy points for that performance (before any captain/vice-captain
 * multiplier, which is applied separately at the team level).
 *
 * Kept framework/DB-free on purpose so it can be unit-exercised directly
 * (see scripts/test_scoring.php) and reused identically by the seed
 * generator and the live scoring-run controller -- one rubric, one place.
 */
class Scoring
{
    public static function pointsFor(array $s, string $role): float
    {
        $points = 0.0;

        if (!empty($s['played'])) {
            $points += 4; // flat bonus for being in the entered XI
        }

        // ---- Batting ----
        $runs = (int)($s['runs'] ?? 0);
        $points += $runs * 1;
        $points += (int)($s['fours'] ?? 0) * 1;
        $points += (int)($s['sixes'] ?? 0) * 2;

        if ($runs >= 100) {
            $points += 16;
        } elseif ($runs >= 50) {
            $points += 8;
        } elseif ($runs >= 30) {
            $points += 4;
        }

        if ($runs === 0 && !empty($s['is_out']) && in_array($role, ['batter', 'allrounder', 'wicketkeeper'], true)) {
            $points -= 2;
        }

        // ---- Bowling ----
        $wickets = (int)($s['wickets'] ?? 0);
        $points += $wickets * 25;

        $bowledLbw = min((int)($s['wickets_bowled_lbw'] ?? 0), $wickets);
        $points += $bowledLbw * 8;

        if ($wickets >= 5) {
            $points += 16;
        } elseif ($wickets >= 4) {
            $points += 8;
        } elseif ($wickets >= 3) {
            $points += 4;
        }

        $points += (int)($s['maidens'] ?? 0) * 4;

        // ---- Fielding ----
        $catches = (int)($s['catches'] ?? 0);
        $points += $catches * 8;
        if ($catches >= 3) {
            $points += 4;
        }
        $points += (int)($s['stumpings'] ?? 0) * 12;
        $points += (int)($s['run_outs_direct'] ?? 0) * 12;
        $points += (int)($s['run_outs_assisted'] ?? 0) * 6;

        return round($points, 1);
    }

    /**
     * Same rubric as pointsFor(), but returns the individual line items
     * instead of just the sum -- powers the manager-facing "explainable"
     * points breakdown so a total is never a black box. Zero-value lines
     * are omitted except "Played in the XI" / a no-stats placeholder.
     *
     * @return array<int, array{label:string, points:float}>
     */
    public static function breakdownFor(array $s, string $role): array
    {
        $lines = [];

        if (empty($s['played'])) {
            return [['label' => 'Did not play in the entered XI', 'points' => 0.0]];
        }
        $lines[] = ['label' => 'Played in the entered XI', 'points' => 4.0];

        $runs = (int)($s['runs'] ?? 0);
        if ($runs > 0) {
            $lines[] = ['label' => "Runs scored ($runs)", 'points' => (float)$runs];
        }
        $fours = (int)($s['fours'] ?? 0);
        if ($fours > 0) {
            $lines[] = ['label' => "Fours ($fours)", 'points' => (float)$fours];
        }
        $sixes = (int)($s['sixes'] ?? 0);
        if ($sixes > 0) {
            $lines[] = ['label' => "Sixes ($sixes)", 'points' => $sixes * 2.0];
        }

        if ($runs >= 100) {
            $lines[] = ['label' => 'Century bonus (100+ runs)', 'points' => 16.0];
        } elseif ($runs >= 50) {
            $lines[] = ['label' => 'Half-century bonus (50+ runs)', 'points' => 8.0];
        } elseif ($runs >= 30) {
            $lines[] = ['label' => '30-run milestone bonus', 'points' => 4.0];
        }

        if ($runs === 0 && !empty($s['is_out']) && in_array($role, ['batter', 'allrounder', 'wicketkeeper'], true)) {
            $lines[] = ['label' => 'Duck penalty', 'points' => -2.0];
        }

        $wickets = (int)($s['wickets'] ?? 0);
        if ($wickets > 0) {
            $lines[] = ['label' => "Wickets ($wickets)", 'points' => $wickets * 25.0];
        }
        $bowledLbw = min((int)($s['wickets_bowled_lbw'] ?? 0), $wickets);
        if ($bowledLbw > 0) {
            $lines[] = ['label' => "Bowled/LBW dismissal bonus ($bowledLbw)", 'points' => $bowledLbw * 8.0];
        }
        if ($wickets >= 5) {
            $lines[] = ['label' => '5-wicket haul bonus', 'points' => 16.0];
        } elseif ($wickets >= 4) {
            $lines[] = ['label' => '4-wicket haul bonus', 'points' => 8.0];
        } elseif ($wickets >= 3) {
            $lines[] = ['label' => '3-wicket haul bonus', 'points' => 4.0];
        }
        $maidens = (int)($s['maidens'] ?? 0);
        if ($maidens > 0) {
            $lines[] = ['label' => "Maiden overs ($maidens)", 'points' => $maidens * 4.0];
        }

        $catches = (int)($s['catches'] ?? 0);
        if ($catches > 0) {
            $lines[] = ['label' => "Catches ($catches)", 'points' => $catches * 8.0];
        }
        if ($catches >= 3) {
            $lines[] = ['label' => '3+ catch bonus', 'points' => 4.0];
        }
        $stumpings = (int)($s['stumpings'] ?? 0);
        if ($stumpings > 0) {
            $lines[] = ['label' => "Stumpings ($stumpings)", 'points' => $stumpings * 12.0];
        }
        $direct = (int)($s['run_outs_direct'] ?? 0);
        if ($direct > 0) {
            $lines[] = ['label' => "Direct-hit run-outs ($direct)", 'points' => $direct * 12.0];
        }
        $assisted = (int)($s['run_outs_assisted'] ?? 0);
        if ($assisted > 0) {
            $lines[] = ['label' => "Assisted run-outs ($assisted)", 'points' => $assisted * 6.0];
        }

        return $lines;
    }

    public static function multiplierFor(bool $isCaptain, bool $isViceCaptain): float
    {
        if ($isCaptain) {
            return 2.0;
        }
        if ($isViceCaptain) {
            return 1.5;
        }
        return 1.0;
    }
}
