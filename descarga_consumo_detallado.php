 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "153";
require_once("includes/rsusuario.php");

$idcliente = intval($_GET['idc']);
$desde = antisqlinyeccion($_GET['desde'], 'text');
$hasta = antisqlinyeccion($_GET['hasta'], 'text');
$idc = $idcliente;
//titular
$buscar = "select razon_social as rz,idcliente as idc  from cliente 
        where idempresa=$idempresa and idcliente=$idcliente
        order by razon_social asc";

$rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tr = $rsbb->RecordCount();



if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
$desde = antisqlinyeccion($desde, 'date');
$hasta = antisqlinyeccion($hasta, 'date');
$add = " and date(ventas.fecha) between $desde and $hasta ";
//echo $add;exit;
//Prepapramos los datos
if ($ida > 0 || $idc > 0) {
    //Si hay adherente o cliente
    if ($ida > 0) {
        $buscar = "Select idcliente from adherentes where idadherente=$ida and idempresa=$idempresa";
        $rsa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idcliente = intval($rsa->fields['idcliente']);

        //traemos al cliente
        $buscar = "Select * from cliente where idcliente=$idcliente and idempresa=$idempresa";
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    }
    if ($idc > 0) {
        $idcliente = intval($idc);
        //traemos al cliente
        $buscar = "Select * from cliente where idcliente=$idcliente and idempresa=$idempresa";
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    }
    //Traemos los datos de adherentes
    $buscar = "Select * from adherentes where idcliente=$idcliente and idempresa=$idempresa order by nomape asc";
    $rsad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $numad = $rsad->RecordCount();

}
$titular = $rsb->fields['razon_social'];





$html = $html."<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />
<title></title>

";
$img = "gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_0.png";
}
require_once  '../clases/mpdf/vendor/autoload.php';
$html .= "    
</head>
<body bgcolor=\"#FFFFFF\">";
$html .= "<br />
         <table width=\"380\" border=\"1\" align=\"center\" style=\"border-collapse:collapse; text-align:center; margin-left:10px; margin-right:10px;margin:0px auto;\">
        <tr>
                <td height=\"45\"  align=\"center\"><img src=\"$img\" height=\"80\" alt=\"\"/></td>
                <td colspan=\"4\" align=\"center\"><strong>$nombreempresa</strong><br />Resumen de Consumo</td>
             </tr>
          <tr>
          <td height=\"22\" align=\"center\" bgcolor=\"#CFCFCF\"><strong>Desde: </strong>".date("d-m-Y", strtotime($_GET['desde']))."</td>
                <td colspan=\"4\" align=\"center\" bgcolor=\"#CFCFCF\"><strong>Hasta: </strong>".date("d-m-Y", strtotime($_GET['hasta']))."</td>
          </tr>
        </table>
      
         <br />
        ";



$html .= "
    <hr />

            <table width=\"600\" align=\"center\" style=\"margin-left:10px; margin-right:10px;margin:0px auto;\" >
                <tr>
                    <td colspan=\"4\" align=\"center\">IDC: $idcliente</td>
                </tr>
            
                <tr>
                                        <td  height=\"25\" bgcolor=\"#E5E5E5\"><strong>Titular Cuenta</strong></td>
                                        ";
$html .= "
                                        <td width=\"146\">".$titular." </td>
                                        <td  bgcolor=\"#E5E5E5\"><strong>Num. Adherentes</strong></td>
                                        <td  align=\"center\">$numad</td>
                </tr>
                <tr>
                                        <td height=\"37\" bgcolor=\"#E5E5E5\"><strong>Linea Credito</strong></td>
                                        <td>".formatomoneda($rsb->fields['linea_sobregiro'])."</td>
                                      <td bgcolor=\"#E5E5E5\"><strong>Linea Adelanto</strong></td>
                                        <td align=\"center\">".formatomoneda(0)."</td>
                 </tr>
                <tr>
                                        <td height=\"37\" bgcolor=\"#E5E5E5\"><strong>Linea Credito Disponible</strong></td>
                                        <td>".formatomoneda($rsb->fields['saldo_sobregiro'])."</td>
                                      <td bgcolor=\"#E5E5E5\"><strong>Adelanto Disponible</strong></td>
                                        <td align=\"center\">".formatomoneda(0)."</td>
                </tr>
          </table>
                                

    <br /><br /><br />

        <table  width=\"600\" style=\"margin-left:10px; margin-right:10px;margin:0px auto;\">    
";
if ($numad > 0) {
    $paso = 0;
    $ante = 0;
    while (!$rsad->EOF) {
        $paso = $paso + 1;
        $ida = intval($rsad->fields['idadherente']);
        if ($ida != $ante) {
            $paso = 1;

        }
        if ($paso == 1) {
            $ante = $ida;
            $html .= "
                    <tr>    
                                                    <td colspan=\"5\" bgcolor=\"#D6FAFF\"><strong>Adherente: ".$rsad->fields['nomape']."</strong></td>
                    <tr>    
                    <tr>
                                                    <td width=\"93\" align=\"center\" bgcolor=\"#D6FAFF\"><strong>Fecha</strong></td>
                                                    <td width=\"83\" align=\"center\" bgcolor=\"#D6FAFF\"><strong>COD Venta</strong></td>
                                                    <td width=\"162\" align=\"center\" bgcolor=\"#D6FAFF\"><strong>Producto</strong></td>
                                                    <td width=\"114\" align=\"center\" bgcolor=\"#D6FAFF\"><strong>Cantidad</strong></td>
                                                    <td width=\"124\" align=\"center\" bgcolor=\"#D6FAFF\"><strong>Sub Total</strong></td>
                    </tr> ";
        }
        $buscar = "Select idadherente,idserviciocom,totalcobrar,total_cobrado,idpedido,total_venta,fecha,ventas.idventa,cantidad,subtotal,pventa,ventas_detalles.idprod,descripcion
                                                from ventas
                                                inner join ventas_detalles on ventas_detalles.idventa=ventas.idventa
                                                inner join productos
                                                on productos.idprod_serial=ventas_detalles.idprod
                                                where ventas.idadherente=$ida $add 
                                                order by idventa,fecha asc";

        $dv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $general = 0;
        while (!$dv->EOF) {
            $general = $general + intval($dv->fields['subtotal']);
            $html .= "
                        <tr>
                                                    <td>".date("d-m-Y h:i:s", strtotime($dv->fields['fecha']))."</td>
                                                    <td>".$dv->fields['idventa']."</td>
                                                  <td>".$dv->fields['descripcion']."</td>
                                                    <td align=\"center\">".formatomoneda($dv->fields['cantidad'], 0)."</td>
                                                    <td align=\"center\">".formatomoneda($dv->fields['subtotal'], 0)."</td>
                                                    
                        </tr>
                                                    ";
            $dv->MoveNext();
        }
        $html .= "
                    <tr>
                                                  <td height=\"30\" colspan=\"4\" align=\"right\"><strong>Total Consumo Gs:  </strong></td>
                                                    <td align=\"center\">".formatomoneda($general)."</td>
                      </tr>";
        $rsad->MoveNext();
    }

} else {
    //De num ad > 0

    $buscar = "Select totalcobrar,total_cobrado,idpedido,total_venta,fecha,ventas.idventa,cantidad,subtotal,pventa,ventas_detalles.idprod,
             descripcion
            from ventas
                                                inner join ventas_detalles on ventas_detalles.idventa=ventas.idventa
                                                inner join productos
                                                on productos.idprod_serial=ventas_detalles.idprod
                                                where ventas.idcliente=$idcliente $add
                                                order by idventa,fecha asc";
    $dv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $general = 0;
    while (!$dv->EOF) {
        $general = $general + intval($dv->fields['subtotal']);
        $html .= "
                                                      <tr>
                                                    <td>".date("d-m-Y h:i:s", strtotime($dv->fields['fecha']))."</td>
                                                    <td>".$dv->fields['idventa']."</td>
                                                  <td>".$dv->fields['descripcion']."</td>
                                                    <td align=\"center\">".formatomoneda($dv->fields['cantidad'], 0)."</td>
                                                    <td align=\"center\">".formatomoneda($dv->fields['subtotal'], 0)."</td>
                                                    
                                                </tr>";
        $dv->MoveNext();
    }
    $html .= "                                                     
                                                  <tr>
                                                  <td height=\"30\" colspan=\"4\" align=\"right\"><strong>Total Consumo Gs:</strong></td>
                                                    <td align=\"center\">".formatomoneda($general)."</td>
                                  </tr>
                                  
                                 ";
}
$html .= "
                                </table>
        
                      ";


$html .= "<hr />
    <div style=\"font-size:10px; text-align:center;\">
PDF Generated by: e-Kar&uacute;<br />
www.restaurante.com.py<br />
</div><br />    <br />    
</body>
</html>";

//echo $html;
//exit;
$mpdf = new mPDF('', 'Legal-P', 0, 0, 0, 0, 0, 0);
//$mpdf = new mPDF('','A4',55,'dejavusans');
//$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
$mini = date('dmYHis');
$mpdf->SetDisplayMode('fullpage');
$mpdf->use_kwt = false;
//$mpdf->shrink_tables_to_fit = 1;
$mpdf->shrink_tables_to_fit = 2.5;
// Write some HTML code:
$mpdf->WriteHTML($html);
// Output a PDF file directly to the browser
//si no se usa el tributo I, no permite usar el nombre indicado y los archivos no sedescargan nunca!!
$mpdf->Output('Consumo_detallado'.$mini.'.pdf', 'I');

?>
