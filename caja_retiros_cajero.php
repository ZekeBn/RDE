 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");


$desde = date("Y-m-d");
$hasta = date("Y-m-d");

if (isset($_GET['desde']) && trim($_GET['desde']) != '') {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


$consulta = "
SELECT *,
(select usuario from usuarios where idusu = caja_retiros.cajero) as cajero,
(select usuario from usuarios where idusu = caja_retiros.retirado_por) as quienllevo,
(select sucursales.nombre from sucursales where idsucu = caja_retiros.idsucursal) as sucursal
FROM caja_retiros

where estado <> 6  
and cajero=$idusu
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
SELECT *,
(select usuario from usuarios where idusu = caja_reposiciones.cajero) as cajero,
(select usuario from usuarios where idusu = caja_reposiciones.entregado_por) as quienllevo,
(select sucursales.nombre from sucursales where idsucu = caja_reposiciones.idsucursal) as sucursal
FROM caja_reposiciones
where estado <> 6  
and cajero=$idusu
";
$rs1 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



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
                    <h2>Reimpresion de valores caja: <span class="fa fa-user"></span>&nbsp; <?php echo $cajero; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo htmlentities($desde); ?>" placeholder="Desde" class="form-control" required />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php   echo htmlentities($hasta); ?>" placeholder="Hasta" class="form-control" required />                    
    </div>
</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><hr /><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th colspan="7">Entrega de valores</th>
            

        </tr>
        <tr>
            <th></th>
            <th align="center">Idcaja</th>
            <th align="center">Fecha Retiro</th>
            <th align="center">Cajero</th>
            <th align="center">Monto</th>
            <th align="center">Observacion</th>
            <th align="center">Sucursal</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="caja_retiros_cajero_rei.php?tipo=1&regser=<?php echo $rs->fields['regserialretira']; ?>" target="_blank" class="btn btn-sm btn-default" title="Reimprimir"  data-toggle="tooltip" data-placement="right"  data-original-title="Reimprimir "><span class="fa fa-print"></span> Reimprimir</a>
             

                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['idcaja']); ?></td>
            <td align="center"><?php if ($rs->fields['fecha_retiro'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_retiro']));
            }  ?></td>
            
            <td align="center"><?php echo antixss($rs->fields['cajero']); ?></td>
            
            <td align="right"><?php echo formatomoneda($rs->fields['monto_retirado']);  ?></td>
            <td align="right"><?php echo($rs->fields['obs']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
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
             <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    
                  </div>
                  <div class="x_content">
                            <div class="table-responsive">
                            <table width="100%" class="table table-bordered jambo_table bulk_action">
                              <thead>
                                <tr>
                                    <th colspan="7">Recepcion de valores</th>
                                    

                                </tr>
                                <tr>
                                    <th></th>
                                    <th align="center">Idcaja</th>
                                    <th align="center">Fecha Recepcion</th>
                                    <th align="center">Cajero</th>
                                    <th align="center">Monto</th>
                                    <th align="center">Observacion</th>
                                    <th align="center">Sucursal</th>


                                </tr>
                              </thead>
                              <tbody>
                        <?php while (!$rs1->EOF) { ?>
                                <tr>
                                    <td>
                                        
                                        <div class="btn-group">
                                            <a href="caja_retiros_cajero_rei.php?tipo=2&regser=<?php echo $rs1->fields['regserialentrega']; ?>" target="_blank" class="btn btn-sm btn-default" title="Reimprimir"  data-toggle="tooltip" data-placement="right"  data-original-title="Reimprimir "><span class="fa fa-print"></span> Reimprimir</a>
                                     

                                        </div>

                                    </td>
                                    <td align="center"><?php echo antixss($rs1->fields['idcaja']); ?></td>
                                    <td align="center"><?php if ($rs1->fields['fecha_reposicion'] != "") {
                                        echo date("d/m/Y H:i:s", strtotime($rs1->fields['fecha_reposicion']));
                                    }  ?></td>
                                    
                                    <td align="center"><?php echo antixss($rs1->fields['cajero']); ?></td>
                                    
                                    <td align="right"><?php echo formatomoneda($rs1->fields['monto_recibido']);  ?></td>
                                    <td align="right"><?php echo($rs1->fields['obs']);  ?></td>
                                    <td align="center"><?php echo antixss($rs1->fields['sucursal']); ?></td>
                                </tr>
                        <?php $rs1->MoveNext();
                        } //$rs->MoveFirst();?>
                              </tbody>
                            </table>
                        </div>
            
                    </div>
                </div>
              </div>
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
