 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "206";
require_once("includes/rsusuario.php");

$idsucu = intval($_GET['idsucu']);

if ($idsucu > 0) {
    $whereadd .= " and productos_sucursales.idsucursal = $idsucu ";
}
$solostockmin = trim($_GET['stm']);
if ($solostockmin == 's') {
    $whereadd .= " and idinsumo in (select idinsumo from stock_minimo where idsucursal = $idsucu) ";
}
$tprod = 0;

if ($_GET['id_categoria'] > 0) {
    $idcategoria = intval($_GET['id_categoria']);
    $whereadd .= " and productos.idcategoria = $idcategoria ";
}
if ($_GET['idproveedor'] > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $whereadd .= " and insumos_lista.idproveedor = $idproveedor ";
}
if (trim($_GET['producto']) != '') {
    $producto = antisqlinyeccion(trim($_GET['producto']), "like");
    $whereadd .= " and productos.descripcion like '%$producto%' ";
}

if ($idsucu > 0) {
    $buscar = "
    Select
    insumos_lista.idproducto as codigo_producto, 
    insumos_lista.idinsumo as codigo_articulo,
    productos.barcode as codigo_barra,
    productos.descripcion as producto,
    medidas.nombre as medida,
    insumos_lista.costo,
    productos_sucursales.precio, 
    (select nombre from sucursales where sucursales.idsucu = productos_sucursales.idsucursal) as sucursal
    from productos
    inner join productos_sucursales on productos.idprod_serial=productos_sucursales.idproducto
    inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    where 
    productos.borrado='N' 
    $whereadd
    order by productos.descripcion asc
    ";
    //echo $buscar;
    $rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tprod = $rspp->RecordCount();

    $buscar = "
    Select 
    count(*) as total
    from productos
    where 
    productos.borrado='N' 
    ";
    $rstpp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
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
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Lista de Precios</h2>
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
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
} else {
    //$value_selected=htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

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
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Stock Min *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <select name="stm" class="form-control">
        <option value="n" <?php if ($_GET['stm'] == 'n') {
            echo 'selected';
        }  ?> >TODOS</option>
        <option value="s" <?php if ($_GET['stm'] == 's') {
            echo 'selected';
        }  ?> >SOLO STOCK MIN CARGADO</option>
    </select>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Categoria *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT id_categoria, nombre
FROM categorias
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_GET['id_categoria'])) {
    $value_selected = htmlentities($_GET['id_categoria']);
} else {
    //$value_selected=htmlentities($rs->fields['id_categoria']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'id_categoria',
    'id_campo' => 'id_categoria',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_categoria',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
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
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Producto </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="producto" id="producto" value="<?php  if (isset($_GET['producto'])) {
        echo htmlentities($_GET['producto']);
    } ?>" placeholder="Producto" class="form-control" />
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>

        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<hr />
            <?php
if ($idsucu > 0) {

    if ($tprod > 0) {
        ?>
<p><a href="inf_lista_precios_xls.php?idsuc=<?php echo $idsucu ?>&stm=<?php echo substr(htmlentities($_GET['stm']), 0, 1); ?>&id_categoria=<?php echo intval($_GET['id_categoria']); ?>&idproveedor=<?php echo intval($_GET['idproveedor']); ?>&producto=<?php echo antixss($_GET['producto']); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar</a></p>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th>Codigo Producto</th>
            <th>Codigo Articulo</th>
            <th>Codigo Barras</th>
            <th>Producto</th>
            <th>Precio Costo</th>
            <th>Precio Venta</th>
            <th>Margen</th>
            <th>% Margen</th>
        </tr>
        </thead>
        <tbody>
    <?php while (!$rspp->EOF) {
        $pcosto = $rspp->fields['costo'];
        $pventa = $rspp->fields['precio'];
        $margen = $pventa - $pcosto;
        $margen_porc = (($pventa - $pcosto) / $pventa) * 100;

        ?>
        <tr>
            <td><?php echo $rspp->fields['codigo_producto']?></td>
            <td><?php echo $rspp->fields['codigo_articulo']?></td>
            <td><?php echo $rspp->fields['codigo_barra']?></td>
            <td><?php echo $rspp->fields['producto']?></td>
            <td align="right"><?php echo formatomoneda($pcosto, '2', 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($pventa, '2', 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($margen, '2', 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($margen_porc, '2', 'N'); ?>%</td>
        </tr>
    <?php $rspp->MoveNext();
    }?>
        </tbody>
    </table>

</div>
                
                
                <?php } else { ?>
                
                <div align="center">
                    <h2>No se encontraron producto con los filtros indicados</h2>
                
                
                </div>
                <?php } ?>
<?php } ?>
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

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
