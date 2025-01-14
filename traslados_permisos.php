 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "313";
require_once("includes/rsusuario.php");

// si el usuario no es de una master franquicia
$consulta = "
select * from usuarios where idusu = $idusu 
";
$rsusfranq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$franq_m = $rsusfranq->fields['franq_m'];
// si el usuario actual no es master franq ni super filtra
if ($franq_m != 'S' && $superus != 'S') {
    $whereadd = "
    and usuarios.franq_m = 'N'
    ";
}

// si no es un super usuario debe filtrar usuarios por este campo
if ($superus != 'S') {
    $whereadd .= "
    and usuarios.super = 'N'
    ";
}

$consulta = "
select usuarios.idusu, usuarios.usuario as usuario,
(
select count(*) as total 
from traslados_permisos 
where 
traslados_permisos.idusuario = usuarios.idusu 
and traslados_permisos.estado = 1 
AND (entrante = 'S' or saliente = 'S')
) as total
from usuarios 
where 
 usuarios.estado = 1 
 $whereadd
 group by usuarios.usuario
order by usuarios.usuario asc
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
                    <h2>Permisos para traslados de stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="traslados_permisos_pref.php" class="btn btn-sm btn-default"><span class="fa fa-cogs"></span> Preferencias</a>
</p>
<hr />

<strong>Vista por Usuario:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idusuario</th>
            <th align="center">Usuario</th>
            <th align="center">Total Depositos</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="traslados_permisos_usu_det.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idusu']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['usuario']); ?></td>
            <td align="center"><?php echo intval($rs->fields['total']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<strong>Vista por Deposito:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Iddeposito</th>
            <th align="center">Deposito</th>
            <th align="center">Total Usuarios</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="traslados_permisos_depo_det.php?id=<?php echo $rs->fields['idtraspermi']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="traslados_permisos_edit.php?id=<?php echo $rs->fields['idtraspermi']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="traslados_permisos_del.php?id=<?php echo $rs->fields['idtraspermi']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['iddeposito']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['deposito']); ?></td>
            <td align="center"><?php echo intval($rs->fields['total']); ?></td>
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
