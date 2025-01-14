<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

// traer del cookie de asignacion para notas de credito igual que en ventas
$nota_suc = "001";
$nota_pexp = "001";



$idnotacred = intval($_GET['id']);
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}

$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 nota_credito_cabeza.estado = 1 
 and nota_credito_cabeza.idnotacred = $idnotacred
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idnotacred = intval($rs->fields['idnotacred']);
$notacredito_numero = $rs->fields['numero'];
$fecha_nota = $rs->fields['fecha_nota'];
$ruc_notacred = $rs->fields['ruc'];
$idcliente_notacred = $rs->fields['idcliente'];
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
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



    // si todo es correcto inserta
    if ($valido == "S") {



        $consulta = "
		update nota_credito_cabeza
		set
			estado = 6
		where
			idnotacred = $idnotacred
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        header("location: nota_credito_cabeza.php");
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
                    <h2>Borrar Nota de Credito a Clientes</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nota Numero </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero" id="numero" value="<?php  if (isset($_POST['numero'])) {
	    echo htmlentities($_POST['numero']);
	} else {
	    echo htmlentities($rs->fields['numero']);
	}?>" placeholder="ej:001-001-0000001" class="form-control" disabled  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha nota </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_nota" id="fecha_nota" value="<?php  if (isset($_POST['fecha_nota'])) {
	    echo htmlentities($_POST['fecha_nota']);
	} else {
	    echo date("Y-m-d");
	}?>" placeholder="Fecha nota" class="form-control" disabled  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motivo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idmotivo, descripcion
FROM nota_cred_motivos_cli
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idmotivo'])) {
    $value_selected = htmlentities($_POST['idmotivo']);
} else {
    $value_selected = htmlentities($rs->fields['idmotivo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmotivo',
    'id_campo' => 'idmotivo',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idmotivo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" disabled ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
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
if (isset($_POST['idsucursal'])) {
    $value_selected = htmlentities($_POST['idsucursal']);
} else {
    $value_selected = htmlentities($rs->fields['idsucursal']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucursal',
    'id_campo' => 'idsucursal',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" disabled ',
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
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='nota_credito_cabeza.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
