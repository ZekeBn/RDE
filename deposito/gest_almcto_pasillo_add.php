<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idalmacto = intval($_GET['idalmacto']);
if ($idalmacto > 0) {
    $consulta = "SELECT gest_deposito_almcto_grl.nombre FROM gest_deposito_almcto_grl WHERE gest_deposito_almcto_grl.idalmacto = $idalmacto";
    $rs_almacto_nombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_almacenamiento = $rs_almacto_nombre->fields['nombre'];
}

$iddeposito = intval($_GET['idpo']);
if ($iddeposito > 0) {
    $consulta = "SELECT gest_depositos.descripcion FROM gest_depositos WHERE gest_depositos.iddeposito = $iddeposito";
    $rs_depo_name = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_deposito = $rs_depo_name->fields['descripcion'];
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
    if (!isset($idalmacto)) {
        $idalmacto = antisqlinyeccion($_POST['idalmacto'], "int");
    }
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = 1;

    $consulta = "SELECT idpasillo from gest_almcto_pasillo where nombre = $nombre  and idalmacto = $idalmacto and estado = 1";
    $rs_almacenamientos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idalmacenamiento_duplicado = intval($rs_almacenamientos->fields['idpasillo']);
    if ($idalmacenamiento_duplicado > 0) {
        $valido = "N";
        $errores .= " -Ya existe un Pasillo con el mismo nombre en este Almacenamiento.<br />";
    }

    if ($idalmacto == 0) {
        $valido = "N";
        $errores .= " - El campo idalmacto no puede ser cero o nulo.<br />";
    }

    if (trim($_POST['nombre']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre no puede estar vacio.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {
        $idpasillo = select_max_id_suma_uno("gest_almcto_pasillo", "idpasillo")["idpasillo"];

        $consulta = "
		insert into gest_almcto_pasillo
		(idpasillo, estado, nombre, idalmacto, registrado_por, registrado_el)
		values
		($idpasillo, $estado, $nombre, $idalmacto, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $location_add = "";
        if (($idalmacto) > 0) {
            $location_add .= "?idalmacto=$idalmacto";
        }
        if ($iddeposito > 0 && ($idalmacto) > 0) {
            $location_add .= "&idpo=$iddeposito";
        }
        if ($iddeposito > 0 && ($idalmacto) <= 0) {
            $location_add .= "?idpo=$iddeposito";
        }
        header("location: gest_deposito_almcto.php".$location_add);
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
  <script>
    function tipo_almacenamiento(value){
      var cara = $("#cara");
      if (value == 2){
        cara.attr("disabled",true);
      }
      if (value == 1){
        cara.attr("disabled",false);
      }
    }
  </script>
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
                    <h2>Almacenamiento detalles  <?php if (isset($nombre_almacenamiento)) { ?> para <?php echo $nombre_almacenamiento ?> <?php } ?> agregar Pasillos</h2>
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


<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Almacenamiento *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

                // consulta

                $consulta = "
				SELECT idalmacto, nombre
				FROM gest_deposito_almcto_grl 
				where
				estado = 1
				order by nombre asc
				";

// valor seleccionado
if (isset($_POST['idalmacto'])) {
    $value_selected = htmlentities($_POST['idalmacto']);
} else {
    $value_selected = $idalmacto;
}

if ($idalmacto > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idalmacto',
    'id_campo' => 'idalmacto',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idalmacto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  '.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control" required="required" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_deposito_almcto.php<?php if (isset($iddeposito)) { ?>?idpo=<?php echo $iddeposito;
	   } ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
	   } ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
