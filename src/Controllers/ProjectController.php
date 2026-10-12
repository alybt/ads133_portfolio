<?php

namespace App\Controllers;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Router\Request;
use App\Support\Auth;
use App\Support\Flash;

class ProjectController
{
    private ProjectRepository $repo;

    public function __construct(?ProjectRepository $repo = null)
    {
        $this->repo = $repo ?? new ProjectRepository();
    }

    /** GET /projects or /projects/create */
    public function create(Request $request): void
    {
        Auth::requireLogin();

        $projects = $this->repo->all();

        view('features/projects/projectform', [
            'pageTitle'      => 'Add Project',
            'currentPage'    => 'projects',
            'projects'       => $projects,
            'errors'         => [],
            'old'            => [],
            'fromController' => true,
        ]);
    }

    /** POST /projects */
    public function store(Request $request): void
    {
        Auth::requireLogin();

        $title       = trim((string) $request->input('title', ''));
        $startedDate = trim((string) $request->input('started_date', ''));
        $endDate     = trim((string) $request->input('end_date', ''));
        $description = trim((string) $request->input('description', ''));
        $photoName   = trim((string) $request->input('photo_name', ''));
        $photoUrl    = trim((string) $request->input('photo_url', ''));
        $linkName    = trim((string) $request->input('link_name', ''));
        $linkUrl     = trim((string) $request->input('link_url', ''));

        $errors = [];

        // Validation: title
        if ($title === '') {
            $errors['title'] = 'Project title is required.';
        } elseif (mb_strlen($title) > 100) {
            $errors['title'] = 'Project title cannot exceed 100 characters.';
        } elseif ($this->repo->existsByTitle($title)) {
            $errors['title'] = 'A project with this title already exists.';
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

        // Validation: end_date
        if ($endDate !== '') {
            $dEnd = \DateTime::createFromFormat('Y-m-d', $endDate);
            if (!$dEnd || $dEnd->format('Y-m-d') !== $endDate) {
                $errors['end_date'] = 'End date must be a valid date (YYYY-MM-DD).';
            } elseif (empty($errors['started_date']) && $startedDate !== '' && $endDate < $startedDate) {
                $errors['end_date'] = 'End date cannot be earlier than start date.';
            }
        }

        // Handle Photo File Upload
        $storedPhotoPath = null;
        $storedPhotoName = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileError = $_FILES['photo']['error'];
            if ($fileError === UPLOAD_ERR_OK) {
                $tmpPath   = $_FILES['photo']['tmp_name'];
                $origName  = basename($_FILES['photo']['name']);
                $fileSize  = $_FILES['photo']['size'];
                $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors['photo'] = 'Uploaded photo must not exceed 5MB.';
                } elseif (!in_array($ext, $allowed, true)) {
                    $errors['photo'] = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP, SVG.';
                } else {
                    $uploadDir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/public/assets/photos';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $newFilename = 'proj_' . uniqid('', true) . '.' . $ext;
                    $targetPath  = $uploadDir . '/' . $newFilename;

                    if (move_uploaded_file($tmpPath, $targetPath)) {
                        $storedPhotoPath = 'assets/photos/' . $newFilename;
                        $storedPhotoName = $photoName !== '' ? $photoName : $origName;
                    } else {
                        $errors['photo'] = 'Failed to save uploaded photo file.';
                    }
                }
            } else {
                $errors['photo'] = 'Error uploading photo file.';
            }
        } elseif ($photoUrl !== '') {
            // Optional external URL or path
            if (mb_strlen($photoUrl) > 250) {
                $errors['photo_url'] = 'Photo URL cannot exceed 250 characters.';
            } else {
                $storedPhotoPath = $photoUrl;
                $storedPhotoName = $photoName !== '' ? $photoName : $title . ' Photo';
            }
        }

        // Validation: Link
        if ($linkUrl !== '') {
            if (mb_strlen($linkUrl) > 250) {
                $errors['link_url'] = 'Link URL cannot exceed 250 characters.';
            } elseif (!filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                $errors['link_url'] = 'Link must be a valid URL (e.g. https://github.com/user/project).';
            }
        }

        if ($linkName !== '') {
            if (mb_strlen($linkName) > 100) {
                $errors['link_name'] = 'Link label cannot exceed 100 characters.';
            }
            if ($linkUrl === '') {
                $errors['link_url'] = 'Link URL is required when a link label is provided.';
            }
        }

        $old = [
            'title'        => $title,
            'started_date' => $startedDate,
            'end_date'     => $endDate,
            'description'  => $description,
            'photo_name'   => $photoName,
            'photo_url'    => $photoUrl,
            'link_name'    => $linkName,
            'link_url'     => $linkUrl,
        ];

        if (!empty($errors)) {
            $projects = $this->repo->all();
            view('features/projects/projectform', [
                'pageTitle'      => 'Add Project',
                'currentPage'    => 'projects',
                'projects'       => $projects,
                'errors'         => $errors,
                'old'            => $old,
                'fromController' => true,
            ]);
            return;
        }

        $project = new Project();
        $project->title       = $title;
        $project->startedDate = $startedDate;
        $project->endDate     = $endDate !== '' ? $endDate : null;
        $project->description = $description !== '' ? $description : null;
        $project->photoPath   = $storedPhotoPath;
        $project->photoName   = $storedPhotoName;
        $project->linkName    = $linkName !== '' ? $linkName : null;
        $project->linkUrl     = $linkUrl !== '' ? $linkUrl : null;

        $this->repo->create($project);

        Flash::set('success', 'Project added successfully!');
        redirect(url('projects'));
    }

    /** POST /projects/delete */
    public function delete(Request $request): void
    {
        Auth::requireLogin();

        $id = (int) ($request->params['id'] ?? $request->input('id', 0));
        if ($id > 0) {
            $this->repo->delete($id);
            Flash::set('success', 'Project deleted successfully.');
        }

        redirect(url('projects'));
    }
}
