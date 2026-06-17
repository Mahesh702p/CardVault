<?php
/**
 * Simple SMTP Mailer Service (No external dependencies)
 */
class MailService {
    public static function send(string $to, string $subject, string $messageHtml): bool {
        $host = $_ENV['SMTP_HOST'] ?? '';
        $port = (int)($_ENV['SMTP_PORT'] ?? 587);
        $user = $_ENV['SMTP_USER'] ?? '';
        $pass = $_ENV['SMTP_PASS'] ?? '';
        $fromEmail = $_ENV['SMTP_FROM'] ?? 'no-reply@vcard.net4hgc.in';
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'CardVault';

        // If SMTP configuration is missing, fall back to PHP's built-in mail() function
        if (empty($host) || empty($user) || empty($pass)) {
            $headers = "MIME-Version: 1.0\r\n" .
                       "Content-type: text/html; charset=utf-8\r\n" .
                       "From: {$fromName} <{$fromEmail}>\r\n" .
                       "Reply-To: {$fromEmail}\r\n" .
                       "X-Mailer: PHP/" . phpversion();
            return @mail($to, $subject, $messageHtml, $headers);
        }

        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            // Connect using TCP connection
            $socket = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                15,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$socket) {
                error_log("SMTP connection failed: {$errstr} ({$errno})");
                return false;
            }

            $read = function($socket) {
                $data = '';
                while ($str = fgets($socket, 515)) {
                    $data .= $str;
                    if (substr($str, 3, 1) === ' ') {
                        break;
                    }
                }
                return $data;
            };

            $write = function($socket, $cmd) {
                fputs($socket, $cmd . "\r\n");
            };

            $read($socket); // banner

            $write($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $read($socket);

            if ($port === 587) {
                $write($socket, "STARTTLS");
                $res = $read($socket);
                if (strpos($res, '220') === false) {
                    error_log("STARTTLS failed: " . $res);
                    fclose($socket);
                    return false;
                }
                // Upgrade connection to TLS
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log("TLS encryption handshake failed");
                    fclose($socket);
                    return false;
                }
                // Re-send EHLO after TLS upgrade
                $write($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $read($socket);
            }

            $write($socket, "AUTH LOGIN");
            $read($socket);

            $write($socket, base64_encode($user));
            $read($socket);

            $write($socket, base64_encode($pass));
            $res = $read($socket);
            if (strpos($res, '235') === false) {
                error_log("SMTP authentication failed: " . $res);
                fclose($socket);
                return false;
            }

            $write($socket, "MAIL FROM: <{$fromEmail}>");
            $read($socket);

            $write($socket, "RCPT TO: <{$to}>");
            $read($socket);

            $write($socket, "DATA");
            $read($socket);

            $subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $fromNameEncoded = "=?UTF-8?B?" . base64_encode($fromName) . "?=";

            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "From: {$fromNameEncoded} <{$fromEmail}>",
                "To: <{$to}>",
                "Subject: {$subjectEncoded}",
                "Date: " . date('r'),
                "X-Mailer: PHP-SMTP-Mailer",
                "",
                $messageHtml
            ];

            $write($socket, implode("\r\n", $headers));
            $write($socket, "\r\n.");
            $read($socket);

            $write($socket, "QUIT");
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            error_log("SMTP Mail Error: " . $e->getMessage());
            return false;
        }
    }
}
