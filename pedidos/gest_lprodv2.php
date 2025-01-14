<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
require_once("../includes/rsusuario.php");

// $buscar="SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
// $rsd=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
// $id_cajas_edi = intval($rsd->fields['id_medida']);
$idproveedor = "";
$ocnum = intval($_GET['id']);
echo json_encode($_GET);
echo json_encode($_POST);
exit;
if ($ocnum == 0) {
    $ocnum = intval($_POST['ocn']);


}
// if($ocnum > 0){
// 	$buscar="SELECT idproveedor from compras_ordenes where ocnum = $ocnum";
// 	$rs_ocnum=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
// 	$idproveedor = $rs_ocnum -> fields['idproveedor'];
// }
//echo $idproveedor;exit;
$v = trim($_POST['bus']);
echo $v;
exit;
if ($v != '' and is_numeric($v)) {
    $ra = antisqlinyeccion($_POST['bus'], 'int');
    $ra = str_replace("'", "", $ra);
    $add = " AND idinsumo LIKE '%$ra%'";
} elseif ($v != '') {
    $ra = antisqlinyeccion($_POST['bus'], 'text');
    $ra = str_replace("'", "", $ra);
    $add = " AND descripcion LIKE '%$ra%'";

} else {
    $add = " ";
}

if ($_POST['fcbar'] != '') {
    $fcbar = antisqlinyeccion($_POST['fcbar'], 'text');
    // Eliminar comillas adicionales si existen
    $fcbar = str_replace("'", "", $fcbar);
    $add = " AND bar_code LIKE '%$fcbar%'";
}

$buscar = "
    SELECT *, 
        (SELECT barcode FROM productos WHERE idprod_serial = insumos_lista.idproducto) AS barcode,
        (SELECT nombre FROM medidas WHERE medidas.id_medida = insumos_lista.idmedida) AS medida,
        (SELECT nombre FROM medidas WHERE medidas.id_medida = insumos_lista.idmedida2) AS medida2,
        (SELECT nombre FROM medidas WHERE medidas.id_medida = insumos_lista.idmedida3) AS medida3
    FROM insumos_lista 
    WHERE
        estado = 'A'
        AND UPPER(insumos_lista.descripcion) NOT LIKE '%DESCUENTO%' 
        AND UPPER(insumos_lista.descripcion) NOT LIKE '%AJUSTE%'
        $add 
    ORDER BY idinsumo ASC
";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprod = $rsp->RecordCount();

//echo $tprod;

?>
<div class="divizquierda350">
<select name="lprod" size="4" id="lprod" style="width:100%;" >
<?php
$i = 1;
while (!$rsp->EOF) {
    $clase = " ";
    if ($i % 2 == 1) {
        $clase = "class='even'";
    }

    ?>

 <option <?php echo $clase;  ?> onclick="verificar_insumo(<?php echo $rsp->fields['idinsumo']; ?>)"  data-hidden-idmedida="<?php echo antixss($rsp->fields['idmedida']);?>" data-hidden-medida="<?php echo antixss($rsp->fields['medida']);?>" data-hidden-idmedida2="<?php echo antixss($rsp->fields['idmedida2']);?>" data-hidden-medida2="<?php echo antixss($rsp->fields['medida2']);?>" data-hidden-cant-medida2="<?php echo intval($rsp->fields['cant_medida2']);?>" data-hidden-idmedida3="<?php echo antixss($rsp->fields['idmedida3']);?>" data-hidden-medida3="<?php echo antixss($rsp->fields['medida3']);?>" data-hidden-cant-medida3="<?php echo intval($rsp->fields['cant_medida3']);?>" data-hidden-cant-edi="<?php echo intval($rsp->fields['cant_caja_edi']);?>" data-hidden-id-edi="<?php echo intval($id_cajas_edi); ?>"  value="<?php echo $rsp->fields['idinsumo']?>" <?php if ($tprod == 1) { ?>selected="selected"<?php } ?>><?php echo $rsp->fields['descripcion'] ?> [<?php echo $rsp->fields['idinsumo']; ?>] - <?php echo $rsp->fields['barcode'] ?></option>
 <?php
        $i++;
    $rsp->MoveNext();
}?>
</select>
</div>
