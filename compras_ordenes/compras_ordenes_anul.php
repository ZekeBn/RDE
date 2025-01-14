<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "280";
$dirsup = "S";
require_once("../includes/rsusuario.php");


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (intval($_GET['idproveedor']) > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $whereadd .= "  and compras_ordenes.idproveedor = $idproveedor " ;
}

$consulta = "
select *,
(select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where compras_ordenes.generado_por = usuarios.idusu) as generado_por,
(select usuario from usuarios where compras_ordenes.borrado_por = usuarios.idusu) as borrado_por,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 estado = 2 
 and fecha >= '$desde'
 and fecha <= '$hasta'
$whereadd
order by ocnum desc
limit 50
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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



            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Anular Ordenes de Compra</h2>
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
<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="desde" id="desde" value="<?php  echo $desde;  ?>" placeholder="Fecha Desde" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="hasta" id="hasta" value="<?php   echo $hasta; ?>" placeholder="Fecha Hasta" class="form-control" required="required" />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idproveedor, nombre
FROM proveedores
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_GET['idproveedor'])) {
    $value_selected = htmlentities($_GET['idproveedor']);
} else {
    //$value_selected=htmlentities($rs->fields['idproveedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
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
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>



<br />
</form>
<div class="clearfix"></div>
<br /><hr /><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Orden Nº</th>
			<th align="center">Fecha Orden</th>
			<th align="center">Generado por</th>
			<th align="center">Tipocompra</th>
			<th align="center">Fecha entrega</th>
			<th align="center">Proveedor</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="compras_ordenes_anul_del.php?id=<?php echo $rs->fields['ocnum']; ?>"  class="btn btn-sm btn-default" title="Anular" data-toggle="tooltip" data-placement="right"  data-original-title="Anular"><span class="fa fa-trash"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['ocnum']); ?></td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
			<td align="center"><?php if (intval($rs->fields['tipocompra']) == 2) {
			    echo "Credito";
			} else {
			    echo "Contado";
			}?></td>
			<td align="center"><?php if ($rs->fields['fecha_entrega'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_entrega']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
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
