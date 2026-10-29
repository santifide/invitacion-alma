<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, bool $ok, string $message): void
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Método no permitido.');
}

if (!is_file(__DIR__ . '/config.php')) {
    error_log('Falta config.php con los datos SMTP.');
    respond(500, false, 'El envío no está configurado. Contactá al organizador.');
}

$config = require __DIR__ . '/config.php';

$field = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};

if ($field('website') !== '') {
    respond(200, true, '¡Gracias! Tu confirmación fue enviada.');
}

$asistencia    = $field('a');
$nombre        = $field('n');
$requerimiento = $field('r');
$cancion       = $field('c');
$mensaje       = $field('m');

if (!in_array($asistencia, ['Sí asistiré', 'No podré asistir'], true)) {
    respond(422, false, 'Indicá si vas a asistir.');
}

if ($nombre === '' || mb_strlen($nombre) > 120) {
    respond(422, false, 'Ingresá tu nombre y apellido.');
}

$limite = static function (string $texto, int $max): string {
    return mb_substr($texto, 0, $max);
};

$requerimiento = $limite($requerimiento, 500);
$cancion       = $limite($cancion, 200);
$mensaje       = $limite($mensaje, 2000);

$esc = static fn(string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
$o = static fn(string $texto): string => $texto !== '' ? $texto : '-';

$filas = [
    'Asistencia'                     => $asistencia,
    'Nombre y apellido'              => $nombre,
    'Requerimiento alimentario'      => $o($requerimiento),
    'Canción que no puede faltar'    => $o($cancion),
    'Mensaje para Alma'              => $o($mensaje),
];

$html = '<div style="font-family:Arial,Helvetica,sans-serif;color:#2d1b4e;max-width:600px;margin:0 auto">'
    . '<h2 style="font-size:20px;margin:0 0 16px">Confirmación de asistencia · XV de Alma</h2>'
    . '<table style="width:100%;border-collapse:collapse">';

foreach ($filas as $etiqueta => $valor) {
    $html .= '<tr>'
        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e0ee;font-weight:bold;vertical-align:top;white-space:nowrap">' . $esc($etiqueta) . '</td>'
        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e0ee;white-space:pre-wrap">' . nl2br($esc($valor)) . '</td>'
        . '</tr>';
}

$html .= '</table></div>';

$texto = '';
foreach ($filas as $etiqueta => $valor) {
    $texto .= $etiqueta . ': ' . $valor . "\r\n";
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = (string)$config['smtp']['host'];
    $mail->Port       = (int)$config['smtp']['port'];
    $mail->SMTPAuth   = true;
    $mail->Username   = (string)$config['smtp']['user'];
    $mail->Password   = (string)$config['smtp']['pass'];
    $mail->SMTPSecure = $config['smtp']['secure'] === 'tls'
        ? PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Timeout    = 20;
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    $mail->setFrom((string)$config['from']['email'], (string)$config['from']['name']);

    foreach ($config['to'] as $destino) {
        $mail->addAddress((string)$destino['email'], (string)($destino['name'] ?? ''));
    }

    foreach ($config['cc'] as $copia) {
        $mail->addCC((string)$copia['email'], (string)($copia['name'] ?? ''));
    }

    $mail->isHTML(true);
    $mail->Subject = 'XV de Alma - ' . $asistencia . ' - ' . $nombre;
    $mail->Body    = $html;
    $mail->AltBody = $texto;

    $mail->send();

    respond(200, true, '¡Gracias! Tu confirmación fue enviada.');
} catch (\Throwable $e) {
    error_log('Error al enviar confirmación: ' . $mail->ErrorInfo);
    respond(500, false, 'No pudimos enviar tu confirmación. Intentá nuevamente en unos minutos.');
}
