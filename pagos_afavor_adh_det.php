 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "430";
require_once("includes/rsusuario.php");

$idpago_afavor = intval($_GET['id']);
if ($idpago_afavor == 0) {
    header("location: pagos_afavor_adh.php");
    exit;
}

$usa_adherente = $rsco->fields['usa_adherente'];

$consulta = "
select *,
(select usuario from usuarios where pagos_afavor_adh.idusuario = usuarios.idusu) as registrado_por,
(select idcuentaclientepagcab from cuentas_clientes_pagos_cab where pagos_afavor_adh.idpago_afavor = cuentas_clientes_pagos_cab.idpago_afavor) as idcuentaclientepagcab,
(select recibo from cuentas_clientes_pagos_cab where pagos_afavor_adh.idpago_afavor = cuentas_clientes_pagos_cab.idpago_afavor) as recibo,
(select idadherente from adherentes where idadherente = pagos_afavor_adh.idadherente) as idadherente,
(select nomape from adherentes where idadherente = pagos_afavor_adh.idadherente) as adherente,
(select nombre_servicio from servicio_comida where idserviciocom = pagos_afavor_adh.idserviciocom) as servicio_comida
from pagos_afavor_adh 
inner join cliente on cliente.idcliente = pagos_afavor_adh.idcliente
where 
idpago_afavor = $idpago_afavor
and pagos_afavor_adh.estado = 1
order by idpago_afavor desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpago_afavor = intval($rs->fields['idpago_afavor']);
$idpago = intval($rs->fields['idpago']);
if ($idpago_afavor == 0) {
    header("location: pagos_afavor_adh.php");
    exit;
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
                    <h2>Anticipos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p><a href="pagos_afavor_adh.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<div class="btn-group">
    <a href="cobro_cuentas_imp.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>&anticipo_redir=s" class="btn btn-sm btn-default" title="Imprimir Ticket" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Ticket"><span class="fa fa-print"></span></a>
    <a href="cobro_cuentas_imp_pre.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>&anticipo_redir=s" class="btn btn-sm btn-default" title="Imprimir Pre Impreso" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Pre Impreso"><span class="fa fa-print"></span></a>
    <a href="cobro_cuentas_imp_pdf.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>&anticipo_redir=s" target="_blank" class="btn btn-sm btn-default" title="Descargar Ticket PDF" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar Ticket PDF"><span class="fa fa-file-pdf-o"></span></a>
    <a href="cobro_cuentas_imp_recibo_pdf.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>&anticipo_redir=s" target="_blank" class="btn btn-sm btn-default" title="Descargar A4 PDF" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar A4 PDF"><span class="fa fa-file"></span></a>
</div>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Cliente</th>
            <th align="center">Recibo</th>
            <th align="center">Fecha Anticipo</th>
            <th align="center">Monto</th>
            <th align="center">Saldo</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?> [<?php echo intval($rs->fields['idcliente']); ?>]</td>
            <td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['saldo']);  ?></td>


        </tr>
<?php $rs->MoveNext();
} $rs->MoveFirst(); ?>
      </tbody>
    </table>
</div>
                      
<?php if (intval($rs->fields['idadherente']) > 0) { ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Adherente</th>
            <th align="center">Servicio de Comida</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo antixss($rs->fields['adherente']); ?> [<?php echo intval($rs->fields['idadherente']); ?>]</td>
            <td align="center"><?php echo antixss($rs->fields['servicio_comida']); ?></td>


        </tr>
<?php $rs->MoveNext();
} $rs->MoveFirst(); ?>
      </tbody>
    </table>
</div>          
<?php } ?>
                  
<hr />
<?php
$consulta = "
SELECT formas_pago.descripcion, gest_pagos_det.monto_pago_det  
FROM gest_pagos_det
inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
inner join formas_pago on formas_pago.idforma  = gest_pagos_det.idformapago
WHERE
gest_pagos.idpago = $idpago
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Forma Pago </th>
    
            <th align="center">Monto</th>



        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>

            <td align="right"><?php echo formatomoneda($rs->fields['monto_pago_det']);  ?></td>



        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
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
        
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
