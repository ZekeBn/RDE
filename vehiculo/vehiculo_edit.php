<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
// TODO:PREGUNTAR MODULO SI AGREGAR NOMAS
$modulo = "42";
$submodulo = "615";

$dirsup = "S";
require_once("../includes/rsusuario.php");



$idvehiculo = intval($_GET['id']);
if ($idvehiculo == 0) {
    header("location: vehiculo.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from vehiculo 
where 
idvehiculo = $idvehiculo
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idvehiculo = intval($rs->fields['idvehiculo']);
if ($idvehiculo == 0) {
    header("location: vehiculo.php");
    exit;
}

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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
    $nro_motor = antisqlinyeccion($_POST['nro_motor'], "text");
    $capacidad_kg = antisqlinyeccion($_POST['capacidad_kg'], "float");
    $capacidad_volumen_m3 = antisqlinyeccion($_POST['capacidad_volumen_m3'], "float");
    $anho_fabricacion = antisqlinyeccion($_POST['anho_fabricacion'], "text");
    $chapa = antisqlinyeccion($_POST['chapa'], "text");
    $chasis = antisqlinyeccion($_POST['chasis'], "text");
    $modelo = antisqlinyeccion($_POST['modelo'], "text");
    $color = antisqlinyeccion($_POST['color'], "text");
    $idvehiculo_propietario = antisqlinyeccion($_POST['idvehiculo_propietario'], "int");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = 1;
    $anulado_por = antisqlinyeccion($_POST['anulado_por'], "int");
    $anulado_el = antisqlinyeccion($_POST['anulado_el'], "text");
    $idmarca = antisqlinyeccion($_POST['idmarca'], "int");




    /*
    codigo
        if(trim($_POST['codigo']) == ''){
            $valido="N";
            $errores.=" - El campo codigo no puede estar vacio.<br />";
        }
    */
    /*
    nro_motor
        if(trim($_POST['nro_motor']) == ''){
            $valido="N";
            $errores.=" - El campo nro_motor no puede estar vacio.<br />";
        }
    */
    if (floatval($_POST['capacidad_kg']) <= 0) {
        $valido = "N";
        $errores .= " - El campo capacidad_kg no puede ser cero o negativo.<br />";
    }
    if (floatval($_POST['capacidad_volumen_m3']) <= 0) {
        $valido = "N";
        $errores .= " - El campo capacidad_volumen_m3 no puede ser cero o negativo.<br />";
    }
    /*
    anho_fabricacion
        if(trim($_POST['anho_fabricacion']) == ''){
            $valido="N";
            $errores.=" - El campo anho_fabricacion no puede estar vacio.<br />";
        }
    */
    /*
    chapa
        if(trim($_POST['chapa']) == ''){
            $valido="N";
            $errores.=" - El campo chapa no puede estar vacio.<br />";
        }
    */
    /*
    chasis
        if(trim($_POST['chasis']) == ''){
            $valido="N";
            $errores.=" - El campo chasis no puede estar vacio.<br />";
        }
    */
    /*
    modelo
        if(trim($_POST['modelo']) == ''){
            $valido="N";
            $errores.=" - El campo modelo no puede estar vacio.<br />";
        }
    */
    if (intval($_POST['idvehiculo_propietario']) == 0) {
        $valido = "N";
        $errores .= " - El campo idvehiculo_propietario no puede ser cero o nulo.<br />";
    }
    /*
    registrado_por
    */
    /*
    registrado_el
    */
    /*
    anulado_por
        if(intval($_POST['anulado_por']) == 0){
            $valido="N";
            $errores.=" - El campo anulado_por no puede ser cero o nulo.<br />";
        }
    */
    /*
    anulado_el
        if(trim($_POST['anulado_el']) == ''){
            $valido="N";
            $errores.=" - El campo anulado_el no puede estar vacio.<br />";
        }
    */
    if (intval($_POST['idmarca']) == 0) {
        $valido = "N";
        $errores .= " - El campo idmarca no puede ser cero o nulo.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
	update vehiculo
	set
		nro_motor=$nro_motor,
		capacidad_kg=$capacidad_kg,
		capacidad_volumen_m3=$capacidad_volumen_m3,
		anho_fabricacion=$anho_fabricacion,
		chapa=$chapa,
		chasis=$chasis,
		modelo=$modelo,
		color=$color,
		idvehiculo_propietario=$idvehiculo_propietario,
		idmarca=$idmarca
	where
		idvehiculo = $idvehiculo
		and estado = 1
	";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: vehiculo.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Vehiculos</h2>
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


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Marca *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <?php

          // consulta

          $consulta = "
          SELECT idmarca, marca
          FROM marca
          where
          idestado = 1
          order by marca asc
          ";

// valor seleccionado
if (isset($_POST['idmarca'])) {
    $value_selected = htmlentities($_POST['idmarca']);
} else {
    $value_selected = $rs->fields['idmarca'];
}

if ($_GET['idmarca'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
  'nombre_campo' => 'idmarca',
  'id_campo' => 'idmarca',

  'nombre_campo_bd' => 'marca',
  'id_campo_bd' => 'idmarca',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
  'pricampo_value' => '',
  'style_input' => 'class="form-control"',
  'acciones' => '  "'.$add,
  'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Modelo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="modelo" id="modelo" value="<?php  if (isset($_POST['modelo'])) {
	    echo htmlentities($_POST['modelo']);
	} else {
	    echo htmlentities($rs->fields['modelo']);
	}?>" placeholder="Modelo" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Color </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="color" id="color" value="<?php  if (isset($_POST['color'])) {
	    echo htmlentities($_POST['color']);
	} else {
	    echo htmlentities($rs->fields['color']);
	}?>" placeholder="Ej.: Rojo" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">A&ntilde;o fabricacion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="anho_fabricacion" id="anho_fabricacion" value="<?php  if (isset($_POST['anho_fabricacion'])) {
	    echo htmlentities($_POST['anho_fabricacion']);
	} else {
	    echo htmlentities($rs->fields['anho_fabricacion']);
	}?>" placeholder="Anho fabricacion" class="form-control"  />                    
	</div>
</div>







<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Propietario *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	  <?php

                    // consulta

                    $consulta = "
					SELECT idpropietario, nombre
					FROM vehiculo_propietario
					where
					estado = 1
					order by nombre asc
					";

// valor seleccionado
if (isset($_POST['idpropietario'])) {
    $value_selected = htmlentities($_POST['idpropietario']);
} else {
    $value_selected = $rs->fields['idvehiculo_propietario'];
}

if ($_GET['idpropietario'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idvehiculo_propietario',
    'id_campo' => 'idvehiculo_propietario',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpropietario',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Chapa </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="chapa" id="chapa" value="<?php  if (isset($_POST['chapa'])) {
	    echo htmlentities($_POST['chapa']);
	} else {
	    echo htmlentities($rs->fields['chapa']);
	}?>" placeholder="Chapa" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Chasis </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="chasis" id="chasis" value="<?php  if (isset($_POST['chasis'])) {
	    echo htmlentities($_POST['chasis']);
	} else {
	    echo htmlentities($rs->fields['chasis']);
	}?>" placeholder="Chasis" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nro motor </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nro_motor" id="nro_motor" value="<?php  if (isset($_POST['nro_motor'])) {
	    echo htmlentities($_POST['nro_motor']);
	} else {
	    echo htmlentities($rs->fields['nro_motor']);
	}?>" placeholder="Nro motor" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Capacidad kg *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="capacidad_kg" id="capacidad_kg" value="<?php  if (isset($_POST['capacidad_kg'])) {
	    echo floatval($_POST['capacidad_kg']);
	} else {
	    echo floatval($rs->fields['capacidad_kg']);
	}?>" placeholder="Capacidad kg" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Capacidad volumen m3 *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="capacidad_volumen_m3" id="capacidad_volumen_m3" value="<?php  if (isset($_POST['capacidad_volumen_m3'])) {
	    echo floatval($_POST['capacidad_volumen_m3']);
	} else {
	    echo floatval($rs->fields['capacidad_volumen_m3']);
	}?>" placeholder="Capacidad volumen m3" class="form-control" required="required" />                    
	</div>
</div>





<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='vehiculo.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
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
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
