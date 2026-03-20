<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Models\UserRepository;
use Mugiew\StarterKit\Services\Auth\AuthService;

final class DashboardController extends Controller
{
    public function __construct(
        \Mugiew\StarterKit\Core\View $view,
        \Mugiew\StarterKit\Services\Security\CsrfManager $csrf,
        private readonly AuthService $auth,
        private readonly UserRepository $users,
    ) {
        parent::__construct($view, $csrf);
    }

    public function index(): Response
    {
        return $this->render('dashboard.index', [
            'title' => 'Dashboard',
            'user' => $this->auth->user(),
            'users' => $this->users->all(),
            'error' => flash('error'),
            'success' => flash('success'),
        ], layout: 'layouts.dashboard');
    }

    public function showCreate(): Response
    {
        return $this->render('dashboard.create', [
            'title' => 'Create User',
            'error' => flash('error'),
            'success' => flash('success'),
        ], layout: 'layouts.dashboard');
    }

    public function store(Request $request): Response
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
            return $this->redirect('/dashboard/users/create');
        }

        if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            flash('error', 'Name must be between 3 and 120 characters.');
            return $this->redirect('/dashboard/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please use a valid email address.');
            return $this->redirect('/dashboard/users/create');
        }

        if (mb_strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            return $this->redirect('/dashboard/users/create');
        }

        if ($password !== $passwordConfirmation) {
            flash('error', 'Password confirmation does not match.');
            return $this->redirect('/dashboard/users/create');
        }

        $userId = $this->users->create($username, $name, $email, password_hash($password, PASSWORD_DEFAULT));

        if ($userId === null) {
            flash('error', 'Email is already used by another account.');
            return $this->redirect('/dashboard/users/create');
        }

        flash('success', 'User created successfully.');
        return $this->redirect('/dashboard');
    }

    public function showEdit(Request $request): Response
    {
        $userId = (int) $request->input('user_id', 0);
        $user = $this->users->findById($userId);

        if ($user === null) {
            flash('error', 'User not found.');
            return $this->redirect('/dashboard');
        }

        return $this->render('dashboard.edit', [
            'title' => 'Edit User',
            'targetUser' => $user,
            'error' => flash('error'),
            'success' => flash('success'),
        ], layout: 'layouts.dashboard');
    }

    public function update(Request $request): Response
    {
        $userId = (int) $request->input('user_id', 0);
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $passwordConfirmation = (string) $request->input('password_confirmation', '');

        if ($userId <= 0 || $this->users->findById($userId) === null) {
            flash('error', 'User not found.');
            return $this->redirect('/dashboard');
        }

        if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            flash('error', 'Name must be between 3 and 120 characters.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please use a valid email address.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        if ($this->users->emailExistsForAnotherUser($email, $userId)) {
            flash('error', 'Email is already used by another account.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        $hashedPassword = null;

        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                flash('error', 'Password must be at least 8 characters.');
                return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
            }

            if ($password !== $passwordConfirmation) {
                flash('error', 'Password confirmation does not match.');
                return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->users->update($userId, $name, $email, $hashedPassword);
        flash('success', 'User updated successfully.');

        return $this->redirect('/dashboard');
    }

    public function destroy(Request $request): Response
    {
        $userId = (int) $request->input('user_id', 0);
        $currentUser = $this->auth->user();

        if ($currentUser !== null && (int) $currentUser['id'] === $userId) {
            flash('error', 'You cannot delete your currently logged-in account.');
            return $this->redirect('/dashboard');
        }

        if ($userId <= 0 || !$this->users->delete($userId)) {
            flash('error', 'Unable to delete user.');
            return $this->redirect('/dashboard');
        }

        flash('success', 'User deleted successfully.');

        return $this->redirect('/dashboard');
    }
}
