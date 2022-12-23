<?php
namespace PlatePHP\PlateFramework;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PlatePHP\PlateFramework\Exceptions\InternalServerException;

class MailClient {
    public string $host;
    public int $port;
    public string $username;
    public string $password;

    public function __construct(string $host, int $port, string $username, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Sends an email with the current credentials to a target with a given subject and optional body.
     * @param string $email
     * @param string $subject
     * @param string|null $body
     * @return bool
     * @throws InternalServerException
     */
    public function sendMail(string $email, string $subject, ?string $body): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->port;
            $mail->setFrom($this->username);
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->isHTML();
            if(!empty($body)) {
                $mail->Body = $body;
            }
            $mail->send();
            return true;
        } catch (Exception) {
            throw new InternalServerException($mail->ErrorInfo);
        }
    }
}