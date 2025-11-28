<?php
// ============ تنظیمات ============
$bot_token = "8496222681:AAFB9nJ0VXNlHeb2YzuoN9FcFozFSA07srQ";
$api_url = "https://api.telegram.org/bot" . $bot_token . "/";

// ایموجی مورد نظر برای الحاق به پیام (هر ایموجی که خواستی بذار)
$appendEmoji = "🙂";

// فایل لاگ برای دیباگ
$logFile = __DIR__ . "/webhook_echo_log.txt";

// تابع لاگ
function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

// تابع ارسال پیام با cURL (POST JSON)
function sendMessage($chat_id, $text) {
    global $api_url;

    $payload = [
        'chat_id' => $chat_id,
        'text'    => $text,
        'parse_mode' => null // می‌تونی "HTML" یا "Markdown" بذاری در صورت نیاز
    ];

    $ch = curl_init($api_url . "sendMessage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    // timeout کوتاه‌تر به‌خاطر محدودیت تلگرام (تلگرام حدود 10 ثانیه صبر می‌کند)
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $resp = curl_exec($ch);
    $errNo = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($errNo) {
        logMsg("cURL error ($errNo): $err");
        return false;
    }

    logMsg("sendMessage response: " . $resp);
    return true;
}

// ============ خواندن آپدیت از تلگرام ============
$raw = file_get_contents("php://input");
if (!$raw) {
    // همیشه 200 جواب بده تا تلگرام دوباره به طور پی‌در‌پی نپیچد
    http_response_code(200);
    exit();
}

logMsg("RAW_UPDATE: " . $raw);

$update = json_decode($raw, true);
if (!$update) {
    logMsg("JSON decode failed");
    http_response_code(200);
    exit();
}

// پیام متنی را استخراج می‌کنیم (پشتیبانی ساده برای message و edited_message)
$message = $update['message'] ?? $update['edited_message'] ?? null;

if (!$message) {
    logMsg("No message found in update");
    http_response_code(200);
    exit();
}

$chat_id = $message['chat']['id'] ?? null;
$text = $message['text'] ?? null;

if (!$chat_id) {
    logMsg("No chat_id found");
    http_response_code(200);
    exit();
}

if ($text !== null && $text !== '') {
    // اگر پیام متنی است، همان را با ایموجی برگردان
    // توجه: برای جلوگیری از مشکلات کاراکتری از json_encode در ارسال استفاده کردیم
    $reply = $text . " " . $appendEmoji;
    sendMessage($chat_id, $reply);
} else {
    // اگر پیام غیرمتنی بود (عکس، ویدئو، استیکر و...) یک پیام توضیحی بفرست
    $note = "این ربات فقط پیام‌های متنی را بازتاب می‌دهد 📩";
    sendMessage($chat_id, $note);
}

// همیشه 200 OK برای تلگرام
http_response_code(200);
