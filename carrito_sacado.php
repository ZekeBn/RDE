 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idventatmp = antisqlinyeccion($_POST['idvt'], "int");
$idproducto = antisqlinyeccion($_POST['idprod'], "int");
$idingrediente = antisqlinyeccion($_POST['iding'], "int");

$prod_1 = antisqlinyeccion($_POST['prod_1'], "int");
$prod_2 = antisqlinyeccion($_POST['prod_2'], "int");
$fechahora = antisqlinyeccion(date("Y-m-d H:i:s"), "text");
$usuario = $idusu;
$idsucursal = $idsucursal;
$idempresa = $idempresa;

//print_r($_POST);


// hacer consulta y obtener
$consulta = "
SELECT recetas_detalles.idprod as idproducto, recetas_detalles.ingrediente as idingrediente, recetas_detalles.alias, insumos_lista.descripcion, recetas_detalles.cantidad, medidas.nombre
FROM recetas_detalles
inner join ingredientes on ingredientes.idingrediente = recetas_detalles.ingrediente
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
inner join medidas on insumos_lista.idmedida=medidas.id_medida
WHERE
recetas_detalles.idprod = $idproducto
and recetas_detalles.ingrediente = $idingrediente
and recetas_detalles.sacar = 'S'
and insumos_lista.idempresa = $idempresa
and recetas_detalles.idempresa = $idempresa
";
//echo $consulta;
$rsagregado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$precio_adicional = antisqlinyeccion($rsagregado->fields['precio_adicional'], "float");
$alias = antisqlinyeccion($rsagregado->fields['alias'], "text");


// candidad debe ser el gramage de la receta
if ($_POST['prod_1'] > 0 && $_POST['prod_2'] > 0) {
    $consulta = "
    select sum(cantidad) as cantidad from recetas_detalles where (idprod = $prod_1 or idprod = $prod_2) and ingrediente = $idingrediente
    and recetas_detalles.idempresa = $idempresa
    ";
    //echo $consulta;
    $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad = antisqlinyeccion($rsrec->fields['cantidad'], "int");
} else {
    $consulta = "
    select cantidad from recetas_detalles where idprod = $idproducto and ingrediente = $idingrediente and recetas_detalles.idempresa = $idempresa
    ";
    //echo $consulta;
    $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad = antisqlinyeccion($rsrec->fields['cantidad'], "int");
}


$consulta = "
INSERT INTO tmp_ventares_sacado
(idventatmp, idproducto, idingrediente, alias, cantidad, fechahora)
VALUES 
($idventatmp,$idproducto, $idingrediente, $alias, $cantidad, $fechahora)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// buscar cantidad total de ese producto y responder
$consulta = "
select 
count(idventatmp) as total
from tmp_ventares_sacado
where 
idventatmp = $idventatmp
and tmp_ventares_sacado.idingrediente = $idingrediente
and tmp_ventares_sacado.idproducto = $idproducto
and tmp_ventares_sacado.idventatmp in (
                    select idventatmp 
                    from tmp_ventares 
                    where 
                    idventatmp = $idventatmp
                    and idempresa = $idempresa 
                    and idsucursal = $idsucursal
                 )
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

echo intval($rs->fields['total']);


?>
