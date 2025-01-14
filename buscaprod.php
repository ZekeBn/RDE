 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
$texto = antisqlinyeccion($_POST['texto'], 'texto');
$texto = str_replace("'", "", $texto);

if ($texto != '') {
    $buscar = "Select descripcion,disponible,idprod from productos where descripcion like '%$texto%' order by descripcion asc";
    $rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tp = $rsp->RecordCount();

    if ($tp > 0) {
        //hay productos y mostramos un select
        ?>
        <div class="listaprod" id="listota">
        <ol id="lista2">
            
       <?php
           $c = 0;
        while (!$rsp->EOF) {
            $c++;
            $describe = trim($rsp->fields['descripcion']);
            $idp = $rsp->fields['idprod'];
            $dispon = $rsp->fields['disponible'];
            ?>
           <li>
            <a href="javascript:void(0)" onClick="seleccionarp(<?php echo $idp ?>,<?php echo $dispon ?>,<?php echo "'".$describe."'"?>)"><?php echo $describe ?></a>
         </li>
        
    <?php !$rsp->MoveNext();
        } ?>
        </ol>
        </div>
    <br />
    <input type="button" value="Listo" onClick="cerrar()" />
    <?php

    } else {


    }
}
?>
