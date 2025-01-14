 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

$dest = antixss($_POST['dest']);




// separar palabras
$texto = trim($_POST['prod']);
$len_texto = strlen($texto);
$texto_sql = antisqlinyeccion($texto, "text");
$whereadd_multiple = '';
if (trim($texto) != '') {
    $textos = explode(' ', $texto);
    foreach ($textos as $texto) {
        $texto = antisqlinyeccion($texto, "like");
        $whereadd_multiple .= " and  ( insumos_lista.descripcion LIKE _utf8 '%$texto%' COLLATE utf8_general_ci )
";
    }
}



// separar palabras

$producto = antisqlinyeccion($_POST['prod'], "text-notnull");
$codbar = antisqlinyeccion($_POST['codbar'], "text-notnull");
if (trim($_POST['prod']) != '') {
    //$whereadd="and insumos_lista.descripcion like '%$producto%'";
    $whereadd = $whereadd_multiple;
} else {
    $whereadd = "and (select barcode from productos where idprod_serial = ingredientes.idinsumo) = '$codbar'";
}

// el deposito depende de la configuracion en preferencias si toma la sucursal actual o el vendedor seleccionado
//$idsucursal=$sucursallogin;


$consulta = "
select insumos_lista.*,ingredientes.idingrediente, medidas.nombre as medida,
(select barcode from productos where idprod_serial = ingredientes.idinsumo) as codbar
from ingredientes 
inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where ingredientes.estado = 1
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
$whereadd

order by 
CASE WHEN
    substring(insumos_lista.descripcion from 1 for $len_texto) = $texto_sql
THEN
    0
ELSE
    1
END asc, 
insumos_lista.descripcion asc 

limit 20
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><div class="table-responsive" id="cuadro_busqueda">
<table width="100%" class="table table-bordered table-hover  bulk_action" id="carrito_busq">
<thead>
  <tr>
      <th></th>
    <th>id</th>
    <th>Ingrediente</th>
    <th>Medida</th>
    <th>Codigo Barras</th>
  </tr>
  </thead>
  <tbody>
<?php while (!$rs->EOF) { ?>
  <tr>
      <td><button type="button" class="btn  btn-default btn-xs" onMouseUp="seleccionar_item(<?php echo antixss($rs->fields['idingrediente']); ?>,'<?php echo $dest; ?>','<?php echo str_replace("'", "", antixss($rs->fields['descripcion'])); ?>',<?php echo antixss($rs->fields['idinsumo']); ?>);" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar" ><span class="fa fa-check-square-o"></span></button></td>
    <td><?php echo antixss($rs->fields['idinsumo']);  ?></td>
    <td><?php echo antixss($rs->fields['descripcion']); ?></td>
    <td><?php echo antixss($rs->fields['medida']); ?></td>
    <td><?php echo antixss($rs->fields['codbar']); ?></td>
  </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>

</div> 
