<?php

declare(strict_types=1);

$publicBrand = isset($settings['website_name']) && trim((string) $settings['website_name']) !== ''
    ? trim((string) $settings['website_name'])
    : APP_NAME;
?>
<header class="public-navbar-wrap">
    <nav class="navbar navbar-expand-lg navbar-dark public-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= escape(url()) ?>">
                <span class="navbar-brand__mark"><?= escape(strtoupper(substr($publicBrand, 0, 1))) ?></span>
                <span><?= escape($publicBrand) ?></span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#publicNavbar"
                aria-controls="publicNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div id="publicNavbar" class="collapse navbar-collapse">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#experience">Experience</a></li>
                    <li class="nav-item"><a class="nav-link" href="#education">Education</a></li>
                    <li class="nav-item"><a class="nav-link" href="#certificates">Certificates</a></li>
                    <li class="nav-item"><a class="nav-link" href="#skills">Skills</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects">Projects</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>

                <div class="d-flex">
                    <a class="btn btn-light btn-sm px-3" href="<?= escape(url('resume.php?download=1')) ?>" download>
                        <i class="fa fa-download me-2"></i>Download CV
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>
