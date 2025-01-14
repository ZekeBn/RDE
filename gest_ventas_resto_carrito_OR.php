 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//Traemos las preferencias para la empresa
$buscar = "Select carry_out from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$carry_out = trim($rspref->fields['carry_out']);


//Cliente x defecto
$buscar = "Select * from cliente where borrable='N' and idempresa=$idempresa";
$rsoclci = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$domicilio = intval($_COOKIE['dom_deliv']);
if ($domicilio > 0) {
    $buscar = "Select * from cliente_delivery inner join cliente_delivery_dom
    on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
    where iddomicilio=$domicilio and cliente_delivery.idempresa=$idempresa limit 1
    ";
    $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $direccion = trim($rscasa->fields['direccion']);
    $telefono = trim($rscasa->fields['telefono']);
    $nombreclidel = trim($rscasa->fields['nombres']);


}


$consulta = "
select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
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

$buscar = "
Select gest_zonas.idzona,descripcion,costoentrega
from gest_zonas
where 
estado=1 
and gest_zonas.idempresa = $idempresa 
and gest_zonas.idsucursal = $idsucursal
order by descripcion asc
";
$rszonas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "
select * from cliente where borrable = 'N' and idempresa = $idempresa limit 1
";
$rsclipred = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><?php if ($_COOKIE['dom_deliv'] > 0) {?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
DELIVERY: <?php echo $nombreclidel?>
</div>
<div style="border:1px solid #000000; text-align:center;"><a href="delivery_pedidos.php">[Cambiar]</a> | <a href="#" onMouseUp="borra_delivery();">[Borrar]</a></div>
<?php } ?>
<div style="border-bottom:1px solid #000000; height:500px; overflow-y:scroll; font-size:10px;">
<table width="98%" border="1" class="tablalinda">
  <tbody>
    <tr>
      <td height="29" bgcolor="#CCCCCC"><strong>Producto</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Cant.</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Total</strong></td>
      <td width="50" align="center" bgcolor="#CCCCCC">&nbsp;</td>
    </tr>
<?php while (!$rs->EOF) {
    $total = $rs->fields['subtotal'];
    $totalacum += $total;
    $des = str_replace("'", "", $rs->fields['descripcion']);
    ?>
    <tr>
      <td height="30"><?php echo Capitalizar($rs->fields['descripcion']); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['total'], 3, 'N'); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['subtotal'], 0, 'N'); ?></td>
      <td align="center"><?php if ($rs->fields['tienereceta'] > 0 or $rs->fields['tieneagregado'] > 0 or $rs->fields['combinado'] == 'S') { ?><a href="javascript:void(0);" onClick="document.location.href='editareceta.php?id=<?php echo $rs->fields['idproducto']; ?>'"><img src="img/receta.png" width="20" height="20" alt="Agregados" /></a>&nbsp;&nbsp;<?php } ?><a href="javascript:void(0);" onClick="borrar('<?php echo $rs->fields['idproducto']; ?>','<?php echo Capitalizar($des); ?>');"><img src="img/borrar.png" width="20" height="20" alt="Borrar" /></a></td>
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
      <td height="30">Agregados</td>
      <td align="center"><?php echo formatomoneda($totalagregado, 0); ?></td>
      <td align="center"><?php echo formatomoneda($montototalagregado, 0); ?></td>
      <td align="center">&nbsp;</td>
    </tr>
<?php } ?>
    <tr>
        <td height="39" colspan="4" align="center"><strong><span style="font-size: 16px;color: #DB171A">Total Venta: <?php echo formatomoneda($totalacum, 0); ?><input type="hidden" name="totalventa" id="totalventa" value="<?php echo $totalacum; ?>">
    <input type="hidden" name="totalventa_real" id="totalventa_real" value="<?php echo $totalacum; ?>"></span></strong></td>
    </tr>
  </tbody>
</table>
<p align="center"><a href="javascript:void(0);" onClick="borrar_todo();"><img src="img/trashico.png" width="20" height="20" alt="Borrar Todo" name="Borrar Todo" /></a><br />
<img src="img/logo_carrito.png" width="200" height="73" alt="" style="margin:10px; "/></p></div>
<br />
<?php if ($valido == "N") { ?>
<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:600px; margin:0px auto; text-align:center;">
<strong>Errores:</strong><br />
<?php echo $errores; ?>
</div><br />
<?php } ?>
<div  style="width:100%; margin-left:0px; height: 180px; margin-top: 40px;" >
      <input type="hidden" name="occliedefe" id="occliedefe" value="<?php echo $rsoclci->fields['idcliente']?>" />
        <table width="300">
            <tr>
              <td width="150" height="38" ><a id="occ" href="#pop5" style="visibility:hidden"  >ABRETE</a>
              <a id="ocb" href="#pop3" onClick="abrecodbarra();" style="visibility:hidden">abrir</a></td>
              <td width="78" >
                  <?php if (intval($totalacum) > 0) {?>
            <a href="#pop4" class="button blue micro radius" onmouseover="popupasigna4();" onClick="cobranza('','',1)" id="cob4"  >&nbsp;
                <span class="icon-check" style=" font-size: 40px; "></span>
                </a>
                <?php }?>
            </td>
              <td width="65" >  <a  href="javascript:void(0);" class="button green micro radius" target="_blank" onClick="document.location.href='gest_administrar_caja.php?lo=1'" title="">
                  &nbsp;
                  <span class="icon-calculator" style=" font-size: 40px; "></span>
              </a>    </td>
              <td width="187" > 
                  <a id="refe" href="gest_impresiones.php" class="button red micro radius" target="_blank"  >&nbsp;
                      <span class="icon-print" style=" font-size: 40px; "></span>
                  </a>
               </td>
            </tr>
        </table><br />
        <p align="center">
        <a href="#" class="btn btn-info"  onMouseUp="document.location.href='delivery_pedidos.php'"><input type="button" name="button" id="button" value="Tomar Delivery"></a>
<?php if ($carry_out == 'S') { ?>        &nbsp;
          <a href="#pop6" class="btn btn-info" onmouseover="popupasigna6();" onMouseUp="carry_out();"><input type="button" name="button" id="button" value="Pasa a Retirar"></a>
<?php } ?>
        </p>
        <a id="adhbtn" href="#pop7"  onClick="abreadherente();" style="display:none;">Adherente</a>
</div>

