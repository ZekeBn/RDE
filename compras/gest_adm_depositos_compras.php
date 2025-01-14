<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "107";
//error_reporting(E_ALL);
require_once("../includes/rsusuario.php");



$compra_desde = date("Y-".'01-01');
$compra_hasta = date("Y-m-d");

if (isset($_GET['desde']) && trim($_GET['desde']) != '') {
    $compra_desde = date("Y-m-d", strtotime($_GET['desde']));
    $compra_hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
$desde = $compra_desde;
$hasta = $compra_hasta;

if (intval($_GET['idproveedor']) > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $whereadd .= " and compras.idproveedor = $idproveedor ";
}


if (intval($_GET['idinsumo']) > 0) {
    $idinsumo = intval($_GET['idinsumo']);
    $whereadd .= " and compras.idcompra in (select idcompra from compras_detalles where codprod =  $idinsumo group by idcompra) ";
}

if (trim($_GET['factura']) != '') {
    $factura = antisqlinyeccion($_GET['factura'], "text");
    $whereadd .= " and compras.facturacompra = $factura ";
}



$buscar = "
Select compras.idtran, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
proveedores.nombre as proveedor, compras.facturacompra,
(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
compras.total as monto_factura, compras.ocnum, 
(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
registrado as registrado_el, compras.idcompra
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
inner join compras on compras.idcompra = gest_depositos_compras.idcompra
where 
revisado_por=0 
and compras.estado <> 6
and compras.fechacompra >= '$compra_desde'
and compras.fechacompra <= '$compra_hasta'
$whereadd
order by fecha_compra desc 
limit 50
";
//echo $buscar;
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tda = $rs->RecordCount();


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
                    <h2>Verificar compra</h2>
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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Compras Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="desde" id="compra_desde" value="<?php  echo htmlentities($compra_desde); ?>" placeholder="Compras Desde" class="form-control" required />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Compras Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="hasta" id="compra_hasta" value="<?php   echo htmlentities($compra_hasta); ?>" placeholder="Compras Hasta" class="form-control" required />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
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


<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Contiene Articulo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idinsumo, CONCAT(descripcion,' [',idinsumo,'] ') as descripcion
FROM insumos_lista
where
estado = 'A'
and hab_compra = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['idinsumo'])) {
    $value_selected = htmlentities($_GET['idinsumo']);
} else {
    //$value_selected=htmlentities($rs->fields['idproveedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idinsumo',
    'id_campo' => 'idinsumo',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idinsumo',

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


<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="factura" id="factura" value="<?php   echo htmlentities($_GET['factura']); ?>" placeholder="Factura completa" class="form-control" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Generar</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><hr /><br />


<strong>Compras finalizadas que aun no se dio ingreso al stock:</strong><br />
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
            <th align="center">Idtran</th>
            <th align="center">Idcompra</th>
			
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Monto factura</th>
			<th align="center">Orden Num.</th>
			<th align="center">Sucursal</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php

while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_adm_depositos_compras_det.php?idcompra=<?php echo $rs->fields['idcompra']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>

			</td>
			<td align="right"><?php echo intval($rs->fields['idtran']);  ?></td>
            <td align="right"><?php echo intval($rs->fields['idcompra']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_factura']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
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
