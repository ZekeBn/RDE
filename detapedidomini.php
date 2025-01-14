 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
$pedidonum = intval($_POST['idpedido']);
//preferencia
$borrar_ped_cod = trim($rsco->fields['borrar_ped_cod']);

if ($pedidonum > 0) {
    $buscar = "
        Select productos.descripcion,tmp_ventares.combinado,idprod_mitad1,idprod_mitad2, tmp_ventares.precio,
        idtmpventares_cab,tmp_ventares.cantidad,idventatmp,idproducto,
        (select tmp_ventares_cab.delivery_costo from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery_costo,
        (select tmp_ventares_cab.delivery from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery,
        (select tmp_ventares_cab.idusu from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as idusu
         from 
        tmp_ventares
        inner join productos on productos.idprod=tmp_ventares.idproducto
        where 
        tmp_ventares.idsucursal=$idsucursal 
        and tmp_ventares.idempresa=$idempresa 
        and idtmpventares_cab=$pedidonum
        and tmp_ventares.borrado = 'N'
        and tmp_ventares.borrado_mozo = 'N'
        order by descripcion asc
        ";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcuerpo = $rsbb->RecordCount();
    //echo $buscar;
    // buscar usuario
    $operador = intval($rsbb->fields['idusu']);
    $consulta = "
        select usuario from usuarios where idusu = $operador
        ";
    $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $operador = $rsop->fields['usuario'];
}

?>
<div align="center" style="margin:10px;">
<br />
<?php if ($tcuerpo > 0) {?>
Pedido: <?php echo $pedidonum; ?>
<br /><hr /><br />

<table width="98%" border="1" class="tablaconborde">
  <tbody>
    <tr>
      <td height="43" bgcolor="#CCCCCC"><strong>Producto</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Agregados</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Eliminados</strong></td>
      <td align="center" bgcolor="#CCCCCC">[X]</td>
      </tr>
<?php while (!$rsbb->EOF) {
    $idventatmp = intval($rsbb->fields['idventatmp']);
    $idproducto = antisqlinyeccion($rsbb->fields['idproducto'], 'text');

    //$total=$rs->fields['precio']*$rs->fields['total'];
    //$totalacum+=$total;

    //$idvt=$rs->fields['idventatmp'];
    $consulta = "
    select tmp_ventares_agregado.*
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
      <td height="50"><?php echo Capitalizar($rsbb->fields['descripcion']); ?><br />Gs. <?php echo formatomoneda($rsbb->fields['precio']); ?><?php
    if ($rsbb->fields['combinado'] == 'S') {

        $prod_1 = $rsbb->fields['idprod_mitad1'];
        $prod_2 = $rsbb->fields['idprod_mitad2'];
        $consulta = "
select *
from productos
where 
(idprod_serial = $prod_1 or idprod_serial = $prod_2)
and idempresa = $idempresa
order by descripcion asc
";
        $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rspcom->EOF) {

            ?><br /><span style="font-style:italic;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Mitad <?php echo Capitalizar($rspcom->fields['descripcion']); ?></span>
      <?php $rspcom->MoveNext();
        }
    } ?></td>
      <td align="center"><?php while (!$rsag->EOF) {?> 
      - <?php echo Capitalizar($rsag->fields['alias']); ?> (Gs. <?php echo formatomoneda($rsag->fields['precio_adicional']); ?>)<br />
      <?php $rsag->MoveNext();
      } ?>
      </td>
      <td align="center"><?php while (!$rssac->EOF) {?> 
        - Sin <?php echo Capitalizar($rssac->fields['alias']); ?><br />
        <?php $rssac->MoveNext();
      } ?></td>
      <td align="center"><?php if ($borrar_ped_cod == 'S') {  ?><input name="codigo_borra_<?php echo $rsbb->fields['idventatmp']; ?>" id="codigo_borra_<?php echo $rsbb->fields['idventatmp']; ?>" type="password" size="6" /><?php } ?><a href="javascript:borra_prod(<?php echo $rsbb->fields['idventatmp']; ?>,<?php echo $rsbb->fields['idtmpventares_cab']; ?>);">[X]</a></td>
      </tr>
<?php $rsbb->MoveNext();
} ?>
  </tbody>
</table>
<?php
$rsbb->MoveFirst();

    $consulta = "
        select *
        from tmp_ventares_cab
        where
        idsucursal = $idsucursal
        and idempresa = $idempresa
        and idtmpventares_cab = $pedidonum
        and finalizado = 'S'
        and registrado = 'N'
        ";
    //echo $consulta;
    $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id = intval($rscab->fields['idtmpventares_cab']);

    if ($rsbb->fields['delivery'] == 'S') {

        ?>
<h1 align="center">Delivery</h1>
<table width="350" border="1" class="tablaconborde">
    <tbody>
      <tr>
        <td><strong>Telefono:</strong></td>
        <td>0<?php echo $rscab->fields['telefono']; ?></td>
      </tr>
      <tr>
        <td><strong>Llevar POS</strong></td>
        <td><?php echo siono($rscab->fields['llevapos']); ?></td>
      </tr>
      <tr>
        <td><strong>Razon Social</strong></td>
        <td><?php echo $rscab->fields['razon_social']; ?></td>
      </tr>
      <tr>
        <td><strong>Ruc:</strong></td>
        <td><?php echo $rscab->fields['ruc']; ?></td>
      </tr>
      <tr>
        <td><strong>Direccion:</strong></td>
        <td><textarea name="textarea2" cols="30" rows="3" id="textarea4"><?php echo $rscab->fields['direccion']; ?></textarea></td>
      </tr>
      <tr>
        <td><strong>Observacion Delivery:</strong></td>
        <td><textarea name="textarea" cols="30" rows="3" id="textarea3"><?php echo $rscab->fields['observacion_delivery']; ?></textarea></td>
      </tr>
      <tr>
        <td><strong>Operador:</strong></td>
        <td><?php echo $operador; ?></td>
      </tr>
      <tr>
        <td><strong>Observacion Operador:</strong></td>
        <td><textarea name="textarea" cols="30" rows="3" id="textarea3"><?php echo $rscab->fields['observacion']; ?></textarea></td>
      </tr>
    </tbody>
  </table>
  <br />
  <table width="350" border="1" class="tablaconborde">
    <tbody>
      <tr>
        <td><strong>Total Compra:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['monto']); ?></td>
      </tr>
      <tr>
        <td><strong>Costo Delivery:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['delivery_costo']); ?></td>
      </tr>
      <tr>
        <td><strong>Total con Delivery:</strong></td>
        <td align="right"><?php echo formatomoneda(intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo'])); ?></td>
      </tr>
    </tbody>
  </table>
  <br />
  <table width="350" border="1" class="tablaconborde">
    <tbody>
      <tr>
        <td><strong>Paga con (Cambio):</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['cambio']); ?></td>
      </tr>
      <tr>
        <td><strong>Vuelto:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'])); ?></td>
      </tr>
    </tbody>
  </table>
<?php } else {
    $idmesa = intval($rscab->fields['idmesa']);
    if ($idmesa > 0) {
        $consulta = "
    SELECT mesas.idmesa, mesas.numero_mesa, salon.nombre
    FROM mesas
    inner join salon on mesas.idsalon = salon.idsalon
    WHERE
    mesas.idmesa = $idmesa
    and salon.idsucursal = $idsucursal
    order by salon.nombre asc, mesas.numero_mesa asc
    ";
        $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    ?>
 <br />
<table width="350" border="1" class="tablaconborde">
  <tbody>
    <tr>
      <td><strong>Operador:</strong></td>
      <td><?php echo $operador; ?></td>
    </tr>
<?php if ($idmesa > 0) { ?>
    <tr>
      <td><strong>Mesa:</strong></td>
      <td><?php echo $rsmesa->fields['numero_mesa']; ?></td>
    </tr>
    <tr>
      <td><strong>Salon:</strong></td>
      <td><?php echo $rsmesa->fields['nombre']; ?></td>
    </tr>
<?php } ?>
<?php if ($rscab->fields['chapa'] != '') { ?>
    <tr>
      <td><strong>Chapa</strong></td>
      <td><?php echo $rscab->fields['chapa']; ?></td>
    </tr>
<?php } ?>
    <tr>
      <td><strong>Observacion Operador:</strong></td>
      <td><?php echo $rscab->fields['observacion']; ?></td>
    </tr>
  </tbody>
</table>

<?php } ?><br /><br /><br />
<input type="button" value="Volver a Imprimir Cocina" id="reimpcoc" style="height:40px; width:200px; display:;" onMouseUp="reimpimir('<?php echo $pedidonum; ?>');">
<input type="button" value="Volver a Imprimir Caja" id="reimpcaj" style="height:40px; width:200px; display:;" onMouseUp="reimpimir_comp('<?php echo $pedidonum; ?>');">
<div id="reimprimebox"></div>
<br /><br />
<?php }?>
<br />
</div>
