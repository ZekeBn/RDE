 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once  '../clases/mpdf/vendor/autoload.php';
require_once("includes/rsusuario.php");

require_once("includes/funciones_cocina.php");


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

if (intval($_GET['v']) > 0) {
    $venta = intval($_GET['v']);
}
if (intval($_GET['vta']) > 0) {
    $venta = intval($_GET['vta']);
}
$idventa = $venta;


//cabecera
$consulta = "
Select factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,ventas.idcliente as idunicocli,
    (select telefono from cliente where idcliente = ventas.idcliente) as telefono,
    (select direccion from cliente where idcliente = ventas.idcliente) as direccion,
    total_cobrado,total_venta,otrosgs,fecha,tipo_venta,descneto,totaliva10,totaliva5,texe,idmesa, ventas.sucursal
from ventas
inner join cliente on cliente.idcliente=ventas.idcliente
where 
cliente.idempresa=$idempresa 
and ventas.idempresa=$idempresa 
and idventa=$venta
and ventas.estado <> 6
";
$rsvv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idventa = intval($rsvv->fields['idventa']);
if ($idventa == 0) {
    echo "La venta fue anulada.";
    exit;
}
$idsucursal = intval($rsvv->fields['sucursal']);
$idpedido = intval($rsvv->fields['idpedido']);
$idped = $idpedido;//para motor de impresion
$idventa = intval($rsvv->fields['idventa']);
$idmesa = intval($rsvv->fields['idmesa']);
// auto impresor
//$factura_auto=factura_autoimpresor($idventa);
//$factura_auto=utf8_encode($factura_auto);


//Preferencias
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$contado_txt = trim($rspref->fields['contado_txt']);
$credito_txt = trim($rspref->fields['credito_txt']);
$factura_pred = trim($rspref->fields['factura_pred']);
$autoimpresor = trim($rspref->fields['autoimpresor']);
$forzar_agrupacion = trim($rspref->fields['forzar_agrupacion']);
$anteponer_moneda_fact = trim($rspref->fields['anteponer_moneda_fact']);
$ticket_fox = trim($rspref->fields['ticket_fox']);
$comanda_o_tk = trim($rspref->fields['comanda_o_tk']);

require_once  '../clases/mpdf/vendor/autoload.php';




// trae la primera impresora
$consulta = "
SELECT * 
FROM impresoratk 
where 
idsucursal = $idsucursal 
and borrado = 'N' 
and tipo_impresora='CAJ' 
order by idimpresoratk  asc
limit 1
";
//echo $consulta;exit;
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}
//echo $comanda_o_tk;exit;
$texto_json = "";
if ($comanda_o_tk == 'T') {
    $texto = ticket_venta($idventa);
} else {
    // ticket de cocina para caja
    $impresor_tip = "CAJ";
    if ($idmesa > 0) {
        $impresor_tip = "MES";
    }
    $parametros_array = [
        'idimpresoratk' => $rsimp->fields['idimpresoratk'],
        'idpedido' => $idpedido,
        'idmesa' => $idmesa,
        'impresor_tip' => $impresor_tip,
        'v' => $idventa
    ];
    //print_r($parametros_array);exit;
    $res = comanda_cocina_consolidado($parametros_array);
    $texto = $res['ticket'];
    if (trim($texto) == '') {
        $texto = ticket_venta($idventa);
    }
}

$ticket_auto = $texto;
$ticket_auto = utf8_encode($ticket_auto);


//echo $html;exit;

$mpdf = new mPDF('', 'A4', 0, 0, 0, 0, 0, 0);


$ticket_auto = preparePreText($ticket_auto);


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
<pre>'.$ticket_auto.'</pre>
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
