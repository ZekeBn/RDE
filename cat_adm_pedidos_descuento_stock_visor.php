 <?php
/*---------------------------------------------------------
01/08/2022:
Se trabaja y mejora la edicion de pedidos
Se agregan telefono, email, lupa para visualizar los pedidos
25/01/23 Se corrige timestam en BD (scar los valores por defecto en los campos fecha para catering)

03/08/2023
if(($_GET['idsucu']) > 0){
    $idsuc=intval($_GET['idsucu']);
    $whereadd.=" and ventas.sucursal = $idsuc";

}
se agrega el filtro por sucursal, pero por permisos en nueva tabla de sucursales_permisos_paneles
sucursales_permisos_panel



-----------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");

// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "354";
require_once("includes/rsusuario.php");
require_once("includes/funciones_cobros.php");
require_once("includes/funciones_ventas.php");
/*-------------------------------------------------
Revertir anulados
----------------------------------------------*/

$msg = '';
$revertir = 0;
$seleccionados = 0;
$idrev = intval($_REQUEST['idt']);
if ($revertir > 0 && $idrev > 0) {
    $update = "update pedidos_eventos set anulado_por=NULL, anulado_el=NULL,estado=3 where idtransaccion=$idrev";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    $msg = "Se ha restaurado la Transaccion $idrev. Verifique.";
}

$existenpedidos = 0;
$add = "";
$orden = "order by pedidos_eventos.evento_para,hora_entrega asc ";
$where = "where pedidos_eventos.estado=3 ";
if ($_REQUEST['desde'] != '') {
    $desde = antisqlinyeccion($_REQUEST['desde'], 'date');
    $add .= " and date(evento_para)>=$desde ";
}
if ($_REQUEST['hasta'] != '') {
    $hasta = antisqlinyeccion($_REQUEST['hasta'], 'date');
    $add .= " and date(pedidos_eventos.evento_para)<=$hasta ";
}
if ($_REQUEST['desdee'] != '') {
    $desdeemision = antisqlinyeccion($_REQUEST['desdee'], 'date');
    $add .= " and date(pedidos_eventos.registrado_el)>=$desdeemision ";
}
if ($_REQUEST['hastae'] != '') {
    $hastaemision = antisqlinyeccion($_REQUEST['hastae'], 'date');
    $add .= " and date(pedidos_eventos.registrado_el)<=$hastaemision ";
}
if ($_REQUEST['nombreape'] != '') {
    $orden = " ";
    $nombre = antisqlinyeccion($_REQUEST['nombreape'], 'text');
    $nsolo = str_replace("'", "", $nombre);
    $add .= " and (select razon_social from cliente where idcliente=pedidos_eventos.id_cliente_solicita)  like ('%$nsolo%') ";
}
if ($_REQUEST['cliente_ped'] != '') {
    $ex = explode("|", $_REQUEST['cliente_ped']);
    $idsolicita = intval($ex[0]);
    $add .= " and pedidos_eventos.id_cliente_solicita=$idsolicita ";

}
if ($_REQUEST['dc'] != '') {
    $documento = antisqlinyeccion($_REQUEST['dc'], 'int');
    $add .= " and cliente.documento=$documento ";
}

if ($_REQUEST['dcestado'] != '') {
    $estapedido = antisqlinyeccion($_REQUEST['dcestado'], 'int');


    switch ($estapedido) {
        case 6:
            $whereadd = "and pedidos_eventos.estado=6 ";
            break;
        default:
            $add .= " and pedidos_eventos.estado_pedido_int=$estapedido ";
    }

}



if ($_REQUEST['dcestadocuenta'] != '') {
    $estapedido = antisqlinyeccion($_REQUEST['dcestadocuenta'], 'int');


    switch ($estapedido) {
        case 1:
            $whereadd .= "and pedidos_eventos.saldo_evento>0 ";
            break;
        case 2:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 ";
            break;
        case 3:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 ";
            break;
        case 4:
            $whereadd .= "and (pedidos_eventos.saldo_evento>0 and pedidos_eventos.cobrado_evento>0 and pedidos_eventos.monto_pedido <> pedidos_eventos.cobrado_evento )  ";
            break;

        default:
    }
}

if ($_REQUEST['dcestadostock'] != '') {
    $estapedido = antisqlinyeccion($_REQUEST['dcestadostock'], 'int');


    switch ($estapedido) {
        case 1:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 and pedidos_eventos.detallado=1 and pedidos_eventos.descontado=0 ";
            break;
        case 2:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 and pedidos_eventos.detallado=2 and pedidos_eventos.descontado=1  ";
            break;

        default:

    }

}

if ($_REQUEST['email'] != '') {
    $email = antisqlinyeccion($_REQUEST['email'], 'email');
    $add .= " and cliente.email=$email ";
}
if ($_REQUEST['transa'] != '') {
    $idtt = intval($_REQUEST['transa']);
    $add .= " and pedidos_eventos.idtransaccion=$idtt ";
}

if ($_REQUEST['idvalor'] != '') {
    $orden = '';
    $idorden = intval($_REQUEST['idvalor']);
    if ($idorden == 1) {
        $campo_orden = " monto_evento DESC ";
        $orden = " order by  $campo_orden ";
    }
    if ($idorden == 2) {
        $campo_orden = " cobrado_evento DESC ";
        $orden = " order by  $campo_orden ";
    }
    if ($idorden == 3) {
        $campo_orden = " saldo_evento DESC ";
        $orden = " order by  $campo_orden ";
    }
}
if (($_GET['idsucu']) > 0) {
    $idsuc = intval($_GET['idsucu']);
    $add .= " and pedidos_eventos.idsucursal = $idsuc";

}

/*-----------------------------*/
//contabilizamos si hay al meeenos un permiso ya usamos el filtro
$buscar = "Select count(*) as total from sucursales_permisos_panel where idusuario=$idusu";
$rpermisos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if (intval($rpermisos->fields['total'] > 0)) {
    $limitasucu = 1;

} else {
    $limitasucu = 0;

}
if ($limitasucu > 0) {
    $addlimita = " and pedidos_eventos.idsucursal in (select idsucursal from sucursales_permisos_panel where idusuario=$idusu) ";

} else {
    $addlimita = "";
}

///verificamos nuevamente el saldo al recargar la pagina
$consulta = "
select *
from tmp_detalles_eventos_factu 
where 
estado <> 6
";
$rsaallverificaevento = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rsaallverificaevento->EOF) {
    $parametros_array_evento['idevento'] = intval($rsaallverificaevento->fields['idevento']);
    //actualizamos nuevamente los saldos de los eventos en el archivo temporarl
    actualiza_saldo_evento($parametros_array_evento);
    $rsaallverificaevento->MoveNext();
}

/*-----------------------------*/
///verificamos si se agrego datos al carrito
$consulta = " 
select * from tmp_detalles_eventos_factu 
where estado = 1 and ualta = $idusu
";
$rscarritofactu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$seleccionado = $rscarritofactu->RecordCount();


//listado de pedidos

$buscar = "Select estado_pedido_int,cliente.razon_social,
cliente.fantasia,cliente.nombre,cliente.apellido,monto_pedido,
regid,idpresupuesto,evento_para,hora_entrega,dire_entrega,
ubicacion_comp as ubicacion,idtransaccion,monto_evento,nombre_evento,
(select email from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as email,
(select telefono from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as telefono,
(select usuario from usuarios where idusu=pedidos_eventos.ultimo_cambio_por
) as quiencambio,
(select mail from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as mailsucursal,
(select telefono from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as telefonosucursal,
ultimo_cambio,
(select razon_social 
        from cliente where idcliente=pedidos_eventos.id_cliente_solicita)
        as  solicitado_por,
    (select idcliente 
        from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as idclientereal,
    (select pasado from produccion_orden_new where idunico=regid limit 1) as pasado,
    pedidos_eventos.registrado_el,confirmado_pedido_el as confirmadoel,
    (select usuario 
    from usuarios where idusu=pedidos_eventos.registrado_por) as quien,
    pedidos_eventos.cobrado_evento, pedidos_eventos.saldo_evento
    from pedidos_eventos 
    inner join cliente on cliente.idcliente=pedidos_eventos.id_cliente_solicita
        where regid >0 $whereadd $addlimita
        $add 
    $orden limit 400";
$rga = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rga->RecordCount();
//$rga->MoveFirst();
//Eliminamos la tabla temporal donde se guardan los eventos a descontar el Stock a travez de la factura.
$consulta = "
delete from tmp_eventos_descuento_stock
where
idusu = $idusu
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

//////////////////////////////////////////////

while (!$rga->EOF) {
    $idevento = intval($rga->fields['regid']);
    $nombreevento = antisqlinyeccion($rga->fields['nombre_evento'], 'text');
    $consulta = " insert into tmp_eventos_descuento_stock(idevento,nombreevento,idusu)
values($idevento,$nombreevento,$idusu)";
    $rga2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $rga->MoveNext();
}

$rga->MoveFirst();


//echo $buscar;
/*
$url_des="cat_panel_pedidos_csv.php?desde=".

antixss($_REQUEST['desde']).
"&hasta=".antixss($_REQUEST['hasta'].
"&desdee=".antixss($_REQUEST['desdee']).

"&hastae=".antixss($_REQUEST['hastae']).
"&nombreape=".antixss($_REQUEST['nombreape']).
"&cliente_ped=".antixss($_REQUEST['cliente_ped']).
"&dc=".antixss($_REQUEST['dc']).
"&dcestado=".antixss($_REQUEST['dcestado']).
"&email=".antixss($_REQUEST['email']).
"&transa=".antixss($_REQUEST['transa']);

*/
$url_des = "cat_panel_pedidos_csv.php".parametros_url();

//echo $buscar;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>

    <script data-require="jquery@2.2.4" data-semver="2.2.4" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  </script>


  

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
                    <h2>Pedidos pendientes de descuento de Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content"> 
                  <div class="clearfix"></div>
<br />

    <div class="clearfix"></div>
<br />
                  <a href="cat_adm_pedidos_new.php<?php echo parametros_url()?> " class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                 <?php if (intval($_REQUEST['dcestadostock']) == 1) { ?>
                    <a onclick="descontar_stock();" id ="factsel"  class="btn btn-sm btn-danger"  ><span class="fa fa-trash"></span>&nbsp;&nbsp;Descuento global de Stock por eventos</a>
                    
                    <?php } ?>
                
                

        
                    <hr />
                    <div align="center" id="resultados">
                        <?php require_once("facturar_catering_car_tmp_factu_desdepedi.php");?>
                    </div>    
                    
            
            <?php
        $buscar = "Select * from visor_pedidos_parametros where idusu=$idusu";
$rl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcolu = $rl->RecordCount();
if ($tcolu > 0) {
    $c1 = 0;
    $c2 = 0;
    $c3 = 0;
    $c4 = 0;
    $c5 = 0;
    $c6 = 0;
    $c7 = 0;
    $c8 = 0;
    $c9 = 0;
    $c10 = 0;
    $c11 = 0;
    $c12 = 0;
    $c1 = intval($rl->fields['col_1']);
    $c2 = intval($rl->fields['col_2']);
    $c3 = intval($rl->fields['col_3']);
    $c4 = intval($rl->fields['col_4']);
    $c5 = intval($rl->fields['col_5']);
    $c6 = intval($rl->fields['col_6']);
    $c7 = intval($rl->fields['col_7']);
    $c8 = intval($rl->fields['col_8']);
    $c9 = intval($rl->fields['col_9']);
    $c10 = intval($rl->fields['col_10']);
    $c11 = intval($rl->fields['col_12']);
    $c12 = 1;
    $anticipo = trim($rl->fields['anticipo']);
    ?><?php if ($tpedidos > 0) {?>
            <div class="table-responsive" id="select_pedi">
                <table width="100%" class="table table-bordered jambo_table bulk_action" id="tablabb">
                <thead>
                <th class="column-title">Ver detalle</th>
                        <th class="column-title">ID Pedido / Cliente</th>
                        <?php if ($c2 > 0) { ?>
                        <th class="column-title">Fecha Evento </th>
                        <?php } ?>
                        <?php if ($c3 > 0) { ?>
                        <th class="column-title">Hora Evento </th>
                        <?php } ?>
                        <?php if ($c7 > 0) { ?>
                        <th class="column-title">Monto Pedido</th>
                        <?php } ?>
                        <?php if ($c8 > 0) { ?>
                        <th class="sorting" tabindex="0" aria-controls="datatable-checkbox" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" style="width: 95.2px;">
                        
                        Monto Abonado</th>
                        <?php } ?>
                        <?php if ($c9 > 0) { ?>
                        <th class="column-title" class="sortable">Saldo Activo</th>
                        <?php } ?>
                        <?php if ($c11 > 0) { ?>
                        <th class="column-title">Fecha Carga</th>
                        <?php } ?>
                        <?php if ($c12 > 0) { ?>
                        <th class="column-title">Registrado por</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
            $sub_total_pedido = 0;
        $sub_total_saldo = 0;
        $sub_total_abonado = 0;
        $existenpedidos = 0;
        while (!$rga->EOF) {
            $existenpedidos++;
            //verificamos si el pedido posee al menos una orden para marcarla en verde y si su estado primario no es modificado
            $idreg = intval($rga->fields['regid']);
            $idtransaccion = intval($rga->fields['idtransaccion']);
            /*------------CAMBIO DE FORMAS Y COLORES DEL PEDIDO SEGUN ESTADO----------------------*/
            $qca = trim($rga->fields['quiencambio']);
            $pasado = 0;
            if ($rga->fields['pasado'] == '' or $rga->fields['pasado'] == 'N') {
                $color = "#F23131";

            } else {
                //ya existe
                $color = "green";
                $pasado = 1;
            }
            if ($qca != '' && $pasado == 1) {
                $colortr = "";
            } else {
                $colortr = "";
            }
            if ($pasado == 1 && $rga->fields['quiencambio'] != '') {
                $colortr = "yellow";
            }
            $pasado = trim($rga->fields['pasado']);
            $nombre_evento = trim($rga->fields['nombre_evento']);
            //Verificamos los estados del pedido
            if ($rga->fields['estado_pedido_int'] == 1) {
                $color = "black";
                $colortr = "white";
                $pedidochar = "Presupuesto";
            }
            if ($rga->fields['estado_pedido_int'] == 3) {
                $color = "green";
                $colortr = "white";
                $pedidochar = "Confirmado";
            }
            if ($rga->fields['estado_pedido_int'] == 4) {
                $color = "green";
                $colortr = "yellow";
                $pedidochar = "Produccion";
            }
            $anticipo = 'S';

            //recalculamos
            $update = "
                    update pedidos_eventos_detalles set subtotal=(precio_venta*cantidad) where subtotal=0 and cantidad > 0 and precio_venta >0 
                    and idtransaccion=$idtransaccion
                    ";
            //$conexion->Execute($update) or die(errorpg($conexion,$update));

            $update = "
                    update pedidos_eventos  set monto_evento=monto_pedido,monto_sin_descuento=monto_pedido where monto_evento<monto_pedido  
                    and idtransaccion=$idtransaccion
                    ";
            //$conexion->Execute($update) or die(errorpg($conexion,$update));


            //recalculamos el saldo_evento
            $update = "
                    update pedidos_eventos  set saldo_evento=(monto_pedido-cobrado_evento) where idtransaccion=$idtransaccion";
            //$conexion->Execute($update) or die(errorpg($conexion,$update));
            //

            //Traemos los valores recalculados si existieren;
            $buscar = "Select * from pedidos_eventos where idtransaccion=$idtransaccion";
            $rsm1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $monto_pedido_actual = floatval($rsm1->fields['monto_pedido']);//Monto del pedido
            $totalentregas = floatval($rsm1->fields['cobrado_evento']);//Cobrado del pedido
            $saldo_evento = floatval($rsm1->fields['saldo_evento']);//Saldo del pedido
            $descontado = floatval($rsm1->fields['descuento_neto']);
            $monto_pedido_actual = $monto_pedido_actual - $descontado;

            $sub_total_pedido = $sub_total_pedido + $monto_pedido_actual;
            $sub_total_saldo = $sub_total_saldo + $saldo_evento;
            $sub_total_abonado = $sub_total_abonado + $totalentregas;


            ?>
                    <td>
                <div class="btn-group"> 
                                    <a onclick="busca_productos_eventos(<?php echo $rga->fields['regid'];?>,'<?php echo $rga->fields['nombre_evento'];?>');" class="btn btn-sm btn-default" title="Ver Detalles" data-toggle="tooltip" data-placement="right"  data-original-title="Ver Detalles"><span class="fa fa-search"></span></a>
                </div>
                </td>

                    <td class="column-title"><?php echo $rga->fields['nombre_evento']?></td>
                    <?php if ($c2 > 0) { ?>
                    <td class="column-title">
                        <?php echo date("d/m/Y", strtotime($rga->fields['evento_para'])); ?>
                            <input type="hidden" name="fechaseg_<?php echo $rga->fields['regid']?>"
                            id="fechaseg_<?php echo $rga->fields['regid']?>" value="<?php echo $rga->fields['evento_para'] ?>" /></td>
                    <?php } ?>
                    <?php if ($c3 > 0) { ?>
                    <td class="column-title"><?php echo $rga->fields['hora_entrega']; ?></td>
                    <?php } ?>
                    
                    <?php if ($c7 > 0) { ?>
                    <td class="column-title" align="right"><?php echo formatomoneda($monto_pedido_actual, 4, 'N'); ?></td>
                    <?php } ?>
                    <?php if ($c8 > 0) { ?>
                    <td class="column-title"><?php echo formatomoneda($totalentregas, 4, 'N'); ?></td>
                    <?php } ?>
                    <?php if ($c9 > 0) { ?>
                    <td class="column-title"><?php echo formatomoneda($saldo_evento, 4, 'N'); ?></td>
                    <?php } ?>
                    <?php if ($c11 > 0) { ?>
                    <td class="column-title"><?php echo date("d/m/Y H:i:s", strtotime($rga->fields['registrado_el'])); ?></td>
                    <?php } ?>
                    <?php if ($c12 > 0) { ?>
                    <td class="column-title"><?php echo $rga->fields['quien']; ?></td>
                    <?php } ?>
                  </tr>
                <?php $rga->MoveNext();
        }?>
                <?php if ($c7 > 0) { ?>
                <tr>
                    <td colspan="14" align="center">
                        <table style="border: 1px;width: 300px;">
                            <tr>
                                <td style="color:black;">Monto Evento</td>
                                <td style="color:black;"><?php echo formatomoneda($sub_total_pedido); ?></td>
                            </tr>
                            <tr>
                                <td style="color:black;">Abonado</td>
                                <td style="color:black;"> <?php echo formatomoneda($sub_total_abonado); ?></td>
                            </tr>
                            <tr>
                                <td style="color:black;">Saldo Evento</td>
                                <td style="color:red;"><strong><?php echo formatomoneda($sub_total_saldo); ?></strong></td>
                            </tr>
                        </table>
                    </td>
                
                
                </tr>
                                
                <?php } ?>    
                </tbody>
                </table>
                </div>
                <?php } else {
                    $existenpedidos = 0;?>
                <span class="fa fa-warning"></span>&nbsp;&nbsp;<h2>No existen pedidos confirmados.</h2>
                <?php }?>
            <?php } else { ?>
            <span class="fa fa-warning"></span>&nbsp;&nbsp;El usuario no posee permisos para ver columnas.
            <?php } ?>
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
              <!--
            <div class="modal fade" id="acciones"  role="dialog" aria-labelledby="titulo" aria-hidden="true" >
            <div class="modal-dialog modal-dialog-centered" role="document" >
              <div class="modal-content" style="width: 800px;">

                <!-- Modal Header 
                <div class="modal-header">
                    
                    <span  id="variadotit" style="font-weight:bold;"></span>
                   <button type="button" class="close" data-dismiss="modal"></button>
                </div>

                <!-- Modal body 
                <div class="modal-body" id="acciones_cuerpo" style="min-height:250px; overflow-y: scroll">
                  
                  
                </div>
                    
                <!-- Modal footer 
                <div class="modal-footer">
            
Monto  descuento            6.293.000
                
                </div>

              </div>
            </div>
          </div>--> 
          
          
           <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">

                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                  </button>
                  <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                </div>
                <div class="modal-body" id="modal_cuerpo">
                ...
                </div>
                <div class="modal-footer"  id="modal_pie">
                  
                  <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
                 
                </div>

              </div>
            </div>
          </div> 
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
      <script>


function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function redirect(){
 window.location = 'cat_adm_pedidos_new.php<?php echo parametros_url()?>';
 }
function busca_productos_eventos(idevento,nombreevento){
    
            var direccionurl='facturar_catering/carrito_ped_detalla_factall.php';
                var parametros = {
                  "idevento" : idevento,
                  "nombreevento" : nombreevento
                };
                $.ajax({          
                    data:  parametros,
                    url:   direccionurl,
                    type:  'post',
                    beforeSend: function () {
                        $('#modal_ventana').modal('show');
                        $("#modal_titulo").html('Detalles del Evento '+nombreevento);
                        $("#modal_cuerpo").html('Cargando...');                
                    },
                    success:  function (response) {
                        $("#modal_cuerpo").html(response);
                    }
                });
        }
        function descontar_stock(){
            var urlparametros= '<?php  echo parametros_url();?>';
            var direccionurl='cat_adm_pedidos_descuento_stock.php'+urlparametros;
            



            //alert(direccionurl);
                var parametros = {
                  "dcestadostock" : 1
                };
                $.ajax({          
                    data:  parametros,
                    url:   direccionurl,
                    type:  'REQUEST',
                    beforeSend: function () {
                        $("#modal_titulo").html("Atencion");
                        $("#modal_cuerpo").html("Procesando..");      
                        $("#modal_ventana").modal("show");    
                    },
                    success:  function (response) {
                        if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        $("#modal_titulo").html("Atencion");
                        $("#modal_cuerpo").html("Todos los Productos fueron descontados exitosamente!");      
                        $("#modal_ventana").modal("show");    
                        redirect();
                    }else{
                        $("#modal_titulo").html("Atencion");
                        $("#modal_cuerpo").html(nl2br(obj.errores));      
                        $("#modal_ventana").modal("show");    
                    }
                }else{
                    alert(response);    
                }
                    }

                });
        }

      </script>
<?php require_once("includes/footer_gen.php"); ?>
      <script>
    <?php if (intval($_REQUEST['idr']) > 0) { ?>       
      $(document).ready(function() {
                       
                        mostrarpanel(<?php echo antixss($_REQUEST['idr']) ?>);
                    
            }); 
          <?php }?>//
         
      </script>
  </body>
</html>
