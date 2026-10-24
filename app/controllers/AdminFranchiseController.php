<?php
require_once __DIR__ . '/../models/Franchise.php';

class AdminFranchiseController
{
    public function index(): void
    {
        Auth::requireRole('super_admin');
        $franchises = Franchise::all();
        require __DIR__ . '/../views/admin/franchises/index.php';
    }

    public function store(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['short_code'] ?? ''));

        if ($name === '' || $code === '') {
            flash_set('error', 'Name and short code are both required.');
            redirect('admin/franchises');
        }

        Franchise::create($name, $code);
        flash_set('success', "Franchise \"$name\" added.");
        redirect('admin/franchises');
    }

    public function update(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (!Franchise::find($id)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['short_code'] ?? ''));
        if ($name === '' || $code === '') {
            flash_set('error', 'Name and short code are both required.');
            redirect('admin/franchises');
        }

        Franchise::update($id, $name, $code);
        flash_set('success', 'Franchise updated.');
        redirect('admin/franchises');
    }

    public function status(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        if (!Franchise::find($id)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        Franchise::setStatus($id, $status);
        flash_set('success', 'Franchise status updated.');
        redirect('admin/franchises');
    }
}
