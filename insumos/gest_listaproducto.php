<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup = "S";
require_once("../includes/rsusuario.php");
$v = trim($_POST['bus']);
$idp = trim($_POST['idp']);
$idp1 = $idp;
if ($v != '') {
    $ra = antisqlinyeccion($_POST['bus'], 'text');
    $ra = str_replace("'", "", $ra);
    $add = " where descripcion like '%$ra%'";
} else {
    if ($idp != '') {
        //vemos si no es una seleccion directa
        $idp = antisqlinyeccion($idp, 'text');
        $add = " where idprod=$idp";

    } else {
        $add = '';
    }
}


$buscar = "Select * from productos $add order by descripcion asc";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprod = $rsp->RecordCount();

$p1 = floatval($rsp->fields['p1']);
$p2 = floatval($rsp->fields['p2']);
$p3 = floatval($rsp->fields['p3']);


?>
<div class="divizquierda350">
	<select size="8" style="width:349px" name="lproductos" id="lproductos" onChange="seleccionar(this.value)">
            <?php while (!$rsp->EOF) {?>    
            <option value="<?php echo $rsp->fields['idprod']?>" <?php if ($idp1 == $rsp->fields['idprod']) { ?>selected="selected"<?php } ?>><?php echo $rsp->fields['descripcion']?></option>
            <?php $rsp->MoveNext();
            }?>
    </select></td>
</div>
<div class="divizquierda">
    <strong>
    <br />Cantidad Vender
    </strong>
    <br />
   <input type="text" name="cantidad" id="cantidad" value="" style="height:20px; width:120px;" />
    <br />   
    <strong>Tipo Precio</strong><br />
        <input type="button" name="uno" id="uno"  value="<?php echo $p1?>" onClick="precio(1)" />
        <input  type="button" name="dos" id="dos"  value="<?php echo $p2?>"  onClick="precio(2)"/>
        <input  type="button" name="tres" id="tres"  value="<?php echo $p3?>" onClick="precio(3)" />
        <input type="hidden" name="tipoprecio" id="tipoprecio" value="0" />
        
        <br />
        <input type="button" value="Agregar Producto" onClick="seleccionarproducto()" />
</div>