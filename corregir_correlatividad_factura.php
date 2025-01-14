 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "2";
$submodulo = "199";
require_once("includes/rsusuario.php");

require_once("includes/funciones_timbrado.php");

$consulta = "
SELECT * 
FROM lastcomprobantes 
where 
idsuc=$factura_suc
and pe=$factura_pexp
and idempresa=$idempresa 
order by ano desc 
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {




    // validaciones basicas
    $valido = "S";
    $errores = "";

    $parametros_array = [
        'numfac' => $_POST['numfac'],
        'factura_suc' => $factura_suc,
        'factura_pexp' => $factura_pexp,
        'idusu' => $idusu,
    ];
    $res = validar_correccion_correlatividad($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = 'N';
        $errores .= $res['errores'];
    }

    // si todo es correcto actualiza
    if ($valido == "S") {

        // registra la correccion
        registrar_correccion_correlatividad($parametros_array);

        header("location: corregir_correlatividad_factura.php?ok=".date("YmdHis"));
        exit;

    }

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
                    <h2>Proxima Factura</h2>
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
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idsuc" id="idsuc" value="<?php echo agregacero($factura_suc, 3); ?>" placeholder="Idsuc" class="form-control" disabled />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Punto de Expedicion *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="pe" id="pe" value="<?php echo agregacero($factura_pexp, 3); ?>" placeholder="Pe" class="form-control" disabled />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Proximo Numero de Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="numfac" id="numfac" value="<?php  if (isset($_POST['numfac'])) {
        echo htmlentities($_POST['numfac'] + 1);
    } else {
        echo htmlentities($rs->fields['numfac'] + 1);
    }?>" placeholder="Numfac" class="form-control" autofocus  />                    
    </div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>

        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>

<?php if ($_GET['ok'] > 0) { ?>
<br /><hr /><br />
<strong>Cambio Aplicado:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Factura Sucursal</th>
            <th align="center">Factura Punto Expedicion</th>
            <th align="center">Proximo Numero de Factura</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td align="center"><?php echo agregacero($factura_suc, 3); ?></td>
            <td align="center"><?php echo agregacero($factura_pexp, 3); ?></td>
            <td align="center"><?php echo agregacero($rs->fields['numfac'] + 1, 7); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />


<?php } ?>
<br /><br /><br /><br />
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
<?php
$consulta = "
select *,
(select usuario from usuarios where lastcomprobantes_log.registrado_por = usuarios.idusu) as registrado_por
from lastcomprobantes_log 
order by idlastlog desc
limit 500
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimos 500 Cambios</h2>
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
            <th align="center">Sucursal</th>
            <th align="center">Punto Expedicion</th>
            <th align="center">Numero factura anterior</th>
            <th align="center">Numero factura nuevo</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td align="center"><?php echo agregacero($rs->fields['idsuc'], 3); ?></td>
            <td align="center"><?php echo agregacero($rs->fields['pe'], 3); ?></td>
            <td align="center"><?php echo intval($rs->fields['numfac_ant']); ?></td>
            <td align="center"><?php echo intval($rs->fields['numfac_new']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
        </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<br /><br /><br /><br />
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
