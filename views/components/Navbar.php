<?php 
use App\Support\Auth;
?>
<head>
    <link rel="stylesheet" href="<?= css('components/Navbar') ?>">
</head>
<header class="navbar">
    <a href="<?= url('/') ?>" class="navLogo"> 
        Logo
    </a>

    <?php 
        $navItems = [
            'Home' => url('/'),
            'About' => '#',
            'Services' => '#',
            'Contact' => '#',
        ];
    ?>
    <nav class="navLinks" id="navLinks">
        <?php foreach ($navItems as $label => $targetUrl): ?>
            <a href="<?= htmlspecialchars($targetUrl) ?>" class="navLink">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>

        <?php if (Auth::check()): ?>
            <form action="<?= url('auth/logout') ?>" method="POST" style="display: inline; margin: 0;">
                <button type="submit" class="navLink navLink--acid">
                    Sign Out
                </button>
            </form>
        <?php else: ?>
            <a href="<?= url('auth/login') ?>" class="navLink navLink--acid">
                Sign In
            </a>
        <?php endif; ?>
    </nav>

    <button class="navHamburger" id="navHamburger" aria-label="Toggle Menu" aria-expanded="false">
        <span class="navBurgerLine"></span>
        <span class="navBurgerLine"></span>
        <span class="navBurgerLine"></span>
    </button>
</header>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const navHamburger = document.getElementById('navHamburger');
        const navLinks     = document.getElementById('navLinks');

        if (navHamburger && navLinks) {
            navHamburger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = navLinks.classList.toggle('open');
                navHamburger.classList.toggle('active');
                navHamburger.setAttribute('aria-expanded', isOpen);
            });
        }
    });
</script>