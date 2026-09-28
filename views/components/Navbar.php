<head>
    <link rel="stylesheet" href="<?= css('components/Navbar') ?>">
</head>
<header class="navbar">
    <a href="#" class="navLogo"> 
        Logo
    </a>

    <?php 
        $navItems = [
            'Home' => '#',
            'About' => '#',
            'Services' => '#',
            'Contact' => '#'
        ];
    ?>
    <nav class="navLinks" id="navLinks">
        <?php foreach ($navItems as $label => $url): ?>
            <a href="<?= htmlspecialchars($url) ?>" class="navLink">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
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