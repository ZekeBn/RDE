<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");


if (trim($_GET['descripcion']) != '') {
    $descripcion = antisqlinyeccion($_GET['descripcion'], "like");
    $whereadd .= " and insumos_lista.descripcion like '%$descripcion%' ";
}

// codigo sobre escribe a todo
if ($_GET['idinsumo'] > 0) {
    $idinsumo = intval($_GET['idinsumo']);
    $whereadd = " and insumos_lista.idinsumo = $idinsumo ";
}
if ($_GET['soloart'] != '') {
    $idinsumo = intval($_GET['idinsumo']);
    if ($_GET['soloart'] == 'pr') {
        $whereadd .= " and insumos_lista.idproducto > 0 ";
    } elseif ($_GET['soloart'] == 'ar') {
        $whereadd .= " and insumos_lista.idproducto is null ";
    } else {
        $whereadd .= "";
    }
}

$consulta = "
select *,
(select descripcion from produccion_centros where idcentroprod=insumos_lista.idcentroprod) as centroprod,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida
from insumos_lista 
where 
 estado = 'A' 
 and hab_invent = 1
$whereadd
order by descripcion asc
limit 100
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
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Articulos</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_GET['descripcion'])) {
	    echo htmlentities($_GET['descripcion']);
	} ?>" placeholder="Articulo" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idinsumo" id="idinsumo" value="<?php  if (intval($_GET['idinsumo']) > 0) {
	    echo intval($_GET['idinsumo']);
	} ?>" placeholder="Codigo Articulo" class="form-control"  />                    
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
<br /><hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Codigo Articulo</th>
			<th align="center">Codigo Producto</th>
			<th align="center">Articulo</th>
			<th align="center">Categoria</th>
			<th align="center">Subcategoria</th>
			<th align="center">Grupo Stock</th>
			<th align="center">Medida</th>
			<th align="center">Ult. Costo</th>
			<th align="center">IVA %</th>
			<th align="center">Habilita compra</th>
			<th align="center">Habilita inventario</th>
			
			<th align="center">CPR</th>
			<th align="center">Proveedor</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="insumos_lista_ultcosto_edit.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['categoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['subcategoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['grupo_stock']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoiva']); ?>%</td>
			<td align="center"><?php if ($rs->fields['hab_compra'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
			<td align="center"><?php if ($rs->fields['hab_invent'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['centroprod']); ?></td>
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
