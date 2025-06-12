<?php

/**
 * Class Mail in namespace Velto\Core.
 *
 * Structure: Provides a static method `send()` for sending emails using SMTP.
 *
 * How it works:
 * - `send($to, $subject, $view, $data = [])`:
 * - Retrieves email configuration from environment variables (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME).
 * - Loads the email body content by rendering a specified view file (`.vel.php`) located in the `views/mails/` directory, extracting provided `$data` into the view's scope.
 * - Constructs email headers, including MIME version, content type (HTML), sender information (From and Reply-To).
 * - Creates the full email message by combining the headers and the rendered body.
 * - Establishes an SMTP connection to the specified host and port using `fsockopen()`. Returns `false` if the connection fails.
 * - Defines helper closures `$expected` to read SMTP server responses and `$send` to send commands and optionally check for expected response codes.
 * - Performs the SMTP handshake: EHLO, AUTH LOGIN (base64 encoding username and password), MAIL FROM, RCPT TO, DATA, sends the email content (including Subject), and QUIT.
 * - Closes the SMTP socket using `fclose()`.
 * - Returns `true` if the email sending process completes without fatal errors, `false` if the initial socket connection fails. Note that this basic implementation doesn't have robust error checking for each SMTP command.
 */

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

        ob_start();
        extract($data);

        if (str_contains($view, '::')) {
            [$namespace, $view] = explode('::', $view, 2);
            if ($namespace === 'axion') {
                $path = BASE_PATH . "/axion/views/mails/{$view}.vel.php";
            } else {
                abort(404, message:'Path mails not found!');
            }
        } else {
            $path = BASE_PATH . "/views/mails/{$view}.vel.php";
        }
        if (file_exists($path)) {
            require $path;
        } else {
            ob_end_clean();
            return false;
        }
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
