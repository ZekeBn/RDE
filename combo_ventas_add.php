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

$idlistacombo = intval($_POST['idlista']);
$idproducto = intval($_POST['idprod']);

// busca si existe la lista
$consulta = "
select * from combos_listas where idlistacombo = $idlistacombo and idempresa = $idempresa and estado = 1
";
$rscombo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idlistacombo = $rscombo->fields['idlistacombo'];
$cantidadmax = $rscombo->fields['cantidad'];


// si existe
if ($idlistacombo > 0) {
    $consulta = "
    select * 
    from productos 
    inner join combos_listas_det on productos.idprod_serial = combos_listas_det.idproducto
    where 
    productos.idempresa = $idempresa
    and combos_listas_det.idempresa= $idempresa 
    and productos.combo <> 'S'
    and productos.borrado = 'N'
    and combos_listas_det.idlistacombo = $idlistacombo
    and combos_listas_det.idproducto = $idproducto
    and combos_listas_det.estado = 1
    order by productos.descripcion asc
    limit 1
    ";
    $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si forma parte de la lista
    $idlistacombo = $rsprod->fields['idlistacombo'];
    if ($idlistacombo > 0) {


        // validar que no haya llegado al maximo permitido para esa lista
        $consulta = "
        select count(*) as total
        from tmp_combos_listas
        where
        tmp_combos_listas.idsucursal = $idsucursal
        and tmp_combos_listas.idusuario = $idusu
        and tmp_combos_listas.idempresa = $idempresa
        and tmp_combos_listas.idlistacombo = $idlistacombo
        and tmp_combos_listas.idventatmp is null
        ";
        $rscuenta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totalbd = intval($rscuenta->fields['total']);

        // si no supero el maximo permitido para el grupo
        if ($totalbd < $cantidadmax) {
            // insertar en tabla temporal
            $consulta = "
            INSERT INTO tmp_combos_listas
            (idlistacombo, idventatmp, idproducto, idempresa, idsucursal, idusuario, registrado_el)
            VALUES
            ($idlistacombo,NULL,$idproducto,$idempresa,$idsucursal,$idusu,'$ahora')
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            // busca si inserto y devuelve la cantidad de ese producto para esa lista para esa empresa para ese usuario y sucursal
            $consulta = "
            select count(*) as total
            from tmp_combos_listas
            where
            tmp_combos_listas.idproducto = $idproducto
            and tmp_combos_listas.idsucursal = $idsucursal
            and tmp_combos_listas.idusuario = $idusu
            and tmp_combos_listas.idempresa = $idempresa
            and tmp_combos_listas.idlistacombo = $idlistacombo
            and tmp_combos_listas.idventatmp is null
            ";
            $rsinsertado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // luego de insertar se suma 1 y se verifica si llego al maximo
            if ($totalbd + 1 < $cantidadmax) {
                echo $rsinsertado->fields['total'];
            } else {
                echo "LISTO";
                exit;
            }

        } else {
            echo "MAX"; // cantidad maxima alcanzada
            exit;
        }

    }
}
?>
