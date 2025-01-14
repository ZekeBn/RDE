 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");


require_once("includes/funciones_iva.php");
require_once("includes/funciones_compras.php");


$idtran = intval($_GET['id']);
if ($idtran == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from tmpcompras 
where 
idtran = $idtran
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtran = intval($rs->fields['idtran']);
$monto_factura = $rs->fields['monto_factura'];
if ($idtran == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}


//Listamos los productos en detalle
$buscar = "
Select sum(subtotal) as subtotal
from tmpcompradeta 
where
idt=$idtran 
";
$rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$subt_acum = $rsdet->fields['subtotal'];

$diferencia = $monto_factura - $subt_acum;
//echo $subt_acum;
//echo $diferencia;exit;

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $idinsumo = antisqlinyeccion($_POST['idinsumo'], "text");





    if (intval($_POST['idinsumo']) == 0) {
        $valido = "N";
        $errores .= " - Debe seleccionar el articulo de ajuste.<br />";
    }
    if ($diferencia == 0) {
        $valido = "N";
        $errores .= " - No se encontraron diferencias.<br />";
    }



    // agregar al carrito de compras
    $parametros_array = [
        'idinsumo' => $_POST['idinsumo'],
        'cantidad' => 1,
        'costo_unitario' => $diferencia,
        'idtransaccion' => $idtran,
        'lote' => $_POST['lote'],
        'vencimiento' => $_POST['vencimiento']
    ];

    $res = validar_carrito_compra($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $res = agregar_carrito_compra($parametros_array);
        $idregcc = $res['idregcc'];

        header("location: gest_reg_compras_resto_det.php?id=".$idtran);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


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
                    <h2>Ajustar Diferencia en Compra</h2>
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
<?php
$consulta = "
select * from tmpcompras where idtran=$idtran
";
$rscompracab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$vencimiento = $rscompracab->fields['vencimiento'];
$totalcompra = $rscompracab->fields['totalcompra'];
$monto_factura = $rscompracab->fields['monto_factura'];
if ($monto_factura < 0) {
    $monto_factura = 0;
}

?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
          <tr>
            <th>Total Factura</th>
            <th><strong>Total Detalle</strong></th>
            <th><strong>Diferencia</strong></th>

          </tr>
        </thead>
        <tbody>
          <tr>
            <td align="right"><?php echo formatomoneda($monto_factura, 4, 'N')?></td>
            <td align="right"><?php echo formatomoneda($subt_acum, 4, 'N')?></td>
            <td align="right"><?php echo $monto_factura - $subt_acum; ?></td>

          </tr>
      </tbody>
    </table>
</div>
        <br />
<div class="clearfix"></div>
<br />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo de Ajuste *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idinsumo, descripcion
FROM insumos_lista
where
estado = 'A'
and descripcion like 'AJUSTE%'
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idinsumo'])) {
    $value_selected = htmlentities($_POST['idinsumo']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idinsumo',
    'id_campo' => 'idinsumo',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idinsumo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>                  
    </div>
</div>
<div class="clearfix"></div>
<br /><br />

    
    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_reg_compras_resto_det.php?id=<?php echo $idtran; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


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
