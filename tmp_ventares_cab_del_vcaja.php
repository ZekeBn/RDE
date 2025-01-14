 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


$idtmpventares_cab = intval($_GET['id']);
if ($idtmpventares_cab == 0) {
    header("location: gest_ventas_resto_caja.php");
    exit;
}
// consulta a la tabla
$consulta = "
select *
from tmp_ventares_cab 
where 
idtmpventares_cab = $idtmpventares_cab
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtmpventares_cab = intval($rs->fields['idtmpventares_cab']);
if ($idtmpventares_cab == 0) {
    header("location: gest_ventas_resto_caja.php");
    exit;
}



//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
if ($idcaja == 0) {
    echo "<br /><br />Debes tener una caja abierta para borrar un producto.<br /><br />";
    exit;
}


//Traemos las preferencias para la empresa
$buscar = "
Select 
borrar_ped, borrar_ped_cod, borrar_ped_cod, tiempo_borrado_pedidos
from preferencias 
where 
idempresa=$idempresa 
";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrar_ped = trim($rspref->fields['borrar_ped']);
$borrar_ped_cod = trim($rspref->fields['borrar_ped_cod']);
$tiempo_seguro_borrado = intval($rspref->fields['tiempo_borrado_pedidos']);




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

    // parametros para la funcion
    $parametros_array = [
        'idtmpventares_cab' => $idtmpventares_cab,
        'cod' => $_POST['cod'],
        'idcaja' => $idcaja,
        'registrado_por' => $idusu, // cajero, si envio codigo la funcion reemplazara por el propietario del codigo
        'idsucursal' => $idsucursal,
    ];
    //print_r($parametros_array);
    // valida los parametros
    $res = validar_borrar_pedido($parametros_array);
    if ($res['valido'] != 'S') {
        $errores .= nl2br($res['errores']);
        $valido = 'N';
    }


    // si todo es correcto actualiza
    if ($valido == "S") {


        // registra el borrado
        $res = registrar_borrar_pedido($parametros_array);


        header("location: gest_ventas_resto_caja.php");
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
                    <h2>Borrar Pedido</h2>
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

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pedido Nº *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idtmpventares_cab" id="idtmpventares_cab" value="<?php  if (isset($_POST['idtmpventares_cab'])) {
        echo htmlentities($_POST['idtmpventares_cab']);
    } else {
        echo htmlentities($rs->fields['idtmpventares_cab']);
    }?>" placeholder="Idtmpventares cab" class="form-control" required readonly disabled="disabled" />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
        echo htmlentities($_POST['razon_social']);
    } else {
        echo htmlentities($rs->fields['razon_social']);
    }?>" placeholder="Razon social" class="form-control" required readonly disabled="disabled" />                    
    </div>
</div>
<?php if ($borrar_ped_cod == "S") { ?>
<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Autorizacion *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="password" name="cod" id="cod" value="" placeholder="codigo de autorizacion" class="form-control" required   />                    
    </div>
</div>
<?php } ?>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
       <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_ventas_resto_caja.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>



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
