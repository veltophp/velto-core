<?php

namespace Velto\Core;

class Mail
{
    public static function send($to, $subject, $view, $data = [])
    {
        $host = Env::get('MAIL_HOST');
        $port = Env::get('MAIL_PORT');
        $username = Env::get('MAIL_USERNAME');
        $password = Env::get('MAIL_PASSWORD');
        $from = Env::get('MAIL_FROM_ADDRESS');
        $fromName = Env::get('MAIL_FROM_NAME');

        // Load email body from view
        ob_start();
        extract($data);
        require BASE_PATH . "/views/mails/{$view}.vel.php";
        $body = ob_get_clean();

        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: {$fromName} <{$from}>",
            "Reply-To: {$from}",
        ];

        $email = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        // Buat SMTP koneksi
        $socket = fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) {
            return false;
        }

        $expected = function ($code) use ($socket) {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (preg_match("/^{$code}/", $line)) {
                    break;
                }
            }
            return $response;
        };

        $send = function ($cmd, $expectCode = null) use ($socket, $expected) {
            fwrite($socket, $cmd . "\r\n");
            return $expectCode ? $expected($expectCode) : null;
        };

        $expected(220);
        $send("EHLO localhost", 250);
        $send("AUTH LOGIN", 334);
        $send(base64_encode($username), 334);
        $send(base64_encode($password), 235);
        $send("MAIL FROM:<$from>", 250);
        $send("RCPT TO:<$to>", 250);
        $send("DATA", 354);
        $send("Subject: $subject\r\n$email\r\n.", 250);
        $send("QUIT", 221);

        fclose($socket);

        return true;
    }
}
