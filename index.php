<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/project-cover.php';

$profile = [
    'first_name' => '',
    'last_name' => '',
    'professional_title' => '',
    'biography' => '',
    'professional_summary' => '',
    'email' => '',
    'phone' => '',
    'location' => '',
    'profile_image_path' => '',
    'years_of_experience' => '',
    'current_position' => '',
    'current_company' => '',
];

$settings = [
    'website_name' => 'Portfolio CMS',
    'website_tagline' => 'Professional portfolio and project showcase',
    'website_description' => 'A modern portfolio management system for showcasing skills, projects, experience, and contact details.',
    'footer_text' => 'Built with Portfolio CMS',
    'copyright_text' => 'Copyright ' . date('Y') . ' Portfolio CMS. All rights reserved.',
    'contact_email' => '',
    'contact_phone' => '',
    'address' => '',
    'google_maps_url' => '',
    'meta_title' => 'Portfolio CMS',
    'meta_description' => 'Professional portfolio and project showcase.',
    'meta_keywords' => '',
    'robots' => 'index,follow',
    'canonical_url' => url(),
    'default_language' => 'en',
    'linkedin_url' => '',
    'github_url' => '',
    'facebook_url' => '',
    'x_url' => '',
    'youtube_url' => '',
];

try {
    $statement = $pdo->query('SELECT setting_key, setting_value FROM website_settings');
    foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
        $key = (string) ($row['setting_key'] ?? '');
        if (array_key_exists($key, $settings)) {
            $settings[$key] = (string) ($row['setting_value'] ?? '');
        }
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
}

try {
    trackPublicVisit($pdo, parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
}

try {
    $statement = $pdo->query('SELECT * FROM profiles WHERE is_public = 1 ORDER BY profile_id LIMIT 1');
    $profile = $statement ? ($statement->fetch(PDO::FETCH_ASSOC) ?: $profile) : $profile;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
}

try {
    $statement = $pdo->query(
        'SELECT s.skill_id, s.name AS skill_name, s.proficiency_level, s.display_order, c.name AS category_name
         FROM skills s
         INNER JOIN skill_categories c ON c.skill_category_id = s.skill_category_id
         WHERE s.is_public = 1
         ORDER BY s.display_order ASC, s.name ASC, s.skill_id ASC'
    );
    $skills = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $skills = [];
}

$profileId = (int) ($profile['profile_id'] ?? 0);
$experiences = [];
$educations = [];
$certificates = [];
$projects = [];
if ($profileId > 0) {
    try {
        $statement = $pdo->prepare(
            'SELECT work_experience_id, employer_name, job_title, employment_type, location, start_date, end_date, is_current, description, display_order
             FROM work_experiences
             WHERE profile_id = :profile_id AND is_public = 1
             ORDER BY display_order ASC, start_date DESC, work_experience_id DESC'
        );
        $statement->execute([':profile_id' => $profileId]);
        $experiences = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $experiences = [];
    }

    try {
        $statement = $pdo->prepare(
            'SELECT education_id, institution_name, degree, field_of_study, location, start_date, end_date, grade, description, display_order
             FROM educations
             WHERE profile_id = :profile_id AND is_public = 1
             ORDER BY display_order ASC, end_date DESC, start_date DESC, education_id DESC'
        );
        $statement->execute([':profile_id' => $profileId]);
        $educations = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $educations = [];
    }

    try {
        $statement = $pdo->prepare(
            'SELECT certification_id, name, issuing_organization, credential_url, issued_date, expiry_date, certificate_image_path, certificate_file_path, display_order
             FROM certifications
             WHERE profile_id = :profile_id AND is_public = 1
             ORDER BY display_order ASC, issued_date DESC, certification_id DESC'
        );
        $statement->execute([':profile_id' => $profileId]);
        $certificates = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $certificates = [];
    }
}

foreach ($certificates as &$certificate) {
    $certificate['image_url'] = trim((string) ($certificate['certificate_image_path'] ?? '')) !== '' ? url((string) $certificate['certificate_image_path']) : '';
    $certificate['pdf_url'] = trim((string) ($certificate['certificate_file_path'] ?? '')) !== '' ? url((string) $certificate['certificate_file_path']) : '';
    $certificate['issue_date_label'] = !empty($certificate['issued_date']) ? date('M j, Y', strtotime((string) $certificate['issued_date'])) : '';
    $certificate['expiry_date_label'] = !empty($certificate['expiry_date']) ? date('M j, Y', strtotime((string) $certificate['expiry_date'])) : '';
}
unset($certificate);

try {
    $statement = $pdo->query(
        'SELECT p.project_id,
                p.title,
                p.short_description,
                p.description,
                p.repository_url,
                p.project_url,
                p.status,
                p.display_order,
                c.name AS category_name,
                (
                    SELECT pi.image_path
                    FROM project_images pi
                    WHERE pi.project_id = p.project_id
                    ORDER BY pi.is_cover_image DESC, pi.display_order ASC, pi.project_image_id ASC
                    LIMIT 1
                ) AS image_path,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR "||")
                    FROM project_technologies pt
                    INNER JOIN technologies t ON t.technology_id = pt.technology_id
                    WHERE pt.project_id = p.project_id
                ) AS technology_names
         FROM projects p
         LEFT JOIN project_categories c ON c.project_category_id = p.project_category_id
         WHERE p.is_public = 1
         ORDER BY p.display_order ASC, p.title ASC, p.project_id DESC'
    );
    $projects = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($projects as &$project) {
        $project['image_url'] = trim((string) ($project['image_path'] ?? '')) !== '' ? url((string) $project['image_path']) : '';
        $rawTechnologies = trim((string) ($project['technology_names'] ?? ''));
        $project['technologies'] = $rawTechnologies !== '' ? array_values(array_filter(array_map('trim', explode('||', $rawTechnologies)))) : [];
        switch ((string) ($project['status'] ?? 'planned')) {
            case 'in_progress':
                $project['status_label'] = 'In Progress';
                break;
            case 'completed':
                $project['status_label'] = 'Completed';
                break;
            case 'archived':
                $project['status_label'] = 'Archived';
                break;
            default:
                $project['status_label'] = 'Planned';
                break;
        }
    }
    unset($project);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $projects = [];
}

$projectsJson = json_encode($projects, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if ($projectsJson === false) {
    $projectsJson = '[]';
}

$certificatesJson = json_encode($certificates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if ($certificatesJson === false) {
    $certificatesJson = '[]';
}

$fullName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
$fullName = $fullName !== '' ? $fullName : (string) $settings['website_name'];
$professionalTitle = trim((string) ($profile['professional_title'] ?? ''));
$professionalTitle = $professionalTitle !== '' ? $professionalTitle : (string) $settings['website_tagline'];
$summary = trim((string) ($profile['professional_summary'] ?? ''));
$summary = $summary !== '' ? $summary : (string) $settings['website_description'];
$biography = trim((string) ($profile['biography'] ?? ''));
$biography = $biography !== '' ? $biography : 'This portfolio is ready to showcase experience, work history, and future achievements.';
$profileImage = trim((string) ($profile['profile_image_path'] ?? ''));
$heroPortraitFallback = 'assets/images/shuaib-al-masri-hero.png';
$location = trim((string) ($profile['location'] ?? ''));
$yearsOfExperience = (int) ($profile['years_of_experience'] ?? 0);
$yearsOfExperienceLabel = $yearsOfExperience > 0 ? (string) $yearsOfExperience : '0+';
$currentPosition = trim((string) ($profile['current_position'] ?? ''));
$currentPosition = $currentPosition !== '' ? $currentPosition : 'Open to opportunities';
$currentCompany = trim((string) ($profile['current_company'] ?? ''));
$currentCompany = $currentCompany !== '' ? $currentCompany : 'Independent';
$contactEmail = trim((string) $settings['contact_email']);
$contactPhone = trim((string) $settings['contact_phone']);
$contactAddress = trim((string) $settings['address']);
$contactMapsUrl = trim((string) $settings['google_maps_url']);
$downloadCvUrl = url('download-cv.php');
$emailSubject = rawurlencode('Portfolio enquiry for ' . $fullName);
$contactHref = $contactEmail !== '' ? 'mailto:' . rawurlencode($contactEmail) . '?subject=' . $emailSubject : '#contact';
$pageTitle = $fullName . ' - ' . $professionalTitle;
$pageDescription = $summary;
$pageKeywords = trim((string) $settings['meta_keywords']);
$canonicalUrl = trim((string) $settings['canonical_url']) !== '' ? (string) $settings['canonical_url'] : url();
$htmlLanguage = preg_match('/^[A-Za-z0-9_-]{2,10}$/', (string) $settings['default_language']) === 1 ? (string) $settings['default_language'] : 'en';
$socialLinks = [
    ['label' => 'GitHub', 'url' => trim((string) $settings['github_url']), 'icon' => 'fa-github'],
    ['label' => 'LinkedIn', 'url' => trim((string) $settings['linkedin_url']), 'icon' => 'fa-linkedin'],
    ['label' => 'Facebook', 'url' => trim((string) $settings['facebook_url']), 'icon' => 'fa-facebook'],
    ['label' => 'X', 'url' => trim((string) $settings['x_url']), 'icon' => 'fa-twitter'],
    ['label' => 'YouTube', 'url' => trim((string) $settings['youtube_url']), 'icon' => 'fa-youtube-play'],
];

ob_start();
require __DIR__ . '/includes/public-header.php';
require __DIR__ . '/includes/public-navbar.php';
?>
<main class="public-main">
    <section class="hero-section" id="home">
        <div class="container hero-section__inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="hero-copy reveal">
                        <span class="eyebrow">Portfolio Management System</span>
                        <h1><?= escape($fullName) ?></h1>
                        <p class="hero-title"><?= escape($professionalTitle) ?></p>
                        <p class="hero-summary"><?= escape($summary) ?></p>
                        <div class="hero-actions">
                            <a class="btn btn-primary btn-lg" href="<?= escape($downloadCvUrl) ?>" download>
                                <i class="fa fa-download me-2"></i>Download CV
                            </a>
                            <a class="btn btn-outline-light btn-lg" href="#contact">
                                <i class="fa fa-envelope me-2"></i>Contact Me
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-card reveal reveal--delay-1">
                        <div class="hero-portrait">
                            <?php if ($profileImage !== ''): ?>
                                <img src="<?= escape(url($profileImage)) ?>" <?= responsiveImageAttributes($fullName, 'eager') ?> class="img-fluid">
                            <?php elseif (is_file(__DIR__ . '/' . $heroPortraitFallback)): ?>
                                <img src="<?= escape(url($heroPortraitFallback)) ?>" <?= responsiveImageAttributes($fullName, 'eager') ?> class="img-fluid">
                            <?php else: ?>
                                <div class="hero-portrait__fallback" aria-hidden="true">
                                    <?= escape(strtoupper(substr($fullName, 0, 1))) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="hero-card__meta">
                            <div>
                                <strong><?= escape($fullName) ?></strong>
                                <span><?= escape($professionalTitle) ?></span>
                            </div>
                            <div class="hero-card__meta-pill">
                                <i class="fa fa-map-marker me-2"></i><?= escape($location !== '' ? $location : 'Available worldwide') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content-section" id="about">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">About Me</span>
                <h2>Experience, focus, and current role</h2>
                <p>Get a quick overview of the professional background behind the portfolio.</p>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <article class="glass-card reveal">
                        <div class="glass-card__body">
                            <h3 class="card-title">Biography</h3>
                            <p class="mb-0"><?= nl2br(escape($biography)) ?></p>
                        </div>
                    </article>
                </div>
                <div class="col-lg-5">
                    <div class="row g-4">
                        <div class="col-12">
                            <article class="info-card reveal reveal--delay-1">
                                <span class="info-card__icon"><i class="fa fa-briefcase"></i></span>
                                <div>
                                    <small>Years of Experience</small>
                                    <strong><?= escape($yearsOfExperienceLabel) ?></strong>
                                </div>
                            </article>
                        </div>
                        <div class="col-12">
                            <article class="info-card reveal reveal--delay-2">
                                <span class="info-card__icon"><i class="fa fa-building"></i></span>
                                <div>
                                    <small>Current Position</small>
                                    <strong><?= escape($currentPosition) ?></strong>
                                </div>
                            </article>
                        </div>
                        <div class="col-12">
                            <article class="info-card reveal reveal--delay-3">
                                <span class="info-card__icon"><i class="fa fa-industry"></i></span>
                                <div>
                                    <small>Current Company</small>
                                    <strong><?= escape($currentCompany) ?></strong>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content-section" id="experience">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">Experience</span>
                <h2>Employment history in a vertical timeline</h2>
                <p>Selected public roles are shown below in chronological order.</p>
            </div>

            <?php if ($experiences === []): ?>
                <div class="empty-state reveal">
                    <i class="fa fa-briefcase"></i>
                    <h3>No public experience yet</h3>
                    <p>Publish work experiences from the admin panel to show them here.</p>
                </div>
            <?php else: ?>
                <div class="experience-timeline">
                    <?php foreach ($experiences as $index => $experience): ?>
                        <?php
                        $startDate = !empty($experience['start_date']) ? date('M Y', strtotime((string) $experience['start_date'])) : '';
                        $endDate = !empty($experience['end_date']) ? date('M Y', strtotime((string) $experience['end_date'])) : 'Present';
                        $period = $experience['is_current'] ? ($startDate !== '' ? $startDate . ' - Present' : 'Present') : ($startDate !== '' ? $startDate . ' - ' . $endDate : $endDate);
                        $description = trim((string) ($experience['description'] ?? ''));
                        $employmentType = trim((string) ($experience['employment_type'] ?? ''));
                        $locationText = trim((string) ($experience['location'] ?? ''));
                        ?>
                        <article class="experience-item reveal reveal--delay-<?= (int) ($index % 4) ?>">
                            <div class="experience-item__marker" aria-hidden="true"></div>
                            <div class="experience-item__content">
                                <div class="experience-item__top">
                                    <div>
                                        <span class="experience-item__company"><?= escape((string) ($experience['employer_name'] ?? '')) ?></span>
                                        <h3><?= escape((string) ($experience['job_title'] ?? '')) ?></h3>
                                    </div>
                                    <span class="experience-item__period"><?= escape($period) ?></span>
                                </div>
                                <div class="experience-item__meta">
                                    <?php if ($employmentType !== ''): ?><span><i class="fa fa-tag me-1"></i><?= escape($employmentType) ?></span><?php endif; ?>
                                    <?php if ($locationText !== ''): ?><span><i class="fa fa-map-marker me-1"></i><?= escape($locationText) ?></span><?php endif; ?>
                                </div>
                                <?php if ($description !== ''): ?>
                                    <p class="experience-item__description mb-0"><?= nl2br(escape($description)) ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="content-section" id="education">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">Education</span>
                <h2>Academic background and achievements</h2>
                <p>Public education records presented in a polished vertical timeline.</p>
            </div>

            <?php if ($educations === []): ?>
                <div class="empty-state reveal">
                    <i class="fa fa-graduation-cap"></i>
                    <h3>No public education yet</h3>
                    <p>Publish education records from the admin panel to display them here.</p>
                </div>
            <?php else: ?>
                <div class="education-timeline">
                    <?php foreach ($educations as $index => $education): ?>
                        <?php
                        $startDate = !empty($education['start_date']) ? date('Y', strtotime((string) $education['start_date'])) : '';
                        $endDate = !empty($education['end_date']) ? date('Y', strtotime((string) $education['end_date'])) : '';
                        $graduationYear = $endDate !== '' ? $endDate : ($startDate !== '' ? $startDate : '');
                        $degree = trim((string) ($education['degree'] ?? ''));
                        $fieldOfStudy = trim((string) ($education['field_of_study'] ?? ''));
                        $locationText = trim((string) ($education['location'] ?? ''));
                        $grade = trim((string) ($education['grade'] ?? ''));
                        $description = trim((string) ($education['description'] ?? ''));
                        ?>
                        <article class="education-item reveal reveal--delay-<?= (int) ($index % 4) ?>">
                            <div class="education-item__marker" aria-hidden="true"></div>
                            <div class="education-item__content">
                                <div class="education-item__top">
                                    <div>
                                        <span class="education-item__institution"><?= escape((string) ($education['institution_name'] ?? '')) ?></span>
                                        <h3><?= escape($degree !== '' ? $degree : 'Academic Achievement') ?></h3>
                                    </div>
                                    <span class="education-item__year"><?= escape($graduationYear !== '' ? $graduationYear : '-') ?></span>
                                </div>
                                <div class="education-item__meta">
                                    <?php if ($fieldOfStudy !== ''): ?><span><i class="fa fa-book me-1"></i><?= escape($fieldOfStudy) ?></span><?php endif; ?>
                                    <?php if ($locationText !== ''): ?><span><i class="fa fa-map-marker me-1"></i><?= escape($locationText) ?></span><?php endif; ?>
                                    <?php if ($grade !== ''): ?><span><i class="fa fa-star me-1"></i><?= escape($grade) ?></span><?php endif; ?>
                                </div>
                                <?php if ($description !== ''): ?>
                                    <p class="education-item__description mb-0"><?= nl2br(escape($description)) ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="content-section" id="certificates">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">Certificates</span>
                <h2>Professional certifications and verified credentials</h2>
                <p>Selected certificates are shown below with responsive cards and full-detail previews.</p>
            </div>

            <?php if ($certificates === []): ?>
                <div class="empty-state reveal">
                    <i class="fa fa-certificate"></i>
                    <h3>No public certificates yet</h3>
                    <p>Publish certificate records from the admin panel to display them here.</p>
                </div>
            <?php else: ?>
                <div class="row g-4 certificates-grid" id="certificatesGrid">
                    <?php foreach ($certificates as $index => $certificate): ?>
                        <?php
                        $certificateId = (int) ($certificate['certification_id'] ?? 0);
                        $delay = number_format(($index % 8) * 0.08, 2, '.', '');
                        $issueDateLabel = (string) ($certificate['issue_date_label'] ?? '');
                        $expiryDateLabel = (string) ($certificate['expiry_date_label'] ?? '');
                        $expired = !empty($certificate['expiry_date']) && strtotime((string) $certificate['expiry_date']) < time();
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="certificate-card reveal" style="--delay: <?= escape($delay) ?>s;">
                                <div class="certificate-card__media">
                                    <?php if (!empty($certificate['image_url'])): ?>
                                        <button class="certificate-card__image-button" type="button" data-certificate-view="<?= (int) $certificateId ?>" aria-label="View <?= escape((string) ($certificate['name'] ?? 'certificate')) ?> image full size">
                                            <img src="<?= escape((string) $certificate['image_url']) ?>" <?= responsiveImageAttributes((string) ($certificate['name'] ?? 'Certificate')) ?> class="certificate-card__image">
                                        </button>
                                    <?php else: ?>
                                        <div class="certificate-card__placeholder" aria-hidden="true">
                                            <i class="fa fa-certificate"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="certificate-card__overlay">
                                        <span class="badge <?= $expired ? 'text-bg-danger' : 'text-bg-success' ?>">
                                            <?= $expired ? 'Expired' : 'Verified' ?>
                                        </span>
                                        <?php if ($issueDateLabel !== ''): ?>
                                            <span class="badge text-bg-dark bg-opacity-50"><?= escape($issueDateLabel) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="certificate-card__body">
                                    <span class="certificate-card__organization"><?= escape((string) ($certificate['issuing_organization'] ?? '')) ?></span>
                                    <h3 class="certificate-card__title"><?= escape((string) ($certificate['name'] ?? '')) ?></h3>
                                    <p class="certificate-card__date mb-3">
                                        <?= escape($issueDateLabel !== '' ? 'Issued ' . $issueDateLabel : 'Issue date unavailable') ?>
                                        <?php if ($expiryDateLabel !== ''): ?>
                                            <span class="d-block">Expires <?= escape($expiryDateLabel) ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <button class="btn btn-outline-primary w-100" type="button" data-certification-id="<?= (int) $certificateId ?>">
                                        <i class="fa fa-eye me-2"></i>View Details
                                    </button>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script type="application/json" id="certificatesData"><?= $certificatesJson ?></script>
            <?php endif; ?>
        </div>
    </section>

    <section class="content-section" id="skills">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">Skills</span>
                <h2>Public skills with proficiency levels</h2>
                <p>Selected skills are displayed below with clear progress indicators.</p>
            </div>

            <?php if ($skills === []): ?>
                <div class="empty-state reveal">
                    <i class="fa fa-code"></i>
                    <h3>No public skills yet</h3>
                    <p>Add public skills from the admin panel to showcase your strengths here.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($skills as $index => $skill): ?>
                        <?php
                        $skillName = (string) ($skill['skill_name'] ?? '');
                        $categoryName = (string) ($skill['category_name'] ?? '');
                        $proficiency = (int) ($skill['proficiency_level'] ?? 0);
                        $proficiency = max(0, min(100, $proficiency));
                        $delay = number_format(($index % 8) * 0.08, 2, '.', '');
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="skill-card reveal" style="--delay: <?= escape($delay) ?>s;">
                                <div class="skill-card__header">
                                    <div>
                                        <span class="skill-card__category"><?= escape($categoryName !== '' ? $categoryName : 'Skill') ?></span>
                                        <h3><?= escape($skillName) ?></h3>
                                    </div>
                                    <span class="skill-card__value"><?= escape((string) $proficiency) ?>%</span>
                                </div>
                                <div class="progress skill-progress" role="progressbar" aria-label="<?= escape($skillName) ?> proficiency" aria-valuenow="<?= (int) $proficiency ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar skill-progress__bar" style="width: <?= (int) $proficiency ?>%"></div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="content-section" id="projects">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">Projects</span>
                <h2>Selected work delivered with care</h2>
                <p>Public projects are shown as responsive cards with quick detail previews.</p>
            </div>

            <?php if ($projects === []): ?>
                <div class="empty-state reveal">
                    <i class="fa fa-folder-open"></i>
                    <h3>No public projects yet</h3>
                    <p>Add public projects from the admin panel to showcase them here.</p>
                </div>
            <?php else: ?>
                <div class="row g-4 projects-grid" id="projectsGrid">
                    <?php foreach ($projects as $index => $project): ?>
                        <?php
                        $projectId = (int) ($project['project_id'] ?? 0);
                        $technologies = $project['technologies'] ?? [];
                        $status = (string) ($project['status'] ?? 'planned');
                        switch ($status) {
                            case 'in_progress':
                                $statusClass = 'text-bg-info';
                                break;
                            case 'completed':
                                $statusClass = 'text-bg-success';
                                break;
                            case 'archived':
                                $statusClass = 'text-bg-secondary';
                                break;
                            default:
                                $statusClass = 'text-bg-warning';
                                break;
                        }
                        $delay = number_format(($index % 8) * 0.08, 2, '.', '');
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="project-card reveal" style="--delay: <?= escape($delay) ?>s;">
                                <div class="project-card__media">
                                    <?php if (!empty($project['image_url'])): ?>
                                        <img src="<?= escape((string) $project['image_url']) ?>" <?= responsiveImageAttributes((string) ($project['title'] ?? 'Project')) ?> class="project-card__image">
                                    <?php else: ?>
                                        <?= renderProjectCover((string) ($project['title'] ?? ''), (string) ($project['category_name'] ?? ''), $technologies, 'project-card__cover') ?>
                                    <?php endif; ?>
                                </div>
                                <div class="project-card__body">
                                    <div class="project-card__meta">
                                        <span class="project-card__category"><?= escape((string) ($project['category_name'] ?? 'Uncategorized')) ?></span>
                                        <span class="badge <?= escape($statusClass) ?>"><?= escape((string) ($project['status_label'] ?? 'Planned')) ?></span>
                                    </div>
                                    <h3 class="project-card__title"><?= escape((string) ($project['title'] ?? '')) ?></h3>
                                    <p class="project-card__summary mb-3"><?= escape((string) ($project['short_description'] ?? '')) ?></p>
                                    <div class="project-card__tech d-flex flex-wrap gap-2 mb-3">
                                        <?php if ($technologies === []): ?>
                                            <span class="text-muted small">No technologies listed</span>
                                        <?php else: ?>
                                            <?php foreach ($technologies as $technology): ?>
                                                <span class="badge rounded-pill text-bg-light border project-tech-chip"><?= escape((string) $technology) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-outline-primary w-100" type="button" data-project-id="<?= (int) $projectId ?>">
                                        <i class="fa fa-eye me-2"></i>View Details
                                    </button>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script type="application/json" id="projectsData"><?= $projectsJson ?></script>
            <?php endif; ?>
        </div>
    </section>

    <section class="content-section" id="contact">
        <div class="container">
            <div class="section-heading reveal">
                <span class="section-kicker">Contact</span>
                <h2>Let’s start a conversation</h2>
                <p>Use the form below to send a message directly from the portfolio.</p>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-5">
                    <div class="contact-panel reveal">
                        <div class="contact-panel__inner">
                            <h3 class="card-title mb-3">Contact Details</h3>
                            <p class="text-muted mb-4">Prefer email, phone, or a quick message? Choose the most convenient option below.</p>

                            <div class="contact-method">
                                <span class="contact-method__icon"><i class="fa fa-envelope"></i></span>
                                <div>
                                    <small>Email</small>
                                    <strong><?= escape($contactEmail !== '' ? $contactEmail : 'Not configured') ?></strong>
                                </div>
                            </div>

                            <div class="contact-method">
                                <span class="contact-method__icon"><i class="fa fa-phone"></i></span>
                                <div>
                                    <small>Phone</small>
                                    <strong><?= escape($contactPhone !== '' ? $contactPhone : 'Not configured') ?></strong>
                                </div>
                            </div>

                            <div class="contact-method">
                                <span class="contact-method__icon"><i class="fa fa-map-marker"></i></span>
                                <div>
                                    <small>Location</small>
                                    <strong><?= escape($contactAddress !== '' ? $contactAddress : 'Available worldwide') ?></strong>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <?php if ($contactEmail !== ''): ?>
                                    <a class="btn btn-primary" href="<?= escape($contactHref) ?>">
                                        <i class="fa fa-paper-plane me-2"></i>Email Me
                                    </a>
                                <?php endif; ?>
                                <?php if ($contactMapsUrl !== ''): ?>
                                    <a class="btn btn-outline-light" href="<?= escape($contactMapsUrl) ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="fa fa-location-arrow me-2"></i>View Map
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form-panel reveal reveal--delay-1">
                        <form
                            id="contactForm"
                            class="needs-validation"
                            novalidate
                            data-submit-url="<?= escape(url('admin/messages/ajax.php?action=submit')) ?>"
                        >
                            <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">

                            <div id="contactFormAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="sender_name">Full Name <span class="text-danger">*</span></label>
                                    <input class="form-control" id="sender_name" name="sender_name" type="text" maxlength="150" required placeholder="Your full name">
                                    <div class="invalid-feedback">Please enter your full name.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sender_email">Email <span class="text-danger">*</span></label>
                                    <input class="form-control" id="sender_email" name="sender_email" type="email" maxlength="255" required placeholder="you@example.com">
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="subject">Subject <span class="text-danger">*</span></label>
                                    <input class="form-control" id="subject" name="subject" type="text" maxlength="250" required placeholder="How can I help?">
                                    <div class="invalid-feedback">Please enter a subject.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="message_body">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message_body" name="message_body" rows="7" maxlength="4000" required placeholder="Write your message here..."></textarea>
                                    <div class="invalid-feedback">Please enter your message.</div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                                <p class="small text-muted mb-0">All fields are handled securely and stored in the dashboard inbox.</p>
                                <button class="btn btn-primary px-4" type="submit" id="contactSubmitBtn">
                                    <span class="contact-save-text">Send Message</span>
                                    <span class="contact-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="projectDetailsModal" tabindex="-1" aria-hidden="true" aria-labelledby="projectDetailsTitle">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content project-details-modal">
                <div class="modal-header">
                    <div>
                        <span class="project-details-modal__eyebrow">Project Details</span>
                        <h5 class="modal-title" id="projectDetailsTitle">Project Details</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="project-details__hero mb-4" id="projectDetailsCover">
                        <img id="projectDetailsImage" src="" alt="" class="img-fluid rounded-4" decoding="async">
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill text-bg-light border" id="projectDetailsCategory">Category</span>
                        <span class="badge" id="projectDetailsStatus">Status</span>
                    </div>
                    <p class="lead mb-3" id="projectDetailsShortDescription"></p>
                    <div class="project-details__description mb-3" id="projectDetailsDescription"></div>
                    <div class="project-details__tech d-flex flex-wrap gap-2" id="projectDetailsTechnologies"></div>
                </div>
                <div class="modal-footer justify-content-between flex-wrap gap-2">
                    <div class="small text-muted">Explore the live implementation and source code below.</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-secondary" id="projectDetailsGithub" href="#" target="_blank" rel="noopener noreferrer">
                            <i class="fa fa-github me-2"></i>GitHub
                        </a>
                        <a class="btn btn-primary" id="projectDetailsDemo" href="#" target="_blank" rel="noopener noreferrer">
                            <i class="fa fa-external-link me-2"></i>Live Demo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="certificateDetailsModal" tabindex="-1" aria-hidden="true" aria-labelledby="certificateDetailsTitle">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content certificate-details-modal">
                <div class="modal-header">
                    <div>
                        <span class="certificate-details-modal__eyebrow">Certificate Details</span>
                        <h5 class="modal-title" id="certificateDetailsTitle">Certificate Details</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="certificate-details__hero mb-4">
                        <img id="certificateDetailsImage" src="" alt="" class="img-fluid rounded-4" decoding="async">
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill text-bg-light border" id="certificateDetailsOrganization">Organization</span>
                        <span class="badge" id="certificateDetailsExpiryBadge" style="display:none;">Valid</span>
                    </div>
                    <p class="lead mb-3" id="certificateDetailsDate"></p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="certificate-details__meta p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-5">
                                <small class="d-block text-uppercase fw-semibold mb-1 text-white-50">Issue Date</small>
                                <strong id="certificateDetailsIssueDate">-</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="certificate-details__meta p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-5">
                                <small class="d-block text-uppercase fw-semibold mb-1 text-white-50">Expiration Date</small>
                                <strong id="certificateDetailsExpiryDate">-</strong>
                            </div>
                        </div>
                    </div>
                    <div class="certificate-details__description mb-3" id="certificateDetailsDescription"></div>
                </div>
                <div class="modal-footer justify-content-between flex-wrap gap-2">
                    <div class="small text-muted">Open the PDF or verify the credential if available.</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-light" id="certificateDetailsPdf" href="#" target="_blank" rel="noopener noreferrer" download style="display:none;">
                            <i class="fa fa-download me-2"></i>Download Certificate
                        </a>
                        <a class="btn btn-primary" id="certificateDetailsVerify" href="#" target="_blank" rel="noopener noreferrer" style="display:none;">
                            <i class="fa fa-check-circle me-2"></i>Verify Credential
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade image-viewer" id="certificateImageViewer" tabindex="-1" aria-labelledby="certificateImageViewerTitle" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-centered">
            <div class="modal-content image-viewer__content">
                <div class="modal-header"><h5 class="modal-title" id="certificateImageViewerTitle">Certificate image</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body image-viewer__body"><button class="image-viewer__nav image-viewer__nav--previous" type="button" aria-label="Previous image"><i class="fa fa-angle-left"></i></button><img id="certificateViewerImage" alt="" decoding="async"><button class="image-viewer__nav image-viewer__nav--next" type="button" aria-label="Next image"><i class="fa fa-angle-right"></i></button></div>
                <div class="modal-footer"><span class="small text-white-50">Use the mouse wheel or trackpad to zoom.</span></div>
            </div>
        </div>
    </div>
</main>
<?php
$pageScripts = '<script src="' . escape(asset('js/projects.js')) . '"></script>' . "\n" . '<script src="' . escape(asset('js/certificates.js')) . '"></script>' . "\n" . '<script src="' . escape(asset('js/messages.js')) . '"></script>';
require __DIR__ . '/includes/public-footer.php';
$pageContent = (string) ob_get_clean();

echo $pageContent;
