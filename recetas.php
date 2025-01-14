 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "375";
require_once("includes/rsusuario.php");


$consulta = "
select productos.descripcion, productos.barcode, productos.idprod_serial,
(select idinsumo from insumos_lista where insumos_lista.idproducto = productos.idprod_serial) as idinsumo,
(select tipoproducto from productos_tipo where idtipoproducto = productos.idtipoproducto) as tipoproducto,
 categorias.nombre as categoria, sub_categorias.descripcion as subcategoria
from productos 
    inner join categorias on productos.idcategoria = categorias.id_categoria 
    inner join sub_categorias on productos.idsubcate = sub_categorias.idsubcate 
WHERE
borrado = 'N'
and (
SELECT recetas.idreceta
FROM recetas
inner join recetas_detalles on recetas_detalles.idreceta = recetas.idreceta
inner join ingredientes on ingredientes.idingrediente = recetas_detalles.ingrediente
inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
where 
recetas.idproducto = productos.idprod_serial
and COALESCE(insumos_lista.idproducto,0) <>  recetas.idproducto
limit 1
) > 0
order by productos.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



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
                    <h2>Listado de Recetas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<?php
$refemenu = 'vent';
require_once("includes/menu_recetas.php"); ?>
<hr />
<p>
<a href="recetas_pdf.php"  target="_blank" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Reporte para Imprimir</a>
<a href="recetas_csv.php" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar CSV</a>
</p>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Codigo</th>
            <th align="center">Codigo Barras</th>
            <th align="center">Producto</th>
            <th align="center">Tipo</th>
            <th align="center">Categoria</th>
            <th align="center">Subcategoria</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="recetas_det.php?id=<?php echo $rs->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>

                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['idinsumo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['barcode']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipoproducto']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['categoria']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['subcategoria']); ?></td>

    
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />

<br /><br /><br />

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
