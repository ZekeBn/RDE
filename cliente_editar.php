<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}
// preferencias caja
$consulta = "
SELECT 
valida_ruc
FROM preferencias_caja 
WHERE  
idempresa = $idempresa 
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$valida_ruc = trim($rsprefcaj->fields['valida_ruc']);

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
    $idvendedor = antisqlinyeccion('', "int");
    $sexo = antisqlinyeccion('', "text");
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $apellido = antisqlinyeccion($_POST['apellido'], "text");
    $nombre_corto = antisqlinyeccion('', "text");
    $idtipdoc = antisqlinyeccion(1, "int");
    $documento = antisqlinyeccion($_POST['documento'], "text");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $telefono = antisqlinyeccion($_POST['telefono'], "text");
    $celular = antisqlinyeccion($_POST['celular'], "float");
    $email = antisqlinyeccion($_POST['email'], "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $comentario = antisqlinyeccion($_POST['comentario'], "text");
    $fechanac = antisqlinyeccion($_POST['fechanac'], "text");
    $tipocliente = antisqlinyeccion($_POST['idclientetipo'], "int");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");
    $fantasia = antisqlinyeccion($_POST['fantasia'], "text");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    $ruc_especial = antisqlinyeccion($_POST['ruc_especial'], "text");
    if ($_POST['ruc_especial'] == 'S') {
        $carnet_diplomatico = $ruc;
    } else {
        $carnet_diplomatico = antisqlinyeccion('', "text");
    }
    $idvendedor = antisqlinyeccion($_POST['idvendedor'], "text");
    $idcanalventacli = antisqlinyeccion($_POST['idcanalventa'], "int");
    $porce_desc = antisqlinyeccion($_POST['porce_desc'], "float");
    $sucursal = antisqlinyeccion($_POST['idsucu'], "int");


    if (trim($_POST['ruc']) == '') {
        $valido = "N";
        $errores .= " - El campo ruc no puede estar vacio.<br />";
    }
    if (intval($_POST['idclientetipo']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo de cliente no puede estar vacio.<br />";
    }

    if (trim($_POST['razon_social']) == '') {
        $valido = "N";
        $errores .= " - El campo razon_social no puede estar vacio.<br />";
    }
    // si es una persona
    if (intval($_POST['idclientetipo']) == 1) {
        if (trim($_POST['nombre']) == '') {
            $valido = "N";
            $errores .= " - El campo nombre no puede estar vacio.<br />";
        }
        if (trim($_POST['apellido']) == '') {
            $valido = "N";
            $errores .= " - El campo apellido no puede estar vacio.<br />";
        }
    }
    // si es una empresa
    if (intval($_POST['idclientetipo']) == 2) {
        if (trim($_POST['fantasia']) == '') {
            $valido = "N";
            $errores .= " - El campo fantasia no puede estar vacio cuando es una persona juridica.<br />";
        }
        $nombre = "NULL";
        $apellido = "NULL";
    }
    $ruc_ar = explode("-", trim($_POST['ruc']));
    $ruc_pri = intval($ruc_ar[0]);
    $ruc_dv = intval($ruc_ar[1]);
    if ($_POST['ruc_especial'] != 'S') {

        if ($valida_ruc == 'S') {
            //print_r($ruc_ar);exit;
            if ($ruc_pri <= 0) {
                $errores .= "- El ruc no puede ser cero o menor.<br />";
                $valido = "N";
            }
            if (trim($ruc_ar[1]) == '') {
                $errores .= "- No se indico el digito verificador del ruc.<br />";
                $valido = "N";
            }
            if (strlen($ruc_dv) <> 1) {
                $errores .= "- El digito verificador del ruc no puede tener 2 numeros.<br />";
                $valido = "N";
            }
            if (calcular_ruc($ruc_pri) <> $ruc_dv) {
                $digitocor = calcular_ruc($ruc_pri);
                $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.<br />";
                $ruc = $ruc_pri.'-'.$digitocor;
                //echo $ruc;exit;
                $valido = "N";
            }
        } else {
            $ruc = substr(trim($_POST['ruc']), 0, 15);
        }
        if ($ruc == $ruc_pred && $razon_social <> $razon_social_pred) {
            $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.<br />";
            $valido = "N";
        }
        if (trim($_POST['ruc']) <> $ruc_pred && $razon_social == $razon_social_pred) {
            $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.<br />";
            $valido = "N";
        }
    }
    // validar que el ruc no exista excepto si es el ruc generico
    $consulta = "
	select * 
	from cliente 
	where 
	ruc = $ruc 
	and estado = 1
	and borrable = 'S'
	and idcliente <> $idcliente
	and ruc <> '$ruc_pred'
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idcliente'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe un cliente con el ruc ingresado, editelo para evitar duplicidad.<br />";
    }
    if ($_POST['documento'] > 0) {
        // validar documento
        $consulta = "
		select * 
		from cliente 
		where 
		documento = $documento
		and estado = 1
		and borrable = 'S'
		and idcliente <> $idcliente
		limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsex->fields['idcliente'] > 0) {
            $valido = "N";
            $errores .= " - Ya existe un cliente con el documento ingresado, editelo para evitar duplicidad.<br />";
        }
    }
    if (intval($_POST['idcanalventa']) > 0) {
        if (intval($_POST['idvendedor']) == 0) {
            $valido = "N";
            $errores .= " - Debe indicar el vendedor cuando se registra un canal de venta.<br />";
        }
    }
    if (intval($_POST['porce_desc']) > 100) {
        $valido = "N";
        $errores .= " - El porcentaje de descuento automatico no puede ser mayor a 100.<br />";
    }
    if (intval($_POST['porce_desc']) < 0) {
        $valido = "N";
        $errores .= " - El porcentaje de descuento automatico no puede ser menor a 0.<br />";
    }


    // conversiones
    if (intval($_POST['porce_desc']) == 0) {
        $porce_desc = antisqlinyeccion('', "float");
    }




    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update cliente
		set
			tipocliente=$tipocliente,
			nombre=$nombre,
			apellido=$apellido,
			documento=$documento,
			ruc=$ruc,
			telefono=$telefono,
			celular=$celular,
			email=$email,
			direccion=$direccion,
			comentario=$comentario,
			fechanac=$fechanac,
			razon_social=$razon_social,
			fantasia=$fantasia,
			actualizado_el='$ahora',
			actualizado_por=$idusu,
			diplomatico = $ruc_especial,
			carnet_diplomatico=$carnet_diplomatico,
			idvendedor=$idvendedor,
			idcanalventacli=$idcanalventacli,
			porce_desc=$porce_desc,
			sucursal=$sucursal
			
		where
			idcliente = $idcliente
			and estado = 1
			and borrable = 'S'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: cliente_editar.php?id=".$idcliente."&ok=s");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function cliente_tipo(tipo){
	// persona fisica
	if(tipo == 1){
		$("#nombre_box").show();
		$("#apellido_box").show();
		//$("#fantasia_box").hide();
	// persona juridica
	}else{
		$("#nombre_box").hide();
		$("#apellido_box").hide();
		//$("#fantasia_box").show();
	}
	
}
</script>
  </head>

  <body class="nav-md" onLoad="cliente_tipo('<?php echo $rs->fields['tipocliente'] ?>');">
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
                    <h2>Editar Cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if (trim($_GET['ok']) != "") { ?>
<div class="alert alert-success alert-dismissible fade in" role="alert">
<strong>Registro Exitoso</strong><br />
</div>
<?php } ?>
<?php if (trim($_GET['ok']) == "") { ?>
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo cliente *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    $tipocliente = $rs->fields['tipocliente'];
    // consulta
    $consulta = "
    SELECT idclientetipo, clientetipo
    FROM cliente_tipo
    where
    estado = 1

    order by clientetipo asc
     ";

    // valor seleccionado
    if (isset($_POST['idclientetipo'])) {
        $value_selected = htmlentities($_POST['idclientetipo']);
    } else {
        $value_selected = htmlentities($rs->fields['tipocliente']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idclientetipo',
        'id_campo' => 'idclientetipo',

        'nombre_campo_bd' => 'clientetipo',
        'id_campo_bd' => 'idclientetipo',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" onchange="cliente_tipo(this.value)" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
	    echo htmlentities($_POST['razon_social']);
	} else {
	    echo htmlentities($rs->fields['razon_social']);
	}?>" placeholder="Razon social" class="form-control" required  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} else {
	    echo htmlentities($rs->fields['ruc']);
	}?>" placeholder="Ruc" class="form-control"  required  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group" id="nombre_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control"   />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellido_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellido" id="apellido" value="<?php  if (isset($_POST['apellido'])) {
	    echo htmlentities($_POST['apellido']);
	} else {
	    echo htmlentities($rs->fields['apellido']);
	}?>" placeholder="Apellido" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre de Fantasia </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_POST['fantasia'])) {
	    echo htmlentities($_POST['fantasia']);
	} else {
	    echo htmlentities($rs->fields['fantasia']);
	}?>" placeholder="Fantasia" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
	    echo htmlentities($_POST['documento']);
	} else {
	    echo htmlentities($rs->fields['documento']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities($rs->fields['telefono']);
	}?>" placeholder="Telefono" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Celular </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="celular" id="celular" value="<?php  if (isset($_POST['celular'])) {
	    echo floatval($_POST['celular']);
	} else {
	    echo floatval($rs->fields['celular']);
	}?>" placeholder="Celular" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="email" id="email" value="<?php  if (isset($_POST['email'])) {
	    echo htmlentities($_POST['email']);
	} else {
	    echo htmlentities($rs->fields['email']);
	}?>" placeholder="Email" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha nacimiento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fechanac" id="fechanac" value="<?php  if (isset($_POST['fechanac'])) {
	    echo htmlentities($_POST['fechanac']);
	} else {
	    echo htmlentities($rs->fields['fechanac']);
	}?>" placeholder="Fechanac" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Canal Venta </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
select idcanalventa, canal_venta
from canal_venta 
where 
 estado = 1 
order by canal_venta asc
 ";

    // valor seleccionado
    if (isset($_POST['idcanalventa'])) {
        $value_selected = htmlentities($_POST['idcanalventa']);
    } else {
        $value_selected = htmlentities($rs->fields['idcanalventacli']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idcanalventa',
        'id_campo' => 'idcanalventa',

        'nombre_campo_bd' => 'canal_venta',
        'id_campo_bd' => 'idcanalventa',

        'value_selected' => $value_selected,

        'pricampo_name' => 'SIN CANAL',
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor Asignado </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
    // consulta
    $consulta = "
select idvendedor, CONCAT(nombres,' ',apellidos) as vendedor
from vendedor 
where 
 estado = 1 
order by CONCAT(nombres,' ',apellidos) asc
 ";

    // valor seleccionado
    if (isset($_POST['idvendedor'])) {
        $value_selected = htmlentities($_POST['idvendedor']);
    } else {
        $value_selected = htmlentities($rs->fields['idvendedor']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idvendedor',
        'id_campo' => 'idvendedor',

        'nombre_campo_bd' => 'vendedor',
        'id_campo_bd' => 'idvendedor',

        'value_selected' => $value_selected,

        'pricampo_name' => 'SIN VENDEDOR',
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">% Descuento Automatico </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="porce_desc" id="porce_desc" value="<?php  if (isset($_POST['porce_desc'])) {
	    echo floatval($_POST['porce_desc']);
	} else {
	    echo floatval($rs->fields['porce_desc']);
	}?>" placeholder="Descuento Automatico" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_POST['comentario'])) {
	    echo htmlentities($_POST['comentario']);
	} else {
	    echo htmlentities($rs->fields['comentario']);
	}?>" placeholder="Comentario" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC Especial *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['ruc_especial'])) {
    $value_selected = htmlentities($_POST['ruc_especial']);
} else {
    $value_selected = $rs->fields['diplomatico'];
}
    // opciones
    $opciones = [
        'NO' => 'N',
        'SI (DIPLOMATICOS, ONG, ETC)' => 'S',
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'ruc_especial',
        'id_campo' => 'ruc_especial',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);

    ?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal </label>
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
    if (isset($_POST['idsucu'])) {
        $value_selected = htmlentities($_POST['idsucu']);
    } else {
        $value_selected = htmlentities($rs->fields['sucursal']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idsucu',
        'id_campo' => 'idsucu',

        'nombre_campo_bd' => 'nombre',
        'id_campo_bd' => 'idsucu',

        'value_selected' => $value_selected,

        'pricampo_name' => 'TODAS',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  ',
        'autosel_1registro' => 'S'

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
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>

        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>

<div class="clearfix"></div>
<br /><br />
<?php } //if(trim($_GET['ok']) == ""){?>


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
