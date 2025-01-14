 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "278";
require_once("includes/rsusuario.php");

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

if (trim($_GET['recibo']) != '') {
    $recibo = antisqlinyeccion(trim($_GET['recibo']), "text");
    $whereadd = "
    and recibo = $recibo
    ";
}
$consulta = "
select * ,
(select usuario from usuarios where cuentas_clientes_pagos_cab.registrado_por = usuarios.idusu) as registrado_por,
(select razon_social from cliente where cliente.idcliente = cuentas_clientes_pagos_cab.idcliente) as cliente
from cuentas_clientes_pagos_cab 
where 
estado = 1
 and date(fecha_pago) >= '$desde'
 and date(fecha_pago) <= '$hasta'
$whereadd
and notanum is null
order by registrado_el desc
limit 10000
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
                    <h2>Buscar Cobros</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<a href="cobro_cuentas.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<hr />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha pago desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php echo $desde; ?>" placeholder="Fecha pago" class="form-control" required />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha pago hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php  echo $hasta; ?>" placeholder="Fecha pago" class="form-control" required />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Recibo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="recibo" id="recibo" value="<?php  if (isset($_GET['recibo'])) {
        echo htmlentities($_GET['recibo']);
    }?>" placeholder="Recibo" class="form-control"  />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><br />


<hr />
<?php if ($rs->fields['idcuentaclientepagcab'] > 0) { ?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Razon Social</th>
            <th align="center">Monto abonado</th>
            <th align="center">Fecha pago</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>

            <th align="center">Recibo</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="cobro_cuentas_imp.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" class="btn btn-sm btn-default" title="Imprimir" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir"><span class="fa fa-print"></span></a>
                    <a href="cobro_cuentas_imp_pdf.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" target="_blank" class="btn btn-sm btn-default" title="Descargar Ticket PDF" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar Ticket PDF"><span class="fa fa-file-pdf-o"></span></a>
                    <a href="cobro_cuentas_imp_recibo_pdf.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" target="_blank" class="btn btn-sm btn-default" title="Descargar A4 PDF" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar A4 PDF"><span class="fa fa-file"></span></a>
                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['cliente']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_abonado']);  ?></td>
            <td align="center"><?php if ($rs->fields['fecha_pago'] != "") {
                echo date("d/m/Y", strtotime($rs->fields['fecha_pago']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>

            <td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>

        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<?php } ?>


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
