<?php
if (!function_exists('url')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    require_once dirname(__DIR__, 2) . '/src/config/config.php';
}

use App\Support\Auth;
use App\Support\Flash;

// Already logged in? Redirect to home
if (Auth::check()) {
    redirect(url('/'));
}

$errors = $errors ?? [];
$old    = $old    ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sign In') ?> | Online Resume</title>
    <link rel="stylesheet" href="<?= css('index') ?>">
    <link rel="stylesheet" href="<?= css('auth/login') ?>">
</head>
<body>
    <?php view('components/Navbar'); ?>

    <main class="login-page">
        <div class="login-card">
            
            <div class="login-card__header">
                <h1 class="login-card__title">Sign In</h1>
                <p class="login-card__subtitle">Enter your credentials to continue</p>
            </div>

            <!-- Flash Success Message (e.g. after logout) -->
            <?php if ($successMsg = Flash::get('success')): ?>
                <div class="alert alert--success">
                    <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>

            <!-- Global Error Message -->
            <?php if (!empty($errors['_global'])): ?>
                <div class="alert alert--error">
                    <?= htmlspecialchars($errors['_global']) ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= url('auth/login') ?>" method="POST" class="login-form">
                
                <!-- Email Field -->
                <div class="form-field">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        required 
                        autofocus
                        class="form-input <?= isset($errors['email']) ? 'form-input--error' : '' ?>"
                        placeholder="you@example.com"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password Field -->
                <div class="form-field">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        class="form-input <?= isset($errors['password']) ? 'form-input--error' : '' ?>"
                        placeholder="••••••••"
                    >
                    <?php if (!empty($errors['password'])): ?>
                        <p class="form-error"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">
                    Sign In
                </button>
            </form>

            <!-- Back to Home -->
            <div class="login-card__footer">
                <a href="<?= url('/') ?>" class="login-back-link">
                    &larr; Back to Home
                </a>
            </div>

        </div>
    </main>

</body>
</html>