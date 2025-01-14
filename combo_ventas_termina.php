 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idprod_princ = intval($_POST['idprod_princ']);
$combinado = antisqlinyeccion("N", "text");
$idlistaprecio = intval($_SESSION['idlistaprecio']);
$idcanalventa = intval($_SESSION['idcanalventa']);

if ($idcanalventa > 0) {
    $consulta = "
    select idlistaprecio, idcanalventa, canal_venta 
    from canal_venta 
    where 
    idcanalventa = $idcanalventa 
    and estado = 1
    limit 1
    ";
    $rscv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlistaprecio = intval($rscv->fields['idlistaprecio']);
}
$seladd_lp = " productos_sucursales.precio ";
if ($idlistaprecio > 0) {
    $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
    $whereadd_lp = "
    and productos_listaprecios.idsucursal = $idsucursal 
    and productos_listaprecios.idlistaprecio = $idlistaprecio 
    and productos_listaprecios.estado = 1
    ";
    $seladd_lp = " productos_listaprecios.precio ";
}

// busca si existen los grupos
$consulta = "
select *,
    (
        select count(*) as total
        from tmp_combos_listas
        where
        tmp_combos_listas.idsucursal = $idsucursal
        and tmp_combos_listas.idusuario = $idusu
        and tmp_combos_listas.idempresa = $idempresa
        and tmp_combos_listas.idlistacombo = combos_listas.idlistacombo
        and tmp_combos_listas.idventatmp is null
    ) as total
from combos_listas 
where 
idproducto = $idprod_princ 
and idempresa = $idempresa 
and estado = 1
";
$rscombo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idlistacombo = $rscombo->fields['idlistacombo'];

// si existe
if ($idlistacombo > 0) {
    $valido = "S";

    // recorre y valida que cada grupo haya llegado a su maximo
    while (!$rscombo->EOF) {

        $maxagrega = intval($rscombo->fields['cantidad']);
        $totagregado = intval($rscombo->fields['total']);


        // verifica si se agrego el maximo
        if ($maxagrega != $totagregado) {
            $valido = "N";
            echo "NOVALIDO";
            exit;
        }


        $rscombo->MoveNext();
    }




    // si todo es valido
    if ($valido == "S") {

        // busca precio del combo
        $consulta = "
        select * , $seladd_lp as p1
        from productos 
        inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
        $joinadd_lp
        where 
        borrado = 'N' 
        and combo = 'S'
        and productos.idempresa = $idempresa
        and productos.idprod_serial = $idprod_princ
        and productos_sucursales.idsucursal = $idsucursal 
        $whereadd_lp
        ";
        $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $precio = $rsprod->fields['p1'];

        // inserta en ventas temporal
        $consulta = "
        INSERT INTO tmp_ventares
        (idproducto, idtipoproducto, cantidad, precio, subtotal, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2, combo) 
        VALUES 
        ($idprod_princ, 2, 1,$precio, $precio, '$ahora', $idusu, 'N', $idsucursal, $idempresa, 'N', 'N', $combinado, NULL, NULL, 'S')
        ;
        ";
        //echo $consulta;
        //exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // busca el id temporal insertado
        $consulta = "
        select idventatmp
        from tmp_ventares  
        where 
        usuario = $idusu 
        and idsucursal = $idsucursal
        and idempresa = $idempresa
        and combo = 'S'
        order by idventatmp desc
        limit 1
        ";
        $rsmaxid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $maxid = $rsmaxid->fields['idventatmp'];

        //actualiza en la tabla temporal de combos listas por cada lista
        $rscombo->MoveFirst();
        while (!$rscombo->EOF) {
            $idlistacombo = $rscombo->fields['idlistacombo'];
            $consulta = "
            update tmp_combos_listas
            set 
            tmp_combos_listas.idventatmp = $maxid
            where
            tmp_combos_listas.idsucursal = $idsucursal
            and tmp_combos_listas.idusuario = $idusu
            and tmp_combos_listas.idempresa = $idempresa
            and tmp_combos_listas.idlistacombo = $idlistacombo
            and tmp_combos_listas.idventatmp is null
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $rscombo->MoveNext();
        }

        //traemos los componentes dle combo
        /*$idventatmp=intval($rs->fields['idventatmp']);


        $buscar="Select *,(select descripcion from productos where productos.idprod_serial=tmp_combos_listas.idproducto) as describeprod ,
        (select count(idproducto) as cantidad from agregado where idproducto=tmp_combos_listas.idproducto) as cantiagregado
        from tmp_combos_listas
        where idventatmp=$maxid

        ";
        $rsl= $conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

        while (!$rsl->EOF){
            //insertamos en tmpventares
            $idprodcomo=intval($rsl->fields['idproducto']);
            $idunicol=intval($rsl->fields['idlistacombo_tmp']);




            $consulta="
            INSERT INTO tmp_ventares
            (idproducto, idtipoproducto, cantidad, precio, subtotal, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2, combo)
            VALUES
            ($idprodcomo, 9, 1,0, 0, '$ahora', $idusu, 'N', $idsucursal, $idempresa, 'N', 'N', 'N', NULL, NULL, 'N')
            ;
            ";

            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            $buscar="select idventatmp
            from tmp_ventares
            where
            usuario = $idusu
            and idsucursal = $idsucursal
            and idempresa = $idempresa
            and idtipoproducto=9
            order by idventatmp desc
            limit 1
            ";
            $rsmaxid=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            $maxid=$rsmaxid->fields['idventatmp'];

            $update="update tmp_combos_listas set idventatmp_partes =$maxid where idproducto=$idprodcomo and idlistacombo_tmp=$idunicol";
            $conexion->Execute($update) or die(errorpg($conexion,$update));



        $rsl->MoveNext();}

        */
        echo "OK";

    }


}
?>
