<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$dirsup = "S";
$editar = intval($_POST['ed']);
if ($editar > 0) {
    if ($editar == 1) {
        $registro = intval($_POST['reg']);

        $buscar = "Select * from sub_categorias where idsubcate=$registro";
        $rsed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $subcategoriachar = trim($rsed->fields['descripcion']);
        $idsubcate = intval($rsed->fields['idsubcate']);
        $idcategoriareg = intval($rsed->fields['idcategoria']);
        $orden = intval($rsed->fields['orden']);



    } else {
        if ($editar == 2) {
            //alta
            $registro = antisqlinyeccion($_POST['reg'], 'text');
            $idppal = intval($_POST['idppal'])	;
            $orden = intval($_POST['orden']);
            $buscar = "Select * from sub_categorias where descripcion=$registro";
            $rn = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            if ($rn->fields['descripcion'] == '') {
                //no existe

                $buscar = "Select max(idsubcate) as mayor from sub_categorias";
                $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $idsubcate = intval($rsmay->fields['mayor']) + 1;


                $insertar = "Insert into sub_categorias
				(idcategoria,idsubcate,descripcion,idempresa,estado,orden)
				values
				($idppal,$idsubcate,$registro,1,1,$orden)
				";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



            } else {
                $errorsub = "<span class='resaltarojo'>El nombre ya existe.</span>";

            }

        } else {
            if ($editar == 3) {
                //Update
                $registro = antisqlinyeccion($_POST['reg'], 'text');
                $idcate = intval($_POST['idcat']);
                $idsub = intval($_POST['idsub']);
                $orden = intval($_POST['orden']);
                $update = "update sub_categorias set descripcion=$registro,idcategoria=$idcate,orden=$orden  where  idsubcate=$idsub";
                $conexion->Execute($update) or die(errorpg($conexion, $update));


            }

        }
    }
}
$eliminar = intval($_POST['el']);
if ($eliminar == 1) {
    $idsub = intval($_POST['reg']);

    if ($idsub > 0) {
        $delete = "delete  from sub_categorias  where idsubcate=$idsub";
        $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    }

}
$buscar = "Select * from sub_categorias order by descripcion asc";
$rscatesub = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Traemos las categorias por si quiera editarlas
$buscar = "Select * from categorias order by nombre asc";
$rscatev = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>
<div class="divizquierdam">
<span class="resaltaazul">Sub Categor&iacute;as</span><br />
 <?php echo $errorsub ?>
 <br />
	<select id="catesub" name="catesub"  size="6" style="width:200px;" onChange="reca(7)" >
            <option value="0">Seleccionar</option>
            <?php while (!$rscatesub->EOF) {?>
                        <option value="<?php echo $rscatesub->fields['idsubcate']?>" <?php if (intval($_POST['catesub']) == intval($rscatesub->fields['idsubcate'])) { ?> selected="selected"			 <?php } ?>><?php echo trim($rscatesub->fields['descripcion']) ?></option>
                        <?php $rscatesub->MoveNext();
            }?>
          
      </select>

</div>
<div class="divizquierdam" ><br /><br /><strong>Nombre Sub-categor&iacute;a</strong><br />
    <input type="text" name="categosub" id="categosub" value="<?php echo $subcategoriachar ?>"/><br />
   <strong>Categor&iacute;a Principal</strong><br />
    <select id="cates" name="cates" title="Indica a qu&eacute; categor&iacute;a principal pertenece &eacute;sta sub-categor&iacute;a">
    <option value="0" selected="selected">Seleccionar</option>
    	<?php while (!$rscatev->EOF) {?>
    	<option value="<?php echo $rscatev->fields['id_categoria']?>" <?php if ($idcategoriareg == $rscatev->fields['id_categoria']) {?> selected='selected' <?php } ?>><?php echo $rscatev->fields['nombre']?></option>
    	<?php $rscatev->MoveNext();
    	}?>
    </select>
    <input type="hidden" name="categoidsub" id="categoidsub" value="<?php echo $idsubcate ?>">
    <br />
    <strong>Orden</strong><br />
    <input type="text" name="orden" id="orden" value="<?php echo $orden?>" title="&Eacute;sta numeraci&oacute;n afecta el modo en el cual se muestra en la p&aacute;gina web (solo si se integra arandu con pagina web)" /><br />
    
     <input type="button" value="Agregar" onClick="reca(8)" />
     <input type="button" value="Modificar" onClick="reca(9)" />
     <input type="button" value="Eliminar" onClick="eliminar(3)"  />
</div>