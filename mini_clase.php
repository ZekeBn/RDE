 <?php
    require_once("includes/conexion.php");
require_once("includes/funciones.php");

$buscar = "Select * from gest_bancos order by descripcion asc";
$rsbanco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>


<td height="29" colspan="2" bgcolor="#F1EFEF"><strong>Banco:
<select id="banco" name="banco" >
    <option value="0" selected="selected">Seleccionar</option>
    <?php while (!$rsbanco->EOF) {?>
    <option value="<?php echo $rsbanco->fields['banco']?>"><?php echo $rsbanco->fields['descripcion']?></option>
    
    <?php $rsbanco->MoveNext();
    }?>
</select>
</strong></td>
 <td height="29" bgcolor="#F1EFEF"><strong>N&uacute;mero:<input type="text" name="numerob" id="numerob" value="0" /></strong></td>
