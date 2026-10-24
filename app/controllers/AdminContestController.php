<?php
require_once __DIR__ . '/../models/Contest.php';
require_once __DIR__ . '/../models/Franchise.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/TeamValidator.php';
require_once __DIR__ . '/../models/ContestEntry.php';

class AdminContestController
{
    public function index(): void
    {
        Auth::requireRole('super_admin');
        $contests = Contest::all();
        require __DIR__ . '/../views/admin/contests/index.php';
    }

    public function create(): void
    {
        Auth::requireRole('super_admin');
        $franchises = Franchise::all(true);
        require __DIR__ . '/../views/admin/contests/form.php';
    }

    public function store(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        [$franchiseAId, $franchiseBId, $matchDatetime, $venue, $error] = $this->collectAndValidate();
        if ($error !== null) {
            flash_set('error', $error);
            redirect('admin/contests/create');
        }

        Contest::create($franchiseAId, $franchiseBId, $matchDatetime, $venue);
        flash_set('success', 'Contest created and open for managers to join.');
        redirect('admin/contests');
    }

    public function edit(): void
    {
        Auth::requireRole('super_admin');
        $id = (int)($_GET['id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $franchises = Franchise::all(true);
        require __DIR__ . '/../views/admin/contests/form.php';
    }

    public function update(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $hasEntries = ContestEntry::leaderboardForContest($id) !== [];
        if ($hasEntries || $contest['status'] === 'completed') {
            flash_set('error', 'This contest already has joined managers or is completed, so its fixture details are locked. Create a new contest instead.');
            redirect('admin/contests');
        }

        [$franchiseAId, $franchiseBId, $matchDatetime, $venue, $error] = $this->collectAndValidate();
        if ($error !== null) {
            flash_set('error', $error);
            redirect("admin/contests/edit?id=$id");
        }

        Contest::update($id, $franchiseAId, $franchiseBId, $matchDatetime, $venue);
        flash_set('success', 'Contest updated.');
        redirect('admin/contests');
    }

    public function view(): void
    {
        Auth::requireRole('super_admin');
        $id = (int)($_GET['id'] ?? 0);
        $contest = Contest::find($id);
        if (!$contest) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $leaderboard = ContestEntry::leaderboardForContest($id);
        require __DIR__ . '/../views/admin/contests/view.php';
    }

    /** @return array{0:int,1:int,2:string,3:?string,4:?string} */
    private function collectAndValidate(): array
    {
        $franchiseAId = (int)($_POST['franchise_a_id'] ?? 0);
        $franchiseBId = (int)($_POST['franchise_b_id'] ?? 0);
        $matchDatetime = trim($_POST['match_datetime'] ?? '');
        $venue = trim($_POST['venue'] ?? '') ?: null;

        if ($franchiseAId === $franchiseBId) {
            return [$franchiseAId, $franchiseBId, $matchDatetime, $venue, 'The two competing franchises must be different.'];
        }
        if (!Franchise::find($franchiseAId) || !Franchise::find($franchiseBId)) {
            return [$franchiseAId, $franchiseBId, $matchDatetime, $venue, 'Choose two valid franchises.'];
        }
        $ts = strtotime($matchDatetime);
        if ($ts === false) {
            return [$franchiseAId, $franchiseBId, $matchDatetime, $venue, 'Enter a valid match date/time.'];
        }

        $pool = Player::byFranchises($franchiseAId, $franchiseBId);
        if (!TeamValidator::fixtureIsFeasible($pool)) {
            return [$franchiseAId, $franchiseBId, $matchDatetime, $venue,
                'This fixture is not playable: the combined active player pool of these two franchises cannot fill every required role (1 wicketkeeper, 3-6 batters, 1-4 all-rounders, 3-6 bowlers) within the 7-per-franchise cap. Try a different pairing, or activate more players for one of these franchises.'];
        }

        return [$franchiseAId, $franchiseBId, date('Y-m-d H:i:s', $ts), $venue, null];
    }
}
