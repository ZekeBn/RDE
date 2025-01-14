 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "310";
require_once("includes/rsusuario.php");

//echo "Modulo en Actualizacion"; exit;

if (isset($_GET['e']) && $_GET['e'] == '1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    /*if($_SESSION['form_control'] != $_GET['form_control']){
        $errores.="- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido="N";
    }
    if(trim($_GET['form_control']) == ''){
        $errores.="- Control del formularios no activado.<br />";
        $valido="N";
    }
    $_SESSION['form_control'] = md5(rand());*/
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    $ruc = antisqlinyeccion($_GET['ruc'], "text");
    $razon_social = antisqlinyeccion($_GET['razon_social'], "like");
    $documento = antisqlinyeccion($_GET['documento'], "text");
    $fantasia = antisqlinyeccion($_GET['fantasia'], "like");

    if (trim($_GET['ruc']) != '') {
        $whereadd .= " and ruc = $ruc ";
    }
    if (trim($_GET['razon_social']) != '') {
        $whereadd .= " and razon_social like '%$razon_social%' ";
    }
    if (trim($_GET['documento']) != '') {
        $whereadd .= " and documento = $documento ";
    }
    if (trim($_GET['fantasia']) != '') {
        $whereadd .= " and fantasia like '%$fantasia%' ";
    }


    // si todo es correcto
    if ($valido == "S") {

        $consulta = "
        select * 
        from cliente 
        where 
        estado = 1
        $whereadd
        limit 50
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
                    <h2>Facturar a cliente recurrente</h2>
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






<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
        echo htmlentities($_GET['ruc']);
    }?>" placeholder="Ruc" class="form-control"  />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_GET['razon_social'])) {
        echo htmlentities($_GET['razon_social']);
    }?>" placeholder="Razon social" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="documento" id="documento" value="<?php  if (isset($_GET['documento'])) {
        echo htmlentities($_GET['documento']);
    }?>" placeholder="Documento" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Fantasia </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_GET['fantasia'])) {
        echo htmlentities($_GET['fantasia']);
    }?>" placeholder="Nombre de Fantasia" class="form-control"  />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

  <input type="hidden" name="e" value="1" />

<br />
</form>
<div class="clearfix"></div>
<br /><hr /><br />


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idcliente</th>
            <th align="center">Ruc</th>
            <th align="center">Razon social</th>
            <th align="center">Nombre Fantasia</th>
            <th align="center">Nombre</th>
            <th align="center">Apellido</th>
            <th align="center">Documento</th>

        </tr>
      </thead>
      <tbody>
<?php
if ($rs->fields['idcliente'] > 0) {
    while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="facturar_recurrente_det.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['apellido']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['documento']);  ?></td>

        </tr>
<?php $rs->MoveNext();
    } //$rs->MoveFirst();

}
?>
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
                    <h2>Ultimas 10 emitidas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php

$consulta = "
select *,
(select sucursales.nombre from sucursales where sucursales.idsucu = ventas.sucursal) as sucursal,
CASE WHEN tipo_venta = 1 then 'CONTADO' ELSE 'CREDITO' END as condicion,
(select canal from canal where canal.idcanal = ventas.idcanal) as canal
from ventas 
where 
 estado <> 6 
order by fecha desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
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
                    <a href="factura_imprime_impresor_sincaja.php?vta=<?php echo $rs->fields['idventa']; ?>" class="btn btn-sm btn-default" title="Imprimir" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir"><span class="fa fa-print"></span></a>
                    <a href="factura_imprime_impresor_sincaja_vp.php?vta=<?php echo $rs->fields['idventa']; ?>" target="_blank" class="btn btn-sm btn-default" title="Vista Previa" data-toggle="tooltip" data-placement="right"  data-original-title="Vista Previa"><span class="fa fa-file-pdf-o"></span></a>
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
            <td align="center"><?php echo intval($rs->fields['ruchacienda']).'-'.intval($rs->fields['dv']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
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
