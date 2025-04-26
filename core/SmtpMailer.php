<?php

namespace Velto\Core;

class SmtpMailer
{
    public static function send($to, $subject, $body)
    {
        $host = Env::get('MAIL_HOST');
        $port = Env::get('MAIL_PORT');
        $username = Env::get('MAIL_USERNAME');
        $password = Env::get('MAIL_PASSWORD');
        $from = Env::get('MAIL_FROM_ADDRESS');
        $fromName = Env::get('MAIL_FROM_NAME');

        $headers = "From: $fromName <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "Return-Path: $from\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";


        $smtp = fsockopen($host, $port, $errno, $errstr, 10);
        if (!$smtp) {
            return false;
        }

        $server_response = fgets($smtp, 515);
        fputs($smtp, "EHLO $host\r\n");
        fgets($smtp, 515);

        fputs($smtp, "AUTH LOGIN\r\n");
        fgets($smtp, 515);

        fputs($smtp, base64_encode($username) . "\r\n");
        fgets($smtp, 515);

        fputs($smtp, base64_encode($password) . "\r\n");
        fgets($smtp, 515);

        fputs($smtp, "MAIL FROM: <$from>\r\n");
        fgets($smtp, 515);

        fputs($smtp, "RCPT TO: <$to>\r\n");
        fgets($smtp, 515);

        fputs($smtp, "DATA\r\n");
        fgets($smtp, 515);

        fputs($smtp, "Subject: $subject\r\n$headers\r\n\r\n$body\r\n.\r\n");
        fgets($smtp, 515);

        fputs($smtp, "QUIT\r\n");
        fclose($smtp);

        return true;
    }
}
