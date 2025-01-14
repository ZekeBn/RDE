<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");



//buscando moneda nacional
$consulta = "SELECT idtipo,descripcion FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["descripcion"];

$idcoc = intval($_GET['idoc']);
if ($idcoc > 0) {
    $buscar = "Select * from empresas";
    $rse = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $empresachar = trim($rse->fields['empresa']);

    $buscar = "Select compras_ordenes.fecha,compras_ordenes.ocnum,usuario, compras_ordenes.idtipo_moneda, compras_ordenes.tipocompra,nombre,fecha_entrega,compras_ordenes.estado,cant_dias,inicia_pago,forma_pago,proveedores.diasvence,
	tipo_moneda.descripcion as moneda_nombre, cotizaciones.cotizacion as cotizacion_venta, cotizaciones.compra as cotizacion_compra
	from compras_ordenes 
	inner join proveedores on proveedores.idproveedor=compras_ordenes.idproveedor 
	inner join usuarios on usuarios.idusu=compras_ordenes.generado_por
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda
	LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
	where ocnum=$idcoc";
    $rsh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $cantidad_dias = $rsh->fields['cant_dias'] > 0 ? $rsh->fields['cant_dias'] : "";
    $fecha_pago_estimada = $rsh->fields['inicia_pago'] != '' ? date("d-m-Y", strtotime($rsh->fields['inicia_pago'])) : " ";
    $condicion_pdf = intval($rsh->fields['tipocompra']) == 2 ? "Credito" : "Contado";
    $fecha_entrega_esperada = date("d/m/Y", strtotime($rsh->fields['fecha_entrega']));
    $fecha_orden = date("d/m/Y", strtotime($rsh->fields['fecha']))."  |  Operador: ".$rsh->fields['usuario'];
    $nombre = $rsh->fields['nombre'];
    $idtipo_moneda = $rsh->fields['idtipo_moneda'];
    $moneda_nombre = $rsh->fields['moneda_nombre'];
    $cotizacion_venta = $rsh->fields['cotizacion_venta'];
    $cotizacion_compra = $rsh->fields['cotizacion_compra'];
    $impreso_el = ' Impreso el '.date("d-m-Y H:i:s");

    $buscar = "
	select *, 
	(
	select barcode 
	from productos 
	inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial 
	where 
	insumos_lista.idinsumo =  compras_ordenes_detalles.idprod
	) as codbar
	from compras_ordenes_detalles 
	where 
	ocnum=$idcoc 
	order by descripcion asc
	";
    $rshd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

} else {
    $error = 'Debe indicar n&uacute;mero de orden.';

}


$img = "../gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "../gfx/empresas/emp_0.png";
}

if ($id_moneda_nacional != $idtipo_moneda) {
    $cotizacion_mensaje = " Tipo Cambio: ".formatoMoneda($cotizacion_venta, 2, 'S');
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
</style>
";



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
$especial = intval($_REQUEST['especial']);// Solo para uso de PDF y Producciones  eventos (clientes)
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

$html = "
		$css
		";

/*--------------------------CABECERA CON FILTROS----------------------------*/
$html .= "";

$html .= "

		";
/*----------------------------------GENERAR PDF----------------------------------------*/
require_once  '../../clases/mpdf/vendor/autoload.php';

//$mpdf = new mPDF('','Legal-P', 0, 0, 0, 0, 0, 0);

$mpdf = new mPDF('c', 'A4-P', 0, '', 0, 0, 0, 0, 0, 0);
//$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
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
)
;




;
$mpdf->WriteHTML($html);

// Output a PDF file directly to the browser
//si no se usa el tributo I, no permite usar el nombre indicado y los archivos no sedescargan nunca!!
//Bandera I saca en pantalla, bandera F graba en la ubicacion seleccionada
$mpdf->Output('tmp_consolidados/consolidados_'.$mini.'.pdf', "I");

/*------------------------------------------------------------------------------------*/
