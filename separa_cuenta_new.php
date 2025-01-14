 <?php
/*------------------------------------------
16/05/2024 Solo revision.
-----------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "216";
require_once("../includes/rsusuario.php");

$idmesa = intval($_GET['idmesa']);

// mesa origen
$consulta = "
select * 
from mesas_atc 
where 
idmesa = $idmesa 
and estado <> 6 
and estado <> 3 
and idsucursal = $idsucursal 
order by idatc desc 
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rs->fields['idmesa']);
$idatc = intval($rs->fields['idatc']);
if (intval($idatc) == 0) {
    echo "- ATC no permitido para tu sucursal.";
    exit;
}

$consulta = "
select permite_separacuenta, permite_agrupar, permite_mudarmesa
from mesas_preferencias
limit 1
";
$rsprefm = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$permite_separacuenta = trim($rsprefm->fields['permite_separacuenta']);
$permite_agrupar = trim($rsprefm->fields['permite_agrupar']);
$permite_mudarmesa = trim($rsprefm->fields['permite_mudarmesa']);
if ($permite_separacuenta == 'N') {
    echo "- Esta accion fue bloqueada por el administrador de tu local.";
    exit;
}


// limpia registros huerfanos
$consulta = "
update  tmp_ventares_cab 
set estado = 6, observacion = 'ANULADO AUTO HUERFANO SEPARA CUENTA',
anulado_el = '$ahora', anulado_por = $idusu
where 
idatc not in (select idatc from mesas_atc where estado = 1) 
and registrado = 'N' 
and idatc > 0 
and estado <> 6
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$buscar = "
Select sum(monto) as total, mesas.numero_mesa, salon.nombre, mesas.idmesa 
from tmp_ventares_cab 
inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa 
INNER JOIN salon on mesas.idsalon = salon.idsalon 
where 
finalizado='S' 
and registrado='N' 
and estado=1 
and tmp_ventares_cab.idsucursal = $idsucursal
and mesas.idmesa = $idmesa
GROUP by mesas.idmesa 
order by mesas.numero_mesa asc
";
$rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$totalmesa = intval($rspedicu->fields['total']);
$numero_mesa = intval($rspedicu->fields['numero_mesa']);
$salon = trim($rspedicu->fields['nombre']);


//echo $idmesa;
if ($idmesa > 0) {
    $buscar = "
    Select productos.descripcion,tmp_ventares.combinado,idprod_mitad1,idprod_mitad2, tmp_ventares.precio, tmp_ventares.subtotal,
    idtmpventares_cab,tmp_ventares.cantidad,idventatmp,idproducto,
    (select tmp_ventares_cab.delivery_costo from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery_costo,
    (select tmp_ventares_cab.delivery from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery,
    (select tmp_ventares_cab.idusu from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as idusu
     from 
    tmp_ventares
    inner join productos on productos.idprod=tmp_ventares.idproducto
    where 
    idsucursal=$idsucursal 
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.idtmpventares_cab in (
                                    select idtmpventares_cab
                                    from tmp_ventares_cab
                                    where
                                    idsucursal = $idsucursal
                                    and finalizado = 'S'
                                    and registrado = 'N'
                                    and estado = 1
                                    and idmesa=$idmesa
                                    )
    and tmp_ventares.idatcdet is null
    and tmp_ventares.idtipoproducto <> 5
    order by productos.descripcion asc, idventatmp asc
    ";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcuerpo = $rsbb->RecordCount();
    // buscar usuario
    $operador = intval($rsbb->fields['idusu']);
    $consulta = "
    select usuario from usuarios where idusu = $operador
    ";
    $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $operador = $rsop->fields['usuario'];

    $buscar = "
    Select sum(monto) as total, mesas.numero_mesa, salon.nombre, mesas.idmesa 
    from tmp_ventares_cab 
    inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa 
    INNER JOIN salon on mesas.idsalon = salon.idsalon 
    where 
    finalizado='S' 
    and registrado='N' 
    and estado=1 
    and tmp_ventares_cab.idsucursal = $idsucursal
    and mesas.idmesa = $idmesa
    GROUP by mesas.idmesa 
    order by mesas.numero_mesa asc
    ";
    $rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $totalmesa = intval($rspedicu->fields['total']);
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
      
<style>
.modal-dialog {
    margin-top: 0;
    margin-bottom: 0;
    height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -webkit-flex-direction: column;
    -ms-flex-direction: column;
    flex-direction: column;
    -webkit-box-pack: center;
    -webkit-justify-content: center;
    -ms-flex-pack: center;
    justify-content: center;
}

.modal.fade .modal-dialog {
    -webkit-transform: translate(0, -100%);
    transform: translate(0, -100%);
}
.modal.in .modal-dialog {
    -webkit-transform: translate(0, 0);
    transform: translate(0, 0);
}
.contador{
    width:20px;
    height:20px;    
    position:absolute;
    font-size:16px;
    font-weight:bold;
    border:1px solid #000000;
    background-color:#CCC;
    text-align:center;
    margin:0px;
    float:left;

}
</style>
<script>
function marcarTodos() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"][id^="item_"]');
    var checkboxAll = document.getElementById('checkbox_all');
    
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = checkboxAll.checked;
    });
}
function asignar_subcuenta() {
    var html_sin_asignar=$("#sin_asignar").html();    
    var html_asignados=$("#asignados").html();    
    var idatcdet = $("#idatcdet").val();
    var checkboxes = document.querySelectorAll('input[type="checkbox"][id^="item_"]');
    var items = '';
    
    checkboxes.forEach(function(checkbox) {
        if (checkbox.checked) {
            var idventatmp = checkbox.value;
            items += idventatmp + ',';
        }
    });
    
    // Eliminar la última coma si existe
    if (items.length > 0) {
        items = items.slice(0, -1);
    }
    
    var direccionurl='separa_cuenta_asigna_subcuenta.php';    
    var parametros = {
      "idventatmp_csv" : items,
      "idatcdet" : idatcdet,
      "accion" : 'ASIGNA',
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#sin_asignar").html('Asignando...');        
            $("#asignados").html('Actualizando...');                    
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    document.location.href='separa_cuenta_new.php?idmesa=<?php echo $idmesa; ?>&idatcdetasignacablog='+obj.idatcdetasignacablog+'#selprod';
                }else{
                    alert('Errores: '+obj.errores);    
                    $("#error_box_msg").html(nl2br(obj.errores));
                    $("#error_box").show();
                    $("#sin_asignar").html(html_sin_asignar);        
                    $("#asignados").html(html_asignados);    
                }
            }else{
                alert(response);
                $("#sin_asignar").html(html_sin_asignar);        
                $("#asignados").html(html_asignados);    
            }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
            $("#sin_asignar").html(html_sin_asignar);        
            $("#asignados").html(html_asignados);    
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        $("#sin_asignar").html(html_sin_asignar);        
        $("#asignados").html(html_asignados);    
    });
    
    // Ahora la variable items contendrá los IDs de los checkboxes marcados como un CSV
    //console.log(items);
    
    //alert(items);
}
function borra_asignacion_subcuenta(idventatmp,idatcdet) {
    var html_sin_asignar=$("#sin_asignar").html();    
    var html_asignados=$("#asignados").html();    
    //var idatcdet = $("#idatcdet").val();
    //var checkboxes = document.querySelectorAll('input[type="checkbox"][id^="item_"]');
    var items = '';
    
    /*checkboxes.forEach(function(checkbox) {
        if (checkbox.checked) {
            var idventatmp = checkbox.value;
            items += idventatmp + ',';
        }
    });
    
    // Eliminar la última coma si existe
    if (items.length > 0) {
        items = items.slice(0, -1);
    }*/
    
    var direccionurl='separa_cuenta_asigna_subcuenta.php';    
    var parametros = {
      "idventatmp_csv" : idventatmp,
      "idatcdet" : idatcdet,
      "accion" : 'DESASIGNA',
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#sin_asignar").html('Asignando...');        
            $("#asignados").html('Actualizando...');                    
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    document.location.href='separa_cuenta_new.php?idmesa=<?php echo $idmesa; ?>&idatcdetasignacablog='+obj.idatcdetasignacablog+'#selprod';
                }else{
                    alert('Errores: '+obj.errores);    
                    $("#error_box_msg").html(nl2br(obj.errores));
                    $("#error_box").show();
                    $("#sin_asignar").html(html_sin_asignar);        
                    $("#asignados").html(html_asignados);    
                }
            }else{
                alert(response);
                $("#sin_asignar").html(html_sin_asignar);        
                $("#asignados").html(html_asignados);    
            }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
            $("#sin_asignar").html(html_sin_asignar);        
            $("#asignados").html(html_asignados);    
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        $("#sin_asignar").html(html_sin_asignar);        
        $("#asignados").html(html_asignados);    
    });
    
    // Ahora la variable items contendrá los IDs de los checkboxes marcados como un CSV
    //console.log(items);
    
    //alert(items);
}
function preticket_mesa(idmesa,idatcdet){
    $("#preticket_imp_"+idatcdet).hide();
    //$("#reimprimebox").html('<iframe src="../impresor_ticket_mesa.php?idmesa='+id+'" style="width:310px; height:500px;"></iframe>');
    // si es la app
    if((typeof window.flutter_inappwebview != 'undefined')){
        document.location.href='../impresor_ticket_mesa.php?idmesa='+idmesa+'&idatcdet='+idatcdet+'&modredir=2';
    }
    // si no es la app
    if((typeof window.flutter_inappwebview == 'undefined')){
        var url='../impresor_ticket_mesa.php?idmesa='+idmesa+'&idatcdet='+idatcdet;
        //alert(url);
        $("#reimprimebox").html('<iframe src="'+url+'" style="width:310px; height:500px;"></iframe>');
        setTimeout(function (){$("#preticket_imp_"+idatcdet).show()},3000);
    }
}
</script>
  </head>
<!-- CUERPO / BODY -->
  <body class="nav-md">
        <div class="container body">
            <div class="main_container">
                <!-- top navigation -->
                  <?php //require_once("includes/menu_top_gen.php");?>
                <!-- /top navigation -->
                <!-- page content -->
                <div class="right_col" role="main">
                    <div class="">
                        
                            <div class="clearfix"></div>
                              <!-- SECCION -->
                                   <div class="row">
                                      <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="x_panel">
                                              <div class="x_title">
                                                      <?php
                                                        // crea imagen
                                                        $img = "../gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "../gfx/empresas/emp_0.png";
}
?>
                                                 <?php require_once("cabecera_ppal.php"); ?>
                                          
                                                  
                                                  
                                                  
                                                 <form id="formu01" action="ventas_salon.php" method="post">
                                                         <input type="hidden" name="ocsalon" id="ocsalon" value="0" />
                                                      <input type="hidden" name="occantidad" id="occantidad" value="0" />
                                                      <input type="hidden" name="ocserial" id="ocserial" value="0" />
                                                  </form>                                              
                                                  
                                             
                                                
                                              </div>
                                                <div class="x_content">
                                                          
                                                      <?php if (trim($errores) != "") { ?>
                                                        <div class="alert alert-danger alert-dismissible fade in" role="alert"><strong>Errores:</strong><br /><?php echo nl2br($errores); ?></div>
                                                  
                                                          <?php } ?>
                                                          

                                                  </div>
                                            </div>
                                          </div>
                                    </div>
                                    <!-- ROW--->
                                       <div class="clearfix"></div>
                                      <!-- Mesas del Sistema -->
                                       <div class="row" id="mesascomponen" >
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <div class="x_panel">
                                                  <div class="x_title">
                                                    <h2><i class="fa fa-arrow-circle-o-right"></i>Separar Cuenta <small id="minitextomesas"></small></h2>
                                                    <div align="center">
                                                      <a href="ventas_salon.php"><i class="fa fa-home fa-2x">&nbsp;Regresar</i></a>
                                                    </div>  
                                             
                                                    <div class="clearfix"></div>
                                                  </div>
                                                  <div class="x_content" id="minimesas">

<?php
$consulta = "
select *,
(select usuario from usuarios where mesas_atc_det.registrado_por = usuarios.idusu) as registrado_por_txt,
(
Select 
sum(tmp_ventares.subtotal) as subtotal
from 
tmp_ventares
inner join productos on productos.idprod=tmp_ventares.idproducto
where 
tmp_ventares.borrado = 'N'
and tmp_ventares.idatcdet = mesas_atc_det.idatcdet
) as monto,
(
Select 
sum(tmp_ventares.cantidad) as cantidad
from 
tmp_ventares
inner join productos on productos.idprod=tmp_ventares.idproducto
where 
tmp_ventares.borrado = 'N'
and tmp_ventares.idatcdet = mesas_atc_det.idatcdet
) as cantidad
from mesas_atc_det 
where 
 estado = 1 
 and idatc = $idatc
order by idatcdet asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<p>
    <a href="mesas_atc_det_add.php?idatc=<?php echo $idatc; ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Sub-Cuenta</a>
    <a id="preticket_imp_0" href="javascript:preticket_mesa(<?php echo $idmesa; ?>,0);" class="btn btn-sm btn-default" title="Preticket" data-toggle="tooltip" data-placement="right"  data-original-title="Preticket"><span class="fa fa-print"></span> Pre-Ticket Global</a>
</p>
<hr />
<div id="reimprimebox"></div>
<strong>Sub Cuentas:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Nombre atc</th>
            <th align="center">Monto</th>
            <th align="center">Cantidad</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a id="preticket_imp_<?php echo $rs->fields['idatcdet']; ?>" href="javascript:preticket_mesa(<?php echo $idmesa; ?>,<?php echo $rs->fields['idatcdet']; ?>);" class="btn btn-sm btn-default" title="Preticket" data-toggle="tooltip" data-placement="right"  data-original-title="Preticket"><span class="fa fa-print"></span> Pre-Ticket</a>
                    <a href="mesas_atc_det_edit.php?id=<?php echo $rs->fields['idatcdet']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="mesas_atc_det_del.php?id=<?php echo $rs->fields['idatcdet']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
                <td align="center"><?php echo antixss($rs->fields['nombre_atc']); ?> [<?php echo antixss($rs->fields['idatcdet']); ?>]</td>
                <td align="center"><?php echo formatomoneda($rs->fields['monto'], 4, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N'); ?></td>
                <td align="center"><?php echo antixss($rs->fields['registrado_por_txt']); ?></td>
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

<div class="clearfix"></div>
<hr />
<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error_box" style="display:none;">
<strong>Errores:</strong><br /><span id="error_box_msg"></span>
</div>
<a name="selprod"></a>
<div id="sin_asignar"  class="col-md-6 col-sm-6 col-xs-12">
<strong>Productos sin sub-cuenta asignada:</strong>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>
          <th style="text-align:center;"><input type="checkbox" name="checkbox_all" id="checkbox_all" value="" onclick="marcarTodos();" /></th>
          <th><strong>Producto</strong></th>
         </tr>
    </thead>
    <tbody>
<?php
$i = 1;
while (!$rsbb->EOF) {
    $idventatmp = intval($rsbb->fields['idventatmp']);
    $idproducto = antisqlinyeccion($rsbb->fields['idproducto'], 'text');

    //$total=$rs->fields['precio']*$rs->fields['total'];
    //$totalacum+=$total;

    //$idvt=$rs->fields['idventatmp'];
    $consulta = "
    select tmp_ventares_agregado.*, precio_adicional*cantidad as subtotalag
    from tmp_ventares_agregado
    where 
    idventatmp = $idventatmp
    order by alias desc
    ";
    $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
select tmp_ventares_sacado.*
from tmp_ventares_sacado
where 
idventatmp = $idventatmp
order by alias desc
";
    $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
    <tr>
      <td align="center"><input type="checkbox" name="item_<?php echo $i; ?>" id="item_<?php echo $i; ?>" value="<?php echo $rsbb->fields['idventatmp']; ?>" />
        </td>
      <td height="50"><?php echo formatomoneda($rsbb->fields['cantidad'], 4, 'N'); ?> x <?php echo Capitalizar($rsbb->fields['descripcion']); ?><br />P. Unitario: <?php echo formatomoneda($rsbb->fields['precio']); ?> | Subtotal: <?php echo formatomoneda($rsbb->fields['subtotal']); ?><?php
    if ($rsbb->fields['combinado'] == 'S') {

        $prod_1 = $rsbb->fields['idprod_mitad1'];
        $prod_2 = $rsbb->fields['idprod_mitad2'];
        $consulta = "
select *
from productos
where 
(idprod_serial = $prod_1 or idprod_serial = $prod_2)
order by descripcion asc
";
        $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rspcom->EOF) {

            ?><br /><span style="font-style:italic;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Mitad <?php echo Capitalizar($rspcom->fields['descripcion']); ?></span>
      <?php $rspcom->MoveNext();
        }
    } ?>
         <br />
      <?php if ($rsag->RecordCount() > 0) { ?>
          <strong>Agregados:</strong>
          <br />
          <?php while (!$rsag->EOF) {?> 
          - <?php echo Capitalizar($rsag->fields['alias']); ?> (Gs. <?php echo formatomoneda($rsag->fields['subtotalag']); ?>)<br />
          <?php
        $subtotal_noasigna += $rsag->fields['subtotalag'];
              $rsag->MoveNext();
          } ?>
      <?php } ?>
      <?php if ($rssac->RecordCount() > 0) { ?>
          <strong>Eliminados:</strong>
          <br />
          <?php while (!$rssac->EOF) {?> 
            - Sin <?php echo Capitalizar($rssac->fields['alias']); ?><br />
            <?php $rssac->MoveNext();
          } ?>
      <?php } ?>  
      </td>
      </tr>
<?php
$subtotal_noasigna += $rsbb->fields['subtotal'] + $rsag->fields['precio_adicional'];
    $i++;
    $rsbb->MoveNext();
} ?>
  </tbody>
</table>
</div>

<p align="center"><strong>Total:</strong> <?php echo formatomoneda($subtotal_noasigna); ?></p>
<br />

Sub Cuenta:
<?php
// consulta
$consulta = "
SELECT idatcdet, nombre_atc
FROM mesas_atc_det
where
estado = 1
and idatc = $idatc
order by nombre_atc asc
 ";
/*
$opciones_extra=array(
    'PROCESO MANUAL' => 'PM'
);
*/

// valor seleccionado
if (isset($_POST['idatcdet'])) {
    $value_selected = htmlentities($_POST['idatcdet']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idatcdet',
    'id_campo' => 'idatcdet',

    'nombre_campo_bd' => 'nombre_atc',
    'id_campo_bd' => 'idatcdet',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    //'opciones_extra' => $opciones_extra,

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
<div class="clearfix"></div>
<br />
<a href="javascript:void(0);" onclick="asignar_subcuenta();" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Asignar Sub-Cuenta</a>
<div class="clearfix"></div>
<br /><br />

</div>

<div id="asignados" class="col-md-6 col-sm-6 col-xs-12">
<?php
$consulta = "
select *,
(select usuario from usuarios where mesas_atc_det.registrado_por = usuarios.idusu) as registrado_por_txt
from mesas_atc_det 
where 
 estado = 1 
 and idatc = $idatc
order by nombre_atc asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<strong>Productos con sub-cuenta asignada:</strong><br />
<?php while (!$rs->EOF) {
    $idatcdet = $rs->fields['idatcdet'];

    ?>
<STRONG>CUENTA #<?php echo antixss($rs->fields['idatcdet']); ?>: <?php echo antixss($rs->fields['nombre_atc']); ?></STRONG><BR />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>
          <th style="text-align:center;"></th>
          <th><strong>Producto</strong></th>
         </tr>
    </thead>
    <tbody>
<?php
    $totalmesa = 0;
    // debe ser igual al de arriba pero filtrando por subcuenta
    $buscar = "
Select productos.descripcion,tmp_ventares.combinado,idprod_mitad1,idprod_mitad2, tmp_ventares.precio, tmp_ventares.subtotal,
idtmpventares_cab,tmp_ventares.cantidad,idventatmp,idproducto,
(select tmp_ventares_cab.delivery_costo from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery_costo,
(select tmp_ventares_cab.delivery from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery,
(select tmp_ventares_cab.idusu from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as idusu
 from 
tmp_ventares
inner join productos on productos.idprod=tmp_ventares.idproducto
where 
idsucursal=$idsucursal 
and tmp_ventares.borrado = 'N'
and tmp_ventares.idtmpventares_cab in (
                                select idtmpventares_cab
                                from tmp_ventares_cab
                                where
                                idsucursal = $idsucursal
                                and finalizado = 'S'
                                and registrado = 'N'
                                and estado = 1
                                and idmesa=$idmesa
                                )
and tmp_ventares.idatcdet = $idatcdet
and tmp_ventares.idtipoproducto <> 5
order by productos.descripcion asc, idventatmp asc
";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $i = 1;
    $subtotal_asigna = 0;
    while (!$rsbb->EOF) {
        $idventatmp = intval($rsbb->fields['idventatmp']);
        $idproducto = antisqlinyeccion($rsbb->fields['idproducto'], 'text');

        //$total=$rs->fields['precio']*$rs->fields['total'];
        //$totalacum+=$total;

        //$idvt=$rs->fields['idventatmp'];
        $consulta = "
    select tmp_ventares_agregado.*, precio_adicional*cantidad as subtotalag
    from tmp_ventares_agregado
    where 
    idventatmp = $idventatmp
    order by alias desc
    ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
select tmp_ventares_sacado.*
from tmp_ventares_sacado
where 
idventatmp = $idventatmp
order by alias desc
";
        $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        ?>
    <tr>
      <td align="center">
      <a href="javascript:void(0);" onclick="borra_asignacion_subcuenta(<?php echo $rsbb->fields['idventatmp']; ?>,<?php echo $idatcdet; ?>);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
      </td>
      <td height="50"><?php echo formatomoneda($rsbb->fields['cantidad'], 4, 'N'); ?> x <?php echo Capitalizar($rsbb->fields['descripcion']); ?><br />P. Unitario: <?php echo formatomoneda($rsbb->fields['precio']); ?> | Subtotal: <?php echo formatomoneda($rsbb->fields['subtotal']); ?><?php
        if ($rsbb->fields['combinado'] == 'S') {

            $prod_1 = $rsbb->fields['idprod_mitad1'];
            $prod_2 = $rsbb->fields['idprod_mitad2'];
            $consulta = "
select *
from productos
where 
(idprod_serial = $prod_1 or idprod_serial = $prod_2)
order by descripcion asc
";
            $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            while (!$rspcom->EOF) {

                ?><br /><span style="font-style:italic;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Mitad <?php echo Capitalizar($rspcom->fields['descripcion']); ?></span>
      <?php $rspcom->MoveNext();
            }
        } ?>
         <br />
      <?php if ($rsag->RecordCount() > 0) { ?>
          <strong>Agregados:</strong>
          <br />
          <?php while (!$rsag->EOF) {?> 
          - <?php echo Capitalizar($rsag->fields['alias']); ?> (Gs. <?php echo formatomoneda($rsag->fields['subtotalag']); ?>)<br />
          <?php
            $subtotal_asigna += $rsag->fields['subtotalag'];
              $rsag->MoveNext();
          } ?>
      <?php } ?>
      <?php if ($rssac->RecordCount() > 0) { ?>
          <strong>Eliminados:</strong>
          <br />
          <?php while (!$rssac->EOF) {?> 
            - Sin <?php echo Capitalizar($rssac->fields['alias']); ?><br />
            <?php $rssac->MoveNext();
          } ?>
      <?php } ?>  
      </td>
      </tr>
<?php

$subtotal_asigna += $rsbb->fields['subtotal'] + $rsag->fields['precio_adicional'];
        $i++;
        $rsbb->MoveNext();
    } ?>
  </tbody>
</table>
</div>

<p align="center"><strong>Total:</strong> <?php echo formatomoneda($subtotal_asigna); ?></p>
<br />
<?php $rs->MoveNext();
} ?>
</div>



 

                                                   </div>
                                                 
                                            </div>
                                        </div>
                                        

                                   </div>
                                     <div class="clearfix"></div>
                                      
                          </div>
                  </div>
                
            
<!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
<!-- POPUP DE MODAL OCULTO -->        
            
            
            
      </div> <!-- /body container -->  
       
        <!-- Impresiones y reimpresiones -->     
        <div style="display: none" id="reimprimebox"></div>
              
       
        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
    <?php require_once("includes/footer_gen.php"); ?>
           <script src="../js/shortcut.js"></script>
        <script src="../js/ventas_mesas_nueva.js?nc=<?php echo date("YmdHis"); ?>"></script>
  </body>
</html>
