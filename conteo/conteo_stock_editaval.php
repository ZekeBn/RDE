<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "403";
$dirsup = "S";

require_once("../includes/rsusuario.php");


$consulta = "
select *
from conteo 
where 
 estado = 1 
order by fecha_inicio desc
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
                    <h2>Editar Valorizacion de Conteo de Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<h1>Estamos mejorando para vos!</h1>
<h2>Muy pronto este y muchos otros modulos estaran disponibles, con muchas funciones nuevas!</h2>
<br /><br /><br />
<?php /*?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Fecha inicio</th>
            <th align="center">Ult modif</th>
            <th align="center">Iniciado por</th>
            <th align="center">Finalizado por</th>
            <th align="center">Estado</th>
            <th align="center">Afecta stock</th>
            <th align="center">Fecha final</th>
            <th align="center">Observaciones</th>
            <th align="center">Idconteo</th>
            <th align="center">Idsucursal</th>
            <th align="center">Idempresa</th>
            <th align="center">Iddeposito</th>
            <th align="center">Inicio registrado el</th>
            <th align="center">Final registrado el</th>
            <th align="center">Sumoventa</th>
        </tr>
      </thead>
      <tbody>
<?php while(!$rs->EOF){ ?>
        <tr>
            <td>

                <div class="btn-group">
                    <a href="conteo_det.php?id=<?php echo $rs->fields['fecha_inicio']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>

                </div>

            </td>
            <td align="center"><?php if($rs->fields['fecha_inicio'] != ""){ echo date("d/m/Y",strtotime($rs->fields['fecha_inicio'])); } ?></td>
            <td align="center"><?php if($rs->fields['ult_modif'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields['ult_modif'])); }  ?></td>
            <td align="center"><?php echo intval($rs->fields['iniciado_por']); ?></td>
            <td align="center"><?php echo intval($rs->fields['finalizado_por']); ?></td>
            <td align="center"><?php echo intval($rs->fields['estado']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['afecta_stock']); ?></td>
            <td align="center"><?php if($rs->fields['fecha_final'] != ""){ echo date("d/m/Y",strtotime($rs->fields['fecha_final'])); } ?></td>
            <td align="center"><?php echo antixss($rs->fields['observaciones']); ?></td>
            <td align="center"><?php echo intval($rs->fields['idconteo']); ?></td>
            <td align="center"><?php echo intval($rs->fields['idsucursal']); ?></td>
            <td align="center"><?php echo intval($rs->fields['idempresa']); ?></td>
            <td align="center"><?php echo intval($rs->fields['iddeposito']); ?></td>
            <td align="center"><?php if($rs->fields['inicio_registrado_el'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields['inicio_registrado_el'])); }  ?></td>
            <td align="center"><?php if($rs->fields['final_registrado_el'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields['final_registrado_el'])); }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['sumoventa']); ?></td>
        </tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
      </tbody>
    </table>
</div>
<br />

<?php */
?>
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
