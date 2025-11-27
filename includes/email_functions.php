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
        $mail->Subject = '¡Bienvenido a la Rueda de Negocios Nodo Bioceánico 2025!';
        
        // Determinar instrucciones según el rol
        $esOferente = ($tipoEmpresa === 'pyme'); // pyme = oferente
        $esDemandante = ($tipoEmpresa === 'grande'); // grande = demandante
        
        $panelUrl = BASE_URL . 'views/login.php';
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: linear-gradient(to right, #1e40af, #2563eb); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>¡Bienvenido a la Rueda de Negocios!</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Nodo Bioceánico Central</p>
                <p style='color: white; margin: 5px 0 0 0;'>28 de Noviembre, 2025 | Arica, Chile</p>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #2563eb; margin-top: 0;'>Hola, {$nombreEmpresa}</h2>
                
                <p style='font-size: 16px; line-height: 1.6;'>
                    ¡Tu registro ha sido exitoso! Ya eres parte de la Rueda de Negocios del Nodo Bioceánico Central.
                </p>
                
                <div style='background: #eff6ff; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #2563eb;'>📧 Tus credenciales de acceso:</h3>
                    <p style='margin: 5px 0;'><strong>Usuario:</strong> {$emailEmpresa}</p>
                    <p style='margin: 5px 0;'><strong>Contraseña:</strong> {$passwordPlainText}</p>
                    <p style='margin: 10px 0 0 0; font-size: 14px; color: #1e40af;'>
                        <em>💡 Te recomendamos cambiar tu contraseña después del primer ingreso</em>
                    </p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #1e40af, #2563eb); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px;'>
                        🚀 Acceder a Mi Panel
                    </a>
                </div>
                
                <div style='background: #f0fdf4; border: 1px solid #10b981; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                    <h3 style='color: #059669; margin-top: 0;'>✨ Próximos pasos:</h3>
                    <ul style='line-height: 2; margin: 10px 0; padding-left: 20px;'>
                        <li><strong>Completa tu perfil</strong> con logo y descripción de tu empresa</li>
                        " . ($esDemandante ? "
                        <li><strong>Revisa tu agenda</strong> y los bloques horarios disponibles</li>
                        <li><strong>Gestiona solicitudes</strong> de reuniones que recibas</li>
                        " : "") . "
                        " . ($esOferente ? "
                        <li><strong>Explora las empresas</strong> participantes del evento</li>
                        <li><strong>Solicita reuniones</strong> en los horarios disponibles</li>
                        " : "") . "
                        <li><strong>Prepara tu presentación</strong> para las reuniones</li>
                    </ul>
                </div>
                
                <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                    <p style='margin: 0; color: #92400e; font-size: 14px;'>
                        <strong>⏰ Fecha del evento:</strong> 28 de Noviembre, 2025<br>
                        <strong>📍 Lugar:</strong> Por confirmar<br>
                        <strong>🕐 Horario:</strong> 11:00 AM - 12:30 PM
                    </p>
                </div>
                
                <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                
                <p style='text-align: center; font-size: 14px; color: #666;'>
                    ¿Necesitas ayuda? Contáctanos a<br>
                    <a href='mailto:contacto@bioceanicocentral.cl' style='color: #2563eb; text-decoration: none; font-weight: bold;'>contacto@bioceanicocentral.cl</a>
                </p>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0 0 5px 0; font-size: 13px; color: #666;'>
                    <strong>Rueda de Negocios - Nodo Bioceánico Central 2025</strong>
                </p>
                <p style='margin: 5px 0; font-size: 11px; color: #999;'>
                    Este es un correo automático, por favor no responder directamente.
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
 * Notificar a empresa demandante sobre nueva solicitud de reunión
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
            <div style='background: linear-gradient(to right, #1e40af, #2563eb); padding: 25px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>🔔 Nueva Solicitud de Reunión</h1>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #2563eb;'>Hola, {$nombreEmpresaGrande}</h2>
                
                <p style='font-size: 16px; line-height: 1.6;'>
                    <strong>{$nombrePyme}</strong> está interesado en reunirse contigo durante la Rueda de Negocios.
                </p>
                
                <div style='background: #fff7ed; border-left: 4px solid #f97316; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin: 0 0 15px 0; color: #ea580c;'>📅 Detalles de la solicitud:</h3>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Empresa:</strong> {$nombrePyme}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Fecha:</strong> {$fechaFormateada}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Horario:</strong> {$horaInicio} - {$horaFin}</p>
                    " . (!empty($notas) ? "
                    <div style='background: white; border: 1px solid #fed7aa; border-radius: 5px; padding: 12px; margin-top: 12px;'>
                        <p style='margin: 0; font-size: 14px;'><strong>Mensaje:</strong></p>
                        <p style='margin: 8px 0 0 0; font-size: 14px; color: #666;'><em>{$notas}</em></p>
                    </div>
                    " : "") . "
                </div>
                
                <div style='background: #eff6ff; border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #1e3a8a; font-size: 14px;'>
                        <strong>⚡ Acción requerida:</strong> Ingresa a tu panel para revisar los detalles y gestionar esta solicitud.
                    </p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #1e40af, #2563eb); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Ver y Gestionar Solicitud
                    </a>
                </div>
                
                <p style='color: #666; font-size: 13px; text-align: center; line-height: 1.5;'>
                    Podrás aceptar o rechazar esta solicitud desde tu panel de control.<br>
                    Te recomendamos revisar el perfil de la empresa antes de tomar una decisión.
                </p>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios - Nodo Bioceánico Central
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
 * Notificar a empresa oferente sobre respuesta a su solicitud (confirmada o rechazada)
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
            $mail->Subject = '✅ ¡Reunión confirmada! - Rueda de Negocios';
            $headerColor = 'linear-gradient(to right, #059669, #10b981)';
            $emoji = '✅';
            $titulo = '¡Reunión Confirmada!';
            $mensaje = "¡Excelentes noticias! <strong>{$nombreEmpresaGrande}</strong> ha aceptado tu solicitud de reunión.";
            $accentColor = '#059669';
        } else {
            $mail->Subject = '❌ Solicitud no aprobada - Rueda de Negocios';
            $headerColor = 'linear-gradient(to right, #dc2626, #ef4444)';
            $emoji = '❌';
            $titulo = 'Solicitud No Aprobada';
            $mensaje = "<strong>{$nombreEmpresaGrande}</strong> no ha podido aprobar tu solicitud de reunión en este horario.";
            $accentColor = '#dc2626';
        }
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: {$headerColor}; padding: 25px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>{$emoji} {$titulo}</h1>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: {$accentColor};'>Hola, {$nombrePyme}</h2>
                
                <p style='font-size: 16px; line-height: 1.6;'>
                    {$mensaje}
                </p>
                
                <div style='background: #f5f5f5; border-left: 4px solid {$accentColor}; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin: 0 0 15px 0; color: {$accentColor};'>📅 Detalles de la reunión:</h3>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Empresa:</strong> {$nombreEmpresaGrande}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Fecha:</strong> {$fechaFormateada}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Horario:</strong> {$horaInicio} - {$horaFin}</p>
                    " . (!$esConfirmada && !empty($motivoRechazo) ? "
                    <div style='background: white; border: 1px solid #fecaca; border-radius: 5px; padding: 12px; margin-top: 12px;'>
                        <p style='margin: 0; font-size: 14px; color: #7f1d1d;'><strong>Motivo:</strong></p>
                        <p style='margin: 8px 0 0 0; font-size: 14px; color: #666;'><em>{$motivoRechazo}</em></p>
                    </div>
                    " : "") . "
                </div>
                
                " . ($esConfirmada ? "
                <div style='background: #d1fae5; border: 1px solid #10b981; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #065f46;'>
                        <strong>✓ ¡Prepárate para la reunión!</strong><br>
                        <span style='font-size: 14px;'>Revisa los detalles, prepara tu presentación y llega puntual. ¡Mucho éxito!</span>
                    </p>
                </div>
                " : "
                <div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #92400e;'>
                        <strong>💡 No te desanimes</strong><br>
                        <span style='font-size: 14px;'>Hay más empresas interesadas. Revisa otros horarios disponibles y solicita nuevas reuniones.</span>
                    </p>
                </div>
                ") . "
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #1e40af, #2563eb); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        " . ($esConfirmada ? "Ver Detalles de la Reunión" : "Explorar Más Oportunidades") . "
                    </a>
                </div>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios - Nodo Bioceánico Central
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
                
                <p style='font-size: 16px; line-height: 1.6;'>
                    Lamentamos informarte que la reunión con <strong>{$nombreOtraEmpresa}</strong> ha sido cancelada.
                </p>
                
                <div style='background: #f3f4f6; border-left: 4px solid #6b7280; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin: 0 0 15px 0; color: #6b7280;'>📅 Detalles de la reunión cancelada:</h3>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Empresa:</strong> {$nombreOtraEmpresa}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Fecha:</strong> {$fechaFormateada}</p>
                    <p style='margin: 8px 0; font-size: 15px;'><strong>Horario:</strong> {$horaInicio} - {$horaFin}</p>
                </div>
                
                <div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #92400e;'>
                        <strong>💡 Próximos pasos:</strong><br>
                        <span style='font-size: 14px;'>Puedes solicitar una nueva reunión en otro horario disponible o con otra empresa del evento.</span>
                    </p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$panelUrl}' style='background: linear-gradient(to right, #1e40af, #2563eb); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Ir a Mi Panel
                    </a>
                </div>
            </div>
            
            <div style='background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 12px; color: #666;'>
                    © 2025 Rueda de Negocios - Nodo Bioceánico Central
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

/**
 * Enviar correo de seguimiento a empresas preinscritas en el formulario externo
 */
function enviarCorreoSeguimientoInscripcion($emailEmpresa, $nombreEmpresa, $mensajePersonalizado, $asunto = null) {
    $mail = configurarPHPMailer();
    if (!$mail) return false;

    $asuntoFinal = $asunto ?: 'Completa tu registro para la Rueda de Negocios Bioceánica';
    $registroUrl = BASE_URL . 'views/registro.php';

    try {
        $mail->addAddress($emailEmpresa, $nombreEmpresa);
        $mail->Subject = $asuntoFinal;

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #111827; max-width: 680px; margin: auto; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 15px 45px rgba(37, 99, 235, 0.12);'>
            <div style='padding: 30px 30px 10px;'>
                <p style='font-size: 16px; line-height: 1.7; margin: 0 0 14px;'>Hola <strong>{$nombreEmpresa}</strong>,</p>
                <p style='font-size: 15px; line-height: 1.7; margin: 0 0 16px;'>Ya quedan pocos dias!!, recuerda completar tu registro y participar en la mesa logistica comercial mas importante del conosur. No te quedes fuera, ya son mas de 35 empresas que participaran de esta actividad. Completa tu registro ya!</p>

                <div style='background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                    <p style='margin: 0; color: #92400e; font-weight: 700;'>Beneficios de confirmar ahora:</p>
                    <ul style='margin: 12px 0 0 20px; padding: 0; color: #92400e; line-height: 1.6; font-size: 14px;'>
                        <li>Asegura tu cupo antes de que se complete el aforo del evento bioceánico.</li>
                        <li>Accede primero a los mejores horarios para reuniones estratégicas.</li>
                    </ul>
                </div>

                <div style='text-align: center; margin: 26px 0;'>
                    <a href='{$registroUrl}' style='background: linear-gradient(to right, #f97316, #fb923c); color: #fff; padding: 15px 40px; text-decoration: none; border-radius: 12px; font-weight: 800; display: inline-block; box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3); letter-spacing: 0.01em;'>
                        Reservar mi cupo ahora
                    </a>
                    <p style='margin: 12px 0 0; font-size: 13px; color: #6b7280;'>Toma menos de 3 minutos y puedes volver cuando quieras.</p>
                </div>

                <p style='font-size: 14px; color: #4b5563; line-height: 1.6; margin: 0;'>Si ya completaste el registro, ¡gracias! Ignora este mensaje. Si no, completa tu inscripción hoy para que no te pierdas el gran evento del Corredor Bioceánico y su vitrina internacional.</p>
            </div>

            <div style='background: #0f172a; color: #cbd5e1; padding: 18px; text-align: center; border-top: 1px solid #1e293b;'>
                <p style='margin: 0; font-size: 12px;'>© 2025 Rueda de Negocios - Nodo Bioceánico Central</p>
                <p style='margin: 6px 0 0 0; font-size: 12px; color: #94a3b8;'>Este es un correo automático, por favor no responder directamente.</p>
            </div>
        </div>
        ";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar seguimiento de inscripción: " . $mail->ErrorInfo);
        return false;
    }
}
?>