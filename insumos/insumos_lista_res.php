<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$idinsumo = intval($_GET['id']);
if ($idinsumo == 0) {
    header("location: insumos_lista.php");
    exit;
}


$consulta = "
select *,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida,
(select usuario from usuarios where idusu = insumos_lista.borrado_por ) as borrado_por
from insumos_lista 
where 
 estado = 'I' 
 and idinsumo = $idinsumo
$whereadd
order by borrado_el desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idinsumo = intval($rs->fields['idinsumo']);
$anulado_el = trim($rs->fields['anulado_el']);
if ($idinsumo == 0) {
    header("location: insumos_lista.php");
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros


    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {
        // restaurar insumo
        $consulta = "
		update insumos_lista
		set
			estado = 'A'
		where
			idinsumo = $idinsumo
			and estado = 'I'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // restaurar producto objetivo solamente si es el que corresponde al mismo que habia cuando se borro el insumo
        $consulta = "
		update prod_lista_objetivos 
		set 
		estado = 1
		where 
		estado = 6 
		and idinsumo = $idinsumo
		and anulado_el = '$anulado_el'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: insumos_lista.php");
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
                    <h2>Restaurar Articulo</h2>
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
	}?>" placeholder="Idinsumo" class="form-control" required readonly disabled="disabled" />                    
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
	   <button type="submit" class="btn btn-success" ><span class="fa fa-recycle"></span> Restaurar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista_bor.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>



<br /><br />

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
