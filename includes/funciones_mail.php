 <?php
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../clases/PHPMailer-master/src/Exception.php';
require '../clases/PHPMailer-master/src/PHPMailer.php';
require '../clases/PHPMailer-master/src/SMTP.php';
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviar_email($parametros_array)
{


    //use PHPMailer\PHPMailer\PHPMailer;
    //use PHPMailer\PHPMailer\Exception;
    require '../clases/PHPMailer-master/src/Exception.php';
    require '../clases/PHPMailer-master/src/PHPMailer.php';
    require '../clases/PHPMailer-master/src/SMTP.php';

    global $conexion;

    // si envio parametros de conexion de serv correo omite la bd
    if ($parametros_array['con_especial'] == 'S') {
        $con_parametros_array = $parametros_array['con_parametros'];
    } else {
        // trae mail de tipo uso general
        $consulta = "
        SELECT * FROM mails_sistema where idtipousomail = 1 and estado = 1 limit 1
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $con_parametros_array = [
            'username' => $rs->fields['username'],
            'password' => $rs->fields['password'],
            'host' => $rs->fields['host'],
            'port' => $rs->fields['port'],
            'from' => $rs->fields['from_mail'],
        ];
    }

    // parametros de conexion servidor de correo
    $username = $con_parametros_array['username'];
    $password = $con_parametros_array['password'];
    $host = $con_parametros_array['host'];
    $port = $con_parametros_array['port'];
    $from = $con_parametros_array['from'];

    // parametros envio
    $texto = $parametros_array['body'];
    $fromName = $parametros_array['fromName'];
    $subject = $parametros_array['subject'];
    $correos_csv = explode(";", trim($parametros_array['correos_csv']));
    $adjunto = $parametros_array['adjunto'];



    $StrBody = "";
    $StrBody .= $texto;

    //require("includes/configmail.php"); //
    $mail = new PHPMailer();
    $mail->IsSMTP();  // set mailer to use SMTP
    $mail->SMTPAuth = true;     // turn on SMTP authentication
    //$mail->SMTPDebug  = 1;
    $mail->SMTPSecure = "tls";
    $mail->Mailer = "smtp";
    $mail->Host = $host;  // smtp host
    $mail->Port = $port;

    $mail->Username = $username;  // dominio\usuario
    $mail->Password = $password;
    $mail->From = $from;
    $mail->FromName = $fromName;
    $mail->IsHTML(true);
    $mail->ContentType = "text/html";
    $mail->CharSet = "iso-8859-1";
    $mail->Subject = $subject;
    $mail->Body = $StrBody;
    if ($adjunto != '') {
        $mail->AddAttachment($adjunto);
    }


    foreach ($correos_csv as $correo) {
        if (trim($correo) != '') {
            $mail->AddAddress($correo, $correo);
        }
    }
    if (!$mail->Send()) {
        echo("Mailer Error: " . $mail->ErrorInfo);
        $res = [
            'valido' => 'N',
            'errores' => "Mailer Error: " . $mail->ErrorInfo
        ];
    } else {
        $res = [
            'valido' => 'S',
            'errores' => ''
        ];
    }

    return $res;
}
/*
// ejemplo envio ver ene test_mail.php

// parametros especiales solo se usa cuando con_especial = S
$con_parametros_array=array(
    'username' => 'sistema@innovasys.com.py',
    'password' => 'mMIuwJGsKBQ-',
    'host' => 'mail.innovasys.com.py',
    'port' => '587',
);


// parametros para enviar correo
$parametros_array=array(
    'from' => 'sistema@servidor.com.py',
    'fromName' => 'SISTEMA',
    'subject' => 'ASUNTO DE PRUEBA',
    'body' => 'HOLA MUNDO 4',
    'correos_csv' => 'omardalbert@gmail.com;josesotto@gmail.com',
    'con_especial' => 'N', // S para enviar parametros especiales de correo
    'con_parametros' => $con_parametros_array

);
// enviar correo
$res=enviar_email($parametros_array);

*/
?>
