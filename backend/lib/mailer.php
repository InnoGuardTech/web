<?php
/**
 * mailer.php - مرسل البريد الإلكتروني والـ SMS
 * في وضع log فقط يكتب إلى ملف log
 * يمكن استبدال SMTP لاحقاً بـ PHPMailer
 */

if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body) {
        $driver = env('MAIL_DRIVER', 'log');
        $from = env('MAIL_FROM_ADDRESS', 'noreply@haraj-yemen.com');
        $fromName = env('MAIL_FROM_NAME', 'حراج اليمن');

        if ($driver === 'log') {
            $logFile = dirname(__DIR__, 2) . '/cache/mail.log';
            $dir = dirname($logFile);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);

            $entry = "[" . date('Y-m-d H:i:s') . "]\n";
            $entry .= "From: $fromName <$from>\n";
            $entry .= "To: $to\n";
            $entry .= "Subject: $subject\n";
            $entry .= "Body:\n$body\n";
            $entry .= str_repeat('=', 60) . "\n";
            @file_put_contents($logFile, $entry, FILE_APPEND);
            return true;
        }

        // SMTP عبر mail() الافتراضي
        $headers = "From: $fromName <$from>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        return @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);
    }
}

if (!function_exists('sendSMS')) {
    function sendSMS($phone, $message) {
        $driver = env('SMS_DRIVER', 'log');

        if ($driver === 'log') {
            $logFile = dirname(__DIR__, 2) . '/cache/sms.log';
            $dir = dirname($logFile);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);

            $entry = "[" . date('Y-m-d H:i:s') . "] TO: $phone\nMSG: $message\n" . str_repeat('-', 50) . "\n";
            @file_put_contents($logFile, $entry, FILE_APPEND);
            return true;
        }

        // مكان تكامل مزود SMS فعلي (مثل Twilio أو مزود يمني)
        return false;
    }
}

if (!function_exists('sendOTP')) {
    function sendOTP($phone, $code, $purpose = 'verify') {
        $purposes = [
            'verify' => 'رمز تأكيد رقم جوالك',
            'reset'  => 'رمز استعادة كلمة المرور',
            'login'  => 'رمز تسجيل الدخول'
        ];
        $title = $purposes[$purpose] ?? 'رمز التحقق';
        $msg = "حراج اليمن: $title هو: $code\nصالح لمدة 10 دقائق.";
        return sendSMS($phone, $msg);
    }
}
