<?php
// Endpoint formularza kontaktowego Premiere Agency
// Wgraj ten plik do katalogu public_html razem ze stroną.
// Zmień $TO_EMAIL na docelowy adres odbiorczy.

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Adres odbiorcy
$TO_EMAIL = 'kontakt@premierescales.pl';
$FROM_EMAIL = 'kontakt@premierescales.pl'; // musi być adres z Twojej domeny na hostingu

// Wczytaj JSON lub form-data
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$name    = trim((string)($data['name']    ?? ''));
$email   = trim((string)($data['email']   ?? ''));
$phone   = trim((string)($data['phone']   ?? ''));
$message = trim((string)($data['message'] ?? ''));

// Honeypot (anti-spam)
if (!empty($data['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

// Walidacja
if ($name === '' || strlen($name) > 120) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Niepoprawne imię i nazwisko']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Niepoprawny adres e-mail']);
    exit;
}
if ($message === '' || strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Wiadomość jest pusta lub za długa']);
    exit;
}
if ($phone !== '' && strlen($phone) > 30) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Niepoprawny numer telefonu']);
    exit;
}

// Treść maila
$subject = "[Premiere] Nowa wiadomość od: $name";

$body  = "Nowa wiadomość z formularza kontaktowego Premiere Agency\n";
$body .= "----------------------------------------\n";
$body .= "Imię i nazwisko: $name\n";
$body .= "E-mail: $email\n";
$body .= "Telefon: " . ($phone !== '' ? $phone : '—') . "\n";
$body .= "Data: " . date('Y-m-d H:i:s') . "\n";
$body .= "----------------------------------------\n\n";
$body .= "Wiadomość:\n$message\n";

// Nagłówki — adres From musi być z domeny obsługiwanej przez hosting
$safeName = preg_replace('/[\r\n]+/', ' ', $name);
$headers  = "From: Premiere Agency <$FROM_EMAIL>\r\n";
$headers .= "Reply-To: $safeName <$email>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail($TO_EMAIL, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, "-f$FROM_EMAIL");

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Nie udało się wysłać wiadomości. Spróbuj ponownie później.']);
    exit;
}

echo json_encode(['ok' => true]);
