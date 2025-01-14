 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
//Debe venir arametrizado, la cantidad de digitos que van a definir la cantidad tomada de la etiqueta para el peso
$tfinaldigpeso = 5;
//tomamos x defecto del post y solo cambia si es pesable
$producto = trim($_POST['prod']);
$cantidad = antisqlinyeccion($_POST['cant'], "float");
$partir = intval($_POST['partir']);
$descarte = intval(substr(trim($_POST), -1, 1));
//echo $descarte;
//exit;
/*
CODIGO DE BARRA: 2000097003157
20 - pesable o unitario, unitario: 21 pesable: 20
00097 - codigo de producto
00315 - peso (2,3) 2 Enteros con 3 Decimales
7 - descarte (final de cadena esta al pedo pero tiene que estar si o si)
*/
if ($partir == 2) {
    //proviene del uso de cod de barras  etiquetas desde el panel central y traemos la preferencia de cod plu pesable
    $buscar = "Select codplu_pesable,total_numeros_cod from preferencias where idempresa=$idempresa";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $codigo_pesable = intval($rsbb->fields['codplu_pesable']);

    //total de digitos que componen la cadena
    $bb = strlen($codigo_pesable);
    //Si esta definido el codigo pesable
    if ($codigo_pesable > 0) {
        //sse definio y vemos el tamanho de los numeros que componen el codigo en la etiqueta
        $cantcodigo = intval($rsbb->fields['total_numeros_cod']);
        //segun éste numero, cortamos la cadena
        if ($cantcodigo == 0) {
            //No se definio, y usamos x defecto 5 mas los dos del cod pesable
            $cantcodigo = 5 + $bb;
        } else {
            $cantcodigo = $cantcodigo + $bb;
        }
        //obtenido el total gral de digitos del codigo, inclyendo el PLU, extraemos
        $cadena = $producto;

        //extraemos el inicial para ver si es pesable
        $cadenapesa = substr($cadena, 0, $bb);
        if ($cadenapesa == $codigo_pesable && $descarte > 0) {
            //es pesable y extraemos el id del producto y la cantidad
            $codigoprod = substr($cadena, $bb, ($cantcodigo - $bb));
            $cc = strlen($codigoprod);
            //Inicio para cantidad
            $cc = $cc + $bb;
            $cantipeso = substr($cadena, $cc, $tfinaldigpeso);
            if (intval($cantipeso < 1000)) {
                $cantipeso = '00.'.intval($cantipeso);
                $cantipeso = floatval($cantipeso);
            } else {
                $numeradorp = substr($cantipeso, 0, 2); // numerador
                $decimalesp = substr($cantipeso, 2, 3); // numerador
                $cantipeso = $numeradorp.'.'.$decimalesp; // peso completo
                $cantipeso = floatval($cantipeso);
            }

            //No esta como pesable de acuerdo a la cadena definida
        } else {
            // busca como codigo de barra
            $consulta = "
            select productos.idprod_serial,productos.idmedida, productos.idtipoproducto, productos_sucursales.precio as precio
            from productos 
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
            where
            productos.idprod_serial is not null
            and productos.idempresa = $idempresa
            and productos.barcode = '$producto'
            and productos.borrado = 'N'
            
            and productos_sucursales.idsucursal = $idsucursal 
            and productos_sucursales.idempresa = $idempresa
            and productos_sucursales.activo_suc = 1
            
            order by productos.descripcion asc
            ";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //echo $consulta;
            //exit;
            // existe codigo de barra
            if (intval($rs->fields['idprod_serial']) > 0) {
                $codigoprod = $rs->fields['idprod_serial'];
                $cantipeso = floatval($_POST['cant']);
            } else {
                //No esta como pesable de acuerdo a la cadena definida ni existe codigo de barra
                $codigoprod = intval($_POST['prod']);
                $cantipeso = floatval($_POST['cant']);
            }
        }

    }
    //Terminado todo,como entro x partir asignamos
    $producto = intval($codigoprod);
    $cantidad = floatval($cantipeso);
    //echo cantipeso;exit;
}
//exit;
//$producto=antisqlinyeccion($_POST['prod'],"int");
//$cantidad=antisqlinyeccion($_POST['cant'],"float");
$precio = antisqlinyeccion($_POST['precio'], "float");
$fechahora = antisqlinyeccion(date("Y-m-d H:i:s"), "text");
$usuario = $idusu;
$idsucursal = $idsucursal;
$idempresa = $idempresa;
$receta_cambiada = antisqlinyeccion("N", "text");
$registrado = antisqlinyeccion("N", "text");
$borrado = antisqlinyeccion("N", "text");
$combinado = antisqlinyeccion("N", "text");
;
$prod_1 = "NULL";
$prod_2 = "NULL";
$subtotal = 0;
// buscar producto si es combinado

if ($_POST['prod_1'] > 0 && $_POST['prod_2'] > 0) {
    $prod_1 = antisqlinyeccion($_POST['prod_1'], "int");
    $prod_2 = antisqlinyeccion($_POST['prod_2'], "int");

    $consulta = "
    select (sum(productos_sucursales.precio)/2) as precio, productos.idmedida, productos.idtipoproducto
    from productos 
    inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
    where
    idprod_serial is not null
    and productos.idempresa = $idempresa
    and (productos.idprod_serial = $prod_1 or productos.idprod_serial = $prod_2)
    
    and productos_sucursales.idsucursal = $idsucursal 
    and productos_sucursales.idempresa = $idempresa
    and productos_sucursales.activo_suc = 1
    
    order by productos.descripcion asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $precio = antisqlinyeccion(redondear_tresceros($rs->fields['precio']), "int");
    $subtotal = $precio;
    $combinado = antisqlinyeccion("S", "text");
    $idtipoproducto = $rs->fields['idtipoproducto'];
} else {

    $consulta = "
    select productos.idmedida, productos.idtipoproducto, productos_sucursales.precio as precio
    from productos 
    inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
    where
    productos.idprod_serial is not null
    and productos.idempresa = $idempresa
    and productos.idprod_serial = $producto
    
    and productos_sucursales.idsucursal = $idsucursal 
    and productos_sucursales.idempresa = $idempresa
    and productos_sucursales.activo_suc = 1
    
    order by productos.descripcion asc
    ";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $precio = antisqlinyeccion($rs->fields['precio'], "float");
    $medida = intval($rs->fields['idmedida']);
    $idtipoproducto = $rs->fields['idtipoproducto'];
    //echo $medida;
    //exit;
    //KILLO y porcion
    if ($medida != 4) {
        //Precio en kls a gramos
        $preciogr = ($precio / 1000);
        $subtotal = (($preciogr * 1000) * $cantidad);
    } else {
        $subtotal = ($precio * $cantidad);
    }
}
$subtotal = round($subtotal);
if (!function_exists("isNatural")) {
    function isNatural($var)
    {
        return preg_match('/^[0-9]+$/', (string )$var) ? true : false;
    }
}


//echo $medida;
// busca si la unidad de medida del producto es unitaria
if ($medida == 4) {
    // si es unitaria valida que no tenga decimales
    if (!isNatural($cantidad)) {
        echo "Error! No puede fraccionar un producto unitario.";
        exit;
    }
    if ($cantidad < 1) {
        echo "Error! un producto unitario debe tener una cantidad compuesta por un numero natural mayor a 0.";
        exit;
    }
    // para evitar while gigante que colapse el sistema,
    //si permitimos esto se debe registar en cantidad con 1 solo registro y no se podra variar la receta
    if ($cantidad > 1000) {
        echo "Error! no se puede vender una cantidad tan grande.";
        exit;
    }
    // si son menos de 10 genera 1 registro por cada uno
    if ($cantidad < 10) {
        // si es unitaria y la cantidad es mayor a 1 genera 1 registro por cada uno
        for ($i = 1;$i <= $cantidad;$i++) {
            //echo $i;

            $consulta = "
            INSERT INTO tmp_ventares
            (idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal) 
            VALUES 
            ($producto, $idtipoproducto,1,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$precio)
            ;
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //echo $consulta;
        }
    } else {
        // inserta 1 vez
        $consulta = "
        INSERT INTO tmp_ventares
        (idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal) 
        VALUES 
        ($producto, $idtipoproducto,$cantidad,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$subtotal)
        ;
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

} else {
    // inserta 1 vez
    $consulta = "
    INSERT INTO tmp_ventares
    (idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal) 
    VALUES 
    ($producto, $idtipoproducto,$cantidad,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$subtotal)
    ;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}









// buscar cantidad total de ese producto y responder
$consulta = "
select 
sum(cantidad) as total
from tmp_ventares 
where 
registrado = 'N'
and usuario = $usuario
and idproducto = $producto
and borrado = 'N'
and finalizado = 'N'
and idsucursal = $idsucursal
and idempresa = $idempresa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

echo floatval($rs->fields['total']);


?>
