 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idclienteprevio = intval($_GET['id']);

$consulta = "
select idcanalventacli, idcliente 
from cliente
where
idcliente = $idclienteprevio
and estado <> 6
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcanalventa = $rs->fields['idcanalventacli'];
$idclienteprevio = $rs->fields['idcliente'];

if ($idcanalventa > 0) {
    $consulta = "
    select *, canal_venta.canal_venta, canal_venta.idcanalventa,
    (select lista_precios_venta.lista_precio from  lista_precios_venta where idlistaprecio = canal_venta.idlistaprecio) as lista_precio
    from canal_venta 
    inner join canal_venta_perm on canal_venta_perm.idcanalventa = canal_venta.idcanalventa
    where 
    canal_venta_perm.idusuario = $idusu
    and canal_venta_perm.estado = 1 
    and canal_venta.estado = 1 
    and canal_venta.idcanalventa = $idcanalventa
    order by canal_venta.canal_venta asc
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lista_precio = $rs->fields['lista_precio'];
    $idlistaprecio = $rs->fields['idlistaprecio'];
    $idcanalventa = $rs->fields['idcanalventa'];
    //echo $idcanalventa;exit;
    if ($idcanalventa > 0) {
        if ($idlistaprecio > 0) {
            $consulta = "
            update productos_listaprecios
            set
            precio = 
                COALESCE(
                    (
                    select 
                    CASE redondeo_direccion
                    WHEN 'A' THEN CEIL(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
                    WHEN 'B' THEN FLOOR(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
                    ELSE
                        ROUND(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
                    END as redondeado
                    from productos_sucursales, lista_precios_venta
                    where
                    productos_sucursales.idproducto = productos_listaprecios.idproducto
                    and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
                    and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio
                    )
                ,0),
            reg_por = $idusu,
            reg_el = '$ahora'
            where
            idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
            and idlistaprecio > 1
            and idlistaprecio = $idlistaprecio
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        } else {
            echo "sin lista de precios asignada.";
            exit;
        }

        // asigna la sesion
        $_SESSION['idclienteprevio'] = $idclienteprevio;

        // destruye la sesion de canal venta por seguridad
        $_SESSION['idcanalventa'] = 0;
        $_SESSION['idcanalventa'] = null;
        unset($_SESSION['idcanalventa']);

        // destruye la sesion de lista precio por seguridad
        $_SESSION['idlistaprecio'] = 0;
        $_SESSION['idlistaprecio'] = null;
        unset($_SESSION['idlistaprecio']);

        header("location: gest_ventas_resto_caja.php");
        exit;

    } else {
        echo "Tu usuario no tiene permisos para el canal asignado a este cliente.";
        exit;
    }
} else {
    // destruye la sesion
    $_SESSION['idclienteprevio'] = 0;
    $_SESSION['idclienteprevio'] = null;
    unset($_SESSION['idclienteprevio']);

    // destruye la sesion de canal venta por seguridad
    $_SESSION['idcanalventa'] = 0;
    $_SESSION['idcanalventa'] = null;
    unset($_SESSION['idcanalventa']);

    // destruye la sesion de lista precio por seguridad
    $_SESSION['idlistaprecio'] = 0;
    $_SESSION['idlistaprecio'] = null;
    unset($_SESSION['idlistaprecio']);

    header("location: gest_ventas_resto_caja.php");
    exit;
}




?>
