<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "99";
require_once("includes/rsusuario.php");
$valor = antisqlinyeccion($_POST['valorbusca'], 'text');
$b = str_replace("'", "", $valor);
$cual = intval($_POST['quebusca']);
if ($cual == 1) {
    $add = " and insumos_lista.descripcion like ('%$b%') " ;
}
if ($cual == 2) {
    $add = " and (select barcode from productos where borrado = 'N' and productos.idprod_serial=insumos_lista.idproducto limit 1) = $valor " ;
}
/*$buscar="Select idinsumo as idprod_serial,idprod_serial,insumos_lista.descripcion,barcode from productos
inner join insumos_lista on insumos_lista on insumos_lista.idproducto=productos.idprod_serial
 $add order by insumos_lista.descripcion asc limit 30";
 */
$buscar = "
 Select
 idinsumo  as idprod_serial,
 descripcion,
 (select barcode from productos where borrado = 'N' and productos.idprod_serial=insumos_lista.idproducto limit 1) as barcode
from insumos_lista
where
estado = 'A'
$add
order by descripcion asc
limit 30
";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



?>
<table class="table table-striped" style="background-color:#FFFFFF;" width="60%">
<thead>
    <tr>
        <th width="28%" height="37" bgcolor="#E8E8E8">Cod Barras </th>
        <th width="25%" bgcolor="#E8E8E8">Descripci&oacute;n</th>
        <th width="173" bgcolor="#E8E8E8">Cantidad Precio</th>
        <th width="24%" bgcolor="#E8E8E8">Cantidad</th>
     	<th width="24%" bgcolor="#E8E8E8">Monto</th>
        <th width="25%" bgcolor="#E8E8E8">&nbsp;</th>
         
    </tr>
</thead>
<tbody>
<?php while (!$rsp->EOF) {


    ?>
<tr>
    <th scope="row"><?php echo $rsp->fields['barcode']?></th>
    <td><?php echo $rsp->fields['descripcion']?></td>
    <td><input type="text" name="ncapre_<?php echo $rsp->fields['idprod_serial'] ?>" id="ncapre_<?php echo $rsp->fields['idprod_serial'] ?>" value="" size="5" onKeyUp="bloquearn(2,<?php echo $rsp->fields['idprod_serial'] ?>);" /></td>
    <td><input type="text" name="nca_<?php echo $rsp->fields['idprod_serial'] ?>" id="nca_<?php echo $rsp->fields['idprod_serial'] ?>" value="" size="5" onKeyUp="bloquearn(1,<?php echo $rsp->fields['idprod_serial'] ?>);" /></td>
      <td><input type="text" name="monto_<?php echo  $rsp->fields['idprod_serial'] ?>" id="monto_<?php echo  $rsp->fields['idprod_serial'] ?>" value="" size="5" style="height: 40px;" /></td>
    <td><a href="javascript:void(0);" onClick="agregarprod(<?php echo  $rsp->fields['idprod_serial']; ?>)" >[<span class="fa fa-check"></span> &nbsp;Agregar ] </a></td>
</tr>
<?php $rsp->MoveNext();
}?>
</tbody>
</table>
<div class="clearfix"></div>