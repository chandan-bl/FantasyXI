<?php
require_once __DIR__ . '/../models/Contest.php';
require_once __DIR__ . '/../models/ContestEntry.php';

class LeaderboardController
{
    /** Season-cumulative leaderboard, viewable by both personas. */
    public function season(): void
    {
        Auth::requireRole('super_admin', 'manager');
        $season = ContestEntry::seasonLeaderboard();
        $contests = Contest::all();
        require __DIR__ . '/../views/shared/leaderboard_season.php';
    }

    /** Per-contest leaderboard, viewable by both personas. */
    public function contest(): void
    {
        Auth::requireRole('super_admin', 'manager');
        $id = (int)($_GET['id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $leaderboard = ContestEntry::leaderboardForContest($id);
        require __DIR__ . '/../views/shared/leaderboard_contest.php';
    }
}
