<?php


//echo 'Migracion de servidor en progreso. EN breve restableceremos tu servicio.';exit;
if (file_exists('adodb-5.20.14/adodb.inc.php')) {
    include_once('adodb-5.20.14/adodb.inc.php');
} else {
    if (file_exists('../adodb-5.20.14/adodb.inc.php')) {
        include_once('../adodb-5.20.14/adodb.inc.php');
    }
    if (file_exists('../../adodb-5.20.14/adodb.inc.php')) {
        include_once('../../adodb-5.20.14/adodb.inc.php');
    }
    if (file_exists('../../../adodb-5.20.14/adodb.inc.php')) {
        include_once('../../../adodb-5.20.14/adodb.inc.php');
    }
    if (file_exists('../../../../adodb-5.20.14/adodb.inc.php')) {
        include_once('../../../../adodb-5.20.14/adodb.inc.php');
    }
}
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT ^ E_WARNING);
// definiendo el tipo de base de datos
$dbdriver = 'mysqli';
$conexion = ADONewConnection($dbdriver); # eg 'mysql' o 'postgres'
$servidor = 'localhost';
$usuario = 'root';
$contrasena = '';
$database = 'rde';
// para evitar inyeccion sql
//$hostname_conexion = $servidor;
//$database_conexion = $database;
//$username_conexion = $usuario;
//$password_conexion = $contrasena;
//$conexionmy = mysql_pconnect($hostname_conexion, $username_conexion, $password_conexion) or trigger_error(mysql_error(),E_USER_ERROR);
date_default_timezone_set("America/Asuncion");
$conexion->Connect($servidor, $usuario, $contrasena, $database);
$conexion->setCharset('utf8');
//$conexion->setCharset('utf8');
// conectando al servidor
/*$conexion->Connect($servidor, $usuario, $contrasena, $database);
$buscar='select * from usuarios';
$rs=$conexion->Execute($buscar);
while (!$rs->EOF){
    echo $rs->fields['nombres'];
    $rs->MoveNext();
}*/
// Determina como se obtienen los arreglos generados por los recordsets
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

date_default_timezone_set("America/Asuncion");
$nombre_sys = "e-kar&uacute;";
$telefono_sys = "0981825580 | 0986723854";
$consulta = "
	SET time_zone = 'America/Asuncion';
	";
//$conexion->Execute($consulta);
$ahora = date("Y-m-d H:i:s");
$ahorad = date("Y-m-d");
$razon_social_pred = strtoupper("Consumidor Final");
$genericoruc = "44444401";
$genericodv = '7';
// concatenaciones que se utilizan en varios script
$generico = $genericoruc.'-'.$genericodv;
$ruc_pred = $generico;
// no debe estar tabulado
$saltolinea = '
';

//$RUTA_IMG_WEB="ecom/gfx/fotosweb";
