<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("./preferencias_compras_ordenes.php");
require_once("../proveedores/preferencias_proveedores.php");

global $mostrar_codigo_origen;
global $preferencias_medidas_referenciales;
global $proveedores_importacion;

$idcoc = intval($_GET['idoc']);

//buscando moneda guarani
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE UPPER(tipo_moneda.descripcion) like \"%GUARANI%\" ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_guarani = $rs_guarani->fields["idtipo"];
$nombre_moneda_guarani = $rs_guarani->fields["nombre"];

// buscar moneda de la orden
$consulta = "Select 
compras_ordenes.idtipo_moneda, 
tipo_moneda.descripcion as nombre_moneda 
from 
compras_ordenes 
INNER JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda 
where 
compras_ordenes.estado = $idcoc";
$rs_orden = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo_moneda_orden = $rs_orden->fields['idtipo_moneda'];
$nombre_moneda_orden = $rs_orden->fields['nombre_moneda'];

//buscando moneda nacional
$consulta = "SELECT idtipo,descripcion FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["descripcion"];

if ($idcoc > 0) {
	$buscar = "Select * from empresas";
	$rse = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
	$empresachar = trim($rse->fields['empresa']);

	$buscar = "Select 
	compras_ordenes.fecha, 
	compras_ordenes.ocnum, 
	compras_ordenes.registrado_el, 
	usuario, 
	compras_ordenes.idtipo_moneda, 
	compras_ordenes.tipocompra, 
	nombre, 
	fecha_entrega, 
	compras_ordenes.estado, 
	cant_dias, 
	inicia_pago, 
	forma_pago, 
	proveedores.diasvence, 
	tipo_moneda.descripcion as moneda_nombre, 
	cotizaciones.cotizacion as cotizacion_venta, 
	cotizaciones.compra as cotizacion_compra,
	embarque.idembarque,
	embarque.fecha_embarque,
	embarque.fecha_llegada,
	embarque.descripcion as embarque_descripcion,
	puertos.descripcion as puerto,
	vias_embarque.descripcion as vias_embarque,
	transporte.descripcion as transporte
  from 
	compras_ordenes 
	inner join proveedores on proveedores.idproveedor = compras_ordenes.idproveedor 
	inner join usuarios on usuarios.idusu = compras_ordenes.generado_por 
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda 
	LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
	LEFT JOIN embarque on embarque.ocnum = compras_ordenes.ocnum
	LEFT JOIN puertos on puertos.idpuerto = embarque.idpuerto
	LEFT JOIN vias_embarque on vias_embarque.idvias_embarque = embarque.idvias_embarque
	LEFT JOIN transporte on transporte.idtransporte = embarque.idtransporte
  where 
	compras_ordenes.ocnum = $idcoc";

	$rsh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
	$cantidad_dias = $rsh->fields['cant_dias'] > 0 ? $rsh->fields['cant_dias'] : "";
	$fecha_pago_estimada = $rsh->fields['inicia_pago'] != '' ? date("d/m/Y", strtotime($rsh->fields['inicia_pago'])) : " ";
	$condicion_pdf = intval($rsh->fields['tipocompra']) == 2 ? "Credito" : "Contado";
	$fecha_orden = date("d/m/Y", strtotime($rsh->fields['fecha'])) . "  |  Operador: " . $rsh->fields['usuario'];
	$nombre = $rsh->fields['nombre'];
	$idtipo_moneda = $rsh->fields['idtipo_moneda'];
	$moneda_nombre = $rsh->fields['moneda_nombre'];
	$cotizacion_venta = $rsh->fields['cotizacion_venta'];
	$cotizacion_compra = $rsh->fields['cotizacion_compra'];
	$impreso_el = ' Impreso el ' . date("d/m/Y H:i:s");
	$idembarque = $rsh->fields['idembarque'];
	$puerto = $rsh->fields['puerto'];
	$transporte = $rsh->fields['transporte'];
	$vias_embarque = $rsh->fields['vias_embarque'];
	$fecha_embarque = date("Y/m/d", strtotime($rsh->fields['fecha_embarque']));
	$fecha_llegada = date("Y/m/d", strtotime($rsh->fields['fecha_llegada']));
	$embarque_descripcion = $rsh->fields['embarque_descripcion'];
	$registrado_el = date("d/m/Y H:i:s", strtotime($rsh->fields['registrado_el']));
	$fecha1 = new datetime($fecha_llegada);
	$fecha2 = new datetime($fecha_embarque);
	$dias = $fecha2->diff($fecha1);
	$dias_cantidad = $dias->days;
	$fecha_llegada_rep = date("d/m/Y", strtotime($fecha_llegada));
	$fecha_embarque_rep = date("d/m/Y", strtotime($fecha_embarque));

	//echo $fecha_embarque_rep; exit;

	$buscar = "
	select compras_ordenes_detalles.*, proveedores_fob.codigo_articulo as codigo_origen,
	insumos_lista.cant_medida2, insumos_lista.cant_medida3,
	(
	select barcode 
	from productos 
	inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial 
	where 
	insumos_lista.idinsumo =  compras_ordenes_detalles.idprod
	) as codbar,
	(
		select cant_medida2 
		from insumos_lista 
		where 
		idinsumo = compras_ordenes_detalles.idprod
	) as cant_medida2,
	(
		select cant_medida3 
		from insumos_lista 
		where 
		idinsumo = compras_ordenes_detalles.idprod
	) as cant_medida3
	from compras_ordenes_detalles 
	INNER JOIN insumos_lista on insumos_lista.idinsumo = compras_ordenes_detalles.idprod
    LEFT JOIN proveedores_fob ON proveedores_fob.idfob = insumos_lista.cod_fob
	where 
	ocnum=$idcoc 
	order by compras_ordenes_detalles.descripcion asc";
	$rshd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
} else {
	$error = 'Debe indicar n&uacute;mero de orden.';
}

$img = "../gfx/empresas/emp_" . $idempresa . ".png";
if (!file_exists($img)) {
	$img = "../gfx/empresas/emp_0.png";
}

if ($id_moneda_nacional != $idtipo_moneda) {
	$cotizacion_mensaje = " Tipo Cambio: " . formatoMoneda($cotizacion_venta, 2, 'S');
}


if (!isset($moneda_nombre) || $moneda_nombre == null || $moneda_nombre == "") {
	$moneda_nombre = $nombre_moneda_nacional;
}

//////////////////////
$css = "
<style>
	@page *{
	margin-top: 0cm;
	margin-bottom: 0cm;
	margin-left: 0cm;
	margin-right: 0cm;
	}

	.fondopagina{
		border:0px solid #000000;
		width:1200px;
		height:1200px;
		margin-top:10px;
		margin-left:auto;margin-right:auto;
		background-image:url('gfx/presupuestos/01.jpg') no-repeat;
		background-size: cover;
	}

	.fondopagina_pagos{
		border:0px solid #FFFFFF;
		width:1200px;
		height:1200px;
		margin-top:10px;
		margin-left:auto;margin-right:auto;
		background-image:url('gfx/presupuestos/p02new.jpg') no-repeat;
		background-size: cover;
	}

	.contenedorppal{
		width:100%;
		height:50px;
		border:2px solid #b8860b;
		border-style: dotted;
	}

	.contenedorppalc{
		color:#b8860b;
		border:0.5px solid #b8860b;
		border-style: dotted;
		height:120px;
		width:650px;
		margin-left:auto;
		margin-right:auto;
	}

	.contenedorppaldire{
		color:#b8860b;
		border:0.5px solid #b8860b;
		border-style: dotted;
		height:40px;
		width:600px;
		margin-top:3%;
		margin-left:auto;
		margin-right:auto;
	}
	
	.contenedorderechamini{
		color:#b8860b;
		border:0px solid #b8860b;
		border-style: dotted;
		width:200px;
		height:60px;
		float:right;
		margin-top:5%;
		margin-right:4%;
	}

	.contenedorizqmini{
		color:#b8860b;
		#border:0.5px solid #b8860b;
		#border-style: dashed;
		width:130px;
		height:40px;
		float:left;
		margin-left:0%;
		margin-top:0%;
		
	}

	.button-1 {
	  background-color: #EA4C89;
	  border-radius: 8px;
	  border-style: none;
	  box-sizing: border-box;
	  color: #FFFFFF;
	  cursor: pointer;
	  display: inline-block;
	  font-family: \"Haas Grot Text R Web\", \"Helvetica Neue\", Helvetica, Arial, sans-serif;
	  font-size: 14px;
	  font-weight: 500;
	  height: 40px;
	  line-height: 20px;
	  list-style: none;
	  margin: 0;
	  outline: none;
	  padding: 10px 16px;
	  position: relative;
	  text-align: center;
	  text-decoration: none;
	  transition: color 100ms;
	  vertical-align: baseline;
	  user-select: none;
	  -webkit-user-select: none;
	  touch-action: manipulation;
	}

	.button-1:hover,
	.button-1:focus {
	  background-color: #F082AC;
	}

	.contenedorceqmini{
		#color:#b8860b;
		#border:0.5px solid #b8860b;
		#border-style: dashed;
		width:300px;
		height:40px;
		float:left;
		margin-top:0%;
	}

	.button-29 {
	  align-items: center;
	  appearance: none;
	  background-image: radial-gradient(100% 100% at 100% 0, #5adaff 0, #5468ff 100%);
	  border: 0;
	  border-radius: 6px;
	  box-shadow: rgba(45, 35, 66, .4) 0 2px 4px,rgba(45, 35, 66, .3) 0 7px 13px -3px,rgba(58, 65, 111, .5) 0 -3px 0 inset;
	  box-sizing: border-box;
	  color: white;
	  cursor: pointer;
	  display: inline-flex;
	  font-family: \"JetBrains Mono\",monospace;
	  height: 40px;
	  justify-content: center;
	  line-height: 1;
	  list-style: none;
	  overflow: hidden;
	  padding-left: 16px;
	  padding-right: 16px;
	  position: relative;
	  text-align: left;
	  text-decoration: none;
	  transition: box-shadow .15s,transform .15s;
	  user-select: none;
	  -webkit-user-select: none;
	  touch-action: manipulation;
	  white-space: nowrap;
	  will-change: box-shadow,transform;
	  font-size: 18px;
	}

	.button-29:focus {
	  box-shadow: #3c4fe0 0 0 0 1.5px inset, rgba(45, 35, 66, .4) 0 2px 4px, rgba(45, 35, 66, .3) 0 7px 13px -3px, #3c4fe0 0 -3px 0 inset;
	}

	.button-29:hover {
	  box-shadow: rgba(45, 35, 66, .4) 0 4px 8px, rgba(45, 35, 66, .3) 0 7px 13px -3px, #3c4fe0 0 -3px 0 inset;
	  transform: translateY(-2px);
	}

	.button-29:active {
	  box-shadow: #3c4fe0 0 3px 7px inset;
	  transform: translateY(2px);
	}

	.contenedordermini{
		#color:#b8860b;
		#border:0.5px solid #b8860b;
		#border-style: dashed;
		width:202px;
		height:40px;
		float:left;
		margin-top:0.8%;
		
	}

	.colordorado{
		color:#b8860b;
	}

	.negrito{
		color:black;
	}
	table {
		border-collapse: collapse; width:100%;
		font-size:12px;
	}
	 
	table,
	th,
	td {
		border: 0px solid black; align:center;
	}
	 
	th,
	td {
		padding: 5px;
	}
</style>";

function limpiacsv($txt)
{
	global $saltolinea;
	$txt = trim($txt);
	$txt = str_replace(";", ",", $txt);
	$txt = str_replace($saltolinea, "", $txt);
	return $txt;
}

/*------------------------------------------RECEPCION DE VALORES--------------------------------*/
$idcpr = intval($_REQUEST['cpr']);
$fecha_prod_desde = antisqlinyeccion($_REQUEST['prod_desde'], 'date');
$fecha_prod_hasta = antisqlinyeccion($_REQUEST['prod_hasta'], 'date');
$fecha_prod_desde_hora = antisqlinyeccion($_REQUEST['prod_desde_hora'], 'text');
$fecha_prod_hasta_hora = antisqlinyeccion($_REQUEST['prod_hasta_hora'], 'text');
$fecha_evento_desde = antisqlinyeccion($_REQUEST['evento_desde'], 'date');
$fecha_evento_hasta = antisqlinyeccion($_REQUEST['evento_hasta'], 'date');
$fecha_evento_desde_hora = antisqlinyeccion($_REQUEST['evento_desde_hora'], 'text');
$fecha_evento_hasta_hora = antisqlinyeccion($_REQUEST['evento_hasta_hora'], 'text');
$fecha_registro_desde = antisqlinyeccion($_REQUEST['freg'], 'date');
$fecha_registro_hasta = antisqlinyeccion($_REQUEST['freg2'], 'date');
$fecha_reg_desde_hora = antisqlinyeccion($_REQUEST['freghoradesde'], 'date');
$fecha_reg_hasta_hora = antisqlinyeccion($_REQUEST['freghorahasta'], 'date');
$idproducto = intval($_REQUEST['idproducto']);
$idcategoria = intval($_REQUEST['idcategoria']);
$id_sub_categoria = intval($_REQUEST['idsubcate']);
$especial = intval($_REQUEST['especial']); // Solo para uso de PDF y Producciones  eventos (clientes)
//echo $especial;exit;

if ($_REQUEST['freg'] == '') {
	$fecha_registro_desde = '';
}
$fecha_registro_hasta = antisqlinyeccion($_REQUEST['freg2'], 'date');
if ($_REQUEST['freg2'] == '') {
	$fecha_registro_hasta = '';
}

$idlugar_entrega = intval($_REQUEST['lugar']);
$ocvalor = intval($_REQUEST['ocvalor']);
/*------------------------------------------RECEPCION DE VALORES--------------------------------*/

//echo $buscar;exit;

$html = " $css";

/*--------------------------CABECERA CON FILTROS----------------------------*/
$html .= "<div style='border:0px solid #000000;'>
			<div style=\"margin-top:0%;width:80%;  margin-left:auto;margin-right:auto;text-align:left;height:50px;\">
            	<div align=\"center\">
          			<div style=\"width:100%; border:1px solid #000000; height:125px;\">
						<table width=\"600\">
							<tr>
								<td> <img src=\"$img\" height=\"120\" /></td>
								<td><h1>$empresachar<br />Orden Compra N&deg;$idcoc</h1></td>
							</tr>
						</table>
            		</div>
            
            		<table width=\"700\" border=\"0\">
						<tbody>
							<tr>
								<td height=\"29\" colspan=\"4\" align=\"center\" bgcolor=\"#F0EBEB\">Fecha Orden:$fecha_orden</td>
							</tr>
							<tr>
								<td height=\"29\" colspan=\"4\" align=\"center\" bgcolor=\"#F0EBEB\">Registrado el:$registrado_el</td>
							</tr>
							<tr>
								<td align=\"right\" bgcolor=\"#F0EBEB\"><strong>Proveedor</strong></td>
								<td colspan=\"3\" align=\"left\">$nombre</td>
							</tr>
							<tr>
								<td width=\"25%\" height=\"36\" align=\"right\" bgcolor=\"#F0EBEB\"><strong>Fecha de Embarque</strong></td>
								<td width=\"25%\" style=\"color:#FC0004; font-weight:bold\">$fecha_embarque_rep</td>
								<td width=\"25%\" height=\"36\" align=\"right\" bgcolor=\"#F0EBEB\"><strong>Fecha Entrega Esperada</strong></td>
								<td width=\"25%\" style=\"color:#FC0004; font-weight:bold\">$fecha_llegada_rep</td>
							</tr>
							<tr>
								<td  align=\"right\" width=\"25%\" bgcolor=\"#F0EBEB\" style=\"font-weight:bold\"><strong>Condicion</strong></td>
								<td width=\"25%\" style=\"color:#FC0004; font-weight:bold\">$condicion_pdf</td>
								<td align=\"right\" bgcolor=\"#F0EBEB\"><strong>Días en Tránsito</strong></td>
								<td align=\"left\">$dias_cantidad</td>
							</tr>
						</tbody>
					</table>

        			<strong>Art&iacute;culos</strong> <hr /> <br />
					
					<table width=\"799\" border=\"1\" style=\"border-collapse:collapse;\">
						<tr>
							<td width=\"100\" height=\"29\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>C&oacute;digo</em></strong></td>
							<td width=\"183\" align=\"center\" bgcolor=\"#B4B4B4\"><strong>Cod Barra</strong></td>
							<td width=\"183\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Producto</em></strong></td>
							<td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Unidades</em></strong></td>";

if ($preferencias_medidas_referenciales == "S") {
	$html .= "
							<td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Cajas</em></strong></td>
							<td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Pallets</em></strong></td>";
}

$html .= "
							<td width=\"111\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Precio Compra(Unidad)</em></strong></td>
							<td width=\"103\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Sub Total $moneda_nombre</em></strong></td>
						</tr>";

$to = 0;

while (!$rshd->EOF) {

	$subt = $rshd->fields['cantidad'] * $rshd->fields['precio_compra'];
	$to = $to + $subt;
	$idprod = $rshd->fields['idprod'];
	$codigo_origen = intval($rshd->fields['codigo_origen']);
	$codbar = $rshd->fields['codbar'];
	$descripcion = $rshd->fields['descripcion'];
	$cantidad = formatomoneda($rshd->fields['cantidad'], 4, 'N');
	$cantidad_cajas = $rshd->fields['cant_medida2'];
	$cantidad_pallets = $rshd->fields['cant_medida3'];
	$cajas = $rshd->fields['cantidad'] / $cantidad_cajas;
	$pallets = $cajas / $cantidad_pallets;

	if ($codigo_origen == null || $codigo_origen == "NULL") {
		$codigo_origen = 0;
	}
	if ($cajas != intval($cajas)) {
		$cajas = 0;
	}
	if ($pallets != intval($pallets)) {
		$pallets = 0;
	}

	$precio_compra = formatomoneda($rshd->fields['precio_compra'], 4, 'N');
	$subt = formatomoneda($subt, 4, 'N');

	$html .= "		<tr>";
	if ($mostrar_codigo_origen == "S" && $codigo_origen != 0) {
		$html .= "		<td height=\"29\" align=\"center\">$codigo_origen</td>";
	} else {
		$html .= 		"<td height=\"29\" align=\"center\">$idprod</td>";
	}
	$html .= "
						<td align=\"center\">$codbar</td>
						<td align=\"center\">$descripcion</td>
						<td align=\"right\">$cantidad</td>";

	if ($preferencias_medidas_referenciales == "S") {
		$html .= "
						<td align=\"right\">$cajas</td>
						<td align=\"right\">$pallets</td>";
	}
	$html .= "
						<td align=\"right\">$precio_compra</td>
						<td align=\"right\">$subt</td>
					</tr>";
	$rshd->MoveNext();
}

$cotizacion_mensaje2 = "";

if ($id_moneda_nacional != $idtipo_moneda) {
	$cotizacion_mensaje2 = " $nombre_moneda_nacional: " . formatomoneda(($cotizacion_venta * $to), 0, "N");
}

$to = formatomoneda($to, 2, 'S');

$html .= "
    				</table>
				</div>
				<br />";

if ($proveedores_importacion == "S" && $idembarque > 0) {
	$html .= "
				<table width=\"799\" border=\"1\" style=\"border-collapse:collapse;\">
					<thead>
						<tr>
							<th width=\"183\" align=\"center\" bgcolor=\"#B4B4B4\"><strong>Puerto</strong></th>
							<th width=\"183\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Transporte</em></strong></th>
							<th width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Vias de Embarque</em></strong></th>
							<th width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Fecha de Embarque</em></strong></th>
							<th width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Fecha de llegada</em></strong></th>
						</tr>
					</thead>
					<tbody>	
						<tr>
							<td align=\"center\">$puerto</td>
							<td align=\"center\">$transporte</td>
							<td align=\"center\">$vias_embarque</td>
							<td align=\"center\">$fecha_embarque</td>
							<td align=\"center\">$fecha_llegada</td>
						</tr>
					</tbody>
				</table>
				<br />";

	if ($embarque_descripcion != "") {
		$html .= "
				<table width=\"799\" border=\"1\" style=\"border-collapse:collapse;\">
					<thead>
						<tr>
							<th width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Descripcion</em></strong></th>
						</tr>
					</thead>
					<tbody>	
						<tr>
							<td align=\"center\">$embarque_descripcion</td>
						</tr>
					</tbody>
				</table>";
	}
}

$html .= "
        <div align=\"center\">
            <div style=\"width:100%; height:260px;border:1px solid #000000;\">
                <table width=\"600px;\" height=\"240\">
                    <tr>
                        <td height=\"32\" colspan=\"4\" align=\"center\"><strong>Total Compra $moneda_nombre  $to $cotizacion_mensaje</strong></td>
                    </tr>
                    <tr>
                        <td height=\"32\" colspan=\"4\" align=\"center\"><strong> $cotizacion_mensaje2 </strong></td>
                    </tr>
                    <tr>
                        <td width=\"84\" height=\"79\"><strong>Encargado Compras</strong></td>
                        <td width=\"216\"><p>..................................................</p></td>
                        <td width=\"41\"><strong>Firma </strong></td>
                        <td width=\"239\"><p>......................................................</p></td>
                    </tr>
                    <tr>
                        <td width=\"84\" height=\"55\"><strong>Administraci&oacute;n</strong></td>
                        <td width=\"216\">..................................................</td>
                        <td width=\"41\"><strong>Firma</strong></td>
                        <td width=\"239\">........................................................</td>
                  	</tr>
                  	<tr>
                        <td height=\"61\"><strong>Observaciones</strong></td>
                        <td colspan=\"3\">$impreso_el</td>
                  	</tr>
                </table>
            </div>
        </div>
	</div>
</div>";

$html .= " ";
/*----------------------------------GENERAR PDF----------------------------------------*/
require_once('../../clases/mpdf/vendor/autoload.php');
// require_once('../clases/vendor/autoload.php');

//$mpdf = new mPDF('','Legal-P', 0, 0, 0, 0, 0, 0);

$mpdf = new mPDF('c', 'A4-P', 0, '', 0, 0, 0, 0, 0, 0);
//$mpdf = new mPDF('c','A4','100','',32,25,27,25,+16,13);
$mpdf->showWatermarkText = false;
$mini = "C-$idpresupuesto";
$mpdf->SetDisplayMode('fullpage');
//$mpdf->shrink_tables_to_fit = 1;
$mpdf->shrink_tables_to_fit = 2.5;
// Write some HTML code:
$mpdf->SetHTMLHeader(
	"<div style='background-color:white;height:150px;margin-left:20%;margin-top:10%;'>
		<p></p> 
	</div>",
	'O'
);
$mpdf->WriteHTML($html);

// Output a PDF file directly to the browser
//si no se usa el tributo I, no permite usar el nombre indicado y los archivos no sedescargan nunca!!
//Bandera I saca en pantalla, bandera F graba en la ubicacion seleccionada
$mpdf->Output('tmp_consolidados/consolidados_' . $mini . '.pdf', "I");
/*------------------------------------------------------------------------------------*/
