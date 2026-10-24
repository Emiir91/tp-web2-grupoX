<?php
class Mailer
{
    private $host;
    private $user;
    private $pass;
    private $port;

    public function __construct($config)
    {
        // Guardamos las credenciales que vienen del config.ini
        $this->host = $config['smtp_host'];
        $this->user = $config['smtp_user'];
        $this->pass = $config['smtp_pass'];
        $this->port = $config['smtp_port'];
    }

    public function enviarCorreo($destinatario, $asunto, $cuerpo)
    {
        // Como no usamos Composer, requerimos los archivos de PHPMailer a mano.
        // Ajustá esta ruta si tu carpeta vendor está en otro lado.
        require_once __DIR__ . '/../vendor/phpMailer/Exception.php';
        require_once __DIR__ . '/../vendor/phpMailer/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpMailer/SMTP.php';

        // Usamos las clases de PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->user;
            $mail->Password = $this->pass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->port;

            // Configuración de codificación para aceptar tildes y ñ
            $mail->CharSet = 'UTF-8';

            // Remitente y Destinatario
            $mail->setFrom($this->user, 'Sistema de Registro');
            $mail->addAddress($destinatario);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $cuerpo;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Si falla, podemos registrar el error o devolver false
            error_log("Error al enviar correo: {$mail->ErrorInfo}");
            return false;
        }
    }
}