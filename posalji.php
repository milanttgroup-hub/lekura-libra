<?php
/**
 * Lektori Libra – slanje upita iz kontakt forme (sa prilozima) na imejl.
 * Radi na standardnom PHP hostingu (cPanel), bez dodatnih biblioteka.
 */
declare(strict_types=1);

// ---------- Podešavanja ----------
const TO_EMAIL     = 'prevodiocibg@gmail.com';
const FROM_EMAIL   = 'noreply@lektorilibra.rs';   // adresa na domenu sajta (bolja isporuka)
const FROM_NAME    = 'Lektori Libra – sajt';
const MAX_FILES    = 3;
const MAX_TOTAL    = 10 * 1024 * 1024;            // 10 MB ukupno
const RATE_LIMIT   = 5;                           // najviše 5 upita ...
const RATE_WINDOW  = 3600;                        // ... po satu sa iste IP adrese
const ALLOWED_EXT  = ['doc','docx','pdf','odt','rtf','txt','ppt','pptx','xls','xlsx','jpg','jpeg','png','zip'];
const ALLOWED_MIME = [
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/pdf', 'application/vnd.oasis.opendocument.text', 'application/rtf', 'text/rtf', 'text/plain',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg', 'image/png', 'application/zip', 'application/x-zip-compressed',
    'application/octet-stream', 'application/CDFV2', 'application/x-ole-storage', // stariji .doc/.xls/.ppt
];

// ---------- Jezik (sr / en) ----------
$EN = (($_POST['lang'] ?? '') === 'en');
function t(string $sr, string $en): string
{
    global $EN;
    return $EN ? $en : $sr;
}

// ---------- Pomoćne funkcije ----------
function page(string $title, string $msg, bool $ok): void
{
    global $EN;
    $lang = $EN ? 'en' : 'sr-Latn';
    $back = $EN ? '/en/#contact' : '/#kontakt';
    $backTxt = $EN ? '← Back to the website' : '← Nazad na sajt';
    http_response_code($ok ? 200 : 400);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex');
    $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $color = $ok ? '#1d3a32' : '#b13421';
    echo <<<HTML
<!doctype html><html lang="{$lang}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>{$t} | Lektori Libra</title><link rel="icon" href="/img/logo.svg" type="image/svg+xml">
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f2ea;color:#1b2622;font:17px/1.7 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;padding:20px}
main{max-width:560px;background:#fffdf9;border:1px solid #e2d8c8;border-radius:22px;padding:40px;text-align:center}
h1{font:600 2rem/1.15 "Iowan Old Style",Palatino,Georgia,serif;color:{$color};margin:0 0 12px}
a{display:inline-block;margin-top:18px;background:#b13421;color:#fff;text-decoration:none;font-weight:600;padding:.85em 1.5em;border-radius:999px}</style></head>
<body><main><h1>{$t}</h1><p>{$msg}</p><a href="{$back}">{$backTxt}</a></main></body></html>
HTML;
    exit;
}

function clean_line(string $v, int $max = 200): string
{
    $v = trim(str_replace(["\r", "\n", "\0"], ' ', $v));
    return mb_substr($v, 0, $max, 'UTF-8');
}

function clean_text(string $v, int $max = 5000): string
{
    $v = str_replace("\0", '', trim($v));
    return mb_substr($v, 0, $max, 'UTF-8');
}

function enc_header(string $v): string
{
    return '=?UTF-8?B?' . base64_encode($v) . '?=';
}

function safe_filename(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', $name) ?? 'fajl';
    return mb_substr($name, 0, 120, 'UTF-8') ?: 'fajl';
}

// ---------- Samo POST ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /#kontakt', true, 303);
    exit;
}

// Prevelik zahtev (PHP odbaci ceo POST kada pređe post_max_size)
if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    page(t('Fajlovi su preveliki', 'Files too large'), t('Ukupna veličina priloga može biti najviše 10 MB. Veće fajlove pošaljite direktno na ', 'Attachments can be up to 10 MB in total. Please send larger files directly to ') . '<b>' . TO_EMAIL . '</b>.', false);
}

// ---------- Zaštita od spama ----------
if (!empty($_POST['website'])) {               // honeypot polje koje ljudi ne vide
    page(t('Hvala!', 'Thank you!'), t('Vaš upit je primljen.', 'Your enquiry has been received.'), true); // botu ne otkrivamo da je odbijen
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateFile = sys_get_temp_dir() . '/lektura_rl_' . hash('sha256', $ip);
$hits = [];
if (is_file($rateFile)) {
    $hits = array_filter(
        array_map('intval', explode(',', (string)file_get_contents($rateFile))),
        static fn(int $t): bool => $t > time() - RATE_WINDOW
    );
}
if (count($hits) >= RATE_LIMIT) {
    page(t('Previše upita', 'Too many enquiries'), t('Poslali ste više upita u kratkom roku. Pokušajte ponovo kasnije ili nas pozovite na +381 65 36 70 320.', 'You have sent several enquiries in a short time. Please try again later or call us on +381 65 36 70 320.'), false);
}

// ---------- Polja ----------
$ime     = clean_line((string)($_POST['ime'] ?? ''), 120);
$email   = clean_line((string)($_POST['email'] ?? ''), 160);
$telefon = clean_line((string)($_POST['telefon'] ?? ''), 60);
$vrsta   = clean_line((string)($_POST['vrsta'] ?? ''), 80);
$jezik   = clean_line((string)($_POST['jezik'] ?? ''), 80);
$rok     = clean_line((string)($_POST['rok'] ?? ''), 80);
$poruka  = clean_text((string)($_POST['poruka'] ?? ''));

if ($ime === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    page(t('Proverite podatke', 'Please check your details'), t('Upišite ime i ispravnu imejl adresu, pa pokušajte ponovo.', 'Please enter your name and a valid email address, then try again.'), false);
}

// ---------- Prilozi ----------
$attachments = [];
if (!empty($_FILES['fajlovi']) && is_array($_FILES['fajlovi']['name'])) {
    $f = $_FILES['fajlovi'];
    $count = count($f['name']);
    $total = 0;
    $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
    for ($i = 0; $i < $count; $i++) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($f['error'][$i] === UPLOAD_ERR_INI_SIZE || $f['error'][$i] === UPLOAD_ERR_FORM_SIZE) {
            page(t('Fajl je prevelik', 'File too large'), t('Ukupna veličina priloga može biti najviše 10 MB. Veće fajlove pošaljite na ', 'Attachments can be up to 10 MB in total. Please send larger files to ') . '<b>' . TO_EMAIL . '</b>.', false);
        }
        if ($f['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'][$i])) {
            page(t('Greška pri slanju fajla', 'Upload error'), t('Fajl nije uspešno otpremljen. Pokušajte ponovo.', 'The file could not be uploaded. Please try again.'), false);
        }
        if (count($attachments) >= MAX_FILES) {
            page(t('Previše fajlova', 'Too many files'), t('Možete priložiti najviše ' . MAX_FILES . ' fajla. Ostale pošaljite na ', 'You can attach up to ' . MAX_FILES . ' files. Please send the rest to ') . '<b>' . TO_EMAIL . '</b>.', false);
        }
        $name = safe_filename((string)$f['name'][$i]);
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = $finfo ? (string)$finfo->file($f['tmp_name'][$i]) : 'application/octet-stream';
        if (!in_array($ext, ALLOWED_EXT, true) || !in_array($mime, ALLOWED_MIME, true)) {
            page(t('Nedozvoljen tip fajla', 'File type not allowed'), t('Dozvoljeni su Word, PDF, PowerPoint, Excel, TXT, RTF, ODT, JPG, PNG i ZIP fajlovi.', 'Allowed formats: Word, PDF, PowerPoint, Excel, TXT, RTF, ODT, JPG, PNG and ZIP.'), false);
        }
        $size = (int)$f['size'][$i];
        $total += $size;
        if ($total > MAX_TOTAL) {
            page(t('Fajlovi su preveliki', 'Files too large'), t('Ukupna veličina priloga može biti najviše 10 MB. Veće fajlove pošaljite na ', 'Attachments can be up to 10 MB in total. Please send larger files to ') . '<b>' . TO_EMAIL . '</b>.', false);
        }
        $attachments[] = ['name' => $name, 'mime' => $mime, 'data' => (string)file_get_contents($f['tmp_name'][$i])];
    }
}

// ---------- Sastavljanje poruke ----------
$rows = [
    'Ime i prezime' => $ime,
    'Imejl'         => $email,
    'Telefon'       => $telefon,
    'Vrsta teksta'  => $vrsta,
    'Jezik teksta'  => $jezik,
    'Željeni rok'   => $rok,
    'Prilozi'       => $attachments ? implode(', ', array_column($attachments, 'name')) : 'nema',
];
$body = "Novi upit sa sajta lektorilibra.rs" . ($EN ? " (engleska verzija)" : "") . "\n\n";
foreach ($rows as $k => $v) {
    $body .= $k . ': ' . ($v !== '' ? $v : '—') . "\n";
}
$body .= "\nPoruka:\n" . ($poruka !== '' ? $poruka : '—') . "\n\n—\nIP: {$ip} · " . date('d.m.Y. H:i') . "\n";

$boundary = 'b' . bin2hex(random_bytes(12));
$headers  = [
    'From: ' . enc_header(FROM_NAME) . ' <' . FROM_EMAIL . '>',
    'Reply-To: ' . enc_header($ime) . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
    'X-Mailer: LekturaLibra',
];
$msg  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
$msg .= chunk_split(base64_encode($body)) . "\r\n";
foreach ($attachments as $a) {
    $fn = enc_header($a['name']);
    $msg .= "--{$boundary}\r\nContent-Type: {$a['mime']}; name=\"{$fn}\"\r\n"
          . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$fn}\"\r\n\r\n"
          . chunk_split(base64_encode($a['data'])) . "\r\n";
}
$msg .= "--{$boundary}--\r\n";

$subject = enc_header(($EN ? '[EN] ' : '') . 'Upit za lekturu: ' . $ime . ($vrsta !== '' ? ' (' . $vrsta . ')' : ''));
$sent = mail(TO_EMAIL, $subject, $msg, implode("\r\n", $headers), '-f' . FROM_EMAIL);

if (!$sent) {
    page(t('Slanje nije uspelo', 'Sending failed'), t('Došlo je do greške pri slanju. Pošaljite upit direktno na ', 'Something went wrong. Please email your enquiry directly to ') . '<b>' . TO_EMAIL . '</b>' . t(' ili nas pozovite na +381 65 36 70 320.', ' or call us on +381 65 36 70 320.'), false);
}

$hits[] = time();
@file_put_contents($rateFile, implode(',', $hits), LOCK_EX);

page(t('Hvala, upit je poslat!', 'Thank you – your enquiry has been sent!'), t('Javićemo vam se sa procenom cene i roka, najčešće istog dana. Ako je hitno, pozovite nas na +381 65 36 70 320.', 'We’ll get back to you with a price and deadline, usually the same day. If it’s urgent, call us on +381 65 36 70 320.'), true);
