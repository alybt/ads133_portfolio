<?php
if (!function_exists('url')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
    require_once dirname(__DIR__, 3) . '/src/config/config.php';
}

use App\Models\Certificate;
use App\Repositories\CertificateRepository;
use App\Support\Auth;
use App\Support\Flash;

// 1. Guard check: Only authenticated users can access this page or insert records
Auth::requireLogin();

$repo = new CertificateRepository();
$errors = $errors ?? [];
$old    = $old ?? [];

// 2. Direct POST fallback: If form is submitted directly without router controller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($fromController)) {
    // If delete action triggered
    if (isset($_POST['_action']) && $_POST['_action'] === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $repo->delete($deleteId);
            Flash::set('success', 'Certificate removed successfully.');
            redirect($_SERVER['REQUEST_URI']);
        }
    }

    // --- Data Retrieval and Validation ---
    $title        = trim((string) ($_POST['title'] ?? ''));
    $company      = trim((string) ($_POST['company'] ?? ''));
    $receivedDate = trim((string) ($_POST['received_date'] ?? ''));
    $description  = trim((string) ($_POST['description'] ?? ''));

    // Validation
    if ($title === '') {
        $errors['title'] = 'Certificate title is required.';
    } elseif (mb_strlen($title) > 250) {
        $errors['title'] = 'Title cannot exceed 250 characters.';
    } elseif ($repo->existsByTitle($title)) {
        $errors['title'] = 'A certificate with this title already exists.';
    }

    if ($company === '') {
        $errors['company'] = 'Issuing organization / company is required.';
    } elseif (mb_strlen($company) > 100) {
        $errors['company'] = 'Company name cannot exceed 100 characters.';
    }

    if ($receivedDate === '') {
        $errors['received_date'] = 'Date received is required.';
    } else {
        $d = \DateTime::createFromFormat('Y-m-d', $receivedDate);
        if (!$d || $d->format('Y-m-d') !== $receivedDate) {
            $errors['received_date'] = 'Date received must be a valid date (YYYY-MM-DD).';
        }
    }

    if (empty($errors)) {
        $cert = new Certificate();
        $cert->title        = $title;
        $cert->company      = $company;
        $cert->receivedDate = $receivedDate;
        // Description is optional, handle null explicitly
        $cert->description  = ($description !== '') ? trim($description) : null;

        $repo->create($cert);
        Flash::set('success', 'Certificate added successfully!');
        redirect($_SERVER['REQUEST_URI']);
    } else {
        // Populate old data for repopulation in case of validation failure
        $old = [
            'title'         => $title,
            'company'       => $company,
            'received_date' => $receivedDate,
            'description'   => $description,
        ];
    }
}

// Load current certificates
$certificates = $certificates ?? $repo->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Add Certificate') ?> | Online Resume</title>
    <link rel="stylesheet" href="<?= css('index') ?>">
    <link rel="stylesheet" href="<?= css('components/Navbar') ?>">
    <!-- Using a general style sheet path for consistency -->
    <link rel="stylesheet" href="<?= css('features/certificates/certform') ?>"> 
</head>
<body>
    <?php view('components/Navbar'); ?>

    <main class="cert-page">
        <div class="cert-container">
            
            <!-- Certificate Input Form Card (Structure aligned with Education form) -->
            <section class="cert-card">
                <div class="cert-card__header">
                    <h1 class="cert-card__title">Add Certificate</h1>
                    <p class="cert-card__subtitle">Record your credentials, licenses, and achievements</p>
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

                <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" class="cert-form edu-form">
                    
                    <!-- Certificate Title Field (Mapped from School Name) -->
                    <div class="form-field">
                        <label for="title" class="form-label">
                            Certificate Title <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            maxlength="250"
                            value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                            required 
                            autofocus
                            placeholder="e.g. AWS Certified Solutions Architect, PMP Certification"
                            class="form-input <?= isset($errors['title']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['title'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['title']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Company/Issuer and Date Grid (Mapped from Address + Start Date) -->
                    <div class="cert-form-grid edu-form-grid">
                        
                        <!-- Issuing Organization / Company Field (Mapped from Address) -->
                        <div class="form-field">
                            <label for="company" class="form-label">
                                Issuing Organization / Company <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="company" 
                                name="company" 
                                maxlength="100"
                                value="<?= htmlspecialchars($old['company'] ?? '') ?>"
                                required 
                                placeholder="e.g. Amazon Web Services, Google, PMI"
                                class="form-input <?= isset($errors['company']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['company'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['company']) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Received Date Field (Mapped from Started Date, single point in time) -->
                        <div class="form-field">
                            <label for="received_date" class="form-label">
                                Date Awarded <span class="required">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="received_date" 
                                name="received_date" 
                                value="<?= htmlspecialchars($old['received_date'] ?? '') ?>"
                                required 
                                class="form-input <?= isset($errors['received_date']) ? 'form-input--error' : '' ?>"
                            >
                            <?php if (!empty($errors['received_date'])): ?>
                                <p class="form-error"><?= htmlspecialchars($errors['received_date']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description Field (Mapped from general description area) -->
                    <div class="form-field">
                        <label for="description" class="form-label">
                            Description / Key Learnings (Optional)
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="3"
                            placeholder="Brief description of the certification, skills verified, or credential details..."
                            class="form-textarea <?= isset($errors['description']) ? 'form-textarea--error' : '' ?>"
                        ><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">
                        Save Certificate Record
                    </button>
                </form>
            </section>

            <!-- Certificate History / Records List Card (Structure aligned with Education list) -->
            <section class="cert-list-card">
                <div class="cert-list-header">
                    <h2 class="cert-list-title">Recorded Certificates</h2>
                    <span class="cert-count-badge edu-count-badge"><?= count($certificates) ?> Records</span>
                </div>

                <?php if (empty($certificates)): ?>
                    <div class="empty-state">
                        <p>No certificates have been added yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="cert-table edu-table cert-table-override">
                            <thead>
                                <tr>
                                    <th>Certificate Title</th>
                                    <th>Issuing Organization</th>
                                    <th>Date Awarded</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $cert): ?>
                                    <tr class="edu-table-row">
                                        <td>
                                            <strong><?= htmlspecialchars($cert->title) ?></strong>
                                            <?php if (!empty($cert->description)): ?>
                                                <div class="cert-desc-text desc-text"><?= nl2br(htmlspecialchars($cert->description)) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-company"><?= htmlspecialchars($cert->company ?? '—') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($cert->receivedDate ?? '—') ?></td>
                                        <td>
                                            <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this certificate?');" style="margin: 0;">
                                                <input type="hidden" name="_action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $cert->id ?>">
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