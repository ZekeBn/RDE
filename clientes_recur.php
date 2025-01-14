<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "298";
require_once("includes/rsusuario.php");

// actualizador
require_once("sys_actualizador.php");


// clientes sin contrato activo
$consulta = "
select * 
from cliente
where
idempresa = $idempresa
and estado = 1
and recurrente = 1
and idcliente not in (select idcliente from contrato where estado = 'A' and idempresa = $idempresa)
order by idcliente desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


// clientes con contrato activo
$consulta = "
select * , 
(select fechahora from licencia_admin where licencia_admin.idcliente = cliente.idcliente order by fechahora desc limit 1) as ultcompruebalic,
(select max(atraso) from operacion where idcliente = cliente.idcliente and saldo > 0) as atraso
from cliente
inner join contrato on contrato.idcliente = cliente.idcliente
where
cliente.idempresa = $idempresa
and contrato.idempresa = $idempresa
and cliente.estado = 1
and contrato.estado = 'A'
and contrato.idcontrato in (select idcontrato from obligaciones where estado = 'A' and idempresa = $idempresa)
order by (select fechahora from licencia_admin where licencia_admin.idcliente = cliente.idcliente order by fechahora desc limit 1) desc
";
$rsact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// contratos sin obligaciones
$consulta = "
select * 
from contrato
inner join cliente on cliente.idcliente = contrato.idcliente
where
contrato.idempresa = $idempresa
and contrato.estado = 'A'
and contrato.idcontrato not in (select idcontrato from obligaciones where estado = 'A' and idempresa = $idempresa)
order by contrato.fecha_inicio desc
";
$rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// contratos vencidos
$consulta = "
select * 
from contrato
inner join cliente on cliente.idcliente = contrato.idcliente
where
contrato.idempresa = $idempresa
and contrato.estado = 'A'
and contrato.renov_auto = 'N'
and fecha_final <= '$ahora'
order by fecha_inicio desc
";
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// contratos por vencer
$fecha = date('Y-m-j', strtotime($ahora));
$nuevafecha = strtotime('+60 day', strtotime($fecha));
$fecfutura = date('Y-m-d', $nuevafecha);
$consulta = "
select * 
from contrato
inner join cliente on cliente.idcliente = contrato.idcliente
where
contrato.idempresa = $idempresa
and contrato.estado = 'A'
and contrato.renov_auto = 'N'
and fecha_final <= '$fecfutura'
order by fecha_inicio desc
";
$rspv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// contratos inactivos
$consulta = "
select *, (select motivo from motivo_rescision where idmotivo = contrato.idmotivo and idempresa = $idempresa) as motivo
from contrato
inner join cliente on cliente.idcliente = contrato.idcliente
where
contrato.idempresa = $idempresa
and contrato.estado = 'I'
order by fecha_baja desc, fecha_final desc, fecha_inicio desc
limit 20
";
$rsi = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
            
<div class="row">
            
            <!-- SECCION -->
              <div class="col-md-12 col-sm-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Clientes Recurrentes sin contrato activo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<p><a href="clientes_recur_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<strong>Clientes sin Contrato Activo:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idcliente</th>
			<th align="center">Nombre Fantasia</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Contacto</th>
			<th align="center">Cargo</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="contrato_add.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Agregar Contrato" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar Contrato"><span class="fa fa-plus"></span></a>
					<a href="clientes_recur_edit.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="clientes_recur_del.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['contacto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cargo_contacto']); ?></td>


		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

			  <p align="center">&nbsp;</p>

              <p align="center"></p>
            
			  <p align="center">&nbsp;</p>

              <p>&nbsp;</p>


                  </div>
                </div>
              </div>

            <!-- SECCION --> 
            
            <!-- SECCION -->
              <div class="col-md-6 col-sm-6">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Contratos sin Obligaciones Activas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

			  <p align="center">(Generar Obligacion)</p>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
			    <tbody>
			      <tr>
			        <th></th>
			        <th>Codclie</th>
			        <th>Nombre de Fantasia</th>
			        <th>Razon Social</th>
			        <th>RUC</th>
			        <th>Contacto</th>
			        <th>Cargo</th>

		          </tr>
			      <?php while (!$rsc->EOF) {?>
			      <tr>
					<th>
                    <a href="sys_obligacion_agrega.php?id=<?php echo $rsc->fields['idcontrato'];?>" class="btn btn-sm btn-default" title="Agregar Obligacion" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar Obligacion"><span class="fa fa-plus"></span></a>
                    </th>
			        <td align="center"><?php echo $rsc->fields['idcliente'];?></td>
			        <td align="center"><?php echo $rsc->fields['fantasia'];?></td>
			        <td align="center"><?php echo $rsc->fields['razon_social'];?></td>
			        <td align="center"><?php echo $rsc->fields['ruc'];?></td>
			        <td align="center"><?php echo $rsc->fields['contacto'];?></td>
			        <td align="center"><?php echo $rsc->fields['cargo_contacto'];?></td>
		          </tr>
			      <?php $rsc->MoveNext();
			      } ?>
		        </tbody>
		      </table>
</div>


                  </div>
                </div>
              </div>
            <!-- SECCION --> 
            
            <!-- SECCION -->
              <div class="col-md-6 col-sm-6">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Contratos Activos Vencidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

			  <p align="center">(Hacer Firmar Renovacion)			  </p>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
			    <tbody>
			      <tr>
                  	<th></th>
			        <th>Vencimiento</th>
			        <th>Codclie</th>
			        <th>Nombre de Fantasia</th>
			        <th>Razon Social</th>
			        <th>RUC</th>
			        <th>Contacto</th>
			        <th>Cargo</th>
			        
		          </tr>
			      <?php while (!$rsv->EOF) {?>
			      <tr>
                  	<th>[Renovar]</th>
			        <td align="center"><?php echo $rsv->fields['fecha_final'];?></td>
			        <td align="center"><?php echo $rsv->fields['idcliente'];?></td>
			        <td align="center"><?php echo $rsv->fields['fantasia'];?></td>
			        <td align="center"><?php echo $rsv->fields['razon_social'];?></td>
			        <td align="center"><?php echo $rsv->fields['ruc'];?></td>
			        <td align="center"><?php echo $rsv->fields['contacto'];?></td>
			        <td align="center"><?php echo $rsv->fields['cargo_contacto'];?></td>
			        
		          </tr>
			      <?php $rsv->MoveNext();
			      } ?>
		        </tbody>
		      </table>
</div>


                  </div>
                </div>
              </div>
            <!-- SECCION --> 
            
            
            <!-- SECCION -->
              <div class="col-md-6 col-sm-6">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Contratos Activos por vencer</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

              <p align="center">(Vencen en los proximos 60 dias, contactar para renovar) </p>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
                <tbody>
                  <tr>
                  	<th></th>
                    <th>Vencimiento</th>
                    <th>Codclie</th>
                    <th>Nombre de Fantasia</th>
                    <th>Razon Social</th>
                    <th>RUC</th>
                    <th>Contacto</th>
                    <th>Cargo</th>
                    
                  </tr>
                  <?php while (!$rspv->EOF) {?>
                  <tr>
                  	<th>[Renovar]</th>
                    <td align="center"><?php echo $rspv->fields['fecha_final'];?></td>
                    <td align="center"><?php echo $rspv->fields['idcliente'];?></td>
                    <td align="center"><?php echo $rspv->fields['fantasia'];?></td>
                    <td align="center"><?php echo $rspv->fields['razon_social'];?></td>
                    <td align="center"><?php echo $rspv->fields['ruc'];?></td>
                    <td align="center"><?php echo $rspv->fields['contacto'];?></td>
                    <td align="center"><?php echo $rspv->fields['cargo_contacto'];?></td>
                    
                  </tr>
                  <?php $rspv->MoveNext();
                  } ?>
                </tbody>
              </table>
</div>


                  </div>
                </div>
              </div>
            <!-- SECCION --> 
            
            
            <!-- SECCION -->
              <div class="col-md-6 col-sm-6">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Contratos Inactivos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


			  <p align="center">(Ultimos 20, verificar motivo de baja)</p>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
                <tbody>
                  <tr>
                    <th>Fecha Baja</th>
                    <th>Vencimiento</th>
                    <th>Motivo Baja</th>
                    <th>Codclie</th>
                    <th>Nombre de Fantasia</th>
                    <th>Razon Social</th>
                    <th>RUC</th>
                    <th>Contacto</th>
                    <th>Cargo</th>
                  </tr>
                  <?php while (!$rspv->EOF) {?>
                  <tr>
                    <td align="center"><?php echo $rspv->fields['fecha_baja'];?></td>
                    <td align="center"><?php echo $rspv->fields['fecha_final'];?></td>
                    <td align="center"><?php echo $rspv->fields['motivo'];?></td>
                    <td align="center"><?php echo $rspv->fields['idcliente'];?></td>
                    <td align="center"><?php echo $rspv->fields['fantasia'];?></td>
                    <td align="center"><?php echo $rspv->fields['razon_social'];?></td>
                    <td align="center"><?php echo $rspv->fields['ruc'];?></td>
                    <td align="center"><?php echo $rspv->fields['contacto'];?></td>
                    <td align="center"><?php echo $rspv->fields['cargo_contacto'];?></td>
                  </tr>
                  <?php $rspv->MoveNext();
                  } ?>
                </tbody>
              </table>
 </div>


                  </div>
                </div>
              </div>
            <!-- SECCION --> 
            
            
            
            <!-- SECCION -->
              <div class="col-md-12 col-sm-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Contratos Activos</h2>
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
                  	<th></th>
			        <th>Codclie</th>
			        <th>Nombre de Fantasia</th>
			        <th>Razon Social</th>
			        <th>RUC</th>
			        <th>Contacto</th>
			        <th>Cargo</th>
			        <th>Firmado</th>
			        <th>Atraso</th>
			        <th>Ult Comprueba Lic</th>
                  </tr>
                  </thead>
                  <tbody>
<?php while (!$rsact->EOF) {?>
			      <tr>
			        <th>
				<div class="btn-group">

					<a href="sys_obligacion_agrega.php?id=<?php echo $rsact->fields['idcontrato'];?>" class="btn btn-sm btn-default" title="Agregar Obligacion" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar Obligacion"><span class="fa fa-plus"></span></a>
					<a href="sys_contrato_baja.php?id=<?php echo $rsact->fields['idcontrato'];?>" class="btn btn-sm btn-default" title="Dar de baja" data-toggle="tooltip" data-placement="right"  data-original-title="Dar de baja"><span class="fa fa-trash-o"></span></a>
				</div>
                </th>

			        <td align="center"><?php echo $rsact->fields['idcliente'];?></td>
			        <td align="center"><?php echo $rsact->fields['fantasia'];?></td>
			        <td align="center"><?php echo $rsact->fields['razon_social'];?></td>
			        <td align="center"><?php echo $rsact->fields['ruc'];?></td>
			        <td align="center"><?php echo $rsact->fields['contacto'];?></td>
			        <td align="center"><?php echo $rsact->fields['cargo_contacto'];?></td>
			        <td align="center" <?php if ($rsact->fields['firmado'] == 'N') { ?>style="color:#FF0000;"<?php } ?>><?php echo $rsact->fields['firmado'];?></td>
			        <td align="center" <?php if ($rsact->fields['atraso'] > 0) { ?>style="color:#FF0000;"<?php } ?>><?php echo $rsact->fields['atraso'];?></td>
			        <td align="center"><?php if ($rsact->fields['ultcompruebalic'] != '') {
			            echo date("d/m/Y H:i:s", strtotime($rsact->fields['ultcompruebalic']));
			        } ?></td>

                  </tr>
<?php $rsact->MoveNext();
} ?>
		        </tbody>
		      </table>
</div>

                  </div>
                </div>
              </div>
            <!-- SECCION --> 
            
            
</div>
            
            
            
            
            
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
