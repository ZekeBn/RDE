 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

// funciones para factura electronica
if ($facturador_electronico != 'S') {
    echo "Tu empresa no esta activada como facturador electronico.";
    exit;
}


$iddocumentoemitido = intval($_POST['id']);
$token_mail_electro = trim($_POST['token']);

if ($token_mail_electro != trim($_SESSION['token_mail_electro'])) {
    echo "Token Incorrecto!";
    exit;
}
if ($token_mail_electro == '') {
    echo "Token no enviado!";
    exit;
}
// borrar token
$_SESSION['token_mail_electro'] = null;
unset($_SESSION['token_mail_electro']);

// para enviar mail
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../clases/PHPMailer-master/src/Exception.php';
require '../clases/PHPMailer-master/src/PHPMailer.php';
require '../clases/PHPMailer-master/src/SMTP.php';


function enviomail($texto, $asunto, $archivo1, $archivo2, $mail_cliente)
{

    global $conexion;
    global $idempresa;
    global $nombreempresa;

    $valido = 'S';
    $enviado = 'N';
    $errores = '';

    $consulta = "
    select 
    mail_username, mail_password, mail_port,
    mail_host, mail_from, mail_fromName
    from  preferencias_electronica 
    where
    estado = 1
    order by idpref asc
    limit 1
    ";
    $rsprefelec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $mail_Username = trim($rsprefelec->fields['mail_username']);
    $mail_Password = trim($rsprefelec->fields['mail_password']);
    $mail_Port = trim($rsprefelec->fields['mail_port']);
    $mail_Host = trim($rsprefelec->fields['mail_host']);
    $mail_From = trim($rsprefelec->fields['mail_from']);
    $mail_FromName = trim($rsprefelec->fields['mail_fromName']);


    // conversiones
    if ($mail_FromName == '') {
        $mail_FromName = $nombreempresa;
    }

    // validaciones
    if ($mail_Username == '') {
        $valido = 'N';
        $errores .= ' No se indico el Username del correo.<br />';
    }
    if ($mail_Password == '') {
        $valido = 'N';
        $errores .= ' No se indico el Password del correo.<br />';
    }
    if ($mail_Port == '') {
        $valido = 'N';
        $errores .= ' No se indico el Port del correo.<br />';
    }
    if ($mail_Host == '') {
        $valido = 'N';
        $errores .= ' No se indico el Host del correo.<br />';
    }
    if ($mail_From == '') {
        $valido = 'N';
        $errores .= ' No se indico el From del correo.<br />';
    }
    if ($mail_FromName == '') {
        $valido = 'N';
        $errores .= ' No se indico el FromName del correo.<br />';
    }
    if (trim($mail_cliente) == '') {
        $valido = 'N';
        $errores .= ' No se indico el Mail de destino del correo.<br />';
    }


    if ($valido == 'S') {

        $StrBody .= $texto;
        $mail = new PHPMailer();
        $mail->SMTPDebug = 0;
        $mail->IsSMTP();                                      // set mailer to use SMTP
        $mail->Host = $mail_Host;  // smtp host
        $mail->Port = $mail_Port;
        //$mail->Host = "smtp.gmail.com"; // GMail
        //$mail->Port = 587;
        //$mail->IsSMTP(); // use SMTP
        //$mail->SMTPAuth = true;
        $mail->SMTPAuth = true;     // turn on SMTP authentication
        $mail->SMTPSecure = 'tls';
        $mail->Username = $mail_Username;  // dominio\usuario
        $mail->Password = $mail_Password;
        $mail->From = $mail_From;
        $mail->FromName = $mail_FromName;
        $mail->IsHTML(true);
        $mail->ContentType = "text/html";
        $mail->CharSet = "iso-8859-1";
        $mail->Subject = "$asunto";
        $mail->Body = $StrBody;
        $mail->AddAttachment($archivo1);
        $mail->AddAttachment($archivo2);
        $mail->AddAddress($mail_cliente, $mail_cliente);

        if (!$mail->Send()) {
            //return ("Mailer Error: " . $mail->ErrorInfo);
            $enviado = 'N';
            $errores .= "Mailer Error: " . $mail->ErrorInfo;
        } else {
            //return "OK";
            $enviado = 'S';
        }

    } else {
        $enviado = 'N';
    }

    $res = [
        'valido' => $valido,
        'enviado' => $enviado,
        'errores' => $errores
    ];

    return $res;

}


// para php7
if (!function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime($value)
    {
        return true;
    }
}
function wp_slash($value)
{
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[ $k ] = wp_slash($v);
            } else {
                $value[ $k ] = addslashes($v);
            }
        }
    } else {
        $value = addslashes($value);
    }

    return $value;
}




//incorporamos preferencias de caja
$buscar = "Select * from preferencias_caja limit 1";
$rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tipo_a4 = intval($rsbb->fields['tipo_a4']);
$leyenda_credito_a4 = trim($rsbb->fields['leyenda_credito_a4']);
$leyenda_contado_a4 = trim($rsbb->fields['leyenda_contado_a4']);


// documento emitido
$consulta = "
select iddocumentoemitido, iddocumentoelectronico, idventa, idnotacredito, idnotadebito, idnotaremision, idautofactura,
estado_set, qr, xml
from documentos_electronicos_emitidos
where
estado = 1
and iddocumentoemitido = $iddocumentoemitido
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddocumentoelectronico = intval($rs->fields['iddocumentoelectronico']);
$idventa = intval($rs->fields['idventa']);
$qr_set = $rs->fields['qr'];
$xml_set = $rs->fields['xml'];
if ($iddocumentoelectronico == 0) {
    echo "Documento inexistente!";
    exit;
}

//cabecera
$consulta = "
Select factura,ventas.idventa,recibo,ventas.razon_social,ventas.ruc,idpedido,ventas.idcliente as idunicocli,
(select telefono from cliente where idcliente = ventas.idcliente) as telefono,
(select direccion from cliente where idcliente = ventas.idcliente) as direccion,
total_cobrado,total_venta,otrosgs,fecha,tipo_venta,descneto,totaliva10,totaliva5,texe,
(select prox_vencimiento from cuentas_clientes where idventa = ventas.idventa) as factura_vto,
(select obs_varios from ventas_datosextra where idventa = ventas.idventa ) as obs_varios,
cliente.email as email_cliente, cliente.borrable
from ventas
inner join cliente on cliente.idcliente=ventas.idcliente
where 
idventa=$idventa
and ventas.estado <> 6
";
$rsvv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$email_cliente = strtolower(trim($rsvv->fields['email_cliente']));
$idventa = intval($rsvv->fields['idventa']);
$razon_social_cliente = htmlentities($rsvv->fields['razon_social']);
$ruc_cliente = htmlentities($rsvv->fields['ruc']);
$cliente_borrable = htmlentities($rsvv->fields['borrable']);
if ($cliente_borrable == 'N') {
    $cliente_generico = 'S';
} else {
    $cliente_generico = 'N';
}
if ($idventa == 0) {
    echo "La venta fue anulada.";
    exit;
}
if ($email_cliente == '') {
    // actualiza estado a sin correo
    $consulta = "
    update documentos_electronicos_emitidos 
    set 
    estado_enviocliente = 5,
    fecha_enviomail = '$ahora'
    where 
    iddocumentoemitido = $iddocumentoemitido
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo "El cliente no cuenta con ningun email registrado.";
    exit;
}
if ($cliente_generico == 'S') {
    // actualiza estado a no aplica
    $consulta = "
    update documentos_electronicos_emitidos 
    set 
    estado_enviocliente = 6,
    fecha_enviomail = '$ahora'
    where 
    iddocumentoemitido = $iddocumentoemitido
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo "Cliente generico, no es necesario enviar correo.";
    exit;
}

// auto impresor
$factura_auto = factura_autoimpresor($idventa);
$factura_auto = utf8_encode($factura_auto);
//echo $factura_auto;exit;

// separa el texto del QR
$texto_ar = explode('<QR>', $factura_auto);
$texto1 = $texto_ar[0].'';
$texto_despues = $texto_ar[1];
$texto_despues_ar = explode('</QR>', $texto_despues);
$texto_qr = $texto_despues_ar[0];
$texto2 = $texto_despues_ar[1];


require_once  '../clases/mpdf/vendor/autoload.php';


$mpdf = new mPDF('', 'A4', 0, 0, 0, 0, 0, 0);

$factura_auto1 = preparePreText($texto1);
$factura_auto_qr = '<div style="text-align: center;"><barcode code="'.$texto_qr.'" type="QR" class="barcode" size="1.6" error="H" disableborder="1" /></div>';
$factura_auto2 = preparePreText($texto2);


$html = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<style>
pre {
  display: block;
  font-family: monospace;
  white-space: pre;
  margin: 1em 0;
  font-size:10px;
} 
*{
    margin:0px;
    padding:0px;    
}
</style>
</head>

<body><br />
<div style="width:250px; margin:0px auto; border:1px solid #000000; padding:5px;">
<pre>'.$factura_auto1.$factura_auto_qr.$factura_auto2.'</pre>
</div>
</body>
</html>
';



//$mpdf=new mPDF('utf-8', array(800,1280)); // ancho , alto
//$mpdf = new mPDF('','A4',55,'dejavusans');
//$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
$mpdf->SetWatermarkText('');
$mpdf->showWatermarkText = false;




$mini = date('dmYHis');
$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit = 1;
//$mpdf->shrink_tables_to_fit = 2.5;
// Write some HTML code:
$mpdf->WriteHTML($html);
$mpdf->showImageErrors = true;

$archivo_pdf = 'documento_electronico_'.$iddocumentoemitido.'.pdf';
$archivo_xml = 'documento_electronico_'.$iddocumentoemitido.'.xml';
$archivo_pdf_ruta = 'facturas_tmp/'.$archivo_pdf;
$archivo_xml_ruta = 'facturas_tmp/'.$archivo_xml;

// crear archivo pdf fisicamente
$mpdf->Output($archivo_pdf_ruta, 'F');
//$mpdf->Output($archivopdf,'I'); // mostrar en el navegador
//$mpdf->Output($archivopdf,'D');  // // descargar directamente
//$mpdf->Output('facturas_tmp/'.$archivopdf,'F'); // guardar archivo en el servidor

// crear archivo xml fisicamente
$file = fopen($archivo_xml_ruta, "w+");
fwrite($file, $xml_set);
fclose($file);


// enviar mail


$consulta = "
select empresa, razon_social, ruc, dv, direccion, telefono 
from empresas 
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_emp = $rs->fields['razon_social'];
$ruc_emp = $rs->fields['ruc'].'-'.$rs->fields['dv'];
$direccion_emp = $rs->fields['direccion'];
$telefono_emp = $rs->fields['telefono'];

$asunto = $nombreempresa.' | DOCUMENTO ELECTRONICO';
$texto = "
<div style=\"border:1px solid #000;padding:10px;\">
<strong>$nombreempresa | $razon_social_emp (RUC: $ruc_emp)</strong><br />
<hr />
<strong>DOCUMENTO ELECTRÓNICO</strong><br />
Estimado Cliente:<br />
$razon_social_cliente | $ruc_cliente<br />
Usted ha recibido un documento electrónico emitido por  con plena validez jurídica e impositiva, una vez recibido puede ingresar al siguiente enlace: <a href=\"$qr_set\" target=\"_blank\">http://ekuatia.set.gov.py/consultas/</a> para consultar sobre el mismo, las consultas pueden hacerlo a través del código QR o por el código de control (CDC) contenidos en el documento remitido.<br />
<hr />
<strong>Direccion: </strong>$direccion_emp<br />
<strong>Telefono: </strong>$telefono_emp<br />
<hr />
Antes de imprimir este correo electrónico, piense bien si es necesario hacerlo: El medio ambiente es cuestión de todos.
<br />
</div>
<br /><br />
Documento Electronico enviado automáticamente a través del Sistema ".$rsco->fields['nombre_sys'].".<br />
<a href='".$rsco->fields['web_sys']."'>".$rsco->fields['web_sys']."</a> 
";
//echo $texto;exit; #602858

/***********  ENVIAR MAIL **********/
$res = enviomail($texto, $asunto, $archivo_pdf_ruta, $archivo_xml_ruta, $email_cliente);
/***********  ENVIAR MAIL **********/


// muestra resultado
if ($res['enviado'] == 'S') {
    // actualiza estado a enviado
    $consulta = "
    update documentos_electronicos_emitidos 
    set 
    estado_enviocliente = 2,
    fecha_enviomail = '$ahora'
    where 
    iddocumentoemitido = $iddocumentoemitido
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // una vez enviado el mail borra los archivos temporales
    unlink($archivo_pdf_ruta);
    unlink($archivo_xml_ruta);

    echo '<strong>Email Enviado!</strong>';
    exit;
} else {
    // actualiza estado a error
    $consulta = "
    update documentos_electronicos_emitidos 
    set 
    estado_enviocliente = 3
    where 
    iddocumentoemitido = $iddocumentoemitido
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // una vez enviado el mail borra los archivos temporales
    unlink($archivo_pdf_ruta);
    unlink($archivo_xml_ruta);

    echo $res['errores'];
    exit;
}




// una vez enviado el mail borra todos los archivos temporales
/*$files = glob('facturas_tmp/*'); // obtiene todos los archivos
foreach($files as $file){
  if(is_file($file)) // si se trata de un archivo
    unlink($file); // lo elimina
}*/

exit;



?>
