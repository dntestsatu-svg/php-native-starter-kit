<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Http\Requests\Auth\LoginRequest;
use Mugiew\StarterKit\Http\Requests\Auth\RegisterRequest;
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

    public function login(LoginRequest $request): Response
    {
        $payload = $request->validated();

        $user = $this->auth->attempt((string) $payload['email'], (string) $payload['password']);

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

    public function register(RegisterRequest $request): Response
    {
        $payload = $request->validated();

        $user = $this->auth->register(
            (string) $payload['username'],
            (string) $payload['name'],
            (string) $payload['email'],
            (string) $payload['password'],
        );

        if ($user === null) {
            flash('error', 'Username or email is already registered.');
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
