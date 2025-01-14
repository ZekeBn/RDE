<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "30";
$submodulo = "632";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require("../../clases/phpqrcode/qrlib.php");
// include('../lib/full/qrlib.php');

$idmesa = intval($_GET['id']);
$consulta = "
	select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon
	from mesas 
	inner join salon on salon.idsalon = mesas.idsalon
	where 
	 estadoex = 1 
	 and idmesa = $idmesa
	order by mesas.numero_mesa asc, salon.nombre asc
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$numero_mesa = intval($rs->fields['numero_mesa']);


$consulta = "
	select * from empresas limit 1
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$url_sistema = strtolower(trim($_SERVER['SERVER_NAME']));
//echo $url_sistema;exit;
$url = 'https://'.$url_sistema.'/mesas_qr/mesas_qr.php?id_mesa='.$idmesa;
// mesas_qr/mesas_qr2.php?id_mesa=121

$dataText = $url;
$saveToFile = false; // nombre del archivo o false para no guardar



/*
// SAVE TO FILE
// TEXT | FILENAME | CORRECT LEVEL | PIXEL PER POINT | OUTER FRAME SIZE | PRINT + SAVE | BACKGROUND COLOR | FOREGROUND COLOR
QRcode::png("https://code-boxx.com", false, 'h', 6, 2, false, 0x000000, 0xFFFFFF);
1. The text you want to encode.
2. The filename to save to, enter false to directly output the image to the browser.
3. Error correction level – L, M, Q, H. (Low, medium, quartile, high)
4. Pixel per point (affects the size of QR code)
5. Outer frame size (size of the border surrounding the QR code)
6. Print and save (true or false). When set to true, it will both save to a file and output as an image.
7. The background color, in RGB hex code.
8. The foreground color, in RGB hex code.
formatos de imagen:
QRcode::svg($dataText, $saveToFile, $saveToFile, QR_ECLEVEL_L, $imageWidth);
QRcode::png($dataText, $saveToFile, QR_ECLEVEL_L, 1200);
*/

$descarga = strtoupper(substr($_GET['desc'], 0, 1));
if ($descarga != 'S') {
    // outputs image directly into browser, as PNG stream
    QRcode::png($dataText, $saveToFile, QR_ECLEVEL_H, 1200);
} else {
    $datetime = date("YmdHis");
    $fichero = 'qr_kar_'.$numero_mesa.'_'.$datetime.'.png';
    QRcode::png($dataText, $fichero, QR_ECLEVEL_H, 1200);


    if (file_exists($fichero)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($fichero).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fichero));
        readfile($fichero);

        // borra el archivo
        unlink($fichero);
        exit;
    } else {
        echo "Hubo un problema y la imagen no se pudo crear, verifique permisos del fichero.";
        exit;
    }


}
