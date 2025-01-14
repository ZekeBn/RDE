 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");

require_once("includes/funciones_carrito.php");

$idmesa = intval($_POST['idmesa']);

$img = "images/media_rest.jpg";

$consulta = "
update tmp_ventares_cab 
set 
estado = 6 
where 
tmp_ventares_cab.idtmpventares_cab not in (
select idtmpventares_cab 
from tmp_ventares
where 
idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
and idtmpventares_cab is not null
)
and tmp_ventares_cab.idtmpventares_cab not in (
select idtmpventares_cab 
from tmp_ventares_bak
where 
idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
and idtmpventares_cab is not null
)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
update tmp_ventares_cab 
set 
monto = (
            COALESCE
            (
                (
                    select sum(subtotal) as total_monto
                    from tmp_ventares
                    where
                    tmp_ventares.idempresa = tmp_ventares_cab.idempresa
                    and tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
                    and tmp_ventares.borrado = 'N'
                    and tmp_ventares.borrado_mozo = 'N'
                    and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                )
            ,0)
            
        )
WHERE
idmesa = $idmesa
and idventa is null
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select * 
from tmp_ventares_cab 
inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa
where 
tmp_ventares_cab.idempresa = $idempresa
and tmp_ventares_cab.registrado = 'N' 
and tmp_ventares_cab.idmesa > 0 
and tmp_ventares_cab.estado <> 6
and tmp_ventares_cab.idmesa = $idmesa
order by tmp_ventares_cab.idtmpventares_cab asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rs->fields['idmesa']);
if ($idmesa == 0) {
    echo "Mesa inexistente o cerrada.";
    exit;
}
if ($idmesa > 0) {
    $consulta = "
        select *, (select numero_mesa from mesas where mesas.idmesa = tmp_ventares_cab.idmesa) as numero_mesa,
        (select usuario from usuarios where idusu = tmp_ventares_cab.idusu) as operador
        from tmp_ventares_cab
        where
        tmp_ventares_cab.idsucursal = $idsucursal
        and tmp_ventares_cab.idempresa = $idempresa
        and tmp_ventares_cab.finalizado = 'S'
        and tmp_ventares_cab.registrado = 'N'
        and tmp_ventares_cab.estado = 1
        and tmp_ventares_cab.idmesa=$idmesa
        order by tmp_ventares_cab.fechahora asc
        ";
    //echo $consulta;
    //exit;
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tcuerpo = $rsmesa->RecordCount();
    $numero_mesa = $rsmesa->fields['numero_mesa'];


    $consulta = "
        select sum(monto) as total_cuenta 
        from tmp_ventares_cab 
        inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa
        where 
        tmp_ventares_cab.registrado = 'N' 
        and tmp_ventares_cab.idmesa > 0 
        and tmp_ventares_cab.estado <> 6
        and tmp_ventares_cab.idmesa = $idmesa
        order by tmp_ventares_cab.idtmpventares_cab asc
        ";
    //echo $consulta;
    $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_cuenta = $rstot->fields['total_cuenta'];
}


?>
                        
                        
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Detalle de la Mesa <?php echo $rs->fields['numero_mesa']; ?></h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
 <strong>Total Cuenta:</strong>  <?php echo formatomoneda($total_cuenta, 0, "N"); ?>                     
<br /><br />
<button type="button" class="btn btn-default" id="preticket_imp" onclick="reimprimir_mesa(<?php echo $idmesa ?>);" ><span class="fa fa-print"></span> Imprimir Preticket</button>
 <a href="#" class="btn btn-sm btn-default" onclick="detallar(<?php echo $idmesa ?>);"><span class="fa fa-search"></span> Vista Consolidada</a>
 <a href="#" class="btn btn-sm btn-primary" onclick="detallar_det(<?php echo $idmesa ?>);"><span class="fa fa-search"></span> Vista Detallada</a>
<hr />
<!--<a href="impresor_ticket_mesa.php?idmesa=<?php echo $idmesa; ?>" target="_blank">IMPRIMIR</a><br />-->
  <?php while (!$rsmesa->EOF) {?>

<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
  <tr>
    <th align="center"  ><strong>Pedido</strong></th>
    <th align="center"><strong>Fecha/Hora</strong></th>
    <th align="center" ><strong>Operador</strong></th>
    
    </tr>
    </thead>
    <tbody>
  <tr align="center">
    <td height="30" style="font-size:16px;"><?php echo $rsmesa->fields['idtmpventares_cab']; ?></td>
    <td><?php echo date("d/m/Y H:i:s", strtotime($rsmesa->fields['fechahora'])); ?></td>
    <td><?php echo $rsmesa->fields['operador']; ?></td>
    </tr>
  <tr>
    <td colspan="3">
<?php
$idtmpventares_cab = $rsmesa->fields['idtmpventares_cab'];
      $parametros_array = ["estado_pedido" => 'R', "idpedido" => $idtmpventares_cab];
      $carrito_detalles = carrito_muestra($parametros_array);


      ?>  
   
<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action" border="1">
    <thead>
        <tr>
            <th  >Producto</th>
            <th  >Cantidad</th>
            <th  >P.U.</th>
            <th  >Subtotal</th>


        </tr>
    </thead>
    <tbody>
<?php foreach ($carrito_detalles as $carrito_detalle) {

    if ($carrito_detalle['idtipoproducto'] != 5) {
        ?>
        <tr>
            <td align="left"><?php echo  $carrito_detalle['descripcion'];
        if (trim($carrito_detalle['observacion']) != '') {
            echo  "<br />&nbsp;&nbsp;( ! ) OBS: ".$carrito_detalle['observacion'];
        }
        //print_r($carrito_detalle['agregados']);


        // combinado extendido
        $combinados = $carrito_detalle['combinado'];
        $ic = 1;
        foreach ($combinados as $combinado) {
            echo "<br />&nbsp;&nbsp;> Parte $ic: ".Capitalizar($combinado['descripcion']);
            $ic++;
        }
        // combo
        $combos = $carrito_detalle['combo'];
        $ic = 1;
        foreach ($combos as $combo) {
            echo "<br />&nbsp;&nbsp;> ".formatomoneda($combo['cantidad'])." x ".Capitalizar($combo['descripcion']);
            $ic++;
        }
        // combinado viejo
        $combinado_vs = $carrito_detalle['combinado_v'];
        $ic = 1;
        foreach ($combinado_vs as $combinado_v) {
            echo "<br />&nbsp;&nbsp;> Parte $ic: ".Capitalizar($combinado_v['descripcion']);
            $ic++;
        }



        // agregados
        $carrito_agregados = $carrito_detalle['agregados'];
        $iag = 1;
        foreach ($carrito_agregados as $carrito_agregado) {
            echo "<br />&nbsp;&nbsp;&nbsp;(+) ".trim($carrito_agregado['alias'], 36)."<br />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Gs. ".formatomoneda($carrito_agregado['precio_adicional'])."";
            $iag++;
        }
        // sacados
        $carrito_sacados = $carrito_detalle['sacados'];
        $iag = 1;
        foreach ($carrito_sacados as $carrito_sacado) {
            echo "<br />&nbsp;&nbsp;&nbsp;(-) SIN ".trim($carrito_sacado['alias'], 36)."";
            $iag++;
        }



        $totalacum += $carrito_detalle['subtotal_con_extras'];

        $estilo_entrada = "";
        $estilo_fondo = "";
        if ($carrito_detalle['tipo_plato'] == 'E') {
            $estilo_entrada = ' style="background-color:#82E9FF;" ';
        }
        if ($carrito_detalle['tipo_plato'] == 'F') {
            $estilo_fondo = ' style="background-color:#82E9FF;" ';
        }


        if ($carrito_detalle['idventatmp'] > 0) {
            $tipo_borra = 'onClick="borrar_item('.$carrito_detalle['idventatmp'].','.$carrito_detalle['idproducto'].',\''.Capitalizar(str_replace("'", "", $carrito_detalle['descripcion'])).'\');"';
            $tipo_personaliza = 'editareceta.php?idvt='.$carrito_detalle['idventatmp'];
            $accion_entrada = 'onclick="marcarplato_item('.$carrito_detalle['idventatmp'].',\'E\');"';
            $accion_fondo = 'onclick="marcarplato_item('.$carrito_detalle['idventatmp'].',\'F\');"';
        } else {
            $tipo_borra = 'onClick="borrar('.$carrito_detalle['idproducto'].',\''.Capitalizar(str_replace("'", "", $des)).'\');"';
            $tipo_personaliza = 'editareceta.php?id='.$carrito_detalle['idproducto'];
            $accion_entrada = 'onclick="marcarplato('.$carrito_detalle['idproducto'].',\'E\');"';
            $accion_fondo = 'onclick="marcarplato('.$carrito_detalle['idproducto'].',\'F\');"';
        }


        ?></td>
            <td align="center"><?php   if ($carrito_detalle['idmedida'] != 4 && $carrito_detalle['idtipoproducto'] == 1) {?><a href="cantidad_cambia.php?id=<?php echo $carrito_detalle['idproducto']; ?>" title="Editar Cantidad"><?php } ?><?php echo  formatomoneda($carrito_detalle['cantidad'], 4, 'N'); ?><?php  if ($carrito_detalle['idmedida'] != 4 && $carrito_detalle['idtipoproducto'] == 1) {?></a><?php } ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['precio_unitario_con_extras'], 2, 'N'); ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['subtotal_con_extras'], 2, 'N'); ?></td>


        </tr>
<?php } // if($carrito_detalle['idtipoproducto'] != 5){?>
<?php } ?>
    <tr>
      <td height="50" colspan="5"><strong>Total: <?php echo formatomoneda($totalacum, 0); ?></strong></td>
    </tr>
    </tbody>
</table>
</div>
<br />
        
    </td>
    </tr>
     </tbody>
</table>
</div>



  <?php $rsmesa->MoveNext();
  }?>




</div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>
