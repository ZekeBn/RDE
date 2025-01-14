<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "24";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idproveedor = intval($_GET['id']);
if ($idproveedor == 0) {
    header("location: proveedores.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from proveedores 
where 
idproveedor = $idproveedor
and estado = 6
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idproveedor = intval($rs->fields['idproveedor']);
$ruc = $rs->fields['ruc'];
$nombre = $rs->fields['nombre'];
if ($idproveedor == 0) {
    header("location: proveedores.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {




    // validaciones basicas
    $valido = "S";
    $errores = "";

    $consulta = "
	select * from proveedores where estado = 1 and ruc = '$ruc' and idproveedor <> $idproveedor limit 1;
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idproveedor'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otro proveedor registrado con el mismo ruc.<br />";
    }

    $consulta = "
	select * from proveedores where estado = 1 and nombre = '$nombre' and idproveedor <> $idproveedor limit 1;
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idproveedor'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otro proveedor registrado con la misma razon social.<br />";
    }



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update proveedores
		set
			estado = 1
		where
			idproveedor = $idproveedor
			and estado = 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: proveedores_borrados.php");
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
                    <h2>Restaurar Proveedor</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idproveedor" id="idproveedor" value="<?php  if (isset($_POST['idproveedor'])) {
	    echo htmlentities($_POST['idproveedor']);
	} else {
	    echo htmlentities($rs->fields['idproveedor']);
	}?>" placeholder="Idproveedor" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} else {
	    echo htmlentities($rs->fields['ruc']);
	}?>" placeholder="Ruc" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentarios </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentarios" id="comentarios" value="<?php  if (isset($_POST['comentarios'])) {
	    echo htmlentities($_POST['comentarios']);
	} else {
	    echo htmlentities($rs->fields['comentarios']);
	}?>" placeholder="Comentarios" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-recycle"></span> Restaurar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='proveedores_borrados.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />

<br />
</form>
<div class="clearfix"></div>
<br />
<br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
