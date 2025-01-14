 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";

require_once("includes/rsusuario.php");
$idcate = intval($_POST['idc']);
if ($idcate > 0) {
    $buscar = "select * from sub_categorias where idcategoria=$idcate  order by descripcion asc";
    $rscates = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
} else {
    $buscar = "select * from sub_categorias order by descripcion asc";
    $rscates = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
}
?>
    <select name="subcatels" id="subcatels" style="width:90%; height:40px;">
        <option value="0" selected="selected">Seleccionar</option>
        <?php while (!$rscates->EOF) {?>
        <option value="<?php echo $rscates->fields['idsubcate']?>" <?php if ($valido == 'N') {
            if (intval($_POST['subcate']) == intval($rscates->fields['idsubcate'])) { ?> selected="selected" <?php }
            } ?>><?php echo trim($rscates->fields['descripcion']) ?></option>
    <?php $rscates->MoveNext();
        }?>
    
</select>
