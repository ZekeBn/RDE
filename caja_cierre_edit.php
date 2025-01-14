 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "269";
require_once("includes/rsusuario.php");

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

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
select *,
(select usuario from usuarios where idusu = caja_super.cajero) as cajero,
(select nombre from sucursales where idsucu =caja_super.sucursal) as sucursal
from caja_super 
where 
 estado_caja <> 6
 and date(fecha_apertura) >= '$desde'
 and date(fecha_apertura) <= '$hasta'
$whereadd2
order by idcaja desc
limit 500
";
//echo $consulta;exit;
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
                    <h2>Editar Cierre de Caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<div class="alert alert-warning alert-dismissible fade in" role="alert">

<strong>IMPORTANTE:</strong><br />Los cajeros no deberian tener acceso a este modulo.
</div>
                  
<form id="form1" name="form1" method="get" action="">



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha apertura Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Fecha apertura" class="form-control" required />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha apertura Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php  echo $hasta; ?>" placeholder="Fecha apertura" class="form-control" required />                    
    </div>
</div>
<?php /*?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cajero *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cajero" id="cajero" value="<?php  if(isset($_POST['cajero'])){ echo intval($_POST['cajero']); }else{ echo intval($rs->fields['cajero']); }?>" placeholder="Cajero" class="form-control" required="required" />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cajero" id="cajero" value="<?php  if(isset($_POST['cajero'])){ echo intval($_POST['cajero']); }else{ echo intval($rs->fields['cajero']); }?>" placeholder="Cajero" class="form-control" required="required" />
    </div>
</div>
<?php */ ?>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-check-square-o"></span> Filtrar</button>

        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idcaja</th>
            <th align="center">Fecha apertura</th>
            <th align="center">Fecha cierre</th>
            <th align="center">Monto apertura</th>
            <th align="center">Monto cierre</th>
            <th align="center">Cajero</th>
            <th align="center">Sucursal</th>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <?php if ($rs->fields['rendido'] != 'S') { ?>
                    <a href="caja_cierre_edit_edit.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <?php } else { ?>
                    Rendido
                    <?php } ?>
                    

                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['idcaja']); ?></td>
            <td align="center"><?php if ($rs->fields['fecha_apertura'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_apertura']));
            }  ?></td>
             <td align="center"><?php if ($rs->fields['fecha_cierre'] != "") {
                 echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_cierre']));
             }  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_cierre']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['cajero']); ?></td>
            <td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
            
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
