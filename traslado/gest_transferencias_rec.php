<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "223";
require_once("includes/rsusuario.php");

$consulta = "
select filtra_recepcion_porsucursal
from preferencias_transfer 
limit 1
";
$rspreftrans = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$filtra_recepcion_porsucursal = $rspreftrans->fields['filtra_recepcion_porsucursal'];

if ($filtra_recepcion_porsucursal == 'S') {
    $whereadd .= "
	and gest_transferencias.destino in (
		select iddeposito
		from traslados_permisos_rec
		where
		idusuario = $idusu
		and entrante = 'S'
	)
	";
}

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.origen) as deposito_origen,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.destino) as deposito_destino,
(select usuario from usuarios where gest_transferencias.generado_por = usuarios.idusu) as generado_por
from gest_transferencias 
where 
 estado = 3 
 $whereadd
  
order by idtanda asc
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Traslados entrantes sin confirmar</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if ($filtra_recepcion_porsucursal == 'S') { ?>
* Se muestran todos los translados pendientes de recepcion donde el usuario actual tiene permitido el traslado entrante para el deposito de destino.<br />
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtanda</th>
			<th align="center">Origen</th>
			<th align="center">Destino</th>
			<th align="center">Fecha transferencia</th>
			<th align="center">Fecha Registrado</th>
			<th align="center">Generado por</th>
			</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_transferencias_rec_det.php?id=<?php echo $rs->fields['idtanda']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idtanda']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_origen']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_destino']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_transferencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_transferencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fecha_real'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_real']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
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
            
<?php
$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.origen) as deposito_origen,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.destino) as deposito_destino,
(select usuario from usuarios where gest_transferencias.generado_por = usuarios.idusu) as generado_por
from gest_transferencias 
where 
 estado = 2 
  $whereadd
and idtanda in (select idtanda from (select idtanda from gest_depositos_mov where cantidad <> cantidad_recibe group by idtanda order by idtanda desc limit 10) dif)
order by idtanda desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Traslados entrantes recibidos con diferencias (Ultimos 10)</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtanda</th>
			<th align="center">Origen</th>
			<th align="center">Destino</th>
			<th align="center">Fecha transferencia</th>
			<th align="center">Fecha Registrado</th>
			<th align="center">Generado por</th>
			</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_transferencias_rec_det_dif.php?id=<?php echo $rs->fields['idtanda']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idtanda']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_origen']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_destino']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_transferencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_transferencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fecha_real'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_real']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
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
            
         
<?php
$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.origen) as deposito_origen,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.destino) as deposito_destino,
(select usuario from usuarios where gest_transferencias.generado_por = usuarios.idusu) as generado_por
from gest_transferencias 
where 
 estado = 2 
  $whereadd
and idtanda not in (select idtanda from (select idtanda from gest_depositos_mov where cantidad <> cantidad_recibe group by idtanda order by idtanda desc) dif)
order by idtanda desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Traslados entrantes recibidos sin diferencias (Ultimos 10)</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtanda</th>
			<th align="center">Origen</th>
			<th align="center">Destino</th>
			<th align="center">Fecha transferencia</th>
			<th align="center">Fecha Registrado</th>
			<th align="center">Generado por</th>
			</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_transferencias_rec_det_dif.php?id=<?php echo $rs->fields['idtanda']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idtanda']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_origen']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_destino']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_transferencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_transferencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fecha_real'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_real']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
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
