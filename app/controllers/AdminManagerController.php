<?php
require_once __DIR__ . '/../models/User.php';

class AdminManagerController
{
    public function index(): void
    {
        Auth::requireRole('super_admin');
        $managers = User::allManagers();
        require __DIR__ . '/../views/admin/managers/index.php';
    }

    public function status(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        if (!User::findById($id)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        User::setStatus($id, $status);
        flash_set('success', 'Manager status updated.');
        redirect('admin/managers');
    }
}
