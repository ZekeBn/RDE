 <?php
/*----------------------------------------
11/07/2024: Se incorpora uso de tarjeta delivery



--------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

/* para borrar todo
update tmp_ventares_cab set estado = 6 where tmp_ventares_cab.estado <> 6 and (tmp_ventares_cab.idcanal = 1 or tmp_ventares_cab.idcanal = 3 or tmp_ventares_cab.idcanal = 2) and tmp_ventares_cab.finalizado = 'S' and tmp_ventares_cab.registrado = 'N'
*/
$consulta = "SELECT usa_tarjetadelivery,muestra_resumen_cobros,muestra_tarjetas_rendir,max_items_resumen
FROM preferencias_caja limit 1 ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$usa_tarjeta_delivery = trim($rsprefcaj->fields['usa_tarjetadelivery']);
$muestra_tarjetas_rendir = trim($rsprefcaj->fields['muestra_tarjetas_rendir']);
$muestra_resumen_cobros = trim($rsprefcaj->fields['muestra_resumen_cobros']);
$max_items_resumen = intval($rsprefcaj->fields['max_items_resumen']);
if ($max_items_resumen > 0) {
    $limite = " limit $max_items_resumen";

}
$idpedido = intval($_REQUEST['idpedido']);

$consulta = "
select transfer_franq, transfer_suc from preferencias_caja limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
$mostrar_enlace = 'S';
if ($idcaja_compartida > 0) {
    $mostrar_enlace = 'N';
}




//print_r($_POST);
$idcanal = intval($_POST['idcanal']);
$idsucursal_post = substr(trim($_POST['idsucursal']), 0, 5);
//Preferencias de mesa
$consulta = "
select rendir_mesa
from mesas_preferencias
";
$rsprefmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$rendir_mesa = $rsprefmesa->fields['rendir_mesa'];

if ($idsucursal_post > 0) {
    $whereadd .= " and tmp_ventares_cab.idsucursal = $idsucursal_post ";
} else {
    $whereadd .= " and tmp_ventares_cab.idsucursal = $idsucursal ";
}
if ($idsucursal_post == 'T') {
    $whereadd = "";
}

if ($idcanal > 0) {
    $whereadd .= " and canal.idcanal = $idcanal ";
}

if ($idpedido > 0) {
    $whereadd .= " and tmp_ventares_cab.idtmpventares_cab = $idpedido ";
}
/*--------------------------RENDICION DE TARJETAS------------------------------*/
$tipo = intval($_POST['tipo']);
$idunico = intval($_POST['rendir']);

if ($tipo == 2 && $idunico > 0) {
    //echo "entra";exit;
    $buscar = "select idtarjetadelivery from tarjeta_delivery_movimientos where idunico=$idunico";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtarjeta = intval($rsbb->fields['idtarjetadelivery']);
    if ($idtarjeta > 0) {
        $update = "Update tarjeta_delivery_movimientos set rendido_el='$ahora',rendido_cajero=$idusu,estado=3 where idunico=$idunico";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        $update = "Update tarjeta_delivery set idestadotarjetadel=1 where idtarjetadelivery=$idtarjeta";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }

}


/*-----------------------------------------------------------------------*/

$consulta = "
    select tmp_ventares_cab.*, canal.canal,
    (select numero_tarjeta from tarjeta_delivery where idtarjetadelivery=tmp_ventares_cab.idtarjetadelivery) as numero_tar_delivery,
    (select nombre from sucursales where idsucu = tmp_ventares_cab.idsucursal ) as sucursal,
    (select usuario from usuarios where idusu = tmp_ventares_cab.idusu) as registrado_por,
    (
    Select cliente_delivery_dom.referencia
    from cliente_delivery_dom
    where  
    cliente_delivery_dom.iddomicilio=tmp_ventares_cab.iddomicilio
    limit 1    
    ) as referencia
    from tmp_ventares_cab
    inner join canal on canal.idcanal =  tmp_ventares_cab.idcanal
    where 
    tmp_ventares_cab.estado <> 6
    and (tmp_ventares_cab.idcanal = 1 or tmp_ventares_cab.idcanal = 3 or tmp_ventares_cab.idcanal = 2) 
    and tmp_ventares_cab.finalizado = 'S'
    and tmp_ventares_cab.registrado = 'N'
    $whereadd 
    order by tmp_ventares_cab.fechahora asc
    ";
///echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$nofificado = "";


?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <?php if ($usa_tarjeta_delivery == 'S') {?>
                <th>Tarjeta Delivery</th>
            <?php } ?>
            <th align="center">Pedido N#</th>
            <th align="center">Fechahora</th>
            <th align="center">Canal</th>
            <th align="center">Razon social</th>
            <th align="center">Ruc</th>
            <th align="center">Cliente Carry Out/Delivery</th>
            <th align="center">Observacion</th>
            <th align="center">Monto</th>

            <th align="center">Sucursal</th>


            <th align="center">Registrado Por</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {
    $idtarjeta_delivery = intval($rs->fields['numero_tar_delivery']);
    $dibu_canal = "";
    $canal_txt = $rs->fields['canal'];
    if ($rs->fields['idcanal'] == 1) {
        $dibu_canal = '<span class="btn btn-app" ><i class="fa fa-car"></i> '.$canal_txt.'</span>';
        $canal_txt = "";
    }
    if ($rs->fields['idcanal'] == 3) {
        $dibu_canal = '<span class="btn btn-app" ><i class="fa fa-motorcycle"></i> '.$canal_txt.'</span>';
        $canal_txt = "";
    }
    if ($rs->fields['idcanal'] == 2) {
        $dibu_canal = '<span class="btn btn-app" ><i class="fa fa-university"></i> '.$canal_txt.'</span>';
        $canal_txt = "";
    }
    if ($rs->fields['notificado'] == 'N') {
        $nofificado = $rs->fields['notificado'];
    }
    ?>
        <tr <?php if ($rs->fields['notificado'] == 'N') {?> style=" background-color:#FFC;" <?php } ?>>
                
            <td>
                
                <div class="btn-group">
                    <a href="javascript:cobrar_pedido(<?php echo $rs->fields['idtmpventares_cab']; ?>);" class="btn btn-sm btn-default" title="Cobrar" data-toggle="tooltip" data-placement="right"  data-original-title="Cobrar"><span class="fa fa-money"></span></a>
                    <a href="javascript:detalle_pedido(<?php echo $rs->fields['idtmpventares_cab']; ?>);" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="tmp_ventares_cab_del.php?id=<?php echo $rs->fields['idtmpventares_cab']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php if ($rsprefcaj->fields['transfer_suc'] == 'S') { ?>
                    <a href="javascript:transfer_suc(<?php echo $rs->fields['idtmpventares_cab']; ?>)" class="btn btn-sm btn-default" title="Trasferir a Sucursal" data-toggle="tooltip" data-placement="right"  data-original-title="Trasferir a Sucursal"><span class="fa fa-paper-plane"></span></a>
                    <?php } ?>
                    <?php if ($rsprefcaj->fields['transfer_franq'] == 'S') { ?>
                    <a href="javascript:transfer_fran(<?php echo $rs->fields['idtmpventares_cab']; ?>)" class="btn btn-sm btn-default" title="Trasferir a Franquicia" data-toggle="tooltip" data-placement="right"  data-original-title="Trasferir a Franquicia"><span class="fa fa-paper-plane"></span></a> 
                    <?php } ?>
                </div>

            </td>
            <?php if ($usa_tarjeta_delivery == 'S') { ?>
                <td style="background-color:yellow;color:red;" align="center"><?php echo $idtarjeta_delivery; ?></td>
            <?php } ?>
            <td align="center"><?php echo intval($rs->fields['idtmpventares_cab']); ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="center"><?php echo $dibu_canal ?><?php echo antixss($canal_txt); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>

            <td align="center"><?php
            $idcanal = $rs->fields['idcanal'];
    if ($idcanal == 1) { // carry out
        echo antixss($rs->fields['chapa']);

    } elseif ($idcanal == 3) { // delivery
        echo antixss($rs->fields['nombre_deliv']).' '.antixss($rs->fields['apellido_deliv']);
    } else {
        echo antixss($rs->fields['chapa']);
    }
    ?></td>


            <td align="center"><?php echo antixss($rs->fields['observacion']); ?> <?php echo antixss($rs->fields['observacion_delivery']); ?> <?php echo antixss($rs->fields['referencia']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['monto']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>


            



        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<hr />
<?php if ($muestra_resumen_cobros == 'S') {
    //Sacr limitacion de canal en el futuro por preferencias o array de canales permitidos
    $buscar = "
    Select ventas.idcanal,canal.canal as canaldes,idventa,total_venta,factura,razon_social,idatc,
    (CASE when ventas.idcanal=4 then 
        (select idventa from ventas_rendido where ventas_rendido.idventa = ventas.idventa and estado = 1) 
    END) as rendido 
     from ventas inner join canal on canal.idcanal = ventas.idcanal
     where ventas.idcaja = $idcaja and ventas.estado <> 6
     and ventas.sucursal = $idsucursal 
     and ventas.idcanal =4 and ventas.idventa NOT IN (select idventa from ventas_rendido where ventas_rendido.idventa = ventas.idventa and estado = 1) 
     order by 
     idventa DESC,rendido ASC $limite
 ";



    $rscob = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    ?>
<div class="col-md-6">
<h2><span class="fa fa-money"></span>&nbsp;Cobranzas en mesa por rendir</h2>
    <div class="table table-bordered">
        <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
            <tr>
                <th align="center">Canal</th>
                <th align="center">Id venta</th>
                <th align="center">Factura</th>
                <th align="center">Cliente</th>
                <th align="center">Monto</th>
            </tr>
          </thead>
          <tbody>
          <?php
    $tglobal = 0;
    while (!$rscob->EOF) {
        $enlace = "";
        $idventa = intval($rscob->fields['idventa']);
        $idcanal = intval($rscob->fields['idcanal']);
        $rendido = intval($rscob->fields['rendido']);
        if ($idcanal == 4 && $rendido == 0) {

            $enlace = "<a class='btn btn-warning' href='ventas_rendido_rendir.php?id=$idventa&rend=s' target='_blank'>$idventa</a>";
        }
        $tglobal = $tglobal + floatval($rscob->fields['total_venta']);
        ?>
            <tr>
                <td align="center"><?php echo antixss($rscob->fields['canaldes']); ?></td>
                <td align="center"><?php echo $enlace; ?></td>
                <td align="center"><?php echo($rscob->fields['factura']); ?></td>
                <td align="center"><?php echo($rscob->fields['razon_social']); ?></td>
                <td align="center"><?php echo formatomoneda($rscob->fields['total_venta']); ?></td>
        
            </tr>
          <?php $rscob->MoveNext();
    } //$rs->MoveFirst();?>
            <tr>
            <td colspan="5" align="center" style="font-size:14px;font-weight:bold;">Total pendiente rendici&oacute;n: &nbsp; <?php echo formatomoneda($tglobal);?></td>
          </tr>
          </tbody>
        </table>
    </div>

</div>
<?php } ?>
<?php if ($muestra_tarjetas_rendir == 'S') {
    $buscar = "
Select idunico,numero_tarjeta,tarjeta_delivery.idtarjetadelivery,idventa,idpedido,
(select razon_social from ventas where idventa=tarjeta_delivery_movimientos.idventa) as razon_social, 
(select factura from ventas where idventa=tarjeta_delivery_movimientos.idventa) as factura,
(select total_venta from ventas where idventa=tarjeta_delivery_movimientos.idventa) as total_venta
from tarjeta_delivery 
inner join tarjeta_delivery_movimientos on tarjeta_delivery_movimientos.idtarjetadelivery=tarjeta_delivery.idtarjetadelivery 
where rendido_cajero IS NULL and tarjeta_delivery.idestadotarjetadel=3
and idventa IN(
 Select ventas.idventa from ventas where ventas.idcaja = $idcaja and ventas.estado <> 6  and ventas.sucursal = $idsucursal 
)
order by numero_tarjeta ASC;


 ";
    $rstarjetas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    ?>
<div class="col-md-6">
<h2><span class="fa fa-folder-open"></span>&nbsp;Tarjetas delivery pendientes de rendicion</h2>
<div class="table table-striped">
        <table width="100%" class="table table-bordered jambo_table bulk_action">
            <thead>
            <tr>
                <th align="center">Tarjeta Numero</th>
                <th align="center">Id venta</th>
                <th align="center">Factura</th>
                <th align="center">Cliente</th>
                <th align="center">Monto</th>
            </tr>
          </thead>
          <tbody>
          <?php
                  $tglobal = 0;
    while (!$rstarjetas->EOF) {
        $tglobal = $tglobal + floatval($rstarjetas->fields['total_venta']);


        ?>
            <tr>
                <td align="center"><a href="javascript:void(0);" onclick="rendido(<?php echo antixss($rstarjetas->fields['idunico']); ?>,2);" class="btn btn-warning"><?php echo antixss($rstarjetas->fields['numero_tarjeta']); ?></a></td>
                <td align="center"><?php echo formatomoneda($rstarjetas->fields['idventa']); ?></td>
                <td align="center"><?php echo($rstarjetas->fields['factura']); ?></td>
                <td align="center"><?php echo($rstarjetas->fields['razon_social']); ?></td>
                <td align="center"><?php echo formatomoneda($rstarjetas->fields['total_venta']); ?></td>
        
            </tr>
          <?php $rstarjetas->MoveNext();
    } //$rs->MoveFirst();?>
          <tr>
            <td colspan="5" align="center" style="font-size:14px;font-weight:bold;">Total pendiente rendici&oacute;n: &nbsp; <?php echo formatomoneda($tglobal);?></td>
          </tr>
          </tbody>
        </table>
    </div>


</div>
<?php } ?>
<input name="notificado" id="notificado" type="hidden" value="<?php echo $nofificado;  ?>" />
