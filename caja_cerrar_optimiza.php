 <?php
// INICIO OPTIMIZADOR DE TABLAS
// busca si ya se optimizo el dia de hoy
$ahorad = date("Y-m-d");
$consulta = "
SELECT `idoptiminizador`, `idcaja`, `fechasola`, `registrado_el` 
FROM `optimizador_cierre_caja` 
WHERE
fechasola = '$ahorad'
limit 1
";
$rsopt = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idoptiminizador = intval($rsopt->fields['idoptiminizador']);
// si aun no se optimizo hoy
if ($idoptiminizador == 0) {

    //
    // marca registros viejos como borrados
    $consulta = "
    update tmp_ventares_cab 
    set 
    estado = 6 
    where 
    idventa is null 
    and date(fechahora) < date_add(date(NOW()), INTERVAL -60 DAY)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    update  tmp_ventares 
    set borrado = 'S'
    WHERE
    idtmpventares_cab is null
     and registrado = 'N'
     and fechahora < date_add(date(NOW()), INTERVAL -60 DAY) 
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // pimero mover
    $consulta = "
    insert into tmp_ventares_bak
    select * from tmp_ventares 
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
    and (registrado = 'S' or borrado = 'S')
    and tmp_ventares.idventatmp not in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
    limit 100000
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    insert into tmp_ventares_bak
    select * from tmp_ventares 
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
    and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where estado = 6)
    and tmp_ventares.idventatmp not in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
    limit 100000
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //and tmp_ventares.idventatmp > (select max(idventatmp) from tmp_ventares_bak)

    // luego borrar
    $consulta = "
    delete from  tmp_ventares
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
    and (registrado = 'S' or borrado = 'S')
    and tmp_ventares.idventatmp in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    delete from  tmp_ventares
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
    and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where estado = 6)
    and tmp_ventares.idventatmp in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // primero mover
    $consulta = "
    INSERT INTO costo_productos_bak
    select * from costo_productos 
    where 
    disponible <= 0
    and ficticio = 0
    and idseriepkcos not in (select idseriepkcos from costo_productos_bak);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // luego borrar
    $consulta = "
    delete from  costo_productos
    where
    idseriepkcos in (select idseriepkcos from costo_productos_bak);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // primero mover
    $consulta = "
    INSERT INTO gest_depositos_stock_bak
    select * from gest_depositos_stock 
    where 
    disponible <= 0
    and ficticio = 0
    and idregseriedptostk not in (select idregseriedptostk from gest_depositos_stock_bak);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // luego borrar
    $consulta = "
    delete from  gest_depositos_stock
    where
    idregseriedptostk in (select idregseriedptostk from gest_depositos_stock_bak);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // optimizar tabla stock_movimiento
    $consulta = "  
    insert into stock_movimientos_bak
    select * from stock_movimientos
    WHERE
    date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
    and stock_movimientos.idstockmov not in (select idstockmov from stock_movimientos_bak where stock_movimientos_bak.idstockmov = stock_movimientos.idstockmov)
    limit 100000
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "  
    delete from stock_movimientos 
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
    and idstockmov in (select idstockmov from stock_movimientos_bak);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // optimizar venta receta
    $consulta = "
    insert into venta_receta_bak 
    select * from venta_receta 
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
    and venta_receta.idventareceta not in (select idventareceta from venta_receta_bak where venta_receta_bak.idventareceta = venta_receta.idventareceta)
    limit 100000
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    delete from venta_receta 
    where 
    date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
    and venta_receta.idventareceta in (select idventareceta from venta_receta_bak);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    insert into optimizador_cierre_caja
    (idcaja, fechasola, registrado_el)
    values
    ($idcaja, '$ahorad', '$ahora')
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

} //if($idoptiminizador == 0){
// FIN OPTIMIZADOR DE TABLAS
?>
