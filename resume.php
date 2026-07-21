<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Dompdf is not installed. Please run composer install before generating the resume PDF.';
    exit;
}

require_once $autoload;

if (!class_exists('\\Dompdf\\Dompdf')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Dompdf could not be loaded from vendor/autoload.php.';
    exit;
}

function resumeFetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function resumeFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function resumeText(?string $value, string $fallback = ''): string
{
    $value = trim((string) $value);

    return $value !== '' ? escape($value) : $fallback;
}

function resumePlain(?string $value, string $fallback = ''): string
{
    $value = trim((string) $value);

    return $value !== '' ? $value : $fallback;
}

function resumeDateLabel(?string $value, string $format = 'M Y'): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

function resumePeriod(?string $start, ?string $end, bool $isCurrent = false): string
{
    $startLabel = resumeDateLabel($start);
    $endLabel = $isCurrent ? 'Present' : resumeDateLabel($end);

    if ($startLabel === '' && $endLabel === '') {
        return '';
    }

    if ($startLabel === '') {
        return $endLabel;
    }

    if ($endLabel === '') {
        return $startLabel;
    }

    return $startLabel . ' - ' . $endLabel;
}

function resumeLimit(string $value, int $length): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $length) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $length, 'UTF-8')) . '...';
    }

    if (strlen($value) <= $length) {
        return $value;
    }

    return rtrim(substr($value, 0, $length)) . '...';
}

function resumeImageDataUri(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    $absolutePath = null;
    if (preg_match('/^(?:[a-z]+:)?\/\//i', $path) === 1) {
        return '';
    }

    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        $absolutePath = $path;
    } else {
        $absolutePath = __DIR__ . '/' . ltrim($path, '/\\');
    }

    $resolvedPath = realpath($absolutePath);
    if ($resolvedPath === false || !is_file($resolvedPath)) {
        return '';
    }

    $mimeType = function_exists('mime_content_type') ? (string) mime_content_type($resolvedPath) : 'image/jpeg';
    $binary = file_get_contents($resolvedPath);
    if ($binary === false) {
        return '';
    }

    return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
}

function resumeLogDownload(PDO $pdo): void
{
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipHash = trim($ipAddress) !== '' ? hash('sha256', $ipAddress) : null;
        $statement = $pdo->prepare(
            'INSERT INTO download_events
                (cv_file_id, visitor_session_id, download_type, source_path, downloaded_at, ip_hash, user_agent)
             VALUES
                (NULL, NULL, :download_type, :source_path, NOW(), :ip_hash, :user_agent)'
        );
        $statement->execute([
            ':download_type' => 'dynamic_resume',
            ':source_path' => 'resume.php',
            ':ip_hash' => $ipHash,
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000) ?: null,
        ]);
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
    }
}

$profile = resumeFetchOne(
    $pdo,
    'SELECT *
     FROM profiles
     WHERE is_public = 1
     ORDER BY profile_id ASC
     LIMIT 1'
);

if ($profile === null) {
    $profile = resumeFetchOne($pdo, 'SELECT * FROM profiles ORDER BY profile_id ASC LIMIT 1');
}

if ($profile === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No profile data is available to generate a resume.';
    exit;
}

$profileId = (int) ($profile['profile_id'] ?? 0);

$settings = [
    'website_name' => 'Portfolio CMS',
    'website_tagline' => 'Professional portfolio and project showcase',
    'github_url' => '',
    'linkedin_url' => '',
];

$resumeLimits = [
    'skill_categories' => 4,
    'skills_per_category' => 8,
    'experiences' => 3,
    'educations' => 2,
    'projects' => 3,
    'certificates' => 4,
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

$skills = resumeFetchAll(
    $pdo,
    'SELECT c.skill_category_id,
            c.name AS category_name,
            c.display_order AS category_order,
            s.name AS skill_name,
            s.proficiency_level,
            s.display_order AS skill_order
     FROM skills s
     INNER JOIN skill_categories c ON c.skill_category_id = s.skill_category_id
     WHERE s.is_public = 1
     ORDER BY c.display_order ASC, c.name ASC, s.display_order ASC, s.name ASC'
);

$skillGroups = [];
foreach ($skills as $row) {
    $categoryName = (string) ($row['category_name'] ?? 'Skills');
    if (!isset($skillGroups[$categoryName])) {
        $skillGroups[$categoryName] = [];
    }

    $skillGroups[$categoryName][] = [
        'name' => (string) ($row['skill_name'] ?? ''),
        'level' => $row['proficiency_level'] !== null ? (int) $row['proficiency_level'] : null,
    ];
}

$skillGroups = array_slice($skillGroups, 0, $resumeLimits['skill_categories'], true);
foreach ($skillGroups as &$items) {
    $items = array_slice($items, 0, $resumeLimits['skills_per_category']);
}
unset($items);

$experiences = resumeFetchAll(
    $pdo,
    'SELECT we.work_experience_id,
            we.employer_name,
            we.job_title,
            we.employment_type,
            we.location,
            we.start_date,
            we.end_date,
            we.is_current,
            we.description,
            GROUP_CONCAT(weh.highlight_text ORDER BY weh.display_order SEPARATOR "||") AS highlights
     FROM work_experiences we
     LEFT JOIN work_experience_highlights weh ON weh.work_experience_id = we.work_experience_id
     WHERE we.profile_id = :profile_id AND we.is_public = 1
     GROUP BY we.work_experience_id
     ORDER BY we.display_order ASC, we.start_date DESC, we.work_experience_id DESC',
    [':profile_id' => $profileId]
);

foreach ($experiences as &$experience) {
    $rawHighlights = trim((string) ($experience['highlights'] ?? ''));
    $experience['highlights'] = $rawHighlights !== ''
        ? array_values(array_filter(array_map('trim', explode('||', $rawHighlights))))
        : [];
}
unset($experience);

$experiences = array_slice($experiences, 0, $resumeLimits['experiences']);

$educations = resumeFetchAll(
    $pdo,
    'SELECT education_id, institution_name, degree, field_of_study, location, start_date, end_date, grade, description, display_order
     FROM educations
     WHERE profile_id = :profile_id AND is_public = 1
     ORDER BY display_order ASC, end_date DESC, start_date DESC, education_id DESC',
    [':profile_id' => $profileId]
);

$educations = array_slice($educations, 0, $resumeLimits['educations']);

$projects = resumeFetchAll(
    $pdo,
    'SELECT p.project_id,
            p.title,
            p.short_description,
            p.description,
            p.status,
            p.display_order,
            c.name AS category_name,
            p.completed_date,
            (
                SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ", ")
                FROM project_technologies pt
                INNER JOIN technologies t ON t.technology_id = pt.technology_id
                WHERE pt.project_id = p.project_id
            ) AS technology_names
     FROM projects p
     LEFT JOIN project_categories c ON c.project_category_id = p.project_category_id
     WHERE p.is_public = 1
     ORDER BY p.display_order ASC, p.title ASC, p.project_id DESC'
);

foreach ($projects as &$project) {
    $project['technologies'] = array_values(array_filter(array_map(
        'trim',
        explode(', ', trim((string) ($project['technology_names'] ?? '')))
    )));
}
unset($project);

$projects = array_slice($projects, 0, $resumeLimits['projects']);

$certificates = resumeFetchAll(
    $pdo,
    'SELECT certification_id,
            name,
            issuing_organization,
            credential_id,
            credential_url,
            certificate_image_path,
            issued_date,
            expiry_date,
            certificate_file_path,
            display_order
     FROM certifications
     WHERE profile_id = :profile_id AND is_public = 1
     ORDER BY display_order ASC, issued_date DESC, certification_id DESC',
    [':profile_id' => $profileId]
);

$certificates = array_slice($certificates, 0, $resumeLimits['certificates']);

$fullName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
$professionalTitle = trim((string) ($profile['professional_title'] ?? ''));
$biography = trim((string) ($profile['biography'] ?? ''));
$email = trim((string) ($profile['email'] ?? ''));
$phone = trim((string) ($profile['phone'] ?? ''));
$location = trim((string) ($profile['location'] ?? ''));
$profileImage = resumeImageDataUri($profile['profile_image_path'] ?? null);
$githubUrl = trim((string) $settings['github_url']);
$linkedinUrl = trim((string) $settings['linkedin_url']);
$summary = $biography !== '' ? resumeLimit($biography, 260) : (string) $settings['website_tagline'];

$isDownload = isset($_GET['download']) && (string) $_GET['download'] !== '0';
if ($isDownload) {
    resumeLogDownload($pdo);
}

$options = new \Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isPhpEnabled', false);

$dompdf = new \Dompdf\Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 16mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2937;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.2pt;
            line-height: 1.45;
        }

        .page {
            width: 100%;
        }

        .hero {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .hero-table {
            width: 100%;
            border-collapse: collapse;
        }

        .hero-table td {
            vertical-align: top;
        }

        .identity {
            width: 62%;
            padding-right: 12px;
        }

        .identity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .avatar-cell {
            width: 80px;
            padding-right: 12px;
        }

        .avatar {
            width: 74px;
            height: 74px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid #d1d5db;
        }

        .name {
            margin: 0;
            color: #0f172a;
            font-size: 24pt;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .title {
            margin: 3px 0 6px;
            color: #2563eb;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .summary {
            margin: 0;
            color: #374151;
        }

        .contact {
            width: 38%;
            padding-left: 12px;
        }

        .contact-box {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 10px 12px;
            background: #f8fafc;
        }

        .contact-row {
            width: 100%;
            border-collapse: collapse;
        }

        .contact-row td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            width: 34%;
            color: #6b7280;
            font-size: 8.8pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .value {
            color: #111827;
            font-weight: 600;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            margin: 0 0 8px;
            padding: 6px 10px;
            color: #ffffff;
            background: #0f172a;
            border-radius: 8px;
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .section-text {
            margin: 0;
            color: #374151;
        }

        .skills-table,
        .item-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .skills-table td,
        .item-table td,
        .data-table td,
        .data-table th {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
        }

        .skills-table th,
        .data-table th {
            text-align: left;
            color: #6b7280;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            background: #f8fafc;
        }

        .skill-tags {
            color: #111827;
        }

        .item-table {
            margin-bottom: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .item-head {
            background: #f8fafc;
        }

        .item-title {
            margin: 0;
            color: #0f172a;
            font-size: 11pt;
            font-weight: 700;
        }

        .item-subtitle {
            margin: 2px 0 0;
            color: #2563eb;
            font-size: 9pt;
            font-weight: 700;
        }

        .item-meta {
            color: #6b7280;
            font-size: 8.8pt;
            text-align: right;
            white-space: nowrap;
        }

        .item-copy {
            color: #374151;
        }

        .bullets {
            margin: 6px 0 0 16px;
            padding: 0;
            color: #374151;
        }

        .bullets li {
            margin: 0 0 4px;
        }

        .muted {
            color: #6b7280;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            font-size: 8.3pt;
            font-weight: 700;
        }

        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 8.5pt;
            text-align: right;
        }

        .page-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <table class="hero-table">
                <tr>
                    <td class="identity">
                        <table class="identity-table">
                            <tr>
                                <?php if ($profileImage !== ''): ?>
                                    <td class="avatar-cell">
                                        <img class="avatar" src="<?= escape($profileImage) ?>" alt="<?= escape($fullName) ?>">
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <h1 class="name"><?= escape($fullName) ?></h1>
                                    <?php if ($professionalTitle !== ''): ?>
                                        <div class="title"><?= escape($professionalTitle) ?></div>
                                    <?php endif; ?>
                                    <p class="summary"><?= escape($summary) ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="contact">
                        <div class="contact-box">
                            <table class="contact-row">
                                <?php if ($email !== ''): ?>
                                    <tr>
                                        <td class="label">Email</td>
                                        <td class="value"><?= escape($email) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($phone !== ''): ?>
                                    <tr>
                                        <td class="label">Phone</td>
                                        <td class="value"><?= escape($phone) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($location !== ''): ?>
                                    <tr>
                                        <td class="label">Location</td>
                                        <td class="value"><?= escape($location) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($githubUrl !== ''): ?>
                                    <tr>
                                        <td class="label">GitHub</td>
                                        <td class="value"><?= escape($githubUrl) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($linkedinUrl !== ''): ?>
                                    <tr>
                                        <td class="label">LinkedIn</td>
                                        <td class="value"><?= escape($linkedinUrl) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <?php if ($skillGroups !== []): ?>
            <div class="section">
                <div class="section-title">Skills</div>
                <table class="skills-table">
                    <thead>
                        <tr>
                            <th style="width: 28%;">Category</th>
                            <th>Skills</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($skillGroups as $categoryName => $items): ?>
                            <tr>
                                <td><strong><?= escape($categoryName) ?></strong></td>
                                <td class="skill-tags">
                                    <?= escape(implode(', ', array_map(static function (array $skill): string {
                                        $label = $skill['name'];
                                        if (!empty($skill['level'])) {
                                            $label .= ' (' . (int) $skill['level'] . '%)';
                                        }
                                        return $label;
                                    }, $items))) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($experiences !== []): ?>
            <div class="section page-break">
                <div class="section-title">Experience</div>
                <?php foreach ($experiences as $experience): ?>
                    <table class="item-table">
                        <tr class="item-head">
                            <td>
                                <p class="item-title"><?= escape((string) ($experience['job_title'] ?? '')) ?></p>
                                <p class="item-subtitle"><?= escape(trim((string) ($experience['employer_name'] ?? '') . (($experience['employment_type'] ?? '') !== '' ? ' | ' . (string) $experience['employment_type'] : ''))) ?></p>
                                <?php if (trim((string) ($experience['location'] ?? '')) !== ''): ?>
                                    <div class="muted"><?= escape((string) $experience['location']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="item-meta">
                                <?= escape(resumePeriod($experience['start_date'] ?? null, $experience['end_date'] ?? null, (bool) ($experience['is_current'] ?? false))) ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="item-copy">
                                <?php if (trim((string) ($experience['description'] ?? '')) !== ''): ?>
                                    <div><?= nl2br(escape(resumeLimit((string) $experience['description'], 700))) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($experience['highlights'])): ?>
                                    <ul class="bullets">
                                        <?php foreach ($experience['highlights'] as $highlight): ?>
                                            <li><?= escape((string) $highlight) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($educations !== []): ?>
            <div class="section page-break">
                <div class="section-title">Education</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Institution</th>
                            <th>Qualification</th>
                            <th style="width: 18%;">Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($educations as $education): ?>
                            <tr>
                                <td>
                                    <strong><?= escape((string) ($education['institution_name'] ?? '')) ?></strong>
                                    <?php if (trim((string) ($education['location'] ?? '')) !== ''): ?>
                                        <div class="muted"><?= escape((string) $education['location']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= escape(trim((string) ($education['degree'] ?? '') . (($education['field_of_study'] ?? '') !== '' ? ' - ' . (string) $education['field_of_study'] : ''))) ?>
                                    <?php if (trim((string) ($education['grade'] ?? '')) !== ''): ?>
                                        <div class="muted">Grade: <?= escape((string) $education['grade']) ?></div>
                                    <?php endif; ?>
                                    <?php if (trim((string) ($education['description'] ?? '')) !== ''): ?>
                                        <div class="muted"><?= escape(resumeLimit((string) $education['description'], 180)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape(resumePeriod($education['start_date'] ?? null, $education['end_date'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($projects !== []): ?>
            <div class="section page-break">
                <div class="section-title">Projects</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Technology</th>
                            <th style="width: 16%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <strong><?= escape((string) ($project['title'] ?? '')) ?></strong>
                                    <?php if (trim((string) ($project['category_name'] ?? '')) !== ''): ?>
                                        <div class="muted"><?= escape((string) $project['category_name']) ?></div>
                                    <?php endif; ?>
                                    <?php
                                        $projectSummary = trim((string) ($project['short_description'] ?? ''));
                                        if ($projectSummary === '') {
                                            $projectSummary = trim((string) ($project['description'] ?? ''));
                                        }
                                    ?>
                                    <?php if ($projectSummary !== ''): ?>
                                        <div class="muted"><?= escape(resumeLimit($projectSummary, 170)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape(implode(', ', $project['technologies'] ?? [])) ?></td>
                                <td><span class="badge"><?= escape(ucwords(str_replace('_', ' ', (string) ($project['status'] ?? 'planned')))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($certificates !== []): ?>
            <div class="section page-break">
                <div class="section-title">Certificates</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Certificate</th>
                            <th>Organization</th>
                            <th style="width: 24%;">Issued / Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $certificate): ?>
                            <tr>
                                <td>
                                    <strong><?= escape((string) ($certificate['name'] ?? '')) ?></strong>
                                    <?php if (trim((string) ($certificate['credential_id'] ?? '')) !== ''): ?>
                                        <div class="muted">Credential ID: <?= escape((string) $certificate['credential_id']) ?></div>
                                    <?php endif; ?>
                                    <?php if (trim((string) ($certificate['credential_url'] ?? '')) !== ''): ?>
                                        <div class="muted"><?= escape((string) $certificate['credential_url']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape((string) ($certificate['issuing_organization'] ?? '')) ?></td>
                                <td>
                                    <?= escape(resumePeriod($certificate['issued_date'] ?? null, $certificate['expiry_date'] ?? null)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="footer">
            Generated automatically from the portfolio database on <?= escape(date('F j, Y')) ?>.
        </div>
    </div>
</body>
</html>
<?php
$html = (string) ob_get_clean();

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

if ($isDownload) {
    try {
        $visitorSessionId = trackPublicVisit($pdo, 'resume.php');
        $statement = $pdo->prepare('INSERT INTO download_events (visitor_session_id, download_type, source_path, ip_hash, user_agent) VALUES (:session_id, :type, :path, :ip_hash, :user_agent)');
        $statement->execute([':session_id' => $visitorSessionId, ':type' => 'generated_pdf', ':path' => 'resume.php', ':ip_hash' => !empty($_SERVER['REMOTE_ADDR']) ? hash('sha256', (string) $_SERVER['REMOTE_ADDR']) : null, ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000) ?: null]);
    } catch (Throwable $exception) { error_log($exception->getMessage()); }
    header('Content-Disposition: attachment; filename="Shuaib-Al-Masri-CV.pdf"');
} else {
    header('Content-Disposition: inline; filename="resume.pdf"');
}

header('Content-Type: application/pdf');
echo $dompdf->output();
