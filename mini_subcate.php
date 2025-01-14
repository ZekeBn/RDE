 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");

$modulo = "11";
$submodulo = "75";
require_once("includes/rsusuario.php");
if ($cate == 0) {
    //no hay categoria pr get
    $categoria = intval($_POST['categoria']);
} else {
    $categoria = $cate;

}

if ($categoria == 0) {


    $buscar = "Select * from sub_categorias where idempresa=$idempresa and estado=1 order by descripcion asc";
} else {
    $buscar = "Select * from sub_categorias where idempresa=$idempresa and estado=1 and idcategoria=$categoria order by descripcion asc";

}
$rscatsub = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
 <select name="subcategoria" id="subcategoria" class="form-control" style="height: 30px; width: 90%;">
    <option value="0" selected="selected">TODOS</option>
    <?php while (!$rscatsub->EOF) {?>
     <option value="<?php echo $rscatsub->fields['idsubcate']?>" <?php if ($subcate == $rscatsub->fields['idsubcate']) {?> selected="Selected"<?php }?>><?php echo $rscatsub->fields['descripcion']?></option>
    <?php $rscatsub->MoveNext();
    }?>
</select>
