<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
// TODO:PREGUNTAR MODULO SI AGREGAR NOMAS
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
//buscando moneda nacional
$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];



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
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $estado = 1;
    $idempresa = intval($_SESSION['idempresa']);
    $defecto = antisqlinyeccion($_POST['defecto'], "int");
    $abreviatura = antisqlinyeccion($_POST['abreviatura'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idmoneda = antisqlinyeccion($_POST['idmoneda'], "int");




    if (trim($_POST['nombre']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre no puede estar vacio.<br />";
    }




    // si todo es correcto inserta
    if ($valido == "S") {
        if ($defecto == 1) {
            $consulta = "
			update paises_propio
			set
				defecto=0
			where
				defecto=1
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        $idpais = select_max_id_suma_uno("paises_propio", "idpais")["idpais"];
        $consulta = "
		insert into paises_propio
		(idpais,nombre, estado, idempresa, defecto, abreviatura, registrado_por, registrado_el, idmoneda)
		values
		($idpais,$nombre, $estado, $idempresa, $defecto, $abreviatura, $registrado_por, $registrado_el, $idmoneda)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: paises.php");
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
                    <h2>Paises</h2>
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

<div class="col-md-6 col-sm-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control" required="required" />                    
	</div>
</div>



<div class="col-md-6 col-xs-12 form-group" >
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Defecto *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<select class="custom-select form-control" name="defecto" id="defecto">
			<?php if ($_POST['defecto'] == '1') {?>
				<option value="1" selected>Si</option>
				<option  value="0">No</option>
			<?php } else { ?>
				<option value="1" >Si</option>
				<option  value="0" selected>No</option>
			<?php } ?>
		</select>
		</div>
	</div>

<div class="col-md-6 col-sm-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Abreviatura </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="abreviatura" id="abreviatura" value="<?php  if (isset($_POST['abreviatura'])) {
	    echo htmlentities($_POST['abreviatura']);
	} else {
	    echo htmlentities($rs->fields['abreviatura']);
	}?>" placeholder="Abreviatura" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

                // consulta

                $consulta = "
				SELECT idtipo, descripcion
				FROM tipo_moneda
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($rs->fields['idmoneda'])) {
    $value_selected = htmlentities($rs->fields['idmoneda']);
} else {
    $value_selected = $id_moneda_nacional;
}

if ($_GET['idmoneda'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmoneda',
    'id_campo' => 'idmoneda',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' "'.$add,
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
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='paises.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
