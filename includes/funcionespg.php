<?php

function antisqlinyeccion($theValue, $theType, $uper = "S")
{
    if (PHP_VERSION < 6) {
        $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
    }

    $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

    switch ($theType) {
        case "text":
            $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
            break;
        case "long":
        case "int":
            $theValue = ($theValue != "") ? intval($theValue) : "NULL";
            break;
        case "float":
            $theValue = ($theValue != "") ? floatval($theValue) : "NULL";
            break;
        case "date":
            $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
            break;
        case "defined":
            $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
            break;
    }
    if ($uper == 'S') {
        $theValue = strtoupper($theValue);
    }
    return $theValue;
}
if (!function_exists("antixss")) {
    function antixss($variable, $tammin = "0", $tammax = "255", $utf8decode = 'NO')
    {

        // conversion utf8
        if ($utf8decode == "SI") {
            $variable = utf8_decode($variable);
        }
        if ($utf8decode == "ENCODE") {
            $variable = utf8_encode($variable);
        }

        // elimina etiquetas html
        $variable = strip_tags($variable, '<br />');
        $variable = substr($variable, 0, $tammax);

        //eliminar eventos de javascript
        $reeplazar = ['javascript:','Javascript:','JavaScript:','document.location','alert(','onload','onclick','onmousedown','onmouseup','onmouseover','onmousemove','onmouseout','onfocus','onblur','onkeypress','onkeydown','onkeyup','onsubmit','onreset','onselect','onchange'];
        $por = "";
        $variable = str_replace($reeplazar, $por, $variable);
        $variable = htmlentities($variable);

        //valida que tenga el tamano minimo, de lo contrario devuelve vacio
        if (strlen($variable) < $tammin) {
            $variable = "";
        }

        return $variable;
    }
}
function permitidos($texto)
{
    //eliminando etiquetas html
    $texto = strip_tags($texto);
    //compruebo que los caracteres sean los permitidos
    $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789áéíóúÁÉÍÓÚñÑ _-().:,/;&@
";
    for ($i = 0; $i < strlen($texto); $i++) {
        if (strpos($permitidos, substr($texto, $i, 1)) === false) {
            //echo substr($texto,$i,1);
        } else {
            $result = $result.substr($texto, $i, 1);
        }
    }
    return $result;
}
function mesespanol($mes)
{
    $mes = intval($mes);
    if ($mes > 12) {
        $mes = 0;
    } elseif ($mes < 1) {
        $mes = 0;
    }
    $meses = "No Especificado,Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre";
    $mesesar = explode(',', $meses);
    $mesespan = $mesesar[$mes];
    return $mesespan;
}
function diaespanol($fecha) // yyyy-mm-dd
{$diaespanol = date("N", strtotime($fecha));
    if ($diaespanol == "1") {
        $diaespan = "Lunes";
    }
    if ($diaespanol == "2") {
        $diaespan = "Martes";
    }
    if ($diaespanol == "3") {
        $diaespan = "Miercoles";
    }
    if ($diaespanol == "4") {
        $diaespan = "Jueves";
    }
    if ($diaespanol == "5") {
        $diaespan = "Viernes";
    }
    if ($diaespanol == "6") {
        $diaespan = "Sabado";
    }
    if ($diaespanol == "7") {
        $diaespan = "Domingo";
    }
    return $diaespan;
}
if (!function_exists("Capitalizar")) {
    function Capitalizar($nombre, $art = "SI", $enhe = "NO")
    {

        // parche para las Ñ ñ
        if ($enhe == 'SI') {
            $nombre = str_replace('&ntilde;', 'ñ', $nombre);
        }

        // aca definimos un array de articulos (en minuscula)
        // aunque lo puedes definir afuera y declararlo global aca
        if ($art == 'SI') {
            $articulos = [
            '0' => 'a',
            '1' => 'de',
            '2' => 'del',
            '3' => 'la',
            '4' => 'los',
            '5' => 'las',
            '6' => 'por',
            '7' => 'con',
            '8' => 'sin',
            '9' => 'el',
            ];
        } else {
            $articulos = [
            '0' => 'alskdfj209348lskjdf++**',
            ];
        }
        // explotamos el nombre
        $palabras = explode(' ', $nombre);

        // creamos la variable que contendra el nombre
        // formateado
        $nuevoNombre = '';

        // parseamos cada palabra
        $i = 1;
        foreach ($palabras as $elemento) {
            // si la palabra es un articulo
            if (in_array(trim(strtolower($elemento)), $articulos)) {
                // concatenamos seguido de un espacio
                if ($i > 1) {
                    $nuevoNombre .= strtolower($elemento)." ";
                } else {
                    $nuevoNombre .= ucfirst(strtolower($elemento))." ";  // si es la primera palabra
                }
            } else {
                // sino, es un nombre propio, por lo tanto aplicamos
                // las funciones y concatenamos seguido de un espacio
                $nuevoNombre .= ucfirst(strtolower($elemento))." ";
            }
            $i++;
        }

        return trim($nuevoNombre);
    }
}
function formatomoneda($numero, $decimales = 0)
{
    $numero = floatval($numero);
    $decimales = intval($decimales);
    $num_formateado = number_format($numero, $decimales, ',', '.');
    return $num_formateado; // Se mostrara por pantalla: 123.344
}
function formatofecha($formato, $fechaymd) // formato de entrada YYYY-mm-dd
{$res = date($formato, strtotime($fechaymd));
    return $res;
}
function fechaymd($fecha) //  dd/mm/YYYY
{$fecha = explode(' ', $fecha);
    $fecha = $fecha[0];
    $fecha = explode("/", $fecha);
    // agregar 0 enfrente
    if (strlen(intval($fecha[1])) == 1) {
        $mes = '0'.intval($fecha[1]);
    } else {
        $mes = intval($fecha[1]);
    }
    if (strlen(intval($fecha[0])) == 1) {
        $dia = '0'.intval($fecha[0]);
    } else {
        $dia = intval($fecha[0]);
    }
    $ano = intval($fecha[2]);
    $res = date("Y-m-d", strtotime($ano.'-'.$mes.'-'.$dia));
    return $res;
}
function errorpg($conexion, $consulta = '')
{
    $error = $conexion->ErrorMsg();
    //$error=utf8_decode($error);
    $error = htmlentities($error);
    if ($consulta != '') {
        $error = $error." <br />Query: ".$consulta."<br /><hr /><br />";
    }
    return $error;
}
function solonumeros($texto)
{
    //eliminando etiquetas html
    $texto = strip_tags($texto);
    //compruebo que los caracteres sean los permitidos
    $permitidos = "0123456789";
    for ($i = 0; $i < strlen($texto); $i++) {
        if (strpos($permitidos, substr($texto, $i, 1)) === false) {
            //echo substr($texto,$i,1);
        } else {
            $result = $result.substr($texto, $i, 1);
        }
    }
    return $result;
}
function edad($fechanac)
{
    //fecha actual
    $dia = date('j');
    $mes = date('n');
    $ano = date('Y');

    //fecha de nacimiento
    $dianaz = date("j", strtotime($fechanac));
    $mesnaz = date("n", strtotime($fechanac));
    $anonaz = date("Y", strtotime($fechanac));
    //si el mes es el mismo pero el dia inferior aun no ha cumplido años, le quitaremos un año al actual
    if (($mesnaz == $mes) && ($dianaz > $dia)) {
        $ano = ($ano - 1);
    }
    //si el mes es superior al actual tampoco habra cumplido años, por eso le quitamos un año al actual
    if ($mesnaz > $mes) {
        $ano = ($ano - 1);
    }
    //ya no habria mas condiciones, ahora simplemente restamos los años y mostramos el resultado como su edad
    $edad = ($ano - $anonaz);
    return $edad;
}
function validafecha($fechaymd)
{
    $valido = "NO";
    list($yy, $mm, $dd) = explode("-", $fechaymd);
    if (is_numeric($yy) && is_numeric($mm) && is_numeric($dd)) {
        //echo checkdate($mm,$dd,$yy);
        if (checkdate($mm, $dd, $yy) == 1) {
            $valido = "SI";
        }
    }
    return $valido;
}
function fechaespanolcompleta($fechaymd)
{
    $res = "";
    if ($fechaymd != '') {
        $res = diaespanol(date("Y-m-d", strtotime($fechaymd))).' '.date("d", strtotime($fechaymd)).' de '.mesespanol(date("m", strtotime($fechaymd))).' de '.date("Y", strtotime($fechaymd));
    }
    return $res;
}
function consultar_permisos($usuario)
{
    global $adodb_conn;
    $consulta = "
	SELECT count(*) as total FROM modulo_usuario 
	inner join modulo_empresa on modulo_usuario.idmodulo=modulo_empresa.idmodulo 
	WHERE  modulo_usuario.idusuario=$usuario
	";
    $resultado = $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
    $cantidad = intval($resultado->fields['total']);
    return $cantidad;
}
function listaprecios($listaprecio)
{
    global $saltolinea;
    $listaprecios = explode($saltolinea, trim($listaprecio));
    foreach ($listaprecios as $precio) {
        if (floatval($precio) > 0) {
            $lista .= ','.floatval($precio);
        }
    }
    $lista = substr($lista, 1);
    return $lista;
}
