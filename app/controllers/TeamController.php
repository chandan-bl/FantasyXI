<?php
require_once __DIR__ . '/../models/Contest.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/ContestEntry.php';
require_once __DIR__ . '/../models/TeamValidator.php';
require_once __DIR__ . '/../models/MatchStat.php';
require_once __DIR__ . '/../models/Scoring.php';

class TeamController
{
    public function build(): void
    {
        Auth::requireRole('manager');
        $managerId = Auth::id();
        $contestId = (int)($_GET['contest_id'] ?? 0);
        $contest = Contest::find($contestId);

        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $pool = Player::byFranchises((int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
        $existingEntry = ContestEntry::findForManagerContest($contestId, $managerId);
        $existingPlayers = $existingEntry ? ContestEntry::playersForEntry((int)$existingEntry['id']) : [];
        $locked = Contest::isLocked($contest);

        if (!$existingEntry && $locked) {
            flash_set('error', 'This contest is locked -- you can no longer build a team for it.');
            redirect('manager/contests');
        }

        // A just-rejected submission for this same contest takes priority over
        // whatever's already saved, so a failed save doesn't wipe the manager's
        // in-progress picks -- see old_input_set() in save() below.
        $oldInput = old_input_get();
        if ($oldInput && (int)($oldInput['contest_id'] ?? 0) === $contestId) {
            $existingIds = array_map('intval', $oldInput['player_ids'] ?? []);
            $captainId = (int)($oldInput['captain_id'] ?? 0) ?: null;
            $vcId = (int)($oldInput['vice_captain_id'] ?? 0) ?: null;
        } else {
            $existingIds = array_map(fn($p) => (int)$p['player_id'], $existingPlayers);
            $captainId = $existingEntry ? (int)$existingEntry['captain_player_id'] : null;
            $vcId = $existingEntry ? (int)$existingEntry['vice_captain_player_id'] : null;
        }

        require __DIR__ . '/../views/manager/team/build.php';
    }

    public function save(): void
    {
        Auth::requireRole('manager');
        Csrf::requireValid();
        $managerId = Auth::id();

        $contestId = (int)($_POST['contest_id'] ?? 0);
        $contest = Contest::find($contestId);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (Contest::isLocked($contest)) {
            flash_set('error', 'This contest is locked -- the deadline has passed, so the team can no longer be created or edited.');
            redirect('manager/contests');
        }

        $submittedIds = array_values(array_unique(array_map('intval', $_POST['player_ids'] ?? [])));
        $captainId = (int)($_POST['captain_id'] ?? 0);
        $viceCaptainId = (int)($_POST['vice_captain_id'] ?? 0);

        $pool = Player::byFranchises((int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
        $poolById = [];
        foreach ($pool as $p) {
            $poolById[(int)$p['id']] = $p;
        }
        $eligibleIds = array_fill_keys(array_keys($poolById), true);

        // Only ever trust server-fetched player records (role/credit/franchise)
        // for validation -- never the client's submitted values.
        $selectedPlayers = [];
        foreach ($submittedIds as $id) {
            if (isset($poolById[$id])) {
                $selectedPlayers[] = $poolById[$id];
            }
        }

        $errors = [];
        if (count($selectedPlayers) !== count($submittedIds)) {
            $errors[] = 'One or more selected players are no longer part of this contest\'s eligible pool.';
        }
        $errors = array_merge($errors, TeamValidator::validate($selectedPlayers, $eligibleIds, $captainId ?: null, $viceCaptainId ?: null));

        if ($errors !== []) {
            flash_set('error', implode(' ', array_unique($errors)));
            old_input_set([
                'contest_id' => $contestId,
                'player_ids' => $submittedIds,
                'captain_id' => $captainId,
                'vice_captain_id' => $viceCaptainId,
            ]);
            redirect("manager/team/build?contest_id=$contestId");
        }

        $totalCredits = array_sum(array_map(fn($p) => (float)$p['credit_value'], $selectedPlayers));
        $existingEntry = ContestEntry::findForManagerContest($contestId, $managerId);

        try {
            ContestEntry::save(
                $contestId,
                $managerId,
                array_map(fn($p) => (int)$p['id'], $selectedPlayers),
                $captainId,
                $viceCaptainId,
                round($totalCredits, 1),
                $existingEntry ? (int)$existingEntry['id'] : null
            );
        } catch (PDOException $e) {
            // Unique(contest_id, manager_id) tripped by a near-simultaneous
            // second submission -- second line of defense behind the
            // managerHasJoined() check in ManagerContestController::join().
            flash_set('error', 'You already have a team saved for this contest.');
            old_input_set([
                'contest_id' => $contestId,
                'player_ids' => $submittedIds,
                'captain_id' => $captainId,
                'vice_captain_id' => $viceCaptainId,
            ]);
            redirect("manager/team/build?contest_id=$contestId");
        }

        flash_set('success', 'Team saved.');
        redirect('manager/mycontests');
    }

    public function myContests(): void
    {
        Auth::requireRole('manager');
        $entries = ContestEntry::forManager(Auth::id());
        require __DIR__ . '/../views/manager/team/mycontests.php';
    }

    public function viewBreakdown(): void
    {
        Auth::requireRole('manager');
        $entryId = (int)($_GET['entry_id'] ?? 0);
        $entry = ContestEntry::find($entryId);

        if (!$entry || (int)$entry['manager_id'] !== Auth::id()) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $contest = Contest::find((int)$entry['contest_id']);
        $players = ContestEntry::playersForEntry($entryId);

        $breakdowns = [];
        foreach ($players as $p) {
            $stat = MatchStat::forContestPlayer((int)$contest['id'], (int)$p['player_id']);
            $lines = $stat ? Scoring::breakdownFor($stat, $p['role']) : null; // null = stats not entered yet
            $multiplier = Scoring::multiplierFor((bool)$p['is_captain'], (bool)$p['is_vice_captain']);
            $breakdowns[(int)$p['player_id']] = ['lines' => $lines, 'multiplier' => $multiplier];
        }

        require __DIR__ . '/../views/manager/team/breakdown.php';
    }
}
