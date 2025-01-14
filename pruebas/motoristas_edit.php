<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$idmotorista = intval($_GET['id']);
if ($idmotorista == 0) {
    header("location: motoristas.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from motoristas 
where 
idmotorista = $idmotorista
and estado = 1
limit 1
";

$consulta2 = "
select * 
from usuarios 
where 
estado = 1
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$rs2 = $conexion->Execute($consulta2) or die(errorpg($conexion, $consulta2));
$idmotorista = intval($rs->fields['idmotorista']);
if ($idmotorista == 0) {
    header("location: motoristas.php");
    exit;
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
    $motorista = antisqlinyeccion($_POST['motorista'], "text");
    $idusu_asignado = antisqlinyeccion($_POST['idusu_asignado'], "int");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");

    $myfile = fopen("log.txt", "a") or die("Unable to open file!");
    fwrite($myfile, $_POST["idusu_asignado_select"]);
    fclose($myfile);


    if (trim($_POST['motorista']) == '') {
        $valido = "N";
        $errores .= " - El campo motorista no puede estar vacio.<br />";
    }
    /*
    idusu_asignado
        if(intval($_POST['idusu_asignado']) == 0){
            $valido="N";
            $errores.=" - El campo idusu_asignado no puede ser cero o nulo.<br />";
        }
    */
    /*
    borrado_por
    */
    /*
    borrado_el
    */


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update motoristas
		set
			motorista=$motorista,
			idusu_asignado=$idusu_asignado
		where
			idmotorista = $idmotorista
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: motoristas.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?><!DOCTYPE html>
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
                    <h2>Titulo Modulo</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="motorista" id="motorista" value="<?php  if (isset($_POST['motorista'])) {
	    echo htmlentities($_POST['motorista']);
	} else {
	    echo htmlentities($rs->fields['motorista']);
	}?>" placeholder="Motorista" class="form-control" required="required"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idusu asignado </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
  // consulta
$consulta = "
SELECT idusu, nombres
FROM usuarios
where
estado = 1
order by nombres asc
 ";

// valor seleccionado
if (isset($_POST['idusu'])) {
    $value_selected = htmlentities($_POST['idusu']);
} else {
    $value_selected = htmlentities($rs->fields['idusu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idusu_asignado',
    'id_campo' => 'idusu_asignado',

    'nombre_campo_bd' => 'nombres',
    'id_campo_bd' => 'idusu',

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
  

    <!--  -->
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='motoristas.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
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
<script>
  var select = document.getElementById('idusu_asignado_select');
  var input = document.getElementById("idusu_asignado");
  select.addEventListener("change", (event) => {
    input.value=select.options[select.selectedIndex].value;
  });

</script>
  </body>
</html>
