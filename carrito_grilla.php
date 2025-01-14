 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "58";
require_once("includes/rsusuario.php");

$consulta = "
select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa
group by descripcion, receta_cambiada
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




?><strong>Carrito:</strong>
<br />
<table width="98%" border="1" class="tablalinda">
  <tbody>
    <tr>
      <td bgcolor="#CCCCCC"><strong>Producto</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Cant.</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Total</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Acciones</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $total = $rs->fields['totalprecio'];
    $totalacum += $total;
    ?>
    <tr>
      <td height="50"><?php echo Capitalizar($rs->fields['descripcion']); ?></td>
      <td align="center"><?php echo $rs->fields['total']; ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['totalprecio'], 0); ?></td>
      <td align="center"><?php if ($rs->fields['tienereceta'] > 0 or $rs->fields['tieneagregado'] > 0 or $rs->fields['combinado'] == 'S') { ?><input type="button" name="button2" id="button9" value="Cambiar Ingredientes" class="boton" onClick="document.location.href='editareceta.php?id=<?php echo $rs->fields['idproducto']; ?>'"><?php } ?>
        <input type="button" name="button2" id="button10" value="Eliminar"  class="boton" onClick="borrar('<?php echo $rs->fields['idproducto']; ?>','<?php echo Capitalizar($rs->fields['descripcion']); ?>');"></td>
    </tr>
<?php $rs->MoveNext();
} ?>
<?php

// buscar si hay agregados y mostrar el total global
$consulta = "
SELECT sum(precio_adicional) as montototalagregados , count(idventatmp) as totalagregados
FROM 
tmp_ventares_agregado
where
idventatmp in (
select tmp_ventares.idventatmp
from tmp_ventares 
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa
)
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalagregado = $rs->fields['montototalagregados'];
$totalagregado = $rs->fields['totalagregados'];
$totalacum += $montototalagregado;

if ($totalagregado > 0) {
    ?>
    <tr>
      <td height="50">Agregados</td>
      <td align="center"><?php echo formatomoneda($totalagregado, 0); ?></td>
      <td align="center"><?php echo formatomoneda($montototalagregado, 0); ?></td>
      <td align="center">&nbsp;</td>
    </tr>
<?php } ?>
    <tr>
      <td height="50" colspan="4"><strong>Total: <?php echo formatomoneda($totalacum, 0); ?></strong></td>
    </tr>
  </tbody>
</table>
<br />
<?php if ($valido == "N") { ?>
<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:600px; margin:0px auto; text-align:center;">
<strong>Errores:</strong><br />
<?php echo $errores; ?>
</div><br />
<?php } ?>
<form id="form1" name="form1" method="post">
<table class="tablalinda" style="margin:0px auto; border:2px solid #000;">
  <tbody>
  <tr align="center" style="border:2px solid #000;">
    <td>
    <table width="99%" height="99%" border="0">
      <tbody>
<?php if (intval($_SESSION['canal']) == 1 or intval($_SESSION['canal']) == 0) { ?>
        <tr>
          <td><input type="text" name="ruc" style="text-transform: uppercase; width:250px;" required id="ruc" placeholder="RUC" value="<?php if (trim($_COOKIE['ruc_cookie']) != '') {
              echo htmlentities($_COOKIE['ruc_cookie']);
          } else {
              echo $ruc_pred;
          } ?>" onChange="mantiene_carrito();" ></td>
        </tr>
        <tr>
          <td><input name="razon_social" type="text" required id="razon_social" placeholder="Razon Social" style="text-transform: uppercase; width:250px;" value="<?php if (trim($_COOKIE['razon_social_cookie']) != '') {
              echo htmlentities($_COOKIE['razon_social_cookie']);
          } else {
              echo $razon_social_pred;
          } ?>" onChange="mantiene_carrito();" ></td>
        </tr><?php
if (trim($_COOKIE['delivery_cookie']) == 'S') {
    $display = "none";
} else {
    $display = "display";
}
    if ($_COOKIE['chapa_cookie'] == 'DELIVERY') {
        $_COOKIE['chapa_cookie'] = "";
    }

    ?>
        <tr id="chapatr" style="display:<?php echo $display; ?>;">
          <td><input type="text" name="chapa" id="chapa" placeholder="Chapa" style="text-transform: uppercase; width:250px;" autocomplete="off"  value="<?php echo htmlentities($_COOKIE['chapa_cookie']); ?>" onChange="mantiene_carrito();" required></td>
        </tr>
        <tr>
          <td><select name="delivery" required id="delivery" style="text-transform: uppercase; width:250px;" onChange="mantiene_carrito();">
            <option value="N" <?php if (trim($_COOKIE['delivery_cookie']) == 'N' or trim($_COOKIE['delivery_cookie']) == '') {  ?> selected<?php } ?>>SIN Delivery</option>
            <option value="S" <?php if (trim($_COOKIE['delivery_cookie']) == 'S') {  ?> selected<?php } ?>>Enviar por Delivery</option>
          </select></td>
        </tr>
<?php } else {
    // mesas
    $consulta = "
SELECT mesas.idmesa, mesas.numero_mesa, salon.nombre
FROM mesas
inner join salon on mesas.idsalon = salon.idsalon
WHERE
salon.idsucursal = $idsucursal
order by salon.nombre asc, mesas.numero_mesa asc
";
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
        <tr>
          <td><select name="mesa" id="mesa" style="text-transform: uppercase; width:250px;" onChange="mantiene_carrito();">
            <option value="">Seleccionar Mesa...</option>
              <?php while (!$rsmesa->EOF) { ?>
              <option value="<?php echo $rsmesa->fields['idmesa']; ?>" <?php if (intval($_COOKIE['mesa_cookie']) == $rsmesa->fields['idmesa']) { ?>selected<?php } ?>>Mesa <?php echo $rsmesa->fields['numero_mesa']; ?> - <?php echo $rsmesa->fields['nombre']; ?></option>
              <?php $rsmesa->MoveNext();
              }  ?>
          </select><input name="razon_social" type="hidden" required id="razon_social" placeholder="Razon Social" style="text-transform: uppercase; width:250px;" value="Consumidor Final" ><input type="hidden" name="ruc" style="text-transform: uppercase; width:250px;" required id="ruc" placeholder="RUC" value="44444401-7" ></td>
        </tr>
<?php } ?>
        <tr>
          <td><textarea name="observacion" cols="40" rows="3" id="observacion" placeholder="Observacion" style="width:250px;" onChange="mantiene_carrito();"><?php echo htmlentities($_COOKIE['observacion_cookie']); ?></textarea></td>
        </tr>
      </tbody>
    </table>
    </td>
    <td rowspan="4" align="center" valign="middle"><input type="submit" value="Registrar Pedido" class="boton" style="height:240px;">
        </td>
  </tr>
  </tbody>
</table><input type="hidden" name="MM_insert" id="MM_insert" value="form1">
</form>
