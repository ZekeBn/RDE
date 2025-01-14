 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
require_once("includes/rsusuario.php");


// busca si hay deposito de transito creado
$consulta = "
select iddeposito from gest_depositos where tiposala = 3 and estado = 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si no hay crea
if (intval($rstran->fields['iddeposito']) == 0) {
    $consulta = "
    select max(iddeposito) as max from gest_depositos
    ";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito_new = intval($rsmax->fields['max']) + 1;

    $consulta = "
    INSERT INTO gest_depositos 
    (
    iddeposito, direccion, idencargado, estado, descripcion, tiposala,
     color, idempresa, idsucursal, borrable, compras, orden_nro
     ) 
    VALUES
    (
    $iddeposito_new, NULL, 1, 1, 'TRANSITO', 3,
     '#FFFFFF', 1, 1, 'N', 0, 1
     );
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}


$consulta = "
select *,
(select usuario from usuarios where gest_depositos.idencargado = usuarios.idusu) as encargado,
(select sucursales.nombre from sucursales where sucursales.idsucu=gest_depositos.idsucursal limit 1) as sucursal,
(select tipo_sala from gest_depositos_tiposala where gest_depositos_tiposala.idtiposala = gest_depositos.tiposala) as tipo_sala
from gest_depositos 
where 
 estado = 1 
order by orden_nro asc, descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//$tdpto=$rsdpto->RecordCount();




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
                    <h2>Administrar Depositos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="gest_depositos_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="gest_deposito_global.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Depositos Globales Detallado</a>
<a href="gest_deposito_global_resumido.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Depositos Globales Resumido</a>
<a href="gest_adm_depositos_borrados.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Depositos Borrados</a>
<a href="gest_deposito_admin_mov_inventario_all.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Inventario Consolidado</a>
<a href="gest_deposito_admin_global_xls.php" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Stock Consolidado</a>
<a href="gest_deposito_admin_global_det_csv.php" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Stock Global Detallado</a>

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Orden nro</th>
            <th align="center">Deposito</th>
            <th align="center">Tipo Deposito</th>
            <th align="center">Sucursal</th>
            <th align="center">Encargado</th>
            <th align="center">Direccion</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="gest_deposito_admin.php?idpo=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <?php if ($rs->fields['tiposala'] != 3) { ?>
                    <a href="gest_depositos_edit.php?id=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <?php if ($rs->fields['borrable'] == 'S') { ?>
                    <a href="gest_depositos_del.php?id=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php } ?>
                    <?php } ?>
                </div>
            </td>
            <td align="center"><?php echo intval($rs->fields['orden_nro']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipo_sala']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['encargado']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
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
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
