 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


//Eliminar
$chau = intval($_POST['chau']);
if ($chau > 0) {
    $update = "Update tmp_ventares_cab set estado=6,anulado_el='$ahora',anulado_por=$idusu where idtmpventares_cab=$chau and idempresa = $idempresa and idsucursal = $idsucursal"    ;
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}


$buscar = "
Select * 
from tmp_ventares_cab 
where 
finalizado='S' 
and registrado='N' 
and estado=1 
and (idmesa=0 or idmesa is null) 
and idcanal = 3  
and idmesa_tmp is null 
and idempresa = $idempresa 
and idsucursal = $idsucursal
 order by fechahora asc";
$rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rspedicu->RecordCount();
$iddomicilio = intval($rspedicu->fields['iddomicilio']);


$iddomicilio = intval($_COOKIE['dom_deliv']);
// datos delivery
$consulta = "
select *, 
(select ruc from cliente where idcliente = cliente_delivery.idcliente) as ruc,
(select razon_social from cliente where idcliente = cliente_delivery.idcliente) as razon_social
from cliente_delivery
inner join cliente_delivery_dom on cliente_delivery.idclientedel = cliente_delivery_dom.idclientedel
where
iddomicilio = $iddomicilio
";
$rsdel = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$telefono = antisqlinyeccion($rsdel->fields['telefono'], "int");

$consulta = "
SELECT razon_social, ruc FROM cliente where borrable = 'N' and estado = 1 order by idcliente asc limit 1
";
$rsclipred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = $rsclipred->fields['razon_social'];
$ruc_pred = $rsclipred->fields['ruc'];

?><div align="center" style="height:450px;width:750px; background-image: url(img/tablet750x450.png); color:#FFFFFF;">
<div style="height:30px;"></div>
<div style="margin-left:42px; margin-right:52px; min-height:400px;">
    <div style="float:left; width:40%;">
    <strong>Guardar Pedido de Delivery</strong>
    <br /> <br />
<table width="99%" border="0">
      <tbody>
        <tr>
          <td width="77">RUC</td>
          <td><input type="text" name="ruc_carry" style="text-transform: uppercase; width:99%;" required id="ruc_carry" placeholder="RUC" value="<?php  echo $rsdel->fields['ruc'];  ?>"  ></td>
        </tr>
        <tr>
          <td>Razon Social</td>
          <td><input name="razon_social_carry" type="text" required id="razon_social_carry" placeholder="Razon Social" style="text-transform: uppercase; width:99%;" value="<?php  echo $rsdel->fields['razon_social'];  ?>"  ></td>
        </tr>

        <tr>
          <td>Sucursal</td>
          <td>
          <?php // consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idsucu_deliv'])) {
    $value_selected = htmlentities($_POST['idsucu_deliv']);
} else {
    //$value_selected=htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu_deliv',
    'id_campo' => 'idsucu_deliv',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);?>
          </td>
        </tr>
        <tr>
          <td>Llevar POS</td>
          <td>
<?php

// valor seleccionado
if (isset($_POST['llevapos_deliv'])) {
    $value_selected = htmlentities($_POST['llevapos_deliv']);
} else {
    $value_selected = '';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'llevapos_deliv',
    'id_campo' => 'llevapos_deliv',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);?>
          </td>
        </tr>
        <tr>
          <td>Cambio de:</td>
          <td><input name="cambio_deliv" type="text" required id="cambio_deliv" placeholder="Cambio" style="text-transform: uppercase; width:99%;" value=""  ></td>
        </tr>
        

        <tr>
          <td>Observacion</td>
          <td><textarea name="observacion_carry" cols="40" rows="3" id="observacion_carry" placeholder="Observacion" style="width:99%;" ></textarea></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td colspan="2" align="center" id="regpedidobox"><input type="button" name="regpedido" id="regpedido" value="Guardar" onMouseUp="registrar_pedido(3);"></td>
          </tr>
      </tbody>
    </table>
<div id="reimprimebox"></div>
    </div>
    <div style="float:left; width:60%;">
    <strong>Deliverys no facturados</strong>
    <br /> <br />
        <div style="overflow:auto;  height:368px; border:1px auto;">
        <table width="99%" border="0" style="color:#000000;">
          <tbody>
            <tr>
              <td align="center" bgcolor="#F8FFCC"><strong>N&ordm;</strong></td>
              <td align="center" bgcolor="#F8FFCC"><strong>Nombre</strong></td>
              <td align="center" bgcolor="#F8FFCC"><strong>Monto</strong></td>
              <td align="center" bgcolor="#F8FFCC"><strong>Registrado</strong></td>
              <td width="10" align="center" bgcolor="#F8FFCC"><img src="img/icn/accept.png" width="16" height="16" title="Cobrar" /></td>
              <td width="10" align="center" bgcolor="#F8FFCC"><img src="img/impres.gif" width="16" height="16" alt=""/></td>
              <td width="10" align="center" bgcolor="#F8FFCC"><img src="img/mesa.png" width="16" height="16" alt=""/></td>
              <!--<td width="10" align="center" bgcolor="#F8FFCC"><a href="#pop1" onMouseUp="asignardt(<?php echo $rspedicu->fields['idtmpventares_cab']?>);"><img src="img/buscar16.png" width="16" height="16" title="Detalle del Pedido" /></a></td>-->
              <td width="10" align="center" bgcolor="#F8FFCC"><img src="img/error1.png" width="20" height="20" title="Borrar"   /></td>
            </tr>
<?php while (!$rspedicu->EOF) {
    $totalventa = intval($rspedicu->fields['monto']);
    $delivery = intval($rspedicu->fields['delivery_costo']);
    $montoglobal = $totalventa + $delivery;
    ?>
            <tr style="background-color:#FFF;">
              <td align="center"><?php echo $rspedicu->fields['idtmpventares_cab'] ?></td>
              <td align="center"><?php echo $rspedicu->fields['nombre_deliv'] ?> <?php echo $rspedicu->fields['apellido_deliv'] ?></td>
              <td align="right"><?php echo formatomoneda($montoglobal); ?></td>
              <td align="center"><?php echo date("d/m/Y H:i", strtotime($rspedicu->fields['fechahora'])); ?></td>
              <td width="10" align="center" bgcolor="#F8FFCC"><a href="javascript:void(0);" onMouseUp="cobrar_pedido_del('<?php echo $rspedicu->fields['idtmpventares_cab'] ?>',<?php echo $montoglobal; ?>,<?php echo $rspedicu->fields['iddomicilio'] ?>);"><img src="img/icn/accept.png" width="16" height="16" title="Cobrar" /></a></td>
              <td align="center"><a href="javascript:void(0);" onMouseUp="reimpimir_comp('<?php echo $rspedicu->fields['idtmpventares_cab'] ?>');"><img src="img/impres.gif" width="16" height="16" alt=""/></a></td>
              <td align="center"><a href="javascript:void(0);" onMouseUp="transfer_mesa('<?php echo $rspedicu->fields['idtmpventares_cab'] ?>');"><img src="img/mesa.png" width="16" height="16" alt="Transferir a mesa"/></a></td>
              <!--<td><a href="#pop1" onMouseUp="asignardt(<?php echo $rspedicu->fields['idtmpventares_cab']?>);"><img src="img/buscar16.png" width="16" height="16" title="Detalle del Pedido" /></a><a href="#pop1" onMouseUp="asignardt(<?php echo $rspedicu->fields['idtmpventares_cab']?>);"></a></td>-->
              <td><a href="javascript:void(0);" onClick="chau(<?php echo $rspedicu->fields['idtmpventares_cab']?>)"><img src="img/error1.png" width="20" height="20"  title="Borrar" /></a></td>
            </tr>
<?php $rspedicu->MoveNext();
}  ?>
          </tbody>
        </table>
        </div>
    </div>
    
  </div>
<div style="height:30px;"></div>
</div>
