<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../config/email_config.php';

/**
 * Configurar instancia de PHPMailer
 */
function configurarPHPMailer() {
    $mail = new PHPMailer(true);
    
    try {
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPOptions = SMTP_OPTIONS;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->isHTML(true);
        
        return $mail;
    } catch (Exception $e) {
        error_log("Error al configurar PHPMailer: " . $e->getMessage());
        return false;
    }
}

/**
 * Enviar correo de bienvenida a nueva empresa registrada
 */
function enviarCorreoBienvenida($emailEmpresa, $nombreEmpresa, $tipoEmpresa, $passwordPlainText) {
    $mail = configurarPHPMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($emailEmpresa, $nombreEmpresa);
        $mail->Subject = '¡Bienvenido a la Rueda de Negocios Arica 2025!';
        
        $tipoTexto = $tipoEmpresa === 'grande' ? 'Empresa Grande' : 'Pyme';
        $panelUrl = BASE_URL . 'views/login.php';
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: linear-gradient(to right, #667eea, #764ba2); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>¡Bienvenido a la Rueda de Negocios!</h1>
                <p style='color: white; margin: 10px 0 0 0;'>28 de Noviembre, 2025 - Arica, Chile</p>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #667eea; margin-top: 0;'>Hola, {$nombreEmpresa}</h2>
                
                <p style='font-size: 16px; line-height: 1.6;'>
                    Tu registro como <strong>{$tipoTexto}</strong> ha sido exitoso. Ya puedes acceder a tu panel de control.
                </p>
                
                <div style='background: #f5f7ff; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #667eea;'>📧 Tus credenciales de acceso:</h3>
                    <p style='margin: 5px 0;'><strong>Email:</strong> {$emailEmpresa}</p>
                    <p style='margin: 5px 0;'><strong>Contraseña:</strong> {$passwordPlainText}</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #667eea, #764ba2); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        🚀 Acceder al Panel
                    </a>
                </div>
                                                
                <h3 style='color: #667eea; margin-top: 30px;'>¿Qué puedes hacer ahora?</h3>
                <ul style='line-height: 1.8;'>
                    " . ($tipoEmpresa === 'grande' ? "
                    <li>✅ Completar tu perfil empresarial</li>
                    <li>📅 Revisar tu agenda de bloques horarios</li>
                    <li>👥 Gestionar solicitudes de reuniones</li>
                    <li>✔️ Confirmar o rechazar reuniones</li>
                    " : "
                    <li>✅ Completar tu perfil y subir tu logo</li>
                    <li>🏢 Explorar empresas grandes disponibles</li>
                    <li>📅 Solicitar reuniones en bloques disponibles</li>
                    <li>👁️ Ver el estado de tus solicitudes</li>
                    ") . "
                </ul>
                
                <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                
                <p style='text-align: center; font-size: 14px; color: #666;'>
                    Si tienes dudas, contáctanos a <a href='mailto:contacto@bioceanicocentral.cl' style='color: #667eea;'>contacto@bioceanicocentral.cl</a>
                </p>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios Arica - Nodo Bioceánico Central<br>
                    Este es un correo automático, por favor no responder.
                </p>
            </div>
        </div>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo de bienvenida: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notificar a empresa grande sobre nueva solicitud de reunión
 */
function notificarSolicitudReunion($emailEmpresaGrande, $nombreEmpresaGrande, $nombrePyme, $fecha, $horaInicio, $horaFin, $notas = '') {
    $mail = configurarPHPMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($emailEmpresaGrande, $nombreEmpresaGrande);
        $mail->Subject = '🔔 Nueva solicitud de reunión - Rueda de Negocios';
        
        $panelUrl = BASE_URL . 'views/panel-empresa-a.php';
        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: linear-gradient(to right, #667eea, #764ba2); padding: 25px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>🔔 Nueva Solicitud de Reunión</h1>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #667eea;'>Hola, {$nombreEmpresaGrande}</h2>
                
                <p style='font-size: 16px;'>
                    <strong>{$nombrePyme}</strong> ha solicitado una reunión contigo en la Rueda de Negocios.
                </p>
                
                <div style='background: #fff3e0; border-left: 4px solid #ff9800; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin: 0 0 15px 0; color: #ff9800;'>📅 Detalles de la solicitud:</h3>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Empresa:</strong> {$nombrePyme}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Fecha:</strong> {$fechaFormateada}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Horario:</strong> {$horaInicio} - {$horaFin}</p>
                    " . (!empty($notas) ? "<p style='margin: 8px 0; font-size: 15px;'><strong>Mensaje:</strong><br><em>{$notas}</em></p>" : "") . "
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #667eea, #764ba2); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Ver y Gestionar Solicitud
                    </a>
                </div>
                
                <p style='color: #666; font-size: 14px; text-align: center;'>
                    Puedes aceptar o rechazar esta solicitud desde tu panel de control.
                </p>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios Arica
                </p>
            </div>
        </div>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar notificación de solicitud: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notificar a pyme sobre respuesta a su solicitud (confirmada o rechazada)
 */
function notificarRespuestaReunion($emailPyme, $nombrePyme, $nombreEmpresaGrande, $fecha, $horaInicio, $horaFin, $estado, $motivoRechazo = '') {
    $mail = configurarPHPMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($emailPyme, $nombrePyme);
        
        $esConfirmada = ($estado === 'confirmada');
        $panelUrl = BASE_URL . 'views/panel-empresa-b.php';
        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        
        if ($esConfirmada) {
            $mail->Subject = '✅ Reunión confirmada - Rueda de Negocios';
            $headerColor = 'linear-gradient(to right, #10b981, #059669)';
            $emoji = '✅';
            $titulo = 'Reunión Confirmada';
            $mensaje = "¡Excelente noticia! <strong>{$nombreEmpresaGrande}</strong> ha confirmado tu solicitud de reunión.";
            $accentColor = '#10b981';
        } else {
            $mail->Subject = '❌ Reunión rechazada - Rueda de Negocios';
            $headerColor = 'linear-gradient(to right, #ef4444, #dc2626)';
            $emoji = '❌';
            $titulo = 'Reunión No Aprobada';
            $mensaje = "<strong>{$nombreEmpresaGrande}</strong> no ha podido aprobar tu solicitud de reunión.";
            $accentColor = '#ef4444';
        }
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: {$headerColor}; padding: 25px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>{$emoji} {$titulo}</h1>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: {$accentColor};'>Hola, {$nombrePyme}</h2>
                
                <p style='font-size: 16px;'>
                    {$mensaje}
                </p>
                
                <div style='background: #f5f5f5; border-left: 4px solid {$accentColor}; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin: 0 0 15px 0; color: {$accentColor};'>📅 Detalles de la reunión:</h3>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Empresa:</strong> {$nombreEmpresaGrande}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Fecha:</strong> {$fechaFormateada}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Horario:</strong> {$horaInicio} - {$horaFin}</p>
                    " . (!$esConfirmada && !empty($motivoRechazo) ? "<p style='margin: 8px 0; font-size: 15px;'><strong>Motivo:</strong><br><em>{$motivoRechazo}</em></p>" : "") . "
                </div>
                
                " . ($esConfirmada ? "
                <div style='background: #d1fae5; border: 1px solid #10b981; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #065f46;'>
                        <strong>✓ ¡Prepárate!</strong> Revisa los detalles de tu reunión y prepara tu presentación.
                    </p>
                </div>
                " : "
                <div style='background: #fee2e2; border: 1px solid #ef4444; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #7f1d1d;'>
                        <strong>No te desanimes.</strong> Puedes solicitar otras reuniones con empresas disponibles.
                    </p>
                </div>
                ") . "
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #667eea, #764ba2); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Ver Mi Panel
                    </a>
                </div>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios Arica
                </p>
            </div>
        </div>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar notificación de respuesta: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notificar cancelación de reunión a ambas partes
 */
function notificarCancelacionReunion($email, $nombreEmpresa, $nombreOtraEmpresa, $fecha, $horaInicio, $horaFin) {
    $mail = configurarPHPMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($email, $nombreEmpresa);
        $mail->Subject = '🚫 Reunión cancelada - Rueda de Negocios';
        
        $panelUrl = BASE_URL . 'views/login.php';
        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: linear-gradient(to right, #6b7280, #4b5563); padding: 25px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>🚫 Reunión Cancelada</h1>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #6b7280;'>Hola, {$nombreEmpresa}</h2>
                
                <p style='font-size: 16px;'>
                    Lamentamos informarte que la reunión con <strong>{$nombreOtraEmpresa}</strong> ha sido cancelada.
                </p>
                
                <div style='background: #f3f4f6; border-left: 4px solid #6b7280; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin: 0 0 15px 0; color: #6b7280;'>📅 Detalles de la reunión cancelada:</h3>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Empresa:</strong> {$nombreOtraEmpresa}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Fecha:</strong> {$fechaFormateada}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Horario:</strong> {$horaInicio} - {$horaFin}</p>
                </div>
                
                <div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #92400e;'>
                        <strong>💡 Sugerencia:</strong> Puedes solicitar una nueva reunión en otro horario disponible.
                    </p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #667eea, #764ba2); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Ir a Mi Panel
                    </a>
                </div>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios Arica
                </p>
            </div>
        </div>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar notificación de cancelación: " . $mail->ErrorInfo);
        return false;
    }
}
?>