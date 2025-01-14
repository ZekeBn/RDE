<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");




$consulta = "
update insumos_lista set idproducto = NULL where idproducto = 0;
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idinsu = intval($_GET['id']);
if ($idinsu == 0) {
    echo "Error! no indico el articulo.";
    exit;
}
$buscar = "select *, 
(select descripcion from productos where idprod_serial = insumos_lista.idproducto) as producto
 from insumos_lista 
 where 
 idinsumo=$idinsu 
 and idempresa = $idempresa 
 and idproducto is null
 ";
$rsconecta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idinsu = intval($rsconecta->fields['idinsumo']);
if ($idinsu == 0) {
    echo "Error! articulo inexistente o es un producto.";
    exit;
}

$idinsumo = intval($_GET['id']);
if ($idinsumo == 0) {
    header("location: insumos_lista.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from insumos_lista 
where 
idinsumo = $idinsumo
and estado = 'A'
and idproducto is null
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idinsumo = intval($rs->fields['idinsumo']);
if ($idinsumo == 0) {
    header("location: insumos_lista.php");
    exit;
}





if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $idinsumo = antisqlinyeccion($idinsu, "int");

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


    if (intval($idinsu) == 0) {
        $valido = "N";
        $errores .= " - El campo idinsumo no puede ser cero o nulo.<br />";
    }




    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update insumos_lista
		set
			estado='I',
			borrado_por=$idusu,
			borrado_el='$ahora'
		where
			idempresa=$idempresa
			and idinsumo = $idinsu
			and idproducto is null
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza en produccion
        $consulta = "
		update prod_lista_objetivos 
		set 
		estado = 6,
		anulado_por = $idusu,
		anulado_el = '$ahora'
		where 
		estado = 1 
		and idinsumo in (select idinsumo from insumos_lista where estado = 'I')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_insumos.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

$buscar = "
Select * 
from productos 
where 
idempresa=$idempresa 
and borrado = 'N' 
and idprod_serial not in (select idproducto from insumos_lista where idinsumo <> $idinsu and idproducto is not null and idempresa=$idempresa)
order by descripcion asc";
//echo $buscar;
$gr2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



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
                    <h2>Borrar Articulo</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Articulo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idinsumo" id="idinsumo" value="<?php  if (isset($_POST['idinsumo'])) {
	    echo htmlentities($_POST['idinsumo']);
	} else {
	    echo htmlentities($rs->fields['idinsumo']);
	}?>" placeholder="Idinsumo" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="descripcion" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
