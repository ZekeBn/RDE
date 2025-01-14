<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");
$idcate = intval($_POST['idc']);
if ($idcate > 0) {
    $buscar = "select * from sub_categorias where idcategoria=$idcate and idempresa = $idempresa and estado = 1 order by descripcion asc";
    $rscates = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
} else {
    $buscar = "select * from sub_categorias where idempresa = $idempresa and estado = 1 order by descripcion asc";
    $rscates = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
}
?>
	<select name="subcatels" id="subcatels" required="required" style="height: 40px; width: 50%;">
		<option value="0" selected="selected">Seleccionar</option>
		<?php while (!$rscates->EOF) {?>
		<option value="<?php echo $rscates->fields['idsubcate']?>" <?php if ((intval($_POST['subcate']) == intval($rscates->fields['idsubcate'])) || (isset($idsubcate) && intval($idsubcate) == intval($rscates->fields['idsubcate']))) { ?> selected="selected" <?php } ?>><?php echo trim($rscates->fields['descripcion']) ?></option>
	<?php $rscates->MoveNext();
		}?>
    
</select>
