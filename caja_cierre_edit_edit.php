 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "269";
require_once("includes/rsusuario.php");


$idcaja = intval($_GET['id']);
//$idcaja=24;

// si el usuario no es soporte
$consulta = "
select soporte, super from usuarios where idusu = $idusu 
";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$soporte = $rsus->fields['soporte'];
$super = $rsus->fields['super'];
if ($soporte != 1) {
    $whereadd2 = "
    and caja_super.cajero not in (select idusu  from usuarios where (soporte = 1  or super = 'S'))
    ";
}

$consulta = "
select * from caja_super where estado_caja <> 6 and idcaja = $idcaja  and rendido = 'N' $whereadd2
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = intval($rs->fields['idcaja']);
$estado_caja = intval($rs->fields['estado_caja']);
if ($idcaja == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}
// loguear todos los cambios




/*

caja_vouchers

caja_billetes

caja_moneda_extra*/

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
                    <h2>Editando Arqueo de Caja #<?php echo $idcaja; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<a href="caja_cierre_edit.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<hr />
<strong>Apertura de Caja:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Monto Apertura</th>
        </tr>
      </thead>
      <tbody>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="caja_aper_edit.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                </div>

            </td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura']);  ?></td>
        </tr>
      </tbody>
    </table>
</div>
<br />
<?php if ($estado_caja == 3) { ?>
<?php

    $consulta = "
select caja_arqueo_fpagos.idserie, formas_pago.descripcion as formapago, monto as total
from caja_arqueo_fpagos 
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where 
 caja_arqueo_fpagos.estado = 1 
 and caja_arqueo_fpagos.idcaja = $idcaja
 and caja_arqueo_fpagos.idformapago > 1
order by formas_pago.descripcion asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?><hr />
<strong>Arqueo Otras Formas de Pago:</strong>
<br />
<a href="caja_arqueo_fpagos_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Forma de Pago</th>
            <th align="center">Monto</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="caja_arqueo_fpagos_edit.php?id=<?php echo $rs->fields['idserie']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="caja_arqueo_fpagos_del.php?id=<?php echo $rs->fields['idserie']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><idserie class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="left"><?php echo antixss($rs->fields['formapago']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['total']);  ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<?php
/*
$consulta="
select *
from caja_vouchers
where
 estado = 1
 and idcaja = $idcaja
order by unicasspk asc
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

?>
<strong>Arqueo de Vouchers:</strong>
<br />
<a href="caja_vouchers_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Total vouchers</th>
        </tr>
      </thead>
      <tbody>
<?php while(!$rs->EOF){ ?>
        <tr>
            <td>

                <div class="btn-group">
                    <a href="caja_vouchers_edit.php?id=<?php echo $rs->fields['unicasspk']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="caja_vouchers_del.php?id=<?php echo $rs->fields['unicasspk']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="right"><?php echo formatomoneda($rs->fields['total_vouchers']);  ?></td>
        </tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
      </tbody>
    </table>
</div>
<br />
<?php */ ?>
<?php
    $consulta = "
select *, gest_billetes.valor as billete
from caja_billetes 
inner join gest_billetes on gest_billetes.idbillete = caja_billetes.idbillete
where 
 caja_billetes.estado = 1 
 and idcaja = $idcaja
order by gest_billetes.valor asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<hr />
<strong>Arqueo de Billetes Moneda Local:</strong>
<br />
<a href="caja_billetes_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Billete</th>
            <th align="center">Cantidad</th>
            <th align="center">Subtotal</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="caja_billetes_edit.php?id=<?php echo $rs->fields['registrobill']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="caja_billetes_del.php?id=<?php echo $rs->fields['registrobill']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>

            <td align="center"><?php echo formatomoneda($rs->fields['billete']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['subtotal']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<hr />
<?php
$consulta = "
select caja_moneda_extra.*, tipo_moneda.descripcion as moneda
from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo = caja_moneda_extra.moneda
where 
 caja_moneda_extra.estado = 1 
 and idcaja = $idcaja
order by caja_moneda_extra.sermone asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<strong>Arqueo de Billetes Moneda Extrangera:</strong>
<br />
<a href="caja_moneda_extra_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Moneda</th>
            <th align="center">Cantidad</th>
            <th align="center">Cotizacion</th>
            <th align="center">Subtotal</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="caja_moneda_extra_edit.php?id=<?php echo $rs->fields['sermone']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="caja_moneda_extra_del.php?id=<?php echo $rs->fields['sermone']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>

            <td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cotiza']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['subtotal']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<hr />
<strong>Pagos por Caja:</strong>
<br />
<a href="pagos_extra_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<?php
$buscar = "
Select estado,unis ,fecha,concepto,monto_abonado,
(select nombre from proveedores where idempresa=$idempresa and idproveedor=pagos_extra.idprov)as provee,
factura,anulado_el,
(select usuario from usuarios where idusu=pagos_extra.anulado_por) as quien
from pagos_extra 
where 
idcaja=$idcaja  
and estado = 1
order by fecha asc";
    $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>    
            <th align="center" ></th>
            <th align="center" ><strong>Fecha/hora</strong></th>
            <th  ><strong>Concepto</strong></th>
            <th  ><strong>Proveedor</strong></th>
            <th  align="center" ><strong>Monto abonado</strong></th>
            <th  ><strong>Factura Num</strong></th>
        
            
        </tr>
    </thead>
    <tbody>
<?php while (!$rst->EOF) {?>
    <tr>
        <td>
            
            <div class="btn-group">
                <a href="pagos_extra_edit.php?id=<?php echo $rst->fields['unis']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                <a href="pagos_extra_del.php?id=<?php echo $rst->fields['unis']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
            </div>

        </td>
        <td><?php echo date("d/m/Y H:i:s", strtotime($rst->fields['fecha'])); ?></td>
        <td><?php echo $rst->fields['concepto']?></td>
        <td align="center"><?php echo $rst->fields['provee']?></td>
        <td align="right"><?php echo formatomoneda($rst->fields['monto_abonado'])?></td>
        <td align="center"><?php echo $rst->fields['factura']?></td>
    </tr>
                            
<?php $rst->MoveNext();
}?>
</tbody>
</table>
</div>

<hr />
<strong>Retiro de Valores:</strong>
<br />
<a href="caja_retiros_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<?php
$buscar = "
select *,
(select usuario from usuarios where caja_retiros.retirado_por = usuarios.idusu) as retirado_por
from caja_retiros 
where 
 estado = 1 
 and idcaja = $idcaja
order by fecha_retiro asc
";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>    
            <th align="center" ></th>
            <th  ><strong>Monto Entregado</strong></th>
            <th align="center" ><strong>Fecha/hora</strong></th>
            
            <th  ><strong>Retirado por</strong></th>
            <th  align="center" ><strong>Obs</strong></th>

        
            
        </tr>
    </thead>
    <tbody>
<?php while (!$rs->EOF) {?>
    <tr>
        <td>
            
            <div class="btn-group">
                <a href="caja_retiros_edit.php?id=<?php echo $rs->fields['regserialretira']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                <a href="caja_retiros_del.php?id=<?php echo $rs->fields['regserialretira']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
            </div>

        </td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_retirado']);  ?></td>
            <td align="center"><?php if ($rs->fields['fecha_retiro'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_retiro']));
            }  ?></td>

            <td align="center"><?php echo antixss($rs->fields['retirado_por']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['obs']); ?></td>
    </tr>
                            
<?php $rs->MoveNext();
}?>
</tbody>
</table>
</div>

<hr />
<strong>Recepcion de Valores:</strong>
<br />
<a href="caja_reposiciones_add.php?id=<?php echo $idcaja ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<?php
$buscar = "
select *,
(select usuario from usuarios where caja_reposiciones.entregado_por = usuarios.idusu) as entregado_por
from caja_reposiciones 
where 
 estado = 1 
 and idcaja = $idcaja
order by fecha_reposicion asc
";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>    
            <th align="center" ></th>
            <th  ><strong>Monto Recibido</strong></th>
            <th align="center" ><strong>Fecha/hora</strong></th>
            
            <th  ><strong>Entregado por</strong></th>
            <th  align="center" ><strong>Obs</strong></th>

        
            
        </tr>
    </thead>
    <tbody>
<?php while (!$rs->EOF) {?>
    <tr>
        <td>
            
            <div class="btn-group">
                <a href="caja_reposiciones_edit.php?id=<?php echo $rs->fields['regserialentrega']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                <a href="caja_reposiciones_del.php?id=<?php echo $rs->fields['regserialentrega']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
            </div>

        </td>

            <td align="right"><?php echo formatomoneda($rs->fields['monto_recibido']);  ?></td>
            <td align="center"><?php if ($rs->fields['fecha_reposicion'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_reposicion']));
            }  ?></td>

            <td align="center"><?php echo antixss($rs->fields['entregado_por']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['obs']); ?></td>
    </tr>
                            
<?php $rs->MoveNext();
}?>
</tbody>
</table>
</div>
<?php /*?>

<?php
$consulta="
select *, moneda.moneda as moneda
from caja_moneda_extra
inner join moneda on moneda.idmoneda = caja_moneda_extra.moneda
where
 caja_moneda_extra.estado = 1
 and idcaja = $idcaja
order by moneda.moneda asc, caja_moneda_extra.subtotal asc
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
?>
<hr />
<strong>Arqueo de Billetes Moneda Extrangera:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Cantidad</th>
            <th align="center">Subtotal</th>
            <th align="center">Moneda</th>
            <th align="center">Cotiza</th>
        </tr>
      </thead>
      <tbody>
<?php while(!$rs->EOF){ ?>
        <tr>
            <td>

                <div class="btn-group">
                    <a href="caja_moneda_extra_edit.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="caja_moneda_extra_del.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>

            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['subtotal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['cotiza']);  ?></td>
        </tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
      </tbody>
    </table>
</div>
<br />
<?php */?>
<?php } ?>

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
