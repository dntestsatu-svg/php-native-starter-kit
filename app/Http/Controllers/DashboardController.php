<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Illuminate\Database\QueryException;
use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Http\Requests\Dashboard\DeleteUserRequest;
use Mugiew\StarterKit\Http\Requests\Dashboard\StoreUserRequest;
use Mugiew\StarterKit\Http\Requests\Dashboard\UpdateUserRequest;
use Mugiew\StarterKit\Models\User;
use Mugiew\StarterKit\Services\Auth\AuthService;

final class DashboardController extends Controller
{
    public function __construct(
        \Mugiew\StarterKit\Core\View $view,
        \Mugiew\StarterKit\Services\Security\CsrfManager $csrf,
        private readonly AuthService $auth,
        private readonly User $users,
    ) {
        parent::__construct($view, $csrf);
    }

    public function index(): Response
    {
        $users = $this->users->newQuery()
            ->with(['profile'])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (User $user): array => $user->toArray())
            ->all();

        return $this->render('dashboard.index', [
            'title' => 'Dashboard',
            'user' => $this->auth->user(),
            'users' => $users,
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

        try {
            $this->users->newQuery()->create([
                'username' => (string) $payload['username'],
                'name' => (string) $payload['name'],
                'email' => (string) $payload['email'],
                'password' => password_hash((string) $payload['password'], PASSWORD_DEFAULT),
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                flash('error', 'Username or email is already used by another account.');
                return $this->redirect('/dashboard/users/create');
            }

            throw $exception;
        }

        flash('success', 'User created successfully.');
        return $this->redirect('/dashboard');
    }

    public function showEdit(Request $request): Response
    {
        $userId = (int) $request->input('user_id', 0);
        /** @var User|null $user */
        $user = $this->users->newQuery()->find($userId);

        if ($user === null) {
            flash('error', 'User not found.');
            return $this->redirect('/dashboard');
        }

        return $this->render('dashboard.edit', [
            'title' => 'Edit User',
            'targetUser' => $user->toArray(),
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

        /** @var User|null $targetUser */
        $targetUser = $this->users->newQuery()->find($userId);

        if ($userId <= 0 || $targetUser === null) {
            flash('error', 'User not found.');
            return $this->redirect('/dashboard');
        }

        $emailExists = $this->users->newQuery()
            ->where('email', $email)
            ->where('id', '<>', $userId)
            ->exists();

        if ($emailExists) {
            flash('error', 'Email is already used by another account.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        $usernameExists = $this->users->newQuery()
            ->where('username', $username)
            ->where('id', '<>', $userId)
            ->exists();

        if ($usernameExists) {
            flash('error', 'Username is already used by another account.');
            return $this->redirect('/dashboard/users/edit?user_id=' . $userId);
        }

        $attributes = [
            'username' => $username,
            'name' => $name,
            'email' => $email,
        ];

        if ($password !== '') {
            $attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $targetUser->fill($attributes);
        $targetUser->save();
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

        /** @var User|null $targetUser */
        $targetUser = $this->users->newQuery()->find($userId);

        if ($userId <= 0 || $targetUser === null) {
            flash('error', 'Unable to delete user.');
            return $this->redirect('/dashboard');
        }

        $targetUser->delete();

        flash('success', 'User deleted successfully.');

        return $this->redirect('/dashboard');
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000';
    }
}
