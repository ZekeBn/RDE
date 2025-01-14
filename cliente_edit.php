<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
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

// cliente generico
$consulta = "
select ruc, razon_social
from cliente 
where 
borrable='N'
limit 1
";
$rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc_pred = $rscligen->fields['ruc'];
$razon_social_pred = $rscligen->fields['razon_social'];

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
    $idclientecat = antisqlinyeccion($_POST['idclientecat'], "int");
    $numero_casa = antisqlinyeccion($_POST['numero_casa'], "int");
    $idtiporeceptor_set = antisqlinyeccion($_POST['idtiporeceptor_set'], "int");

    $numero_casa = antisqlinyeccion($_POST['numero_casa'], "int");
    $departamento = antisqlinyeccion($_POST['departamento'], "int");
    $iddistrito = antisqlinyeccion($_POST['iddistrito'], "int");
    $idciudad = antisqlinyeccion($_POST['idciudad'], "int");
    $idtipooperacionset = antisqlinyeccion($_POST['idtipooperacionset'], "int");


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
    if (intval($_POST['idtiporeceptor_set']) == 0) {
        $valido = "N";
        $errores .= " - El campo naturaleza persona no puede estar vacio.<br />";
    }
    // conversiones
    if (intval($_POST['idtiporeceptor_set']) == 0) {
        $idtiporeceptor_set = 1;
    }
    if ($ruc_pred == trim($_POST['ruc'])) {
        $idtiporeceptor_set = 2;
    }
    if ($_POST['ruc_especial'] == 'S') {
        $idtiporeceptor_set = 2;
    }
    if (intval($_POST['idtipooperacionset']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar el tipo de operacion por defecto para informar a tributacion.<br />";
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

    // validaciones facturador electronico
    if ($facturador_electronico == 'S') {
        if (trim($_POST['direccion']) != '') {
            if (intval($_POST['numero_casa']) == 0) {
                $valido = "N";
                $errores .= " - El campo numero casa no puede estar vacio cuando se completa el domicilio.<br />";
            }
            if (intval($_POST['numero_casa']) < 100) {
                $valido = "N";
                $errores .= " - El campo numero casa debe ser mayor o igual a 100 cuando se completa el domicilio.<br />";
            }
        }

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
			sucursal=$sucursal,
			idclientecat=$idclientecat,
			numero_casa=$numero_casa,
			departamento=$departamento,
			id_distrito=$iddistrito,
			idciudad=$idciudad,
			idtiporeceptor_set=$idtiporeceptor_set,
			idtipooperacionset=$idtipooperacionset
			
		where
			idcliente = $idcliente
			and estado = 1
			and borrable = 'S'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //actualizar los datos SOLO de laa primera sucursal disponible (MATRIZ)

        $buscar = "Select * from sucursal_cliente where idcliente=$idcliente and estado=1 order by idsucursal_clie ASC LIMIT 1";
        $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idsucursal_clie = intval($rs->fields['idsucursal_clie']);

        if ($tipocliente == 1) {
            $nf = trim($_POST['nombre'].' '.$_POST['apellido']);
        } else {

            $nf = trim($_POST['razon_social']);
        }




        if ($idsucursal_clie > 0) {
            $update = "update sucursal_cliente set sucursal='$nf',direccion=$direccion,mail=$email,telefono=$celular where idcliente=$idcliente and idsucursal_clie=$idsucursal_clie";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

        }

        // para la set
        $consulta = "
		update cliente 
		set 
		idtiporeceptor_set = 2 
		where 
		idcliente = $idcliente
		and (ruc = 'X' or borrable = 'N')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: cliente.php");
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
				<div class="clearfix"></div>
<br /><br />
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>AVISO:</strong><br />
Si usted es facturador electronico y desea que se informe la direccion debe completar obligatoriamente: direccion, departamento, distrito, ciudad y numero de casa, en caso que alguno de los 5 no este cargado, se registrara en su sistema pero no se informara en la factura electronica.
</div>
<br /><br />
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Naturaleza Persona *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
$tipocliente = $rs->fields['tipocliente'];
// consulta
$consulta = "
    SELECT idnaturalezapersona, naturaleza_persona
    FROM naturaleza_persona
    order by naturaleza_persona asc
     ";

// valor seleccionado
if (isset($_POST['idtiporeceptor_set'])) {
    $value_selected = htmlentities($_POST['idtiporeceptor_set']);
} else {
    $value_selected = htmlentities($rs->fields['idtiporeceptor_set']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtiporeceptor_set',
    'id_campo' => 'idtiporeceptor_set',

    'nombre_campo_bd' => 'naturaleza_persona',
    'id_campo_bd' => 'idnaturalezapersona',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>
	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Operacion por Defecto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php

// consulta
$consulta = "
    SELECT idtipooperacionset, tipo_operacion
    FROM tipo_operacion_set
    order by tipo_operacion asc
     ";

// valor seleccionado
if (isset($_POST['idtipooperacionset'])) {
    $value_selected = htmlentities($_POST['idtipooperacionset']);
} else {
    $value_selected = $rs->fields['idtipooperacionset'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipooperacionset',
    'id_campo' => 'idtipooperacionset',

    'nombre_campo_bd' => 'tipo_operacion',
    'id_campo_bd' => 'idtipooperacionset',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>
	
	<div class="clearfix"></div>


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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Casa </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero_casa" id="numero_casa" value="<?php  if (isset($_POST['numero_casa'])) {
	    echo htmlentities($_POST['numero_casa']);
	} else {
	    echo htmlentities($rs->fields['numero_casa']);
	}?>" placeholder="numero casa" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Departamento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
    SELECT id as iddepartamento, descripcion as departamento
    FROM departamentos
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['departamento'])) {
    $value_selected = htmlentities($_POST['departamento']);
} else {
    $value_selected = htmlentities($rs->fields['departamento']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'departamento',
    'id_campo' => 'departamento',

    'nombre_campo_bd' => 'departamento',
    'id_campo_bd' => 'iddepartamento',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  onchange="departamento_sel();" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>          
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Distrito </label>
	<div class="col-md-9 col-sm-9 col-xs-12" id="distrito_box">
<?php

// consulta
$consulta = "
    SELECT iddistrito, distrito
    FROM distrito
    order by distrito asc
     ";

// valor seleccionado
if (isset($_POST['iddistrito'])) {
    $value_selected = htmlentities($_POST['iddistrito']);
} else {
    $value_selected = htmlentities($rs->fields['id_distrito']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddistrito',
    'id_campo' => 'iddistrito',

    'nombre_campo_bd' => 'distrito',
    'id_campo_bd' => 'iddistrito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' onchange="distrito_sel();"  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>         
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ciudad </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
    SELECT idciudad, nombre
    FROM ciudades
    order by nombre asc
     ";

// valor seleccionado
if (isset($_POST['idciudad'])) {
    $value_selected = htmlentities($_POST['idciudad']);
} else {
    $value_selected = htmlentities($rs->fields['idciudad']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idciudad',
    'id_campo' => 'idciudad',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idciudad',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?> 
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
	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Categoria Cliente </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idclientecat, cliente_categoria
FROM cliente_categoria
where
estado = 1
order by cliente_categoria asc
 ";

// valor seleccionado
if (isset($_POST['idclientecat'])) {
    $value_selected = htmlentities($_POST['idclientecat']);
} else {
    $value_selected = htmlentities($rs->fields['idclientecat']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idclientecat',
    'id_campo' => 'idclientecat',

    'nombre_campo_bd' => 'cliente_categoria',
    'id_campo_bd' => 'idclientecat',

    'value_selected' => $value_selected,

    'pricampo_name' => 'NO APLICA',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
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
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<hr />
					  

<div class="clearfix"></div>
<br /><br />

<div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Sucursales del Cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
				  	<?php

                        $buscar = "Select * from sucursal_cliente where idcliente=$idcliente order by idsucursal_clie ASC";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$treg = $rs->RecordCount();
if ($treg > 0) {

    ?>
				  
				  
					<div class="table-responsive">
					<table width="100%" class="table table-bordered jambo_table bulk_action">
					  <thead>
						<tr>
							
							<th align="center">Ids</th>
							<th align="center">Descripcion</th>
							<th align="center">Celular</th>
							<th align="center">Email</th>
							<th align="center">Direccion</th>
						</tr>
					  </thead>
					  <tbody>
						<?php
        while (!$rs->EOF) {
            ?>
						<tr>
							<td><?php echo formatomoneda($rs->fields['idsucursal_clie']);  ?></td>
							<td align="center"><?php echo $rs->fields['sucursal']; ?></td>
							<td align="center"><?php echo $rs->fields['telefono']; ?></td>
							<td align="center"><?php echo $rs->fields['mail']; ?></td>
							<td align="center"><?php echo $rs->fields['direccion']; ?></td>
						</tr>
					




						<?php
                $rs->MoveNext();
        }
    ?>
						</tbody>
					</table>
				</div>
						<?php } ?>
                  </div>
                </div>
              </div>
</div>
            <!-- SECCION --> 

			
			
			
                  </div>
                </div>
              </div>
            </div>
            
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
