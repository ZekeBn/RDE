 <?php
// LIMPIAR AGRUPACIONES HUERFANAS
/*
update mesas_atc_grupos_cab
set
mesas_atc_grupos_cab.estadogrupo = 3
where
mesas_atc_grupos_cab.idgrupomesa in
(
SELECT mesas_atc_grupos_cab.idgrupomesa FROM `mesas_atc_grupo_deta`
inner join mesas_atc_grupos_cab on mesas_atc_grupos_cab.idgrupomesa = mesas_atc_grupo_deta.idgrupomesa
WHERE
mesas_atc_grupo_deta.idatc not in (select mesas_atc.idatc from mesas_atc where mesas_atc.estado = 1)
);
*/
// INICIO CIERRA GRUPOS QUE SUS DETALLES NO ESTEN ACTIVOS EN NINGUN ATC
$consulta = "
select idgrupomesa
from mesas_atc_grupos_cab
where
mesas_atc_grupos_cab.idgrupomesa in 
    (
        SELECT mesas_atc_grupo_deta.idgrupomesa 
        FROM mesas_atc_grupo_deta
        WHERE
        mesas_atc_grupo_deta.idatc not in (
            select 
            mesas_atc.idatc 
            from mesas_atc 
            where 
            mesas_atc.estado = 1
        )
    )
limit 1
";
$rsparch = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsparch->fields['idgrupomesa']) > 0) {
    $consulta = "
    update mesas_atc_grupos_cab 
    set 
    mesas_atc_grupos_cab.estadogrupo = 3 
    where
    mesas_atc_grupos_cab.idgrupomesa in 
        (
            SELECT mesas_atc_grupo_deta.idgrupomesa 
            FROM mesas_atc_grupo_deta
            WHERE
            mesas_atc_grupo_deta.idatc not in (
                select 
                mesas_atc.idatc 
                from mesas_atc 
                where 
                mesas_atc.estado = 1
            )
        )
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// FIN CIERRA GRUPOS QUE SUS DETALLES NO ESTEN ACTIVOS EN NINGUN ATC
// INICIO FINALIZA DETALLES QUE NO TIENEN CABECERAS ACTIVAS
$consulta = "
select idgrupomesa
from mesas_atc_grupo_deta
where 
idgrupomesa not in (
    select idgrupomesa 
    from mesas_atc_grupos_cab 
    where 
    estadogrupo = 1
)
limit 1
";
$rsparch = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsparch->fields['idgrupomesa']) > 0) {
    $consulta = "
    update mesas_atc_grupo_deta 
    set 
    estado = 3 
    where 
    idgrupomesa not in 
        (
            select idgrupomesa 
            from mesas_atc_grupos_cab 
            where 
            estadogrupo = 1
        )
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// FIN FINALIZA DETALLES QUE NO TIENEN CABECERAS ACTIVAS
// INICIO DESVINCULA AGRUPACIONES EN ATC QUE NO TIENEN GRUPOS ACTIVOS
$consulta = "
select idatc
from mesas_atc
where 
idgrupomesa not in (
    select idgrupomesa 
    from mesas_atc_grupos_cab 
    where 
    estadogrupo = 1
)
LIMIT 1
";
$rsparch = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsparch->fields['idatc']) > 0) {
    $consulta = "
    update mesas_atc 
    set 
    idgrupomesa = 0 
    where 
    idgrupomesa not in (
        select idgrupomesa 
        from mesas_atc_grupos_cab 
        where 
        estadogrupo = 1
    )
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// FIN DESVINCULA AGRUPACIONES EN ATC QUE NO TIENEN GRUPOS ACTIVOS

// INICIO DESVINCULA AGRUPACIONES EN MESAS QUE NO TIENEN GRUPOS ACTIVOS
$consulta = "
select idmesa
from mesas
where 
estado_mesa = 6 
and agrupado_con is not null 
and agrupado_con > 0
and idmesa not in (
    select idmesa 
    from mesas_atc_grupo_deta 
    where 
    estado = 1
)
LIMIT 1
";
$rsparch = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsparch->fields['idmesa']) > 0) {
    $consulta = "
    update mesas 
    set 
    agrupado_con = NULL, 
    estado_mesa = 1 
    where 
    estado_mesa = 6 
    and agrupado_con is not null 
    and agrupado_con > 0
    and idmesa not in (
        select idmesa 
        from mesas_atc_grupo_deta 
        where 
        estado = 1
    )
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// FIN DESVINCULA AGRUPACIONES EN MESAS QUE NO TIENEN GRUPOS ACTIVOS

// INICIO mesas libres que tienen atc activos
$consulta = "
SELECT idmesa 
FROM mesas 
where 
estadoex = 1 
and estado_mesa = 1
and idmesa in (
    select idmesa 
    from mesas_atc 
    where 
    mesas_atc.estado = 1
)
LIMIT 1
";
$rsparch = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsparch->fields['idmesa']) > 0) {
    $consulta = "
    update mesas 
    set 
    estado_mesa = 2
    where 
    estadoex = 1 
    and estado_mesa = 1
    and idmesa in (
        select idmesa 
        from mesas_atc 
        where 
        mesas_atc.estado = 1
    )
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// FIN mesas libres que tienen atc activos

// corregir atc con sucursal mal asignadas
/*$consulta="
update `mesas_atc`
set
idsucursal = (select mesas.idsucursal from mesas where idmesa = mesas_atc.idmesa)
where
idsucursal <> (select mesas.idsucursal from mesas where idmesa = mesas_atc.idmesa)
and estado = 1
";
$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
*/

?>
