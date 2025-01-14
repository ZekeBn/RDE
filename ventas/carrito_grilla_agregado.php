<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

if (intval($idvt) == 0) {
    $idvt = intval($_GET['idvt']);
}

$consulta = "
select tmp_ventares_agregado.*
from tmp_ventares_agregado
where 
idventatmp = $idvt
and idventatmp in (
					select idventatmp 
					from tmp_ventares 
					where 
					idventatmp = $idvt
					and idempresa = $idempresa 
					and idsucursal = $idsucursal
				 )
order by alias desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




?>
<strong>Carrito de Adicionales:</strong>
<br />
<table width="98%" border="1" class="tablalinda">
  <tbody>
    <tr>
      <td bgcolor="#CCCCCC"><strong>Agregado</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Precio Adicional</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Acciones</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $total = $rs->fields['precio_adicional'];
    $totalacum += $total;
    ?>
    <tr>
      <td height="50"><?php echo Capitalizar($rs->fields['alias']); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['precio_adicional']); ?></td>
      <td align="center"><input type="button" name="button2" id="button10" value="Eliminar"  class="boton" onClick="borrar('<?php echo $rs->fields['idproducto']; ?>','<?php echo $rs->fields['idingrediente']; ?>','<?php echo $rs->fields['idtmpventaresagregado']; ?>','<?php echo Capitalizar($rs->fields['descripcion']); ?>');"></td>
    </tr>
<?php $rs->MoveNext();
} ?>
    <tr>
      <td height="50" colspan="4"><strong>Total Adicionales: <?php echo formatomoneda($totalacum, 0); ?></strong></td>
    </tr>
  </tbody>
</table>
<br />

<p align="center"><br /><br /></p>
<p align="center">&nbsp;</p>
<br /><br /><br />