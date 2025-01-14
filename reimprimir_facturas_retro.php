 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
/*--------------------------------------------------------
se agrega la factura a4




-----------------------------------------------------*/

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (trim($_GET['hdesde']) == '' or trim($_GET['hhasta']) == '') {
    $hdesde = "00:00";
    $hhasta = "23:59";
} else {
    $hdesde = date("H:i", strtotime($_GET['hdesde']));
    $hhasta = date("H:i", strtotime($_GET['hhasta']));
}
$desde_completo = $desde." ".$hdesde.':00';
$hasta_completo = $hasta." ".$hhasta.':59';

$whereadd = "
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
 and ventas.fecha >= '$desde_completo' 
 and ventas.fecha <= '$hasta_completo' 
";


if (($_GET['cj']) > 0) {
    $idcaja = intval($_GET['cj']);
    $whereadd .= " and ventas.idcaja = $idcaja";

}
if (($_GET['idsucu']) > 0) {
    $idsuc = intval($_GET['idsucu']);
    $whereadd .= " and ventas.sucursal = $idsuc";

}
if (trim($_GET['idsucu']) == 'ss') {

    $consulta = "
    select idsucu 
    from sucursales 
    where 
    estado = 1 
    and matriz = 'N'
    ";
    $rssucufil = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $sucu_or_add = "and ( ".$saltolinea;
    $i = 0;
    while (!$rssucufil->EOF) {
        $i++;
        $idsuc = intval($rssucufil->fields['idsucu']);
        if ($i == 1) {
            $sucu_or_add .= " ventas.sucursal = $idsuc ".$saltolinea;
        } else {
            $sucu_or_add .= " or ventas.sucursal = $idsuc ".$saltolinea;
        }
        $rssucufil->MoveNext();
    }
    $sucu_or_add .= " ) ".$saltolinea;
    $whereadd = $sucu_or_add;

}
if (($_GET['ruc']) != '') {
    $ruc = antisqlinyeccion($_GET['ruc'], "text");
    $whereadd .= " and ventas.ruc = $ruc";
}
if (($_GET['factura']) != '') {
    $factura = antisqlinyeccion($_GET['factura'], "text");
    $whereadd = " and ventas.factura = $factura";
}
if (($_GET['idventa']) > 0) {
    $idventa = intval($_GET['idventa']);
    $whereadd = " and ventas.idventa = $idventa";
}

if (trim($_GET['desde']) != '') {
    $consulta = "
    select *,
    (select sucursales.nombre from sucursales where sucursales.idsucu = ventas.sucursal) as sucursal,
    (select sucursales.integracion_pegasus from sucursales where sucursales.idsucu = ventas.sucursal) as integracion_pegasus,
    CASE WHEN tipo_venta = 1 then 'CONTADO' ELSE 'CREDITO' END as condicion,
    (select canal from canal where canal.idcanal = ventas.idcanal) as canal
    from ventas 
    where 
     estado <> 6 

    $whereadd
    order by fecha asc
    limit 10000
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

$buscar = "Select usa_factura_a4_auto from preferencias limit 1";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$usa_factura_a4 = trim($rsp->fields['usa_factura_a4_auto']);

$buscar = "Select permite_reimpresion_masiva from preferencias_caja limit 1";
$rspc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$permite_reimpresion_masiva = trim($rspc->fields['permite_reimpresion_masiva']);


//$usa_factura_a4="S";

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
                    <h2>Reimpresion de Facturas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="time" name="hdesde" id="hdesde" value="<?php  echo $hdesde; ?>" placeholder="hora desde" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="time" name="hhasta" id="hhasta" value="<?php echo $hhasta; ?>" placeholder="hora hasta" class="form-control" required />      
    
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
    $value_selected = htmlentities($rs->fields['idsucu']);
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
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">RUC </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
        echo htmlentities($_GET['ruc']);
    } ?>" placeholder="RUC" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="documento" id="documento" value="<?php  if (isset($_GET['documento'])) {
        echo htmlentities($_GET['documento']);
    } ?>" placeholder="Documento" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="factura" id="factura" value="<?php  if (isset($_GET['factura'])) {
        echo htmlentities($_GET['factura']);
    }?>" placeholder="Factura" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Idventa </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idventa" id="idventa" value="<?php  if (isset($_GET['idventa'])) {
        echo htmlentities($_GET['idventa']);
    } ?>" placeholder="Idventa" class="form-control"  />                    
    </div>
</div>

<div class="clearfix"></div>
<br />


    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>

        </div>
    </div>


<br />
</form>        
<div class="clearfix"></div>
<br />
<?php if (trim($_GET['desde']) != '') {?>
<hr />
<?php if ($permite_reimpresion_masiva == 'S') { ?>
<a href="reimprimir_facturas_retro_masivo.php<?php echo parametros_url(); ?>" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Reimpresion Masiva</a>
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idventa</th>
            <th align="center">Fecha</th>
            <th align="center">Factura</th>
            <th align="center">Condicion</th>
            <th align="center">Sucursal</th>
            <th align="center">Total sin Desc</th>
            <th align="center">Descneto</th>
            <th align="center">Total venta</th>
            <th align="center">Razon social</th>
            <th align="center">Ruc</th>
            <th align="center">Canal</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <?php if (trim($rs->fields['factura']) != '') { ?>
                    <a href="factura_imprime_impresor_sincaja.php?vta=<?php echo $rs->fields['idventa']; ?>" class="btn btn-sm btn-default" title="Imprimir Factura" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Factura"><span class="fa fa-print"></span> F</a>
                    <a href="factura_imprime_impresor_sincaja_vp.php?vta=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Vista Previa Factura" data-toggle="tooltip" data-placement="right"  data-original-title="Vista Previa Factura"><span class="fa fa-file-pdf-o"></span> F</a>
                    <?php if ($usa_factura_a4 == 'S') {?>
                         <a href="factura_imprime_impresor_sincaja_vp.php?clase=1&vta=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Factura A4" data-toggle="tooltip" data-placement="right"  data-original-title="Factura A4"><span class="fa fa-file-pdf-o"></span> F A4</a>    
                    <?php }
                    } ?>
                    <?php if (trim($rs->fields['integracion_pegasus']) == 'S') { ?>
                    <a href="mesas/pegasus_reenvio.php?idventa=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Reenviar a Pegasus" data-toggle="tooltip" data-placement="right"  data-original-title="Reenviar a Pegasus"><span class="fa fa-send"></span> Pegasus</a>
                    <?php } ?>
                    <a href="factura_imprime_impresor_sincaja.php?vta=<?php echo $rs->fields['idventa']; ?>&tk=1" class="btn btn-sm btn-default" title="Imprimir Ticket" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Ticket"><span class="fa fa-print"></span> T</a>
                    <a href="factura_imprime_impresor_sincaja_vp_tk.php?vta=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Vista Previa Ticket" data-toggle="tooltip" data-placement="right"  data-original-title="Vista Previa Ticket"><span class="fa fa-file-pdf-o"></span> T</a>
                    <a href="cat_ventas_envia_mail.php?idventa=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Enviar Facturas por Email" data-toggle="tooltip" data-placement="right"  data-original-title="Enviar por Mail"><span class="fa fa-envelope"></span> M</a>                                                                                                                                                                                                                                                                                                 
                    <!--<a href="factura_imprime_impresor_sincaja_vp_pant.php?vta=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Impresion de Pantalla" data-toggle="tooltip" data-placement="right"  data-original-title="Impresion de Pantalla"><span class="fa fa-file-pdf-o"></span> T</a>-->
                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
            <td align="center"><?php if ($rs->fields['fecha'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['condicion']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total_venta']); ?></td>
            <td align="center"><?php echo intval($rs->fields['descneto']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
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
