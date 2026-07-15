<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function uout(array $payload, int $status = 200): never { http_response_code($status); echo json_encode($payload); exit; }

$token = $_POST[CSRF_TOKEN_NAME] ?? null;
$fields = ['profile-photo'=>'profile_image_path', 'cover-photo'=>'cover_photo_path', 'logo'=>'logo_path', 'favicon'=>'favicon_path'];
$type = $_POST['type'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || !verifyCsrfToken($token) || !isset($fields[$type], $_FILES['image'])) uout(['success'=>false, 'message'=>'Invalid request.'], 419);
$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size'] > MAX_UPLOAD_SIZE) uout(['success'=>false, 'message'=>'Upload failed or file is too large.'], 422);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/x-icon'=>'ico', 'image/vnd.microsoft.icon'=>'ico'];
if (!isset($extensions[$mime]) || ($type !== 'favicon' && $extensions[$mime] === 'ico') || ($extensions[$mime] !== 'ico' && !getimagesize($file['tmp_name']))) uout(['success'=>false, 'message'=>'Use a valid JPG, PNG, or WebP image.'], 422);
$directory = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'profile';
if (!is_dir($directory) && !mkdir($directory, 0755, true)) uout(['success'=>false, 'message'=>'Storage unavailable.'], 500);
$path = 'uploads/profile/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path))) uout(['success'=>false, 'message'=>'Unable to store image.'], 500);

try {
    $profile = $pdo->query('SELECT profile_id FROM profiles ORDER BY profile_id LIMIT 1')->fetch();
    if (!$profile) throw new RuntimeException('Profile does not exist.');
    $field = $fields[$type];
    $pdo->prepare("UPDATE profiles SET `{$field}` = :path WHERE profile_id = :id")->execute(['path'=>$path, 'id'=>$profile['profile_id']]);
    uout(['success'=>true, 'message'=>'Image uploaded.', 'url'=>url($path)]);
} catch (Throwable $exception) {
    @unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    error_log($exception->getMessage());
    uout(['success'=>false, 'message'=>'Save profile details before uploading a photo.'], 422);
}
