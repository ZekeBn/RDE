 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "84";
require_once("includes/rsusuario.php");

$valido = "S";
$errores = "";

$iddeposito = intval($_POST['iddeposito']);
$idproducto = intval($_POST['idproducto']);
$idsucursal_imp = intval($_POST['idsucursal']);

//print_r($_POST);exit;


if ($iddeposito == 0) {
    $valido = "N";
    $errores = "-No se indico el deposito.".$saltolinea;
}
if ($idproducto == 0) {
    $valido = "N";
    $errores = "-No se indico el producto.".$saltolinea;
}
if ($idsucursal_imp == 0) {
    $valido = "N";
    $errores = "-No se indico la sucursal.".$saltolinea;
}

// valida que la imipresora pertenezca a la sucursal
/*$consulta="
SELECT *
FROM producto_deposito
where
borrado = 'N'
and idsucursal = $idsucursal_imp
and iddeposito  = $iddeposito
limit 1
";
$rseximp=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idimpresora=intval($rseximp->fields['idimpresoratk']);
if($idimpresora == 0){
    $valido="N";
    $errores="-La impresora indicada no corresponde a la sucursal.".$saltolinea;
}*/

if ($valido == 'S') {

    // busca si esta asignado a otro deposito
    $consulta = "
    select * 
    from producto_deposito 
    where 
    idproducto = $idproducto 
    and idsucursal = $idsucursal_imp
    and iddeposito <> $iddeposito
    limit 1
    ";
    //echo $consulta;exit;
    $rsotr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // recorre y borra
    while (!$rsotr->EOF) {
        $idproducto_otr = intval($rsotr->fields['idproducto']);
        $iddeposito_otr = intval($rsotr->fields['iddeposito']);
        $idsucursal_otr = intval($rsotr->fields['idsucursal']);
        // si existe borra
        if ($idproducto_otr > 0) {
            // borrar el producto para ese deposito
            $consulta = "
            delete from producto_deposito where idproducto = $idproducto_otr and idsucursal = $idsucursal_otr
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            // loguear borrado
            $consulta = "
            insert into producto_deposito_log
            (idproducto, iddeposito, idsucursal, accion, registrado_por, registrado_el)
            values
            ($idproducto_otr, $iddeposito_otr, $idsucursal_otr, 'B', $idusu, '$ahora')
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        $rsotr->MoveNext();
    }

    // busca si existe en la bd
    $consulta = "
    select * 
    from producto_deposito 
    where 
    idproducto = $idproducto 
    and iddeposito = $iddeposito
    and idsucursal = $idsucursal_imp
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto_ex = intval($rs->fields['idproducto']);
    // si ya existe borrar
    if ($idproducto_ex > 0) {
        $accion = "B"; //borrar
    } else {
        $accion = "A"; // agregar
    }


    // si existe borra
    if ($idproducto_ex > 0) {


        // borrar el producto para ese deposito
        $consulta = "
        delete from producto_deposito where iddeposito = $iddeposito and idproducto = $idproducto and idsucursal= $idsucursal_imp
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // loguear borrado
        $consulta = "
        insert into producto_deposito_log
        (idproducto, iddeposito, idsucursal, accion, registrado_por, registrado_el)
        values
        ($idproducto, $iddeposito, $idsucursal_imp, 'B', $idusu, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si no existe registra
    } else {


        // agrega
        $consulta = "
        INSERT INTO producto_deposito
        (idproducto, iddeposito, idsucursal) 
        VALUES 
        ($idproducto,$iddeposito,$idsucursal_imp)
        ";
        //echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // loguear agregado
        $consulta = "
        insert into producto_deposito_log
        (idproducto, iddeposito, idsucursal, accion, registrado_por, registrado_el)
        values
        ($idproducto, $iddeposito, $idsucursal_imp, 'A', $idusu, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    }



}


// busca en la bd el nuevo valor
$consulta = "
select idproducto, iddeposito
from producto_deposito 
where 
idproducto = $idproducto
and iddeposito = $iddeposito
and idsucursal = $idsucursal_imp
limit 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


require_once('productos_depo_suc_asignar_suc_mini.php');

?>
