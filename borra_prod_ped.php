 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
$idventatmp = intval($_POST['idventatmp']);
$idtmpventares_cab = intval($_POST['idtmpventares_cab']);
$idmesa = intval($_POST['mesa']);
$idatc = intval($_POST['idatc']);
$rechazo = antisqlinyeccion(substr(trim($_POST['rechazo']), 0, 1), "text");
$idmotivorecha = antisqlinyeccion($_POST['idmotivorecha'], "int");

//echo "No se usa mas!"; /// usar borra_prod_ped_new.php
//exit;

//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
if ($idcaja == 0) {
    echo "<br /><br />Debes tener una caja abierta para borrar un producto.<br /><br />";
    exit;
}
if ($_POST['rechazo'] == 'S') {
    if (intval($_POST['idmotivorecha']) == 0) {

        echo "<br /><br />Debe indicar el motivo de rechazo.<br /><br />";
        exit;

    }
}

//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrar_ped = trim($rspref->fields['borrar_ped']);
$borrar_ped_cod = trim($rspref->fields['borrar_ped_cod']);

if ($idventatmp > 0 && $idtmpventares_cab > 0) {
    $buscar = "
        Select productos.descripcion,tmp_ventares.combinado,idprod_mitad1,idprod_mitad2, tmp_ventares.precio,
        idtmpventares_cab,tmp_ventares.cantidad,idventatmp,idtmpventares_cab,idproducto,
        (select tmp_ventares_cab.delivery_costo from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery_costo,
        (select tmp_ventares_cab.delivery from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery,
        (select tmp_ventares_cab.idusu from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as idusu
         from 
        tmp_ventares
        inner join productos on productos.idprod=tmp_ventares.idproducto
        where 
        tmp_ventares.idsucursal=$idsucursal 
        and tmp_ventares.idempresa=$idempresa 
        and tmp_ventares.idtmpventares_cab=$idtmpventares_cab
        and tmp_ventares.idventatmp=$idventatmp
        order by descripcion asc
        ";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    $tcuerpo = $rsbb->RecordCount();
    $idventatmp = intval($rsbb->fields['idventatmp']);
    $idtmpventares_cab = intval($rsbb->fields['idtmpventares_cab']);
    // si existe
    if ($idventatmp > 0 && $idtmpventares_cab > 0) {

        // si exige codigo para borrar
        if ($borrar_ped_cod == "S") {
            $codigo = md5(trim($_POST['cod']));
            $consulta = "
                select * 
                from codigos_borraped 
                where 
                codigo = '$codigo'
                and estado = 1
                and idusuario in (select idusu from usuarios where estado = 1)
                limit 1
                ";
            $rscod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if (intval($rscod->fields['idusuario']) == 0) {
                echo "<br /><br /><p align=\"center\">Codigo de autorizacion Incorrecto!</p><br /><br />";
                exit;
            }
        }

        // si esta permitido borrar
        if ($borrar_ped == 'S') {

            // busca los productos a borrar
            $consulta = "
                select * 
                from tmp_ventares 
                where
                tmp_ventares.idventatmp = $idventatmp 
                and tmp_ventares.idtmpventares_cab = $idtmpventares_cab
                and tmp_ventares.idempresa=$idempresa 
                and borrado = 'N'
                ;
                ";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // recorre borra
            while (!$rs->EOF) {

                $idventatmp = $rs->fields['idventatmp'];
                $idtmpventaresagregado = intval($rs->fields['idtmpventaresagregado']);

                // borra los detalles que contienen ese producto
                $consulta = "
                    update tmp_ventares
                    set 
                    borrado = 'S',
                    borrado_mozo = 'S',
                    borrado_mozo_el = '$ahora',
                    borrado_mozo_por = $idusu,
                    borrado_mozo_idcaja = $idcaja,
                    rechazo = $rechazo,
                    idmotivorecha = $idmotivorecha
                    where
                    idventatmp = $idventatmp
                    ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // borra los agregados relacionados al idventatmp principal
                $consulta = "
                    update tmp_ventares
                    set 
                    borrado = 'S',
                    borrado_mozo = 'S',
                    borrado_mozo_el = '$ahora',
                    borrado_mozo_por = $idusu,
                    borrado_mozo_idcaja = $idcaja,
                    rechazo = $rechazo,
                    idmotivorecha = $idmotivorecha
                    where
                    idventatmp_princ_delagregado = $idventatmp
                    ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // borra los agregados de la tabla de agregados
                $consulta = "
                    delete from tmp_ventares_agregado
                    where
                    idventatmp = $idventatmp
                    ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                if ($idtmpventaresagregado > 0) {
                    $consulta = "
                        delete from tmp_ventares_agregado
                        where
                        idtmpventaresagregado = $idtmpventaresagregado
                        ";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }


                $rs->MoveNext();
            }


            $consulta = "
                update tmp_ventares 
                set 
                borrado = 'S',
                borrado_mozo = 'S',
                borrado_mozo_el = '$ahora',
                borrado_mozo_por = $idusu,
                borrado_mozo_idcaja = $idcaja,
                rechazo = $rechazo,
                idmotivorecha = $idmotivorecha
                where 
                tmp_ventares.idventatmp = $idventatmp 
                and tmp_ventares.idtmpventares_cab = $idtmpventares_cab
                and tmp_ventares.idempresa=$idempresa 
                ";
            //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));



            // verifica que luego de borrar hayan todavia productos en el pedido cabecera
            $consulta = "
                select tmp_ventares.idtmpventares_cab
                from tmp_ventares
                where 
                tmp_ventares.idtmpventares_cab = $idtmpventares_cab
                and tmp_ventares.idempresa=$idempresa 
                and tmp_ventares.borrado = 'N'
                and tmp_ventares.borrado_mozo = 'N'
                ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si no hay borra el pedido
            if (intval($rsex->fields['idtmpventares_cab']) == 0) {
                $update = "
                    Update tmp_ventares_cab 
                    set 
                    estado=6, 
                    anulado_el='$ahora', 
                    anulado_por=$idusu,
                    anulado_idcaja = $idcaja,
                    monto = 0
                    where 
                    tmp_ventares_cab.idtmpventares_cab=$idtmpventares_cab
                    and tmp_ventares_cab.idempresa = $idempresa 
                    and tmp_ventares_cab.idsucursal = $idsucursal
                    ";
                $conexion->Execute($update) or die(errorpg($conexion, $update));

                // si es mesa
                if ($idmesa > 0) {
                    // busca si hay otros pedidos activos en esa mesa
                    $buscar = "
                        Select 
                        idtmpventares_cab
                         from 
                        tmp_ventares
                        inner join productos on productos.idprod=tmp_ventares.idproducto
                        where 
                        tmp_ventares.idsucursal=$idsucursal 
                        and tmp_ventares.borrado = 'N'
                        and tmp_ventares.idtmpventares_cab in (
                                                        select idtmpventares_cab
                                                        from tmp_ventares_cab
                                                        where
                                                        idsucursal = $idsucursal
                                                        and idempresa = $idempresa
                                                        and finalizado = 'S'
                                                        and registrado = 'N'
                                                        and estado = 1
                                                        and idmesa=$idmesa
                                                        )
                        order by productos.descripcion asc
                        ";
                    $rspedmes = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                    // si hay responde que muestre
                    if (intval($rspedmes->fields['idtmpventares_cab']) > 0) {
                        echo "OK";
                        exit;
                        // si no hay
                    } else {
                        if ($idatc == 0) {
                            echo "<br /><br /><p align=\"center\">LA TOTALIDAD DE PEDIDOS DE LA MESA FUE BORRADO.</p><br /><br />";
                            //echo $buscar;
                            exit;
                        } else {
                            echo "OK";
                            exit;
                        }
                    }

                }


                echo "<br /><br /><p align=\"center\">PEDIDO BORRADO</p><br /><br />";
                exit;
                // si hay actualiza el monto del pedido
            } else {
                /*$consulta="
                update tmp_ventares_cab
                set
                monto = (
                            COALESCE
                            (
                                (
                                    select sum(precio) as total_monto
                                    from tmp_ventares
                                    where
                                    tmp_ventares.idempresa = $idempresa
                                    and tmp_ventares.idsucursal = $idsucursal
                                    and tmp_ventares.borrado = 'N'
                                    and tmp_ventares.borrado_mozo = 'N'
                                    and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                                )
                            ,0)
                            +
                            COALESCE
                            (
                                (
                                    SELECT sum(precio_adicional) as montototalagregados
                                    FROM
                                    tmp_ventares_agregado
                                    where
                                    idventatmp in
                                    (
                                        select idventatmp
                                        from tmp_ventares
                                        where
                                        tmp_ventares.idempresa = $idempresa
                                        and tmp_ventares.idsucursal = $idsucursal
                                        and tmp_ventares.borrado = 'N'
                                        and tmp_ventares.borrado_mozo = 'N'
                                        and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                                    )
                                )
                            ,0)
                        )
                WHERE
                idtmpventares_cab = $idtmpventares_cab
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
                $consulta = "
                    update tmp_ventares_cab 
                    set 
                    monto = (
                                COALESCE
                                (
                                    (
                                        select sum(subtotal) as total_monto
                                        from tmp_ventares
                                        where
                                        tmp_ventares.idempresa = $idempresa
                                        and tmp_ventares.idsucursal = $idsucursal
                                        and tmp_ventares.borrado = 'N'
                                        and tmp_ventares.borrado_mozo = 'N'
                                        and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                                    )
                                ,0)
                                
                            )
                    WHERE
                    idtmpventares_cab = $idtmpventares_cab    
                    ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                echo "OK";
                exit;
            }


        } else {
            echo "<br /><br />Acceso Denegado! tu usuario no tiene permisos para borrar pedidos.<br /><br />";
            exit;
        }
    } else {
        echo "<br /><br />Pedido inexistente o ya fue borrado.<br /><br />";
        exit;
    }



} else {
    echo "<br /><br />No se envio ningun parametro.<br /><br />";
    exit;
}

?>
