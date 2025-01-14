 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "467";
require_once("includes/rsusuario.php");


//preferencias
$buscar = "Select muestra_caja_abierta from preferencias where idempresa=$idempresa";
$rspre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$muestrac = trim($rspre->fields['muestra_caja_abierta']);

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
// limite por defecto
$limit = " limit 1000 ";


if (intval($_GET['cajero']) > 0) {
    $cajero_g = intval($_GET['cajero']);
    $whereadd .= "
    and caja_super.cajero = $cajero_g
    ";
}
if (intval($_GET['tipocaja']) > 0) {
    $tipocaja = intval($_GET['tipocaja']);
    $whereadd .= "
    and caja_super.tipocaja = $tipocaja
    ";
} else {
    $whereadd .= "
    and caja_super.tipocaja = 1
    ";
}
if (intval($_GET['idsucu']) > 0) {
    $idsucu = intval($_GET['idsucu']);
    $whereadd .= "
    and caja_super.sucursal = $idsucu
    ";
}

// no cambiar el orden
if (!isset($_GET['estado_caja'])) {
    $whereadd .= "
    ";
    $limit = " limit 100 ";
} elseif (intval($_GET['estado_caja']) == 0) {
    $whereadd .= "
    and date(fecha_apertura) >= '$desde'
    and date(fecha_apertura) <= '$hasta'
    ";
} elseif (intval($_GET['estado_caja']) == 1) {
    $whereadd .= "
    and estado_caja = 1
    ";
} else {
    $estado_caja = intval($_GET['estado_caja']);
    $whereadd .= "
    and estado_caja = $estado_caja
    and date(fecha_apertura) >= '$desde'
    and date(fecha_apertura) <= '$hasta'
    ";
}
// estos sobreescriben a los de arriba
if (intval($_GET['idcaja']) > 0) {
    $idcaja = intval($_GET['idcaja']);
    $whereadd = "
    and caja_super.idcaja = $idcaja
    ";
}
if ($soporte <> 1) {
    $whereadd2 = "
    and cajero not in (select idusu from usuarios where soporte = 1)
    ";
}
$consulta = "
select * , (select usuario from usuarios where idusu = caja_super.cajero) as cajero_usu,
(select nombre from sucursales where idempresa = $idempresa and idsucu = caja_super.sucursal) as sucursal,
(select tipo_caja from caja_gestion_tipo where idtipocaja = caja_super.tipocaja) as tipo_caja
from caja_super
where
estado_caja <> 6
$whereadd
$whereadd2
order by caja_super.estado_caja asc, fecha_apertura desc
$limit
";
//echo $consulta; exit;
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
                    <h2>Informe de Caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Apertura Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Apertura Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required />                    

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
    $value_selected = "";
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_GET['estado_caja'])) {
    $value_selected = htmlentities($_GET['estado_caja']);
} else {
    $value_selected = "";
}
// opciones
$opciones = [
    'ABIERTA' => '1',
    'CERRADA' => '3'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'estado_caja',
    'id_campo' => 'estado_caja',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Caja </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_GET['tipocaja'])) {
    $value_selected = htmlentities($_GET['tipocaja']);
} else {
    $value_selected = 1;
}
// opciones
$opciones = [
    'CAJA RECAUDACION' => '1',
    'CAJA CHICA' => '2'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'tipocaja',
    'id_campo' => 'tipocaja',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cajero </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idusu, usuario
FROM usuarios
where
estado = 1
order by usuario asc
 ";

// valor seleccionado
if (isset($_GET['cajero'])) {
    $value_selected = htmlentities($_GET['cajero']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'cajero',
    'id_campo' => 'cajero',

    'nombre_campo_bd' => 'usuario',
    'id_campo_bd' => 'idusu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Idcaja </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idcaja" id="idcaja" value="<?php echo $idcaja; ?>" class="form-control"  />                    

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

<hr />

<a href="informe_caja_new_csv.php?desde=<?php echo antixss($desde); ?>&hasta=<?php echo antixss($hasta); ?>&idsucu=<?php echo antixss($_GET['idsucu']); ?>&estado_caja=<?php echo antixss($_GET['estado_caja']); ?>&tipocaja=<?php echo antixss($_GET['tipocaja']); ?>&idusu=<?php echo antixss($_GET['idusu']); ?>&idcaja=<?php echo antixss($_GET['idcaja']); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar CSV</a>
                      
<a href="informe_caja_new_pdf.php?desde=<?php echo antixss($desde); ?>&hasta=<?php echo antixss($hasta); ?>&idsucu=<?php echo antixss($_GET['idsucu']); ?>&estado_caja=<?php echo antixss($_GET['estado_caja']); ?>&tipocaja=<?php echo antixss($_GET['tipocaja']); ?>&idusu=<?php echo antixss($_GET['idusu']); ?>&idcaja=<?php echo antixss($_GET['idcaja']); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-pdf-o"></span> Descargar PDF</a>
                      
<a href="informe_caja_new_venta_pdf.php?desde=<?php echo antixss($desde); ?>&hasta=<?php echo antixss($hasta); ?>&idsucu=<?php echo antixss($_GET['idsucu']); ?>&estado_caja=<?php echo antixss($_GET['estado_caja']); ?>&tipocaja=<?php echo antixss($_GET['tipocaja']); ?>&idusu=<?php echo antixss($_GET['idusu']); ?>&idcaja=<?php echo antixss($_GET['idcaja']); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-pdf-o"></span> Descargar Ventas de la Caja</a>
                      
                      <br />
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
            <tr align="center" valign="middle">
              <th></th>
              <th>Idcaja</th>
              
              <th>Sucursal</th>
              <th>Apertura</th>
              <th>Cierre</th>
              <th>Estado</th>
              <th>Cajero</th>
              <th>Monto Apertura</th>
              <th>Monto al cierre</th>
              <th>Sobrante</th>
              <th>Faltante</th>
               <th>Tipo Caja</th>
              <th>Rendido</th>

            </tr>
    </thead>    
    <tbody>
<?php
while (!$rs->EOF) {

    $estado = $rs->fields['estado_caja'];
    if ($estado == 1) {
        $estadocaja = "Abierta";
    } elseif ($estado == 3) {
        $estadocaja = "Cerrada";
    } else {
        $estadocaja = "Indeterminada";
    }
    ?>
            <tr align="center" valign="middle">
                 <td>
               <?php if ($muestrac == 'S') { ?>
                       <a href="informe_caja_arq_new.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default"  title="Resumen" data-toggle="tooltip" data-placement="right"  data-original-title="Resumen"><span class="fa fa-search"></span> R</a>
                 <?php } else {
                     //es NO, por lo cual solo debe mostrarse el boton si el estado ==3
                     if ($estado == 3) { ?>
                          <a href="informe_caja_arq_new.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default"  title="Resumen" data-toggle="tooltip" data-placement="right"  data-original-title="Resumen"><span class="fa fa-search"></span> R</a>
                      <?php }?>
                  <?php }?>

                     <?php if ($muestrac == 'S') { ?>
                  <a href="informe_caja_det_new.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default"  title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span> D</a>
                 <?php } else {
                     //es NO, por lo cual solo debe mostrarse el boton si el estado ==3
                     if ($estado == 3) { ?>
                          <a href="informe_caja_det_new.php?id=<?php echo $rs->fields['idcaja']; ?>" class="btn btn-sm btn-default"  title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span> D</a>
                      <?php }?>
                  <?php }?>
                </td>
              <td><?php echo $rs->fields['idcaja']; ?></td>
              <td align="left"><?php echo $rs->fields['sucursal']; ?></td>
              <td><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_apertura'])); ?></td>
              <td><?php if ($rs->fields['fecha_cierre'] != '') {
                  echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_cierre']));
              } ?></td>
              <td><?php echo $estadocaja?></td>
              <td><?php echo capitalizar($rs->fields['cajero_usu']); ?></td>
              <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura']); ?></td>
              <td><?php if ($estado == 3) {
                  echo formatomoneda($rs->fields['monto_cierre']);
              } else {
                  echo "Caja Abierta";
              }  ?></td>
              <td align="right"><?php if ($estado == 3) {
                  echo formatomoneda($rs->fields['sobrante']);
              } else {
                  echo "Caja Abierta";
              }  ?></td>
              <td align="right" style="color:#FF0000;"><?php if ($estado == 3) {
                  echo formatomoneda($rs->fields['faltante']);
              } else {
                  echo "Caja Abierta";
              } ?></td>
              <td align="left"><?php echo $rs->fields['tipo_caja']; ?></td>
              
<td>
<?php if ($rs->fields['rendido'] != 'S') {?>
<button class="btn btn-sm btn-default" type="button" style="background-color:#F00; color:#FFF; margin:0px;"><span class="fa fa-times-circle"></span></button>
<?php } else { ?>
<button class="btn btn-sm btn-default" type="button" style="background-color:#090; color:#FFF; margin:0px;"><span class="fa fa-check-circle"></span></button>
<?php } ?>
</td>
     
            </tr>
<?php $rs->MoveNext();
} ?>
      </tbody>
    </table>
</div>
<br />

<br /><br />

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
