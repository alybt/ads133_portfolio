<?php

namespace App\Controllers;

use App\Models\Education;
use App\Repositories\EducationRepository;
use App\Router\Request;
use App\Support\Auth;
use App\Support\Flash;

class EducationController
{
    private EducationRepository $repo;

    public function __construct(?EducationRepository $repo = null)
    {
        $this->repo = $repo ?? new EducationRepository();
    }

    /** GET /educations or /educations/create */
    public function create(Request $request): void
    {
        Auth::requireLogin();

        $educations = $this->repo->all();

        view('features/educations/eduform', [
            'pageTitle'      => 'Add Education',
            'currentPage'    => 'educations',
            'educations'     => $educations,
            'errors'         => [],
            'old'            => [],
            'fromController' => true,
        ]);
    }

    /** POST /educations */
    public function store(Request $request): void
    {
        Auth::requireLogin();

        $schoolName  = trim((string) $request->input('school_name', ''));
        $address     = trim((string) $request->input('address', ''));
        $grades      = trim((string) $request->input('grades', ''));
        $startedDate = trim((string) $request->input('started_date', ''));
        $endDate     = trim((string) $request->input('end_date', ''));

        $errors = [];

        // Validation: school_name
        if ($schoolName === '') {
            $errors['school_name'] = 'School name is required.';
        } elseif (mb_strlen($schoolName) > 250) {
            $errors['school_name'] = 'School name cannot exceed 250 characters.';
        } elseif ($this->repo->existsBySchoolName($schoolName)) {
            $errors['school_name'] = 'This school name is already recorded.';
        }

        // Validation: address
        if ($address === '') {
            $errors['address'] = 'Address is required.';
        } elseif (mb_strlen($address) > 250) {
            $errors['address'] = 'Address cannot exceed 250 characters.';
        }

        // Validation: grades
        if ($grades !== '') {
            if (!is_numeric($grades)) {
                $errors['grades'] = 'Grades must be a valid number or decimal.';
            } elseif ((float) $grades < 0) {
                $errors['grades'] = 'Grades cannot be negative.';
            }
        }

        // Validation: started_date
        if ($startedDate === '') {
            $errors['started_date'] = 'Start date is required.';
        } else {
            $dStart = \DateTime::createFromFormat('Y-m-d', $startedDate);
            if (!$dStart || $dStart->format('Y-m-d') !== $startedDate) {
                $errors['started_date'] = 'Start date must be a valid date (YYYY-MM-DD).';
            }
        }

        // Validation: end_date (optional, but if provided must be valid and after start date)
        if ($endDate !== '') {
            $dEnd = \DateTime::createFromFormat('Y-m-d', $endDate);
            if (!$dEnd || $dEnd->format('Y-m-d') !== $endDate) {
                $errors['end_date'] = 'End date must be a valid date (YYYY-MM-DD).';
            } elseif (empty($errors['started_date']) && $startedDate !== '' && $endDate < $startedDate) {
                $errors['end_date'] = 'End date cannot be earlier than start date.';
            }
        }

        $old = [
            'school_name'  => $schoolName,
            'address'      => $address,
            'grades'       => $grades,
            'started_date' => $startedDate,
            'end_date'     => $endDate,
        ];

        if (!empty($errors)) {
            $educations = $this->repo->all();
            view('features/educations/eduform', [
                'pageTitle'      => 'Add Education',
                'currentPage'    => 'educations',
                'educations'     => $educations,
                'errors'         => $errors,
                'old'            => $old,
                'fromController' => true,
            ]);
            return;
        }

        $edu = new Education();
        $edu->schoolName  = $schoolName;
        $edu->address     = $address;
        $edu->grades      = $grades !== '' ? $grades : null;
        $edu->startedDate = $startedDate;
        $edu->endDate     = $endDate !== '' ? $endDate : null;

        $this->repo->create($edu);

        Flash::set('success', 'Education record added successfully!');
        redirect(url('educations'));
    }

    /** POST /educations/{id}/delete */
    public function delete(Request $request): void
    {
        Auth::requireLogin();

        $id = (int) ($request->params['id'] ?? $request->input('id', 0));
        if ($id > 0) {
            $this->repo->delete($id);
            Flash::set('success', 'Education record deleted.');
        }

        redirect(url('educations'));
    }
}
