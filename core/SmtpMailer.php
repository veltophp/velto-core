<?php

/**
 * Class SmtpMailer in namespace Velto\Core.
 *
 * Structure: Provides a static method `send()` for sending emails via SMTP.
 *
 * How it works:
 * - `send($to, $subject, $body)`:
 * - Retrieves SMTP server details and sender information from environment variables.
 * - Constructs email headers, including sender, reply-to, return path, X-Mailer, MIME version, and content type (HTML).
 * - Establishes an SMTP connection to the specified host and port using `fsockopen()`. Returns `false` on connection failure.
 * - Performs the SMTP handshake: EHLO, AUTH LOGIN (base64 encoding username and password), MAIL FROM, RCPT TO, DATA, sends the email subject, headers, and body, and finally QUIT.
 * - Reads server responses after each command (though responses aren't thoroughly checked for errors in this basic implementation).
 * - Closes the SMTP connection.
 * - Returns `true` if the process completes without a fatal connection error. Note that successful delivery is not guaranteed by this basic implementation.
 */

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
