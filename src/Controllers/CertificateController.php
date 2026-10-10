<?php

namespace App\Controllers;

use App\Models\Certificate;
use App\Repositories\CertificateRepository;
use App\Router\Request;
use App\Support\Auth;
use App\Support\Flash;

class CertificateController
{
    private CertificateRepository $repo;

    public function __construct(?CertificateRepository $repo = null)
    {
        $this->repo = $repo ?? new CertificateRepository();
    }

    /** GET /certificates or /certificates/create */
    public function create(Request $request): void
    {
        Auth::requireLogin();

        $certificates = $this->repo->all();

        view('features/certificates/certform', [
            'pageTitle'      => 'Add Certificate',
            'currentPage'    => 'certificates',
            'certificates'   => $certificates,
            'errors'         => [],
            'old'            => [],
            'fromController' => true,
        ]);
    }

    /** POST /certificates */
    public function store(Request $request): void
    {
        Auth::requireLogin();

        $title        = trim((string) $request->input('title', ''));
        $company      = trim((string) $request->input('company', ''));
        $receivedDate = trim((string) $request->input('received_date', ''));
        $description  = trim((string) $request->input('description', ''));

        $errors = [];

        // Validation: title
        if ($title === '') {
            $errors['title'] = 'Certificate title is required.';
        } elseif (mb_strlen($title) > 250) {
            $errors['title'] = 'Title cannot exceed 250 characters.';
        } elseif ($this->repo->existsByTitle($title)) {
            $errors['title'] = 'A certificate with this title already exists.';
        }

        // Validation: company
        if ($company === '') {
            $errors['company'] = 'Issuing organization / company is required.';
        } elseif (mb_strlen($company) > 100) {
            $errors['company'] = 'Company name cannot exceed 100 characters.';
        }

        // Validation: received_date
        if ($receivedDate === '') {
            $errors['received_date'] = 'Date received is required.';
        } else {
            $d = \DateTime::createFromFormat('Y-m-d', $receivedDate);
            if (!$d || $d->format('Y-m-d') !== $receivedDate) {
                $errors['received_date'] = 'Date received must be a valid date (YYYY-MM-DD).';
            }
        }

        $old = [
            'title'         => $title,
            'company'       => $company,
            'received_date' => $receivedDate,
            'description'   => $description,
        ];

        if (!empty($errors)) {
            $certificates = $this->repo->all();
            view('features/certificates/certform', [
                'pageTitle'      => 'Add Certificate',
                'currentPage'    => 'certificates',
                'certificates'   => $certificates,
                'errors'         => $errors,
                'old'            => $old,
                'fromController' => true,
            ]);
            return;
        }

        $cert = new Certificate();
        $cert->title        = $title;
        $cert->company      = $company;
        $cert->receivedDate = $receivedDate;
        $cert->description  = $description !== '' ? $description : null;

        $this->repo->create($cert);

        Flash::set('success', 'Certificate added successfully!');
        redirect(url('certificates'));
    }

    /** POST /certificates/delete */
    public function delete(Request $request): void
    {
        Auth::requireLogin();

        $id = (int) ($request->params['id'] ?? $request->input('id', 0));
        if ($id > 0) {
            $this->repo->delete($id);
            Flash::set('success', 'Certificate deleted successfully.');
        }

        redirect(url('certificates'));
    }
}
