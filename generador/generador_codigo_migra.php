<?php


// mas herramientas en: mysql_masivo.php

// CONEXION
if (file_exists('adodb-5.20.14/adodb.inc.php')) {
    include_once('adodb-5.20.14/adodb.inc.php');
} else {
    if (file_exists('../adodb-5.20.14/adodb.inc.php')) {
        include_once('../adodb-5.20.14/adodb.inc.php');
    } else {
        include_once('../../adodb-5.20.14/adodb.inc.php');
    }
}
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT ^ E_WARNING);
// definiendo el tipo de base de datos
$dbdriver = 'mysqli';
$conexion = ADONewConnection($dbdriver); # eg 'mysql' o 'postgres'
$servidor = 'localhost:3307';
$usuario = 'root';
$contrasena = '';
$database = 'migra';
$conexion->Connect($servidor, $usuario, $contrasena, $database);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

include_once('../includes/funciones.php');
/*
PASO 1:
importar a mysql local llamado migra
DROP TABLE IF EXISTS `migra`;
CREATE TABLE IF NOT EXISTS `migra` (
  `tabla` varchar(100) NOT NULL,
  `columna` varchar(100) NOT NULL,
  PRIMARY KEY (`tabla`,`columna`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



PASO 2:
base origen, obtener columnas
SELECT table_name, column_name
FROM information_schema.`COLUMNS`
where
TABLE_SCHEMA = 'sistema_bdorigen' ORDER BY `TABLE_NAME` ASC

exportar a csv
TABLE_NAME;COLUMN_NAME

//////  NUEVO **********************

// hacer lo mismo para la base de destino
SELECT table_name, column_name
FROM information_schema.`COLUMNS`
where
TABLE_SCHEMA = 'sistema_bddestino' ORDER BY `TABLE_NAME` ASC

exportar a csv
TABLE_NAME;COLUMN_NAME

// reporte de diferencias en tablas
SELECT tabla FROM `migra` where tabla not in (select tabla from migra_new group by tabla) group by tabla

// guardar reporte
insert into migra_dif (tabla)
SELECT tabla FROM `migra` where tabla not in (select tabla from migra_new group by tabla) group by tabla

// eliminar tablas que no existen en destino
delete from migra where tabla in (
SELECT tabla FROM `migra` where tabla not in (select tabla from migra_new group by tabla) group by tabla)

// reporte de diferencias en columnas
SELECT concat(tabla,';',columna) FROM `migra` where concat(tabla,';',columna) not in (select concat(tabla,';',columna) from migra_new group by concat(tabla,';',columna)) group by concat(tabla,';',columna)

// guardar reporte
insert into migra_dif_col (tabla, columna, tabla_columna)
SELECT tabla, columna, concat(tabla,';',columna) FROM `migra` where concat(tabla,';',columna) not in (select concat(tabla,';',columna) from migra_new group by concat(tabla,';',columna)) group by concat(tabla,';',columna)

// eliminar columnas que no existen en destino
delete from migra where concat(tabla,';',columna) in ( SELECT concat(tabla,';',columna) FROM `migra` where concat(tabla,';',columna) not in (select concat(tabla,';',columna) from migra_new group by concat(tabla,';',columna)) group by concat(tabla,';',columna))

// comparador directo
SELECT table_name, column_name
FROM information_schema.`COLUMNS`
where
TABLE_SCHEMA = 'servid20_maestro'
and CONCAT(table_name,'-',column_name) NOT IN
(
SELECT CONCAT(table_name,'-',column_name)
FROM information_schema.`COLUMNS`
where
TABLE_SCHEMA = 'servid20_lascazuelas'
)
ORDER BY `TABLE_NAME` ASC;

//////  NUEVO **********************

PASO 3:
en excel para insertar en mysql
=CONCATENAR("INSERT INTO migra (tabla, columna) VALUES ('";A2;"','";B2;"');")

PASO 4:
agregar nueva BD a MysqlMasivo (Avisar Omar)

PASO 5:
// crear tabla sistema_nombrebd_new solo estructura desde bf --- bf > operaciones > copiar base > unicamente estructura
// desmarcar CREAR BASE DE DATOS antes de copiar y desmarcar Ajustar privilegios y luego continuar

// luego migrar datos  con la consulta:



*/
// PASO 6:
// configuracion
/*
$bd_origen="sistema_milnove";
$bd_destino="sistema_milnove_new";
*/
$bd_origen = "servid20_sil_marta_elena";
$bd_destino = "servid20_sil_marta_elena_new";

// arma columnas
$consulta = "
select * 
from migra 
order by tabla asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cols = [];
while (!$rs->EOF) {
    $tabla = $rs->fields['tabla'];
    $cols[$tabla][] = $rs->fields['columna'];
    $rs->MoveNext();
}
$rs->MoveFirst();

//print_r($cols);
echo "
Primera Migracion:<BR />
<textarea cols='100' rows='30'>";
foreach ($cols as $key => $value) {
    //echo $key."<br />";

    $columnas = "";
    foreach ($value as $keysub => $valuesub) {
        $columnas .= $valuesub.',';
    }
    $columnas = substr($columnas, 0, -1);

    // quitar limit 1
    $insert = '
insert into '.$bd_destino.'.'.$key.' ('.$columnas.')
select '.$columnas.'
from '.$bd_origen.'.'.$key.' 
limit 1
;

'; //limit 1
    echo $insert;



}

echo "</textarea>";

echo "
<BR />Segunda migracion:<BR />
<textarea  cols='100' rows='30'>";
foreach ($cols as $key => $value) {
    //echo $key."<br />";

    $columnas = "";
    foreach ($value as $keysub => $valuesub) {
        $columnas .= $valuesub.',';
    }
    $columnas = substr($columnas, 0, -1);

    // quitar limit 1
    $insert = '
insert into '.$bd_destino.'.'.$key.' ('.$columnas.')
select '.$columnas.'
from '.$bd_origen.'.'.$key.' 

;

'; //limit 1
    echo $insert;



}

echo "</textarea>";

// arma columnas
$consulta = "
select tabla 
from migra 
group by tabla 
order by tabla asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
echo "
<BR />BORRADO DE TABLAS:<BR />
<textarea  cols='100' rows='30'>";
while (!$rs->EOF) {

    $tabla = $rs->fields['tabla'];
    echo "truncate table ".$bd_destino.".".$tabla.";
";

    $rs->MoveNext();
}
$rs->MoveFirst();
echo "</textarea>";


echo "
<BR />Reemplazar Modulos:<BR />
<textarea  cols='100' rows='30'>";


echo "
truncate table ".$bd_destino.".modulo;
TRUNCATE table ".$bd_destino.".modulo_detalle;
truncate table ".$bd_destino.".modulo_empresa;

insert into ".$bd_destino.".modulo 
select * from servid20_maestro.modulo;

insert into ".$bd_destino.".modulo_detalle
select * from servid20_maestro.modulo_detalle;

insert into ".$bd_destino.".modulo_empresa
select * from servid20_maestro.modulo_empresa;

";
echo "</textarea>";



// tablas vacias
$consulta = "
select tabla 
from migra 
group by tabla 
order by tabla asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
echo "
<BR />OBTENER TABLAS VACIAS:<BR />
<textarea  cols='100' rows='30'>";
while (!$rs->EOF) {

    $tabla = $rs->fields['tabla'];
    echo "SELECT '".$tabla."' as tabla, COUNT(*) as total from ".$bd_origen.".".$tabla." union all
";

    $rs->MoveNext();
}
$rs->MoveFirst();
echo "</textarea>";

/*


IMPORTANTE!!!!
PONER EN MYSQLMASIVO




///PARA VER DIFERENCIAS ENTRE 2 BASES:
SELECT tabla, columna, 'A_nohayen_B' as tipo
FROM `migra`
WHERE
concat(tabla,columna) not in (
select concat(tabla,columna) from migra2
)
UNION ALL
SELECT tabla, columna, 'B_nohayen_A' as tipo
FROM `migra2`
WHERE
concat(tabla,columna) not in (
select concat(tabla,columna) from migra
)
ORDER BY `tipo`  ASC


// DIFERENCIAS ENTRE 2 BASES POR BD DIRECTO DEL SERVIDOR

// CONSULTA PARA INSERTAR
TRUNCATE TABLE comparador;
INSERT INTO `comparador`
(`table_schema`, `table_name`, `column_name`, `is_nullable`, `data_type`)
SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, IS_NULLABLE, DATA_TYPE
FROM information_schema.`COLUMNS`
where
(
    TABLE_SCHEMA = 'innovasysadmin_pederzani_new'
    or
    TABLE_SCHEMA = 'innovasysadmin_pederzani_new_demo'
)
ORDER BY `TABLE_SCHEMA` asc, `TABLE_NAME` ASC;
update `comparador` set concatenado = CONCAT(table_name,'_',column_name,'_',is_nullable,'_',data_type);

// CONSULTA PARA COMPARAR
SELECT table_schema as bd_origen,table_name,column_name,is_nullable,data_type
FROM `comparador`
WHERE
table_schema = 'innovasysadmin_pederzani_new'
and concatenado not in
(
    SELECT concatenado
    FROM `comparador`
    WHERE
    table_schema = 'innovasysadmin_pederzani_new_demo'
)
UNION ALL
SELECT table_schema as bd_origen,table_name,column_name,is_nullable,data_type
FROM `comparador`
WHERE
table_schema = 'innovasysadmin_pederzani_new_demo'
and concatenado not in
(
    SELECT concatenado
    FROM `comparador`
    WHERE
    table_schema = 'innovasysadmin_pederzani_new'
)


*/
