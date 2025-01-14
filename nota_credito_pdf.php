<?php
/*-----------------------------------------
25/08/2023
--------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
require_once("includes/num2letra.php");
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
ini_set('memory_limit', '512M');
$idunico = intval($_REQUEST['id']);
$tipo = intval($_REQUEST['tipo']);

if ($idunico == 0) {
    echo "Error al obtener ID p NC.";
    exit;
}


$html = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
<style>
.cabezalcompleto {
  border: 1px solid #A37A89; 
  height: 90px;
  border-radius: 15px;
  width:90%;text-align:center;
  margin-left:auto;
  margin-right:auto;
} 
.datosclientes {
  border: 0px solid #A37A89; 
  height: 90px;
  border-radius: 15px;
  width:90%;
  margin-left:auto;
  margin-right:auto;
} 
.cajustadotit{
	border: 1px solid #000000; margin-left:auto;margin-right:auto; width:90%; height:20px;
	
}
.cajustado {
	border: 1px solid #000000; margin-left:auto;margin-right:auto; width:90%; height:400px;
}
.cabeza1 {
  border: 0px solid #A37A89; 
  height: 90px;
  border-radius: 15px;
  width:20%;
  text-align:center;
  float:left;
} 
.cabeza1logo {
  border: 0px solid #A37A89; 
  
  border-radius: 15px;
  
  text-align:center;
  float:left;
} 
.cabeza2 {
  border: 0px solid #A37A89; 
  height: 90px;
  border-radius: 15px;
  width:50%;
  float:left;
  text-align:center;
  font-size:0.6em;
} 
.cabeza3 {
  border: 0px solid #A37A89; 
  height: 90px;
  border-radius: 15px;
  width:20%;
  float:left;
  text-align:center;
  font-size:0.6em;
  font-weight:bold;
} 
.cabezacentral {
  border: 0px solid #A37A89; 
  height: 70px;
  border-radius: 15px;
  width:50%;
  float:left;
  font-size:0.8em;
} 
.cabezacentralizq {
  border: 0px solid #A37A89; 
  height: 70px;
  border-radius: 15px;
  width:45%;
  float:left;
  font-size:0.8em;
} 
.describeproductocab{
	float:left; 
	width:39%; 
	text-align:left; 
	font-size:0.6em; 
	border-right:1px solid;
	margin:0px;
	font-weight:bold;
	font-size: 14px;
}
.describeproducto{
	float:left; 
	width:39%; 
	text-align:left; 
	font-size:0.6em; 
	/*border-right:1px solid;*/
	margin:0px;
}
.describeproducto10cab{
	float:left; 
	width:10%; 
	text-align:center; 
	font-size:0.6em;
	border-right:1px solid;
	margin:0px;
	font-weight:bold;
	font-size: 14px;
}
.describeproducto10{
	float:left; 
	width:10%; 
	text-align:right; 
	font-size:0.6em;
	/*border-right:1px solid;*/
	margin:0px;
}
table {
  width: 100%;
}
table, th, td {
  border-left: 1px solid;
   font-size:0.8em;
   border-collapse:collapse;
}
</style>
</head>

<body><br />
';



/*-----------------------------------------------------------------------------------------------------------------------*/
$consulta = "
select * from empresas where idempresa = $idempresa
";
$rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_empresa = trim($rsemp->fields['razon_social']);
$telfoactual = trim($rsemp->fields['telefono']);
$direccion_empresa = trim($rsemp->fields['direccion']);
$correo_empresa = trim($rsemp->fields['email']);
$telefono_empresa = trim($rsemp->fields['telefono']);
$actividad_economica = trim($rsemp->fields['actividad_economica']);
$ruc_empresa = trim($rsemp->fields['ruc'].'-'.$rsemp->fields['dv']);

$buscar = "Select *,
(select email from cliente where idcliente=nota_credito_cabeza.idcliente) as correo,

(Select descripcion from nota_cred_motivos_cli where idmotivo=nota_credito_cabeza.idmotivo) as motivonc
 from nota_credito_cabeza where idnotacred=$idunico  ";
$rsnota = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$numero_NC = trim($rsnota->fields['numero']);
$timbrado = intval($rsnota->fields['timbrado']);
$timb_valido_desde = date("d/m/Y", strtotime($rsnota->fields['timb_valido_desde']));
$timb_valido_hasta = date("d/m/Y", strtotime($rsnota->fields['timb_valido_hasta']));
$fecha = date("d/m/Y", strtotime($rsnota->fields['fecha_nota']));
$rz = trim($rsnota->fields['razon_social']);
$obs = trim($rsnota->fields['observaciones']);
$motivo = trim($rsnota->fields['motivonc']);
$dct = trim($rsnota->fields['ruc']);
$correo = trim($rsnota->fields['correo']);




$buscar = "
	select factura from nota_credito_cuerpo where idnotacred=$idunico";
$rsfac = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$docu = $rsfac->fields['factura'];

$buscar = "
	Select
	timbrado,idcajaaplicar,numero,fecha_nota,idnotacred,razon_social,nota_credito_cabeza.registrado_el,nota_credito_cabeza.estado,
	ruc,timb_valido_hasta as vtotimbrado,
	(select sum(subtotal) as t from nota_credito_cuerpo where idnotacred=nota_credito_cabeza.idnotacred) as totalnc,
	(select factura from nota_credito_cuerpo where idnotacred=nota_credito_cabeza.idnotacred limit 1) as facturaaplicar,

	CASE WHEN
		(select idnotacred from nota_credito_cuerpo_impuesto where idnotacred = nota_credito_cabeza.idnotacred limit 1) > 0
	THEN
		REPLACE(ROUND(COALESCE((select sum(monto_col) from nota_credito_cuerpo_impuesto where idnotacred = nota_credito_cabeza.idnotacred and nota_credito_cuerpo_impuesto.iva_porc_col = 10),0),0),'.',',')
	ELSE
		REPLACE(ROUND(COALESCE((Select sum(subtotal) as subtotal from nota_credito_cuerpo where idnotacred=nota_credito_cabeza.idnotacred and iva_porc=10),0),0),'.',',')
	END as gravadas_10,

	CASE WHEN
		(select idnotacred from nota_credito_cuerpo_impuesto where idnotacred = nota_credito_cabeza.idnotacred limit 1) > 0
	THEN
		REPLACE(ROUND(COALESCE((select sum(monto_col) from nota_credito_cuerpo_impuesto where idnotacred = nota_credito_cabeza.idnotacred and nota_credito_cuerpo_impuesto.iva_porc_col = 5),0),0),'.',',')
	ELSE
		REPLACE(ROUND(COALESCE((Select sum(subtotal) as subtotal from nota_credito_cuerpo where idnotacred=nota_credito_cabeza.idnotacred and iva_porc=5),0),0),'.',',')
	END as gravadas_05,

	CASE WHEN
		(select idnotacred from nota_credito_cuerpo_impuesto where idnotacred = nota_credito_cabeza.idnotacred limit 1) > 0
	THEN
		REPLACE(ROUND(COALESCE((select sum(monto_col) from nota_credito_cuerpo_impuesto where idnotacred = nota_credito_cabeza.idnotacred and nota_credito_cuerpo_impuesto.iva_porc_col = 0),0),0),'.',',')
	ELSE
		REPLACE(ROUND(COALESCE((Select sum(subtotal) as subtotal from nota_credito_cuerpo where idnotacred=nota_credito_cabeza.idnotacred and iva_porc=0),0),0),'.',',')
	END as gravadas_00


	from nota_credito_cabeza
	where
	date(fecha_nota) between '$desde' and '$hasta'
	$whereadd
	order by numero asc";


$consulta = "
	select *, (select descripcion from gest_depositos where iddeposito = nota_credito_cuerpo.iddeposito) as deposito,
	(select iva_describe from tipo_iva where idtipoiva = nota_credito_cuerpo.idtipoiva) as tipoiva
	from nota_credito_cuerpo 
	where 
	idnotacred = $idunico
	order by idnotacred asc
	";
$rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




//$rs=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));


$clase = "F";//aca va F para descarga I para Pantalla
//crea imagen
$img = "gfx/empresas/emp_1.png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_1.jpg";
    if (!file_exists($img)) {
        $img = "gfx/empresas/emp_0.png";
    }
}





$html .= "
	<br />
		<div class=\"cabezalcompleto\">
			<div class=\"cabeza1\">
					<img src=\"$img\" style=\"height: 90px;width:150px;\" class=\"cabeza1logo\" />
				</div>
			<div class=\"cabeza2\">
					 $nombreempresa <br />de $razon_social_empresa<br />
					$correo_empresa | $direccion_empresa  | Telefono(s): $telefono_empresa<hr />
					$actividad_economica<br />";

$html .= "	
			</div>
			 <div class=\"cabeza3\">
				<strong>Timbrado &nbsp; $timbrado</strong><br /> 
				 <strong>Vigencia &nbsp; $timb_valido_desde hasta $timb_valido_hasta</strong><br /> 
				 <strong>RUC &nbsp; $ruc_empresa</strong><br /> 
				 <br />
				<span style='font-size:1.3em;'> NOTA DE CREDITO<br /> N&deg;: $numero_NC </span>

			</div>

		</div>
		";
$html .= "
		<div class=\"datosclientes\">
			<div class=\"cabezacentral\">
						<br />Fecha : $fecha 
						<br />Nombre / Raz&oacute;n Social :  $rz 
						<br />RUC / CI: $dct
						<br />Direcci&oacute;n : $direccion
						<br />Correo : $correo
			</div>
			<div class=\"cabezacentralizq\"> 
						<br />Motivo NC: $motivo
						<br />Documento Asociado: $docu 
						<br />Observaciones: $obs 
						
			</div>
		</div>
		<div style=\"border: 0px solid #000000; height: 400px;\">
			<div class=\"cajustadotit\">
				<div   class=\"describeproductocab\">
					Descripcion/Producto
				</div>
				<div class=\"describeproducto10cab\">
					Cantidad
				</div>
				<div   class=\"describeproducto10cab\">
					Precio
				</div>
				<div   class=\"describeproducto10cab\">
					Exentas
				</div>
				<div   class=\"describeproducto10cab\">
					5%
				</div>
				<div  class=\"describeproducto10cab\">
					10%
				</div>
				<div   class=\"describeproducto10cab\">
					Subtotal
				</div>
			</div>
			<div class=\"cajustado\">";
$tgiv10 = 0;
$iv10 = "";
$ex = "";
$iv5 = "";
$tiv10 = 0;
$tiv5 = 0;
$tx = 0;
$subtiva10 = 0;
while (!$rsc->EOF) {
    $idprodu = trim($rsc->fields['codproducto']);
    $tipoiva = intval($rsc->fields['iva_porc']);
    if ($tipoiva == 10) {

        $tiv10 = $rsc->fields['subtotal'];
        $subtiva10 = $subtiva10 + ($tiv10 / 11);
    }
    if ($tipoiva == 5) {

        $iv5 = $rsc->fields['subtotal'];
        $subtiva5 = $subtiva5 + ($iv5 / 21);
    }
    if ($tipoiva == 0) {

        $tx = $rsc->fields['subtotal'];

    }
    $subt = $subt + $tx + $tiv5 + $tiv10;
    $tgiv10 = $tgiv10 + $tiv10;
    $html .= "
						<div class=\"describeproducto\">
							<div style='margin-left: 4px;'>".$rsc->fields['descripcion']."</div>
						</div>
						<div class=\"describeproducto10\">
							".formatomoneda($rsc->fields['cantidad'], 4, 'N')."
						</div>
						<div class=\"describeproducto10\">
							".formatomoneda($rsc->fields['precio'], 4, 'N')."
						</div>
						<div class=\"describeproducto10\">
							".formatomoneda($tx, 0, 'N')."
						</div>
						<div class=\"describeproducto10\">
							".formatomoneda($iv5, 0, 'N')."
						</div>
						<div class=\"describeproducto10\">
							".formatomoneda($tiv10, 0, 'N')."
						</div>
						<div class=\"describeproducto10\">
							".formatomoneda($tx + $tiv5 + $tiv10, 4, 'N')."
						</div>";



    $rsc->MoveNext();

}
$txt = num2letras($subt);
$html .= "
			</div>
			<div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto;height:20px;text-align:left;\">
				<div style=\"border: 0px solid #000000; float:left; width:20%; height:20px;text-align:left;font-size:0.8em;font-weight:bold;\">
					Sub totales Gs
					
				</div>
				<div style=\"border: 0px solid #000000; float:left; width:75%; height:20px;text-align:right;font-size:1.0em;font-weight:bold;\">
					
					&nbsp;&nbsp; Exenta:&nbsp; ".formatomoneda($tx, 0, 'N')."&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp; 5%&nbsp;:".formatomoneda($iv5, 0, 'N')." &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;10%&nbsp;:".formatomoneda($tgiv10, 0, 'N')."
			
				
				</div>
			</div>
			<div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto;height:20px;text-align:left;\">
				
				<div style=\"border: 0px solid #000000; float:left; width:50%; height:20px;text-align:left;font-size:1.1em;font-weight:bold;\">
				Total Gs:&nbsp;&nbsp; $txt <br />
				
				</div>
				<div style=\"border: 0px solid #000000; float:left; width:48%; height:20px;text-align:left;font-size:1.1em;font-weight:bold;\">
					
					|&nbsp;&nbsp;TOTAL GS:&nbsp;".formatomoneda($subt, 0, 'N')."
			
				
				</div>
				
			</div>
			
			";
$html .= "
			<div style=\"border: 1px solid #000000; margin-left:auto; margin-right:auto; width:90%; height:20px;text-align:left;\">
				<div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
					Liquidacion IVA
				</div>
				<div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
					5%: ".formatomoneda($vv5, 0, 'N')."
				</div>
				<div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
					10%: ".formatomoneda($subtiva10, 0, 'N')."
				</div>
			</div>
		</div>";
$html .= "
		</body>
		</html>
		";


if ($tipo == 1) {

    //envio
    require_once  '../clases/mpdf/vendor/autoload.php';
    $ahora2 = date("YmdHi");
    $mpdf = new mPDF('c', 'Legal', 0, '', 0, 0, 0, 0, 0, 0);
    //$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
    $mpdf->showWatermarkText = false;
    $mini = "numero_$idunico";
    $mpdf->SetDisplayMode('fullpage');
    //$mpdf->shrink_tables_to_fit = 1;
    $mpdf->shrink_tables_to_fit = 2.5;
    $mpdf->WriteHTML($html);
    $mpdf->Output('presupuestos/presupuesto_'.$mini.'.pdf', "F");
    $file = "presupuestos/presupuesto_$mini.pdf";
    $tipotexto = intval($_REQUEST['tipotexto']);
    $texto = trim($_REQUEST['cuerpo']);
    require_once('enviar_email_central.php');
    $idusuario_pedido = $idusu;
    //$emailn="josesotto@gmail.com";

    $res = Enviomail($texto, $email, $file, $idusuario_pedido, $idusu, 0, $asunto);

    if ($res == 'OK') {
        unlink("$file");
        echo 'Email Enviado!!';
        exit;
    } else {
        echo $res;
        exit;
    }
} else {

    require_once  '../clases/mpdf/vendor/autoload.php';
    $ahora2 = date("YmdHi");
    $mpdf = new mPDF('c', 'Legal', 0, '', 0, 0, 0, 0, 0, 0);
    //$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
    $mpdf->showWatermarkText = false;
    $mini = "numero_$idunico";
    $mpdf->SetDisplayMode('fullpage');
    //$mpdf->shrink_tables_to_fit = 1;
    $mpdf->shrink_tables_to_fit = 2.5;
    $mpdf->WriteHTML($html);
    $mpdf->Output('temporales/nc_'.$mini.'.pdf', "I");


    //echo $html;exit;
}



//*---------------------------------------------                         --------------------------------------------------//
//------------------------------------------------Cabecera---------------------------------------------------------//
