<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function out(array $payload, int $status = 200): never { http_response_code($status); echo json_encode($payload); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) out(['success' => false, 'message' => 'Invalid request.'], 419);

$limits = ['first_name'=>100, 'last_name'=>100, 'professional_title'=>200, 'email'=>255, 'phone'=>50, 'location'=>200, 'nationality'=>100, 'date_of_birth'=>10, 'years_of_experience'=>2, 'current_position'=>200, 'current_company'=>200, 'professional_summary'=>1000, 'about_me'=>5000];
$data = []; $errors = [];
foreach ($limits as $field => $limit) { $data[$field] = trim((string) ($_POST[$field] ?? '')); if (strlen($data[$field]) > $limit) $errors[$field] = 'Too long.'; }
foreach (['first_name', 'last_name', 'professional_title'] as $field) if (!$data[$field]) $errors[$field] = 'Required.';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
if ($data['years_of_experience'] !== '' && (!ctype_digit($data['years_of_experience']) || (int) $data['years_of_experience'] > 99)) $errors['years_of_experience'] = 'Use 0-99.';
if ($data['date_of_birth'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth']) || $data['date_of_birth'] > date('Y-m-d'))) $errors['date_of_birth'] = 'Invalid date.';
if ($errors) out(['success' => false, 'message' => 'Correct the highlighted fields.', 'errors' => $errors], 422);

try {
    $row = $pdo->query('SELECT profile_id FROM profiles ORDER BY profile_id LIMIT 1')->fetch();
    $profile = ['first_name'=>$data['first_name'], 'last_name'=>$data['last_name'], 'professional_title'=>$data['professional_title'], 'biography'=>$data['about_me'] ?: null, 'professional_summary'=>$data['professional_summary'] ?: null, 'email'=>$data['email'], 'phone'=>$data['phone'] ?: null, 'location'=>$data['location'] ?: null, 'nationality'=>$data['nationality'] ?: null, 'date_of_birth'=>$data['date_of_birth'] ?: null, 'years_of_experience'=>$data['years_of_experience'] !== '' ? (int) $data['years_of_experience'] : null, 'current_position'=>$data['current_position'] ?: null, 'current_company'=>$data['current_company'] ?: null];
    if ($row) {
        $profile['id'] = $row['profile_id'];
        $pdo->prepare('UPDATE profiles SET first_name=:first_name,last_name=:last_name,professional_title=:professional_title,biography=:biography,professional_summary=:professional_summary,email=:email,phone=:phone,location=:location,nationality=:nationality,date_of_birth=:date_of_birth,years_of_experience=:years_of_experience,current_position=:current_position,current_company=:current_company WHERE profile_id=:id')->execute($profile);
    } else {
        $pdo->prepare('INSERT INTO profiles(first_name,last_name,professional_title,biography,professional_summary,email,phone,location,nationality,date_of_birth,years_of_experience,current_position,current_company) VALUES(:first_name,:last_name,:professional_title,:biography,:professional_summary,:email,:phone,:location,:nationality,:date_of_birth,:years_of_experience,:current_position,:current_company)')->execute($profile);
    }
    out(['success' => true, 'message' => 'Profile saved successfully.']);
} catch (Throwable $exception) { error_log($exception->getMessage()); out(['success' => false, 'message' => 'Unable to save profile.'], 500); }
