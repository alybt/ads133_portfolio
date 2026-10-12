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
            'Home'     => url('/'),
            'About'    => '#',
            'Services' => '#',
            'Contact'  => '#',
        ];

        $toolItems = [];
        if (Auth::check()) {
            $toolItems = [
                'Education'    => url('educations'),
                'Certificates' => url('certificates'),
                'Projects'     => url('projects'),
                'Links'        => url('links'),
            ];
        }
    ?>
    <nav class="navLinks" id="navLinks">
        <?php foreach ($navItems as $label => $targetUrl): ?>
            <a href="<?= htmlspecialchars($targetUrl) ?>" class="navLink">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>

        <?php if (Auth::check()): ?>
            <div class="navDropdown" id="navToolsDropdown">
                <button type="button" class="navLink navDropdownBtn" id="navToolsBtn" aria-haspopup="true" aria-expanded="false">
                    Tools <span class="navDropdownArrow">▾</span>
                </button>
                <div class="navDropdownMenu" id="navToolsMenu">
                    <?php foreach ($toolItems as $toolLabel => $toolUrl): ?>
                        <a href="<?= htmlspecialchars($toolUrl) ?>" class="navDropdownItem">
                            <?= htmlspecialchars($toolLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

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
        const navHamburger     = document.getElementById('navHamburger');
        const navLinks         = document.getElementById('navLinks');
        const navToolsDropdown = document.getElementById('navToolsDropdown');
        const navToolsBtn      = document.getElementById('navToolsBtn');

        if (navHamburger && navLinks) {
            navHamburger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = navLinks.classList.toggle('open');
                navHamburger.classList.toggle('active');
                navHamburger.setAttribute('aria-expanded', isOpen);
            });
        }

        if (navToolsBtn && navToolsDropdown) {
            navToolsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const isOpen = navToolsDropdown.classList.toggle('open');
                navToolsBtn.setAttribute('aria-expanded', isOpen);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!navToolsDropdown.contains(e.target)) {
                    navToolsDropdown.classList.remove('open');
                    navToolsBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
</script>