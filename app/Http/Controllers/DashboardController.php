<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Http\Requests\Dashboard\DeleteUserRequest;
use Mugiew\StarterKit\Http\Requests\Dashboard\StoreUserRequest;
use Mugiew\StarterKit\Http\Requests\Dashboard\UpdateUserRequest;
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

    public function store(StoreUserRequest $request): Response
    {
        $payload = $request->validated();

        $userId = $this->users->create(
            (string) $payload['username'],
            (string) $payload['name'],
            (string) $payload['email'],
            password_hash((string) $payload['password'], PASSWORD_DEFAULT)
        );

        if ($userId === null) {
            flash('error', 'Username or email is already used by another account.');
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

    public function update(UpdateUserRequest $request): Response
    {
        $payload = $request->validated();

        $userId = (int) $payload['user_id'];
        $username = (string) $payload['username'];
        $name = (string) $payload['name'];
        $email = (string) $payload['email'];
        $password = (string) ($payload['password'] ?? '');

        if ($userId <= 0 || $this->users->findById($userId) === null) {
            flash('error', 'User not found.');
            return $this->redirect('/dashboard');
        }

        if ($this->users->emailExistsForAnotherUser($email, $userId)) {
            flash('error', 'Email is already used by another account.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        if ($this->users->usernameExistsForAnotherUser($username, $userId)) {
            flash('error', 'Username is already used by another account.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        $hashedPassword = null;

        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->users->update($userId, $username, $name, $email, $hashedPassword);
        flash('success', 'User updated successfully.');

        return $this->redirect('/dashboard');
    }

    public function destroy(DeleteUserRequest $request): Response
    {
        $payload = $request->validated();

        $userId = (int) $payload['user_id'];
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
