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
and estado = 6
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmotorista = intval($rs->fields['idmotorista']);
if ($idmotorista == 0) {
    header("location: motoristas.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $motorista = antisqlinyeccion($_POST['motorista'], "text");
    $idusu_asignado = antisqlinyeccion($_POST['idusu_asignado'], "int");
    $estado = antisqlinyeccion($_POST['estado'], "int");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $borrado_por = antisqlinyeccion($_POST['borrado_por'], "int");
    $borrado_el = antisqlinyeccion($_POST['borrado_el'], "text");


    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update motoristas
		set
			estado = 1
		where
			idmotorista = $idmotorista
			and estado = 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: motoristas.php");
        exit;

    }

}


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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idmotorista *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idmotorista" id="idmotorista" value="<?php  if (isset($_POST['idmotorista'])) {
	    echo htmlentities($_POST['idmotorista']);
	} else {
	    echo htmlentities($rs->fields['idmotorista']);
	}?>" placeholder="Idmotorista" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="motorista" id="motorista" value="<?php  if (isset($_POST['motorista'])) {
	    echo htmlentities($_POST['motorista']);
	} else {
	    echo htmlentities($rs->fields['motorista']);
	}?>" placeholder="Motorista" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idusu asignado </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idusu_asignado" id="idusu_asignado" value="<?php  if (isset($_POST['idusu_asignado'])) {
	    echo htmlentities($_POST['idusu_asignado']);
	} else {
	    echo htmlentities($rs->fields['idusu_asignado']);
	}?>" placeholder="Idusu asignado" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Estado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="estado" id="estado" value="<?php  if (isset($_POST['estado'])) {
	    echo htmlentities($_POST['estado']);
	} else {
	    echo htmlentities($rs->fields['estado']);
	}?>" placeholder="Estado" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Borrado por </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="borrado_por" id="borrado_por" value="<?php  if (isset($_POST['borrado_por'])) {
	    echo htmlentities($_POST['borrado_por']);
	} else {
	    echo htmlentities($rs->fields['borrado_por']);
	}?>" placeholder="Borrado por" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Borrado el </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="datetime" name="borrado_el" id="borrado_el" value="<?php  if (isset($_POST['borrado_el'])) {
	    echo htmlentities($_POST['borrado_el']);
	} else {
	    echo htmlentities($rs->fields['borrado_el']);
	}?>" placeholder="Borrado el" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-recycle"></span> Restaurar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='motoristas.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />

<br />
</form>

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
