<?php

declare(strict_types=1);
?>
<footer class="bg-dark text-white py-4 mt-auto">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-4 text-center text-md-start">
                <small>&copy; <?= date('Y') ?> <?= escape(APP_NAME) ?>. All rights reserved.</small>
            </div>

            <div class="col-md-4 text-center">
                <a class="text-white fs-5 mx-2" href="#" aria-label="GitHub">
                    <i class="fa-brands fa-github"></i>
                </a>
                <a class="text-white fs-5 mx-2" href="#" aria-label="LinkedIn">
                    <i class="fa-brands fa-linkedin"></i>
                </a>
                <a class="text-white fs-5 mx-2" href="#" aria-label="Facebook">
                    <i class="fa-brands fa-facebook"></i>
                </a>
            </div>

            <div class="col-md-4 text-center text-md-end">
                <button
                    class="btn btn-outline-light btn-sm"
                    type="button"
                    aria-label="Back to top"
                    onclick="window.scrollTo({ top: 0, behavior: 'smooth' });"
                >
                    <i class="fa-solid fa-arrow-up me-1"></i>Back to top
                </button>
            </div>
        </div>
    </div>
</footer>

<script src="<?= escape(asset('lib/jquery/dist/jquery.min.js')) ?>"></script>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
<script src="<?= escape(asset('js/script.js')) ?>"></script>
<script src="<?= escape(asset('js/dashboard.js')) ?>"></script>
</body>
</html>
