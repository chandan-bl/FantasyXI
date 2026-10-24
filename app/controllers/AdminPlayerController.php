<?php
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/Franchise.php';

class AdminPlayerController
{
    public function index(): void
    {
        Auth::requireRole('super_admin');
        $players = Player::all();
        $franchises = Franchise::all();
        $roleFilter = $_GET['role'] ?? '';
        $franchiseFilter = $_GET['franchise_id'] ?? '';
        if ($roleFilter !== '') {
            $players = array_values(array_filter($players, fn($p) => $p['role'] === $roleFilter));
        }
        if ($franchiseFilter !== '') {
            $players = array_values(array_filter($players, fn($p) => (int)$p['franchise_id'] === (int)$franchiseFilter));
        }
        require __DIR__ . '/../views/admin/players/index.php';
    }

    public function create(): void
    {
        Auth::requireRole('super_admin');
        $franchises = Franchise::all(true);
        require __DIR__ . '/../views/admin/players/form.php';
    }

    public function store(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $data = $this->collect();
        $errors = $this->validate($data);

        if ($errors !== []) {
            flash_set('error', $errors[0]);
            redirect('admin/players/create');
        }

        Player::create($data);
        flash_set('success', "Player \"{$data['name']}\" added to the pool.");
        redirect('admin/players');
    }

    public function edit(): void
    {
        Auth::requireRole('super_admin');
        $id = (int)($_GET['id'] ?? 0);
        $player = Player::find($id);
        if (!$player) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $franchises = Franchise::all(true);
        require __DIR__ . '/../views/admin/players/form.php';
    }

    public function update(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (!Player::find($id)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $data = $this->collect();
        $errors = $this->validate($data);
        if ($errors !== []) {
            flash_set('error', $errors[0]);
            redirect("admin/players/edit?id=$id");
        }

        Player::update($id, $data);
        flash_set('success', "Player \"{$data['name']}\" updated.");
        redirect('admin/players');
    }

    public function status(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        if (!Player::find($id)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        Player::setStatus($id, $status);
        flash_set('success', 'Player status updated.');
        redirect('admin/players');
    }

    private function collect(): array
    {
        return [
            'name'            => trim($_POST['name'] ?? ''),
            'franchise_id'    => (int)($_POST['franchise_id'] ?? 0),
            'role'            => $_POST['role'] ?? '',
            'credit_value'    => (float)($_POST['credit_value'] ?? 0),
            'career_runs'     => (int)($_POST['career_runs'] ?? 0),
            'career_wickets'  => (int)($_POST['career_wickets'] ?? 0),
            'career_matches'  => (int)($_POST['career_matches'] ?? 0),
            'career_trophies' => (int)($_POST['career_trophies'] ?? 0),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Player name is required.';
        }
        if (!in_array($data['role'], ['wicketkeeper', 'batter', 'allrounder', 'bowler'], true)) {
            $errors[] = 'Choose a valid role.';
        }
        if (!Franchise::find($data['franchise_id'])) {
            $errors[] = 'Choose a valid franchise.';
        }
        if ($data['credit_value'] < 6.5 || $data['credit_value'] > 10.5) {
            $errors[] = 'Credit value must be between 6.5 and 10.5.';
        }
        return $errors;
    }
}
