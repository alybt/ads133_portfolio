<?php
namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Router\Request;
use App\Services\UserService;
use App\Support\Flash;
use RuntimeException;

class UserController
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService(new UserRepository());
    }

    /** GET /users */
    public function index(Request $request): void
    {
        $users = $this->service->listAll();
        view('users/index', [
            'pageTitle'   => 'Users',
            'currentPage' => 'users',
            'users'       => $users,
        ]);
    }

    /** GET /users/create */
    public function create(Request $request): void
    {
        view('users/create', [
            'pageTitle'   => 'New User',
            'currentPage' => 'users',
            'user'        => null,
            'errors'      => [],
            'old'         => [],
        ]);
    }

    /** POST /users */
    public function store(Request $request): void
    {
        try {
            $result = $this->service->create($request->body, $request->file('photo'));
        } catch (RuntimeException $e) {
            $result = ['errors' => ['photo' => $e->getMessage()]];
        }

        if ($result['errors']) {
            view('users/create', [
                'pageTitle'   => 'New User',
                'currentPage' => 'users',
                'user'        => null,
                'errors'      => $result['errors'],
                'old'         => $request->body,
            ]);
            return;
        }

        Flash::set('success', 'User created.');
        redirect(url('users/' . $result['user']->id));
    }

    /** GET /users/{id} */
    public function show(Request $request): void
    {
        $user = $this->service->get((int) $request->params['id']);
        if (!$user) {
            http_response_code(404);
            view('errors/404', ['pageTitle' => 'Not Found']);
            return;
        }

        view('users/show', [
            'pageTitle'   => $user->fullName(),
            'currentPage' => 'users',
            'user'        => $user,
        ]);
    }

    /** GET /users/{id}/edit */
    public function edit(Request $request): void
    {
        $user = $this->service->get((int) $request->params['id']);
        if (!$user) {
            http_response_code(404);
            view('errors/404', ['pageTitle' => 'Not Found']);
            return;
        }

        view('users/edit', [
            'pageTitle'   => 'Edit ' . $user->fullName(),
            'currentPage' => 'users',
            'user'        => $user,
            'errors'      => [],
            'old'         => [],
        ]);
    }

    /** POST /users/{id} */
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];

        try {
            $result = $this->service->update($id, $request->body, $request->file('photo'));
        } catch (RuntimeException $e) {
            $result = ['errors' => ['photo' => $e->getMessage()]];
        }

        if ($result['errors']) {
            view('users/edit', [
                'pageTitle'   => 'Edit User',
                'currentPage' => 'users',
                'user'        => $this->service->get($id),
                'errors'      => $result['errors'],
                'old'         => $request->body,
            ]);
            return;
        }

        Flash::set('success', 'User updated.');
        redirect(url('users/' . $id));
    }

    /** POST /users/{id}/delete */
    public function destroy(Request $request): void
    {
        $this->service->delete((int) $request->params['id']);
        Flash::set('success', 'User deleted.');
        redirect(url('users'));
    }
}