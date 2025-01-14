 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "134";
require_once("includes/rsusuario.php");

$consulta = "
select tipo_conteo from preferencias_inventario limit 1
";
$rsprefinv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_conteo = $rsprefinv->fields['tipo_conteo'];
$add_url = "";
if ($tipo_conteo == 'C') {
    $add_url = "_codbar";
}


$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(
    SELECT GROUP_CONCAT(grupo_insumos.nombre) AS grupos 
    from conteo_grupos 
    inner join grupo_insumos on grupo_insumos.idgrupoinsu = conteo_grupos.idgrupoinsu
    where 
    conteo_grupos.idconteo = conteo.idconteo
) as grupos
from conteo
where
estado <> 6
order by idconteo desc
limit 100
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
                    <h2>Conteo de Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  
<p><a href="conteo_stock_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
    <tr>
      <th>Accion</th>
      <th># Conteo</th>
      <th>Deposito</th>
      <th>Grupos</th>
      <th>Iniciado</th>
      <th>Modificado</th>
      <th>Finalizado</th>
      <th>Afecta Stock</th>
      <th>Estado</th>

      </tr>
      </thead>
      <tbody>
<?php
$i = 1;
while (!$rs->EOF) { ?>
    <tr>
      <td>
<?php
$idconteo = $rs->fields['idconteo'];
    $mostrarbtn = "N";
    if ($rs->fields['estado'] == 1) {
        $mostrarbtn = "S";
        $link = "conteo_stock_contar$add_url.php?id=".$idconteo;
        $txtbtn = "Abrir";
        $iconbtn = "plus";
        $tipoboton = "success";
    }
    if ($rs->fields['estado'] == 2) {
        $mostrarbtn = "S";
        $link = "conteo_stock_contar$add_url.php?id=".$idconteo;
        $txtbtn = "Retomar";
        $iconbtn = "recycle";
        $tipoboton = "success";
    }
    if ($rs->fields['estado'] == 3) {
        $mostrarbtn = "S";
        $link = "conteo_stock_reporte$add_url.php?id=".$idconteo;
        $txtbtn = "Reporte";
        $iconbtn = "search";
        $tipoboton = "default";
    }
    if ($mostrarbtn == 'S') {
        ?>
                <div class="btn-group">
                    <a href="<?php echo $link; ?>" class="btn btn-sm btn-<?php echo $tipoboton; ?>" title="<?php echo $txtbtn; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn; ?>"><span class="fa fa-<?php echo $iconbtn; ?>"></span> <?php echo $txtbtn; ?></a>
                </div>
<?php } ?></td>
    
      <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo $rs->fields['grupos']; ?></td>
      <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['inicio_registrado_el'])); ?></td>
      <td align="center"><?php if ($rs->fields['ult_modif'] != '') {
          echo date("d/m/Y", strtotime($rs->fields['ult_modif']));
      } ?></td>
      <td align="center"><?php if ($rs->fields['final_registrado_el'] != '') {
          echo date("d/m/Y H:i:s", strtotime($rs->fields['final_registrado_el']));
      } ?></td>
      <td align="center"><?php if ($rs->fields['afecta_stock'] == 'S') {
          echo "SI";
      } else {
          echo "NO";
      } ?></td>
      <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
  </tr>
<?php $i++;
    $rs->MoveNext();
} ?>
  </tbody>
</table>
</div>
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
