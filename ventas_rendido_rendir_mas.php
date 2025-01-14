 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "670";
require_once("includes/rsusuario.php");



//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1  order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
if ($idcaja_compartida > 0) {
    echo "- Tu caja es compartida, no puede usar esta funcion, deben hacerlo desde la caja principal.";
    exit;
}
if ($idcaja == 0) {
    echo "- La caja debe estar abierta para poder realizar una venta.";
    exit;
}
if ($estadocaja == 3) {
    echo "- La caja debe estar abierta para poder realizar una venta.";
    exit;
}

/*
,ventas.factura, ventas.idcaja, ventas.fecha, usuarios.usuario, ventas.totalcobrar as monto_venta,
mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu,
mesas_estados_lista.descripcion as estado_mesa
*/

$consulta_glob = "
select ventas.idventa, 1 as `estado`, $idusu as `registrado_por`, '$ahora' as `registrado_el`, NULL as `anulado_por`, NULL as `anulado_el`,
'S' as masivo
from ventas
inner join mesas_atc on mesas_atc.idatc = ventas.idatc
inner join mesas on mesas.idmesa = mesas_atc.idmesa
inner join salon on salon.idsalon = mesas.idsalon
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
inner join mesas_estados_lista on mesas_estados_lista.idestadomesa = mesas.estado_mesa
inner join usuarios on usuarios.idusu = ventas.registrado_por
where
ventas.estado <> 6
and ventas.sucursal = $idsucursal
and mesas_atc.estado = 3
and ventas.idventa not in (select idventa from ventas_rendido where ventas_rendido.idventa = ventas.idventa and estado = 1)
and ventas.idcaja = $idcaja
and ventas.fecha < NOW() - INTERVAL 24 HOUR
";
//echo $consulta_glob;exit;
$rs = $conexion->Execute($consulta_glob) or die(errorpg($conexion, $consulta_glob));
$idventa = intval($rs->fields['idventa']);
$idmesa = intval($rs->fields['idmesa']);
if ($idventa == 0) {
    echo "No quedan ventas sin rendir que hayan pasado mas de 24 horas, las de las ultimas 24 horas debe rendirlas manualmente cada una. 
    <br /><a href='ventas_rendido.php'>[Volver]</a>";
    exit;
}




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

    // BUSCA SI ya no esta Rendido
    $consulta = "
    select idventarendido from ventas_rendido where idventa = $idventa and estado = 1
    ";
    $rsrend = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventarendido = intval($rsrend->fields['idventarendido']);
    if ($idventarendido > 0) {
        $errores .= "- No se puede rendir por que ya fue rendido anteriormente.<br />";
        $valido = "N";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        INSERT INTO `ventas_rendido`
        (`idventa`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, masivo) 
        $consulta_glob
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // cambiar estado de la Mesa
        $consulta = "
        update mesas 
        set 
        estado_mesa = 1
        where
        idmesa in (select idmesa from ($consulta_glob) as sinrendir)
        and estado_mesa = 8
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: ventas_rendido.php");
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
                    <h2>Rendir Ventas por Mesa Masivamente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p><a href="ventas_rendido.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />



<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<p align="center"><strong>Marcar como Rendido MASIVAMENTE todas las ventas de mas de 24 horas?</strong></p><br />

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='ventas_rendido.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


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
