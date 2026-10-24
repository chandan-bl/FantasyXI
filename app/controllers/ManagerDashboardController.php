<?php
require_once __DIR__ . '/../models/Contest.php';
require_once __DIR__ . '/../models/ContestEntry.php';

class ManagerDashboardController
{
    public function dashboard(): void
    {
        Auth::requireRole('manager');
        $managerId = Auth::id();

        $myEntries = ContestEntry::forManager($managerId);
        $openContests = Contest::openForManager($managerId);

        $seasonPoints = array_sum(array_column($myEntries, 'total_points'));
        $contestsJoined = count($myEntries);

        // Season rank
        $season = ContestEntry::seasonLeaderboard();
        $rank = null;
        foreach ($season as $i => $row) {
            if ((int)$row['manager_id'] === $managerId) {
                $rank = $i + 1;
                break;
            }
        }

        $stats = [
            'season_points'   => $seasonPoints,
            'contests_joined' => $contestsJoined,
            'open_contests'   => count($openContests),
            'season_rank'     => $rank,
            'season_total'    => count($season),
        ];

        $recentEntries = array_slice($myEntries, 0, 5);
        $upcomingOpen = array_slice($openContests, 0, 4);

        require __DIR__ . '/../views/manager/dashboard.php';
    }
}
