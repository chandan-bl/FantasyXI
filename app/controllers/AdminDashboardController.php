<?php
require_once __DIR__ . '/../models/Contest.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/Franchise.php';
require_once __DIR__ . '/../models/User.php';

class AdminDashboardController
{
    public function dashboard(): void
    {
        Auth::requireRole('super_admin');

        $contests = Contest::all();
        $openCount = 0;
        $lockedPendingCount = 0;
        $completedCount = 0;
        foreach ($contests as $c) {
            if ($c['status'] === 'completed') {
                $completedCount++;
            } elseif (is_contest_locked($c['match_datetime'])) {
                $lockedPendingCount++;
            } else {
                $openCount++;
            }
        }

        $stats = [
            'players'    => count(Player::all()),
            'franchises' => count(Franchise::all()),
            'managers'   => count(User::allManagers()),
            'open'       => $openCount,
            'pending'    => $lockedPendingCount,
            'completed'  => $completedCount,
        ];

        $recentContests = array_slice($contests, 0, 6);

        require __DIR__ . '/../views/admin/dashboard.php';
    }
}
