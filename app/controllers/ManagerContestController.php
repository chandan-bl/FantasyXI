<?php
require_once __DIR__ . '/../models/Contest.php';

class ManagerContestController
{
    public function browse(): void
    {
        Auth::requireRole('manager');
        $contests = Contest::openForManager(Auth::id());
        require __DIR__ . '/../views/manager/contests/browse.php';
    }

    public function join(): void
    {
        Auth::requireRole('manager');
        Csrf::requireValid();

        $id = (int)($_POST['contest_id'] ?? 0);
        $contest = Contest::find($id);
        $managerId = Auth::id();

        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (Contest::isLocked($contest)) {
            flash_set('error', 'This contest is locked -- the deadline has passed.');
            redirect('manager/contests');
        }

        if (Contest::managerHasJoined($id, $managerId)) {
            flash_set('error', "You've already joined this contest.");
            redirect('manager/contests');
        }

        // Joining just marks intent -- the actual team is built (and the
        // contest_entries row created) on first save in the team builder.
        // Send the manager straight there.
        redirect("manager/team/build?contest_id=$id");
    }
}
