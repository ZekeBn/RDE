 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "538";
require_once("includes/rsusuario.php");

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (trim($_GET['hdesde']) == '' or trim($_GET['hhasta']) == '') {
    $hdesde = '00:00';
    $hhasta = '23:59';
} else {
    $hdesde = htmlentities($_GET['hdesde']);
    $hhasta = htmlentities($_GET['hhasta']);
}

// conversion
$desde_comp = $desde.' '.$hdesde.':00';
$hasta_comp = $hasta.' '.$hhasta.':59';

//Traemos la info solicitada
$whereadd = '';

$cajer = intval($_REQUEST['cajer']);
if ($cajer > 0) {
    $whereadd .= " and pagos_extra.idusu=$cajer";
}

$prov = intval($_REQUEST['prov']);
if ($prov > 0) {
    $whereadd .= " and pagos_extra.idprov=$prov";
}

$idsucu = intval($_REQUEST['idsucu']);
if ($idsucu > 0) {
    $whereadd .= " and caja_super.sucursal=$idsucu";
}
//Pagos por caja desde el cajero de la caja de recaudacion

$buscar = "
Select pagos_extra.idcaja,pagos_extra.fecha,pagos_extra.factura,pagos_extra.concepto,(select nombre from proveedores where idproveedor=pagos_extra.idprov)
as proveedor,pagos_extra.monto_abonado,(select usuario from usuarios where idusu=pagos_extra.idusu) as cajero 
from pagos_extra
inner join usuarios on usuarios.idusu=pagos_extra.idusu
inner join caja_super on caja_super.idcaja = pagos_extra.idcaja
where 
date(pagos_extra.fecha) between '$desde' and '$hasta'
and pagos_extra.fecha >= '$desde_comp'
and pagos_extra.fecha <= '$hasta_comp'
and pagos_extra.estado=1
$whereadd
order by pagos_extra.idcaja desc
";
$rspagos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



$parametros_url = trim($_SERVER['REQUEST_URI']);
$ex = explode("?", $parametros_url);
$parametros_url = $ex[1];
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
                    <h2>Pagos por caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <form id="form1" name="form1" method="get" action="">


                      <div class="x_content">
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12"> Fecha Desde *</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Fecha apertura" class="form-control" required />
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12"> Fecha Hasta *</label>
                            <div class="col-md-9 col-sm-6 col-xs-12">
                            <input type="date" name="hasta" id="hasta" value="<?php  echo $hasta; ?>" placeholder="Fecha apertura" class="form-control" required />
                            </div>
                        </div>

                      <div class="x_content">
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12"> Hora Desde *</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <input type="time" name="hdesde" id="hdesde" value="<?php  echo $hdesde; ?>" placeholder="Hora apertura" class="form-control" required />
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12"> Hora Hasta *</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <input type="time" name="hhasta" id="hhasta" value="<?php  echo $hhasta; ?>" placeholder="hora apertura" class="form-control" required />
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12"> Cajero *</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <?php
                                $buscar = "select idusu,usuario from usuarios U where exists (select pe.idusu from pagos_extra pe
                                    where pe.idusu = U.idusu
                                    group by pe.idusu)
                                    order by U.usuario";
$rcjr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>
                                <select name="cajer" id="cajer" class="form-control" >
                                            <option value="" selected="selected">Seleccionar</option>
                                            <?php while (!$rcjr->EOF) {?>
                                            <option value="<?php echo $rcjr->fields['idusu']?>" <?php if ($rcjr->fields['idusu'] == $_REQUEST['cajer']) {?> selected="selected" <?php } ?>><?php echo $rcjr->fields['usuario']?></option>
                                            <?php $rcjr->MoveNext();
                                            }?>
                                </select>
                            </div>

                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Proveedor *</label>
                        <div class="col-md-9 col-sm-6 col-xs-12">
                            <?php
                            $buscar = "select idproveedor,nombre from proveedores P where exists (select pe.idprov from pagos_extra pe
                            where pe.idprov = P.idproveedor and estado=1
                            group by pe.idprov)
                            order by P.nombre";
$rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>
                            <select name="prov" id="prov" class="form-control" >
                                        <option value="" selected="selected">Seleccionar</option>
                                        <?php while (!$rsprov->EOF) {?>
                                        <option value="<?php echo $rsprov->fields['idproveedor']?>" <?php if ($rsprov->fields['idproveedor'] == $_REQUEST['prov']) {?> selected="selected" <?php } ?>><?php echo $rsprov->fields['nombre']?></option>
                                        <?php $rsprov->MoveNext();
                                        }?>
                            </select>
                        </div>
                            </div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";


// valor seleccionado
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S',

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>


  </div>


                        <div class="clearfix"></div>

<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-check-square-o"></span> Filtrar</button>

        </div>
    </div>
                    </form>





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION -->

            <?php if (isset($_GET['desde'])) { ?>
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                  <h2>Resumen de Pagos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                        <hr />
                        <div class="col-md-12">
                            <div class="table-responsive">
                            <p><a class="btn btn-warning" href="inf_pagosxcaja_xl_pd.php?clase=1&<?php echo $parametros_url; ?>" target="_blank"><span class="fa fa-file-pdf-o">&nbsp; Descargar PDF</a></p>
                                <table width="100%" class="table table-bordered jambo_table bulk_action">
                                  <thead>
                                    <tr>
                                        <th colspan="7">Pagos por caja</th>
                                    </tr>
                                    <tr>

                                        <th align="center">Id caja</th>
                                        <th align="center">Cajero</th>
                                        <th align="center">Fecha de pago</th>
                                        <th align="center">Proveedor</th>
                                        <th align="center">Factura</th>
                                        <th align="center">Concepto</th>
                                        <th align="center">Monto Abonado</th>

                                    </tr>
                                  </thead>
                                  <tbody>
                            <?php while (!$rspagos->EOF) {

                                $totalpagos = $totalpagos + floatval($rspagos->fields['monto_abonado']);

                                ?>
                                    <tr>

                                        <td align="center"><?php echo antixss($rspagos->fields['idcaja']); ?></td>
                                        <td align="center"><?php echo antixss($rspagos->fields['cajero']); ?></td>
                                        <td align="center"><?php if ($rspagos->fields['fecha'] != "") {
                                            echo date("d/m/Y H:i:s", strtotime($rspagos->fields['fecha']));
                                        }  ?></td>
                                        <td align="center"><?php echo antixss($rspagos->fields['proveedor']); ?></td>
                                        <td align="center"><?php echo antixss($rspagos->fields['factura']); ?></td>
                                        <td align="right"><?php echo antixss($rspagos->fields['concepto']);  ?></td>
                                        <td align="right"><?php echo formatomoneda($rspagos->fields['monto_abonado']);  ?></td>


                                    </tr>
                            <?php $rspagos->MoveNext();
                            } //$rs->MoveFirst();?>
                            <tr>
                                <td colspan="6" align="right">Total Pagos:</td>
                                <td colspan="12" align="right"><?php echo formatomoneda($totalpagos, 0, 'N'); ?></td>
                            </tr>

                                  </tbody>
                                </table>
                            </div>
                        </div>

                        <br />





                  </div>
                </div>
              </div>
            </div>

            <?php } ?>

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
