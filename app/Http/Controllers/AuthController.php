<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Services\Auth\AuthService;

final class AuthController extends Controller
{
    public function __construct(
        \Mugiew\StarterKit\Core\View $view,
        \Mugiew\StarterKit\Services\Security\CsrfManager $csrf,
        private readonly AuthService $auth,
    ) {
        parent::__construct($view, $csrf);
    }

    public function showLogin(): Response
    {
        return $this->render('auth.login', [
            'title' => 'Login',
            'error' => flash('error'),
            'success' => flash('success'),
        ]);
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please use a valid email address.');
            return $this->redirect('/login');
        }

        if ($password === '') {
            flash('error', 'Password is required.');
            return $this->redirect('/login');
        }

        $user = $this->auth->attempt($email, $password);

        if ($user === null) {
            flash('error', 'Invalid credentials.');
            return $this->redirect('/login');
        }

        $this->auth->login($user);
        flash('success', 'Welcome back.');

        return $this->redirect('/dashboard');
    }

    public function showRegister(): Response
    {
        return $this->render('auth.register', [
            'title' => 'Register',
            'error' => flash('error'),
            'success' => flash('success'),
        ]);
    }

    public function register(Request $request): Response
    {
        $username = trim((string) $request->input('username', ''));
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $passwordConfirmation = (string) $request->input('password_confirmation', '');

        if (
            $username === '' ||
            mb_strlen($username) < 3 ||
            mb_strlen($username) > 20 ||
            !preg_match('/^[a-zA-Z0-9]+$/', $username)
        ) {
            flash('error', 'Username harus 3-20 karakter dan hanya boleh huruf & angka tanpa spasi.');
            return $this->redirect('/register');
        }

        if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            flash('error', 'Name must be between 3 and 120 characters.');
            return $this->redirect('/register');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please use a valid email address.');
            return $this->redirect('/register');
        }

        if (mb_strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            return $this->redirect('/register');
        }

        if ($password !== $passwordConfirmation) {
            flash('error', 'Password confirmation does not match.');
            return $this->redirect('/register');
        }

        $user = $this->auth->register($username, $name, $email, $password);

        if ($user === null) {
            flash('error', 'This email is already registered.');
            return $this->redirect('/register');
        }

        $this->auth->login($user);
        flash('success', 'Registration successful.');

        return $this->redirect('/dashboard');
    }

    public function logout(): Response
    {
        $this->auth->logout();
        flash('success', 'You have been logged out.');

        return $this->redirect('/login');
    }
}
