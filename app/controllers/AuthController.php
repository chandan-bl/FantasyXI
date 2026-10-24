<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        Csrf::requireValid();

        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (Auth::attempt($email, $password)) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }

        flash_set('error', 'Invalid email/password, or your account is inactive.');
        $old = ['email' => $email];
        require __DIR__ . '/../views/auth/login.php';
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void
    {
        Csrf::requireValid();

        $data = [
            'name'     => trim($_POST['name'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'password' => (string)($_POST['password'] ?? ''),
        ];

        $v = new Validator();
        $v->required($data, 'name', 'Name')
          ->required($data, 'email', 'Email')
          ->email($data, 'email', 'Email')
          ->required($data, 'password', 'Password')
          ->minLength($data, 'password', 8, 'Password');

        if (!$v->fails() && User::findByEmail($data['email'])) {
            flash_set('error', 'That email is already registered.');
            $old = $data;
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        if ($v->fails()) {
            flash_set('error', $v->first());
            $old = $data;
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        User::create($data['name'], $data['email'], $data['password'], 'manager');
        flash_set('success', 'Account created. Log in to start building your first team.');
        redirect('login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('login');
    }
}
