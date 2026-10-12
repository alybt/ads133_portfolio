<?php
if (!function_exists('url')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
    require_once dirname(__DIR__, 3) . '/src/config/config.php';
}

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Support\Auth;
use App\Support\Flash;

// 1. Guard check: Only authenticated users can access this page or insert records
Auth::requireLogin();

$repo = new ProjectRepository();
$errors = $errors ?? [];
$old    = $old ?? [];

// 2. Direct POST fallback: If form is submitted directly without router controller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($fromController)) {
    // If delete action triggered
    if (isset($_POST['_action']) && $_POST['_action'] === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $repo->delete($deleteId);
            Flash::set('success', 'Project removed successfully.');
            redirect($_SERVER['REQUEST_URI']);
        }
    }

    // --- Data Retrieval and Validation ---
    $title       = trim((string) ($_POST['title'] ?? ''));
    $startedDate = trim((string) ($_POST['started_date'] ?? ''));
    $endDate     = trim((string) ($_POST['end_date'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $photoName   = trim((string) ($_POST['photo_name'] ?? ''));
    $photoUrl    = trim((string) ($_POST['photo_url'] ?? ''));
    $linkName    = trim((string) ($_POST['link_name'] ?? ''));
    $linkUrl     = trim((string) ($_POST['link_url'] ?? ''));

    // Validation
    if ($title === '') {
        $errors['title'] = 'Project title is required.';
    } elseif (mb_strlen($title) > 100) {
        $errors['title'] = 'Project title cannot exceed 100 characters.';
    } elseif ($repo->existsByTitle($title)) {
        $errors['title'] = 'A project with this title already exists.';
    }

    if ($startedDate === '') {
        $errors['started_date'] = 'Start date is required.';
    } else {
        $dStart = \DateTime::createFromFormat('Y-m-d', $startedDate);
        if (!$dStart || $dStart->format('Y-m-d') !== $startedDate) {
            $errors['started_date'] = 'Start date must be a valid date (YYYY-MM-DD).';
        }
    }

    if ($endDate !== '') {
        $dEnd = \DateTime::createFromFormat('Y-m-d', $endDate);
        if (!$dEnd || $dEnd->format('Y-m-d') !== $endDate) {
            $errors['end_date'] = 'End date must be a valid date (YYYY-MM-DD).';
        } elseif (empty($errors['started_date']) && $startedDate !== '' && $endDate < $startedDate) {
            $errors['end_date'] = 'End date cannot be earlier than start date.';
        }
    }

    // Photo file upload handling
    $storedPhotoPath = null;
    $storedPhotoName = null;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath  = $_FILES['photo']['tmp_name'];
            $origName = basename($_FILES['photo']['name']);
            $fileSize = $_FILES['photo']['size'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

            if ($fileSize > 5 * 1024 * 1024) {
                $errors['photo'] = 'Uploaded photo must not exceed 5MB.';
            } elseif (!in_array($ext, $allowed, true)) {
                $errors['photo'] = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP, SVG.';
            } else {
                $uploadDir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/public/assets/photos';
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
        if (mb_strlen($photoUrl) > 250) {
            $errors['photo_url'] = 'Photo URL cannot exceed 250 characters.';
        } else {
            $storedPhotoPath = $photoUrl;
            $storedPhotoName = $photoName !== '' ? $photoName : $title . ' Photo';
        }
    }

    // Link validation
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

    if (empty($errors)) {
        $project = new Project();
        $project->title       = $title;
        $project->startedDate = $startedDate;
        $project->endDate     = $endDate !== '' ? $endDate : null;
        $project->description = $description !== '' ? trim($description) : null;
        $project->photoPath   = $storedPhotoPath;
        $project->photoName   = $storedPhotoName;
        $project->linkName    = $linkName !== '' ? $linkName : null;
        $project->linkUrl     = $linkUrl !== '' ? $linkUrl : null;

        $repo->create($project);
        Flash::set('success', 'Project added successfully!');
        redirect($_SERVER['REQUEST_URI'] ?? url('projects'));
    } else {
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
    }
}

// Load current projects
$projects = $projects ?? $repo->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Add Project') ?> | Online Resume</title>
    <link rel="stylesheet" href="<?= css('index') ?>">
    <link rel="stylesheet" href="<?= css('components/Navbar') ?>">
    <link rel="stylesheet" href="<?= css('features/projects/projectform') ?>">
</head>
<body>
    <?php view('components/Navbar'); ?>

    <main class="proj-page cert-page">
        <div class="proj-container cert-container">
            
            <!-- Project Input Form Card (Design aligned with Certificate and Education forms) -->
            <section class="proj-card cert-card">
                <div class="proj-card__header cert-card__header">
                    <h1 class="proj-card__title cert-card__title">Add Project</h1>
                    <p class="proj-card__subtitle cert-card__subtitle">Showcase your portfolio works, applications, and builds</p>
                </div>

                <!-- Flash Success Message -->
                <?php if ($successMsg = Flash::get('success')): ?>
                    <div class="alert alert--success">
                        <span>✓</span>
                        <div><?= htmlspecialchars($successMsg) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Global Error Alert -->
                <?php if (!empty($errors['_global'])): ?>
                    <div class="alert alert--error">
                        <span>⚠</span>
                        <div><?= htmlspecialchars($errors['_global']) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? url('projects')) ?>" method="POST" enctype="multipart/form-data" class="proj-form cert-form">
                    
                    <!-- Project Title Field -->
                    <div class="form-field">
                        <label for="title" class="form-label">
                            Project Title <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            maxlength="100"
                            value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                            required 
                            autofocus
                            placeholder="e.g. Distributed Task Scheduler, E-Commerce Storefront, AI Code Assistant"
                            class="form-input <?= isset($errors['title']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['title'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['title']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Timeline Grid (Started Date and End Date) -->
                    <div class="proj-form-grid cert-form-grid">
                        
                        <!-- Started Date Field -->
                        <div class="form-field">
                            <label for="started_date" class="form-label">
                                Started Date <span class="required">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="started_date" 
                                name="started_date" 
                                value="<?= htmlspecialchars($old['started_date'] ?? '') ?>"
                                required 
                                class="form-input <?= isset($errors['started_date']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['started_date'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['started_date']) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- End Date Field (Optional) -->
                        <div class="form-field">
                            <label for="end_date" class="form-label">
                                End Date / Completion (Optional)
                            </label>
                            <input 
                                type="date" 
                                id="end_date" 
                                name="end_date" 
                                value="<?= htmlspecialchars($old['end_date'] ?? '') ?>"
                                class="form-input <?= isset($errors['end_date']) ? 'form-input--error' : '' ?>"
                            >
                            <span class="form-hint">Leave blank if currently ongoing or in active development</span>
                            <?php if (!empty($errors['end_date'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['end_date']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description Field -->
                    <div class="form-field">
                        <label for="description" class="form-label">
                            Project Description & Tech Stack (Optional)
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="3"
                            placeholder="Brief description of the project, features built, key responsibilities, or technologies used..."
                            class="form-textarea <?= isset($errors['description']) ? 'form-textarea--error' : '' ?>"
                        ><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                        <?php if (!empty($errors['description'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['description']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Photo Upload & Label Section -->
                    <div class="form-field">
                        <label class="form-label">
                            Project Screenshot / Thumbnail (Optional)
                        </label>
                        <div class="file-upload-box" id="fileUploadBox">
                            <input 
                                type="file" 
                                id="photo" 
                                name="photo" 
                                accept="image/*"
                                class="file-upload-input"
                                onchange="previewProjectPhoto(this)"
                            >
                            <div class="file-upload-content">
                                <span class="file-upload-icon">📷</span>
                                <span class="file-upload-text" id="uploadText">Click or drag & drop image to upload</span>
                                <span class="file-upload-subtext">Supports JPG, PNG, WEBP, GIF, SVG up to 5MB</span>
                            </div>
                            <img id="photoPreview" class="file-upload-preview" alt="Preview">
                        </div>
                        <?php if (!empty($errors['photo'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['photo']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Optional Photo URL / Caption Grid -->
                    <div class="proj-form-grid cert-form-grid">
                        <div class="form-field">
                            <label for="photo_name" class="form-label">
                                Photo Label / Caption (Optional)
                            </label>
                            <input 
                                type="text" 
                                id="photo_name" 
                                name="photo_name" 
                                maxlength="250"
                                value="<?= htmlspecialchars($old['photo_name'] ?? '') ?>"
                                placeholder="e.g. Dashboard View, Main UI, Architecture Diagram"
                                class="form-input"
                            >
                        </div>
                        <div class="form-field">
                            <label for="photo_url" class="form-label">
                                Or Image URL (Optional)
                            </label>
                            <input 
                                type="url" 
                                id="photo_url" 
                                name="photo_url" 
                                maxlength="250"
                                value="<?= htmlspecialchars($old['photo_url'] ?? '') ?>"
                                placeholder="https://example.com/screenshot.png"
                                class="form-input <?= isset($errors['photo_url']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['photo_url'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['photo_url']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Link Section (Referencing links table) -->
                    <div class="proj-form-grid cert-form-grid">
                        <div class="form-field">
                            <label for="link_url" class="form-label">
                                Project Link / URL (Optional)
                            </label>
                            <input 
                                type="url" 
                                id="link_url" 
                                name="link_url" 
                                maxlength="250"
                                value="<?= htmlspecialchars($old['link_url'] ?? '') ?>"
                                placeholder="https://github.com/username/project or https://demo.app"
                                class="form-input <?= isset($errors['link_url']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['link_url'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['link_url']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="link_name" class="form-label">
                                Link Label / Name (Optional)
                            </label>
                            <input 
                                type="text" 
                                id="link_name" 
                                name="link_name" 
                                maxlength="100"
                                value="<?= htmlspecialchars($old['link_name'] ?? '') ?>"
                                placeholder="e.g. GitHub Repository, Live Demo, Website"
                                class="form-input <?= isset($errors['link_name']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['link_name'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['link_name']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">
                        Save Project Record
                    </button>
                </form>
            </section>

            <!-- Project History / Records List Card -->
            <section class="proj-list-card cert-list-card">
                <div class="proj-list-header cert-list-header">
                    <h2 class="proj-list-title cert-list-title">Recorded Projects</h2>
                    <span class="proj-count-badge cert-count-badge"><?= count($projects) ?> Records</span>
                </div>

                <?php if (empty($projects)): ?>
                    <div class="empty-state">
                        <p>No projects have been added yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="proj-table cert-table">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Thumbnail</th>
                                    <th>Project Details</th>
                                    <th>Timeline</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $proj): ?>
                                    <tr>
                                        <!-- Photo Thumbnail -->
                                        <td>
                                            <?php if (!empty($proj->photoPath)): ?>
                                                <?php 
                                                    $imgSrc = filter_var($proj->photoPath, FILTER_VALIDATE_URL)
                                                        ? $proj->photoPath 
                                                        : url($proj->photoPath);
                                                ?>
                                                <a href="<?= htmlspecialchars($imgSrc) ?>" target="_blank" rel="noopener">
                                                    <img 
                                                        src="<?= htmlspecialchars($imgSrc) ?>" 
                                                        alt="<?= htmlspecialchars($proj->photoName ?? $proj->title) ?>"
                                                        class="project-thumb"
                                                        title="<?= htmlspecialchars($proj->photoName ?? 'Click to view full size') ?>"
                                                    >
                                                </a>
                                            <?php else: ?>
                                                <div class="project-no-thumb">No Img</div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Project Title, Description & Link -->
                                        <td>
                                            <strong><?= htmlspecialchars($proj->title) ?></strong>
                                            <?php if (!empty($proj->photoName) && !empty($proj->photoPath)): ?>
                                                <span class="badge-company" style="margin-left: 0.5rem; font-size: 0.7rem;">
                                                    <?= htmlspecialchars($proj->photoName) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($proj->description)): ?>
                                                <div class="proj-desc-text cert-desc-text"><?= nl2br(htmlspecialchars($proj->description)) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($proj->linkUrl)): ?>
                                                <div style="margin-top: 0.45rem;">
                                                    <a href="<?= htmlspecialchars($proj->linkUrl) ?>" target="_blank" rel="noopener noreferrer" class="project-link-badge">
                                                        <span>🔗</span> <?= htmlspecialchars(!empty($proj->linkName) ? $proj->linkName : 'Visit Project') ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Timeline -->
                                        <td>
                                            <span class="badge-timeline <?= empty($proj->endDate) ? 'badge-present' : '' ?>">
                                                <?= htmlspecialchars($proj->startedDate ?? '') ?>
                                                &rarr;
                                                <?= !empty($proj->endDate) ? htmlspecialchars($proj->endDate) : 'Present' ?>
                                            </span>
                                        </td>

                                        <!-- Action -->
                                        <td>
                                            <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? url('projects')) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');" style="margin: 0;">
                                                <input type="hidden" name="_action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $proj->id ?>">
                                                <button type="submit" class="btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>

    <script>
        function previewProjectPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const uploadText = document.getElementById('uploadText');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (uploadText) {
                        uploadText.textContent = input.files[0].name;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                preview.src = '';
                if (uploadText) {
                    uploadText.textContent = 'Click or drag & drop image to upload';
                }
            }
        }
    </script>
</body>
</html>
