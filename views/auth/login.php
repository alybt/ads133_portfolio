<?php
    use App\Support\Auth;
    use App\Support\Flash;

    // Already signed in? Send them home.
    if (Auth::check()) {
        redirect(url('/'));
    }

    $errors = $errors ?? [];
    $old    = $old    ?? [];
?>
<head>
    <link rel="stylesheet" href="<?= css('auth/login') ?>">
</head>

<div class="login-page">
    <div class="login-card">
        <h1 class="login-card__title">Sign in</h1>

        <?php if ($msg = Flash::get('success')): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['_global'])): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($errors['_global']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('login') ?>" class="login-form">
            <div class="form-field">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    autocomplete="username"
                    autofocus
                    required
                >
                <?php if (!empty($errors['email'])): ?>
                    <p class="form-error"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
                <?php if (!empty($errors['password'])): ?>
                    <p class="form-error"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn--primary">Sign in</button>
        </form>
    </div>
</div>