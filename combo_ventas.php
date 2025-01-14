 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

/*
si el grupo tiene 1 solo producto cargado
debe hacer un insert directo y no figurar en la lista
si la cantidad es 6 y solo hay 1 producto cargado hace 6 insert
*/

$id = intval($_POST['id']);

$consulta = "
select * 
from combos_listas 
where 
idproducto = $id 
and estado = 1
and (select borrado from productos where idprod_serial = combos_listas.idproducto ) = 'N'
";
$rscombo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// categoria del producto principal
$consulta = "
select * 
from productos  
where 
idprod_serial = $id 
and idempresa = $idempresa 
and combo = 'S'
and borrado = 'N'
";
$rsprodprin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cat_princ = $rsprodprin->fields['idcategoria'];


?>
<?php while (!$rscombo->EOF) {

    $idlistacombo = $rscombo->fields['idlistacombo'];
    $idprod_princ = $rscombo->fields['idproducto'];


    $consulta = "
    select * ,
    (
        select count(*) as total
        from tmp_combos_listas
        where
        tmp_combos_listas.idproducto = combos_listas_det.idproducto
        and tmp_combos_listas.idsucursal = $idsucursal
        and tmp_combos_listas.idusuario = $idusu
        and tmp_combos_listas.idempresa = $idempresa
        and tmp_combos_listas.idlistacombo = $idlistacombo
        and tmp_combos_listas.idventatmp is null
    ) as total
    from productos 
    inner join combos_listas_det on productos.idprod_serial = combos_listas_det.idproducto
    where 
    productos.idempresa = $idempresa
    and combos_listas_det.idempresa= $idempresa 
    and productos.combo <> 'S'
    and combos_listas_det.idlistacombo = $idlistacombo
    and productos.idprod_serial in (select idproducto from combos_listas_det where idlistacombo = $idlistacombo and idempresa = $idempresa)
    and productos.borrado = 'N'
    order by 
    productos.descripcion asc
    limit 100    
    ";
    //echo $consulta;
    $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<div style="height:15px; font-size:15px; margin:5px;"><strong><?php echo $rscombo->fields['nombre']; ?> (<?php echo $rscombo->fields['cantidad']; ?>): </strong><input type="button" value="Reiniciar" onMouseUp="reinicia_grupo(<?php echo $idlistacombo?>,<?php echo $idprod_princ; ?>);"></div><br />
<div id="grupo_<?php echo $idlistacombo?>">
<?php
    $col = intval(ceil($totalprod / $totalcol));
    $x = 1;

    while (!$rsprod->EOF) {
        $img = "gfx/productos/prod_".$rsprod->fields['idprod_serial'].".jpg";
        if (!file_exists($img)) {
            $img = "gfx/productos/prod_0.jpg";
        }

        ?><div id="prod_<?php echo $rsprod->fields['idprod_serial'].'_'.$idlistacombo; ?>" class="producto" <?php
        // si es producto
        if (trim($rsprod->fields['combinado']) == 'N' && trim($rsprod->fields['combo']) == 'N') {
            //echo "onClick=\"apretar('".$rsprod->fields['idprod_serial']."',0,0);\" ";
            echo "onClick=\"agrega_prod_grupo(".$rsprod->fields['idprod_serial'].",".$idlistacombo.");\" ";
        } else {
            //echo "onClick=\"apretar_pizza('".$rsprod->fields['idprod_serial']."');\" ";
        }
        ?> ><div class="contador" id="contador_<?php echo $rsprod->fields['idprod_serial'].'_'.$idlistacombo; ?>" ><?php echo intval($rsprod->fields['total']); ?></div>
    <?php if (trim($rsprod->fields['descripcion']) != '') { ?><img src="<?php echo $img ?>" height="81" width="163" border="0" alt="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" title="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" /><br /><?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>
    <br />
    <?php /* ?><div class="clear"></div>
    <div style="width:90px; margin:0px auto;">
        <div style="float:left; width:22px;"><img src="img/c_agrega_mini.jpg" width="22" height="22" alt="" onMouseUp="agrega_prod_grupo(<?php echo $rsprod->fields['idprod_serial']; ?>,<?php echo $idlistacombo?>)"/></div>
        <div style="float:left; width:46px;"><input type="text" size="3" maxlength="3" value="0" style="width:46px; text-align:center; margin-bottom:15px;" id="cant_<?php echo $rsprod->fields['idprod_serial'].'_'.$idlistacombo; ?>" ></div>
        <div style="float:left; width:22px;"><img src="img/c_elimina_mini.jpg" width="22" height="22" alt="" onMouseUp=").value)-1;"/></div>
    </div><?php */ ?>
    
    <br /><?php } ?>
</div>
    <?php $x++;
        $rsprod->MoveNext();
    }?>

<div class="clear"></div><br />
</div>
<?php $rscombo->MoveNext();
} ?>
<p align="center"><input type="button" value="TERMINADO" style="height:50px;" onMouseUp="terminar_combo(<?php echo $idprod_princ; ?>,<?php echo $cat_princ; ?>);"></p>
