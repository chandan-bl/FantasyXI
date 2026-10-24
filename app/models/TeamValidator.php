<?php
/**
 * Pure validation logic for a Dream11-style 11-player team, framework/DB-free
 * so it can be exercised the same way from the live TeamController and from
 * the seed generator. Every rule here MUST be re-checked server-side at
 * save time -- this is not a client-side-only convenience.
 */
class TeamValidator
{
    public const BUDGET = 100.0;
    public const SQUAD_SIZE = 11;
    public const MAX_PER_FRANCHISE = 7;
    public const QUOTA = [
        'wicketkeeper' => [1, 1],
        'batter'       => [3, 6],
        'allrounder'   => [1, 4],
        'bowler'       => [3, 6],
    ];

    /**
     * @param array $selectedPlayers list of ['id'=>, 'role'=>, 'credit_value'=>, 'franchise_id'=>]
     * @param array $eligibleIds set of player ids allowed for this contest (both squads), id => true
     * @return string[] list of human-readable error messages, empty = valid
     */
    public static function validate(array $selectedPlayers, array $eligibleIds, ?int $captainId, ?int $viceCaptainId): array
    {
        $errors = [];

        $count = count($selectedPlayers);
        if ($count !== self::SQUAD_SIZE) {
            $errors[] = "A team must have exactly " . self::SQUAD_SIZE . " players (you selected $count).";
        }

        $seen = [];
        $roleCounts = ['wicketkeeper' => 0, 'batter' => 0, 'allrounder' => 0, 'bowler' => 0];
        $franchiseCounts = [];
        $creditSum = 0.0;

        foreach ($selectedPlayers as $p) {
            $id = (int)$p['id'];

            if (isset($seen[$id])) {
                $errors[] = "Player #{$id} was selected more than once.";
                continue;
            }
            $seen[$id] = true;

            if (!isset($eligibleIds[$id])) {
                $errors[] = "Player #{$id} is not part of this contest's eligible pool.";
                continue;
            }

            $role = $p['role'];
            if (isset($roleCounts[$role])) {
                $roleCounts[$role]++;
            }

            $franchiseId = (int)$p['franchise_id'];
            $franchiseCounts[$franchiseId] = ($franchiseCounts[$franchiseId] ?? 0) + 1;

            $creditSum += (float)$p['credit_value'];
        }

        foreach (self::QUOTA as $role => [$min, $max]) {
            $n = $roleCounts[$role];
            if ($n < $min || $n > $max) {
                $label = role_label($role);
                $errors[] = "$label count must be between $min and $max (you have $n).";
            }
        }

        foreach ($franchiseCounts as $fid => $n) {
            if ($n > self::MAX_PER_FRANCHISE) {
                $errors[] = "You can pick at most " . self::MAX_PER_FRANCHISE . " players from a single franchise (franchise #{$fid} has $n).";
            }
        }

        // Round to 1dp before comparing to avoid float noise flagging exactly-100 teams.
        if (round($creditSum, 1) > self::BUDGET) {
            $errors[] = "Team credit total is " . number_format($creditSum, 1) . ", which exceeds the " . number_format(self::BUDGET, 1) . "-credit budget.";
        }

        if ($captainId === null || $viceCaptainId === null) {
            $errors[] = "Pick both a captain and a vice-captain.";
        } elseif ($captainId === $viceCaptainId) {
            $errors[] = "Captain and vice-captain must be two different players.";
        } else {
            if (!isset($seen[$captainId])) {
                $errors[] = "Captain must be one of the 11 selected players.";
            }
            if (!isset($seen[$viceCaptainId])) {
                $errors[] = "Vice-captain must be one of the 11 selected players.";
            }
        }

        return $errors;
    }

    /**
     * Server-side feasibility check for a fixture: can the combined eligible
     * pool of the two chosen franchises even satisfy the minimum role quota
     * AND the max-7-per-franchise cap at the same time? Two failure modes
     * found during testing on the real curated dataset:
     *  - a franchise thin in one role (e.g. no specialist wicketkeeper)
     *  - a franchise with very few total players: if one side has fewer
     *    than (SQUAD_SIZE - MAX_PER_FRANCHISE) players, the other side's
     *    7-player cap makes it impossible to ever reach 11 total (e.g. a
     *    3-player roster can supply at most 3, so the partner would need to
     *    supply 8 -- one more than the cap allows).
     * Either failure makes the fixture unplayable for any manager, so a
     * contest pairing like that is rejected server-side at creation time.
     *
     * @param array $pool list of ['role'=>..., 'franchise_id'=>...] for every active player across both franchises
     */
    public static function fixtureIsFeasible(array $pool): bool
    {
        if (count($pool) < self::SQUAD_SIZE) {
            return false;
        }

        $roleCounts = ['wicketkeeper' => 0, 'batter' => 0, 'allrounder' => 0, 'bowler' => 0];
        $franchiseCounts = [];
        foreach ($pool as $p) {
            if (isset($roleCounts[$p['role']])) {
                $roleCounts[$p['role']]++;
            }
            if (isset($p['franchise_id'])) {
                $fid = (int)$p['franchise_id'];
                $franchiseCounts[$fid] = ($franchiseCounts[$fid] ?? 0) + 1;
            }
        }

        foreach (self::QUOTA as $role => [$min, ]) {
            if ($roleCounts[$role] < $min) {
                return false;
            }
        }

        if (count($franchiseCounts) === 2) {
            $capped = array_sum(array_map(fn($n) => min($n, self::MAX_PER_FRANCHISE), $franchiseCounts));
            if ($capped < self::SQUAD_SIZE) {
                return false;
            }
        }

        return true;
    }
}
