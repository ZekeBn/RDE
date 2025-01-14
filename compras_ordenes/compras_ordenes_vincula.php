<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$ocnum = intval($_GET['id']);

$consulta = "
select *,
(select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where compras_ordenes.generado_por = usuarios.idusu) as generado_por,
(select usuario from usuarios where compras_ordenes.borrado_por = usuarios.idusu) as borrado_por,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 estado = 2 
 and ocnum = $ocnum
 and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6)
order by ocnum desc
limit 1
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
                    <h2>Orden de Compra Finalizada #<?php echo $ocnum ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

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

<hr />
Vincular con la Compra:



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
