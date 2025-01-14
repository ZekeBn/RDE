 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");


require_once("includes/funciones_mesas.php");

$idmesa = intval($_GET['idmesa']);
if ($idmesa == 0) {
    header("location: cuenta_mesas.php");
    exit;
}

$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
where 
mesas.estadoex = 1 
and mesas_atc.idmesa = $idmesa
and sucursales.idsucu = $idsucursal    
and mesas_atc.estado = 1
order by mesas.numero_mesa asc, salon.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rs->fields['idmesa']);
if ($idmesa == 0) {
    header("location: cuenta_mesas.php");
    exit;
}

$numero_mesa = $rs->fields['numero_mesa'];
$salon = $rs->fields['salon'];
$sucursal = $rs->fields['sucursal'];
$idatc = $rs->fields['idatc'];

// saldo de la mesa
$parametros_array_saldo['idatc'] = $idatc;
$saldo_mesa_res = saldo_mesa($parametros_array_saldo);
$saldo_mesa = floatval($saldo_mesa_res['saldo_mesa']);
$pagos_mesa = floatval($saldo_mesa_res['pagos_mesa']);
$monto_mesa = floatval($saldo_mesa_res['monto_mesa']);

$atajos_propinas = "10,15,20,25";
$atajos_propinas_ar = explode(",", $atajos_propinas);








if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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
    $idqrtmpmonto = $_POST['idqrtmpmonto'];
    $monto_abonar = $_POST['monto_abonar'];
    $monto_propina = floatval($_POST['monto_propina']);
    $registrado_por = $idusu;
    $registrado_el = $ahora;

    $parametros_array = [
        "monto_abonar" => $monto_abonar,
        "monto_propina" => $monto_propina,
        "idatc" => $idatc,
        "registrado_por" => $registrado_por,
        "registrado_el" => $registrado_el
    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = qr_tmp_monto_add($parametros_array);
        $idqrtmpmonto = $res["idqrtmpmonto"];
        if ($res["valido"] == "S") {
            header("location: cuenta_mesas_qr_gen.php?id=$idqrtmpmonto");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    } else {
        $errores .= $res["errores"];
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// se puede mover esta funcion al archivo funciones_qr_tmp_monto.php y realizar un require_once
function qr_tmp_monto_add($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";



    $monto_abonar = antisqlinyeccion($parametros_array['monto_abonar'], "float");
    $monto_propina = antisqlinyeccion($parametros_array['monto_propina'], "float");
    //$total_abonar=antisqlinyeccion($parametros_array['total_abonar'],"float");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $total_abonar = $monto_abonar + $monto_propina;


    if (floatval($parametros_array['monto_abonar']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_abonar no puede ser cero o negativo.<br />";
    }
    /*if(floatval($parametros_array['monto_propina']) <= 0){
        $valido="N";
        $errores.=" - El campo monto_propina no puede ser cero o negativo.<br />";
    }*/
    if (intval($parametros_array['idatc']) == 0) {
        $valido = "N";
        $errores .= " - El campo idatc no puede ser cero o nulo.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
        insert into qr_tmp_monto
        (monto_abonar, monto_propina, total_abonar, idatc, registrado_por, registrado_el)
        values
        ($monto_abonar, $monto_propina, $total_abonar, $idatc, $registrado_por, $registrado_el)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // obtiene el insertado
        $consulta = "
        select idqrtmpmonto from qr_tmp_monto where registrado_por = $registrado_por order by idqrtmpmonto desc limit 1
        ";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idqrtmpmonto = intval($rsmax->fields['idqrtmpmonto']);



    }


    return ["errores" => $errores,"valido" => $valido,"idqrtmpmonto" => $idqrtmpmonto];
}


//$pagos_mesa=13000;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php
    $title_personaliza = "Pagar Mesa";
require_once("includes/head_gen.php"); ?>
<script>
function propina_atajo(porcentaje) {
    var monto_abonar = $("#monto_abonar").val();
    
    // Calcular la propina y redondear hacia abajo
    var monto_propina = Math.round(monto_abonar * (porcentaje / 100));
    
    // Establecer el valor en el campo de propina
    $("#monto_propina").val(monto_propina);
    
    // Calcular el total
    calcula_total();
}
function calcula_total(){
    var monto_abonar = $("#monto_abonar").val();
    var monto_propina = $("#monto_propina").val();
    // Verificar si monto_propina no es un número válido
    if (isNaN(monto_propina) || monto_propina === "") {
        monto_propina = 0;
    }
    var total_abonar = parseFloat(monto_abonar)+parseFloat(monto_propina);
    $("#total_abonar").val(total_abonar);
}
</script>
  </head>

  <body class="nav-md" onload="calcula_total();">
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
            
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Cobrar Mesa: <?php echo $numero_mesa; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                               
            
 <strong>Mesa:</strong> <?php echo $numero_mesa; ?> | <strong>Salon:</strong> <?php echo antixss($salon); ?> | <strong>Sucursal:</strong> <?php echo antixss($sucursal); ?> <br />   
 <strong>Monto Mesa: </strong> <?php echo formatomoneda($monto_mesa); ?> | 
 <strong>Pagos Mesa: </strong> <?php echo formatomoneda($pagos_mesa); ?> | 
 <strong>Saldo Mesa: </strong> <?php echo formatomoneda($saldo_mesa); ?>    
  <hr />   
  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Abonar Mesa *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" onchange="calcula_total();" onkeypress="calcula_total();"  onkeyup="calcula_total();" name="monto_abonar" id="monto_abonar" value="<?php  if (isset($_POST['monto_abonar'])) {
        echo htmlentities($_POST['monto_abonar']);
    } else {
        echo htmlentities($saldo_mesa);
    }?>" placeholder="Monto Abonar" class="form-control" required="required" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />
<strong>Calcular Propina:</strong><br />
<?php foreach ($atajos_propinas_ar as $porcentaje_propina) { ?>
<a href="#" class="btn btn-sm btn-default"  onClick="propina_atajo(<?php echo $porcentaje_propina; ?>);"><span class="fa fa-smile-o"></span> <?php echo $porcentaje_propina; ?>%</a>    
<?php } ?>
<div class="clearfix"></div>
<br />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Propina (Opcional)</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" onchange="calcula_total();" onkeypress="calcula_total();"  onkeyup="calcula_total();" name="monto_propina" id="monto_propina" value="<?php  if (isset($_POST['monto_propina'])) {
        echo htmlentities($_POST['monto_propina']);
    } else {
        echo '0';
    }?>" placeholder="Monto propina (opcional)" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Total Abonar</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" readonly name="total_abonar" id="total_abonar" value="<?php   echo floatval($saldo_mesa); ?>" placeholder="Total Abonar" class="form-control" required="required" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Generar QR</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cuenta_mesas.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<Hr />
<STRONG>PAGOS REGISTRADOS:</STRONG>
<?php
$consulta = "
select 'PAGO MESA' AS tipo, mesas_cobros_deta.montoabonado, formas_pago.descripcion as forma_pago,
(select usuario from usuarios where mesas_cobros_deta.registrado_por = usuarios.idusu) as registrado_por,
mesas_cobros_deta.registrado_el
from mesas_cobros_deta 
inner join formas_pago on formas_pago.idforma = mesas_cobros_deta.idformapago
where 
 estadopago = 1 
 and  idatc = $idatc
UNION ALL
select 'PROPINA' AS tipo, mesas_atc_propinas.monto_propina as montoabonado, formas_pago.descripcion as forma_pago,
(select usuario from usuarios where mesas_atc_propinas.registrado_por = usuarios.idusu) as registrado_por,
mesas_atc_propinas.registrado_el
from mesas_atc_propinas
inner join formas_pago on formas_pago.idforma = mesas_atc_propinas.mediopago
where
idatc = $idatc
order by registrado_el desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Tipo</th>
            <th align="right">Monto abonado</th>
            <th align="center">Forma pago</th>
            <th align="center">Registrado el</th>
            <th align="center">Registrado por</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td align="center"><?php echo antixss($rs->fields['tipo']); ?></td>
            <td align="right"><?php if ($rs->fields['tipo'] == 'PROPINA') {
                echo "*";
            } echo formatomoneda($rs->fields['montoabonado']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['forma_pago']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
        </tr>
<?php
if ($rs->fields['tipo'] == 'PAGO MESA') {
    $montoabonado_acum += $rs->fields['montoabonado'];
}


    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>
            <td align="right"><?php echo formatomoneda($montoabonado_acum); ?></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
      </tfoot>
    </table>
</div>
<strong>* La propina no descuenta el saldo de la mesa.</strong>
<br />
<div class="clearfix"></div>
<br /><br />









                <div class="row">

      

                      
                    </div>
                            
                      
                      
                      
                      
                      
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        
        <!-- POPUP DE MODAL OCULTO -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="estadocuentadet">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Detalle de la Cuenta</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
                        ...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->
        
        

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
