<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
require_once("includes/funciones_compras.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");


$idtran = intval($_GET['id']);
if ($idtran == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}
$consulta = "
SELECT * 
FROM tmpcompras
where
idtran  = $idtran
and estado = 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;
$idproveedor = intval($rstran->fields['proveedor']);
$idtran = intval($rstran->fields['idtran']);
if ($idtran == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}
//echo $idproveedor;exit;
$consulta = "
select tipocompra from preferencias limit 1
";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipocompra = intval($rspref->fields['tipocompra']);

$consulta = "
select obliga_cdc, tipocomprobante_def from preferencias_compras limit 1
";
$rsprefcompra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$obliga_tipocomprobante = 'S';
$obliga_cdc = trim($rsprefcompra->fields['obliga_cdc']);
$tipocomprobante_def = trim($rsprefcompra->fields['tipocomprobante_def']);

/*
$consulta="
select max(numero) as idtran from transacciones_compras
";
$rstranc=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idtran=intval($rstranc->fields['idtran'])+1;
if($idtran == 0){
    $idtran=1;
}*/

// si se selecciono proveedor
if ($idproveedor > 0) {

    // busca si existe en la bd
    $consulta = "
	select * from proveedores where estado = 1 and idproveedor = $idproveedor
	";
    $rsprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $incrementa = $rsprov->fields['incrementa'];
    $diasvence = $rsprov->fields['diasvence'];
    // si no existe redirecciona
    if (intval($rsprov->fields['idproveedor']) == 0) {
        header("location: tmpcompras_add.php");
        exit;
    }
    // si es incremental
    if ($incrementa == 'S') {
        $consulta = "
		SELECT fact_num 
		FROM facturas_proveedores 
		where 
		estado <> 6
		 and id_proveedor = $idproveedor
		 order by fact_num desc
		 limit 1
		";
        $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $facturacompra = "001-001-".agregacero($rsfac->fields['fact_num'] + 1, 7);
        $tipocomprobante_def = 1;
    }

    // actualiza numeracion proveedor
    $consulta = "
	update facturas_proveedores 
	set 
	fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
	where 
	fact_num is null
	and id_proveedor=$idproveedor;
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // habilita compras
    $consulta = "
	select * from compras_habilita where estado = 1 order by idcomprahab asc limit 1
	";
    $rscomhab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $fechadesde_habilita = $rscomhab->fields['fechadesde'];
    $fechahasta_habilita = $rscomhab->fields['fechahasta'];
    $fechadesde_txt = date("d/m/Y", strtotime($rscomhab->fields['fechadesde']));
    $fechahasta_txt = date("d/m/Y", strtotime($rscomhab->fields['fechahasta']));

    // buscar en la base el timbrado
    $consulta = "
	Select * 
	from compras 
	where 
	idproveedor=$idproveedor
	and estado=1 
	order by fechacompra desc 
	limit 1
	";
    //echo $consulta;exit;
    $rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $timbrado_bd = $rstimb->fields['timbrado'];
    $vto_timbrado_bd = $rstimb->fields['vto_timbrado'];
    //echo $timbrado_bd;exit;


}


$consulta = "
SELECT * 
FROM tipo_comprobante
where 
estado = 1
and vence_timbrado = 'S'
";
$rstipcompv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipos_comprobantes_vence = "";
while (!$rstipcompv->EOF) {
    $tipos_comprobantes_vence .= $rstipcompv->fields['idtipocomprobante'].',';
    $rstipcompv->MoveNext();
}
$tipos_comprobantes_vence = substr($tipos_comprobantes_vence, 0, -1);
$tipos_comprobantes_vence_ar = explode(',', $tipos_comprobantes_vence);

function limpiacdc($cdc)
{
    $cdc = trim($cdc);
    $cdc = str_replace(' ', '', $cdc);
    $cdc = htmlentities($cdc);
    $cdc = solonumeros($cdc);
    return $cdc;
}
if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $sucursal = antisqlinyeccion($_POST['sucursal'], "float");
    $fechahora = antisqlinyeccion($_POST['fechahora'], "text");
    $estado = antisqlinyeccion(1, "float");
    $idtipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
    $fecha_compra = antisqlinyeccion($_POST['fecha_compra'], "text");
    //$facturacompra_incrementa=antisqlinyeccion($_POST['facturacompra_incrementa'],"int");
    $totalcompra = antisqlinyeccion(0, "float");
    $monto_factura = antisqlinyeccion($_POST['monto_factura'], "float");
    //$idproveedor=antisqlinyeccion($_GET['idproveedor'],"int");
    $moneda = antisqlinyeccion('0', "int");
    $cambio = antisqlinyeccion('0', "float");
    $cambioreal = antisqlinyeccion('0', "float");
    $cambiohacienda = antisqlinyeccion('0', "float");
    $cambioproveedor = antisqlinyeccion('0', "float");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "text");
    $timbrado = antisqlinyeccion($_POST['timbrado'], "int");
    $vto_timbrado = antisqlinyeccion($_POST['vto_timbrado'], "text");
    $ocnum = antisqlinyeccion($_POST['ocnum'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idtipocomprobante = antisqlinyeccion(trim($_POST['idtipocomprobante']), "int");
    $cdc = antisqlinyeccion(limpiacdc($_POST['cdc']), "text");

    // validar formato de factura
    $factura_part = explode("-", trim($_POST['facturacompra']));
    $factura_prov_suc = trim($factura_part[0]);
    $factura_prov_pex = trim($factura_part[1]);
    $factura_prov_nro = trim($factura_part[2]);
    $facturacompleta = trim($factura_prov_suc.$factura_prov_pex.$factura_prov_nro);
    $facturacompra_incrementa = intval($factura_prov_nro);
    $facturacompra = antisqlinyeccion($facturacompleta, "text");
    $facturacompra_guion = antisqlinyeccion(trim($_POST['facturacompra']), "text");

    $parametros_array = [
        "sucursal" => $sucursal,
        "fechahora" => $fechahora,
        "estado" => $estado,
        "idempresa" => $idempresa,
        "idtipocompra" => $idtipocompra,
        "fecha_compra" => $fecha_compra,
        "totalcompra" => $totalcompra,
        "monto_factura" => $monto_factura,
        "idproveedor" => $idproveedor,
        "moneda" => $moneda,
        "cambio" => $cambio,
        "cambioreal" => $cambioreal,
        "cambiohacienda" => $cambiohacienda,
        "cambioproveedor" => $cambioproveedor,
        "vencimiento" => $vencimiento,
        "timbrado" => $timbrado,
        "vto_timbrado" => $vto_timbrado,
        "ocnum" => $ocnum,
        "registrado_por" => $registrado_por,
        "registrado_el" => $registrado_el,
        "facturacompra_guion" => $facturacompra_guion,
        "idtipocomprobante" => $idtipocomprobante,
        "cdc" => $cdc,
        "factura_part" => $factura_part,
        "factura_prov_suc" => $factura_prov_suc,
        "factura_prov_pex" => $factura_prov_pex,
        "factura_prov_nro" => $factura_prov_nro,
        "facturacompleta" => $facturacompleta,
        "facturacompra_incrementa" => $facturacompra_incrementa,
        "facturacompra" => $facturacompra,
        "obliga_cdc" => $obliga_cdc,
        "tipos_comprobantes_vence_ar" => $tipos_comprobantes_vence_ar,
        "idtran" => $idtran,
        "idusu" => $idusu,
        "edit" => 1
    ];


    $respuesta = validar_cabecera_compra($parametros_array);
    if ($respuesta['valido'] == 'N') {
        $valido = $respuesta['valido'];
        $errores .= nl2br($respuesta['errores']);
    }
    // si todo es correcto inserta
    if ($respuesta["valido"] == "S" && $valido == "S") {



        editar_cabecera_compra($parametros_array);//responde idtran


        // si es contado
        header("location: gest_reg_compras_resto_det.php?id=".$idtran);
        exit;

        // si es credito
        //header("location: tmpcompras_cred.php");
        //exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function alerta_modal(titulo,mensaje){
	$('#dialogobox').modal('show');
	$("#myModalLabel").html(titulo);
	$("#modal_cuerpo").html(mensaje);
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function tipo_compra(tipo){
	
	if(tipo == 2){
		$("#vencimiento_box").show();
		var fecha_compra = $("#fecha_compra").val();
		// si se indico fecha de compra
		if(fecha_compra != ''){
			var parametros = {
					"idproveedor"    : <?php echo $idproveedor ?>,
					"fechacompra"    : fecha_compra,
					"tipocompra"     : tipo
			};
			$.ajax({
				data:  parametros,
				url:   'cargavto_new.php',
				type:  'post',
				beforeSend: function () {
					$("#vencimiento").val('Cargando...');
				},
				success:  function (response) {
					//alert(response);
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						$("#vencimiento").val(obj.vencimiento);
					}else{
						alert(response);	
					}
				}
			});
		}
		
	}else{
		$("#vencimiento_box").hide();
	}
	
	
}
function validar_fecha(fecha){
	
	var fecha = $("#fecha_compra").val();
	var valido = 'S';
	var fe=fecha.split("-");
	var ano=fe[0];
	var mes=fe[1];
	var dia=fe[2];
	var f1 = new Date(ano, mes, dia); 
	var f2 = new Date(<?php echo date("Y"); ?>, <?php echo date("m"); ?>, <?php echo date("d"); ?>);
	var fdesde = new Date(<?php echo date("Y", strtotime($fechadesde_habilita)); ?>, <?php echo date("m", strtotime($fechadesde_habilita)); ?>, <?php echo date("d", strtotime($fechadesde_habilita)); ?>);
	var fhasta = new Date(<?php echo date("Y", strtotime($fechahasta_habilita)); ?>, <?php echo date("m", strtotime($fechahasta_habilita)); ?>, <?php echo date("d", strtotime($fechahasta_habilita)); ?>);
    // fecha no puede estar en el futuro
	if (f1 > f2){
		valido = 'N';
	}
	// la fecha no puede ser menor a la fecha desde
	if(f1 < fdesde){
		valido = 'N';	
	}
	// la fecha no puede ser mayor a la fecha hasta
	if(f1 > fhasta){
		valido = 'N';	
	}
	if(valido == 'N'){
		alerta_modal('Incorrecto','Fecha de compra incorrecta, habilitado entre: <?php echo date("d/m/Y", strtotime($fechadesde_txt)); ?> y <?php echo date("d/m/Y", strtotime($fechahasta_txt)); ?> y no pude ser mayor a hoy <?php echo date("d/m/Y", strtotime($ahora)); ?>.');
		$("#fecha_compra").val('');
	}else{
		//cargavto();
	}

	
}
function cambia_prov(idproveedor){
	document.location.href='tmpcompras_add.php?idproveedor='+idproveedor;	
}
function tipo_comprobante(tipo){
	// CDC si es factura electronica
	if(tipo == 4){
		$("#cdc_box").show();
	}else{
		$("#cdc_box").hide();
		$("#vto_timbrado_box").show();
	}
	var tipos_comprobantes_vence = '<?php echo $tipos_comprobantes_vence; ?>'; 
	// vencimiento timbrado
	if(inArray(tipo, tipos_comprobantes_vence)){
		$("#vto_timbrado_box").show();
	}else{
		$("#vto_timbrado_box").hide();
	}
	
}
function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}
function get_numbers(txt) {
	var res = txt.replace(/\D/g, '');
	//alert(res);
	return res;
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Compra</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

Obs: si respeta el orden de carga de datos algunos campos se completaran solos.
<div class="clearfix"></div>
<br />

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">(1) Proveedor *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php


    // consulta
    $consulta = "
    SELECT idproveedor, nombre
    FROM proveedores
    where
    estado = 1
    order by nombre asc
     ";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($idproveedor);
}

if ($idproveedor > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cambia_prov(this.value);" '.$add,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>
<?php if (intval($idproveedor) > 0) { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">(2) Fecha compra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_compra" id="fecha_compra" value="<?php  if (isset($_POST['fecha_compra'])) {
	    echo htmlentities($_POST['fecha_compra']);
	} else {
	    echo htmlentities($rstran->fields['fecha_compra']);
	}?>" placeholder="Fecha compra" class="form-control" required onBlur="validar_fecha();" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">(3) Factura Nro *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="facturacompra" id="facturacompra" value="<?php  if (isset($_POST['facturacompra'])) {
	    echo htmlentities($_POST['facturacompra']);
	} else {
	    echo htmlentities($rstran->fields['facturacompra_guion']);
	}?>" placeholder="Ej: 001-001-0000123" class="form-control" required <?php if ($incrementa == 'S') { ?> readonly<?php } ?> />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">(4) Monto factura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="monto_factura" id="monto_factura" value="<?php  if (isset($_POST['monto_factura'])) {
	    echo floatval($_POST['monto_factura']);
	} else {
	    echo floatval($rstran->fields['monto_factura']);
	}?>" placeholder="Monto factura" class="form-control" required />                    
	</div>
</div>
	
	


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">(5) Tipo Comp. *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
if ($incrementa == 'S') {
    $readonly_tipocomp = " readonly ";
    $whereadd_tipocom = " and idtipocomprobante = 1 ";
} else {
    $readonly_tipocomp = "";
    $whereadd_tipocom = "";
}

    // consulta
    $consulta = "
SELECT idtipocomprobante, tipocomprobante
FROM tipo_comprobante
where
estado = 1
$whereadd_tipocom
order by tipocomprobante asc
";

    // valor seleccionado
    if (isset($_POST['idtipocomprobante'])) {
        $value_selected = htmlentities($_POST['idtipocomprobante']);
    } else {
        $value_selected = htmlentities($rstran->fields['idtipocomprobante']);
        if (intval($rstran->fields['idtipocomprobante']) == 0) {
            $value_selected = $tipocomprobante_def;
        }
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idtipocomprobante',
        'id_campo' => 'idtipocomprobante',

        'nombre_campo_bd' => 'tipocomprobante',
        'id_campo_bd' => 'idtipocomprobante',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  required="required" onchange="tipo_comprobante(this.value);" '.$readonly_tipocomp,
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>                
	</div>
</div>

<?php
if ($obliga_cdc == 'S') {
    $cdc_req = '';
    $cdc_ast = "*";
} else {
    $cdc_req = '';
    $cdc_ast = "";
}


    // si envio  post
    if (isset($_POST['idtipocomprobante'])) {
        // si es electronica
        if (intval($_POST['idtipocomprobante']) == 4) {
            $muesta_cdc = 'style="display:display;"';
        } else {
            $muesta_cdc = 'style="display:none;"';
        }
    } else {
        // si es electronica
        if (intval($rstran->fields['idtipocomprobante']) == 4) {
            $muesta_cdc = 'style="display:display;"';
        } else {
            $muesta_cdc = 'style="display:none;"';
        }
    }
    ?>
<div class="col-md-6 col-sm-6 form-group" id="cdc_box" <?php echo $muesta_cdc; ?>>
	<label class="control-label col-md-3 col-sm-3 col-xs-12">CDC <?php $cdc_ast; ?></label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cdc" id="cdc" value="<?php  if (isset($_POST['cdc'])) {
	    echo limpiacdc($_POST['cdc']);
	} else {
	    echo limpiacdc($rstran->fields['cdc']);
	}?>" placeholder="CDC" class="form-control" <?php echo $cdc_req; ?> onchange="this.value = get_numbers(this.value)" />                    
	</div>
</div>	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> Timbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="timbrado" id="timbrado" value="<?php

    if ($incrementa != 'S') {
        if (isset($_POST['timbrado'])) {
            echo intval($_POST['timbrado']);
        } else {
            echo intval($rstran->fields['timbrado']);
            //echo $timbrado_bd;
        }
    } else {
        echo 1;
    }

    ?>" placeholder="Timbrado" class="form-control" required <?php if ($incrementa == 'S') { ?> readonly<?php } ?> />                    
	</div>
</div>

<?php
// si envio  post
if (isset($_POST['idtipocomprobante'])) {
    // si es con vencimiento
    if (in_array($_POST['idtipocomprobante'], $tipos_comprobantes_vence_ar)) {
        $muesta_ven = 'style="display:display;"';
    } else {
        $muesta_ven = 'style="display:none;"';
    }
} else {
    // si es con vencimiento
    if (in_array($rstran->fields['idtipocomprobante'], $tipos_comprobantes_vence_ar)) {
        $muesta_ven = 'style="display:display;"';
    } else {
        $muesta_ven = 'style="display:none;"';
    }
}
    ?>
<div class="col-md-6 col-sm-6 form-group" id="vto_timbrado_box" <?php echo $muesta_ven;  ?>>
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Vto timbrado </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="vto_timbrado" id="vto_timbrado" value="<?php
        if (isset($_POST['vto_timbrado'])) {
            echo htmlentities($_POST['vto_timbrado']);
        } else {
            echo htmlentities($rstran->fields['vto_timbrado']);
            //echo $vto_timbrado_bd;
        }
    ?>" placeholder="Vto timbrado" class="form-control" <?php if ($incrementa == 'S') { ?> readonly<?php } ?>  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> Orden Compra</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php require_once("tmpcompras_ocn.php"); ?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
    SELECT idsucu, nombre
    FROM sucursales
    where
    estado = 1
    order by nombre asc
     ";

    // valor seleccionado
    if (isset($_POST['sucursal'])) {
        $value_selected = htmlentities($_POST['sucursal']);
    } else {
        $value_selected = htmlentities($rstran->fields['sucursal']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'sucursal',
        'id_campo' => 'sucursal',

        'nombre_campo_bd' => 'nombre',
        'id_campo_bd' => 'idsucu',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> Tipo compra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
    SELECT idtipocompra, tipocompra
    FROM tipocompra
    order by tipocompra asc
     ";

    // valor seleccionado
    if (isset($_POST['idtipocompra'])) {
        $value_selected = htmlentities($_POST['idtipocompra']);
    } else {
        $value_selected = htmlentities($rstran->fields['tipocompra']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idtipocompra',
        'id_campo' => 'idtipocompra',

        'nombre_campo_bd' => 'tipocompra',
        'id_campo_bd' => 'idtipocompra',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" onchange="tipo_compra(this.value);" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>

<div id="vencimiento_box" class="col-md-6 col-sm-6 form-group" <?php

if (isset($_POST['idtipocompra'])) {
    $idtipocompra = $_POST['idtipocompra'];
} else {
    $idtipocompra = $rstran->fields['tipocompra'];
}

    if ($idtipocompra != 2) {


        ?>style="display:none;"<?php } ?>>
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> Vencimiento Factura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="vencimiento" id="vencimiento" value="<?php  if (isset($_POST['vencimiento'])) {
	    echo htmlentities($_POST['vencimiento']);
	} else {
	    echo htmlentities($rstran->fields['vencimiento']);
	}?>" placeholder="Vencimiento" class="form-control"  />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='tmpcompras.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
<?php } ?>
</form>
<div class="clearfix"></div>
<br /><br />





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        <!-- POPUP DE MODAL OCULTO -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->


        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
