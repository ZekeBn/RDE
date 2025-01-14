 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");
//
$agregar = intval($_POST['agregar']);
$describe = antisqlinyeccion($_POST['describe'], 'text');

if ($agregar > 0) {
    $buscar = "Select * from tipos_eventos where descripcion=$describe and estado <> 6";
    $rscontrol = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rscontrol->fields['idtipoevento'] > 0) {
        $errorex = 1;
    } else {
        $insertar = "insert into tipos_eventos (descripcion,estado) values ($describe,1)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    }


}

$buscar = "Select * from tipos_eventos where estado <> 6 order by descripcion asc"    ;
$rstipoev = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



?>
<select name="idtipoev" id="idtipoev" style="height:40px; width: 80%;">
            <option value="" selected="selected">Seleccionar</option>
            <?php while (!$rstipoev->EOF) {?>
            <option value="<?php echo $rstipoev->fields['idtipoevento']?>" <?php if ($_POST['idtipoev'] == $rstipoev->fields['idtipoevento']) { ?> selected="selected"<?php } ?>><?php echo $rstipoev->fields['descripcion']?></option>
            
            <?php $rstipoev->MoveNext();
            }?>
</select>
<button type="button" class="btn btn-dark go-class" onClick="agregar(1);"><span class="fa fa-plus"></span></button>
