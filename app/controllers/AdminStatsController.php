<?php
require_once __DIR__ . '/../models/Contest.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/MatchStat.php';
require_once __DIR__ . '/../models/ScoringRun.php';
require_once __DIR__ . '/../models/MatchSimulator.php';

class AdminStatsController
{
    private const FIELDS = [
        'played', 'runs', 'fours', 'sixes', 'is_out',
        'wickets', 'wickets_bowled_lbw', 'maidens',
        'catches', 'stumpings', 'run_outs_direct', 'run_outs_assisted',
    ];

    public function entry(): void
    {
        Auth::requireRole('super_admin');
        $id = (int)($_GET['id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $pool = Player::byFranchises((int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
        $existingStats = MatchStat::forContest($id);
        require __DIR__ . '/../views/admin/stats/entry.php';
    }

    public function save(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['contest_id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!is_contest_locked($contest['match_datetime']) && $contest['status'] !== 'completed') {
            flash_set('error', 'Match stats can only be entered once the contest deadline has passed.');
            redirect("admin/contests/stats?id=$id");
        }

        $rows = $_POST['stat'] ?? [];
        foreach ($rows as $playerId => $row) {
            $playerId = (int)$playerId;
            $data = [];
            foreach (self::FIELDS as $f) {
                if ($f === 'played' || $f === 'is_out') {
                    $data[$f] = !empty($row[$f]) ? 1 : 0;
                } else {
                    $data[$f] = max(0, (int)($row[$f] ?? 0));
                }
            }
            // A wicket-count sanity check: can't attribute more bowled/LBW
            // dismissals than the total wickets taken.
            if ($data['wickets_bowled_lbw'] > $data['wickets']) {
                $data['wickets_bowled_lbw'] = $data['wickets'];
            }
            MatchStat::upsert($id, $playerId, $data);
        }

        flash_set('success', 'Match stats saved. Run scoring when ready.');
        redirect("admin/contests/stats?id=$id");
    }

    public function simulate(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['contest_id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!is_contest_locked($contest['match_datetime']) && $contest['status'] !== 'completed') {
            flash_set('error', 'Match stats can only be entered once the contest deadline has passed.');
            redirect("admin/contests/stats?id=$id");
        }

        $pool = Player::byFranchises((int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
        $rows = MatchSimulator::simulate($pool, (int)$contest['franchise_a_id'], (int)$contest['franchise_b_id']);
        foreach ($rows as $playerId => $data) {
            MatchStat::upsert($id, $playerId, $data);
        }

        flash_set('success', 'Realistic match stats simulated -- review the numbers below, then run scoring when ready.');
        redirect("admin/contests/stats?id=$id");
    }

    public function score(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['contest_id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!is_contest_locked($contest['match_datetime'])) {
            flash_set('error', 'Cannot run scoring before the contest deadline has passed.');
            redirect("admin/contests/stats?id=$id");
        }

        ScoringRun::run($id);
        flash_set('success', 'Scoring run complete. Leaderboards updated.');
        redirect("admin/contests/view?id=$id");
    }
}
