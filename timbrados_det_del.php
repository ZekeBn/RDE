<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");



$idtanda = intval($_GET['id']);
if ($idtanda == 0) {
    header("location: timbrados.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from facturas 
where 
idtanda = $idtanda
and estado = 'A'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
$idtimbrado = intval($rs->fields['idtimbrado']);
$idtipodocutimbrado = $rs->fields['idtipodocutimbrado'];
if ($idtanda == 0) {
    header("location: timbrados.php");
    exit;
}
$consulta = "
select * from timbrado where idtimbrado = $idtimbrado and estado = 1
";
$rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtimbrado = intval($rstimb->fields['idtimbrado']);
$timbrado = intval($rstimb->fields['timbrado']);
$valido_desde = $rstimb->fields['inicio_vigencia'];
$valido_hasta = $rstimb->fields['inicio_vigencia'];
if ($idtimbrado == 0) {
    header("location: timbrados.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $secuencia1 = antisqlinyeccion($_POST['secuencia1'], "int");


    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {



        $consulta = "
		update facturas
		set
			estado = 'I',
			borrado_por = $idusu,
			borrado_el = '$ahora'
		where
			idtanda = $idtanda
			and estado = 'A'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // insertar log
        $consulta = "
		INSERT INTO facturaslog
		(idtanda, idtimbrado, idtipodocutimbrado, secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, log_registrado_el, log_registrado_por, log_tipomov, tipoimpreso,idtimbradotipo)
		SELECT idtanda, idtimbrado, idtipodocutimbrado,  secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, '$ahora', $idusu, 'D', tipoimpreso,idtimbradotipo
		FROM facturas 
		WHERE 
		idtanda = $idtanda
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: timbrados_det.php?id=$idtimbrado");
        exit;

    }

}

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Administracion de Timbrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idtanda *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idtanda" id="idtanda" value="<?php  if (isset($_POST['idtanda'])) {
	    echo htmlentities($_POST['idtanda']);
	} else {
	    echo htmlentities($rs->fields['idtanda']);
	}?>" placeholder="Idtanda" class="form-control" required readonly disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="timbrado" id="timbrado" value="<?php  if (isset($_POST['timbrado'])) {
	    echo intval($_POST['timbrado']);
	} else {
	    echo intval($rs->fields['timbrado']);
	}?>" placeholder="Timbrado" class="form-control" required readonly disabled="disabled"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados_det.php?id=<?php echo $idtimbrado; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
