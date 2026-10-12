<?php

namespace App\Controllers;

use App\Models\Link;
use App\Repositories\LinkRepository;
use App\Router\Request;
use App\Support\Auth;
use App\Support\Flash;

class LinkController
{
    private LinkRepository $repo;

    public function __construct(?LinkRepository $repo = null)
    {
        $this->repo = $repo ?? new LinkRepository();
    }

    /** GET /links or /links/create */
    public function create(Request $request): void
    {
        Auth::requireLogin();

        $links = $this->repo->all();

        view('features/links/linkform', [
            'pageTitle'      => 'Add Link',
            'currentPage'    => 'links',
            'links'          => $links,
            'errors'         => [],
            'old'            => [],
            'fromController' => true,
        ]);
    }

    /** POST /links */
    public function store(Request $request): void
    {
        Auth::requireLogin();

        $name = trim((string) $request->input('name', ''));
        $link = trim((string) $request->input('link', ''));

        $errors = [];

        // Validation: name
        if ($name === '') {
            $errors['name'] = 'Link name / platform is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Link name cannot exceed 100 characters.';
        }

        // Validation: link
        if ($link === '') {
            $errors['link'] = 'Destination URL is required.';
        } elseif (mb_strlen($link) > 250) {
            $errors['link'] = 'URL cannot exceed 250 characters.';
        } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
            $errors['link'] = 'Destination must be a valid URL (e.g. https://github.com/username).';
        }

        $old = [
            'name' => $name,
            'link' => $link,
        ];

        if (!empty($errors)) {
            $links = $this->repo->all();
            view('features/links/linkform', [
                'pageTitle'      => 'Add Link',
                'currentPage'    => 'links',
                'links'          => $links,
                'errors'         => $errors,
                'old'            => $old,
                'fromController' => true,
            ]);
            return;
        }

        $linkObj = new Link();
        $linkObj->name = $name;
        $linkObj->link = $link;

        $this->repo->create($linkObj);

        Flash::set('success', 'Link added successfully!');
        redirect(url('links'));
    }

    /** POST /links/delete */
    public function delete(Request $request): void
    {
        Auth::requireLogin();

        $id = (int) ($request->params['id'] ?? $request->input('id', 0));
        if ($id > 0) {
            $this->repo->delete($id);
            Flash::set('success', 'Link deleted successfully.');
        }

        redirect(url('links'));
    }
}
