<?php
    use App\Repositories\UserRepository;
    use App\Services\UserService;

    $userService = new UserService(new UserRepository());
    $user = $userService->get(1);

?>
<head>
    <link rel="stylesheet" href="<?= css('user/homepage') ?>">
</head>
<div class="homepages">
    <?php if ($user): ?>
    <h1 class="userName"><?= htmlspecialchars($user->fullName()) ?></h1>
    <?php else: ?>
        <p>User not found.</p>
    <?php endif; ?>
</div>