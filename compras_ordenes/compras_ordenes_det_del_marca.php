<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");





$ocseria = intval($_POST['ocseria']);
if ($ocseria == 0) {
    echo "No se envio el id";
    exit;
}
$accion = substr(trim($_POST['accion']), 0, 1);

// consulta a la tabla
$consulta = "
select * 
from compras_ordenes_detalles
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum 
where 
ocseria = $ocseria
and compras_ordenes.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocseria = intval($rs->fields['ocseria']);
$ocnum = intval($rs->fields['ocnum']);
if ($ocseria == 0) {
    echo "Orden de compra finalizada, no se puede editar.";
    exit;
}






// validaciones basicas
$valido = "S";
$errores = "";

if ($accion == 'M') {
    $marca_borra = 1;
} else {
    $marca_borra = 0;
}


// si todo es correcto actualiza
if ($valido == "S") {

    $consulta = "
	update  compras_ordenes_detalles
	set 
	marca_borra = $marca_borra
	where
		ocseria=$ocseria
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //header("location: compras_ordenes_det.php?id=$ocnum");
    //exit;

}



$buscar = "
Select *, 
(select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
(
	select productos.barcode 
	from insumos_lista 
	inner join productos on productos.idprod_serial = insumos_lista.idproducto
	 where 
	 idinsumo = compras_ordenes_detalles.idprod
)  as barcode
from  compras_ordenes_detalles 
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
where 
compras_ordenes_detalles.ocseria=$ocseria 
and compras_ordenes.estado = 1
order by compras_ordenes_detalles.descripcion asc
limit 1
";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><?php if ($rscu->fields['marca_borra'] == 0) { ?>
                    <a href="javascript:marca_borrado(<?php echo $rscu->fields['ocseria']; ?>);" class="btn btn-sm btn-default" title="Marcar para borrar"   ><span class="fa fa-toggle-off"></span> No se Borrara</a>
					<?php } else { ?>
                    <a href="javascript:desmarca_borrado(<?php echo $rscu->fields['ocseria']; ?>);" class="btn btn-sm btn-default" title="Desmarcar (no borrar)"  ><span class="fa fa-toggle-on"></span> Se Borrara</a>
<?php } ?>