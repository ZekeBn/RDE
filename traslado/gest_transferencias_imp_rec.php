<?php

require_once("includes/conexion.php");
$modulo = "1";
$submodulo = "223";
require_once("includes/rsusuario.php");
require_once("includes/funciones.php");

$tanda = intval($_GET['id']);
if ($tanda > 0) {
    //traemos la tanda anterior
    $buscar = "Select *,(select usuario from usuarios where idusu=gest_transferencias.generado_por) as responsable,
	(Select descripcion from gest_depositos where iddeposito=gest_transferencias.origen) as origenc,
	(Select descripcion from gest_depositos where iddeposito=gest_transferencias.destino) as destinoc
	 from gest_transferencias where idtanda=$tanda";
    $rscabe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $fechatrans = date("d-m-Y", strtotime($rscabe->fields['fecha_transferencia']));

    $idgenera = intval($rscabe->fields['generado_por']);
    $origen = trim($rscabe->fields['origenc']);
    $destino = trim($rscabe->fields['destinoc']);
    $dst = intval($rscabe->fields['destino']);
    $or = intval($rscabe->fields['origen']);
    $responsable = ($rscabe->fields['responsable']);

    //cuerpo
    $buscar = "select *,(select descripcion from insumos_lista where idinsumo=gest_depositos_mov.idproducto) as descripcion,
	(select barcode from productos inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial where idinsumo=gest_depositos_mov.idproducto) as barcode
	 from gest_depositos_mov 
	 where 
	 idtanda=$tanda 
	 and idempresa=$idempresa
	 and gest_depositos_mov.estado <> 6
	 order by descripcion asc";
    $rscuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


} else {
    $error = 'Debe indicar n&uacute;mero de traslado.';

}

$consulta = "
select valoriza_reposicion, usa_controladopor from preferencias_transfer limit 1
";
$rspreftrans = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$valoriza_reposicion = $rspreftrans->fields['valoriza_reposicion'];
$usa_controladopor = $rspreftrans->fields['usa_controladopor'];
if ($valoriza_reposicion == 'S') {
    $mostrarcosto = "S";
}

$img = "gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_0.png";
}


$html1 = "
<!doctype html>
<html>
<head>
<meta charset=\"utf-8\">
<title>Orden de Translado</title>
<style>
*{
	font-size:12px;
}
</style>
</head>

<body>
<div align=\"center\">";
if ($error != '') {
    $error;
    exit;

}
$html1 = $html1."
  <div align=\"center\" style=\"width:610px; border:1px solid #000000; height:70px; margin:0px auto;\">
      <table width=\"510\">
        <tr>
            <td><img src=\"$img\" height=\"70\" alt=\"\"/></td>
            <td align=\"left\">
			<stong>Traslado N&deg;</strong> $tanda<br />
			<stong>Fecha Traslado:</strong> $fechatrans  /  $responsable<br />
			<strong>Origen:</strong> $origen / <strong>Destino: </strong> $destino		<br />
				
			</td>
        </tr>
      </table>
 </div>
 <br />
 
";
if ($mostrarcosto == 'S') {
    $html1 .= "
	 <table width=\"480\" align=\"center\" border=\"1\"  style=\"border-collapse:collapse;\">
	<tr>
	 <td  colspan=\"8\" align=\"center\" bgcolor=\"#E4E4E4\"><strong>Art&iacute;culos</strong></td>";
} else {
    $html1 .= "<table width=\"480\" align=\"center\" border=\"1\"  style=\"border-collapse:collapse;\">
	<tr>
	 <td  colspan=\"6\" align=\"center\" bgcolor=\"#E4E4E4\"><strong>Art&iacute;culos</strong></td>";
}
$html1 .= "</tr>
	<tr>
    	<td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>C&oacute;digo</em></strong></td>
		<td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>C&oacute;digo Barras</em></strong></td>
        <td align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>Producto</em></strong></td>
        <td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>Cant Envio</em></strong></td>
		<td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>Cant Recibe</em></strong></td>
		<td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>Diferencia</em></strong></td>";
if ($mostrarcosto == 'S') {
    $html1 = $html1."
        <td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>Costo Gs</em></strong></td>
         <td  align=\"center\" bgcolor=\"#E4E4E4\"><strong><em>Subtotal Gs</em></strong></td>
    	";
} else {
    // $html1=$html1."<td colspan=\"2\"></td></td>";

}
$html1 = $html1."</tr>
	";

$to = 0;
while (!$rscuerpo->EOF) {


    $diferencia = $rscuerpo->fields['cantidad_recibe'] - $rscuerpo->fields['cantidad'];
    $cantacum += $rscuerpo->fields['cantidad'];
    $cantrecacum += $rscuerpo->fields['cantidad_recibe'];
    $difacum += $diferencia;

    //Buscamos el precio de compras
    $pp = antisqlinyeccion($rscuerpo->fields['idproducto'], 'texto');
    /*
        $buscar="Select costogs from gest_depositos_stock
         where iddeposito=$dst and idproducto=$pp and disponible > 0 order by idseriecostos asc";
         $buscar="Select precio_costo as costogs from costo_productos
             where id_producto=$pp and precio_costo > 0 order by idseriepkcos desc limit 1";
         $rscos=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

         $costo=floatval($rscos->fields['costogs']);
          */
    $costo = floatval($rscuerpo->fields['costo_unitario_art']);
    $subt = floatval($rscuerpo->fields['cantidad']) * $costo;
    $to = $to + $subt;
    $html2 = $html2."
	<tr>
		<td align=\"center\">".$rscuerpo->fields['idproducto']."</td>
		<td align=\"center\">".$rscuerpo->fields['barcode']."</td>
        <td align=\"left\">".$rscuerpo->fields['descripcion']."</td>
        <td align=\"right\">".formatomoneda($rscuerpo->fields['cantidad'], 4, 'N')."</td>
		<td align=\"right\">".formatomoneda($rscuerpo->fields['cantidad_recibe'], 4, 'N')."</td>
		<td align=\"right\">".formatomoneda($diferencia, 4, 'N')."</td>
		";

    if ($mostrarcosto == 'S') {
        $html2 = $html2."<td align=\"right\">".formatomoneda($costo)."</td>
		<td align=\"right\">".formatomoneda($subt)."</td>";
    } else {
        //$html2=$html2."<td colspan=\"2\"></td>";
    }
    $html2 = $html2."</tr> ";
    $rscuerpo->MoveNext();
}

$html2 = $html2."
	<tr bgcolor=\"#E4E4E4\">
		<td align=\"left\" colspan=\"3\">Total</td>
        <td align=\"right\">".formatomoneda($cantacum, 4, 'N')."</td>
		<td align=\"right\">".formatomoneda($cantrecacum, 4, 'N')."</td>
		<td align=\"right\">".formatomoneda($difacum, 4, 'N')."</td>
		";

if ($mostrarcosto == 'S') {
    $html2 = $html2."<td align=\"right\"></td>
		<td align=\"right\">".formatomoneda($to)."</td>";
} else {
    //$html2=$html2."<td colspan=\"2\"></td>";
}
$html2 = $html2."</tr> ";

if ($usa_controladopor == 'S') {
    $alto = "160";
} else {
    $alto = "100";
}

$html3 = "
</table>

</div>
<br />
<div align=\"center\">
    <div style=\"width:610px; height:".$alto."px;border:1px solid #000000; margin-left:auto; margin-right:auto;\">
    	<table width=\"610px;\" >";
if ($mostrarcosto == 'S') {
    $html3 .= "
        	<tr>
            	<td colspan=\"4\" align=\"center\"><strong>Total Enviado Gs: ".formatomoneda($to)."</strong></td>
            
            </tr>";
}
if ($usa_controladopor == 'S') {
    $html3 .= "
    		<tr>
            	<td ><strong>Controlado por</strong></td>
                <td><p>..................................................</p></td>
                <td ><strong>Firma </strong></td>
                <td ><p>......................................................</p></td>
            </tr>
			";

}


$html3 .= "
    		<tr>
            	<td ><strong>Entregado por</strong></td>
                <td><p>..................................................</p></td>
                <td ><strong>Firma </strong></td>
                <td ><p>......................................................</p></td>
            </tr>
            <tr>
            	<td ><strong>Recibido por</strong></td>
                <td>..................................................</td>
                <td><strong>Firma</strong></td>
                <td>........................................................</td>
          </tr>
           <tr>
            	<td ><strong>Fecha / Hora</strong></td>
                <td colspan=\"3\">..................................................................</td>
          </tr>
          <tr>
                <td colspan=\"3\" align=\"center\">Impreso el ".date("d/m/Y H:i:s")."</td>
            
          </tr>
    	</table>
    </div>
</div>
</body>
</html>";
$ah = date("YmdHis");
$hh = rand();

$final = $html1.$html2.$html3;

//$final;
require_once("../clases/dompdf-master/dompdf_config.inc.php");
$dompdf = new DOMPDF();
$dompdf->load_html($final);
$dompdf->render();
$dompdf->stream("traslados_$ah.pdf");
