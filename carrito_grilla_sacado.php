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
select tmp_ventares_sacado.*
from tmp_ventares_sacado
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
<strong>Lista de ingredientes excluidos:</strong>
<br />
<table width="98%" border="1" class="tablalinda">
  <tbody>
    <tr>
      <td bgcolor="#CCCCCC"><strong>Ingredientes Excluidos</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Acciones</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $total = $rs->fields['precio_adicional'];
    $totalacum += $total;
    ?>
    <tr>
      <td height="50">SIN <?php echo Capitalizar($rs->fields['alias']); ?></td>
      <td align="center"><input type="button" name="button2" id="button10" value="Eliminar"  class="boton" onClick="borrar('<?php echo $rs->fields['idproducto']; ?>','<?php echo $rs->fields['idingrediente']; ?>','<?php echo $rs->fields['idtmpventaressacado']; ?>','<?php echo Capitalizar($rs->fields['descripcion']); ?>');"></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<br />

<p align="center"><br /><br /></p>
<p align="center">&nbsp;</p>
<br /><br /><br />
