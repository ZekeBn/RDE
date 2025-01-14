 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "278";
require_once  '../clases/mpdf/vendor/autoload.php';
require_once("includes/rsusuario.php");

require_once("includes/funciones_cobros.php");




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
$idcuentaclientepagcab = intval($_GET['id']);


$consulta = "
select * 
from cuentas_clientes_pagos_cab 
where 
idcuentaclientepagcab = $idcuentaclientepagcab
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcuentaclientepagcab = $rs->fields['idcuentaclientepagcab'];


$factura_auto = recibo_pago($idcuentaclientepagcab);

$factura_auto = utf8_encode($factura_auto);




require_once  '../clases/mpdf/vendor/autoload.php';











//echo $html;exit;

$mpdf = new mPDF('', 'A4', 0, 0, 0, 0, 0, 0);
//$mpdf=new mPDF('utf-8', array(800,1280)); // ancho , alto
//$mpdf = new mPDF('','A4',55,'dejavusans');
//$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);

$factura_auto = preparePreText($factura_auto);


$html = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Vista Previa Factura</title>
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
<pre>'.$factura_auto.'</pre>
</div>
</body>
</html>
';










$mpdf->SetWatermarkText('');
$mpdf->showWatermarkText = false;




$mini = date('dmYHis');
$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit = 1;
//$mpdf->shrink_tables_to_fit = 2.5;
// Write some HTML code:
$mpdf->WriteHTML($html);
$mpdf->showImageErrors = true;
// Output a PDF file
$mpdf->Output($archivopdf, 'I'); // mostrar en el navegador
//$mpdf->Output($archivopdf,'D');  // // descargar directamente
//$mpdf->Output('facturas_tmp/'.$archivopdf,'F'); // guardar archivo en el servidor


// una vez enviado el mail borra todos los archivos temporales
/*$files = glob('facturas_tmp/*'); // obtiene todos los archivos
foreach($files as $file){
  if(is_file($file)) // si se trata de un archivo
    unlink($file); // lo elimina
}*/

exit;

?>
