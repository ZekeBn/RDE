<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "192";
$asig_pag = 'S';
require_once("includes/rsusuario.php");

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $csucursal = antisqlinyeccion($_POST['sucursal'], "int");
    $cfactura_suc = antisqlinyeccion($_POST['factura_suc'], "int");
    $cfactura_pexp = antisqlinyeccion($_POST['factura_pexp'], "int");
    $cidsalon_usu = antisqlinyeccion($_POST['idsalon_usu'], "int");
    $cidterminal_usu = antisqlinyeccion($_POST['idterminal_usu'], "int");

    if (intval($_POST['sucursal']) <= 0) {
        $valido = "N";
        $errores .= " - Debe indicar el local de venta.<br />";
    }

    if (intval($_POST['factura_suc']) <= 0) {
        $valido = "N";
        $errores .= " - Debe indicar la sucursal de la factura.<br />";
    }

    if (intval($_POST['factura_pexp']) <= 0) {
        $valido = "N";
        $errores .= " - Debe indicar el punto de expedicion de la factura.<br />";
    }
    if (intval($_POST['sucursal']) > 0) {
        $sucursal = intval($_POST['sucursal']);
        $consulta = "
		select idsucu from sucursales where idsucu = $sucursal and estado = 1 limit 1
		";
        $rssucex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rssucex->fields['idsucu']) == 0) {
            $valido = "N";
            $errores .= " - Sucursal Inexistente.<br />";
        }
    }

    // si todo es correcto actualiza
    if ($valido == "S") {



        $consulta = "
		update usuarios
		set
			sucursal = $csucursal,
			factura_suc = $cfactura_suc,
			factura_pexp = $cfactura_pexp,
			idsalon_usu = $cidsalon_usu,
			idterminal_usu = $cidterminal_usu
		where
			idusu = $idusu
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // para el log
        $idsucursal_ant = $idsucursal;
        $idsucursal_asig = $csucursal;
        $factura_suc_ant = intval($factura_suc);
        $factura_suc_asig = $cfactura_suc;
        $factura_pexp_ant = intval($factura_pexp);
        $factura_pexp_asig = $cfactura_pexp;
        $idsalon_usu_ant = intval($idsalon_usu);
        $idsalon_usu_asig = $cidsalon_usu;
        $idterminal_usu_ant = intval($idterminal_usu);
        $idterminal_usu_asig = $cidterminal_usu;

        // registra cambio
        $consulta = "
		INSERT INTO asignasucu_auto
		(idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa, factura_suc_ant, factura_suc_asig, factura_pexp_ant, factura_pexp_asig, idsalon_usu_ant, idsalon_usu_asig) 
		VALUES 
		($idusu,$idsucursal_ant,$idsucursal_asig,'$ahora',$idempresa, $factura_suc_ant, $factura_suc_asig, $factura_pexp_ant, $factura_pexp_asig, $idsalon_usu_ant, $idsalon_usu_asig)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //echo $consulta;

        setcookie("csucursal", intval($idsucursal_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_suc", intval($factura_suc_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_pexp", intval($factura_pexp_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidsalon_usu", intval($idsalon_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidterminal_usu", intval($idterminal_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años

        $_SESSION['idterminal_usu'] = $idterminal_usu_asig;

        header("location: asigna_sucursal_pc.php?ok=s#asignado");
        exit;


    }



}

/*
if(isset($_POST['sucursal'])){
    $csucursal=intval($_POST['sucursal']);
    // busca que exista y le pertenezca
    $buscar="select * from sucursales where idempresa=$idempresa and idsucu = $csucursal order by nombre asc";
    $rssuc=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    // si existe
    if($rssuc->fields['idsucu'] > 0){
        $csucursal=$rssuc->fields['idsucu'];
        setcookie("csucursal",$csucursal,time()+(10*365*24*60*60)); // 10 años
        header("location: asigna_sucursal_pc.php?ok=s");
        exit;
    }

}*/






// busca los parametros locales y actualiza usuario si son diferentes
$csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
$cfactura_suc = antisqlinyeccion($_COOKIE['cfactura_suc'], 'int');
$cfactura_pexp = antisqlinyeccion($_COOKIE['cfactura_pexp'], 'int');
$cidsalon_usu = antisqlinyeccion($_COOKIE['cidsalon_usu'], 'int');
$cidterminal_usu = antisqlinyeccion($_COOKIE['cidterminal_usu'], 'int');
if ($csucursal > 0) {

    $diferente = "N";

    // conversioens
    if (intval($cfactura_suc) == 0) {
        $cfactura_suc = 1;
    }
    if (intval($cfactura_pexp) == 0) {
        $cfactura_pexp = 1;
    }

    // busca si son diferentes al del usuario
    if ($idsucursal != $csucursal) {
        $diferente = "S";
    }
    if ($factura_suc != $cfactura_suc) {
        $diferente = "S";
    }
    if ($factura_pexp != $cfactura_pexp) {
        $diferente = "S";
    }
    if (intval($idsalon_usu) != intval($cidsalon_usu)) {
        $diferente = "S";
    }
    if (intval($idterminal_usu) != intval($cidterminal_usu)) {
        $diferente = "S";
    }
    //echo $idsalon_usu.'-'.$cidsalon_usu;
    if ($diferente == 'S') {
        // asignar a usuario la sucursal
        $consulta = "
		UPDATE usuarios 
		SET
			sucursal=$csucursal,
			factura_suc = $cfactura_suc,
			factura_pexp = $cfactura_pexp,
			idsalon_usu = $cidsalon_usu,
			idterminal_usu = $cidterminal_usu
		WHERE
			idempresa=$idempresa
			and idusu=$idusu
			and estado=1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // tambien en la variable de sesion
        $_SESSION['idsucursal'] = $csucursal;


        // para el log
        $idsucursal_ant = $idsucursal;
        $idsucursal_asig = $csucursal;
        $factura_suc_ant = $factura_suc;
        $factura_suc_asig = $cfactura_suc;
        $factura_pexp_ant = $factura_pexp;
        $factura_pexp_asig = $cfactura_pexp;
        $idsalon_usu_ant = antisqlinyeccion($idsalon_usu, "int");
        $idsalon_usu_asig = $cidsalon_usu;
        $idterminal_usu_ant = antisqlinyeccion($idterminal_usu, "int");
        $idterminal_usu_asig = $cidsalon_usu;


        // registra cambio
        $consulta = "
		INSERT INTO asignasucu_auto
		(idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa, factura_suc_ant, factura_suc_asig, factura_pexp_ant, factura_pexp_asig, idsalon_usu_ant, idsalon_usu_asig) 
		VALUES 
		($idusu,$idsucursal_ant,$idsucursal_asig,'$ahora',$idempresa, $factura_suc_ant, $factura_suc_asig, $factura_pexp_ant, $factura_pexp_asig, $idsalon_usu_ant, $idsalon_usu_asig)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // asigna cookie
        setcookie("csucursal", intval($idsucursal_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_suc", intval($factura_suc_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_pexp", intval($factura_pexp_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidsalon_usu", intval($idsalon_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidterminal_usu", intval($idterminal_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años

        $_SESSION['idterminal_usu'] = $idterminal_usu_asig;

        //header("location: index.php?s=ok");
        header("location: asigna_sucursal_pc.php?ok=s#asignado");
        exit;

    }
}


/*
// si la pc esta asignada a una sucursal actualiza el usuario
if(isset($_COOKIE['csucursal']) && trim($_COOKIE['csucursal']) != '' && $_GET['ok'] == 's'){
    $csucursal=antisqlinyeccion($_COOKIE['csucursal'],'int');
    //echo $csucursal;
    $buscar="select * from sucursales where idempresa=$idempresa and idsucu = $csucursal order by nombre asc";
    $rspc=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $sucursal_activa=strtoupper(trim($rspc->fields['nombre']));
    $idsucuact=$rspc->fields['idsucu'];

    // si la sucursal actual es diferente de la asignada a la pc
    if($idsucursal != $idsucuact){

        if($idsucuact > 0){

            // asignar a usuario la sucursal
            $consulta="
            UPDATE usuarios
            SET
                sucursal=$idsucuact
            WHERE
                idempresa=$idempresa
                and idusu=$idusu
                and estado=1
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            // tambien en la variable de sesion
            $_SESSION['idsucursal'] = $idsucuact;

            // registra cambio
            $consulta="
            INSERT INTO asignasucu_auto
            (idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa)
            VALUES
            ($idusu,$idsucursal,$idsucuact,'$ahora',$idempresa)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            header("location: index.php?s=ok");
            exit;

        }

    }

}
*/


//print_r($_COOKIE);



$datos_locales_ar = [
    'factura_suc' => $cfactura_suc,
    'factura_pexp' => $cfactura_pexp,
    'idsucursal' => $idsucursal,
    'idsalon_usu' => $cidsalon_usu,
    'idterminal_usu' => $cidterminal_usu,
    'idusu' => $idusu
];
$datos_locales = json_encode($datos_locales_ar, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);


// buscar url_local de la sucursal
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
$rsimpprint = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$url_local = 'http://localhost/impresorweb/datos_locales.php';
// si existe reemplaza, caso contrario usa la de caja que trae arriba
if (trim($rsimpprint->fields['url_local']) != '') {
    $url_local = trim($rsimpprint->fields['url_local']).'datos_locales.php';
}
?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function guarda_datos_locales(){
	var direccionurl = '<?php echo $url_local; ?>';
	var parametros = {
	  'datos_locales'   : '<?php echo $datos_locales; ?>', 
	  'accion' : 'a' // a: agregar/editar l: leer
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#asignadiv").html('Asignando...');				
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					$("#asignadiv").html('');
				}else{
					$("#asignadiv").html("Error: ".obj.errores);
				}
			}else{
				alert(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Archivo local de asignaciones no existe. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			//alert('Pagina no encontrada [404]');
			alert('Archivo local de asignaciones no existe [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
	});
}
</script>
  </head>

  <body class="nav-md" <?php if ($_GET['ok'] == 's') { ?>onLoad="guarda_datos_locales();"<?php } ?>>
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
                    <h2>Asignar PC a Sucursal y Punto Expedicion</h2>
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
<?php if (trim($_GET['sucex']) == "n") { ?>
<div class="alert alert-warning alert-dismissible fade in" role="alert">
La sucursal a la que estaba asignada tu usuario ya no existe, favor asignar a otra.
</div>
<?php } ?>
			<p align="left"> Atencion! se asignara a esta computadora los parametros seleccionados. <br /> <br /></p>
			<p align="center">&nbsp;</p>

<div id="asignadiv"></div>

			<form id="form1" name="form1" method="post" action="">
            
<?php

//lista de sucursales
$buscar = "select * from sucursales where idempresa=$idempresa and estado <> 6 order by nombre asc";
$rsfd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Local de Venta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
			<select name="sucursal" id="sucursal" class="form-control" required>
			      <option value="0" selected="selected">Seleccionar</option>
			      <?php while (!$rsfd->EOF) {?>
			      <option value="<?php echo $rsfd->fields['idsucu']?>" <?php if ($rsfd->fields['idsucu'] == $idsucursal) {?>selected="selected"<?php }?>><?php echo $rsfd->fields['nombre']?></option>
			      <?php $rsfd->MoveNext();
			      }?>
		        </select>
	</div>
</div>

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="factura_suc" id="factura_suc" value="<?php if (intval($cfactura_suc) > 0) {
	    echo agregacero($cfactura_suc, 3);
	} ?>" placeholder="00x" class="form-control" required />
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Punto Expedicion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="factura_pexp" id="factura_pexp" value="<?php if (intval($cfactura_pexp) > 0) {
	    echo agregacero($cfactura_pexp, 3);
	} ?>"  placeholder="00x" class="form-control" required />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Salon *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
    SELECT idsalon, CONCAT(salon.nombre,' [',sucursales.nombre,']') as nombre
    FROM salon
	inner join sucursales on sucursales.idsucu = salon.idsucursal
    where
   	salon.estado_salon = 1
	and sucursales.estado = 1
    order by salon.nombre asc
     ";

// valor seleccionado
if (isset($_POST['idsalon_usu'])) {
    $value_selected = htmlentities($_POST['idsalon_usu']);
} else {
    $value_selected = htmlentities($cidsalon_usu);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsalon_usu',
    'id_campo' => 'idsalon_usu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsalon',

    'value_selected' => $value_selected,

    'pricampo_name' => 'No Aplica',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>
				
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Terminal </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
    SELECT idterminal, terminal.terminal as nombre
    FROM terminal
    where
   	terminal.estado = 1
    order by terminal.terminal asc
     ";

// valor seleccionado
if (isset($_POST['idterminal'])) {
    $value_selected = htmlentities($_POST['idterminal']);
} else {
    $value_selected = htmlentities($cidterminal_usu);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idterminal_usu',
    'id_campo' => 'idterminal_usu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idterminal',

    'value_selected' => $value_selected,

    'pricampo_name' => 'No Aplica',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>
            
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Asignar</button>

        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<hr />
<img src="img/partes_factura.jpg" class="img-thumbnail" />
<br /><br />
<img src="img/timbset1.jpg" class="img-thumbnail" />
<br /><br />
<hr />
<a name="asignado" id="asignado"></a>
            <p align="left" style="font-size:14px;">
            <strong style="color:#F00;">Parametros asignados a esta PC:</strong><br /><br />
<?php
if (isset($_COOKIE['csucursal']) && trim($_COOKIE['csucursal']) != '') {
    $csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
    //echo $csucursal;
    $buscar = "select * from sucursales where idempresa=$idempresa and idsucu = $csucursal order by nombre asc";
    $rspc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $sucursal_activa = strtoupper(trim($rspc->fields['nombre']));


}
if (isset($_COOKIE['cidsalon_usu']) && trim($_COOKIE['cidsalon_usu']) != '') {
    $cidsalon_usu = antisqlinyeccion($_COOKIE['cidsalon_usu'], 'int');
    //echo $csucursal;
    $buscar = "select * from salon where idsalon = $idsalon_usu order by nombre asc";
    $rssal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $salon_activo = strtoupper(trim($rssal->fields['nombre']));


}
if (isset($_COOKIE['cidterminal_usu']) && trim($_COOKIE['cidterminal_usu']) != '') {
    $cidterminal_usu = antisqlinyeccion($_COOKIE['cidterminal_usu'], 'int');
    //echo $csucursal;
    $buscar = "select * from terminal where idterminal = $cidterminal_usu order by terminal asc";
    $rssal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $terminal_activa = strtoupper(trim($rssal->fields['terminal']));


}
?>
<?php if ($sucursal_activa != '') { ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <tr>
    <td width="50%">Local de Venta:</td>
    <td><?php echo $sucursal_activa?></td>
  </tr>
  <tr>
    <td width="50%">Salon:</td>
    <td><?php echo $salon_activo ?></td>
  </tr>
  <tr>
    <td width="50%">Terminal:</td>
    <td><?php echo $terminal_activa ?></td>
  </tr>
  <tr>
    <td>Factura Sucursal:</td>
    <td><?php echo agregacero($cfactura_suc, 3); ?></td>
  </tr>
  <tr>
    <td>Factura Punto Expedicion:</td>
    <td><?php echo agregacero($cfactura_pexp, 3); ?></td>
  </tr>
  
<?php


$consulta = "
SELECT * 
FROM lastcomprobantes 
where 
idsuc=$factura_suc
and pe=$factura_pexp
and idempresa=$idempresa 
order by ano desc 
limit 1
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
  <tr>
    <td>Proxima Factura:</td>
    <td><?php echo agregacero($cfactura_suc, 3); ?>-<?php echo agregacero($cfactura_pexp, 3); ?>-<?php echo agregacero($rs->fields['numfac'] + 1, 7); ?></td>
  </tr>
</table>
</div>
<?php } else { ?>
<strong style="color:#FF0000">SIN SUCURSAL ASIGNADA</strong>
<?php } ?>
            </p>
<hr />
<img src="img/comunicado_timbrado_largo.png" class="img-thumbnail" />
           
            
            
            
			<br /><br /><br />



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
           <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Historico de Asignaciones:</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php
    $consulta = "
select *,
(select usuario from usuarios where asignasucu_auto.idusu = usuarios.idusu) as registrado_por,
(select nombre from sucursales where idsucu = asignasucu_auto.idsucursal_ant) as sucursal_ant,
(select nombre from sucursales where idsucu = asignasucu_auto.idsucursal_asig) as sucursal_asig
from asignasucu_auto 
where 
idusu not in (select idusu from usuarios where super = 'S')
order by idasignasucu desc
limit 20
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<strong>Viendo Ultimas 20:</strong> <a href="asigna_sucursal_his.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver mas</a><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Fecha/hora</th>
			<th align="center">Usuario</th>
			<th align="center">Sucursal Anterior</th>
			<th align="center">Sucursal Asignada</th>
			<th align="center">Factura sucursal Anterior</th>
			<th align="center">Factura sucursal Asignada</th>
			<th align="center">Factura Punto Exp Anterior</th>
			<th align="center">Factura Punto Exp Asignado</th>


		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php if ($rs->fields['fechahora'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['sucursal_ant']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal_asig']); ?></td>
			<td align="center"><?php echo agregacero($rs->fields['factura_suc_ant'], 3); ?></td>
			<td align="center"><?php echo agregacero($rs->fields['factura_suc_asig'], 3); ?></td>
			<td align="center"><?php echo agregacero($rs->fields['factura_pexp_ant'], 3); ?></td>
			<td align="center"><?php echo agregacero($rs->fields['factura_pexp_asig'], 3); ?></td>


		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />




<br /><br /><br />
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
