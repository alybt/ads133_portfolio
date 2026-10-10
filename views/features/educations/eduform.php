<?php
if (!function_exists('url')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
    require_once dirname(__DIR__, 3) . '/src/config/config.php';
}

use App\Models\Education;
use App\Repositories\EducationRepository;
use App\Support\Auth;
use App\Support\Flash;

// 1. Guard check: Only authenticated users can access this page or insert records
Auth::requireLogin();

$repo = new EducationRepository();
$errors = $errors ?? [];
$old    = $old ?? [];

// 2. Direct POST fallback: If form is submitted directly without router controller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($fromController)) {
    // If delete action triggered
    if (isset($_POST['_action']) && $_POST['_action'] === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $repo->delete($deleteId);
            Flash::set('success', 'Education record removed.');
            redirect($_SERVER['REQUEST_URI']);
        }
    }

    $schoolName  = trim((string) ($_POST['school_name'] ?? ''));
    $address     = trim((string) ($_POST['address'] ?? ''));
    $grades      = trim((string) ($_POST['grades'] ?? ''));
    $startedDate = trim((string) ($_POST['started_date'] ?? ''));
    $endDate     = trim((string) ($_POST['end_date'] ?? ''));

    // Validation
    if ($schoolName === '') {
        $errors['school_name'] = 'School name is required.';
    } elseif (mb_strlen($schoolName) > 250) {
        $errors['school_name'] = 'School name cannot exceed 250 characters.';
    } elseif ($repo->existsBySchoolName($schoolName)) {
        $errors['school_name'] = 'This school name already exists.';
    }

    if ($address === '') {
        $errors['address'] = 'Address is required.';
    } elseif (mb_strlen($address) > 250) {
        $errors['address'] = 'Address cannot exceed 250 characters.';
    }

    if ($grades !== '') {
        if (!is_numeric($grades)) {
            $errors['grades'] = 'Grades must be a valid number.';
        } elseif ((float) $grades < 0) {
            $errors['grades'] = 'Grades cannot be negative.';
        }
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

    if (empty($errors)) {
        $edu = new Education();
        $edu->schoolName  = $schoolName;
        $edu->address     = $address;
        $edu->grades      = $grades !== '' ? $grades : null;
        $edu->startedDate = $startedDate;
        $edu->endDate     = $endDate !== '' ? $endDate : null;

        $repo->create($edu);
        Flash::set('success', 'Education record added successfully!');
        redirect($_SERVER['REQUEST_URI']);
    } else {
        $old = [
            'school_name'  => $schoolName,
            'address'      => $address,
            'grades'       => $grades,
            'started_date' => $startedDate,
            'end_date'     => $endDate,
        ];
    }
}

// Load current educations
$educations = $educations ?? $repo->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Add Education') ?> | Online Resume</title>
    <link rel="stylesheet" href="<?= css('index') ?>">
    <link rel="stylesheet" href="<?= css('components/Navbar') ?>">
    <link rel="stylesheet" href="<?= css('features/educations/eduform') ?>">
</head>
<body>
    <?php view('components/Navbar'); ?>

    <main class="edu-page">
        <div class="edu-container">
            
            <!-- Education Input Form Card -->
            <section class="edu-card">
                <div class="edu-card__header">
                    <h1 class="edu-card__title">Add Education</h1>
                    <p class="edu-card__subtitle">Record your academic background & credentials</p>
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

                <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" class="edu-form">
                    
                    <!-- School Name Field -->
                    <div class="form-field">
                        <label for="school_name" class="form-label">
                            School / University Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="school_name" 
                            name="school_name" 
                            maxlength="250"
                            value="<?= htmlspecialchars($old['school_name'] ?? '') ?>"
                            required 
                            autofocus
                            placeholder="e.g. University of California, Berkeley"
                            class="form-input <?= isset($errors['school_name']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['school_name'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['school_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Address Field -->
                    <div class="form-field">
                        <label for="address" class="form-label">
                            Address / Campus Location <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            maxlength="250"
                            value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                            required 
                            placeholder="e.g. Berkeley, California, USA"
                            class="form-input <?= isset($errors['address']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['address'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['address']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Grades and Start/End Date Grid -->
                    <div class="edu-form-grid">
                        
                        <!-- Grades Field -->
                        <div class="form-field">
                            <label for="grades" class="form-label">
                                Grades / GPA / Percentage
                            </label>
                            <input 
                                type="number" 
                                step="any"
                                min="0"
                                id="grades" 
                                name="grades" 
                                value="<?= htmlspecialchars($old['grades'] ?? '') ?>"
                                placeholder="e.g. 3.85 or 95"
                                class="form-input <?= isset($errors['grades']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['grades'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['grades']) ?></p>
                            <?php endif; ?>
                        </div>

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

                    </div>

                    <!-- End Date Field -->
                    <div class="form-field">
                        <label for="end_date" class="form-label">
                            End Date / Expected Graduation (Optional)
                        </label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            value="<?= htmlspecialchars($old['end_date'] ?? '') ?>"
                            class="form-input <?= isset($errors['end_date']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['end_date'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['end_date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">
                        Save Education Record
                    </button>
                </form>
            </section>

            <!-- Education History / Records List Card -->
            <section class="edu-list-card">
                <div class="edu-list-header">
                    <h2 class="edu-list-title">Recorded Educations</h2>
                    <span class="edu-count-badge"><?= count($educations) ?> Records</span>
                </div>

                <?php if (empty($educations)): ?>
                    <div class="empty-state">
                        <p>No education records have been added yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="edu-table">
                            <thead>
                                <tr>
                                    <th>School Name</th>
                                    <th>Address</th>
                                    <th>Grades</th>
                                    <th>Timeline</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($educations as $edu): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($edu->schoolName) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($edu->address ?? '—') ?></td>
                                        <td>
                                            <?php if ($edu->grades !== null && $edu->grades !== ''): ?>
                                                <span class="badge-grade"><?= htmlspecialchars($edu->grades) ?></span>
                                            <?php else: ?>
                                                <span style="color: var(--grey-mid);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($edu->startedDate ?? '') ?>
                                            &rarr;
                                            <?= htmlspecialchars($edu->endDate ?: 'Present') ?>
                                        </td>
                                        <td>
                                            <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this education entry?');" style="margin: 0;">
                                                <input type="hidden" name="_action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $edu->id ?>">
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

</body>
</html>
