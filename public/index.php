<?php

declare(strict_types=1);

// Let PHP's built-in dev server (`php -S`) serve real static files directly
// instead of routing every asset request through the front controller.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminDashboardController.php';
require_once __DIR__ . '/../app/controllers/AdminPlayerController.php';
require_once __DIR__ . '/../app/controllers/AdminFranchiseController.php';
require_once __DIR__ . '/../app/controllers/AdminContestController.php';
require_once __DIR__ . '/../app/controllers/AdminStatsController.php';
require_once __DIR__ . '/../app/controllers/AdminManagerController.php';
require_once __DIR__ . '/../app/controllers/ManagerDashboardController.php';
require_once __DIR__ . '/../app/controllers/ManagerContestController.php';
require_once __DIR__ . '/../app/controllers/TeamController.php';
require_once __DIR__ . '/../app/controllers/LeaderboardController.php';

$router = new Router();

// ---- Auth ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->post('logout', fn() => (new AuthController())->logout());
$router->get('register', fn() => (new AuthController())->showRegister());
$router->post('register', fn() => (new AuthController())->register());

// ---- Super Admin: dashboard ----
$router->get('admin/dashboard', fn() => (new AdminDashboardController())->dashboard());

// ---- Super Admin: player pool ----
$router->get('admin/players', fn() => (new AdminPlayerController())->index());
$router->get('admin/players/create', fn() => (new AdminPlayerController())->create());
$router->post('admin/players/store', fn() => (new AdminPlayerController())->store());
$router->get('admin/players/edit', fn() => (new AdminPlayerController())->edit());
$router->post('admin/players/update', fn() => (new AdminPlayerController())->update());
$router->post('admin/players/status', fn() => (new AdminPlayerController())->status());

// ---- Super Admin: franchises ----
$router->get('admin/franchises', fn() => (new AdminFranchiseController())->index());
$router->post('admin/franchises/store', fn() => (new AdminFranchiseController())->store());
$router->post('admin/franchises/update', fn() => (new AdminFranchiseController())->update());
$router->post('admin/franchises/status', fn() => (new AdminFranchiseController())->status());

// ---- Super Admin: contests ----
$router->get('admin/contests', fn() => (new AdminContestController())->index());
$router->get('admin/contests/create', fn() => (new AdminContestController())->create());
$router->post('admin/contests/store', fn() => (new AdminContestController())->store());
$router->get('admin/contests/edit', fn() => (new AdminContestController())->edit());
$router->post('admin/contests/update', fn() => (new AdminContestController())->update());
$router->get('admin/contests/view', fn() => (new AdminContestController())->view());

// ---- Super Admin: match stats + scoring ----
$router->get('admin/contests/stats', fn() => (new AdminStatsController())->entry());
$router->post('admin/contests/stats/save', fn() => (new AdminStatsController())->save());
$router->post('admin/contests/stats/simulate', fn() => (new AdminStatsController())->simulate());
$router->post('admin/contests/score', fn() => (new AdminStatsController())->score());

// ---- Super Admin: managers ----
$router->get('admin/managers', fn() => (new AdminManagerController())->index());
$router->post('admin/managers/status', fn() => (new AdminManagerController())->status());

// ---- Manager: dashboard ----
$router->get('manager/dashboard', fn() => (new ManagerDashboardController())->dashboard());

// ---- Manager: contests ----
$router->get('manager/contests', fn() => (new ManagerContestController())->browse());
$router->post('manager/contests/join', fn() => (new ManagerContestController())->join());

// ---- Manager: team building ----
$router->get('manager/team/build', fn() => (new TeamController())->build());
$router->post('manager/team/save', fn() => (new TeamController())->save());
$router->get('manager/mycontests', fn() => (new TeamController())->myContests());
$router->get('manager/team/breakdown', fn() => (new TeamController())->viewBreakdown());

// ---- Leaderboards (shared) ----
$router->get('leaderboard/season', fn() => (new LeaderboardController())->season());
$router->get('leaderboard/contest', fn() => (new LeaderboardController())->contest());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    redirect('login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
