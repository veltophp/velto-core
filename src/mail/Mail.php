<?php

namespace Velto\Core\Mail;

use Velto\Core\Env\Env;

class Mail
{
    public static function send($to, $subject, $view, $data = [])
    {
        $log = []; 

        $host       = Env::get('MAIL_HOST');
        $port       = Env::get('MAIL_PORT');
        $username   = Env::get('MAIL_USERNAME');
        $password   = Env::get('MAIL_PASSWORD');
        $from       = Env::get('MAIL_FROM_ADDRESS');
        $fromName   = Env::get('MAIL_FROM_NAME');
        $encryption = strtolower(Env::get('MAIL_ENCRYPTION'));

        
        if ($view === null && isset($data['body'])) {
            $body = $data['body'];
        } else {
            ob_start();
            extract($data);
            $path = BASE_PATH . "/resources/Views/mails/{$view}.vel.php";

            if (!file_exists($path)) {
                ob_end_clean();
                velto_log("❌ View not found: $path");
                return false;
            }

            require $path;
            $body = ob_get_clean();
        }

        
        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: {$fromName} <{$from}>",
            "Reply-To: {$from}",
            "Subject: {$subject}",
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        $transport = ($encryption === 'ssl') ? "ssl://" : '';
        $socket = fsockopen($transport . $host, $port, $errno, $errstr, 10);

        if (!$socket) {
            $log[] = "❌ Connection failed: $errstr ($errno)";
            velto_log(implode("\n", $log));
            return false;
        }

        $read = function () use ($socket) {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            return trim($response);
        };

        $write = function ($cmd) use ($socket) {
            fwrite($socket, $cmd . "\r\n");
        };

        $log[] = "🔌 CONNECT: " . $read();
        $write("EHLO localhost");
        $log[] = "👋 EHLO: " . $read();

        if ($encryption === 'tls') {
            $write("STARTTLS");
            $starttlsResp = $read();
            $log[] = "🔒 STARTTLS: $starttlsResp";

            if (!str_starts_with($starttlsResp, '220')) {
                fclose($socket);
                $log[] = "❌ STARTTLS gagal";
                velto_log(implode("\n", $log));
                return false;
            }

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                $log[] = "❌ Gagal mengaktifkan TLS";
                velto_log(implode("\n", $log));
                return false;
            }

            $write("EHLO localhost");
            $log[] = "🔁 EHLO after TLS: " . $read();
        }

        $write("AUTH LOGIN");
        $log[] = "🔐 AUTH: " . $read();

        $write(base64_encode($username));
        $log[] = "📧 USERNAME: " . $read();

        $write(base64_encode($password));
        $auth = $read();
        $log[] = "🔑 PASSWORD: $auth";

        if (!str_starts_with($auth, '235')) {
            fclose($socket);
            $log[] = "❌ SMTP Auth failed";
            velto_log(implode("\n", $log));
            return false;
        }

        $write("MAIL FROM:<$from>");
        $log[] = "📤 MAIL FROM: " . $read();

        $write("RCPT TO:<$to>");
        $log[] = "📥 RCPT TO: " . $read();

        $write("DATA");
        $log[] = "📝 DATA: " . $read();

        $write($message . "\r\n.");
        $log[] = "📨 SEND: " . $read();

        $write("QUIT");
        $log[] = "👋 QUIT: " . $read();

        fclose($socket);

        velto_log(implode("\n", $log));
        return true;
    }
}
