<?php
if (!function_exists('url')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
    require_once dirname(__DIR__, 3) . '/src/config/config.php';
}

use App\Models\Link;
use App\Repositories\LinkRepository;
use App\Support\Auth;
use App\Support\Flash;

// 1. Guard check: Only authenticated users can access this page or insert records
Auth::requireLogin();

$repo = new LinkRepository();
$errors = $errors ?? [];
$old    = $old ?? [];

// 2. Direct POST fallback: If form is submitted directly without router controller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($fromController)) {
    // If delete action triggered
    if (isset($_POST['_action']) && $_POST['_action'] === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $repo->delete($deleteId);
            Flash::set('success', 'Link removed successfully.');
            redirect($_SERVER['REQUEST_URI'] ?? url('links'));
        }
    }

    // --- Data Retrieval and Validation ---
    $name = trim((string) ($_POST['name'] ?? ''));
    $link = trim((string) ($_POST['link'] ?? ''));

    // Validation
    if ($name === '') {
        $errors['name'] = 'Link name / platform is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Link name cannot exceed 100 characters.';
    }

    if ($link === '') {
        $errors['link'] = 'Destination URL is required.';
    } elseif (mb_strlen($link) > 250) {
        $errors['link'] = 'URL cannot exceed 250 characters.';
    } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
        $errors['link'] = 'Destination must be a valid URL (e.g. https://github.com/username).';
    }

    if (empty($errors)) {
        $linkObj = new Link();
        $linkObj->name = $name;
        $linkObj->link = $link;

        $repo->create($linkObj);
        Flash::set('success', 'Link added successfully!');
        redirect($_SERVER['REQUEST_URI'] ?? url('links'));
    } else {
        $old = [
            'name' => $name,
            'link' => $link,
        ];
    }
}

// Load current links
$links = $links ?? $repo->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Add Link') ?> | Online Resume</title>
    <link rel="stylesheet" href="<?= css('index') ?>">
    <link rel="stylesheet" href="<?= css('components/Navbar') ?>">
    <link rel="stylesheet" href="<?= css('features/links/linkform') ?>">
</head>
<body>
    <?php view('components/Navbar'); ?>

    <main class="link-page cert-page">
        <div class="link-container cert-container">
            
            <!-- Link Input Form Card (Structure aligned with Certificate and Education forms) -->
            <section class="link-card cert-card">
                <div class="link-card__header cert-card__header">
                    <h1 class="link-card__title cert-card__title">Add Link</h1>
                    <p class="link-card__subtitle cert-card__subtitle">Manage your social profiles, personal websites, and external links</p>
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

                <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? url('links')) ?>" method="POST" class="link-form cert-form">
                    
                    <!-- Link Name / Platform Field -->
                    <div class="form-field">
                        <label for="name" class="form-label">
                            Link Label / Platform Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            maxlength="100"
                            value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                            required 
                            autofocus
                            placeholder="e.g. GitHub, LinkedIn, Personal Portfolio, Behance, X / Twitter"
                            class="form-input <?= isset($errors['name']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['name'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Destination URL Field -->
                    <div class="form-field">
                        <label for="link" class="form-label">
                            Destination URL <span class="required">*</span>
                        </label>
                        <input 
                            type="url" 
                            id="link" 
                            name="link" 
                            maxlength="250"
                            value="<?= htmlspecialchars($old['link'] ?? '') ?>"
                            required 
                            placeholder="https://github.com/yourusername"
                            class="form-input <?= isset($errors['link']) ? 'form-input--error' : '' ?>"
                        >
                        <?php if (!empty($errors['link'])): ?>
                            <p class="form-error"><?= htmlspecialchars($errors['link']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">
                        Save Link Record
                    </button>
                </form>
            </section>

            <!-- Links History / Records List Card -->
            <section class="link-list-card cert-list-card">
                <div class="link-list-header cert-list-header">
                    <h2 class="link-list-title cert-list-title">Recorded Links</h2>
                    <span class="link-count-badge cert-count-badge"><?= count($links) ?> Records</span>
                </div>

                <?php if (empty($links)): ?>
                    <div class="empty-state">
                        <p>No links have been added yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="link-table cert-table">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Platform / Name</th>
                                    <th>Destination URL</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $item): ?>
                                    <tr>
                                        <!-- Platform Name Badge -->
                                        <td>
                                            <span class="badge-platform"><?= htmlspecialchars($item->name) ?></span>
                                        </td>

                                        <!-- Clickable URL -->
                                        <td>
                                            <a href="<?= htmlspecialchars($item->link) ?>" target="_blank" rel="noopener noreferrer" class="link-url-text">
                                                🔗 <?= htmlspecialchars($item->link) ?>
                                            </a>
                                        </td>

                                        <!-- Delete Action -->
                                        <td>
                                            <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? url('links')) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this link?');" style="margin: 0;">
                                                <input type="hidden" name="_action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $item->id ?>">
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
