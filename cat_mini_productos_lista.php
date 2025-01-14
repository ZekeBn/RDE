 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
$add = "";
$idmesa = 0;

//print_r($_POST);
if ($_POST['texto'] != '') {
    $texto = antisqlinyeccion($_POST['texto'], 'texto');
    $texto = str_replace("'", "", $texto);
    $add = " and descripcion like('%$texto%') ";
}
if (intval($idcategoria) > 0) {
    $add .= " and  idcategoria=$idcategoria ";
} else {
    //POST
    if (intval($_POST['idcategoria']) > 0) {
        $idcategoria = intval($_POST['idcategoria']);
        $add .= " and idcategoria=$idcategoria ";
    }
}
//echo $idcategoria;exit;
if ($idsubcategoria > 0) {
    $add .= " and idsubcate=$idsubcategoria ";
} else {
    //POST
    if (intval($_POST['idsubcate']) > 0) {
        $idsubcategoria = intval($_POST['idsubcate']);
        $add .= " and idsubcate=$idsubcategoria ";
    }
}

if ($add == '') {
    $limite = " limit 100";
}


$buscar = "
Select * , productos_sucursales.precio as p1
from productos 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
where 
productos.borrado = 'N' 
            and productos_sucursales.idsucursal = $idsucursal 
            and productos_sucursales.idempresa = $idempresa
            and productos_sucursales.activo_suc = 1
$add 
order by productos.descripcion asc 
$limite";
$rsproducto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
?>
<div style="width: 100%; overflow-y: scroll;overflow-x: scroll;">
<table width="90%" class="table table-bordered">
<thead>
<tr>
  
  <th width="46%" height="36">Descripcion</th>
    <th width="31%">Cantidad</th>
  <th width="23%">Precio</th>
  </tr>
</thead>
<tbody>



<?php
while (!$rsproducto->EOF) {
    //$imag="../gfx/productos/prod_".$rsproducto->fields['idprod_serial'].".jpg";
    //if(!file_exists($imag)){
    //$imag="../gfx/productos/prod_0.jpg";
    //}
    $i++;
    ?>
     
<?php
        //Combinado simple debe volar

    $enla = "";
    if ($rsproducto->fields['idtipoproducto'] == 1) { // producto
        $enla = "onClick=\"apretarauto('".$rsproducto->fields['idprod_serial']."',0,0,$idmesa);\" ";
    } elseif ($rsproducto->fields['idtipoproducto'] == 2) {  // combo
        $enla = "onClick=\"apretar_combo('".$rsproducto->fields['idprod_serial']."');\" ";
    } elseif ($rsproducto->fields['idtipoproducto'] == 3) {  // combinado simple NO USAR MAS
        $enla = "onClick=\"apretar_combinado('".$rsproducto->fields['idprod_serial']."',$idmesa);\" ";
    } elseif ($rsproducto->fields['idtipoproducto'] == 4) {  // combinado extendido
        $enla = "onClick=\"apretar_combinado('".$rsproducto->fields['idprod_serial']."',$idmesa);\" ";
    } else { // por defecto producto
        $enla = "onClick=\"apretarauto('".$rsproducto->fields['idprod_serial']."',0,0,$idmesa);\" ";
    }
    $idtemporal = $rsproducto->fields['idprod_serial'];
    ?>         

<tr >

        
    <td><?php echo $rsproducto->fields['descripcion']?></td>
    <td><input  type="number" name="can_<?php echo $rsproducto->fields['idprod_serial']; ?>" id="can_<?php echo $rsproducto->fields['idprod_serial']; ?>" value="" onKeyUp="seleccionar(<?php echo $rsproducto->fields['idprod_serial']; ?>,this.value)" />
      <a id="enla_<?php echo $rsproducto->fields['idprod_serial']; ?>" <?php echo $enla; ?>></a></td>
    <td><input type="hidden" name="preciolis_<?php echo $rsproducto->fields['idprod_serial']; ?>" id="preciolis_<?php echo $rsproducto->fields['idprod_serial']; ?>" value="<?php echo $rsproducto->fields['p1']; ?>" />
        <?php echo formatomoneda($rsproducto->fields['p1']); ?>
    <?php //echo $rsproducto->fields['idtipoproducto']?></td>
    </tr>

<?php $rsproducto->MoveNext();
}?>

</tbody>
</table>
    </div>
