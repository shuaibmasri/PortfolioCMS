<?php

declare(strict_types=1);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= escape(url()) ?>">
            Portfolio CMS
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div id="mainNavbar" class="collapse navbar-collapse">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= escape(url('#home')) ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= escape(url('#about')) ?>">About</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= escape(url('#experience')) ?>">Experience</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= escape(url('#projects')) ?>">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= escape(url('#certificates')) ?>">Certificates</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= escape(url('#contact')) ?>">Contact</a></li>
            </ul>

            <a class="btn btn-primary px-4" href="<?= escape(url('resume.php?download=1')) ?>" download>
                <i class="fa-solid fa-download me-2"></i>Download CV
            </a>
        </div>
    </div>
</nav>
