<?php

if (!function_exists("antisqlinyeccion")) {
    function antisqlinyeccion($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        if (PHP_VERSION < 6) {
            $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
        }
        if (PHP_VERSION < 7) {
            $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
        } else {

            //$theValue = mysqli_real_escape_string($conexion,$retheValue) ;
            $theValue = addslashes($theValue);
        }


        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                $theValue = strtoupper($theValue);
                break;
            case "textbox":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                //$theValue=strtoupper($theValue); // no usar mayuscula o no funciona los saltos de linea
                break;
            case "clave":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "email":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "web":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = (trim($theValue) != "") ? intval($theValue) : "NULL";
                break;
            case "float":
                $theValue = ($theValue != "") ? floatval($theValue) : "NULL";
                break;
            case "double":
                $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "like":
                $theValue = ($theValue != "") ? "" . $theValue . "" : "NULL";
                $theValue = strtoupper($theValue);
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
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


if (!function_exists("capitalizar")) {
    function capitalizar($nombre)
    {
        // aca definimos un array de articulos (en minuscula)
        // aunque lo puedes definir afuera y declararlo global aca
        $articulos = [
        '0' => 'a',
        '1' => 'de',
        '2' => 'del',
        '3' => 'la',
        '4' => 'los',
        '5' => 'las',
        ];

        // explotamos el nombre
        $palabras = explode(' ', $nombre);

        // creamos la variable que contendra el nombre
        // formateado
        $nuevoNombre = '';

        // parseamos cada palabra
        foreach ($palabras as $elemento) {
            // si la palabra es un articulo
            if (in_array(trim(strtolower($elemento)), $articulos)) {
                // concatenamos seguido de un espacio
                $nuevoNombre .= strtolower($elemento)." ";
            } else {
                // sino, es un nombre propio, por lo tanto aplicamos
                // las funciones y concatenamos seguido de un espacio
                $nuevoNombre .= ucfirst(strtolower($elemento))." ";
            }
        }

        return trim($nuevoNombre);
    }
}
function formatomoneda($numero, $decimales = 0, $muestrasiempre = 'S')
{
    $numero = floatval($numero);
    $decimales = intval($decimales);
    $sep_decimal = ",";
    $sep_miles = ".";
    $num_formateado = number_format($numero, $decimales, $sep_decimal, $sep_miles);
    if ($muestrasiempre == 'N') {
        $num_formateadoar = explode($sep_decimal, $num_formateado);
        $num_entero = $num_formateadoar[0];
        $num_decimal = $num_formateadoar[1];
        if ($num_decimal > 0) {
            $num_formateado = rtrim($num_formateado, 0);
        } else {
            $num_formateado = $num_entero;
        }

    }
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
    return $ano.'-'.$mes.'-'.$dia;
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
function sololetras($texto)
{
    //eliminando etiquetas html
    $texto = strip_tags($texto);
    //compruebo que los caracteres sean los permitidos
    $permitidos = "abcdefghijklmnopqrstuvwxyzñABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for ($i = 0; $i < strlen($texto); $i++) {
        if (strpos($permitidos, substr($texto, $i, 1)) === false) {
            //echo substr($texto,$i,1);
        } else {
            $result = $result.substr($texto, $i, 1);
        }
    }
    return $result;
}

function errorpg($conexion, $consulta = '')
{
    $error = $conexion->ErrorMsg();
    //$error=utf8_decode($error);
    $error = htmlentities($error);
    if ($consulta != '') {
        $error = $error." <br /><br />Query: ".$consulta."<br /><hr /><br />";
    }
    return $error;
}

function antixss($texto)
{
    return htmlentities($texto);
}

//Primer dia del mes
function primer_dia($fechahoy)
{
    $fecha = strtotime($fechahoy); //Recibimos la fecha y la convertimos a tipo fecha
    $d = date("d", $fecha); //Obtenemos el dia
    $m = date("m", $fecha); //Obtenemos el mes
    $Y = date("Y", $fecha); //Obtenemos el año
    $primerDia = date("d/m/Y", mktime(0, 0, 0, $m, $d - $d + 1, $Y));

    return $primerDia; //Regresamos el valor obtenido
}
//Ultimo dia del Mes
function ultimodia($fechahoy)
{
    $fecha = strtotime($fechahoy); //Recibimos la fecha y la convertimos a tipo fecha
    $d = date("d", $fecha); //Obtenemos el dia
    $m = date("m", $fecha); //Obtenemos el mes
    $Y = date("Y", $fecha); //Obtenemos el año
    $ultimoDia = date("d/m/Y", mktime(0, 0, 0, $m + 1, $d - $d, $Y)); //Obtenemos el
    $a = explode("/", $ultimoDia);
    $ultimoDia = $a[0];
    return $ultimoDia; //Regresamos el valor obtenido
}
//Ultimo dia del Mes
function ultimodiaorig($fechahoy)
{
    $fecha = strtotime($fechahoy); //Recibimos la fecha y la convertimos a tipo fecha
    $d = date("d", $fecha); //Obtenemos el dia
    $m = date("m", $fecha); //Obtenemos el mes
    $Y = date("Y", $fecha); //Obtenemos el año
    $ultimoDia = date("d/m/Y", mktime(0, 0, 0, $m + 1, $d - $d, $Y)); //Obtenemos el
    return $ultimoDia; //Regresamos el valor obtenido
}

function mismafechaproximomes($fecha, $dia) // yyyy-mm-dd
{$fechapart = explode('-', $fecha);
    $diap = $dia;
    $mesp = $fechapart[1];
    $anop = $fechapart[0];
    // sumar lo requerido al mes exepto en diciembre
    if ($mesp == 12) {
        $mesp = 1;
        $anop += 1;
    } else {
        $mesp += 1;
    }
    // si el mes tiene 30 dias
    if (($mesp == 4) or ($mesp == 6) or ($mesp == 9) or ($mesp == 11)) {
        if ($diap == 31) {
            $diap = 30;
        }
    }
    // si es febrero y el dia mes mayor a 28
    if (($mesp == 2) && ($diap > 28)) {
        // si el dia es mayor a 29 poner 29
        if ($diap > 29) {
            $diap = 29;
        }
        // verificar que no sea bisiesto
        if ($diap == 29) {
            if (!checkdate($mesp, $diap, $anop)) {
                $diap = 28;
            }
        }
    }
    // validar dias imposibles
    if ($diap > 31 or $diap < 1) {
        $diap = 1;
    }
    $fechaproximomes = date("Y-m-d", strtotime($anop.'-'.$mesp.'-'.$diap));
    return $fechaproximomes;
}

function abrir_caja($idusu, $fecha, $sucursal)
{
    global $conexionpg;
    //Desgloce de la fecha segun la fecha de cobro
    $ahora2 = date("Y-m-d", strtotime($fecha));
    $res = explode('-', $ahora2);
    $mes = $res[1];
    $anito = $res[0];
    $dia = $res[2];
    //Verificar si no existe una caja abierta, solo si no existe se abre
    $buscar = "Select idcaja,estado from caja where idsucu=$sucursal and cajero=$idusu and mes=$mes and ano=$anito and dia=$dia";
    $apcaja = $conexionpg->Execute($buscar) or die(errorpg($conexionpg, $buscar));

    if (intval($apcaja->fields['idcaja'] == 0)) {
        $buscar = "Select max(idcaja) as mayor from caja where cajero=$idusu and idsucu=$sucursal";
        $rsm = $conexionpg->Execute($buscar) or die(errorpg($conexionpg, $buscar));
        $id = $rsm->fields['mayor'];
        $id = $id + 1;
        $inserta = "insert into caja (idcaja,fecha_apertura,cajero,lastmov,mes,ano,idsucu,estado,dia) values
			  ($id,'$ahora2',$idusu,current_timestamp,$mes,$anito,$sucursal,1,$dia)";
        $conexionpg->Execute($inserta) or die(errorpg($conexionpg, $inserta));

    }
}
function completadocuv($parte1, $parte2, $numero)
{

    $cab = trim($parte1).trim($parte2);
    $limite = 7;
    $medio = '';
    $final = $numero;
    $cantf = strlen($final);
    $dif = ($limite - $cantf);
    if ($dif > 0) {
        for ($i = 1;$i <= $dif;$i++) {
            $medio = $medio.'0';

        }
        //al finalizar el for, le metemos a completar como debe ser
        $compuesto = $cab.$medio.$final;
    } else {
        // no habian diferencias
        $compuesto = $cab.$final;
    }
    $documentom = $compuesto;
    return $documentom;
}


/*-------------------------------------DOCUMENTOS----------------------------------------------*/

//Tickete de acuerdo a la sucursal utilizada
function buscartickete($sucursallogin, $puntoexp, $empresal)
{
    global $conexion;
    $buscar = "Select max(numtk) as mayor from 
	lastcomprobantes where 	idsuc=$sucursallogin and 
	pe=$puntoexp and idempresa=$empresal";
    $rstk = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $maxntk = intval(($rstk->fields['mayor']) + 1);
    $hoy = date("Y-m-d");
    $ano = explode('-', $hoy);
    $anoactual = $ano[0];
    if ($maxntk == 1) {
        $buscar = "Select * from lastcomprobantes  where 	idsuc=$sucursallogin and idempresa=$empresal";
        $rsdd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $anof = intval($rsdd->fields['ano']);
        if ($ano == 0) {
            $insertar = "Insert into lastcomprobantes 
		idsuc,pe,idempresa,ano)
			values
			($sucursallogin,$puntoexp,$empresal,$anoactual)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }
    }
    //Completar segun secuencia de la Empresa

    $parte1 = '00'.$sucursallogin;
    $parte2 = '00'.$puntoexp;

    $cab = trim($parte1).trim($parte2);
    $limite = 7;
    $medio = '';
    $final = trim($maxntk);
    //Contar
    $cantf = strlen($final);
    $dif = ($limite - $cantf);
    if ($dif > 0) {
        for ($i = 1;$i <= $dif;$i++) {
            $medio = $medio.'0';

        }
        //al finalizar el for, le metemos a completar como debe ser
        $compuesto = $cab.$medio.$final;
    } else {
        // no habian diferencias
        $compuesto = $cab.$final;
    }

    return $compuesto;

}
//Factura de acuerdo a la sucursal utilizada
function buscarfactura($sucursallogin, $puntoexp, $empresal)
{
    //Aca hay que buscar en la tabla de ultimos comprobantes
    $hoy = date("Y-m-d");
    $ano = explode('-', $hoy);
    $anoactual = $ano[0];
    global  $conexion;
    $buscar = "Select max(numfac) as mayor from lastcomprobantes where idsuc=$sucursallogin and ano=$anoactual and pe=$puntoexp and idempresa=$empresal";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexionpg, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    //SI es 1 es porq no existe registro o bien se cambio de año por lo cual hay que buscar del anterior.
    if ($maxnfac == 1) {
        $anopasado = ($anoactual - 1);
        $buscar = "Select max(numfac) as mayor from lastcomprobantes where idsuc=$sucursallogin and ano=$anopasado and pe=$puntoexp  and idempresa=$empresal";
        $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    }
    //Creamos la secuencia basados en la regla :
    //Sucursal->Punto de expedicion->Numeracion

    $parte1 = '00'.$sucursallogin;
    $parte2 = '00'.$puntoexp;

    //Completar segun secuencia de la Empresa
    $cab = trim($parte1).trim($parte2);
    $limite = 7;
    $medio = '';
    $final = trim($maxnfac);
    //Contar
    $cantf = strlen($final);
    $dif = ($limite - $cantf);
    if ($dif > 0) {
        for ($i = 1;$i <= $dif;$i++) {
            $medio = $medio.'0';

        }
        //al finalizar el for, le metemos a completar como debe ser
        $compuesto = $cab.$medio.$final;
    } else {
        // no habian diferencias
        $compuesto = $cab.$final;
    }

    return $compuesto;

}
function buscarfactura2($sucursallogin, $puntoexp, $empresal)
{
    //Aca hay que buscar en la tabla de ultimos comprobantes
    global  $conexion;
    $buscar = "Select max(numfac) as mayor from lastcomprobantes where idsuc=$sucursallogin and pe=$puntoexp and idempresa=$empresal";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexionpg, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    return $maxnfac;
}
function prox_factura_auto($factura_suc, $factura_pexp, $idempresa)
{
    //Aca hay que buscar en la tabla de ultimos comprobantes
    global  $conexion;
    $buscar = "
	Select max(numfac) as mayor 
	from lastcomprobantes 
	where 
	idsuc=$factura_suc 
	and pe=$factura_pexp 
	and idempresa=$idempresa
	";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    return $maxnfac;
}
function timbrado_tanda($factura_suc, $factura_pexp, $idempresa, $numfac = 0, $idtipodocutimbrado = 1, $fecha = '')
{
    global  $conexion;
    global $ahora;
    if ($fecha == '') {
        $ahorad = date("Y-m-d", strtotime($ahora));
    } else {
        $ahorad = date("Y-m-d", strtotime($fecha));
    }
    if ($numfac > 0) {
        $whereadd = "
		and $numfac >= inicio
		and $numfac <= fin
		";
    }
    $consulta = "
	SELECT * 
	FROM facturas 
	where 
	estado = 'A'
	and valido_hasta >= '$ahorad'
	and valido_desde <= '$ahorad'
	and sucursal = $factura_suc
	and punto_expedicion = $factura_pexp
	and idtipodocutimbrado = $idtipodocutimbrado
	$whereadd
	";
    // echo $consulta;
    // exit;
    $rstimbrado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $res = [
        'idtanda' => $rstimbrado->fields['idtanda'],
        'timbrado' => $rstimbrado->fields['timbrado'],
        'valido_hasta' => $rstimbrado->fields['valido_hasta'],
        'valido_desde' => $rstimbrado->fields['valido_desde'],
        'inicio' => $rstimbrado->fields['inicio'],
        'fin' => $rstimbrado->fields['fin'],
        'factura_suc' => $rstimbrado->fields['sucursal'],
        'factura_pexp' => $rstimbrado->fields['punto_expedicion'],
        'tipoimpreso' => $rstimbrado->fields['tipoimpreso'],

    ];
    return $res;

}
//Hoja Levante de acuerdo a la sucursal utilizada
function buscarhoja($sucursallogin, $puntoexp, $empresal)
{

    $hoy = date("Y-m-d");
    $ano = explode('-', $hoy);
    $anoactual = $ano[0];
    global  $conexion;
    $buscar = "Select max(numhoja) as mayor from lastcomprobantes where idsuc=$sucursallogin and ano=$anoactual and pe=$puntoexp and  idempresa=$empresal";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexionpg, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    //SI es 1 es porq no existe registro o bien se cambio de año por lo cual hay que buscar del anterior.
    if ($maxnfac == 1) {
        $anopasado = ($anoactual - 1);
        $buscar = "Select max(numhoja) as mayor from lastcomprobantes where idsuc=$sucursallogin and ano=$anopasado and pe=$puntoexp and idempresa=$empresal";
        $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    }
    //Creamos la secuencia basados en la regla :
    //Sucursal->Punto de expedicion->Numeracion

    $parte1 = '00'.$sucursallogin;
    $parte2 = '00'.$puntoexp;

    //Completar segun secuencia de la Empresa
    $cab = trim($parte1).trim($parte2);
    $limite = 7;
    $medio = '';
    $final = trim($maxnfac);
    //Contar
    $cantf = strlen($final);
    $dif = ($limite - $cantf);
    if ($dif > 0) {
        for ($i = 1;$i <= $dif;$i++) {
            $medio = $medio.'0';

        }
        //al finalizar el for, le metemos a completar como debe ser
        $compuesto = $cab.$medio.$final;
    } else {
        // no habian diferencias
        $compuesto = $cab.$final;
    }

    return $compuesto;

}

function comprobartandafacturas($sucursal, $puntoexp, $empresal)
{
    global $conexion;
    global $idusu;
    //Buscar el menor id de tanda disponible para ese usuario y esa sucursal

    $buscar = "select min(idtanda) as menor from facturas where sucursal=$sucursal and punto_expedicion=$puntoexp and estado='A' and idempresa=$empresal  ";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $menordisponible = intval($rs->fields['menor']);
    if ($menordisponible == 0) {
        $continua = 'N';
    } else {
        //Hay facturas
        //Comprobar que existan facturas libres.
        //Si existen hay que ver la validez del timbrado
        $buscar = "Select * from facturas where idtanda=$menordisponible and idempresa=$empresal" 	;
        $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $ahora = date("Y-m-d");
        $fechafinal = date("Y-m-d", strtotime($rs->fields['valido_hasta']));
        if ($fechafinal < $ahora) {
            //Ya esta vencido el timbrado,por lo cual updateamos las facturas restantes
            $update = "update facturas set estado='C' where idtanda=$menordisponible and idempresa=$empresal";
            $$conexion->Execute($update) or die(errorpg($conexion, $update));
            //Ahora las facturas en detalles
            $update = "Update facturas_detalles set estado='AN',anulado_por=$idusu,fecha_anulado=current_timestamp where idtanda=$menordisponible and idempresa=$empresal";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            //

            //Buscamos si no hay alguna tanda adicional disponible
            $buscar = "Select min(idtanda) as menor from facturas where idtanda >$menordisponible and sucursal=$sucursal and punto_expedicion=$puntoexp and estado='A' and idempresa=$empresal";
            $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $menordispo = intval($rs->fields['idtanda']);

            if ($menordispo == 0) {
                $continua = 'EF';
            } else {
                $continua = 'S';
            }
        } else {
            //Todo Ok con la fecha y timbrado.
            $continua = 'S';

        }


    }
    return $continua;

}
function comprobartandalevante($sucursal, $puntoexp, $empresal)
{
    global $conexion;
    global $idusu;
    //Buscar el menor id de tanda disponible para ese usuario y esa sucursal

    $buscar = "select min(idtanda) as menor from hojalevante where sucursal=$sucursal and punto_expedicion=$puntoexp and estado='A' and idempresa=$empresal ";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $menordisponible = intval($rs->fields['menor']);
    if ($menordisponible == 0) {
        $continua = 'N';
    } else {
        //Hay facturas
        //Comprobar que existan facturas libres.
        //Si existen hay que ver la validez del timbrado
        $buscar = "Select * from hojalevante where idtanda=$menordisponible and idempresa=$empresal"	;
        $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $ahora = date("Y-m-d");
        $fechafinal = date("Y-m-d", strtotime($rs->fields['valido_hasta']));
        if ($fechafinal < $ahora) {
            //Ya esta vencido el timbrado,por lo cual updateamos las facturas restantes
            $update = "update hojalevante set estado='C' where idtanda=$menordisponible and idempresa=$empresal";
            $$conexionpg->Execute($update) or die(errorpg($conexionpg, $update));
            //Ahora las facturas en detalles
            $update = "Update hojalevante_detalles set estado='AN',anulado_por=$idusu,fecha_anulado=current_timestamp where idtanda=$menordisponible and idempresa=$empresal";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            //

            //Buscamos si no hay alguna tanda adicional disponible
            $buscar = "Select min(idtanda) as menor from hojalevante where idtanda >$menordisponible and sucursal=$sucursal and punto_expedicion=$puntoexp and estado='A' and idempresa=$empresal";
            $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $menordispo = intval($rs->fields['idtanda']);

            if ($menordispo == 0) {
                $continua = 'EF';
            } else {
                $continua = 'S';
            }
        } else {
            //Todo Ok con la fecha y timbrado.
            $continua = 'S';

        }


    }
    return $continua;

}
/*----------------------------------------------FINAL DOCUMENTOS---------------------------------------*/
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
function limpiaocr($texto)
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
function dias_transcurridos($fechaini, $fechafin, $abs = 'SI')
{
    if (!is_integer($fechaini)) {
        $fechaini = strtotime($fechaini);
    }
    if (!is_integer($fechafin)) {
        $fechafin = strtotime($fechafin);
    }
    if ($abs == 'SI') {
        $cantdias = floor(abs($fechaini - $fechafin) / 60 / 60 / 24);
    } else {
        $cantdias = floor(($fechaini - $fechafin) / 60 / 60 / 24);
    }
    $dias = $cantdias;
    return $dias;
}
function redondeo2($monto, $decimales = 0)
{
    if ($decimales == 0) {
        // caso con moneda a lo paraguay inflada
        $monto = ceil($monto / pow(10, 3)) * pow(10, 3);
    } else {
        // caso con centavos
        $monto = ceil($monto);
    }
    return $monto;
}

function fechaanos($fechaorig, $cantanos)
{

    $fecha = date_create($fechaorig);
    date_add($fecha, date_interval_create_from_date_string("$cantanos years"));
    return date_format($fecha, 'Y-m-d');
}

function consultar_permisos($loginid, $empresita)
{
    global $conexion;

    $consulta = "
	select a.estado,a.idmodulo,a.idempresa,b.asignado_el
	from modulo_usuario a,modulo_empresa b
	where a.idmodulo=b.idmodulo
	and b.estado=1
	and a.estado=1
	and a.idusu=$loginid
	and a.idempresa=$empresita
	limit 1" ;
    $resultado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $cantidad = $resultado->RecordCount();
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
function ip_real()
{
    if ($_SERVER) {
        if ($_SERVER["HTTP_X_FORWARDED_FOR"]) {
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif ($_SERVER["HTTP_CLIENT_IP"]) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    return $realip;
}


function dias_transcurridos2($fecha_i, $fecha_f)
{
    $dias = (strtotime($fecha_i) - strtotime($fecha_f)) / 86400;
    $dias = abs($dias);
    $dias = floor($dias);
    return $dias;
}
function calcular_ruc($p_numero, $p_basemax = 11)
{
    //$numero=$numero.'';
    $len = strlen($p_numero) - 1;
    //echo $len.'<br />';
    for ($i = 0;$i <= $len;$i++) {
        // para recorrer por caracter
        $v_caracter = strtoupper(substr($p_numero, $i, 1));
        //echo $v_caracter.'<br />';
        //obtiene el valor ascii del caracter
        $v_caracter_asci = ord($v_caracter);
        //echo $v_caracter.':'.$v_caracter_asci.'<br />';
        //Cambia la ultima letra por ascii en caso que la cedula termine en letra
        if (!($v_caracter_asci >= 48 && $v_caracter_asci <= 57)) { // 0 a 9
            $v_numero_al .= $v_caracter_asci;
        } else {
            $v_numero_al .= $v_caracter;
        }
        //echo $v_numero_al.'<br />';
    }
    $k = 2;
    $v_total = 0;
    for ($i = strlen($v_numero_al) - 1;$i >= 0;$i--) {
        if ($k > $p_basemax) {
            $k = 2;
        }
        $v_numero_aux = intval(substr($v_numero_al, $i, 1));
        //echo $v_numero_aux.'<br />';
        //echo $v_numero_aux;
        ///echo substr($v_numero_aux,$i,1);
        $v_total = $v_total + ($v_numero_aux * $k);
        $k = $k + 1;
    }

    $v_resto = fmod($v_total, 11);

    if ($v_resto > 1) {
        $v_digit = 11 - $v_resto;
    } else {
        $v_digit = 0;
    }
    return $v_digit;
}
function diasemana($fecha)
{
    $diahoy = '';
    $fechats = strtotime($fecha); //a timestamp
    $f = date('w', $fechats);
    if ($f == 0) {
        $diahoy = "DOMINGO";
    }
    if ($f == 1) {
        $diahoy = "LUNES";
    }
    if ($f == 2) {
        $diahoy = "MARTES";
    }
    if ($f == 3) {
        $diahoy = "MIERCOLES";
    }
    if ($f == 4) {
        $diahoy = "JUEVES";
    }
    if ($f == 5) {
        $diahoy = "VIERNES";
    }
    if ($f == 6) {
        $diahoy = "SABADO";
    }
    return $diahoy;
}
function diasemana_largo_mysql($n)
{
    $dia = $n;
    if ($dia == 1) {
        $res = 'DOMINGO'; // domingo
    } elseif ($dia == 2) {
        $res = 'LUNES'; // domingo
    } elseif ($dia == 3) {
        $res = 'MARTES'; // domingo
    } elseif ($dia == 4) {
        $res = 'MIERCOLES'; // domingo
    } elseif ($dia == 5) {
        $res = 'JUEVES'; // domingo
    } elseif ($dia == 6) {
        $res = 'VIERNES'; // domingo
    } elseif ($dia == 7) {
        $res = 'SABADO'; // domingo
    } else {
        $res = "Error! dia de la semana invalido";
        //exit;
    }
    return $res;

}
/*---------------IMAGENES------------------------*/
function verificardirectorio($directorio)
{

    //verifica la existencia de un directorio, y si n o existe lo genera
    $listo = 'N';
    if (is_dir($directorio)) {
        $listo = 'S';
    } else {
        @mkdir($directorio, 0777, true);
        if (@mkdir) {
            $listo = 'S';
        }
    }
    return $listo;

}
// 1 Leer el contenido de un directorio}
function ReadFolderDirectory($origen, $destino, $subtempo)
{
    $dir = $origen;
    //Antes de comenzar,comprobamos que exista destino, sino hay que crearlo
    if (is_dir($destino)) {
        //echo 'existe!';
    } else {
        @mkdir($destino, 0777, true);
    }
    $c = 0;
    $listDir = [];
    if ($handler = opendir($dir)) {
        while (($sub = readdir($handler)) !== false) {
            if ($sub != "." && $sub != ".." && $sub != "Thumb.db") {
                if (is_file($dir."/".$sub)) {
                    $c = $c + 1;
                    $nuevoarchivo = "$c.jpg";
                    //Renombrar el archivo al destino
                    rename("$dir/$sub", "$destino/$nuevoarchivo");

                    $listDir[] = $nuevoarchivo;
                } elseif (is_dir($dir."/".$sub)) {
                    $listDir[$sub] = $this->ReadFolderDirectory($dir."/".$sub);
                }
            }
        }
        closedir($handler);
        //Borrar directorio temporal (solo el sub)

        rmdir("$subtempo");
    }
    return $listDir;
}
function aleatorio($directorio)
{
    $handle = opendir("$directorio/");
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != ".htaccess" && $file != "index.html") {
            //print "$file";
            $ficheros[] = $file;
        }
    }
    closedir($handle);
    $imagen = $directorio."/".$ficheros[mt_rand(0, count($ficheros) - 1)];
    return $imagen;
}
function contar($directoriovv)
{
    $total = 0;
    $handle = opendir("$directoriovv/");

    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != ".htaccess" && $file != "index.html") {
            $total = $total + 1;

        }
    }
    closedir($handle);
    //print_r($ficheros);
    //exit;
    return ($total);


}
function mostrarimagenes($directoriovv)
{

    $handle = opendir("$directoriovv/");

    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != ".htaccess" && $file != "index.html") {
            $ficheros[] = $file;

        }
    }
    closedir($handle);
    //print_r($ficheros);
    //exit;
    return ($ficheros);


}
function thumb($origencarpeta)
{
    $ancho_nuevo = 80;
    $carpeta = "$origencarpeta/";
    $ext = ["gif","jpg","png"];
    $carpetanueva = "$origencarpeta/";
    if (is_dir($carpeta) && $dir = opendir($carpeta)) {
        while (($nombre_archivo = readdir($dir)) !== false) {
            $archivo = pathinfo($carpeta.$nombre_archivo);
            if (in_array(strtolower($archivo['extension']), $ext)) {
                if (strtolower($archivo['extension']) == "gif") {
                    $img = imagecreatefromgif($carpeta.$nombre_archivo.'-thumb');
                } elseif (strtolower($archivo['extension']) == "jpg") {
                    $img = imagecreatefromjpeg($carpeta.$nombre_archivo);
                } elseif (strtolower($archivo['extension']) == "png") {
                    $img = imagecreatefrompng($carpeta.$nombre_archivo);
                }
                $ancho = imagesx($img);
                $altura = imagesy($img);
                $ancho_nuevo = $ancho_nuevo;
                $altura_nueva = floor($altura * ($ancho_nuevo / $ancho));
                $tmp_img = imagecreatetruecolor($ancho_nuevo, $altura_nueva);
                imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $ancho_nuevo, $altura_nueva, $ancho, $altura);
                if (strtolower($archivo['extension']) == "gif") {
                    imagegif($tmp_img, $carpetanueva.$nombre_archivo);
                } elseif (strtolower($archivo['extension']) == "jpg") {
                    imagejpeg($tmp_img, $carpetanueva.$nombre_archivo);
                } elseif (strtolower($archivo['extension']) == "png") {
                    imagepng($tmp_img, $carpetanueva.$nombre_archivo);
                }
            }
        }
    }
    closedir($dir);
}
function totalminutos($fechainicio, $fechafinal)
{
    $start_date = new DateTime($fechainicio);
    $since_start = $start_date->diff(new DateTime($fechafinal));
    /*echo $since_start->days.' days total<br>';
    echo $since_start->y.' years<br>';
    echo $since_start->m.' months<br>';
    echo $since_start->d.' days<br>';
    echo $since_start->h.' hours<br>';
    echo $since_start->i.' minutes<br>';
    echo $since_start->s.' seconds<br>';*/
    //$tminutos=$since_start->i;
    $minutes = $since_start->days * 24 * 60;
    $minutes += $since_start->h * 60;
    $minutes += $since_start->i;
    $tminutos = $minutes;
    //echo $minutes.' minutes';
    return $tminutos;

}
function antiguedad($fechainicio, $fechafinal)
{
    $retorno = '';
    //retorna la diferencia entre dos fechas, para calcular antiguedad en tiempo
    $inicio = $fechainicio;
    $fin = $fechafinal;

    $datetime1 = new DateTime($inicio);
    $datetime2 = new DateTime($fin);

    # obtenemos la diferencia entre las dos fechas
    $interval = $datetime2->diff($datetime1);

    # obtenemos la diferencia en meses
    $intervalMeses = $interval->format("%m");
    # obtenemos la diferencia en años y la multiplicamos por 12 para tener los meses
    $intervalAnos = $interval->format("%y");
    if ($intervalAnos > 0) {
        if ($intervalAnos < 10) {
            if ($intervalAnos == 1) {
                $palabra = "a&ntilde;o";
            } else {
                $palabra = "a&ntilde;os";
            }
        } else {
            $palabra = "a&ntilde;o(s)";
        }
        if ($intervalMeses < 10) {
            $palabra2 = "meses";
        } else {
            $palabra2 = "meses";
        }
        $retorno = $intervalAnos." $palabra y ".$intervalMeses." $palabra2.";
    } else {
        if ($intervalMeses < 10) {
            $palabra2 = "mes";
        } else {
            $palabra2 = "meses";
        }
        $retorno = $intervalMeses." $palabra2.";
    }
    return $retorno;
}
function mesletra($numeromes)
{
    switch ($numeromes) {
        case 1: $month_text = "Enero";
            break;
        case 2: $month_text = "Febrero";
            break;
        case 3: $month_text = "Marzo";
            break;
        case 4: $month_text = "Abril";
            break;
        case 5: $month_text = "Mayo";
            break;
        case 6: $month_text = "Junio";
            break;
        case 7: $month_text = "Julio";
            break;
        case 8: $month_text = "Agosto";
            break;
        case 9: $month_text = "Septiembre";
            break;
        case 10: $month_text = "Octubre";
            break;
        case 11: $month_text = "Noviembre";
            break;
        case 12: $month_text = "Diciembre";
            break;
    }
    return ($month_text);
}
/*------------------------------------------------------*/

function trackid($tipo, $idusu, $sucursal, $idempresa, $idpedido, $idtransaccion)
{

    global $conexion;

    $hoy = date("d-m-Y");
    $fechaex = explode("-", $hoy);

    $dia = $fechaex[0];
    $mes = $fechaex[1];
    $ano = $fechaex[2];

    if (intval($tipo) == 1) {
        //pedidos en gestion
        $tabla = "gest_tracking";
    } else {
        if (intval($tipo) == 2) {
            //logistica ventas
            $tabla = "log_tracking";
        }
    }

    $buscar = "Select max(secuencia) as mayor from $tabla where idempresa=$idempresa 
	and sucursal=$sucursal and date(creado_el)=current_date";



    $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $mayorsec = ($rst->fields['mayor']) + 1;

    //armamos el tcid
    $numerobase = $ano.$mes.$dia.$sucursal.$idempresa;
    $secuencia = $mayorsec;
    $compuesto = $numerobase.$secuencia.$idusu;

    //buscamos por seguridad que no exista ese numero compuesto
    $buscar = "select compuesto from $tabla where compuesto=$compuesto and idempresa=$idempresa";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    if (intval($rsb->fields['compuesto']) == 0) {
        //Registramos de inmediato
        $insertar = "insert into $tabla
		(numerobase,secuencia,idempresa,sucursal,creado_el,idusu,idventa,compuesto,idpedido,idtransaccion)
		values
		($numerobase,$secuencia,$idempresa,
		$sucursal,current_timestamp,$idusu,0,$compuesto,$idpedido,$idtransaccion)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    } else {
        //Ya existe, por lo cual debemos asegurarnos de generar uno distinto
        $con = 1;
        while ($con == 1) {
            $secuencia = $secuencia + 1;
            $compuesto = $numerobase.$secuencia.$idusu;

            $buscar = "select compuesto from log_tracking 
			where compuesto=$compuesto and idempresa=$idempresa";
            $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            if (intval($rsb->fields['compuesto']) == 0) {
                $insertar = "insert into log_tracking
				(numerobase,secuencia,idempresa,sucursal,creado_el,idusu,idventa,compuesto)
				values
				($numerobase,$secuencia,$idempresa,$sucursal,current_timestamp,$idusu,0,$compuesto)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                $c = 0;
                break;
            }

        }//final del while
    }//fin del else
    return $compuesto;

}
function limpiar($String)
{
    $String = str_replace(['á','à','â','ã','ª','ä'], "a", $String);
    $String = str_replace(['Á','À','Â','Ã','Ä'], "A", $String);
    $String = str_replace(['Í','Ì','Î','Ï'], "I", $String);
    $String = str_replace(['í','ì','î','ï'], "i", $String);
    $String = str_replace(['é','è','ê','ë'], "e", $String);
    $String = str_replace(['É','È','Ê','Ë'], "E", $String);
    $String = str_replace(['ó','ò','ô','õ','ö','º'], "o", $String);
    $String = str_replace(['Ó','Ò','Ô','Õ','Ö'], "O", $String);
    $String = str_replace(['ú','ù','û','ü'], "u", $String);
    $String = str_replace(['Ú','Ù','Û','Ü'], "U", $String);
    $String = str_replace(['[','^','´','`','¨','~',']'], "", $String);
    $String = str_replace("ç", "c", $String);
    $String = str_replace("Ç", "C", $String);
    $String = str_replace("ñ", "n", $String);
    $String = str_replace("Ñ", "N", $String);
    $String = str_replace("Ý", "Y", $String);
    $String = str_replace("ý", "y", $String);

    $String = str_replace("&aacute;", "a", $String);
    $String = str_replace("&Aacute;", "A", $String);
    $String = str_replace("&eacute;", "e", $String);
    $String = str_replace("&Eacute;", "E", $String);
    $String = str_replace("&iacute;", "i", $String);
    $String = str_replace("&Iacute;", "I", $String);
    $String = str_replace("&oacute;", "o", $String);
    $String = str_replace("&Oacute;", "O", $String);
    $String = str_replace("&uacute;", "u", $String);
    $String = str_replace("&Uacute;", "U", $String);
    return $String;
}
function convert_date_php_js($date)
{
    $converted_date = date("Y", strtotime($date)) . ', ' . (date("n", strtotime($date)) - 1) . ', ' . date("j", strtotime($date));
    return $converted_date;
}
function siono($txt)
{
    if ($txt == 'S') {
        $res = "SI";
    } elseif ($txt == 'N') {
        $res = "NO";
    } else {
        $res = "SIN DATOS";
    }
    return $res;
}
function redondear_tresceros($numero)
{
    $res = (ceil($numero / 1000) * 1000);
    return $res;
}
function texto_tk($txt, $maxcol = 40, $centrar = 'N')
{
    $res = substr($txt, 0, $maxcol);
    if ($centrar == 'S') {
        $espaciosadd = "";
        $tamtxt = strlen($res);
        $espacios_libres = $maxcol - $tamtxt;
        $espacios_izq = floor($espacios_libres / 2);
        if ($espacios_izq < 0) {
            $espacios_izq = 0;
        }
        for ($i = 0;$i < $espacios_izq;$i++) {
            $espaciosadd .= " ";
        }
        $res = $espaciosadd.$res;
    }
    return $res;
}
function agregacero($numero, $caracteresmax = 7)
{
    $len = strlen($numero);
    if ($len > 0) {
        if ($len > $caracteresmax) {
            $numero = substr($numero, 0, $caracteresmax);
        }
        $faltan = $caracteresmax - $len;
        $ceros = "";
        for ($i = 1;$i <= $faltan;$i++) {
            $ceros = $ceros."0";
        }
        $numero = $ceros.$numero;
    }
    return $numero;
}
function agregaespacio($numero, $caracteresmax = 7)
{
    $len = strlen($numero);
    if ($len > 0) {
        if ($len > $caracteresmax) {
            $numero = substr($numero, 0, $caracteresmax);
        }
        $faltan = $caracteresmax - $len;
        $ceros = "";
        for ($i = 1;$i <= $faltan;$i++) {
            $ceros = $ceros." ";
        }
        $numero = $numero.$ceros;
    }
    return $numero;
}
function agregaespacio2($numero)
{
    if ($numero > 0) {
        for ($i = 1;$i <= $numero;$i++) {
            $ceros = $ceros." ";
        }
        $numero = $ceros;
    }
    return $numero;
}
function agregasangria($numero, $separador)
{
    if ($numero > 0) {
        for ($i = 1;$i <= $numero;$i++) {
            $ceros = $ceros.$separador;
        }
        $numero = $ceros;
    }
    return $numero;
}
function corta_nombreempresa($nombreempresa, $tamax = 40)
{
    $tamnombre = strlen($nombreempresa);
    $espacios = $tamax - $tamnombre;
    $espacios_ant = round(($espacios / 2), 0);
    $espacios_add = "";
    for ($i = 1;$i <= $espacios_ant;$i++) {
        $espacios_add .= " ";
    }
    $nombreempresa_centrado = $espacios_add.$nombreempresa;
    return $nombreempresa_centrado;
}
function mediopago($medio)
{
    if ($medio == 1) {
        $res = "EFECTIVO";
    } elseif ($medio == 2) {
        $res = "CHEQUE";
    } elseif ($medio == 3) {
        $res = "TRANSFERENCIA";
    } else {
        $res = "NO DEFINIDO";
    }
    return $res;
}
function diasemana_abrev($fecha)
{
    $diahoy = '';
    $fechats = strtotime($fecha); //a timestamp
    $f = date('w', $fechats);
    if ($f == 0) {
        $diahoy = "D";
    }
    if ($f == 1) {
        $diahoy = "L";
    }
    if ($f == 2) {
        $diahoy = "M";
    }
    if ($f == 3) {
        $diahoy = "X";
    }
    if ($f == 4) {
        $diahoy = "J";
    }
    if ($f == 5) {
        $diahoy = "V";
    }
    if ($f == 6) {
        $diahoy = "S";
    }
    return $diahoy;
}
function mes_abrev($mes)
{
    $mes = intval($mes);
    if ($mes > 12) {
        $mes = 0;
    } elseif ($mes < 1) {
        $mes = 0;
    }
    $meses = "N/A,ENE,FEB,MAR,ABR,MAY,JUN,JUL,AGO,SEP,OCT,NOV,DIC";
    $mesesar = explode(',', $meses);
    $mesespan = $mesesar[$mes];
    return $mesespan;

}
function transcurrido($segundos)
{

    $segundo = floor($segundos);
    $minuto = floor($segundos / 60);
    $hora = floor($segundos / 60 / 60);
    $dia = floor($segundos / 60 / 60 / 24);

    $res = "";

    // divide en partes
    if ($minuto > 0) {
        $segundo = round($segundos - ($minuto * 60), 0);
    }
    if ($hora > 0) {
        $minuto = round($minuto - ($hora * 60), 0);
    }
    if ($dia > 0) {
        $hora = round($hora - ($minuto * 60), 0);
    }

    // construye el tiempo
    if ($dia > 0) {
        $res .= $dia.' Dias. ';
    }
    if ($hora > 0) {
        $res .= $hora.' hs. ';
    }
    if ($minuto > 0) {
        $res .= $minuto.' min. ';
    }
    if ($segundo > 0) {
        $res .= $segundo.' seg. ';
    }


    return $res;

}
function dif_mes($fechainicio, $fechafin)
{

    $inicio = $fechainicio;
    $fin = $fechafin;

    $datetime1 = new DateTime($inicio);
    $datetime2 = new DateTime($fin);

    # obtenemos la diferencia entre las dos fechas
    $interval = $datetime2->diff($datetime1);

    # obtenemos la diferencia en meses
    $intervalMeses = $interval->format("%m");
    # obtenemos la diferencia en años y la multiplicamos por 12 para tener los meses
    $intervalAnos = $interval->format("%y") * 12;

    $mesestotal = $intervalMeses + $intervalAnos;

    //echo "hay una diferencia de ".($intervalMeses+$intervalAnos)." meses";
    return $mesestotal;

}
function ultimoDiaMes($mes, $año)
{
    for ($dia = 28;$dia <= 31;$dia++) {
        if (checkdate($mes, $dia, $año)) {
            $fecha = $dia;
        }
    }
    return $fecha;
}
function recalcular_caja_new($idcaja, $caja_chica_cierre = 0)
{

    global $idempresa;
    global $conexion;
    global $ahora;
    global $idusu;




    /******************   PARA CAJA NUEVA **********/////////
    // busca en arqueo por formas de pago e inserta en vouchers
    $consulta = "
	INSERT INTO `caja_vouchers`
	(
	idcaja, cajero, total_vouchers, registrado_el, 
	anulado_por, anulado_el, estado, registrado_adm_por, id_idserie
	)
	SELECT 
	idcaja, registrado_por, monto, registrado_el,
	NULL, NULL, estado, NULL, idserie
	FROM caja_arqueo_fpagos
	where 
	id_unicasspk is null 
	and id_registrobill is null 
	and id_sermone is null
	and idformapago > 1
	and idcaja=$idcaja 
	and idserie not in (select id_idserie from caja_vouchers where id_idserie is not null and idcaja=$idcaja )
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // actuaaliza el id_unicasspk
    $consulta = "
	update caja_arqueo_fpagos
	set
	id_unicasspk = (
		select  unicasspk
		from caja_vouchers
		where
		caja_vouchers.id_idserie = caja_arqueo_fpagos.idserie
		and idcaja=$idcaja
	)
	where
	idcaja = $idcaja
	and id_unicasspk is null
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // cargar retroactivo vouchers
    $consulta = "
	INSERT INTO `caja_arqueo_fpagos`
	(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)
	
	select idcaja, 2, total_vouchers, NULL, NULL, estado, cajero, registrado_el, NULL, 
	NULL, unicasspk, NULL, NULL
	from caja_vouchers
	WHERE
	unicasspk not in (select id_unicasspk from caja_arqueo_fpagos where id_unicasspk is not null)
	and idcaja not in (select idcaja from caja_arqueo_fpagos where idformapago > 1 and idcaja is not null and id_unicasspk is null)
	and idcaja in (select idcaja  from caja_super where estado_caja = 3 and idcaja = caja_vouchers.idcaja)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // cargar retroactivo billetes
    $consulta = "
	INSERT INTO `caja_arqueo_fpagos`
	(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)
	
	select idcaja, 1, subtotal, NULL, NULL, estado, idcajero, NOW(), NULL,
	NULL, NULL,  registrobill, NULL
	from caja_billetes 
	WHERE
	registrobill not in (select id_registrobill from caja_arqueo_fpagos where id_registrobill is not null)
	and idcaja not in (
	select idcaja from caja_arqueo_fpagos 
	where 
	idformapago = 1 
	and idcaja is not null 
	and id_registrobill is not null
	and id_sermone is not null
	and idcaja in (select idcaja  from caja_super where estado_caja = 3 and idcaja = caja_billetes.idcaja)
	)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    // cargar retroactivo moneda extrangera
    $consulta = "
	INSERT INTO `caja_arqueo_fpagos`
	(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)
	
	select idcaja, 1, subtotal, NULL, NULL, estado, cajero, NOW(), NULL,
	NULL, NULL,  NULL, sermone
	from caja_moneda_extra 
	WHERE
	sermone not in (select id_sermone from caja_arqueo_fpagos where id_sermone is not null)
	and idcaja not in (
	select idcaja from caja_arqueo_fpagos 
	where 
	idformapago = 1 
	and idcaja is not null 
	and id_registrobill is not null
	and id_sermone is not null
	)
	and idcaja in (select idcaja  from caja_super where estado_caja = 3 and idcaja = caja_moneda_extra.idcaja)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    /******************   PARA CAJA NUEVA **********/////////



    //Registramos
    $update = "
	update caja_super 
	set 
		monto_cierre=COALESCE((
		select sum(monto) as total
		from caja_arqueo_fpagos
		inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
		where
		caja_arqueo_fpagos.idcaja = caja_super.idcaja
		and caja_arqueo_fpagos.estado <> 6
		),0),
		recalculado_por = $idusu,
		recalculado_el = '$ahora'
	where 
		idcaja=$idcaja
		and estado_caja=3
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //******* nuevo calculo de faltante y sobrante *****************//

    // DIFERENCIA =  TOTAL DECLARADO - TOTAL SISTEMA (APERTURA+INGRESOS-EGRESOS )
    $consulta = "
	select 
		COALESCE((
		select sum(monto) as total
		from caja_arqueo_fpagos
		inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
		where
		caja_arqueo_fpagos.idcaja = caja_super.idcaja
		and caja_arqueo_fpagos.estado <> 6
		),0)
		-
		(
			COALESCE(caja_super.monto_apertura,0)
			+
			COALESCE((
			SELECT sum(gest_pagos.total_cobrado) as total
			FROM gest_pagos
			where
			gest_pagos.estado <> 6
			and gest_pagos.idcaja = caja_super.idcaja
			and gest_pagos.tipomovdinero = 'E'
			),0)
			-
			COALESCE((
			SELECT sum(gest_pagos.total_cobrado) as total
			FROM gest_pagos
			where
			gest_pagos.estado <> 6
			and gest_pagos.idcaja = caja_super.idcaja
			and gest_pagos.tipomovdinero = 'S'
			),0)
		) as diferencia
		
	from caja_super
	where
	idcaja = $idcaja
	";
    $rsdif = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $diferencia = floatval($rsdif->fields['diferencia']);
    if ($diferencia > 0) {
        $faltante = 0;
        $sobrante = $diferencia;
    } elseif ($diferencia < 0) {
        $faltante = $diferencia * -1;
        $sobrante = 0;
    } else {
        $faltante = 0;
        $sobrante = 0;
    }

    $consulta = "
	update caja_super 
	set 
	faltante = $faltante,
	sobrante = $sobrante
	where
	idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    //******* nuevo calculo de faltante y sobrante *****************//


    //return $update;
    return "ok";

}
//2021-11-06 renombrar este por viejo y el de arriba quue se llame asi
function recalcular_caja($idcaja, $caja_chica_cierre = 0)
{

    global $idempresa;
    global $conexion;
    global $ahora;
    global $idusu;


    // busca si hay una caja abierta por este usuario
    $consulta = "
	Select * 
	from caja_super 
	where 
	idcaja = $idcaja
	order by fecha desc 
	limit 1
	";
    $rscaja = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $montobre = $rscaja->fields['monto_apertura'];

    //Total de Cobranzas en el dia
    $buscar = "Select  sum(total_cobrado) as tcobra from gest_pagos where estado=1 and idcaja=$idcaja and rendido='S'";
    $rscobro = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcobranza = floatval($rscobro->fields['tcobra']);

    //Total de ventas EFEC en el dia
    $buscar = "Select  sum(totalcobrar) as tventa from ventas where estado=1 and idcaja=$idcaja";
    $rsventas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tventa = floatval($rsventas->fields['tventa']);

    //Total de ventas CREDITO en el dia
    $buscar = "Select  sum(totalcobrar) as tventa from ventas where estado=2 and idcaja=$idcaja and tipo_venta=2";
    $rscc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $cred = floatval($rscc->fields['tventa']);

    //Cobranza en Efectivo
    $buscar = "Select  sum(efectivo) as efectivogs from gest_pagos 
	where
	 estado=1 and idcaja=$idcaja and rendido='S'";
    $rsefe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tefe = floatval($rsefe->fields['efectivogs']);

    //Cobranzas  Tarjeta  Credito
    $buscar = "Select  sum(montotarjeta) as tarje from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S' and tipotarjeta = 1";
    $rstarje = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    //Cobranzas  Tarjeta debito
    $buscar = "Select  sum(montotarjeta) as tarje from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S' and tipotarjeta = 2";
    $rstarjedeb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    // total tarjeta
    $tarje = floatval($rstarje->fields['tarje']) + floatval($rstarjedeb->fields['tarje']);

    //Cobranzas  cheque
    $buscar = "Select  sum(montocheque) as cheque from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S'";
    $rscheque = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



    //Cobranzas  transferencia
    $buscar = "Select  sum(montotransfer) as transfer from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S'";
    $rstransfer = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    // cobranzas otros medios
    $buscar = "
	Select  sum(monto_pago_det) as total_cobrado 
	from gest_pagos
    inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
	inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
	where 
	gest_pagos.estado=1 
	and gest_pagos.idcaja=$idcaja
	and gest_pagos.rendido = 'S'
    and gest_pagos_det.idformapago > 9
	and formas_pago.computa_caja = 1
	";
    //echo $buscar;exit;
    $rstotrmedios = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $total_otrospagos = floatval($rstotrmedios->fields['total_cobrado']);

    //Pagos por caja
    $consulta = "
	select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'R'),0)) as totalp_r,
	(COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'C'),0)) as totalp
	from cuentas_empresa_pagos
	inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
	where
	cuentas_empresa_pagos.idcaja = $idcaja
	and cuentas_empresa_pagos.idempresa = $idempresa
	and cuentas_empresa_pagos.estado <> 6
	ORDER BY cuentas_empresa_pagos.fecha_pago asc
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tpagos = floatval($rsv->fields['totalp_r']);
    $tpagos_ch = floatval($rsv->fields['totalp']);

    $consulta = "
	select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'R'),0)) as totalp_r,
	(COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'C'),0)) as totalp
	from cuentas_empresa_pagos
	inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
	where
	cuentas_empresa_pagos.idcaja = $idcaja
	and cuentas_empresa_pagos.idempresa = $idempresa
	and cuentas_empresa_pagos.estado <> 6
	and cuentas_empresa_pagos.mediopago = 1
	ORDER BY cuentas_empresa_pagos.fecha_pago asc
	";
    $rsvef = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tpagosef = floatval($rsvef->fields['totalp_r']);
    $tpagosef_ch = floatval($rsv->fields['totalp']);

    //Retiros(entrega de plata)desde el cajero al supervisor
    $buscar = "Select count(*) as cantidad,sum(monto_retirado) as tretira from caja_retiros
	where idcaja=$idcaja and estado=1";
    $rsretiros = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tretiros = intval($rsretiros->fields['cantidad']);
    $tretirosgs = intval($rsretiros->fields['tretira']);

    //Reposiciones de Dinero (desde el tesorero al cajero
    $buscar = "Select  sum(monto_recibido) as recibe from caja_reposiciones where idcaja=$idcaja and estado=1";
    $rsrepo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $trepo = floatval($rsrepo->fields['recibe']);


    // valores de la caja
    $tarjecred = floatval($rstarje->fields['tarje']);
    $tarjedeb = floatval($rstarjedeb->fields['tarje']);
    $tcheque = floatval($rscheque->fields['cheque']);
    $ttransfer = floatval($rstransfer->fields['transfer']);

    //Disponible actual caja
    $totalteorico = $tefe + $montobre + $tarje + $tcheque + $ttransfer - $tpagos;
    $totalteoricoefe = $tefe + $montobre - $tpagos;

    //total en monedas extranjeras pero convertidas a gs
    $buscar = "select sum(subtotal) as tmone from caja_moneda_extra where idcaja=$idcaja and estado=1";
    $extra = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $textra = floatval($extra->fields['tmone']);

    //total en monedas arqueadas
    $buscar = "select sum(subtotal) as total from caja_billetes where idcaja=$idcaja  and estado=1";
    $tarqueo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tarquegs = intval($tarqueo->fields['total']);

    //Cobranza pendiente x delivery
    $buscar = "Select  sum(total_cobrado) as totalpend  from gest_pagos 
	where 
	estado=1 
	and idcaja=$idcaja 
	and rendido ='N'";
    $rspend = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tpendi1 = floatval($rspend->fields['totalpend']);

    /******************   PARA CAJA NUEVA **********/////////
    // busca en arqueo por formas de pago e inserta en vouchers
    $consulta = "
	INSERT INTO `caja_vouchers`
	(
	idcaja, cajero, total_vouchers, registrado_el, 
	anulado_por, anulado_el, estado, registrado_adm_por, id_idserie
	)
	SELECT 
	idcaja, registrado_por, monto, registrado_el,
	NULL, NULL, estado, NULL, idserie
	FROM caja_arqueo_fpagos
	where 
	id_unicasspk is null 
	and id_registrobill is null 
	and id_sermone is null
	and idformapago > 1
	and idcaja=$idcaja 
	and idserie not in (select id_idserie from caja_vouchers where id_idserie is not null and idcaja=$idcaja )
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // actuaaliza el id_unicasspk
    $consulta = "
	update caja_arqueo_fpagos
	set
	id_unicasspk = (
		select  unicasspk
		from caja_vouchers
		where
		caja_vouchers.id_idserie = caja_arqueo_fpagos.idserie
		and idcaja=$idcaja

	)
	where
	idcaja = $idcaja
	and id_unicasspk is null
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // cargar retroactivo vouchers
    $consulta = "
	INSERT INTO `caja_arqueo_fpagos`
	(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)
	
	select idcaja, 2, total_vouchers, NULL, NULL, estado, cajero, registrado_el, NULL, 
	NULL, unicasspk, NULL, NULL
	from caja_vouchers
	WHERE
	unicasspk not in (select id_unicasspk from caja_arqueo_fpagos where id_unicasspk is not null)
	and idcaja not in (select idcaja from caja_arqueo_fpagos where idformapago > 1 and idcaja is not null and id_unicasspk is null)
	and idcaja in (select idcaja  from caja_super where estado_caja = 3 and idcaja = caja_vouchers.idcaja)
	and idcaja = $idcaja

	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // cargar retroactivo billetes
    $consulta = "
	INSERT INTO `caja_arqueo_fpagos`
	(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)
	
	select idcaja, 1, subtotal, NULL, NULL, estado, idcajero, NOW(), NULL,
	NULL, NULL,  registrobill, NULL
	from caja_billetes 
	WHERE
	registrobill not in (select id_registrobill from caja_arqueo_fpagos where id_registrobill is not null)
	and idcaja not in (
	select idcaja 
	from caja_arqueo_fpagos 
	where 
	idformapago = 1 
	and idcaja is not null 
	and id_registrobill is not null
	and id_sermone is not null
	)
	and idcaja in (select idcaja  from caja_super where estado_caja = 3 and idcaja = caja_billetes.idcaja)
	and idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    // cargar retroactivo moneda extrangera
    $consulta = "
	INSERT INTO `caja_arqueo_fpagos`
	(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)
	
	select idcaja, 1, subtotal, NULL, NULL, estado, cajero, NOW(), NULL,
	NULL, NULL,  NULL, sermone
	from caja_moneda_extra 
	WHERE
	sermone not in (select id_sermone from caja_arqueo_fpagos where id_sermone is not null)
	and idcaja not in (
	select idcaja 
	from caja_arqueo_fpagos 
	where 
	idformapago = 1 
	and idcaja is not null 
	and id_registrobill is not null
	and id_sermone is not null
	)
	and idcaja in (select idcaja  from caja_super where estado_caja = 3 and idcaja = caja_moneda_extra.idcaja)
	and idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // actualizar estados de moneda nacional
    $consulta = "
	update  `caja_arqueo_fpagos`
	set 
	estado = (select estado from caja_billetes where caja_billetes.registrobill = caja_arqueo_fpagos.id_registrobill)
	WHERE
	idcaja=$idcaja
	and (select estado from caja_billetes where caja_billetes.registrobill = caja_arqueo_fpagos.id_registrobill) is not null
	and caja_arqueo_fpagos.id_registrobill is not null
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualizar estados de moneda extrangera
    $consulta = "
	update  `caja_arqueo_fpagos`
	set 
	estado = (select estado from caja_moneda_extra where caja_moneda_extra.sermone = caja_arqueo_fpagos.id_sermone)
	WHERE
	idcaja=$idcaja
	and (select estado from caja_moneda_extra where caja_moneda_extra.sermone = caja_arqueo_fpagos.id_sermone) is not null
	and caja_arqueo_fpagos.id_sermone is not null
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    /******************   PARA CAJA NUEVA **********/////////



    // total vouchers
    $consulta = "
	select sum(total_vouchers) as totalvouchers 
	from caja_vouchers 
	where 
	estado <> 6 
	and idcaja = $idcaja
	";
    $rsvo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totalvouchers = floatval($rsvo->fields['totalvouchers']);

    // calculos

    // efectivo en moneda extrangera + moneda nacional
    $neto = $textra + $tarquegs;
    $totalarqueado = $neto;

    // todo el efectivo de todas las monedas + tarjetas de credito + cheque + transfer
    $subtotal = $neto + $tarje + $tcheque + $ttransfer + $total_otrospagos - ($tpagos);

    $total_arqueado_ef = $totalarqueado;

    //  total ingresos
    $buscar = "
	Select  sum(monto_pago_det) as total_cobrado 
	from gest_pagos
    inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
	inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
	where 
	gest_pagos.estado=1 
	and gest_pagos.idcaja=$idcaja
	and gest_pagos.rendido = 'S'
	and formas_pago.computa_caja = 1
	";
    //echo $buscar;exit;
    $rsingresos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    //Vemos el faltante y sobrante
    //$sobrante=($totalarqueado+$totalvouchers)-($tefe+$tarje+$tcheque+$ttransfer+$total_otrospagos-$tpagos-$tretirosgs+$trepo+$rscaja->fields['monto_apertura']);
    //$faltante=($tefe+$tarje+$tcheque+$ttransfer+$total_otrospagos-$tpagos-$tretirosgs+$trepo+$rscaja->fields['monto_apertura'])-($totalarqueado+$totalvouchers);
    $sobrante = ($totalarqueado + $totalvouchers) - ($rsingresos->fields['total_cobrado'] - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura']);
    $faltante = ($rsingresos->fields['total_cobrado'] - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura']) - ($totalarqueado + $totalvouchers);
    if ($sobrante < 0) {
        $sobrante = 0;
    }
    if ($faltante < 0) {
        $faltante = 0;
    }
    $dispo = ($subtotal + $trepo) - $tretirosgs;
    $ape = $rscaja->fields['monto_apertura'];
    $ape_ch = $rscaja->fields['caja_chica'];
    $montocierre = floatval($textra) + floatval($tarquegs) + floatval($totalvouchers);
    $dispo = floatval($dispo);
    $ahora = date("Y-m-d H:i:s");

    //Registramos
    $update = "
	update caja_super 
	set 
		monto_cierre=$montocierre,
		total_cobros_dia=$tcobranza,
		total_pagos_dia=$tpagos,
		faltante=$faltante,
		sobrante=$sobrante,
		total_efectivo=$tefe,
		total_tarjeta=$tarjecred,
		total_tarjeta_debito=$tarjedeb,
		total_cheque=$tcheque,
		total_transfer=$ttransfer,
		total_global_gs=$dispo,
		total_entrega_gs=$tretirosgs,
		total_credito=$cred,
		total_reposiciones_gs=$trepo, 
		total_pend = $tpendi1, 
		caja_chica_cierre=$caja_chica_cierre, 
		total_pagos_dia_ch = $tpagos_ch,
		total_vouchers = $totalvouchers,
		total_arqueado_ef = $total_arqueado_ef,
		recalculado_por = $idusu,
		recalculado_el = '$ahora'
	where 
		idcaja=$idcaja
		and estado_caja=3
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //******* nuevo calculo de faltante y sobrante *****************//

    // igualar detalle con cabecera de pagos
    $consulta = "
	update gest_pagos 
	set 
	total_cobrado =  COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago),0)
	WHERE
	COALESCE(total_cobrado,0) <> COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago),0)
	and gest_pagos.idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // DIFERENCIA =  TOTAL DECLARADO - TOTAL SISTEMA (APERTURA+INGRESOS-EGRESOS )
    $consulta = "
	select 
		COALESCE((
		select sum(monto) as total
		from caja_arqueo_fpagos
		inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
		where
		caja_arqueo_fpagos.idcaja = caja_super.idcaja
		and caja_arqueo_fpagos.estado <> 6
		),0)
		-
		(
			COALESCE(caja_super.monto_apertura,0)
			+
			COALESCE((
			SELECT sum(gest_pagos.total_cobrado) as total
			FROM gest_pagos
			where
			gest_pagos.estado <> 6
			and gest_pagos.idcaja = caja_super.idcaja
			and gest_pagos.tipomovdinero = 'E'
			),0)
			-
			COALESCE((
			SELECT sum(gest_pagos.total_cobrado) as total
			FROM gest_pagos
			where
			gest_pagos.estado <> 6
			and gest_pagos.idcaja = caja_super.idcaja
			and gest_pagos.tipomovdinero = 'S'
			),0)
		) as diferencia
		
	from caja_super
	where
	idcaja = $idcaja
	";
    $rsdif = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $diferencia = floatval($rsdif->fields['diferencia']);
    if ($diferencia > 0) {
        $faltante = 0;
        $sobrante = $diferencia;
    } elseif ($diferencia < 0) {
        $faltante = $diferencia * -1;
        $sobrante = 0;
    } else {
        $faltante = 0;
        $sobrante = 0;
    }


    $consulta = "
	update caja_super 
	set 
	faltante = $faltante,
	sobrante = $sobrante
	where
	idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //Registramos
    $update = "
	update caja_super 
	set 
		monto_cierre=COALESCE((
		select sum(monto) as total
		from caja_arqueo_fpagos
		inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
		where
		caja_arqueo_fpagos.idcaja = caja_super.idcaja
		and caja_arqueo_fpagos.estado <> 6
		),0),
		recalculado_por = $idusu,
		recalculado_el = '$ahora'
	where 
		idcaja=$idcaja
		and estado_caja=3
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));


    //******* nuevo calculo de faltante y sobrante *****************//


    //return $update;
    return "ok";

}
function recalcular_caja_old($idcaja, $caja_chica_cierre = 0)
{

    global $idempresa;
    global $conexion;
    global $ahora;
    global $idusu;


    // busca si hay una caja abierta por este usuario
    $consulta = "
	Select * 
	from caja_super 
	where 
	idcaja = $idcaja
	order by fecha desc 
	limit 1
	";
    $rscaja = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $montobre = $rscaja->fields['monto_apertura'];

    //Total de Cobranzas en el dia
    $buscar = "Select  sum(total_cobrado) as tcobra from gest_pagos where estado=1 and idcaja=$idcaja and rendido='S'";
    $rscobro = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcobranza = floatval($rscobro->fields['tcobra']);

    //Total de ventas EFEC en el dia
    $buscar = "Select  sum(totalcobrar) as tventa from ventas where estado=1 and idcaja=$idcaja";
    $rsventas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tventa = floatval($rsventas->fields['tventa']);

    //Total de ventas CREDITO en el dia
    $buscar = "Select  sum(totalcobrar) as tventa from ventas where estado=2 and idcaja=$idcaja and tipo_venta=2";
    $rscc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $cred = floatval($rscc->fields['tventa']);

    //Cobranza en Efectivo
    $buscar = "Select  sum(efectivo) as efectivogs from gest_pagos 
	where
	 estado=1 and idcaja=$idcaja and rendido='S'";
    $rsefe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tefe = floatval($rsefe->fields['efectivogs']);

    //Cobranzas  Tarjeta  Credito
    $buscar = "Select  sum(montotarjeta) as tarje from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S' and tipotarjeta = 1";
    $rstarje = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    //Cobranzas  Tarjeta debito
    $buscar = "Select  sum(montotarjeta) as tarje from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S' and tipotarjeta = 2";
    $rstarjedeb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    // total tarjeta
    $tarje = floatval($rstarje->fields['tarje']) + floatval($rstarjedeb->fields['tarje']);

    //Cobranzas  cheque
    $buscar = "Select  sum(montocheque) as cheque from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S'";
    $rscheque = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



    //Cobranzas  transferencia
    $buscar = "Select  sum(montotransfer) as transfer from gest_pagos 
	where 
	estado=1 and idcaja=$idcaja  and rendido='S'";
    $rstransfer = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    //Pagos por caja
    $consulta = "
	select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'R'),0)) as totalp_r,
	(COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'C'),0)) as totalp
	from cuentas_empresa_pagos
	inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
	where
	cuentas_empresa_pagos.idcaja = $idcaja
	and cuentas_empresa_pagos.idempresa = $idempresa
	and cuentas_empresa_pagos.estado <> 6
	ORDER BY cuentas_empresa_pagos.fecha_pago asc
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tpagos = floatval($rsv->fields['totalp_r']);
    $tpagos_ch = floatval($rsv->fields['totalp']);

    $consulta = "
	select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'R'),0)) as totalp_r,
	(COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja 
	and idempresa=$idempresa and estado <> 6  and tipocaja = 'C'),0)) as totalp
	from cuentas_empresa_pagos
	inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
	where
	cuentas_empresa_pagos.idcaja = $idcaja
	and cuentas_empresa_pagos.idempresa = $idempresa
	and cuentas_empresa_pagos.estado <> 6
	and cuentas_empresa_pagos.mediopago = 1
	ORDER BY cuentas_empresa_pagos.fecha_pago asc
	";
    $rsvef = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tpagosef = floatval($rsvef->fields['totalp_r']);
    $tpagosef_ch = floatval($rsv->fields['totalp']);

    //Retiros(entrega de plata)desde el cajero al supervisor
    $buscar = "Select count(*) as cantidad,sum(monto_retirado) as tretira from caja_retiros
	where idcaja=$idcaja and estado=1";
    $rsretiros = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tretiros = intval($rsretiros->fields['cantidad']);
    $tretirosgs = intval($rsretiros->fields['tretira']);

    //Reposiciones de Dinero (desde el tesorero al cajero
    $buscar = "Select  sum(monto_recibido) as recibe from caja_reposiciones where idcaja=$idcaja and estado=1";
    $rsrepo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $trepo = floatval($rsrepo->fields['recibe']);


    // valores de la caja
    $tarjecred = floatval($rstarje->fields['tarje']);
    $tarjedeb = floatval($rstarjedeb->fields['tarje']);
    $tcheque = floatval($rscheque->fields['cheque']);
    $ttransfer = floatval($rstransfer->fields['transfer']);

    //Disponible actual caja
    $totalteorico = $tefe + $montobre + $tarje + $tcheque + $ttransfer - $tpagos;
    $totalteoricoefe = $tefe + $montobre - $tpagos;

    //total en monedas extranjeras pero convertidas a gs
    $buscar = "select sum(subtotal) as tmone from caja_moneda_extra where idcaja=$idcaja and estado=1";
    $extra = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $textra = floatval($extra->fields['tmone']);

    //total en monedas arqueadas
    $buscar = "select sum(subtotal) as total from caja_billetes where idcaja=$idcaja  and estado=1";
    $tarqueo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tarquegs = intval($tarqueo->fields['total']);

    //Cobranza pendiente x delivery
    $buscar = "Select  sum(total_cobrado) as totalpend  from gest_pagos 
	where 
	estado=1 
	and idcaja=$idcaja 
	and rendido ='N'";
    $rspend = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tpendi1 = floatval($rspend->fields['totalpend']);

    // total vouchers
    $consulta = "
	select sum(total_vouchers) as totalvouchers 
	from caja_vouchers 
	where 
	estado <> 6 
	and idcaja = $idcaja
	";
    $rsvo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totalvouchers = floatval($rsvo->fields['totalvouchers']);

    // calculos

    // efectivo en moneda extrangera + moneda nacional
    $neto = $textra + $tarquegs;
    $totalarqueado = $neto;

    // todo el efectivo de todas las monedas + tarjetas de credito + cheque + transfer
    $subtotal = $neto + $tarje + $tcheque + $ttransfer - ($tpagos);

    $total_arqueado_ef = $totalarqueado;

    //Vemos el faltante y sobrante
    //$sobrante=$totalarqueado-$totalteoricoefe;
    //$faltante=$totalteoricoefe-$totalarqueado;
    //$sobrante=($totalarqueado+$totalvouchers)-;
    $sobrante = ($totalarqueado + $totalvouchers) - ($tefe + $tarje + $tcheque + $ttransfer - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura']);
    $faltante = ($tefe + $tarje + $tcheque + $ttransfer - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura']) - ($totalarqueado + $totalvouchers);
    if ($sobrante < 0) {
        $sobrante = 0;
    }
    if ($faltante < 0) {
        $faltante = 0;
    }
    $dispo = ($subtotal + $trepo) - $tretirosgs;
    $ape = $rscaja->fields['monto_apertura'];
    $ape_ch = $rscaja->fields['caja_chica'];
    $montocierre = floatval($textra) + floatval($tarquegs) + floatval($totalvouchers);
    $dispo = floatval($dispo);
    $ahora = date("Y-m-d H:i:s");

    //Registramos
    $update = "
	update caja_super 
	set 
		monto_cierre=$montocierre,
		total_cobros_dia=$tcobranza,
		total_pagos_dia=$tpagos,
		faltante=$faltante,
		sobrante=$sobrante,
		total_efectivo=$tefe,
		total_tarjeta=$tarjecred,
		total_tarjeta_debito=$tarjedeb,
		total_cheque=$tcheque,
		total_transfer=$ttransfer,
		total_global_gs=$dispo,
		total_entrega_gs=$tretirosgs,
		total_credito=$cred,
		total_reposiciones_gs=$trepo, 
		total_pend = $tpendi1, 
		caja_chica_cierre=$caja_chica_cierre, 
		total_pagos_dia_ch = $tpagos_ch,
		total_vouchers = $totalvouchers,
		total_arqueado_ef = $total_arqueado_ef,
		recalculado_por = $idusu,
		recalculado_el = '$ahora'
	where 
		idcaja=$idcaja
		and estado_caja=3
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    //return $update;
    return "ok";

}
function redondear_billete($numero, $ceros, $arriba = 'S')
{
    $cero_add = "";
    for ($i = 1;$i <= $ceros;$i++) {
        $cero_add .= "0";
    }
    $redondeador = "1".$cero_add;
    if ($arriba == 'S') {
        $res = (ceil($numero / $redondeador) * $redondeador);
    } else {
        $res = (floor($numero / $redondeador) * $redondeador);
    }
    return $res;
}
function actualiza_saldos_clientes($idcliente, $idadherente = 0, $idserviciocom = 0)
{
    // variables globales
    global $idempresa;
    global $idsucursal;
    global $conexion;
    global $ahora;
    global $idusu;

    // limpia variables
    $idcliente = intval($idcliente);
    $idadherente = intval($idadherente);
    $idserviciocom = intval($idserviciocom);

    // si hay cliente
    if ($idcliente > 0) {
        // actualiza saldo del cliente
        $consulta = "
		update cliente set 
		saldo_sobregiro = linea_sobregiro 
						  - COALESCE((
							select sum(cuentas_clientes.saldo_activo) as saldoactivo 
							from cuentas_clientes 
							where 
							cuentas_clientes.idcliente = cliente.idcliente 
							and cuentas_clientes.estado <> 6
						  ),0) 
						  + COALESCE((
						  select sum(pagos_afavor_adh.saldo) as saldoafavor
						  from  pagos_afavor_adh
						  where
						  pagos_afavor_adh.idcliente = cliente.idcliente
						  and pagos_afavor_adh.estado <> 6
						  ),0)
		where 
		cliente.idcliente = $idcliente
		";
        //echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // si hay adherente
        if ($idadherente > 0) {

            // actualiza saldo del adherente
            $consulta = "
			update adherentes set 
			disponible = linea_sobregiro
						 - COALESCE((
							select sum(cuentas_clientes.saldo_activo) as saldoactivo 
							from cuentas_clientes 
							where 
							cuentas_clientes.idcliente = adherentes.idcliente 
							and cuentas_clientes.idadherente = adherentes.idadherente
							and cuentas_clientes.estado <> 6
						),0)
						+ COALESCE((
						  select sum(pagos_afavor_adh.saldo) as saldoafavor
						  from  pagos_afavor_adh
						  where
						  pagos_afavor_adh.idcliente = adherentes.idcliente 
						  and pagos_afavor_adh.idadherente = adherentes.idadherente
						  and pagos_afavor_adh.estado <> 6
						),0)
			where 
			adherentes.idcliente = $idcliente
			and adherentes.idadherente = $idadherente
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si hay servicio comida
            if ($idserviciocom > 0) {

                // actualiza saldo del servicio de comida
                $consulta = "
				update adherentes_servicioscom set 
				disponibleserv = linea_credito
								- COALESCE((
									select sum(cuentas_clientes.saldo_activo) as saldoactivo 
									from cuentas_clientes 
									where 
									cuentas_clientes.idcliente = adherentes_servicioscom.idcliente 
									and cuentas_clientes.idadherente = adherentes_servicioscom.idadherente
									and cuentas_clientes.idserviciocom = adherentes_servicioscom.idserviciocom
									and cuentas_clientes.estado <> 6
								  ),0)
								+ COALESCE((
								  select sum(pagos_afavor_adh.saldo) as saldoafavor
								  from  pagos_afavor_adh
								  where
								  pagos_afavor_adh.idcliente = adherentes_servicioscom.idcliente 
  								  and pagos_afavor_adh.idadherente = adherentes_servicioscom.idadherente
								  and pagos_afavor_adh.idserviciocom = adherentes_servicioscom.idserviciocom
								  and pagos_afavor_adh.estado <> 6
								),0)
				where 
				adherentes_servicioscom.idcliente = $idcliente
				and adherentes_servicioscom.idadherente = $idadherente
				and adherentes_servicioscom.idserviciocom = $idserviciocom
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                //echo $consulta;exit;


            }


        }

    }



}
function acredita_saldoafavor($idcliente)
{
    echo "la funcion acredita_saldoafavor quedo deprecada.";
    exit;
    // variables globales
    global $idempresa;
    global $idsucursal;
    global $conexion;
    global $ahora;
    global $idusu;

    // limpia variables e inicializa
    $idcliente = intval($idcliente);
    $idadherente = intval($idadherente);
    $idserviciocom = intval($idserviciocom);

    // busca pagos a favor
    $consulta = "
	select *, pagos_afavor_adh.saldo as saldoafavor
	from  pagos_afavor_adh
	where
	pagos_afavor_adh.idcliente = $idcliente
	and pagos_afavor_adh.saldo > 0
	and pagos_afavor_adh.estado <> 6
	order by fechahora asc, idpago_afavor asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // recorre y paga cuentas hasta que no quede saldo
    while (!$rs->EOF) {
        $idpago_afavor = $rs->fields['idpago_afavor'];
        $saldoactivo = $rs->fields['saldoafavor'];
        $idpago = $rs->fields['idpago'];
        $idadherente = intval($rs->fields['idadherente']);
        $idserviciocom = intval($rs->fields['idserviciocom']);
        $dispoact = $saldoactivo;
        while ($dispoact > 0) {
            // busca las cuentas por servicio
            $consulta = "
			select *, cuentas_clientes.saldo_activo
			from cuentas_clientes 
			where 
			cuentas_clientes.idcliente = $idcliente
			and cuentas_clientes.idadherente = $idadherente
			and cuentas_clientes.idserviciocom = $idserviciocom
			and cuentas_clientes.saldo_activo > 0
			and cuentas_clientes.idempresa = $idempresa
			and cuentas_clientes.estado <> 6
			order by registrado_el asc, idcta asc
			";
            $rscuen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcta = intval($rscuen->fields['idcta']);
            // si encuentra recorre y abona
            if ($idcta > 0) {
                // recorre cada cuenta
                while (!$rscuen->EOF) {
                    $idcta = $rscuen->fields['idcta'];
                    $saldoactivo_cuenta = intval($rscuen->fields['saldo_activo']);
                    $idadherente = intval($rscuen->fields['idadherente']);
                    $idserviciocom = intval($rscuen->fields['idserviciocom']);

                    // si el pago a favor es mayor a la cuenta
                    if ($dispoact > $saldoactivo_cuenta) {
                        $monto_abonado = $saldoactivo_cuenta;

                        // registra el pago
                        $consulta = "
						INSERT INTO 
						cuentas_clientes_pagos
						(fecha_pago, idpago, idpago_afavor, idcuenta, idcliente, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, 
						banco, chequenum, estado, sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idadherente, idserviciocom) 
						VALUES 
						('$ahora', $idpago, $idpago_afavor, $idcta, $idcliente, $monto_abonado, $idusu, $idempresa, $monto_abonado, 0, 0, 0, 1, 
						$idsucursal, 0, $monto_abonado, 0, NULL, $idadherente, $idserviciocom)
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // cera el saldo de la cuenta
                        $consulta = "
						update cuentas_clientes 
						set
						saldo_activo = 0
						where
						idcliente = $idcliente
						and idadherente = $idadherente
						and idserviciocom = $idserviciocom
						and cuentas_clientes.idcta = $idcta
						and cuentas_clientes.estado <> 6
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // resta al saldo de pago lo abonado
                        $dispoact = $dispoact - $saldoactivo_cuenta;

                        // si el pago a favor es menor a la cuenta
                    } elseif ($dispoact < $saldoactivo_cuenta) {
                        $monto_abonado = $dispoact;
                        $saldonew = $saldoactivo_cuenta - $monto_abonado;

                        // registra el pago
                        $consulta = "
						INSERT INTO 
						cuentas_clientes_pagos
						(fecha_pago, idpago, idpago_afavor, idcuenta, idcliente, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, 
						banco, chequenum, estado, sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idadherente, idserviciocom) 
						VALUES 
						('$ahora', $idpago, $idpago_afavor, $idcta, $idcliente, $monto_abonado, $idusu, $idempresa, $monto_abonado, 0, 0, 0, 1, 
						$idsucursal, 0, $monto_abonado, 0, NULL, $idadherente, $idserviciocom)
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // pone la diferencia como saldo de la cuenta
                        $consulta = "
						update cuentas_clientes 
						set
						saldo_activo = $saldonew
						where
						idcliente = $idcliente
						and idadherente = $idadherente
						and idserviciocom = $idserviciocom
						and cuentas_clientes.idcta = $idcta
						and cuentas_clientes.estado <> 6
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // cera el saldo de pago
                        $dispoact = 0;
                        break 2;


                        // si el pago a favor es igual a la cuenta
                    } else {
                        $monto_abonado = $dispoact;

                        // registra el pago
                        $consulta = "
						INSERT INTO 
						cuentas_clientes_pagos
						(fecha_pago, idpago, idpago_afavor, idcuenta, idcliente, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, 
						banco, chequenum, estado, sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idadherente, idserviciocom) 
						VALUES 
						('$ahora', $idpago, $idpago_afavor, $idcta, $idcliente, $monto_abonado, $idusu, $idempresa, $monto_abonado, 0, 0, 0, 1, 
						$idsucursal, 0, $monto_abonado, 0, NULL, $idadherente, $idserviciocom)
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // cera el saldo de la cuenta
                        $consulta = "
						update cuentas_clientes 
						set
						saldo_activo = 0
						where
						idcliente = $idcliente
						and idadherente = $idadherente
						and idserviciocom = $idserviciocom
						and cuentas_clientes.idcta = $idcta
						and cuentas_clientes.estado <> 6
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // cera el saldo de pago
                        $dispoact = 0;
                        break 2;
                    }



                    $rscuen->MoveNext();
                }


            } else { // if($idcta > 0){
                break;
            }

        } // while($dispoact > 0){

        $rs->MoveNext();
    }

    $consulta = "
	update pagos_afavor_adh 
	set saldo = COALESCE(monto,0)-COALESCE((
		select sum(cuentas_clientes_pagos.monto_abonado) 
		from cuentas_clientes_pagos
		where
		cuentas_clientes_pagos.idcliente = pagos_afavor_adh.idcliente
		and cuentas_clientes_pagos.idadherente = pagos_afavor_adh.idadherente
		and cuentas_clientes_pagos.idserviciocom = pagos_afavor_adh.idserviciocom
		and cuentas_clientes_pagos.idpago_afavor = pagos_afavor_adh.idpago_afavor
		and cuentas_clientes_pagos.idempresa = $idempresa
		and cuentas_clientes_pagos.estado <> 6
		),0)
	where
	 pagos_afavor_adh.idempresa = $idempresa
	 and pagos_afavor_adh.estado <> 6
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
function campo_radio($consulta, $parametros_array)
{
    global $conexion;

    // parametros
    $nombre_campo = $parametros_array['nombre_campo']; // name html
    $nombre_campo_bd = $parametros_array['nombre_campo_bd']; // name bd
    $id_campo_bd = $parametros_array['id_campo_bd']; // id bd
    $value_selected = $parametros_array['value_selected']; // valor seleccionado
    $separador = $parametros_array['separador']; // separador

    // conversiones
    if (trim($separador) == '') {
        $separador = "<br />";
    }

    // trae datos de la bd
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // construye campo
    $i = 1;
    while (!$rs->EOF) {
        // valor actual
        $value_actual = $rs->fields[$id_campo_bd];
        $name_actual = $rs->fields[$nombre_campo_bd];
        // si es el valor seleccionado
        if ($value_selected == $value_actual) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
        $html .= '<input type="radio" name="'.$nombre_campo.'" id="'.$nombre_campo.'_'.$i.'" value="'.$value_actual.'" '.$checked.' /> <span class="radioauto">'.$name_actual.'</span>'.$separador;
        $i++;
        $rs->MoveNext();
    }

    return $html;
}
function regladetres($valor_1, $resultado_1, $valor_2)
{
    // 	valor_1 ------ resultado_1
    //  valor_2 ------    ?
    $resultado_2 = ($valor_2 * $resultado_1) / $valor_1;
    return $resultado_2;
}
function orden_pago_valida($parametros_array)
{

    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (intval($parametros_array['idordenpago']) == 0) {
        $valido = "N";
        $errores .= " - El campo idordenpago no puede ser cero o nulo.<br />";
    }
    if (floatval($parametros_array['monto_abonado']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_abonado no puede ser cero o negativo.<br />";
    }

    $idordenpago = $parametros_array['idordenpago'];
    $consulta = "
	select * 
	from orden_pago
	inner join orden_pago_det on orden_pago.idordenpago = orden_pago_det.idordenpago
	where 
	orden_pago.estado = 3 
	and orden_pago.idordenpago = $idordenpago
	";
    //echo $consulta;
    //exit;
    $rsorddet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsorddet->fields['idordenpago']) == 0) {
        $errores .= "- Orden de pago inexistente o no autorizada.<br />";
        $valido = "N";
    }

    while (!$rsorddet->EOF) {

        $parametros_array['idfactura'] = $rsorddet->fields['idfactura'];
        $parametros_array['monto_abonar'] = $rsorddet->fields['monto_abonar'];

        //print_r($parametros_array);
        //exit;

        $res = pago_proveedor_valida($parametros_array);
        $valido = $res['valido'];
        $errores = $res['errores'];

        $rsorddet->MoveNext();
    }

    $respuesta = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $respuesta;

}


function pago_proveedor_valida($parametros_array)
{

    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (intval($parametros_array['idformapago']) == 0) {
        $valido = "N";
        $errores .= " - El campo idformapago no puede ser cero o nulo.<br />";
    }
    // si no es una nota de credito
    if (intval($parametros_array['idformapago']) != 12) {
        $idforma = intval($parametros_array['idformapago']);
        $consulta = "
		select afecta_caja from formas_pago2 where idforma = $idforma
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rs->fields['afecta_caja'] == 'S') {
            if (intval($parametros_array['idcaja']) == 0) {
                $valido = "N";
                $errores .= " - El campo idcajagestion no puede ser cero o nulo para la forma de pago seleccionada.<br />";
            }
        }
    }
    if (trim($parametros_array['fecha_pago']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_pago no puede estar vacio.<br />";
    }

    if (intval($parametros_array['cajero']) == 0) {
        $valido = "N";
        $errores .= " - El campo cajero no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['registrado_por']) == 0) {
        $valido = "N";
        $errores .= " - El campo registrado_por no puede ser cero o nulo.<br />";
    }


    $idfactura = intval($parametros_array['idfactura']);
    $montoabonar = floatval($parametros_array['monto_abonar']);
    if (intval($idfactura) == 0) {
        $valido = "N";
        $errores .= " - El campo idfactura no puede ser cero o nulo.<br />";
    }
    if (intval($montoabonar) == 0) {
        $valido = "N";
        $errores .= " - El campo montoabonar no puede ser cero o nulo.<br />";
    }

    // conversiones
    $montoabonar_txt = formatomoneda($montoabonar, 0, "N");
    // validaciones si la factura es a credito
    $consulta = "
	select *
	from facturas_proveedores 
	where 
	facturas_proveedores.id_factura = $idfactura
	";
    $rsfacprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_factura = $rsfacprov->fields['tipo_factura'];
    $saldo_factura = $rsfacprov->fields['saldo_factura'];
    $estado_factura = $rsfacprov->fields['estado'];
    $estado_carga = $rsfacprov->fields['estado_carga'];
    $saldo_factura_txt = formatomoneda($saldo_factura, 0, "N");
    $factura = trim($rsfacprov->fields['factura_numero']);
    // si el estado_carga no es 3
    if ($estado_carga != 3) {
        $valido = "N";
        $errores .= " - No se puede abonar la factura $factura por que su carga de items no finalizo aun.<br />";
    }
    if ($estado_factura != 1) {
        $valido = "N";
        $errores .= " - No se puede abonar la factura $factura [$idfactura] por que se encuentra anulada.<br />";
    }
    // si es al contado
    if ($tipo_factura == 1) {
        if ($montoabonar > $saldo_factura) {
            $valido = "N";
            $errores .= " - El monto a abonar ($montoabonar_txt) de la factura $factura es mayor al saldo ($saldo_factura_txt).<br />";
        }
    }
    // si es a credito
    if ($tipo_factura == 2) {
        // valida el monto a abonar no sea superior que la cuenta en operaciones_proveedores
        $consulta = "
		select sum(operaciones_proveedores.saldo_factura) as saldo_factura, facturas_proveedores.id_factura, 
		facturas_proveedores.factura_numero
		from operaciones_proveedores 
		inner join facturas_proveedores on facturas_proveedores.id_factura = operaciones_proveedores.idfactura
		where  
		operaciones_proveedores.idfactura = $idfactura
		and operaciones_proveedores.estado <> 6
		group by facturas_proveedores.id_factura, facturas_proveedores.factura_numero
		order by facturas_proveedores.id_factura asc
		";
        $rsopprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $saldo_factura = floatval($rsopprov->fields['saldo_factura']);
        $factura = trim($rsopprov->fields['factura_numero']);

        $saldo_factura_txt = formatomoneda($saldo_factura, 0, "N");
        if ($montoabonar > $saldo_factura) {
            $valido = "N";
            $errores .= " - El monto a abonar ($montoabonar_txt) de la factura $factura es mayor al saldo ($saldo_factura_txt).<br />";
        }

    }


    $respuesta = [
        'valido' => $valido,
        'errores' => $errores,
        'version' => 2
    ];

    return $respuesta;



}
function orden_pago_registra($parametros_array)
{
    global $conexion;
    global $ahora;

    $idordenpago = antisqlinyeccion($parametros_array['idordenpago'], "int");

    // cabecera de orden de pago
    $consulta = "
	select * from orden_pago where idordenpago = $idordenpago and estado = 3
	";
    $rsorp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproveedor = intval($rsorp->fields['idproveedor']);

    $consulta = "
	select *, 
	(select tipo_factura from facturas_proveedores where id_factura = orden_pago_det.idfactura and estado <> 6 ) as tipo_factura,
	(select saldo_factura from facturas_proveedores where id_factura = orden_pago_det.idfactura and estado <> 6 ) as saldo_factura
	from orden_pago_det 
	where 
	idordenpago = $idordenpago
	";
    $rsorpdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    while (!$rsorpdet->EOF) {
        $monto_abonar = floatval($rsorpdet->fields['monto_abonar']);
        $idfactura = $rsorpdet->fields['idfactura'];
        $idordenpagodet = intval($rsorpdet->fields['idordenpagodet']);
        //$tipofactura=$rsorpdet->fields['tipo_factura']; //  1-CONTADO 2-CREDITO
        //$saldo_factura=$rsorpdet->fields['saldo_factura'];
        $parametros_array['monto_abonar'] = $monto_abonar;
        $parametros_array['idfactura'] = $idfactura;
        $parametros_array['idordenpagodet'] = $idordenpagodet;


        pago_proveedor_registra($parametros_array);


        $rsorpdet->MoveNext();
    } // while(!$rsorpdet->EOF){



}
function pago_proveedor_registra($parametros_array)
{

    global $conexion;
    global $ahora;


    //print_r($parametros_array);
    $idordenpago = antisqlinyeccion($parametros_array['idordenpago'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $monto_abonado_global = antisqlinyeccion($parametros_array['monto_abonado'], "float");
    $idnotacredito = antisqlinyeccion($parametros_array['idnotacredito'], "int");

    // cabecera
    $idcaja = antisqlinyeccion(intval($parametros_array['idcaja']), "int");
    $fecha_pago = antisqlinyeccion($parametros_array['fecha_pago'], "text");
    $cajero = antisqlinyeccion($parametros_array['cajero'], "int");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");



    //while(!$rsorpdet->EOF){
    $monto_abonar = floatval($parametros_array['monto_abonar']);
    $idfactura = intval($parametros_array['idfactura']);
    $idordenpagodet = intval($parametros_array['idordenpagodet']);
    $idnotacreditodet = intval($parametros_array['idnotacreditodet']);

    $consulta = "
	select * from facturas_proveedores where id_factura = $idfactura and estado <> 6 limit 1
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $tipofactura = $rsfac->fields['tipo_factura']; //  1-CONTADO 2-CREDITO
    $saldo_factura = $rsfac->fields['saldo_factura'];
    $idproveedor = $rsfac->fields['id_proveedor'];

    // recorre e inserta en detalle mientras haya saldo cobrado
    $monto_abonar_dispo = $monto_abonar;
    while ($monto_abonar_dispo > 0) {



        // inserta en la cabecera
        $consulta = "
		insert into pagos_proveedores_cab
		(idcaja, fecha_pago, cajero, estado, monto_abonado, idordenpago, idproveedor, monto_efectivo, monto_cheque, monto_transferencia, monto_tarjeta, idbanco, idcuentacon, cheque_nro, transfer_nro, registrado_por, registrado_el, boleta_nro, monto_boleta, idnotacredito)
		values
		($idcaja, $fecha_pago, $cajero, $estado, $monto_abonar, $idordenpago, $idproveedor, 0, 0, 0, 0, NULL, NULL, NULL, NULL, $registrado_por, $registrado_el, NULL, 0, $idnotacredito)
		";
        //echo $consulta;
        //exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // obtiene el id insertado
        $consulta = "
		select idpagoprovcab from pagos_proveedores_cab where idordenpago=$idordenpago order by idpagoprovcab desc
		";
        $rsmaxpagoprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpagoprovcab = $rsmaxpagoprov->fields['idpagoprovcab'];


        // si es contado
        if ($tipofactura == 1) {

            $consulta = "
			insert into pagos_proveedores_det
			(idpagoprovcab, periodo, monto_abonado, idfactura, idoperacionprov, idordenpagodet, idnotacreditodet)
			values
			($idpagoprovcab, NULL, $monto_abonar_dispo, $idfactura, NULL, $idordenpagodet, $idnotacreditodet)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




            // actualiza tabla de facturas_proveedores con
            $consulta = "
			update facturas_proveedores
			set 
			cobrado_factura = coalesce((
					select 
					sum(pagos_proveedores_det.monto_abonado) as monto_abonado 
					from pagos_proveedores_cab 
					inner join pagos_proveedores_det on pagos_proveedores_cab.idpagoprovcab = pagos_proveedores_det.idpagoprovcab
					where 
					pagos_proveedores_det.idfactura = facturas_proveedores.id_factura 
					and pagos_proveedores_cab.estado <> 6
			),0)
			where 
			facturas_proveedores.id_factura = $idfactura
			and tipo_factura = 1
			and facturas_proveedores.estado <> 6
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            $monto_abonar_dispo = 0;

        }

        // si es a credito
        if ($tipofactura == 2) {
            // busca el detalle de esa operacion
            $consulta = "
			select * 
			from 
				operaciones_proveedores 
				inner join operaciones_proveedores_detalle  on 
				operaciones_proveedores.idoperacionprov = operaciones_proveedores_detalle.idoperacionprov
			where 
				operaciones_proveedores.idfactura = $idfactura 
				and operaciones_proveedores_detalle.saldo_cuota > 0
			order by operaciones_proveedores.idoperacionprov asc, operaciones_proveedores_detalle.periodo asc
			";
            $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // recorre la tabla de detalles y acredita
            while (!$rsdet->EOF) {


                $idoperacionprov = intval($rsdet->fields['idoperacionprov']);
                $periodo = intval($rsdet->fields['periodo']);
                $saldo_cuota_det = floatval($rsdet->fields['saldo_cuota']);

                //echo "dispo:".$monto_abonar_dispo."<br />";
                //echo "saldo:".$saldo_cuota_det."<br />";


                // calcula el nuevo saldo disponible y el monto a acreditar al pago
                if ($monto_abonar_dispo > $saldo_cuota_det) {
                    $monto_abonado_pag = $saldo_cuota_det;
                    $monto_abonar_dispo = $monto_abonar_dispo - $saldo_cuota_det;
                } elseif ($monto_abonar_dispo < $saldo_cuota_det) {
                    $monto_abonado_pag = $monto_abonar_dispo;
                    $monto_abonar_dispo = 0;
                } elseif ($monto_abonar_dispo == $saldo_cuota_det) {
                    $monto_abonado_pag = $saldo_cuota_det;
                    $monto_abonar_dispo = 0;
                }
                //echo "abonado:".$monto_abonado_pag."<br />";

                $consulta = "
				insert into pagos_proveedores_det
				(idpagoprovcab, periodo, monto_abonado, idfactura, idoperacionprov, idordenpagodet, idnotacreditodet)
				values
				($idpagoprovcab, $periodo, $monto_abonado_pag, $idfactura, $idoperacionprov, $idordenpagodet, $idnotacreditodet)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



                // actualiza tabla de la cuenta, detalle y cabecera
                $consulta = "
				update operaciones_proveedores_detalle
				set 
				cobra_cuota = coalesce((
					select 
					sum(pagos_proveedores_det.monto_abonado) as monto_abonado 
					from pagos_proveedores_det 
					inner join pagos_proveedores_cab on pagos_proveedores_cab.idpagoprovcab = pagos_proveedores_det.idpagoprovcab
					where 
					pagos_proveedores_det.idoperacionprov = operaciones_proveedores_detalle.idoperacionprov
					and pagos_proveedores_det.periodo = operaciones_proveedores_detalle.periodo
					and pagos_proveedores_cab.estado <> 6
					),0),
				fecha_ultpago = (
					select max(fecha_pago) as fecha_pago
					from pagos_proveedores_det
					inner join pagos_proveedores_cab on pagos_proveedores_cab.idpagoprovcab = pagos_proveedores_det.idpagoprovcab
					where 
					pagos_proveedores_det.idoperacionprov = operaciones_proveedores_detalle.idoperacionprov
					and pagos_proveedores_det.periodo = operaciones_proveedores_detalle.periodo
					and pagos_proveedores_cab.estado <> 6
					)
				where
				idoperacionprov = $idoperacionprov
				and periodo = $periodo
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // actualiza saldo
                $consulta = "
				update operaciones_proveedores_detalle
				set 
				saldo_cuota = coalesce(monto_cuota,0)-coalesce(cobra_cuota,0)-coalesce(quita_cuota,0)
				where
				idoperacionprov = $idoperacionprov
				and periodo = $periodo
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // actualiza cancelacion
                $consulta = "
				update operaciones_proveedores_detalle
				set 
				fecha_can = fecha_ultpago,
				dias_atraso = 0,
				dias_pago = fecha_ultpago-vencimiento,
				estado_saldo = 3
				where
				idoperacionprov = $idoperacionprov
				and periodo = $periodo
				and saldo_cuota = 0
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                if ($monto_abonar_dispo == 0) {
                    break;
                }

                $rsdet->MoveNext();
            }

            // update cabecera de pagos contra su detalle en orden pago
            $consulta = "
			update pagos_proveedores_cab 
			set 
			monto_abonado = (select sum(pagos_proveedores_det.monto_abonado) from pagos_proveedores_det where idpagoprovcab = pagos_proveedores_cab.idpagoprovcab)
			where
			idordenpago = $idordenpago
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // update cabecera de pagos contra su detalle en nota credito
            $consulta = "
			update pagos_proveedores_cab 
			set 
			monto_abonado = (select sum(pagos_proveedores_det.monto_abonado) from pagos_proveedores_det where idpagoprovcab = pagos_proveedores_cab.idpagoprovcab)
			where
			idnotacredito = $idnotacredito
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // update operaciones_proveedores con su detalle
            $consulta = "
			update operaciones_proveedores
			set 
			abonado_factura = coalesce((
				select sum(cobra_cuota) 
				from operaciones_proveedores_detalle 
				where 
				idoperacionprov = operaciones_proveedores.idoperacionprov
				),0)
			where
			idoperacionprov = $idoperacionprov
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // update saldos en operaciones_proveedores
            $consulta = "
			update operaciones_proveedores
			set 
			saldo_factura = coalesce(monto_factura,0)-coalesce(abonado_factura,0)-coalesce(quita_factura,0)
			where
			idoperacionprov = $idoperacionprov
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza facturas proveedores con operaciones_proveedores
            $consulta = "
			update facturas_proveedores
			set 
			cobrado_factura = coalesce((
				select sum(abonado_factura) 
				from operaciones_proveedores
				where
				idfactura = facturas_proveedores.id_factura
			),0)
			where 
			facturas_proveedores.id_factura = $idfactura
			and tipo_factura = 2
			and facturas_proveedores.estado <> 6
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        } //if($tipofactura == 2){



    } // while($monto_abonar_dispo > 0){


    // actualiza la cabecera pagos_proveedores_cab

    // actualiza saldos en factura, sin importar credito o contado
    $consulta = "
	update facturas_proveedores 
	set 
	saldo_factura = coalesce(total_factura,0)-coalesce(cobrado_factura,0)-coalesce(quita_factura,0)
	where 
	facturas_proveedores.id_factura = $idfactura
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    //$rsorpdet->MoveNext(); } // while(!$rsorpdet->EOF){




}
function pago_proveedor_valida_anulacion($parametros_array)
{

    global $conexion;

    $idordenpago = intval($parametros_array['idordenpago']);
    $anulado_por = $parametros_array['anulado_por'];
    $idnotacredito = intval($parametros_array['idnotacredito']);
    if ($idnotacredito == 0) {
        if (intval($idordenpago) == 0) {
            $valido = "N";
            $errores .= " - El campo idordenpago no puede ser cero o menor a cero.<br />";
        }
    }
    if ($idordenpago == 0) {
        if (intval($idnotacredito) == 0) {
            $valido = "N";
            $errores .= " - El campo idnotacredito no puede ser cero o menor a cero.<br />";
        }
    }
    if (intval($anulado_por) == 0) {
        $valido = "N";
        $errores .= " - El campo anulado_por no puede ser cero o menor a cero.<br />";
    }
    if ($idordenpago > 0) {
        $consulta = "
		select * 
		from orden_pago 
		where
		idordenpago = $idordenpago
		and estado <> 6
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rs->fields['idordenpago']) == 0) {
            $valido = "N";
            $errores .= " - Orden de pago inexitente o ya fue anulada.<br />";
        }
        // validar anulacion de caja
        $idcajamov = intval($rs->fields['idcajamov']);
        $parametros_array = [
            'idcajamov' => $idcajamov,
            'anulado_por' => $anulado_por
        ];
        // si existe movimiento de caja
        if ($idcajamov > 0) {
            $rescaj = valida_anulacion_caja_mov($parametros_array);
            if ($rescaj['valido'] == 'N') {
                $valido = $rescaj['valido'];
                $errores .= $rescaj['errores'];
            }
        }
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;



}
function pago_proveedor_anula($parametros_array)
{

    global $conexion;
    global $ahora;

    $idordenpago = intval($parametros_array['idordenpago']);
    $idnotacredito = intval($parametros_array['idnotacredito']);
    $anulado_por = intval($parametros_array['anulado_por']);

    // si se genero con una orden de pago
    if ($idordenpago > 0) {
        $consulta = "
		select orden_pago.*,  orden_pago_det.idfactura
		from orden_pago 
		inner join orden_pago_det on orden_pago_det.idordenpago = orden_pago.idordenpago
		where
		orden_pago.idordenpago = $idordenpago
		and orden_pago.estado <> 6
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcajamov = $rs->fields['idcajamov'];

        // anular pago proveedor cab
        $consulta = "
		update pagos_proveedores_cab
		set 
			estado = 6	
		WHERE
			idordenpago = $idordenpago
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // anular movimiento de caja
        $parametros_array_caj = [
            'idcajamov' => $idcajamov,
            'anulado_por' => $anulado_por
        ];
        anula_caja_mov($parametros_array_caj);


        // anular orden de pago
        $consulta = "
		update orden_pago
		set
			estado = 6,
			anulado_por = $anulado_por,
			anulado_el = '$ahora'
		where
			idordenpago = $idordenpago
			and estado = 4
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }
    // si se genero con una nota de credito
    if ($idnotacredito > 0) {
        $consulta = "
		select nota_credito_cuerpo_proveedor.id_factura as idfactura
		from nota_credito_cuerpo_proveedor 
		inner join nota_credito_cabeza_proveedor on nota_credito_cabeza_proveedor.idnotacred = nota_credito_cuerpo_proveedor.idnotacred
		where
		estado <> 6
		and nota_credito_cuerpo_proveedor.idnotacred = $idnotacredito
		and nota_credito_cuerpo_proveedor.id_factura is not null
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // anular pago proveedor cab
        $consulta = "
		update pagos_proveedores_cab
		set 
			estado = 6	
		WHERE
			idnotacredito = $idnotacredito
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }




    // restaurar saldos con centinela
    while (!$rs->EOF) {
        $idfactura = $rs->fields['idfactura'];
        centinela_fact_prov($idfactura);
        $rs->MoveNext();
    }




}
function centinela_fact_prov($idfactura)
{

    global $conexion;

    // busca si es al contado o a credito
    $consulta = "
	select * 
	from facturas_proveedores
	where
	id_factura = $idfactura
	and estado <> 6
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_factura = $rs->fields['tipo_factura']; // 1 contado 2 credito
    $idfactura = $rs->fields['id_factura'];

    if ($tipo_factura == 1) {
        // actualiza tabla de facturas_proveedores con
        $consulta = "
		update facturas_proveedores
		set 
		cobrado_factura = coalesce((
				select 
				sum(pagos_proveedores_det.monto_abonado) as monto_abonado 
				from pagos_proveedores_cab 
				inner join pagos_proveedores_det on pagos_proveedores_cab.idpagoprovcab = pagos_proveedores_det.idpagoprovcab
				where 
				pagos_proveedores_det.idfactura = facturas_proveedores.id_factura 
				and pagos_proveedores_cab.estado <> 6
		),0)
		where 
		facturas_proveedores.id_factura = $idfactura
		and tipo_factura = 1
		and facturas_proveedores.estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    if ($tipo_factura == 2) {

        $consulta = "
		select * 
		from operaciones_proveedores 
		where 
		idfactura = $idfactura
		and estado <> 6
		";
        $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idoperacionprov = $rsop->fields['idoperacionprov'];

        $consulta = "
		update operaciones_proveedores_detalle
			set 
			cobra_cuota = coalesce((
				select 
				sum(pagos_proveedores_det.monto_abonado) as monto_abonado 
				from pagos_proveedores_det 
				inner join pagos_proveedores_cab on pagos_proveedores_cab.idpagoprovcab = pagos_proveedores_det.idpagoprovcab
				where 
				pagos_proveedores_det.idoperacionprov = operaciones_proveedores_detalle.idoperacionprov
				and pagos_proveedores_det.periodo = operaciones_proveedores_detalle.periodo
				and pagos_proveedores_cab.estado <> 6
				),0),
			fecha_ultpago = (
				select max(fecha_pago) as fecha_pago
				from pagos_proveedores_det
				inner join pagos_proveedores_cab on pagos_proveedores_cab.idpagoprovcab = pagos_proveedores_det.idpagoprovcab
				where 
				pagos_proveedores_det.idoperacionprov = operaciones_proveedores_detalle.idoperacionprov
				and pagos_proveedores_det.periodo = operaciones_proveedores_detalle.periodo
				and pagos_proveedores_cab.estado <> 6
				)
		where
		idoperacionprov = $idoperacionprov
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza saldo
        $consulta = "
		update operaciones_proveedores_detalle
		set 
		saldo_cuota = coalesce(monto_cuota,0)-coalesce(cobra_cuota,0)-coalesce(quita_cuota,0)
		where
		idoperacionprov = $idoperacionprov
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // actualiza cancelacion
        $consulta = "
		update operaciones_proveedores_detalle
		set 
		fecha_can = fecha_ultpago,
		dias_atraso = 0,
		dias_pago = fecha_ultpago-vencimiento,
		estado_saldo = 3
		where
		idoperacionprov = $idoperacionprov
		and saldo_cuota = 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // actualiza no canceladas
        $consulta = "
		update operaciones_proveedores_detalle
		set 
		fecha_can = NULL,
		dias_atraso = fecha_ultpago-vencimiento,
		dias_pago = 0,
		estado_saldo = 1
		where
		idoperacionprov = $idoperacionprov
		and saldo_cuota > 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        // update operaciones_proveedores con su detalle
        $consulta = "
		update operaciones_proveedores
		set 
		abonado_factura = coalesce((
			select sum(cobra_cuota) 
			from operaciones_proveedores_detalle 
			where 
			idoperacionprov = operaciones_proveedores.idoperacionprov
			),0)
		where
		idoperacionprov = $idoperacionprov
		and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // update saldos en operaciones_proveedores
        $consulta = "
		update operaciones_proveedores
		set 
		saldo_factura = coalesce(monto_factura,0)-coalesce(abonado_factura,0)-coalesce(quita_factura,0)
		where
		idoperacionprov = $idoperacionprov
		and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza facturas proveedores con operaciones_proveedores
        $consulta = "
		update facturas_proveedores
		set 
		cobrado_factura = coalesce((
			select sum(operaciones_proveedores.abonado_factura) 
			from operaciones_proveedores
			where
			operaciones_proveedores.idfactura = facturas_proveedores.id_factura
			and operaciones_proveedores.estado <> 6
		),0)
		where 
		facturas_proveedores.id_factura = $idfactura
		and facturas_proveedores.tipo_factura = 2
		and facturas_proveedores.estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update operaciones_proveedores set
		plazo_periodo_abonado = (select max(periodo) from operaciones_proveedores_detalle where saldo_cuota = 0 and idoperacionprov = operaciones_proveedores.idoperacionprov  ),
		plazo_periodo_remanente = (select min(periodo) from operaciones_proveedores_detalle where saldo_cuota > 0 and idoperacionprov = operaciones_proveedores.idoperacionprov  ),
		dias_atraso = (select max(dias_atraso) from operaciones_proveedores_detalle where idoperacionprov = operaciones_proveedores.idoperacionprov ),
		max_atraso = (select max(dias_pago) from operaciones_proveedores_detalle where idoperacionprov = operaciones_proveedores.idoperacionprov ),
		prom_atraso = (select sum(dias_pago)/max(periodo) from operaciones_proveedores_detalle where idoperacionprov = operaciones_proveedores.idoperacionprov and fecha_can is not null ),
		fecha_ultimopago = (select max(fecha_ultpago) from operaciones_proveedores_detalle where idoperacionprov = operaciones_proveedores.idoperacionprov)
		where
		idoperacionprov = $idoperacionprov
		and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update operaciones_proveedores 
		set
		fecha_cancelacion = fecha_ultimopago
		where
		idoperacionprov = $idoperacionprov
		and estado <> 6
		and saldo_factura = 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update operaciones_proveedores 
		set
		fecha_cancelacion = NULL
		where
		idoperacionprov = $idoperacionprov
		and estado <> 6
		and saldo_factura > 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    // actualiza saldos en factura, sin importar credito o contado
    $consulta = "
	update facturas_proveedores 
	set 
	saldo_factura = coalesce(total_factura,0)-coalesce(cobrado_factura,0)-coalesce(quita_factura,0)
	where 
	facturas_proveedores.id_factura = $idfactura
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));





}
function completar($dato, $cantidad_total, $valor_completar)
{
    //retorna en formato char la cantidad indicada sirve para sucursal, pe y otras cosas
    $a = strlen($dato);

    $diferencia = $cantidad_total - $a;
    $r = '';
    if ($diferencia > 0) {
        for ($i = 1;$i <= $diferencia;$i++) {
            $r = $r."$valor_completar";
        }
    } else {
        $r = $dato;
    }
    $completo = $r.$dato;

    return ($completo);
}
function campo_select($consulta, $parametros_array, $conexion = '')
{

    global $conexion;


    // parametros
    $nombre_campo = $parametros_array['nombre_campo']; // name html
    $id_campo = $parametros_array['id_campo']; // id html
    $nombre_campo_bd = $parametros_array['nombre_campo_bd']; // name bd
    $id_campo_bd = $parametros_array['id_campo_bd']; // id bd
    $value_selected = $parametros_array['value_selected']; // valor seleccionado
    $pricampo_name = $parametros_array['pricampo_name']; //  nombre de la primera opcion
    $pricampo_value = $parametros_array['pricampo_value']; // valor de la primera opcion
    $style_input = $parametros_array['style_input'];
    $acciones = $parametros_array['acciones'];
    $autosel_1registro = $parametros_array['autosel_1registro']; // si hay 1 solo registro selecciona automaticamente en caso que sea 'S'
    $opciones_extra = $parametros_array['opciones_extra'];
    $cant_opciones_extra = intval(count($opciones_extra));
    $data_hidden = $parametros_array['data_hidden'];
    $data_hidden2 = $parametros_array['data_hidden2'];
    $data_hidden3 = $parametros_array['data_hidden3'];
    $data_hidden4 = $parametros_array['data_hidden4'];
    // trae datos de la bd
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $rs->MoveFirst();
    if ($autosel_1registro == 'S') {
        $cant_opciones = intval($rs->recordCount()) + $cant_opciones_extra;
    }


    // construye campo
    $html .= '<select name="'.$nombre_campo.'" id="'.$id_campo.'" '.$style_input.' '.$acciones.'>';
    // si la seleccion automatica esta desactivada
    if ($autosel_1registro != 'S') {
        $html .= '<option value="'.$pricampo_value.'">'.$pricampo_name.'</option>';
    }
    // si la seleccion automatica esta activada
    if ($autosel_1registro == 'S') {
        // si hay mas de una opcion o no hay ninguna
        if ($cant_opciones <> 1) {
            $html .= '<option value="'.$pricampo_value.'">'.$pricampo_name.'</option>';
        }
    }
    while (!$rs->EOF) {

        // valor actual
        $value_actual = trim($rs->fields[$id_campo_bd]);
        $name_actual = $rs->fields[$nombre_campo_bd];
        $value_hidden = "";
        if (isset($data_hidden)) {

            $value_hidden .= " data-hidden-value='". $rs->fields[$data_hidden]."' ";
        }
        if (isset($data_hidden2)) {

            $value_hidden .= " data-hidden-value2='". $rs->fields[$data_hidden2]."' ";
        }
        if (isset($data_hidden3)) {

            $value_hidden .= " data-hidden-value3='". $rs->fields[$data_hidden3]."' ";
        }
        if (isset($data_hidden4)) {

            $value_hidden .= " data-hidden-value4='". $rs->fields[$data_hidden4]."' ";
        }
        //echo $value_selected;
        //echo $value_actual;
        // si es el valor seleccionado
        if (trim($value_selected) == trim($value_actual)) {
            $selected = 'selected="selected"';
        } else {
            $selected = '';
        }
        // si la seleccion automatica esta activada y hay mas de 1 registro
        if ($autosel_1registro == 'S' && $cant_opciones < 2) {
            $selected = 'selected="selected"';
        }

        $html .= '<option value="'.$value_actual.'" '.$value_hidden.' '.$selected.'>'.$name_actual.'</option>';
        $rs->MoveNext();
    }
    if ($cant_opciones_extra > 0) {
        foreach ($opciones_extra as $key => $value) {

            // valor actual
            $value_actual = trim($value);
            $name_actual = trim($key);
            // si es el valor seleccionado
            if (trim($value_selected) == trim($value_actual)) {
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }
            // si la seleccion automatica esta activada y hay mas de 1 registro
            if ($autosel_1registro == 'S' && $cant_opciones < 2) {
                $selected = 'selected="selected"';
            }

            $html .= '<option value="'.$value_actual.'" '.$selected.'>'.$name_actual.'</option>';
        }
    }
    $html .= '</select>';

    return $html;
}
function campo_select_sinbd($parametros_array)
{

    // parametros
    $nombre_campo = $parametros_array['nombre_campo']; // name html
    $id_campo = $parametros_array['id_campo']; // id html
    $nombre_campo_bd = $parametros_array['nombre_campo_bd']; // name bd
    $id_campo_bd = $parametros_array['id_campo_bd']; // id bd
    $value_selected = $parametros_array['value_selected']; // valor seleccionado
    $pricampo_name = $parametros_array['pricampo_name']; //  nombre de la primera opcion
    $pricampo_value = $parametros_array['pricampo_value']; // valor de la primera opcion
    $style_input = $parametros_array['style_input'];
    $acciones = $parametros_array['acciones'];
    $autosel_1registro = $parametros_array['autosel_1registro']; // si hay 1 solo registro selecciona automaticamente en caso que sea 'S'
    $opciones = $parametros_array['opciones'];

    // trae datos de la bd
    //$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

    if ($autosel_1registro == 'S') {
        $cant_opciones = intval(count($opciones));
    }


    // construye campo
    $html .= '<select name="'.$nombre_campo.'" id="'.$id_campo.'" '.$style_input.' '.$acciones.'>';
    // si la seleccion automatica esta desactivada
    if ($autosel_1registro != 'S') {
        $html .= '<option value="'.$pricampo_value.'">'.$pricampo_name.'</option>';
    }
    // si la seleccion automatica esta activada
    if ($autosel_1registro == 'S') {
        // si hay mas de una opcion o no hay ninguna
        if ($cant_opciones <> 1) {
            $html .= '<option value="'.$pricampo_value.'">'.$pricampo_name.'</option>';
        }
    }
    foreach ($opciones as $key => $value) {

        // valor actual
        $value_actual = trim($value);
        $name_actual = trim($key);
        // si es el valor seleccionado
        if (trim($value_selected) == trim($value_actual)) {
            $selected = 'selected="selected"';
        } else {
            $selected = '';
        }
        // si la seleccion automatica esta activada y hay mas de 1 registro
        if ($autosel_1registro == 'S' && $cant_opciones < 2) {
            $selected = 'selected="selected"';
        }

        $html .= '<option value="'.$value_actual.'" '.$selected.'>'.$name_actual.'</option>';
    }
    $html .= '</select>';

    return $html;
}
// devuelve el idcaja si esta abierta
function caja_abierta($parametros)
{

    global $conexion;

    // datos entrada
    $idcajero = intval($parametros['idcajero']);
    $idsucursal = intval($parametros['idsucursal']);
    $idtipocaja = intval($parametros['idtipocaja']);

    // validar
    $valido = "S";
    $errores = "";

    if ($idcajero == 0) {
        $valido = "N";
        $errores .= " - No envio el codigo de cajero.<br />";
    }
    if ($idsucursal == 0) {
        $valido = "N";
        $errores .= " - No envio la sucursal.<br />";
    }
    if ($idtipocaja == 0) {
        $valido = "N";
        $errores .= " - No envio el tipo de caja.<br />";
    }

    // busca si ya tiene una caja abierta
    $consulta = "
		select idcaja
		from caja_gestion 
		where 
		 estado = 1 
		 and cajero = $idcajero
		 and idsucursal = $idsucursal
		 and idtipocaja = $idtipocaja
		order by fecha_apertura asc
		";
    $rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = intval($rscaj->fields['idcaja']);

    if ($idcaja == 0) {
        $valido = "N";
        $errores .= " - No hay caja abierta.<br />";
    }
    if ($valido == 'N') {
        $idcaja = 0;
    }


    $respuesta = [
        "valido" => $valido,
        "errores" => $errores,
        "idcaja" => $idcaja
    ];

    return $respuesta;
}
// devuelve la caja
function actualiza_caja($idcaja)
{

    global $conexion;

    $idcaja = intval($idcaja);

    if ($idcaja == 0) {
        echo "ERROR! NO INDICO LA CAJA";
        exit;
    }

    // actualiza cabecera de movimientos
    $consulta = "
	update caja_gestion_mov_cab
	set
	monto_movimiento = coalesce((select sum(monto_movimiento) from caja_gestion_mov_det where idcajamov = caja_gestion_mov_cab.idcajamov),0)
	where
	estado = 1
	and caja_gestion_mov_cab.idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // ingresos y egresos, este o no cerrada la caja
    $consulta = "
	update caja_gestion 
	  set 
	  total_ingresos = coalesce(
		  (
		  select sum(monto_movimiento) 
		  from caja_gestion_mov_cab 
		  where 
		  tipomovdinero = 'E' 
		  and estado = 1
		  and idcaja = $idcaja
		  )
	  ,0),
	  total_egresos = coalesce(
		  (
		  select sum(monto_movimiento) 
		  from caja_gestion_mov_cab 
		  where 
		  tipomovdinero = 'S' 
		  and estado = 1
		  and idcaja = $idcaja
		  )
	  ,0)
	where
	idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // actualza faltantes y sobrantes, solo si esta cerrada la caja
    $consulta = "
	update caja_gestion as a
	set 
	faltante = (
		select faltante from (select
		CASE WHEN
		coalesce(monto_cierre,0) < (coalesce(monto_apertura,0)+coalesce(total_ingresos,0)-coalesce(total_egresos,0))
		THEN
		(coalesce(monto_apertura,0)+coalesce(total_ingresos,0)-coalesce(total_egresos,0))-coalesce(monto_cierre,0)
		ELSE
		0
		END as faltante
		from caja_gestion
		where
		estado = 2
		and idcaja = $idcaja
		) as f
	),
	sobrante = (
		select sobrante from (select
		CASE WHEN
			coalesce(monto_cierre,0) > (coalesce(monto_apertura,0)+coalesce(total_ingresos,0)-coalesce(total_egresos,0))
		THEN
			coalesce(monto_cierre,0)-(coalesce(monto_apertura,0)+coalesce(total_ingresos,0)-coalesce(total_egresos,0))
		ELSE
			0
		END as sobrante
		from caja_gestion
		where
		estado = 2
		and idcaja = $idcaja
		) as s
	)
	where
	estado = 2
	and idcaja = $idcaja
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



}
function caja_movimiento_valida($parametros)
{

    // ver como se genera en: caja_mov_test.php

    global $conexion;
    global $ahora;

    $valido = "S";
    $errores = "";

    if (intval($parametros['idcaja']) <= 0) {
        $valido = "N";
        $errores .= " - No envio el idcaja.<br />";
    }
    // valida que la caja este abierta
    $caja_abierta = caja_abierta_sn($parametros['idcaja']);
    if ($caja_abierta != 'S') {
        $valido = "N";
        $errores .= " - No se puede registrar un movimiento en una caja cerrada.<br />";
    }
    if (intval($parametros['idtipocajamov']) <= 0) {
        $valido = "N";
        $errores .= " - No indico el tipo de caja.<br />";
    }
    if (trim($parametros['tipomovdinero']) != 'E' && trim($parametros['tipomovdinero']) != 'S') {
        $valido = "N";
        $errores .= " - No indico el tipo de movimiento el dinero.<br />";
    }
    if (floatval($parametros['monto_movimiento']) <= 0) {
        $valido = "N";
        $errores .= " - No indico el monto del movimiento.<br />";
    }
    if (intval($parametros['idmoneda']) <= 0) {
        $valido = "N";
        $errores .= " - No indico la moneda.<br />";
    }
    if (trim($parametros['fechahora_mov']) == '') {
        $valido = "N";
        $errores .= " - No indico la fecha y hora del movimiento.<br />";
    }
    if (intval($parametros['registrado_por']) <= 0) {
        $valido = "N";
        $errores .= " - No indico el usuario que realizo el movimiento.<br />";
    }
    // busca si la moneda es extrangera
    $idmoneda = intval($parametros['idmoneda']);
    $consulta = "
	SELECT idmoneda, moneda, estado, borrable, nacional
 	 FROM moneda
	 where
	 idmoneda = $idmoneda
	";
    $rsmon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nacional = $rsmon->fields['nacional'];
    // si es extrangera
    if ($nacional != 'S') {
        // busca que este cargada la cotizacion
        $consulta = "
		SELECT idcot as idcotizacion, cotizacion, fecha, estado, idmoneda
	    FROM cotizaciones
		where
		idmoneda = $idmoneda
		and estado <> 6
		";
        $rscot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si no esta cargado
        if (intval($rscot->fields['idcotizacion']) == 0) {
            $valido = "N";
            $errores .= " - Cuando la moneda es extrangera debe existir una cotizacion cargada.<br />";
        }

    }


    // recorre cada detalle
    $detalles = $parametros['detalles'];
    $cantidad_detalles = count($detalles);
    $monto_movimiento_acum = 0;
    $i = 1;

    foreach ($detalles as $detalle) {

        if (floatval($detalle['monto_movimiento']) <= 0) {
            $valido = "N";
            $errores .= " - No indico el monto de movimiento del detalle: $i.<br />";
        }
        if (intval($detalle['idcuentacon']) <= 0) {
            $valido = "N";
            $errores .= " - No indico el idcuentacon del detalle: $i.<br />";
        }
        if (intval($detalle['idformapago']) <= 0) {
            $valido = "N";
            $errores .= " - No indico la forma de pago del detalle: $i.<br />";
        }
        // validaciones por forma de pago 2 cheque  10 cheque diferido
        if (intval($detalle['idformapago']) == 10 or intval($detalle['idformapago']) == 2) {

            // entrada
            if ($parametros['tipomovdinero'] == 'E') {

                if (intval($detalle['idbanco']) == 0) {
                    $valido = "N";
                    $errores .= " - No indico el banco del cheque en el detalle: $i.<br />";
                }
                if (trim($detalle['fecha_emision']) == '') {
                    $valido = "N";
                    $errores .= " - No indico la fecha de emision del cheque en el detalle: $i.<br />";
                }
                if (trim($detalle['fecha_vencimiento']) == '') {
                    $valido = "N";
                    $errores .= " - No indico la fecha de vencimiento del cheque en el detalle: $i.<br />";
                }
                /*if(intval($detalle['idtipodoc_titular']) == 0){
                    $valido="N";
                    $errores.=" - No indico tipo de documento del titular del cheque en el detalle: $i.<br />";
                }
                if(intval($detalle['nrodoc_titular']) == 0){
                    $valido="N";
                    $errores.=" - No indico numero de documento del titular del cheque en el detalle: $i.<br />";
                }
                if(trim($detalle['nomape_titular']) == ''){
                    $valido="N";
                    $errores.=" - No indico nombre del titular del cheque en el detalle: $i.<br />";
                }
                if(intval($detalle['nrochq']) == 0){
                    $valido="N";
                    $errores.=" - No indico numero del cheque en el detalle: $i.<br />";
                }
                if(intval($detalle['idtipopersona_titular']) == 0){
                    $valido="N";
                    $errores.=" - No indico el tipo de persona titular del cheque en el detalle: $i.<br />";
                }
                if(trim($detalle['idpaisdoc_titular']) == ''){
                    $valido="N";
                    $errores.=" - No indico el pais del documento del titular del cheque en el detalle: $i.<br />";
                }*/

                // conversioens
                if (intval($detalle['idformapago']) == 2) {
                    $idchequerecibidotipo = 1; // a la vista
                } else {
                    $idchequerecibidotipo = 2; // diferido
                }



                $parametros_cheque_rec = [
                    'idcaja' => $parametros['idcaja'],
                    'monto_cheque' => $detalle['monto_movimiento'],
                    'fecha_emision' => $detalle['fecha_emision'],
                    'fecha_vencimiento' => $detalle['fecha_vencimiento'],
                    'idbanco' => $detalle['idbanco'],
                    'idmoneda' => $idmoneda,
                    'idtipopersona_titular' => $detalle['idtipopersona_titular'], // 1 fisica 2 juridica
                    'idpaisdoc_titular' => $detalle['idpaisdoc_titular'], // PY
                    'idtipodoc_titular' => $detalle['idtipodoc_titular'],
                    'nrodoc_titular' => $detalle['nrodoc_titular'],
                    'nomape_titular' => $detalle['nomape_titular'],
                    'idchequerecibidotipo' => $idchequerecibidotipo, // 1 a la vista 2 diferido
                    'nrochq' => $detalle['nrochq']

                ];

                /*$res=valida_cheque_recibido($parametros_cheque_rec);
                if($res['valido'] == 'N'){
                    $valido=$res['valido'];
                    $errores.=$res['errores'];
                }*/


                // salida
            } else {


                if (intval($detalle['idbanco']) == 0) {
                    $valido = "N";
                    $errores .= " - No indico el banco del cheque en el detalle: $i.<br />";
                }
                if (trim($detalle['fecha_emision']) == '') {
                    $valido = "N";
                    $errores .= " - No indico la fecha de emision del cheque en el detalle: $i.<br />";
                }
                if (trim($detalle['fecha_vencimiento']) == '') {
                    $valido = "N";
                    $errores .= " - No indico la fecha de vencimiento del cheque en el detalle: $i.<br />";
                }
                if (intval($detalle['nrochq']) == 0) {
                    $valido = "N";
                    $errores .= " - No indico numero del cheque en el detalle: $i.<br />";
                }

                // conversioens
                if (intval($detalle['idformapago']) == 2) {
                    $idchequeemitidotipo = 1; // a la vista
                } else {
                    $idchequeemitidotipo = 2; // diferido
                }

                $parametros_cheque_emi = [
                    'idcaja' => $parametros['idcaja'],
                    'monto_cheque' => $detalle['monto_movimiento'],
                    'fecha_emision' => $detalle['fecha_emision'],
                    'fecha_vencimiento' => $detalle['fecha_vencimiento'],
                    'idbanco' => $detalle['idbanco'],
                    'idcuentacon' => $detalle['idcuentacon'],
                    'idmoneda' => $idmoneda,
                    'nrochq' => $detalle['nrochq'],
                    'idchequeemitidotipo' => $idchequeemitidotipo // 1 a la vista 2 diferido
                ];

                $res = valida_cheque_emitido($parametros_cheque_emi);
                if ($res['valido'] == 'N') {
                    $valido = $res['valido'];
                    $errores .= $res['errores'];
                }



            }



        }


        // valida que exista la cuenta conciliable
        $idcuentacon = intval($detalle['idcuentacon']);
        $consulta = "
		select idcuenta as idcuentacon from cuentas where idcuenta = $idcuentacon
		";
        //echo $consulta;
        //exit;
        $rsccon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsccon->fields['idcuentacon']) <= 0) {
            $valido = "N";
            $errores .= " - No se establecio la cuenta conciliable del detalle: $i.<br />";
        }


        // suma los montos
        $monto_movimiento_acum += $detalle['monto_movimiento'];
        $i++;
    }

    // compara la sumatoria de movimientos del detalle contra la cabecera
    if ($monto_movimiento_acum != $parametros['monto_movimiento']) {
        $valido = "N";
        $errores .= " - La sumatoria de detalles de montos de movimiento no coincide con el monto del movimiento.<br />";
    }


    $resultado = [
    'valido' => $valido,
    'errores' => $errores
    ];


    return $resultado;
}
function caja_movimiento_registra($parametros)
{

    global $conexion;
    global $ahora;


    // datos entrada
    $idcaja = antisqlinyeccion($parametros['idcaja'], "int");
    $idtipocajamov = antisqlinyeccion($parametros['idtipocajamov'], "int");
    $tipomovdinero = antisqlinyeccion($parametros['tipomovdinero'], "text");
    $monto_movimiento = antisqlinyeccion($parametros['monto_movimiento'], "float");
    $estado = 1;
    $registrado_por = antisqlinyeccion($parametros['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idmoneda = antisqlinyeccion($parametros['idmoneda'], "int");
    $fechahora_mov = antisqlinyeccion($parametros['fechahora_mov'], "text");
    $fechamov = antisqlinyeccion(date("Y-m-d", strtotime($parametros['fechahora_mov'])), "text");

    // busca si la moneda es extrangera
    $idmoneda = intval($parametros['idmoneda']);
    $consulta = "
	SELECT idmoneda, moneda, estado, borrable, nacional
 	 FROM moneda
	 where
	 idmoneda = $idmoneda
	";
    $rsmon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nacional = $rsmon->fields['nacional'];
    // si es extrangera
    if ($nacional != 'S') {
        $consulta = "
		SELECT idcot as idcotizacion, cotizacion, fecha, estado,  idmoneda
	    FROM cotizaciones
		where
		idmoneda = $idmoneda
		and estado <> 6
		";
        $rscot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // trae de la bd si la moneda es extrangera
        $cotizacion = antisqlinyeccion($rscot->fields['cotizacion'], "text");
        $idcotizacion = antisqlinyeccion($rscot->fields['idcotizacion'], "int");
    } else {
        $idcotizacion = "NULL";
        $cotizacion = "NULL";
    }

    // registra cabecera
    $consulta = "
	insert into caja_gestion_mov_cab
	(idcaja, idtipocajamov, tipomovdinero, monto_movimiento, estado, registrado_por, registrado_el, idmoneda, cotizacion, idcotizacion, fechahora_mov, fechamov)
	values
	($idcaja, $idtipocajamov, $tipomovdinero, $monto_movimiento, $estado, $registrado_por, $registrado_el, $idmoneda, $cotizacion, $idcotizacion, $fechahora_mov, $fechamov)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // busca el ultimo id caja insertado
    $consulta = "
	select max(idcajamov) as idcajamov
	 from caja_gestion_mov_cab 
	 where 
	 idcaja = $idcaja 
	 and registrado_por = $registrado_por
	 ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcajamov = intval($rs->fields['idcajamov']);

    // recorre cada detalle
    $detalles = $parametros['detalles'];
    $cantidad_detalles = count($detalles);
    $monto_movimiento_acum = 0;
    $i = 1;
    foreach ($detalles as $detalle) {

        $monto_movimiento = antisqlinyeccion($detalle['monto_movimiento'], "float");
        $idcuentacon = antisqlinyeccion($detalle['idcuentacon'], "int");
        $idformapago = antisqlinyeccion($detalle['idformapago'], "int");
        $idtipopersona_titular = antisqlinyeccion($detalle['idtipopersona_titular'], "int");
        $idpaisdoc_titular = substr(trim($detalle['idpaisdoc_titular']), 0, 3);
        $idbanco = antisqlinyeccion($detalle['idbanco'], "int");
        $fecha_emision_chq = antisqlinyeccion($detalle['fecha_emision'], "text");
        $nrochq = antisqlinyeccion($detalle['nrochq'], "text");
        $fecha_transfer = antisqlinyeccion($detalle['fecha_emision'], "text");
        $boleta_nro = antisqlinyeccion($detalle['boleta_nro'], "text");
        $transfer_nro = antisqlinyeccion($detalle['transfer_nro'], "text");


        // registra detalle
        $consulta = "
		insert into caja_gestion_mov_det
		(
		idcajamov, monto_movimiento, idcuentacon, idformapago,
		idbanco, fecha_emision_chq, nrochq, fecha_transfer, boleta_nro, transfer_nro
		)
		values
		(
		$idcajamov, $monto_movimiento, $idcuentacon, $idformapago,
		$idbanco, $fecha_emision_chq, $nrochq, $fecha_transfer, $boleta_nro, $transfer_nro
		)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        // validaciones por forma de pago 2 cheque  10 cheque diferido
        if (intval($detalle['idformapago']) == 10 or intval($detalle['idformapago']) == 2) {

            // obtiene el idcajamovdet insertado
            $consulta = "
			select max(idcajamovdet) as idcajamovdet from caja_gestion_mov_det where idcajamov = $idcajamov
			";
            $rscdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcajamovdet = intval($rscdet->fields['idcajamovdet']);

            // conversiones
            if (intval($detalle['idformapago']) == 2) {
                $idchequerecibidotipo = 1; // a la vista
                $idchequeemitidotipo = 1; // a la vista
            } else {
                $idchequerecibidotipo = 2; // diferido
                $idchequeemitidotipo = 2; // diferido
            }

            // entrada
            if ($parametros['tipomovdinero'] == 'E') {


                $parametros_cheque_rec = [
                    'idcaja' => $parametros['idcaja'],
                    'monto_cheque' => $detalle['monto_movimiento'],
                    'fecha_emision' => $detalle['fecha_emision'],
                    'fecha_vencimiento' => $detalle['fecha_vencimiento'],
                    'idbanco' => $detalle['idbanco'],
                    'idmoneda' => $idmoneda,
                    'idtipopersona_titular' => $idtipopersona_titular, // 1 fisica 2 juridica
                    'idpaisdoc_titular' => $idpaisdoc_titular,
                    'idtipodoc_titular' => $detalle['idtipodoc_titular'],
                    'nrodoc_titular' => $detalle['nrodoc_titular'],
                    'nomape_titular' => $detalle['nomape_titular'],
                    'idchequerecibidotipo' => $idchequerecibidotipo, // 1 a la vista 2 diferido
                    'nrochq' => $detalle['nrochq'],
                    'idcajamov' => $idcajamov,
                    'idcajamovdet' => $idcajamovdet,
                    'registrado_por' => $registrado_por
                ];
                registra_cheque_recibido($parametros_cheque_rec);

                // salida
            } else { // if($parametros['tipomovdinero'] == 'E'){

                $parametros_cheque_emi = [
                    'idcaja' => $parametros['idcaja'],
                    'monto_cheque' => $detalle['monto_movimiento'],
                    'fecha_emision' => $detalle['fecha_emision'],
                    'fecha_vencimiento' => $detalle['fecha_vencimiento'],
                    'idbanco' => $detalle['idbanco'],
                    'idcuentacon' => $detalle['idcuentacon'],
                    'idmoneda' => $idmoneda,
                    'idchequeemitidotipo' => $idchequeemitidotipo, // 1 a la vista 2 diferido
                    'nrochq' => $detalle['nrochq'],
                    'idcajamov' => $idcajamov,
                    'idcajamovdet' => $idcajamovdet,
                    'registrado_por' => $registrado_por
                ];
                registra_cheque_emitido($parametros_cheque_emi);

            }// if($parametros['tipomovdinero'] == 'E'){

        } // if(intval($detalle['idformapago']) == 10 or intval($detalle['idformapago']) ==  2){

        $i++;
    }



    // actualiza caja
    if ($idcaja > 0) {
        actualiza_caja($idcaja);
    }

    return $idcajamov;

}
// devuelve el idcaja si esta abierta
function caja_abierta_sn($idcaja)
{

    global $conexion;

    // para evitar error sql
    $idcaja = intval($idcaja);

    // busca si ya tiene una caja abierta
    $consulta = "
		select idcaja
		from caja_gestion 
		where 
		 estado = 1 
		 and idcaja = $idcaja
		limit 1
		";
    $rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = intval($rscaj->fields['idcaja']);


    if ($idcaja > 0) {
        $abierto = 'S';
    } else {
        $abierto = 'N';
    }


    return $abierto;
}

function valida_cheque_recibido($parametros_array)
{

    // validaciones
    $valido = "S";
    $errores = "";

    if (intval($parametros_array['idcaja']) == 0) {
        $valido = "N";
        $errores .= " - No indico la caja del cheque.<br />";
    }
    if (intval($parametros_array['monto_cheque']) == 0) {
        $valido = "N";
        $errores .= " - No indico el monto del cheque.<br />";
    }
    if (intval($parametros_array['fecha_emision']) == 0) {
        $valido = "N";
        $errores .= " - No indico la fecha de emision del cheque.<br />";
    }
    if (trim($parametros_array['fecha_vencimiento']) == '') {
        $valido = "N";
        $errores .= " - No indico la fecha de vencimiento del cheque.<br />";
    }
    if (intval($parametros_array['idbanco']) == 0) {
        $valido = "N";
        $errores .= " - No indico el banco del cheque.<br />";
    }
    if (intval($parametros_array['idmoneda']) == 0) {
        $valido = "N";
        $errores .= " - No indico la moneda del cheque.<br />";
    }
    /*if(intval($parametros_array['idtipopersona_titular']) == 0){
        $valido="N";
        $errores.=" - No indico si el titular es una persona fisica o juridica.<br />";
    }
    if(trim($parametros_array['idpaisdoc_titular']) == ''){
        $valido="N";
        $errores.=" - No indico el pais del documento del titular.<br />";
    }
    if(intval($parametros_array['idtipodoc_titular']) == 0){
        $valido="N";
        $errores.=" - No indico tipo de documento del titular del cheque.<br />";
    }
    if(intval($parametros_array['nrodoc_titular']) == 0){
        $valido="N";
        $errores.=" - No indico numero de documento del titular del cheque.<br />";
    }
    if(trim($parametros_array['nomape_titular']) == ''){
        $valido="N";
        $errores.=" - No indico nombre del titular del cheque en el detalle: $i.<br />";
    }*/
    if (intval($parametros_array['nrochq']) == 0) {
        $valido = "N";
        $errores .= " - No indico numero del cheque en el detalle: $i.<br />";
    }
    if (intval($parametros_array['idchequerecibidotipo']) == 0) {
        $valido = "N";
        $errores .= " - No indico si el cheque es a la vista o diferido.<br />";
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];
    return $res;
}
function valida_cheque_emitido($parametros_array)
{

    if (intval($parametros_array['idcaja']) == 0) {
        $valido = "N";
        $errores .= " - El campo idcaja no puede ser cero o nulo del cheque.<br />";
    }
    if (floatval($parametros_array['monto_cheque']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_cheque no puede ser cero o negativo.<br />";
    }
    if (trim($parametros_array['fecha_emision']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_emision del cheque no puede estar vacio.<br />";
    }
    if (trim($parametros_array['fecha_vencimiento']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_vencimiento del cheque no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idbanco']) == 0) {
        $valido = "N";
        $errores .= " - El campo idbanco del cheque no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idcuentacon']) == 0) {
        $valido = "N";
        $errores .= " - El campo idcuentacon del cheque no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idmoneda']) == 0) {
        $valido = "N";
        $errores .= " - El campo idmoneda del cheque no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idchequeemitidotipo']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo de cheque emitido no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['nrochq']) == 0) {
        $valido = "N";
        $errores .= " - El campo numero de cheque no puede ser cero o nulo.<br />";
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];
    return $res;
}
function registra_cheque_recibido($parametros_array)
{

    global $conexion;
    global $ahora;

    // recibe parametros
    $idcaja = antisqlinyeccion($parametros_array['idcaja'], "int");
    $idcajamov = antisqlinyeccion($parametros_array['idcajamov'], "int");
    $monto_cheque = antisqlinyeccion($parametros_array['monto_cheque'], "float");
    $fecha_emision = antisqlinyeccion($parametros_array['fecha_emision'], "text");
    $fecha_vencimiento = antisqlinyeccion($parametros_array['fecha_vencimiento'], "text");
    $idbanco = antisqlinyeccion($parametros_array['idbanco'], "int");
    $idmoneda = antisqlinyeccion($parametros_array['idmoneda'], "int");
    $nrocta = antisqlinyeccion($parametros_array['nrocta'], "text");
    $idtipopersona_titular = antisqlinyeccion($parametros_array['idtipopersona_titular'], "int");
    $idpaisdoc_titular = antisqlinyeccion($parametros_array['idpaisdoc_titular'], "text");
    $idtipodoc_titular = antisqlinyeccion($parametros_array['idtipodoc_titular'], "int");
    $nrodoc_titular = antisqlinyeccion($parametros_array['nrodoc_titular'], "int");
    $fecha_deposito = antisqlinyeccion($parametros_array['fecha_deposito'], "text");
    $fecha_acreditado = antisqlinyeccion($parametros_array['fecha_acreditado'], "text");
    $boleta_deposito = antisqlinyeccion($parametros_array['boleta_deposito'], "text");
    $nomape_titular = antisqlinyeccion($parametros_array['nomape_titular'], "text");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $idchequerecibidoestado = 1;
    $estado = 1;
    $idchequerecibidotipo = antisqlinyeccion($parametros_array['idchequerecibidotipo'], "int");
    $anulado_el = antisqlinyeccion('', "text");
    $anulado_por = antisqlinyeccion('', "int");
    $nrochq = antisqlinyeccion($parametros_array['nrochq'], "int");
    $idcajamovdet = antisqlinyeccion($parametros_array['idcajamovdet'], "int");

    $consulta = "
	insert into cheques_recibidos
	(idcaja, idcajamov, idcajamovdet, monto_cheque, fecha_emision, fecha_vencimiento, idbanco, idmoneda, nrocta, idtipopersona_titular, idpaisdoc_titular, idtipodoc_titular, nrodoc_titular, fecha_deposito, fecha_acreditado, boleta_deposito, nomape_titular, registrado_el, registrado_por, idchequerecibidoestado, estado, idchequerecibidotipo, anulado_el, anulado_por, nrochq)
	values
	($idcaja, $idcajamov, $idcajamovdet, $monto_cheque, $fecha_emision, $fecha_vencimiento, $idbanco, $idmoneda, $nrocta, $idtipopersona_titular, $idpaisdoc_titular, $idtipodoc_titular, $nrodoc_titular, $fecha_deposito, $fecha_acreditado, $boleta_deposito, $nomape_titular, $registrado_el, $registrado_por, $idchequerecibidoestado, $estado, $idchequerecibidotipo, $anulado_el, $anulado_por, $nrochq)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select max(idchequerecibido) as idchequerecibido from cheques_recibidos where registrado_por = $registrado_por
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	update 
	caja_gestion_mov_det 
	set idchequerecibido = (select idchequerecibido from cheques_recibidos where idcajamovdet = $idcajamovdet)
	where 
	idcajamovdet = $idcajamovdet
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    return $rsmax->fields['idchequerecibido'];

}
function registra_cheque_emitido($parametros_array)
{

    global $conexion;
    global $ahora;

    // recibe parametros
    $idcaja = antisqlinyeccion($parametros_array['idcaja'], "int");
    $idcajamov = antisqlinyeccion($parametros_array['idcajamov'], "int");
    $monto_cheque = antisqlinyeccion($parametros_array['monto_cheque'], "float");
    $fecha_emision = antisqlinyeccion($parametros_array['fecha_emision'], "text");
    $fecha_vencimiento = antisqlinyeccion($parametros_array['fecha_vencimiento'], "text");
    $idbanco = antisqlinyeccion($parametros_array['idbanco'], "int");
    $idcuentacon = antisqlinyeccion($parametros_array['idcuentacon'], "int");
    $idmoneda = antisqlinyeccion($parametros_array['idmoneda'], "int");
    $fecha_acreditado = antisqlinyeccion($parametros_array['fecha_acreditado'], "text");
    $boleta_deposito = antisqlinyeccion($parametros_array['boleta_deposito'], "text");
    //$nomape_titular=antisqlinyeccion($parametros_array['nomape_titular'],"text");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $idchequeemitidoestado = 1;
    $estado = 1;
    $idchequeemitidotipo = antisqlinyeccion($parametros_array['idchequeemitidotipo'], "int");
    $anulado_el = antisqlinyeccion('', "text");
    $anulado_por = antisqlinyeccion('', "int");
    $nrochq = antisqlinyeccion($parametros_array['nrochq'], "int");
    $idcajamovdet = antisqlinyeccion($parametros_array['idcajamovdet'], "int");


    $consulta = "
	insert into cheques_emitidos
	(idcaja, idcajamov, idcajamovdet, monto_cheque, fecha_emision, fecha_vencimiento, idbanco, idcuentacon, idmoneda, fecha_acreditado, boleta_deposito, registrado_el, registrado_por, idchequeemitidoestado, estado, idchequeemitidotipo, anulado_el, anulado_por, nrochq)
	values
	($idcaja, $idcajamov, $idcajamovdet, $monto_cheque, $fecha_emision, $fecha_vencimiento, $idbanco, $idcuentacon, $idmoneda, $fecha_acreditado, $boleta_deposito, $registrado_el, $registrado_por, $idchequeemitidoestado, $estado, $idchequeemitidotipo, $anulado_el, $anulado_por, $nrochq)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $consulta = "
	select max(idchequeemitido) as idchequeemitido from cheques_emitidos where registrado_por = $registrado_por
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	update 
	caja_gestion_mov_det 
	set idchequeemitido = (select idchequeemitido from cheques_emitidos where idcajamovdet = $idcajamovdet)
	where 
	idcajamovdet = $idcajamovdet
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    return $rsmax->fields['idchequeemitido'];
}
function valida_anulacion_caja_mov($parametros_array)
{
    global $conexion;
    global $ahora;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $idcajamov = intval($parametros_array['idcajamov']);
    $anulado_por = intval($parametros_array['anulado_por']);

    if ($idcajamov == 0) {
        $valido = "N";
        $errores = "- No se indico el id de movimiento de caja.<br />";
    }
    if ($anulado_por == 0) {
        $valido = "N";
        $errores = "- No se indico el usuario que anula la caja.<br />";
    }

    //echo $idcajamov;
    //exit;


    $consulta = "
	select * from caja_gestion_mov_cab where idcajamov = $idcajamov limit 1
	";
    $rscajmov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = intval($rscajmov->fields['idcaja']);

    // hay caja cero para algunos tipos de movimiento
    if (trim($idcaja) > 0) {
        $consulta = "
		select * from caja_gestion where idcaja = $idcaja and estado <> 6 limit 1
		";
        $rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcaja = $rscaj->fields['idcaja'];
        $estado_caja = $rscaj->fields['estado'];
        if (intval($estado_caja) != 1) {
            $valido = "N";
            $errores .= " - La caja al cual corresponde este movimiento se encuentra cerrada, debe reabrir la caja para anular.<br />";
        }

        if (intval($idcaja) == 0) {
            $valido = "N";
            $errores .= " - Caja inexistente.<br />";
        }
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];
    return $res;

}
function anula_caja_mov($parametros_array)
{
    global $conexion;
    global $ahora;


    $idcajamov = intval($parametros_array['idcajamov']);
    $anulado_por = intval($parametros_array['anulado_por']);

    $consulta = "
	select * from caja_gestion_mov_cab where idcajamov = $idcajamov
	";
    $rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = $rscaj->fields['idcaja'];


    // anula la cabecera
    $consulta = "
	update caja_gestion_mov_cab
	set
	estado = 6,
	borrado_por=$anulado_por,
	borrado_el='$ahora'
	where
	idcajamov = $idcajamov
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // busca los detalles de movimiento de caja que contienen cheques
    $consulta = "
	select * 
	from caja_gestion_mov_det
	where
	idcajamov = $idcajamov
	and (idchequerecibido is not null or idchequeemitido is not null)
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idchequerecibido = intval($rs->fields['idchequerecibido']);
    $idchequeemitido = intval($rs->fields['idchequeemitido']);
    // si existen registros
    if ($idchequerecibido > 0) {
        // recorre el detalle de movimiento de caja
        while (!$rs->EOF) {

            $idchequerecibido = $rs->fields['idchequerecibido'];
            // anula los cheques recibidos
            $consulta = "
			update cheques_recibidos
			set 
			estado = 6,
			anulado_el = '$ahora',
			anulado_por = $anulado_por
			where 
			idchequerecibido = $idchequerecibido
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rs->MoveNext();
        }
    }
    // si existen registros
    if ($idchequeemitido > 0) {
        // recorre el detalle de movimiento de caja
        while (!$rs->EOF) {

            $idchequeemitido = $rs->fields['idchequeemitido'];
            // anula los cheques recibidos
            $consulta = "
			update cheques_emitidos
			set 
			estado = 6,
			anulado_el = '$ahora',
			anulado_por = $anulado_por
			where 
			idchequeemitido = $idchequeemitido
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rs->MoveNext();
        }
    }

    // actualiza caja
    if ($idcaja > 0) {
        actualiza_caja($idcaja);
    }

    // retnorna el id de movimiento de caja anulado
    return $idcajamov;

}
function validar_anulacion_compra($parametros_array)
{
    global $conexion;
    global $ahora;

    // inicializar variables
    $valido = "S";
    $errores = "";


    $anulado_por = $parametros_array['anulado_por'];
    $anulado_el = $ahora;
    $idfactura = intval($parametros_array['idfactura']);

    // validaciones
    if (intval($parametros_array['anulado_por']) == 0) {
        $valido = "N";
        $errores .= " - No se indico quien anula la compra.<br />";
    }


    // validar que no hayan pagos acreditados, si hay entonces se debe pedir que anule la orden de pago
    $consulta = "
	select *, (select id_factura from facturas_proveedores_compras where id_factura = $idfactura limit 1) as compra
	from facturas_proveedores
	where
	id_factura = $idfactura
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $estado_carga = intval($rs->fields['estado_carga']);
    $estado_factura = intval($rs->fields['estado']);
    $iddeposito = intval($rs->fields['iddeposito']);
    $compra = intval($rs->fields['compra']);
    // valida que ya no este anulada
    if ($estado_factura == 6) {
        $valido = "N";
        $errores .= " - La factura ya se encuentra anulada.<br />";
    }

    // valida que el estado_carga sea 3 osea que ya afecto stock
    if ($estado_carga != 3) {
        $valido = "N";
        $errores .= " - No puedes anular una factura que aun no termino de cargarse, elimine la carga activa.<br />";
    }

    // valida que no tenga pagos ingresados
    if ($rs->fields['cobrado_factura'] > 0) {
        $valido = "N";
        $errores .= " - No puedes anular una factura con pagos ya acreditados, primero debe anular los pagos.<br />";
    }
    // valida que exista el deposito de la mercaderia si no es un gasto
    if ($rs->fields['compra'] > 0) {
        /*if(intval($rs->fields['iddeposito']) == 0){
            $valido="N";
            $errores.=" - No se indico el deposito a retornar las mercaderias anuladas.<br />";
        }*/
    }

    // validar que no exista una orden de pago llenandose con esta factura
    $consulta = "
	SELECT *
	FROM orden_pago
	inner join orden_pago_det on orden_pago.idordenpago = orden_pago_det.idordenpago
	where
	orden_pago.estado <> 6
	and orden_pago_det.idfactura = $idfactura
	";
    $rsor = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idordenpago = $rsor->fields['idordenpago'];
    if (intval($rsor->fields['idfactura']) > 0) {
        $valido = "N";
        $errores .= " - No puedes anular una factura con una orden de pago en proceso de carga, verifique la orden Nro $idordenpago.<br />";
    }


    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;



}
function anular_compra($parametros_array)
{

    global $conexion;
    global $ahora;

    $idfactura = intval($parametros_array['idfactura']);
    $anulado_el = $ahora;
    $anulado_por = intval($parametros_array['anulado_por']);

    $consulta = "
	select *, (select id_factura from facturas_proveedores_compras where id_factura = $idfactura limit 1) as compra
	from facturas_proveedores
	where
	id_factura = $idfactura
	and estado <> 6
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = $rs->fields['iddeposito'];




    // si es a credito anula las operaciones
    $consulta = "
	update operaciones_proveedores
	set
		estado = 6
	where
		idfactura = $idfactura
		and estado <> 6
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // anula la factura
    $consulta = "
	update facturas_proveedores
	set
		estado = 6,
		anulado_por = $anulado_por,
		anulado_el = '$ahora'
	where
		id_factura = $idfactura
		and estado <> 6
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



}
function limpiar_txt_fac($txt)
{
    $txt = limpia_puso_factura($txt);
    //$txt=utf8_encode($txt); // solo aplica en algunos servidores
    return $txt;
}
function limpia_puso_factura($txt)
{
    $txt = str_replace('"', '', $txt);
    $txt = str_replace("'", "", $txt);
    $txt = str_replace("-", "", $txt);
    //$txt=str_replace("/","",$txt);
    $txt = str_replace("}", "", $txt);
    return $txt;
}
function factura_autoimpresor($idventa, $inappwebview = 0)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;
    global $idusu;

    $aux = $saltolinea;
    if (intval($inappwebview) == 1) {
        $saltolinea = " \\r ";
    }
    // log de impresiones
    $consulta = "
	INSERT INTO log_impresiones_ventas
	(idventa, impreso_por, impreso_el) 
	VALUES
	($idventa,$idusu,'$ahora')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select * from empresas where idempresa = $idempresa
	";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
    $actividad_economica = trim($rsemp->fields['actividad_economica']);
    $telefono_empresa = trim($rsemp->fields['telefono']);

    /*---------------------------------------------
    AGREGADO: 10/11/2022:Mostrar vuelto y o cotizaciones
    -----------------------------------------------------*/
    $consulta = "
		select * from preferencias_caja limit 1;
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $muestra_cotizacion = trim($rsprefcaj->fields['usa_cotizacion']);
    $muestra_recibevuelto = trim($rsprefcaj->fields['muestrarecibe']);

    //$muestra_formapago=trim($rsprefcaj->fields['muestra_formapago']);

    //$muestra_linea_credito=trim($rsprefcaj->fields['muestralc']);
    //$muestra_saldo_linea_credito=trim($rsprefcaj->fields['muestrasaldolc']);




    $consulta = "
	select * from preferencias where idempresa = $idempresa
	";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $muestra_fantasia_fac = trim($rspref->fields['muestra_fantasia_fac']);
    $muestra_actividad_fac = trim($rspref->fields['muestra_actividad_fac']);
    $muestra_coti_auto = trim($rspref->fields['muestra_cotizacion_auto']);
    $consulta = "
	select pie_factura, leyenda_termico, texto_matriz_fact, pie_final_factura, fantasia_sucursal_fact, 
	leyenda_credito, muestra_formapago, desglosa_impuesto_multiple, multi_razonsocial_suc, limita_tamano_concepto
	from preferencias_caja 
	where 
	idempresa = $idempresa
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $pie_factura = trim($rsprefcaj->fields['pie_factura']);
    $leyenda_termico = trim($rsprefcaj->fields['leyenda_termico']);
    $texto_matriz_fact = trim($rsprefcaj->fields['texto_matriz_fact']);
    $pie_final_factura = trim($rsprefcaj->fields['pie_final_factura']);
    $fantasia_sucursal_fact = trim($rsprefcaj->fields['fantasia_sucursal_fact']);
    $leyenda_credito = $rsprefcaj->fields['leyenda_credito']; // no poner trim
    $muestra_formapago = trim($rsprefcaj->fields['muestra_formapago']);
    $desglosa_impuesto_multiple = trim($rsprefcaj->fields['desglosa_impuesto_multiple']);
    $multi_razonsocial_suc = trim($rsprefcaj->fields['multi_razonsocial_suc']);
    $limita_tamano_concepto = trim($rsprefcaj->fields['limita_tamano_concepto']);

    $consulta = "
	select *,ventas.vuelto as vv, ventas.sucursal as idsucursal, (select count(*) from (select idprod FROM ventas_detalles where ventas_detalles.idemp = $idempresa and ventas_detalles.idventa = $idventa GROUP by idprod) as tot)  as total_detalles,
	(select numero_mesa from mesas where idmesa = ventas.idmesa) as numero_mesa,
	(select chapa from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_corto_cliente,
	(select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
	(select observacion_larga from ventas_observacion_larga where idventa = ventas.idventa) as observacion_larga,
	(select usuario from usuarios where idusu = ventas.registrado_por) as cajero,
	
	(select wifi from sucursales where sucursales.idsucu = ventas.sucursal limit 1) as wifi,
	(select telefono from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as telefono,
	(select direccion from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as direccion,
	(select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,
	(select llevapos  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as llevapos,
	(select cambio  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as cambio,
	(select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as vuelto,
	(select observacion_delivery  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion_delivery,
	(select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
	(select nombre_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_deliv,
	(select apellido_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as apellido_deliv,
    (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,	
	(select idtmpventares_cab as idpedido from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as idpedido,
	(select motorista from motoristas where ventas.idmotorista = motoristas.idmotorista) as motorista,
	(select cliente_delivery_dom.referencia from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as referencia,
	CASE WHEN
		idsucursal_clie is not null
	THEN
		CASE WHEN 
			(SELECT count(sucursal_cliente.idsucursal_clie) from sucursal_cliente where idcliente = ventas.idcliente and estado = 1) > 0
		THEN
			(SELECT sucursal_cliente.direccion from sucursal_cliente where idsucursal_clie = ventas.idsucursal_clie)
		ELSE
			cliente.direccion
		END
	ELSE
		cliente.direccion
	END	as direccion_cliente,
	(select canal_venta from canal_venta where idcanalventa  = ventas.idcanalventa) as canal_venta,
	CASE WHEN 
		tipo_venta = 2
	THEN
		(select prox_vencimiento from cuentas_clientes where idventa = ventas.idventa limit 1) 
	ELSE
		''
	END AS vencimiento_factura
	from ventas
	inner join cliente on cliente.idcliente = ventas.idcliente 
	where 
	ventas.idventa = $idventa
	and ventas.estado <> 6 
	limit 1
	";
    php_console_log($consulta);
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventa = $rsv->fields['idventa'];
    $canal_venta = trim($rsv->fields['canal_venta']);
    $tipo_venta = intval($rsv->fields['tipo_venta']);
    $documento = intval($rsv->fields['documento']);
    $total_detalles = intval($rsv->fields['total_detalles']);
    $idpedido = intval($rsv->fields['idpedido']);
    $idtandatimbrado = intval($rsv->fields['idtandatimbrado']);
    $factura_nro = trim($rsv->fields['factura']);
    php_console_log($factura_nro);
    $orden_compra = trim($rsv->fields['ocnumero']);
    if ($factura_nro == '') {
        echo "FACTURA NO GENERADA";
        exit;
    }
    if ($rsv->fields['finalizo_correcto'] == 'N') {
        echo "ANULAR VENTA";
        exit;
    }
    $fechahora = date("d/m/Y H:i", strtotime($rsv->fields['fecha']));
    $idsucursal = intval($rsv->fields['idsucursal']);
    // conversion factura
    $factura_nro = str_replace("-", "", $factura_nro);
    $factura_nro = substr($factura_nro, 0, 3).'-'.substr($factura_nro, 3, 3).'-'.substr($factura_nro, 6, 7);


    // busca si hay productos del regimen gastronomico
    $consulta = "
	select idventadetimp from ventas_detalles_impuesto where idventa = $idventa and idtipoiva = 4 limit 1
	";
    $rsexreg = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsexreg->fields['idventadetimp'] > 0) {
        $regimen_gastro = "S";
    } else {
        $regimen_gastro = "N";
    }


    $consulta = "
	select idventadet, count(*) as total
	from ventas_detalles_impuesto 
	where 
	idventa = $idventa
    group by idventadet
    order by count(*) desc
    limit 1
	";
    $rsexmult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsexmult->fields['total']) > 1) {
        $iva_multiple = "S";
    } else {
        $iva_multiple = "N";
    }


    // numeros a letras
    require_once("includes/num2letra.php");

    $descuento = floatval($rsv->fields['descneto']);
    $total_factura = $rsv->fields['total_venta'] - $descuento; // incluido el descuento
    $total_factura_txt = strtoupper(num2letras(floatval($total_factura)));
    // solo sucursal que no es casa matriz por eso idsucu > 0
    $consulta = "
	SELECT * 
	FROM sucursales 
	where 
	idempresa = $idempresa 
	and idsucu = $idsucursal
	and idsucu > 1
	";
    //echo $consulta;
    $rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $wifi = trim($rssuc->fields['wifi']);
    $subcat_factura = trim($rssuc->fields['subcat_factura']);

    // solo si usa fantasia por sucursal
    if ($fantasia_sucursal_fact == 'S') {
        // sucursal actual
        $consulta = "
		SELECT * 
		FROM sucursales 
		where 
		idsucu = $idsucursal
		";
        $rssucact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $fantasia_sucursal = trim($rssucact->fields['fantasia_sucursal']);

        if ($fantasia_sucursal != '') {
            $nombre_fantasia_empresa = $fantasia_sucursal;
        }
    }
    if ($multi_razonsocial_suc == 'S') {
        // sucursal actual
        $consulta = "
		SELECT razon_social_sucursal, ruc_sucursal, actividad_sucursal
		FROM sucursales 
		where 
		idsucu = $idsucursal
		";
        $rssucact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $razon_social_sucursal = trim($rssucact->fields['razon_social_sucursal']);
        $ruc_sucursal = trim($rssucact->fields['ruc_sucursal']);
        $actividad_sucursal = trim($rssucact->fields['actividad_sucursal']);
        if ($razon_social_sucursal != '') {
            $razon_social_empresa = $razon_social_sucursal;
        }
        if ($ruc_sucursal != '') {
            $ruc_empresa = $ruc_sucursal;
        }
        if ($actividad_sucursal != '') {
            $actividad_economica = $actividad_sucursal;
        }
    }


    if ($idtandatimbrado > 0) {
        $ahorad = date("Y-m-d", strtotime($ahora));
        $consulta = "
		SELECT * 
		FROM facturas 
		where 
		idtanda = $idtandatimbrado
		limit 1
		";
        $rstimbrado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        /*
        and estado = 'A'
        and valido_hasta >= '$ahorad'
        and valido_desde <= '$ahorad'
        */
        //echo $consulta;
        $timbrado = trim($rstimbrado->fields['timbrado']);
        $valido_desde = date("d/m/Y", strtotime($rstimbrado->fields['valido_desde']));
        $valido_hasta = date("d/m/Y", strtotime($rstimbrado->fields['valido_hasta']));
        $idtanda = intval($rstimbrado->fields['idtanda']);
        $idtimbradotipo = intval($rstimbrado->fields['idtimbradotipo']);
        if ($idtanda == 0) {
            echo "Timbrado vencido o inexistente.";
            exit;
        }

    } else {

        echo "No hay timbrado activo.";
        exit;
    }
    $consulta = "
	select 0 as idventatmp, idprod_serial,
	CASE WHEN 
		ventas_detalles.pchar IS NULL
	THEN
		productos.descripcion
	ELSE	
		ventas_detalles.pchar
	END as producto,  
	ventas_detalles.pchar,
	productos.idtipoproducto,
	CASE WHEN sum(cantidad) > 0 THEN sum(subtotal)/sum(cantidad) ELSE pventa END as pventa,  
	sum(cantidad) as cantidad, 
	(sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
	 sum(subtotal) as subtotal,
	 max(idventadet) as idventadet
	from ventas_detalles 
	inner join productos on productos.idprod_serial = ventas_detalles.idprod
	where 
	ventas_detalles.idventa = $idventa
	and productos.idtipoproducto not in (2,4)
	GROUP by idprod_serial, 
	CASE WHEN 
		ventas_detalles.pchar IS NULL
	THEN
		productos.descripcion
	ELSE	
		ventas_detalles.pchar
	END,
	ventas_detalles.pchar,
	 iva, barcode, productos.idtipoproducto
	
	 UNION ALL
	 
	 select idventatmp, idprod_serial,
	CASE WHEN 
		ventas_detalles.pchar IS NULL
	THEN
		productos.descripcion
	ELSE	
		ventas_detalles.pchar
	END as producto,  
	ventas_detalles.pchar,
	productos.idtipoproducto,
	CASE WHEN cantidad > 0 THEN subtotal/cantidad ELSE pventa END as pventa,  
	cantidad, 
	(subtotal-(subtotal/(1+iva/100))) as iva_monto, iva, barcode,
	 subtotal,
	idventadet
	from ventas_detalles 
	inner join productos on productos.idprod_serial = ventas_detalles.idprod
   
	where 
	ventas_detalles.idventa = $idventa
    and productos.idtipoproducto in (2,4)
	
	order by idventadet asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    $buscar = "SELECT max(idtmpventares_cab) as mayor from tmp_ventares_cab where idventa=$idventa";
    $rspediid = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idpedido = intval($rspediid->fields['mayor']);

    // tipos de iva de la factura actual
    /*$consulta="
    select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
    from ventas_detalles_impuesto
    where
    idventa = $idventa
    group by iva_porc_col
    order by iva_porc_col desc
    ";
    //echo $consulta;
    $rsivaporc=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
    $consulta = "
	SELECT iva_porc, 
	(
		select sum(monto_col) as subtotal_poriva
		from ventas_detalles_impuesto 
		where 
		idventa = $idventa
		and ventas_detalles_impuesto.iva_porc_col = tipo_iva.iva_porc
		group by iva_porc_col
	) as subtotal_poriva,
	(
		select sum(ivaml) as subtotal_monto_iva
		from ventas_detalles_impuesto 
		where 
		idventa = $idventa
		and ventas_detalles_impuesto.iva_porc_col = tipo_iva.iva_porc
		group by iva_porc_col
	) as subtotal_monto_iva
	FROM tipo_iva 
	where 
	col_fija='S'
	order by iva_porc desc
	";
    $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // conversiones
    if ($tipo_venta == 2) {
        $condicion_venta = "FACTURA CREDITO";
    } else {
        $condicion_venta = "FACTURA CONTADO";
    }
    $factura = "";
    $de_fac = "";

    if ($idtimbradotipo == 3) {
        $factura .= texto_tk("KUDE de Factura Electronica", 40, 'S').$saltolinea;
    }

    //$factura.=$saltolinea;
    if ($muestra_fantasia_fac == 'S') {
        if (trim($nombre_fantasia_empresa) != '' && trim($nombre_fantasia_empresa) != trim($razon_social_empresa)) {
            $factura .= texto_tk(trim($nombre_fantasia_empresa), 40, 'S').$saltolinea;
            //$factura.=texto_tk(trim('DE'),40,'S').$saltolinea;
            $de_fac = "DE ";
        }
    }
    $factura .= texto_tk($de_fac.trim($razon_social_empresa), 40, 'S').$saltolinea;
    $factura .= texto_tk("RUC: ".trim($ruc_empresa), 40, 'S').$saltolinea;
    if (trim($actividad_economica) != '' && $muestra_actividad_fac == 'S') {
        $factura .= "Actividad Economica: ".trim($actividad_economica).$saltolinea;
    }
    $factura .= $texto_matriz_fact.': '.trim($direccion_empresa).$saltolinea;
    if ($rssuc->fields['idsucu'] > 0) {
        $factura .= 'Sucursal: '.trim($rssuc->fields['nombre']).$saltolinea;
        $factura .= trim($rssuc->fields['direccion']).$saltolinea;
    }
    if (trim($telefono_empresa) != '') {
        $factura .= 'Tel: '.trim($telefono_empresa).$saltolinea;
    }
    $factura .= texto_tk("TIMBRADO: ".$timbrado, 40, 'S').$saltolinea;
    /*$factura.=texto_tk("Inicio Vigencia: ".$valido_desde,40,'S').$saltolinea;
    $factura.=texto_tk("Fin Vigencia: ".$valido_hasta,40,'S').$saltolinea;*/
    if ($idtimbradotipo == 3) {
        $factura .= texto_tk("Inicio Vigencia: ".$valido_desde, 40, 'S').$saltolinea;
    } else {
        $factura .= texto_tk("Vigencia: ".$valido_desde.' AL '.$valido_hasta, 40, 'S').$saltolinea;
    }
    // factura electronica
    if ($idtimbradotipo == 3) {
        $factura .= texto_tk('FACTURA ELECTRONICA'.': '.$factura_nro, 40, 'S').$saltolinea;
        if ($tipo_venta == 2) {
            $factura .= texto_tk('          COND VENTA: CREDITO', 40).$saltolinea;
        } else {
            $factura .= texto_tk('          COND VENTA: CONTADO', 40).$saltolinea;
        }

    } else {
        $factura .= texto_tk($condicion_venta.' | Nro: '.$factura_nro, 40, 'S').$saltolinea;
    }

    if (trim($rsv->fields['vencimiento_factura']) != '') {
        $factura .= texto_tk("VENCIMIENTO FACTURA: ".date("d/m/Y", strtotime($rsv->fields['vencimiento_factura'])), 40, 'S').$saltolinea;
    }

    //$factura.=texto_tk('Nro: '.$factura_nro,40,'S').$saltolinea;
    $factura .= texto_tk("Fecha y Hora: ".$fechahora, 40, 'S').$saltolinea;
    if ($orden_compra != '') {
        $factura .= texto_tk("Orden Compra: ".$orden_compra, 40, 'S').$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    if (trim($rsv->fields['ruc']) != '') {
        $factura .= 'RUC      : '.$rsv->fields['ruc'].$saltolinea;
    } else {
        $factura .= 'RUC      : '.$rsv->fields['ruc'].'-'.$rsv->fields['dv'].$saltolinea;
    }
    if (trim($documento) != '') {
        $factura .= 'CI       : '.$documento.$saltolinea;
    }
    $factura .= 'Cliente  : '.$rsv->fields['razon_social'].$saltolinea;
    if ($canal_venta != '') {
        $factura .= '----------------------------------------'.$saltolinea;
        $factura .= "CANAL VENTA: ".$canal_venta.$saltolinea;
        if (trim($rsv->fields['codpedido_externo']) != '') {
            $factura .= 'COD: '.$rsv->fields['codpedido_externo'].$saltolinea;
        }
    }

    if (trim($rsv->fields['direccion_cliente']) != '') {
        $factura .= 'Direccion: '.$rsv->fields['direccion_cliente'].$saltolinea;
    }


    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Cant    Descripcion'.$saltolinea;
    if ($iva_multiple == 'S') {
        $factura .= 'P.U.              P.T.                  '.$saltolinea;
        if ($desglosa_impuesto_multiple == 'S') {
            $factura .= 'Valores discriminados por impuesto      '.$saltolinea;
        }
    } else {
        $factura .= 'P.U.              P.T.             Tasa%'.$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsdet->EOF) {
        $idventatmp = $rsdet->fields['idventatmp'];
        if ($limita_tamano_concepto == 'S') {
            $factura .= agregaespacio(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 8).agregaespacio(escapeApostrofes($rsdet->fields['producto']), 32).$saltolinea;
        } else {
            $factura .= agregaespacio(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 8).escapeApostrofes($rsdet->fields['producto']).$saltolinea;
        }

        // combinado
        if ($rsdet->fields['idtipoproducto'] == 4) {
            if ($idventatmp > 0) {
                $consulta = "
				SELECT productos.descripcion
				FROM tmp_combinado_listas
				inner join tmp_ventares on tmp_ventares.idventatmp = tmp_combinado_listas.idventatmp
				inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
				inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
				where 
				tmp_ventares.idventatmp = $idventatmp
				";
                $rscomb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                while (!$rscomb->EOF) {
                    $factura .= agregaespacio('        ', 8).agregaespacio('»'.$rscomb->fields['descripcion'], 32).$saltolinea;
                    $rscomb->MoveNext();
                }
            }
        }
        // combo
        if ($rsdet->fields['idtipoproducto'] == 2) {
            if ($idventatmp > 0) {
                $consulta = "
				select productos.descripcion, productos.idprod_serial, 
				max(tmp_combos_listas.idventatmp_partes) as idventatmp_partes, 
				count(*) as total
				from productos 
				inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
				where 
				tmp_combos_listas.idventatmp = $idventatmp
				group by productos.descripcion, productos.idprod_serial
				";
                $rscomb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                while (!$rscomb->EOF) {
                    $factura .= agregaespacio('        ', 8).agregaespacio('»'.$rscomb->fields['total'].' x '.$rscomb->fields['descripcion'], 32).$saltolinea;
                    $rscomb->MoveNext();
                }
            }
        }

        if ($iva_multiple == 'S') {
            $factura .= agregaespacio(formatomoneda($rsdet->fields['pventa'], 2, 'N'), 18).agregaespacio(formatomoneda($rsdet->fields['subtotal'], 4, 'N'), 17).agregaespacio('', 5).$saltolinea;
        } else {
            $factura .= agregaespacio(formatomoneda($rsdet->fields['pventa'], 2, 'N'), 18).agregaespacio(formatomoneda($rsdet->fields['subtotal'], 4, 'N'), 17).agregaespacio(formatomoneda($rsdet->fields['iva'], 4, 'N'), 5).$saltolinea;
        }


        if ($iva_multiple == 'S') {
            if ($regimen_gastro == 'S') {
                $leyenda = "REGIMEN S/ DECRETO 3881";
            }

            // discriminar tasa
            $idproducto = $rsdet->fields['idprod_serial'];
            $pchar = trim($rsdet->fields['pchar']);
            if (trim($pchar) == '' && $idventatmp == 0) {
                $consulta = "
				select idproducto, 	iva_porc_col, sum(monto_col) as monto_col 
				from ventas_detalles_impuesto
				where
				idventadet in (
					select idventadet 
					from ventas_detalles
					where 
					idventa = $idventa
					and idproducto = $idproducto
					and pchar is null
					)
				group by idproducto, iva_porc_col
				";
                $rsdetimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                $consulta = "
				select idproducto, 	iva_porc_col, sum(monto_col) as monto_col 
				from ventas_detalles_impuesto
				where
				idventadet in (
					select idventadet 
					from ventas_detalles
					where 
					idventa = $idventa
					and idventatmp = $idventatmp
					
					)
				group by idproducto, iva_porc_col
				";
                $rsdetimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
            while (!$rsdetimp->EOF) {
                $iva_porc_col = floatval($rsdetimp->fields['iva_porc_col']);
                if ($iva_porc_col == 0) {
                    $nombre_col = "Exenta";
                } else {
                    $nombre_col = 'Grav. '.$iva_porc_col.'%';
                }
                if ($desglosa_impuesto_multiple == 'S') {
                    $factura .= agregaespacio(' -'.$nombre_col, 11).' : '.agregaespacio(formatomoneda($rsdetimp->fields['monto_col'], 0, 'N'), 18).$saltolinea;
                }
                $rsdetimp->MoveNext();
            }

        } // if($iva_multiple == 'S'){

        $rsdet->MoveNext();
    }




    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Total a pagar en GS: '.formatomoneda($total_factura, 4, 'N').$saltolinea;
    $factura .= $total_factura_txt.$saltolinea;
    /*-------------------------VUELTO Y COTIZACION-------------------*/
    if ($muestra_recibevuelto == 'S') {
        //Mostrar monto recibido y vuelto
        $recibeplata = floatval($rsv->fields['recibido']);
        $vuelto = floatval($rsv->fields['vv']);
        if ($vuelto > 0) {
            //echo $vuelto;exit;
            $addlinea = "RECIBIDO Gs: ".formatomoneda($recibeplata, 4, 'N')." -> VUELTO : ".formatomoneda($vuelto, 4, 'N');
            $factura .= $addlinea.$saltolinea;
        }
    }

    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsivaporc->EOF) {
        if ($rsivaporc->fields['iva_porc'] > 0) {
            $factura .= 'Total Grav. '.agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%', 3).' : '.formatomoneda($rsivaporc->fields['subtotal_poriva'] - $rsivaporc->fields['descneto10'], 0, 'N').$saltolinea;
        } else {
            $factura .= 'Total Exenta    : '.formatomoneda($rsivaporc->fields['subtotal_poriva'], 0, 'N').$saltolinea;
        }
        $rsivaporc->MoveNext();
    }
    $rsivaporc->MoveFirst();
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Liquidacion del I.V.A.'.$saltolinea;
    while (!$rsivaporc->EOF) {
        if ($rsivaporc->fields['iva_porc'] > 0) {
            $subtotal_monto_iva_acum += $rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'];
            $factura .= ''.agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%', 3).' : '.formatomoneda($rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'], 0, 'N').$saltolinea;
        }
        $rsivaporc->MoveNext();
    }
    $factura .= 'Total I.V.A. : '.formatomoneda($subtotal_monto_iva_acum, 0, 'N').$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;


    // si la preferencia dice que muestre
    if ($muestra_formapago == 'S') {

        $consulta = "
		SELECT formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det,
		(select idpago_afavor from gest_pagos_det_datos where idpagodet = gest_pagos_det.idpagodet) as idpago_afavor,
        (
        SELECT cuentas_clientes_pagos_cab.recibo 
        FROM gest_pagos_det_datos
		inner join pagos_afavor_adh on pagos_afavor_adh.idpago_afavor = gest_pagos_det_datos.idpago_afavor
        inner join cuentas_clientes_pagos_cab on cuentas_clientes_pagos_cab.idpago_afavor = pagos_afavor_adh.idpago_afavor
        where 
        gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
        ) as recibo
		
		FROM gest_pagos_det
		inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
		inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
		where 
		gest_pagos.idventa = $idventa
		and gest_pagos.estado <> 6
		";
        $rsfpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsfpag->fields['formapago'] != '') {
            $factura .= 'Pagos: '.$saltolinea;
            while (!$rsfpag->EOF) {
                $factura .= agregaespacio(strtoupper($rsfpag->fields['formapago']), 22).': '.agregaespacio_tk(formatomoneda($rsfpag->fields['monto_pago_det']), 16, 'der', 'S').$saltolinea;
                if (intval($rsfpag->fields['idpago_afavor']) > 0) {
                    $factura .= '  > Aplicado a: '.$rsfpag->fields['recibo'].' ['.$rsfpag->fields['idpago_afavor'].']'.$saltolinea;
                }
                $rsfpag->MoveNext();
            }
            $factura .= '----------------------------------------'.$saltolinea;
        }
    }
    if ($muestra_cotizacion == 'S') {
        //Mostraremos la cotizacion en otras monedas
        // muestra solo las monedas con cotizacion cargada en el dia
        $ahorad = date("Y-m-d");
        $consulta = "
			select *,
					(
					select cotizaciones.cotizacion
					from cotizaciones
					where 
					cotizaciones.estado = 1 
					and date(cotizaciones.fecha) = '$ahorad'
					and tipo_moneda.idtipo = cotizaciones.tipo_moneda
					order by cotizaciones.fecha desc
					limit 1
					) as cotizacion
			from tipo_moneda 
			where
			estado = 1
			and borrable = 'S'
			and 
			(
				(
					borrable = 'N'
				) 
				or  
				(
					tipo_moneda.idtipo in 
					(
					select cotizaciones.tipo_moneda 
					from cotizaciones
					where 
					cotizaciones.estado = 1 
					and date(cotizaciones.fecha) = '$ahorad'
					)
				)
			)
			order by borrable ASC, descripcion asc
			";
        $rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tmoneda = $rsmoneda->RecordCount();
        if ($tmoneda > 0) {

            $factura .= $saltolinea.'------------OTRAS MONEDAS---------------'.$saltolinea;
            while (!$rsmoneda->EOF) {
                $coti = formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N');
                $monto = formatomoneda($total_factura / $rsmoneda->fields['cotizacion'], 2, 'N');
                $palabra1 = trim($rsmoneda->fields['descripcion'])."($coti) : ".$monto;


                $addlinea = "$palabra1";
                $factura .= $addlinea.$saltolinea;
                $rsmoneda->MoveNext();
            }
        }
    }

    if ($rsv->fields['numero_mesa'] > 0) {
        $factura .= 'Mesa: '.$rsv->fields['numero_mesa'].' PAX: '.$saltolinea;
    }
    if (trim($rsv->fields['nombre_corto_cliente']) != '') {
        $factura .= 'Nombre: '.$rsv->fields['nombre_corto_cliente'].$saltolinea;
    }
    if (trim($rsv->fields['observacion_larga']) != '') {
        $factura .= 'Obs.: '.$rsv->fields['observacion_larga'].$saltolinea;
    } else {
        if (trim($rsv->fields['observacion']) != '') {
            $factura .= 'Obs.: '.$rsv->fields['observacion'].$saltolinea;
        }
    }
    if (trim($rsv->fields['cajero']) != '') {
        $factura .= 'Cajero: '.$rsv->fields['cajero'].$saltolinea;
    }
    //$factura.='Cod Pedido: '.$idpedido.$saltolinea;
    // delivery
    if ($rsv->fields['idcanal'] == 3) {
        $factura .= '----------------------------------------'.$saltolinea;
        $factura .= "DELIVERY: ".$saltolinea;
        if (trim($rsv->fields['motorista']) != '') {
            $factura .= "MOTORISTA: ".trim($rsv->fields['motorista']).$saltolinea;
        }
        $factura .= "LLEVA POS: ".siono($rsv->fields['llevapos']).$saltolinea;
        $factura .= "Telefono: 0".$rsv->fields['telefono'].$saltolinea;
        $factura .= "Cliente: ".$rsv->fields['nombre_deliv'].' '.$rsv->fields['apellido_deliv'].$saltolinea;
        $factura .= "Direccion: ".$rsv->fields['direccion'].$saltolinea;
        if (trim($rsv->fields['referencia']) != '') {
            $factura .= "Referencia: ".$rsv->fields['referencia'].$saltolinea;
        }
        if (trim($rsv->fields['observacion_larga']) != '') {
            $factura .= "Obs. Oper: ".$rsv->fields['observacion_larga'].$saltolinea;
        } else {
            if (trim($rsv->fields['observacion']) != '') {
                $factura .= "Obs. Oper: ".$rsv->fields['observacion'].$saltolinea;
            }
        }
        if (trim($rsv->fields['observacion_delivery']) != '') {
            $factura .= "Obs. Deliv: ".$rsv->fields['observacion_delivery'].$saltolinea;
        }
        $factura .= '----------------------------------------'.$saltolinea;
    }
    // mostrar cotizacion si esta Activida
    if ($muestra_coti_auto == 'S') {
        $hoy = date("Y-m-d", strtotime($rsv->fields['fecha']));
        $buscar = "select descripcion,idtipo,(select cotizacion  from cotizaciones where estado=1 and date(fecha)='$hoy' and tipo_moneda=idtipo  order by fecha desc limit 1) as cotizacion
		from tipo_moneda 
		where estado=1 order by descripcion asc";
        $rscoti = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tcoti = $rscoti->RecordCount();
        if ($tcoti > 0) {
            $factura .= '------------OTRAS MONEDAS----------------'.$saltolinea;

            while (!$rscoti->EOF) {

                $coti = floatval($rscoti->fields['cotizacion']);
                $vc = $total_factura / $coti;
                $vf = formatomoneda($vc, 2, 'S');
                $nombre = trim($rscoti->fields['descripcion']);
                $factura .= "$nombre = ".$vf.$saltolinea;

                $rscoti->MoveNext();
            }
        }
    }

    $factura .= 'Caja: #'.$rsv->fields['idcaja']." Vta: #".$rsv->fields['idventa'].' Ped: #'.$idpedido.$saltolinea;
    if ($tipo_venta == 2) {
        if (trim($leyenda_credito) != '') {
            $factura .= '----------------------------------------'.$saltolinea;
            $factura .= $leyenda_credito.$saltolinea;
        }
    }


    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Impreso: '.date("d/m/Y H:i:s").$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;


    // SI ES ELECTRONICA
    if ($idtimbradotipo == 3) {
        //$factura.=$saltolinea;
        $factura .= 'Consulte la validez de esta Factura Electronica con el numero de CDC impreso abajo en: https://ekuatia.set.gov.py/consultas'.$saltolinea;
        $factura .= 'CDC: 0180 1121 7010 0100 1000 0362 2202 2041 8188 2264 6475'.$saltolinea;
        $factura .= 'ESTE DOCUMENTO ES UNA REPRESENTACION GRAFICA DE UN DOCUMENTO ELECTRONICO (XML)'.$saltolinea;

        $consulta = "
		select qr from documentos_electronicos_emitidos where idventa = $idventa order by iddocumentoemitido desc limit 1
		";
        $rsqr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $contenido_qr = $rsqr->fields['qr'];

        /*$contenido_qr="https://ekuatia.set.gov.py/consultas-test/qr?nVersion=150&Id=01801121701001001000036222022041818822646475&dFeEmiDE=323032322d30342d31385430343a30303a3536&dRucRec=80017843&dTotGralOpe=480000.00&dTotIVA=30000&cItems=2&DigestValue=79346e3465376d575a6e5647562f2b3172684b5a7a71495a385276666e44355a41516d6963496c46734c593d&IdCSC=0001&cHashQR=e05eddc5a6cd4866c258aa9cba27a176ccb010b916bb41f0fa619f40708bcfe0";*/

        $factura_qr .= '<QR>'.$contenido_qr.'</QR>'.$saltolinea;


        $factura_pos_qr .= '----------------------------------------'.$saltolinea;
    }

    if (trim($wifi) != '') {
        $factura_pos_qr .= texto_tk('WIFI: '.$wifi, 40, 'S').$saltolinea;
    }
    if (strlen($pie_factura) > 40) {
        $factura_pos_qr .= $pie_factura.$saltolinea;
    } else {
        $factura_pos_qr .= texto_tk($pie_factura, 40, 'S').$saltolinea;
    }
    if (trim($leyenda_termico) != '') {
        $factura_pos_qr .= $leyenda_termico.$saltolinea;
        $factura_pos_qr .= '----------------------------------------'.$saltolinea;
    }
    /*
    $factura.='Original: Cliente'.$saltolinea;
    $factura.='Duplicado: Archivo Tributario'.$saltolinea;
    $factura.='Triplicado: Contabilidad'.$saltolinea;*/
    $factura_pos_qr .= $pie_final_factura.$saltolinea;


    $factura_pos_qr .= $saltolinea;


    // opcional, sucategoria por factura
    if ($subcat_factura == 'S') {
        $consulta = "
		select sub_categorias.descripcion as subcategoria, sum(ventas_detalles.cantidad) as cantidad, 
		sum(ventas_detalles.subtotal) as total
		from ventas 
		inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
		inner join productos on productos.idprod_serial = ventas_detalles.idprod
		inner join sub_categorias on sub_categorias.idsubcate = productos.idsubcate
		where
		ventas.idventa = $idventa
		and  ventas.estado <> 6
		group by sub_categorias.descripcion
		order by sum(ventas_detalles.subtotal) desc
		";
        $rssub = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $factura_pos_qr .= $saltolinea.$saltolinea.$saltolinea;
        $factura_pos_qr .= '----------------------------------------'.$saltolinea;
        $factura_pos_qr .= texto_tk("VENTAS POR SUBCATEGORIA VTA #".$idventa, 40, 'S').$saltolinea;
        $factura_pos_qr .= '----------------------------------------'.$saltolinea;
        $factura_pos_qr .= 'Cant    Sub-categoria'.$saltolinea;
        $factura_pos_qr .= '----------------------------------------'.$saltolinea;
        while (!$rssub->EOF) {
            $factura_pos_qr .= agregaespacio(formatomoneda($rssub->fields['cantidad'], 4, 'N'), 8).agregaespacio($rssub->fields['subcategoria'], 32).$saltolinea;
            $rssub->MoveNext();
        }
        $factura_pos_qr .= '----------------------------------------'.$saltolinea;
        $factura_pos_qr .= $saltolinea;

    }
    // si es factura elctronica se separa por que sino inventa un espacio el wordwrap
    if ($idtimbradotipo == 3) {
        // texto anterior a qr aplica el recorte
        $factura = wordwrap($factura, 40, $saltolinea, true);
        // texto posterior a qr aplica el recorte
        $factura_pos_qr = wordwrap($factura_pos_qr, 40, $saltolinea, true);
        // une los 3 textos
        $factura_final = $factura.$factura_qr.$factura_pos_qr;
    } else {
        $factura_final = wordwrap($factura.$factura_qr.$factura_pos_qr, 40, $saltolinea, true);
    }

    $saltolinea = $aux;

    return $factura_final;

}
function ticket_venta($idventa)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;
    global $idusu;


    // log de impresiones
    $consulta = "
	INSERT INTO log_impresiones_ventas
	(idventa, impreso_por, impreso_el) 
	VALUES
	($idventa,$idusu,'$ahora')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select * from empresas where idempresa = $idempresa
	";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $fantasia_empresa = trim($rsemp->fields['empresa']);

    $consulta = "
	select * from preferencias_caja limit 1;
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $muestra_formapago = trim($rsprefcaj->fields['muestra_formapago']);

    $muestra_linea_credito = trim($rsprefcaj->fields['muestralc']);
    $muestra_saldo_linea_credito = trim($rsprefcaj->fields['muestrasaldolc']);
    $muestra_cotizacion = trim($rsprefcaj->fields['usa_cotizacion']);
    $muestra_recibevuelto = trim($rsprefcaj->fields['muestrarecibe']);

    //linea_sobregiro: linea mensual
    //saldo_sobregiro: linea disponible
    $consulta = "
	select *,ventas.vuelto as vv, ventas.sucursal as idsucursal, ventas.idcanal, (select count(*) from (select idprod FROM ventas_detalles where ventas_detalles.idemp = $idempresa and ventas_detalles.idventa = $idventa GROUP by idprod) as tot)  as total_detalles,
	(select numero_mesa from mesas where idmesa = ventas.idmesa) as numero_mesa,
	(select canal from canal where idcanal = ventas.idcanal) as canal,
	(select wifi from sucursales where sucursales.idsucu = ventas.sucursal limit 1) as wifi,
	(select telefono from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as telefono,
	(select direccion from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as direccion,
	(select chapa from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_corto_cliente,
	(select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,
	(select llevapos  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as llevapos,
	(select cambio  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as cambio,
	(select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as vuelto,
	(select observacion_delivery  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion_delivery,
	(select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
	(select nombre_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_deliv,
	(select apellido_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as apellido_deliv,
    (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,	
	(select idtmpventares_cab as idpedido from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as idpedido,
	(select usuario from usuarios where idusu = ventas.registrado_por) as cajero,
	formapago as idformapago,
	(select descripcion from formas_pago where idforma = ventas.formapago) as forma_pago,
	(select cant_adultos+cant_ninos+cant_nopaga as pax from mesas_atc where mesas_atc.idatc = ventas.idatc) as pax,
	(select canal_venta from canal_venta where idcanalventa  = ventas.idcanalventa) as canal_venta
	from ventas
	inner join cliente on cliente.idcliente = ventas.idcliente 
	where 
	ventas.idempresa = $idempresa 
	and ventas.idventa = $idventa
	and ventas.estado <> 6 
	limit 1
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_venta = intval($rsv->fields['tipo_venta']);
    $total_detalles = intval($rsv->fields['total_detalles']);
    $idpedido = intval($rsv->fields['idpedido']);
    $idtandatimbrado = intval($rsv->fields['idtandatimbrado']);
    $factura_nro = trim($rsv->fields['factura']);
    $idadherente = intval($rsv->fields['idadherente']);
    if ($rsv->fields['finalizo_correcto'] == 'N') {
        echo "ANULAR VENTA";
        exit;
    }

    // datos del adherente
    if ($idadherente > 0) {
        $consulta = "SELECT * FROM adherentes where idadherente = $idadherente and idempresa = $idempresa";
        $rsadh = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $nombre_adherente = $saltolinea."ADH: ".texto_tk(trim($rsadh->fields['nomape']), 35);
        $nombre_adherente_sinsalto = "ADH: ".texto_tk(trim($rsadh->fields['nomape']), 35);
        $codadherente = "";
        if ($rprf->fields['imprime_cod_adherente'] == 'S') {
            $consulta = "SELECT * FROM clientes_codigos where idadherente = $idadherente and idempresa = $idempresa";
            $rsadhcod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $codadherente = $saltolinea."COD ADH: ".texto_tk($rsadhcod->fields['us_cod'], 35);

            //$nombre_adherente=$nombre_adherente.'';
        }
    }


    /*if($factura_nro == ''){
        echo "FACTURA NO GENERADA";
        exit;
    }*/
    $fechahora = date("d/m/Y H:i", strtotime($rsv->fields['fecha']));
    $idsucursal = intval($rsv->fields['idsucursal']);
    // numeros a letras
    require_once("includes/num2letra.php");

    $descuento = floatval($rsv->fields['descneto']);
    $total_factura = $rsv->fields['total_venta'] - $descuento; // incluido el descuento
    $total_factura_txt = strtoupper(num2letras(floatval($total_factura)));
    $delivery_costo = $rsv->fields['delivery_costo'];

    // solo sucursal que no es casa matriz por eso idsucu > 0
    $consulta = "
	SELECT * 
	FROM sucursales 
	where 
	idempresa = $idempresa 
	and idsucu = $idsucursal
	and idsucu > 1
	";
    //echo $consulta;
    $rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    /*if($idtandatimbrado > 0){
        $ahorad=date("Y-m-d",strtotime($ahora));
        $consulta="
        SELECT *
        FROM facturas
        where
        idtanda = $idtandatimbrado
        and estado = 'A'
        and valido_hasta >= '$ahorad'
        and valido_desde <= '$ahorad'
        ";
        $rstimbrado=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        //echo $consulta;
        $timbrado=trim($rstimbrado->fields['timbrado']);
        $valido_desde=date("d/m/Y",strtotime($rstimbrado->fields['valido_desde']));
        $valido_hasta=date("d/m/Y",strtotime($rstimbrado->fields['valido_hasta']));
        $idtanda=intval($rstimbrado->fields['idtanda']);
        if($idtanda == 0){
            echo "Timbrado vencido o inexistente.";
            exit;
        }

    }else{

        echo "No hay timbrado activo.";
        exit;
    }*/

    $consulta = "
	select idprod_serial, productos.descripcion as producto, pventa, sum(cantidad) as cantidad, 
	(sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
	 productos.descripcion as producto, sum(subtotal) as subtotal
	from ventas_detalles 
	inner join productos on productos.idprod_serial = ventas_detalles.idprod
	where 
	ventas_detalles.idemp = $idempresa 
	and ventas_detalles.idventa = $idventa
	GROUP by idprod_serial, productos.descripcion, iva, pventa, barcode
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // tipos de iva de la factura actual
    $consulta = "
	select  iva as iva_porc, sum(subtotal) as subtotal_poriva, (sum(subtotal)-(sum(subtotal)/(1+iva/100))) as subtotal_monto_iva,
	CASE WHEN
		iva = 10
	THEN
		$descuento
	ELSE
		0
	END as descneto10,
	CASE WHEN
		iva = 10
	THEN
		$descuento/11
	ELSE
		0
	END as descnetoiva10
	from ventas_detalles 
	where 
	idventa = $idventa
	group by iva 
	order by iva desc
	";
    //echo $consulta;
    $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // conversiones
    if ($tipo_venta == 2) {
        $condicion_venta = "TICKET CREDITO";
    } else {
        $condicion_venta = "TICKET CONTADO";
    }
    $factura = "";
    $factura .= $saltolinea;
    $factura .= texto_tk(trim($fantasia_empresa), 40, 'S').$saltolinea;
    //$factura.=texto_tk("RUC: ".trim($ruc_empresa),40,'S').$saltolinea;
    if (trim($direccion_empresa) != '') {
        $factura .= 'C Matriz: '.trim($direccion_empresa).$saltolinea;
    }
    if ($rssuc->fields['idsucu'] > 0) {
        $factura .= 'Sucursal: '.trim($rssuc->fields['nombre']).$saltolinea;
        $factura .= trim($rssuc->fields['direccion']).$saltolinea;
    }
    $factura .= texto_tk($condicion_venta, 40, 'S').$saltolinea;
    $factura .= texto_tk("VTA: ".$idventa." | PED: ".$idpedido, 40, 'S').$saltolinea;
    $factura .= texto_tk("Fecha y Hora: ".$fechahora, 40, 'S').$saltolinea;
    $factura .= 'RUC      : '.$rsv->fields['ruchacienda'].'-'.$rsv->fields['dv'].$saltolinea;
    if (trim($documento) != '') {
        $factura .= 'CI       : '.$documento.$saltolinea;
    }
    $factura .= 'Cliente  : '.$rsv->fields['razon_social'].$saltolinea;
    if (trim($rsv->fields['canal_venta']) != '') {
        $factura .= '----------------------------------------'.$saltolinea;
        $factura .= 'CANAL VENTA: '.$rsv->fields['canal_venta'].$saltolinea;
        if (trim($rsv->fields['codpedido_externo']) != '') {
            $factura .= 'COD: '.$rsv->fields['codpedido_externo'].$saltolinea;
        }
    }
    if ($idadherente > 0) {
        $factura .= $nombre_adherente_sinsalto.$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    //$factura.='CI / RUC : '.$rsv->fields['ruchacienda'].'-'.$rsv->fields['dv'].$saltolinea;
    //$factura.='Cliente  : '.$rsv->fields['razon_social'].$saltolinea;
    //$factura.='----------------------------------------'.$saltolinea;
    $factura .= 'Cant    Descripcion'.$saltolinea;
    $factura .= 'P.U.              P.T.             Tasa%'.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsdet->EOF) {
        $factura .= agregaespacio(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 8).agregaespacio($rsdet->fields['producto'], 32).$saltolinea;
        $factura .= agregaespacio(formatomoneda($rsdet->fields['pventa'], 4, 'N'), 18).agregaespacio(formatomoneda($rsdet->fields['subtotal'], 4, 'N'), 17).agregaespacio(formatomoneda($rsdet->fields['iva'], 4, 'N'), 5).$saltolinea;
        $rsdet->MoveNext();
    }
    if ($rsv->fields['idcanal'] == 3 && $delivery_costo > 0) {
        $factura .= agregaespacio(formatomoneda(1, 4, 'N'), 8).agregaespacio('DELIVERY', 32).$saltolinea;
        $factura .= agregaespacio(formatomoneda($delivery_costo, 4, 'N'), 18).agregaespacio(formatomoneda($delivery_costo, 4, 'N'), 17).agregaespacio(formatomoneda(10, 4, 'N'), 5).$saltolinea;
    }
    if ($descuento > 0) {
        $factura .= agregaespacio(formatomoneda(1, 4, 'N'), 8).agregaespacio('DESCUENTO', 32).$saltolinea;
        $factura .= agregaespacio(formatomoneda($descuento * -1, 4, 'N'), 18).agregaespacio(formatomoneda($descuento * -1, 4, 'N'), 17).agregaespacio(formatomoneda(10, 4, 'N'), 5).$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Total a pagar en GS: '.formatomoneda($total_factura, 4, 'N').$saltolinea;
    $factura .= $total_factura_txt.$saltolinea;


    $factura .= '----------------------------------------'.$saltolinea;
    if ($rsv->fields['numero_mesa'] > 0) {
        $factura .= 'Mesa: '.intval($rsv->fields['numero_mesa']).' | PAX: '.intval($rsv->fields['pax']).$saltolinea;
    }
    // delivery
    if ($rsv->fields['idcanal'] == 3) {
        $factura .= "DELIVERY: ".$saltolinea;
        $factura .= "LLEVA POS: ".siono($rsv->fields['llevapos']).$saltolinea;
        $factura .= "Telefono: 0".$rsv->fields['telefono'].$saltolinea;
        $factura .= "Cliente: ".$rsv->fields['nombre_deliv'].' '.$rsv->fields['apellido_deliv'].$saltolinea;
        $factura .= "Direccion: ".$rsv->fields['direccion'].$saltolinea;
        $factura .= "Obs. Oper: ".$rsv->fields['observacion'].$saltolinea;
        $factura .= "Obs. Deliv: ".$rsv->fields['observacion_delivery'].$saltolinea;
        $factura .= '----------------------------------------'.$saltolinea;
    }
    /*
        (select numero_mesa from mesas where idmesa = ventas.idmesa) as numero_mesa,
    (select canal from canal where idcanal = ventas.idcanal) as canal,
    (select wifi from sucursales where sucursales.idsucu = ventas.sucursal limit 1) as wifi,
    (select chapa from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_corto_cliente,
    (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,
    (select llevapos  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as llevapos,
    (select cambio  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as cambio,
    (select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as vuelto,
    (select observacion_delivery  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion_delivery,
    (select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
    (select usuario from usuarios where idusu = ventas.registrado_por) as cajero,
    formapago as idformapago,
    (select descripcion from formas_pago where idforma = ventas.formapago) as forma_pago
    */
    if ($muestra_formapago == 'S') {
        $factura .= 'Pagos: '.$saltolinea;
        $consulta = "
		SELECT formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det
		FROM gest_pagos_det
		inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
		inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
		where 
		gest_pagos.idventa = $idventa
		";
        $rsfpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rsfpag->EOF) {
            $factura .= agregaespacio(strtoupper($rsfpag->fields['formapago']), 22).': '.agregaespacio_tk(formatomoneda($rsfpag->fields['monto_pago_det']), 16, 'der', 'S').$saltolinea;
            $rsfpag->MoveNext();
        }
        $factura .= '----------------------------------------'.$saltolinea;
    }
    $factura .= 'Caja: #'.$rsv->fields['idcaja']." Vta: #".$rsv->fields['idventa'].$saltolinea;
    if ($muestra_linea_credito == 'S') {
        $montolc = floatval($rsv->fields['linea_sobregiro']);
        $addlinea = "Linea Mes: ".formatomoneda($montolc, 4, 'N');
        $factura .= $addlinea.$saltolinea;
    }
    if ($muestra_saldo_linea_credito == 'S') {

        $montosaldo = floatval($rsv->fields['saldo_sobregiro']);
        $addlinea = "Saldo Actual: ".formatomoneda($montosaldo, 4, 'N');
        $factura .= $addlinea.$saltolinea;
    }
    if ($muestra_cotizacion == 'S') {
        //Mostraremos la cotizacion en otras monedas
        // muestra solo las monedas con cotizacion cargada en el dia
        $ahorad = date("Y-m-d");
        $consulta = "
			select *,
					(
					select cotizaciones.cotizacion
					from cotizaciones
					where 
					cotizaciones.estado = 1 
					and date(cotizaciones.fecha) = '$ahorad'
					and tipo_moneda.idtipo = cotizaciones.tipo_moneda
					order by cotizaciones.fecha desc
					limit 1
					) as cotizacion
			from tipo_moneda 
			where
			estado = 1
			and borrable = 'S'
			and 
			(
				(
					borrable = 'N'
				) 
				or  
				(
					tipo_moneda.idtipo in 
					(
					select cotizaciones.tipo_moneda 
					from cotizaciones
					where 
					cotizaciones.estado = 1 
					and date(cotizaciones.fecha) = '$ahorad'
					)
				)
			)
			order by borrable ASC, descripcion asc
			";
        $rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tmoneda = $rsmoneda->RecordCount();
        if ($tmoneda > 0) {

            $factura .= $saltolinea.'------------OTRAS MONEDAS---------------'.$saltolinea;
            while (!$rsmoneda->EOF) {
                $coti = formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N');
                $monto = formatomoneda($total_factura / $rsmoneda->fields['cotizacion'], 2, 'N');
                $palabra1 = trim($rsmoneda->fields['descripcion'])."($coti) : ".$monto;


                $addlinea = "$palabra1";
                $factura .= $addlinea.$saltolinea;
                $rsmoneda->MoveNext();
            }
        }
    }
    if ($muestra_recibevuelto == 'S') {
        //Mostrar monto recibido y vuelto
        $recibeplata = floatval($rsv->fields['recibido']);
        $vuelto = floatval($rsv->fields['vv']);
        $addlinea = "Recibido Gs: ".formatomoneda($recibeplata, 4, 'N')." -> Vuelto : ".formatomoneda($vuelto, 4, 'N');
        $factura .= $addlinea.$saltolinea;
    }

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Impreso: '.date("d/m/Y H:i:s").$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= texto_tk('*** USO INTERNO, AGUARDE SU FACTURA ***', 40, 'S').$saltolinea;
    $factura .= texto_tk('*** GRACIAS POR SU COMPRA ***', 40, 'S').$saltolinea;

    $factura .= $saltolinea;

    $factura = wordwrap($factura, 40, $saltolinea, true);
    return $factura;

}
function ticket_venta_json($idventa)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;
    global $idusu;

    // log de impresiones
    $consulta = "
	INSERT INTO log_impresiones_ventas
	(idventa, impreso_por, impreso_el) 
	VALUES
	($idventa,$idusu,'$ahora')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "Select * from preferencias where idempresa=$idempresa";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $contado_txt = trim($rspref->fields['contado_txt']);
    $credito_txt = trim($rspref->fields['credito_txt']);
    $factura_pred = trim($rspref->fields['factura_pred']);
    $forzar_agrupacion = trim($rspref->fields['forzar_agrupacion']);
    $anteponer_moneda_fact = trim($rspref->fields['anteponer_moneda_fact']);
    $ticket_fox = trim($rspref->fields['ticket_fox']);
    if ($forzar_agrupacion == '') {
        $forzar_agrupacion = "N";
    }
    if ($factura_pred == '') {
        $factura_pred = "N";
    }
    if ($anteponer_moneda_fact == '') {
        $anteponer_moneda_fact = "N";
    }
    $agrupar_articulos = trim($rspref->fields['agrupar_items_factura']);//Indica si se imprimen las cantidades y descripciones de la venta en la factura
    $describe_factura = trim(strtoupper($rspref->fields['describe_factura']))	;//Indica el texto x defecto que se va usar si esta en uso agrupar facturas
    $maximo_items = intval($rspref->fields['max_items_factura']); //Idica cuantos articulos caben en la factura. Por mas que agrupar facturas sea si, se debe completar con la cantidad excta para rellenar los vacios (lineas)
    $max_items_factura = $maximo_items;
    $imprime_idvta = trim($rspref->fields['imprimir_idvta']);	//Imprime ek id de la vta en factura
    $imprime_idped = trim($rspref->fields['imprimir_idped']);	//Imprime ek id pedido en factura

    $consulta = "
	select *, (select count(*) from (select idprod FROM ventas_detalles where ventas_detalles.idemp = $idempresa and ventas_detalles.idventa = $idventa GROUP by idprod) as tot)  as total_detalles,
	(select canal from canal where idcanal = ventas.idcanal) as canal,
	(select wifi from sucursales where sucursales.idsucu = ventas.sucursal limit 1) as wifi,
	(select chapa from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_corto_cliente,
	(select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,
	(select llevapos  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as llevapos,
	(select cambio  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as cambio,
	(select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as vuelto,
	(select observacion_delivery  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion_delivery,
	(select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
	(select usuario from usuarios where idusu = ventas.registrado_por) as cajero,
	formapago as idformapago,
	(select descripcion from formas_pago where idforma = ventas.formapago) as forma_pago
	from ventas
	inner join cliente on cliente.idcliente = ventas.idcliente 
	where 
	ventas.idempresa = $idempresa 
	and ventas.idventa = $idventa
	and ventas.estado <> 6 
	limit 1
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_detalles = intval($rsv->fields['total_detalles']);
    $idpedido = intval($rsv->fields['idpedido']);
    $idcanal = intval($rsv->fields['idcanal']);
    $canal = htmlentities($rsv->fields['canal']);
    $wifi = htmlentities($rsv->fields['wifi']);
    $nombre_corto_cliente = htmlentities($rsv->fields['nombre_corto_cliente']);
    $delivery_costo = htmlentities($rsv->fields['delivery_costo']);
    $cajero = htmlentities($rsv->fields['cajero']);
    $idformapago = intval($rsv->fields['idformapago']);
    $formapago = htmlentities($rsv->fields['forma_pago']);

    $llevapos = htmlentities($rsv->fields['llevapos']);
    $pagacon = htmlentities($rsv->fields['cambio']);
    $vuelto = $rsv->fields['vuelto'];
    $observacion_delivery = htmlentities($rsv->fields['observacion_delivery']);
    $observacion_operador = htmlentities($rsv->fields['observacion']);

    if ($rsv->fields['finalizo_correcto'] == 'N') {
        echo "ANULAR VENTA";
        exit;
    }

    $consulta = "
	select idprod_serial, productos.descripcion as producto, pventa, sum(cantidad) as cantidad, 
	(sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
	 productos.descripcion as producto, sum(subtotal) as subtotal,
	 sum(subtotal_sindesc) as subtotal_sindesc, COALESCE(sum(ventas_detalles.descuento),0) as descuento_monto, 
	 COALESCE(sum(descuento_porc)/count(descuento_porc),0) as descuento_porc
	from ventas_detalles 
	inner join productos on productos.idprod_serial = ventas_detalles.idprod
	where 
	ventas_detalles.idemp = $idempresa 
	and ventas_detalles.idventa = $idventa
	GROUP by idprod_serial, productos.descripcion, iva, pventa, barcode
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // numeros a letras
    require_once("includes/num2letra.php");

    $descuento = floatval($rsv->fields['descneto']);
    $total_factura = $rsv->fields['total_venta'] - $descuento; // incluido el descuento
    $total_factura_txt = strtoupper(num2letras(floatval($total_factura)));
    if ($anteponer_moneda_fact == 'S') {
        $total_factura_txt = "GUARANIES ".$total_factura_txt;
    }
    // si tiene descuento
    if ($descuento > 0) {
        $total_detalles++;
    }


    // si supera la cantidad de la factura
    if ($total_detalles <= $max_items_factura && $forzar_agrupacion == 'N') {
        while (!$rsdet->EOF) {
            $factura_det[] = [
                'cantidad' => $rsdet->fields['cantidad'],
                'descripcion' => limpiar_txt_fac(trim($rsdet->fields['producto'])),
                'precio_unitario' => $rsdet->fields['pventa'],
                'subtotal' => $rsdet->fields['subtotal'], //
                'subtotal_sindesc' => $rsdet->fields['subtotal_sindesc'],
                'descuento_monto' => $rsdet->fields['descuento_monto'],
                'descuento_porc' => $rsdet->fields['descuento_porc'],
                'iva_monto' => $rsdet->fields['iva_monto'],
                'iva_porc' => $rsdet->fields['iva'],
                'codigo_barras' => trim($rsdet->fields['barcode']),
                'codigo_producto' => $rsdet->fields['idprod_serial']
            ];
            $rsdet->MoveNext();
        }
        // si tiene descuento
        if ($descuento > 0) {
            $factura_det[] = [
                'cantidad' => 1,
                'descripcion' => trim('DESCUENTO'),
                'precio_unitario' => ($descuento) * -1,
                'subtotal' => ($descuento) * -1, //
                'iva_monto' => ($descuento / 11) * -1,
                'iva_porc' => 10,
                'codigo_barras' => '',
                'codigo_producto' => ''
            ];
        }
    } else {
        $factura_det[] = [
            'cantidad' => 1,
            'descripcion' => trim('CONSUMISION'),
            'precio_unitario' => $total_factura,
            'subtotal' => $total_factura, //
            'iva_monto' => $total_factura / 11,
            'iva_porc' => 10,
            'codigo_barras' => '',
            'codigo_producto' => ''
        ];
    }


    // tipos de iva de la factura actual
    $consulta = "
	select  
	iva as iva_porc, sum(subtotal) as subtotal_poriva, 
	(sum(subtotal)-(sum(subtotal)/(1+iva/100))) as subtotal_monto_iva,
	CASE WHEN
		iva = 10
	THEN
		$descuento
	ELSE
		0
	END as descneto10,
	CASE WHEN
		iva = 10
	THEN
		$descuento/11
	ELSE
		0
	END as descnetoiva10
	from ventas_detalles 
	where 
	idventa = $idventa
	group by iva 
	order by iva desc
	";
    //echo $consulta;
    $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //while por cada tipo de iva y totaliza metiendo en un array
    /*if($total_detalles <= $max_items_factura && $forzar_agrupacion == 'N'){
        while(!$rsivaporc->EOF){
            $factura_det_impuesto[]=array(
                'subtotal_poriva' => $rsivaporc->fields['subtotal_poriva']-$rsivaporc->fields['descneto10'],
                'iva_monto_total' => $rsivaporc->fields['subtotal_monto_iva']-$rsivaporc->fields['descnetoiva10'],
                'iva_porc_total' => $rsivaporc->fields['iva_porc']
            );
        $rsivaporc->MoveNext(); }

    }else{
            $factura_det_impuesto[]=array(
                'subtotal_poriva' => $total_factura, //
                'iva_monto_total' => $total_factura/11,
                'iva_porc_total' => 10
            );
    }*/




    // conversiones
    if ($rsv->fields['tipo_venta'] == 2) {
        $condicion = 'CRED';
        $contado_txt = "";
    } else {
        $condicion = 'CON';
        $credito_txt = "";
    }
    if ($total_detalles > $max_items_factura) {
        $total_detalles = 1;
    }
    if ($forzar_agrupacion == 'S') {
        $total_detalles = 1;
    }

    // datos extra
    $idmesa = intval($rsv->fields['idmesa']);
    if ($idmesa > 0) {
        $consulta = "
		SELECT * , (select usuario from usuarios where idusu = mesas.idmozo_abrio) as mozo_abrio
		FROM mesas 
		inner join salon on salon.idsalon = mesas.idsalon
		where 
		idmesa = $idmesa
		limit 1
		";
        $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $mesa = $rsmesa->fields['numero_mesa'];
        $salon = $rsmesa->fields['nombre'];
        $mozo_abrio = $rsmesa->fields['mozo_abrio'];
    }




    $factura_cab = [
        'idventa' => $idventa,
        'idpedido' => $idpedido,
        'ruc' => $rsv->fields['ruchacienda'].'-'.$rsv->fields['dv'],
        'razon_social' => limpiar_txt_fac(trim($rsv->fields['razon_social'])),
        'direccion' => limpiar_txt_fac(trim($rsv->fields['direccion'])),
        'telefono' => trim($rsv->fields['telefono']),
        'condicion' => $condicion, // CON contado O CRED credito
        'contado_txt' => $contado_txt, // X, CON o CONTADO
        'credito_txt' => $credito_txt, // X, CRED o CREDITO
        'cuotas' => '', // 0 opcional
        'dias_credito' => '',  // 30 60 90 opcional
        'remision' => '',  // 00100100654321 opcional
        'ticket' => $idventa,
        'vencimiento' => '', // 2019-06-15 opcional
        'fecha_emision' => date("Y-m-d", strtotime($rsv->fields['fecha'])), // 2019-05-17 opcional
        'muestra_tk' => 'S', // S o N
        'muestra_idv' => 'S', // S o N
        'total_ticket' => $total_factura,
        'total_ticket_txt' => $total_factura_txt,
        'max_items_ticket' => $max_items_factura, // cantidad maxima de items que entran en el cuerpo de la factura
        'total_detalles' => $total_detalles,
        'detalle_ticket' => $factura_det,
        'detalle_impuesto' => $factura_det_impuesto,

        'mesa' => $mesa,
        'salon' => $salon,
        'mozo' => $mozo_abrio,
        'canal' => $canal,
        'idcanal' => $idcanal,
        'cajero' => $cajero,
        'texto_cabeza' => $texto_cabeza,
        'texto_pie' => $texto_pie,
        'wifi' => $wifi,
        'nombre_corto_cliente' => $nombre_corto_cliente,
        'idformapago' => $idformapago,
        'formapago' => $formapago,
        'costo_envio' => $delivery_costo,
        'llevapos' => $llevapos,
        'pagacon' => $pagacon,
        'vuelto' => $vuelto,
        'observacion_delivery' => $observacion_delivery,
        'observacion_operador' => $observacion_operador,
        'usa_detalle' => 'N',
        'detalle_bobo' => ''

    ];




    // convierte a formato json
    $ticket_json = json_encode($factura_cab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    return $ticket_json;


}
function factura_preimpresa($idventa)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;
    global $idusu;

    // log de impresiones
    $consulta = "
	INSERT INTO log_impresiones_ventas
	(idventa, impreso_por, impreso_el) 
	VALUES
	($idventa,$idusu,'$ahora')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "Select * from preferencias where idempresa=$idempresa";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $contado_txt = trim($rspref->fields['contado_txt']);
    $credito_txt = trim($rspref->fields['credito_txt']);
    $factura_pred = trim($rspref->fields['factura_pred']);
    $forzar_agrupacion = trim($rspref->fields['forzar_agrupacion']);
    $anteponer_moneda_fact = trim($rspref->fields['anteponer_moneda_fact']);
    $ticket_fox = trim($rspref->fields['ticket_fox']);
    if ($forzar_agrupacion == '') {
        $forzar_agrupacion = "N";
    }
    if ($factura_pred == '') {
        $factura_pred = "N";
    }
    if ($anteponer_moneda_fact == '') {
        $anteponer_moneda_fact = "N";
    }
    $agrupar_articulos = trim($rspref->fields['agrupar_items_factura']);//Indica si se imprimen las cantidades y descripciones de la venta en la factura
    $describe_factura = trim(strtoupper($rspref->fields['describe_factura']))	;//Indica el texto x defecto que se va usar si esta en uso agrupar facturas
    $maximo_items = intval($rspref->fields['max_items_factura']); //Idica cuantos articulos caben en la factura. Por mas que agrupar facturas sea si, se debe completar con la cantidad excta para rellenar los vacios (lineas)
    $max_items_factura = $maximo_items;
    $imprime_idvta = trim($rspref->fields['imprimir_idvta']);	//Imprime ek id de la vta en factura
    $imprime_idped = trim($rspref->fields['imprimir_idped']);	//Imprime ek id pedido en factura

    /*parametrizar
    CASE WHEN
        ventas.idsucursal_clie > 0
        and COALESCE((select count(idcliente) FROM sucursal_cliente where idcliente = ventas.idcliente and estado = 1),0) > 0
    THEN
        (select direccion FROM sucursal_cliente where idsucursal_clie = ventas.idsucursal_clie)
    ELSE
        cliente.direccion
    END as direccion
    */
    /*
    CASE WHEN
        ventas.idsucursal_clie > 0
        and COALESCE((select count(idcliente) FROM sucursal_cliente where idcliente = ventas.idcliente and estado = 1),0) > 1
    THEN
        (select direccion FROM sucursal_cliente where idsucursal_clie = ventas.idsucursal_clie)
    ELSE
        cliente.direccion
    END as direccion,
    */
    $consulta = "
	select *, (select count(*) from (select idprod FROM ventas_detalles where ventas_detalles.idemp = $idempresa and ventas_detalles.idventa = $idventa GROUP by idprod) as tot)  as total_detalles,

	(select documento from cliente where cliente.idcliente = ventas.idcliente) as documento,
	CASE WHEN
		idsucursal_clie is not null
	THEN
		CASE WHEN 
			(SELECT count(sucursal_cliente.idsucursal_clie) from sucursal_cliente where idcliente = ventas.idcliente and estado = 1) > 0
		THEN
			(SELECT sucursal_cliente.direccion from sucursal_cliente where idsucursal_clie = ventas.idsucursal_clie)
		ELSE
			cliente.direccion
		END
	ELSE
		cliente.direccion
	END	as direccion, ocnumero
	from ventas
	inner join cliente on cliente.idcliente = ventas.idcliente 
	where 
	ventas.idventa = $idventa
	and ventas.estado <> 6 
	limit 1
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_detalles = intval($rsv->fields['total_detalles']);
    $idpedido = intval($rsv->fields['idpedido']);
    $ocnumero = trim($rsv->fields['ocnumero']);

    $forzar_agrupacion = trim($rsv->fields['detalle_agrupado']);//Indica si se imprimen las cantidades y descripciones de la venta en la factura
    $describe_factura = trim(strtoupper($rspref->fields['describe_factura']))	;//Indica el texto x defecto que se va usar si esta en uso agrupar facturas

    if ($rsv->fields['finalizo_correcto'] == 'N') {
        echo "ANULAR VENTA";
        exit;
    }



    //CASE WHEN sum(ventas_detalles.descuento) > 0 THEN sum(subtotal)/sum(cantidad) ELSE pventa END as pventa,
    $consulta = "
	select idventadet, idprod_serial, 
	CASE WHEN 
		ventas_detalles.pchar IS NULL
	THEN
		productos.descripcion
	ELSE	
		ventas_detalles.pchar
	END as producto,  
	ventas_detalles.pchar,
	
    
	CASE WHEN sum(cantidad) > 0 THEN sum(subtotal)/sum(cantidad) ELSE pventa END as pventa,	
	
	sum(cantidad) as cantidad, 
	(sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
	 sum(subtotal) as subtotal, ventas_detalles.pchar,
	 max(idventadet) as idventadet
	from ventas_detalles 
	inner join productos on productos.idprod_serial = ventas_detalles.idprod
	where 
	ventas_detalles.idemp = $idempresa 
	and ventas_detalles.idventa = $idventa
	GROUP by idprod_serial, 
	CASE WHEN 
		ventas_detalles.pchar IS NULL
	THEN
		productos.descripcion
	ELSE	
		ventas_detalles.pchar
	END,  
	ventas_detalles.pchar,
	
	iva, barcode, ventas_detalles.pchar
	order by max(idventadet) asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // numeros a letras
    //require_once("includes/num2letra.php");
    require_once("num2letra.php");

    $descuento = floatval($rsv->fields['descneto']);
    $total_factura = $rsv->fields['total_venta'] - $descuento; // incluido el descuento
    $total_factura_txt = strtoupper(num2letras(floatval($total_factura)));
    if ($anteponer_moneda_fact == 'S') {
        $total_factura_txt = "GUARANIES ".$total_factura_txt;
    }
    // si tiene descuento
    if ($descuento > 0) {
        $total_detalles++;
    }


    // si no supera la cantidad de la factura
    if ($total_detalles <= $max_items_factura && $forzar_agrupacion == 'N') {
        while (!$rsdet->EOF) {
            $idproducto = $rsdet->fields['idprod_serial'];
            $pchar = trim($rsdet->fields['pchar']);
            $idventadet = $rsdet->fields['idventadet'];
            if ($pchar == '') {
                $consulta = "
				select idproducto, iva_porc_col, sum(monto_col) as monto_col 
				from ventas_detalles_impuesto
				where
				idventadet in (
					select idventadet 
					from ventas_detalles
					where 
					idventa = $idventa
					and idproducto = $idproducto
					and pchar is null
					)
				group by idproducto, iva_porc_col
				";
                $rsdetimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                $consulta = "
				select idproducto, iva_porc_col, sum(monto_col) as monto_col 
				from ventas_detalles_impuesto
				where
				idventadet in (
					select idventadet 
					from ventas_detalles
					where 
					idventa = $idventa
					and idventadet = $idventadet
					)
				group by idproducto, iva_porc_col
				";
                //echo $consulta;exit;
                $rsdetimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
            $factura_det_cols = [];
            while (!$rsdetimp->EOF) {
                $factura_det_cols[] = [
                    'iva_porc_col' => floatval($rsdetimp->fields['iva_porc_col']),
                    'monto_col' => $rsdetimp->fields['monto_col'],
                ];
                $rsdetimp->MoveNext();
            }

            if (trim($rsdet->fields['pchar']) == '') {
                $descripcion = limpiar_txt_fac(trim($rsdet->fields['producto']));
            } else {
                $descripcion = limpiar_txt_fac(trim($rsdet->fields['pchar']));
            }

            $factura_det[] = [
                'cantidad' => $rsdet->fields['cantidad'],
                'descripcion' => $descripcion,
                'precio_unitario' => $rsdet->fields['pventa'],
                'subtotal' => $rsdet->fields['subtotal'], //
                'iva_monto' => $rsdet->fields['iva_monto'],
                'iva_porc' => $rsdet->fields['iva'],
                'codigo_barras' => trim($rsdet->fields['barcode']),
                'codigo_producto' => $rsdet->fields['idprod_serial'],
                'detalle_cols' => $factura_det_cols
            ];
            $rsdet->MoveNext();
        }
        // si tiene descuento
        /*if($descuento > 0){
                $factura_det[]=array(
                    'cantidad' => 1,
                    'descripcion' => trim('DESCUENTO'),
                    'precio_unitario' => ($descuento)*-1,
                    'subtotal' => ($descuento)*-1, //
                    'iva_monto' => ($descuento/11)*-1,
                    'iva_porc' => 10,
                    'codigo_barras' => '',
                    'codigo_producto' => ''
                );
        }*/
    } else {
        $consulta = "
			select iva_porc_col, sum(monto_col) as monto_col 
			from ventas_detalles_impuesto
			where
			idventadet in (
				select idventadet 
				from ventas_detalles
				where 
				idventa = $idventa
				)
			group by iva_porc_col
			";
        $rsdetimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $factura_det_cols = [];
        while (!$rsdetimp->EOF) {
            $factura_det_cols[] = [
                'iva_porc_col' => floatval($rsdetimp->fields['iva_porc_col']),
                'monto_col' => $rsdetimp->fields['monto_col'],
            ];
            $rsdetimp->MoveNext();
        }


        $factura_det[] = [
            'cantidad' => 1,
            'descripcion' => trim('CONSUMISION'),
            'precio_unitario' => $total_factura,
            'subtotal' => $total_factura, //
            'iva_monto' => $total_factura / 11,
            'iva_porc' => 10,
            'codigo_barras' => '',
            'codigo_producto' => '',
            'detalle_cols' => $factura_det_cols
        ];
    }

    // temporal para ventas viejas
    $consulta = "
	select idventadetimp from ventas_detalles_impuesto where idventa = $idventa limit 1
	";
    $rsivaporcex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsivaporcex->fields['idventadetimp']) == 0) {

        // tipos de iva de la factura actual
        $consulta = "
		select  iva as iva_porc, sum(subtotal) as subtotal_poriva, (sum(subtotal)-(sum(subtotal)/(1+iva/100))) as subtotal_monto_iva,
		CASE WHEN
			iva = 10
		THEN
			$descuento
		ELSE
			0
		END as descneto10,
		CASE WHEN
			iva = 10
		THEN
			$descuento/11
		ELSE
			0
		END as descnetoiva10
		from ventas_detalles 
		where 
		idventa = $idventa
		group by iva 
		order by iva desc
		";
        //echo $consulta;
        $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //while por cada tipo de iva y totaliza metiendo en un array
        if ($total_detalles <= $max_items_factura && $forzar_agrupacion == 'N') {
            while (!$rsivaporc->EOF) {
                $factura_det_impuesto[] = [
                    'subtotal_poriva' => $rsivaporc->fields['subtotal_poriva'] - $rsivaporc->fields['descneto10'],
                    'iva_monto_total' => $rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'],
                    'iva_porc_total' => $rsivaporc->fields['iva_porc']
                ];
                $rsivaporc->MoveNext();
            }

        } else {
            $factura_det_impuesto[] = [
                'subtotal_poriva' => $total_factura, //
                'iva_monto_total' => $total_factura / 11,
                'iva_porc_total' => 10
            ];
        }

    } else { // if(intval($rsivaporcex->fields['idventadetimp']) == 0){

        $consulta = "
		select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
		from ventas_detalles_impuesto 
		where 
		idventa = $idventa
		group by iva_porc_col
		order by iva_porc_col desc
		";
        //echo $consulta;
        $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //while por cada tipo de iva y totaliza metiendo en un array
        //if($total_detalles <= $max_items_factura && $forzar_agrupacion == 'N'){
        while (!$rsivaporc->EOF) {
            $factura_det_impuesto[] = [
                'subtotal_poriva' => $rsivaporc->fields['subtotal_poriva'] - $rsivaporc->fields['descneto10'],
                'iva_monto_total' => $rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'],
                'iva_porc_total' => floatval($rsivaporc->fields['iva_porc']),
            ];
            $rsivaporc->MoveNext();
        }

        /*}else{
                $factura_det_impuesto[]=array(
                    'subtotal_poriva' => $total_factura, //
                    'iva_monto_total' => $total_factura/11,
                    'iva_porc_total' => 10
                );
        }*/

    } //if(intval($rsivaporcex->fields['idventadetimp']) == 0){


    // conversiones
    if ($rsv->fields['tipo_venta'] == 2) {
        $condicion = 'CRED';
        $contado_txt = "";
    } else {
        $condicion = 'CON';
        $credito_txt = "";
    }
    if ($total_detalles > $max_items_factura) {
        $total_detalles = 1;
    }
    if ($forzar_agrupacion == 'S') {
        $total_detalles = 1;
    }


    $carnetdiplo = trim($rsv->fields['carnet_diplomatico']);
    if ($carnetdiplo != '') {
        $ruc = $carnetdiplo;
    } else {
        $ruc = trim($rsv->fields['ruc']);
        if ($ruc == '') {
            $ruc = $rsv->fields['ruchacienda'].'-'.$rsv->fields['dv'];
        }
    }

    // busca si hay productos del regimen gastronomico
    $consulta = "
	select idventadetimp from ventas_detalles_impuesto where idventa = $idventa and idtipoiva = 4 limit 1
	";
    $rsleyenda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsleyenda->fields['idventadetimp'] > 0) {
        $leyenda = "REGIMEN S/ DECRETO 3881";
    }
    /*---------------------------------------------*/
    $dias_credito = trim($rsv->fields['obs_varios']);//viene del nuevo campo en ventas!!!

    /*---------------------------------------------*/

    $factura_cab = [
        'idventa' => $idventa,
        'idpedido' => $idpedido,
        'ruc' => trim($ruc),
        'razon_social' => limpiar_txt_fac(trim($rsv->fields['razon_social'])),
        'documento' => limpiar_txt_fac(trim($rsv->fields['documento'])),
        'direccion' => limpiar_txt_fac(trim($rsv->fields['direccion'])),
        'telefono' => trim($rsv->fields['telefono']),
        'condicion' => $condicion, // CON contado O CRED credito
        'contado_txt' => $contado_txt, // X, CON o CONTADO
        'credito_txt' => $credito_txt, // X, CRED o CREDITO
        'cuotas' => '', // 0 opcional
        'dias_credito' => $dias_credito,  // 30 60 90 opcional
        'remision' => '',  // 00100100654321 opcional
        'factura' => $rsv->fields['factura'],
        'vencimiento' => '', // 2019-06-15 opcional
        'fecha_emision' => date("Y-m-d", strtotime($rsv->fields['fecha'])), // 2019-05-17 opcional
        'orden_compra_nro' => $ocnumero,


        'muestra_fac' => 'S', // S o N
        'muestra_idv' => 'S', // S o N
        'leyenda' => $leyenda,
        'total_factura' => $total_factura,
        'total_factura_txt' => $total_factura_txt,
        'max_items_factura' => $max_items_factura, // cantidad maxima de items que entran en el cuerpo de la factura
        'total_detalles' => $total_detalles,
        'detalle_factura' => $factura_det,
        'detalle_impuesto' => $factura_det_impuesto
    ];

    //print_r($factura_cab);exit;
    // convierte a formato json
    $factura_json = json_encode($factura_cab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    //elimina caracteres no imprimibles como el control caracter
    //$factura_json=preg_replace('/[[:^print:]]/', "", $factura_json);

    return $factura_json;

}
function preticket_mesa_json($idatc)
{

    if ($idatc == 0) {
        echo "no indico el atc";
        exit;
    }

    global $conexion;

    $consulta = "
	select sum(monto) as monto, 
	(select count(*) from (select tmp_ventares.idproducto from tmp_ventares where tmp_ventares.idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idatc = $idatc) GROUP by idproducto) as tot)  as total_detalles,
	(select wifi from sucursales where sucursales.idsucu = tmp_ventares_cab.idsucursal limit 1) as wifi,
	(select chapa from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as nombre_corto_cliente,
	(select delivery_costo  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as delivery_costo,
	(select llevapos  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as llevapos,
	(select cambio  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as cambio,
	(select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as vuelto,
	(select observacion_delivery  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as observacion_delivery,
	(select observacion  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as observacion,
	(SELECT numero_mesa FROM mesas_atc inner join mesas on mesas.idmesa = mesas_atc.idmesa where idatc = $idatc) as numero_mesa,
	(SELECT salon.nombre FROM mesas_atc inner join mesas on mesas.idmesa = mesas_atc.idmesa inner JOIN salon on salon.idsalon = mesas.idsalon where idatc = $idatc) as salon,
	(SELECT usuario FROM mesas_atc inner join usuarios on mesas_atc.idmozo = usuarios.idusu where idatc = $idatc) as mozo_abrio,
	(SELECT nombre_mesa FROM mesas_atc inner join mesas on mesas.idmesa = mesas_atc.idmesa where idatc = $idatc) as nombre_mesa
	from tmp_ventares_cab 
	where 
	idatc = $idatc
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_factura = floatval($rsv->fields['monto']);
    $total_factura_txt = formatomoneda(floatval($rsv->fields['monto']), 4, 'N');
    $total_detalles = intval($rsv->fields['total_detalles']);
    $mesa = intval($rsv->fields['numero_mesa']);
    $wifi = htmlentities($rsv->fields['wifi']);
    //$nombre_corto_cliente=htmlentities($rsv->fields['nombre_corto_cliente']);
    $nombre_corto_cliente = htmlentities($rsv->fields['nombre_mesa']);
    $delivery_costo = htmlentities($rsv->fields['delivery_costo']);
    $cajero = htmlentities($rsv->fields['cajero']);
    $salon = htmlentities($rsv->fields['salon']);
    $mozo_abrio = htmlentities($rsv->fields['mozo_abrio']);

    $llevapos = htmlentities($rsv->fields['llevapos']);
    $pagacon = htmlentities($rsv->fields['cambio']);
    $vuelto = $rsv->fields['vuelto'];
    $observacion_delivery = htmlentities($rsv->fields['observacion_delivery']);
    $observacion_operador = htmlentities($rsv->fields['observacion']);


    $consulta = "
	select tmp_ventares.receta_cambiada, productos.descripcion, productos.idprod_serial, 
	sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
	(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
	(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
	from tmp_ventares 
	inner join productos on tmp_ventares.idproducto = productos.idprod_serial
	where 
	tmp_ventares.idtmpventares_cab in ( 
										select idtmpventares_cab 
										from tmp_ventares_cab 
										where
										finalizado = 'S' 
										and registrado = 'N' 
										and idatc=$idatc 
										and estado = 1 
										)
	and tmp_ventares.borrado = 'N' 
	group by descripcion, idprod_serial, receta_cambiada
	order by descripcion asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsdet->EOF) {
        $factura_det[] = [
            'cantidad' => $rsdet->fields['total'],
            'descripcion' => limpiar_txt_fac(trim($rsdet->fields['descripcion'])),
            'precio_unitario' => $rsdet->fields['totalprecio'],
            'subtotal' => $rsdet->fields['subtotal'], //
            'iva_monto' => 0,
            'iva_porc' => 0,
            'codigo_barras' => trim($rsdet->fields['barcode']),
            'codigo_producto' => $rsdet->fields['idprod_serial']
        ];
        $rsdet->MoveNext();
    }

    $ticket_cab = [
        'idventa' => 0,
        'idpedido' => 0,
        'ruc' => '',
        'razon_social' => '',
        'direccion' => '',
        'telefono' => '',
        'condicion' => 'CON', // CON contado O CRED credito
        'contado_txt' => 'X', // X, CON o CONTADO
        'credito_txt' => '', // X, CRED o CREDITO
        'cuotas' => '', // 0 opcional
        'dias_credito' => '',  // 30 60 90 opcional
        'remision' => '',  // 00100100654321 opcional
        'ticket' => 0,
        'vencimiento' => '', // 2019-06-15 opcional
        'fecha_emision' => date("Y-m-d"), // 2019-05-17 opcional
        'muestra_tk' => 'N', // S o N
        'muestra_idv' => 'N', // S o N
        'total_ticket' => $total_factura,
        'total_ticket_txt' => $total_factura_txt,
        'max_items_ticket' => 1, // cantidad maxima de items que entran en el cuerpo de la factura
        'total_detalles' => $total_detalles,
        'detalle_ticket' => $factura_det,
        'detalle_impuesto' => $factura_det_impuesto,

        'mesa' => $mesa,
        'salon' => $salon,
        'mozo' => $mozo_abrio,
        'canal' => 'MESA',
        'idcanal' => 4,
        'cajero' => 0,
        'texto_cabeza' => $texto_cabeza,
        'texto_pie' => $texto_pie,
        'wifi' => $wifi,
        'nombre_corto_cliente' => $nombre_corto_cliente,
        'idformapago' => 0,
        'formapago' => '',
        'costo_envio' => $delivery_costo,
        'llevapos' => $llevapos,
        'pagacon' => $pagacon,
        'vuelto' => $vuelto,
        'observacion_delivery' => $observacion_delivery,
        'observacion_operador' => $observacion_operador,
        'usa_detalle' => 'N',
        'detalle_bobo' => ''

    ];



    // convierte a formato json
    $ticket_json = json_encode($ticket_cab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    return $ticket_json;

}
function preticket_json($idpedido)
{

    $idpedido = intval($idpedido);
    if ($idpedido == 0) {
        echo "no indico el pedido";
        exit;
    }

    global $conexion;

    $consulta = "
	select sum(monto) as monto, 
	(select count(*) from (select tmp_ventares.idproducto from tmp_ventares where tmp_ventares.idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idtmpventares_cab = $idpedido) GROUP by idproducto) as tot)  as total_detalles,
	(select wifi from sucursales where sucursales.idsucu = tmp_ventares_cab.idsucursal limit 1) as wifi,
	(select chapa from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as nombre_corto_cliente,
	(select delivery_costo  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as delivery_costo,
	(select llevapos  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as llevapos,
	(select cambio  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as cambio,
	(select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as vuelto,
	(select observacion_delivery  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as observacion_delivery,
	(select observacion  from tmp_ventares_cab where idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab order by fechahora desc limit 1) as observacion,
	(select canal from canal where idcanal = tmp_ventares_cab.idcanal) as canal,
	(select nombres from usuarios where idusu = tmp_ventares_cab.idusu) as operador
	from tmp_ventares_cab 
	where 
	idtmpventares_cab = $idpedido
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_factura = floatval($rsv->fields['monto']);
    $total_factura_txt = formatomoneda(floatval($rsv->fields['monto']), 4, 'N');
    $total_detalles = intval($rsv->fields['total_detalles']);
    $mesa = intval($rsv->fields['numero_mesa']);
    $wifi = htmlentities($rsv->fields['wifi']);
    $nombre_corto_cliente = htmlentities($rsv->fields['nombre_corto_cliente']);
    $delivery_costo = htmlentities($rsv->fields['delivery_costo']);
    $cajero = htmlentities($rsv->fields['cajero']);
    $salon = htmlentities($rsv->fields['salon']);
    $mozo_abrio = htmlentities($rsv->fields['mozo_abrio']);
    $canal = htmlentities($rsv->fields['canal']);
    $idcanal = htmlentities($rsv->fields['idcanal']);
    $telefono = htmlentities($rsv->fields['telefono']);
    $operador = htmlentities($rsv->fields['operador']);

    $llevapos = htmlentities($rsv->fields['llevapos']);
    $pagacon = htmlentities($rsv->fields['cambio']);
    $vuelto = $rsv->fields['vuelto'];
    $observacion_delivery = htmlentities($rsv->fields['observacion_delivery']);
    $observacion_operador = htmlentities($rsv->fields['observacion']);


    $consulta = "
	select tmp_ventares.receta_cambiada, productos.descripcion, productos.idprod_serial, 
	sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
	(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
	(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
	from tmp_ventares 
	inner join productos on tmp_ventares.idproducto = productos.idprod_serial
	where 
	tmp_ventares.idtmpventares_cab in ( 
										select idtmpventares_cab 
										from tmp_ventares_cab 
										where
										finalizado = 'S' 
										and registrado = 'N' 
										and idtmpventares_cab=$idpedido 
										and estado = 1 
										)
	and tmp_ventares.borrado = 'N' 
	group by descripcion, idprod_serial, receta_cambiada
	order by descripcion asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsdet->EOF) {
        $factura_det[] = [
            'cantidad' => $rsdet->fields['total'],
            'descripcion' => limpiar_txt_fac(trim($rsdet->fields['descripcion'])),
            'precio_unitario' => $rsdet->fields['totalprecio'],
            'subtotal' => $rsdet->fields['subtotal'], //
            'iva_monto' => 0,
            'iva_porc' => 0,
            'codigo_barras' => trim($rsdet->fields['barcode']),
            'codigo_producto' => $rsdet->fields['idprod_serial']
        ];
        $rsdet->MoveNext();
    }

    $ticket_cab = [
        'idventa' => 0,
        'idpedido' => $idpedido,
        'ruc' => '',
        'razon_social' => '',
        'direccion' => '',
        'telefono' => $telefono,
        'condicion' => 'CON', // CON contado O CRED credito
        'contado_txt' => 'X', // X, CON o CONTADO
        'credito_txt' => '', // X, CRED o CREDITO
        'cuotas' => '', // 0 opcional
        'dias_credito' => '',  // 30 60 90 opcional
        'remision' => '',  // 00100100654321 opcional
        'ticket' => $idpedido,
        'vencimiento' => '', // 2019-06-15 opcional
        'fecha_emision' => date("Y-m-d"), // 2019-05-17 opcional
        'muestra_tk' => 'S', // S o N
        'muestra_idv' => 'N', // S o N
        'total_ticket' => $total_factura,
        'total_ticket_txt' => $total_factura_txt,
        'max_items_ticket' => 1, // cantidad maxima de items que entran en el cuerpo de la factura
        'total_detalles' => $total_detalles,
        'detalle_ticket' => $factura_det,
        'detalle_impuesto' => $factura_det_impuesto,

        'mesa' => $mesa,
        'salon' => $salon,
        'mozo' => $mozo_abrio,
        'canal' => $canal,
        'idcanal' => $idcanal,
        'cajero' => $operador,
        'texto_cabeza' => $texto_cabeza,
        'texto_pie' => $texto_pie,
        'wifi' => $wifi,
        'nombre_corto_cliente' => $nombre_corto_cliente,
        'idformapago' => 0,
        'formapago' => '',
        'costo_envio' => $delivery_costo,
        'llevapos' => $llevapos,
        'pagacon' => $pagacon,
        'vuelto' => $vuelto,
        'observacion_delivery' => $observacion_delivery,
        'observacion_operador' => $observacion_operador,
        'usa_detalle' => 'N',
        'detalle_bobo' => ''

    ];



    // convierte a formato json
    $ticket_json = json_encode($ticket_cab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    return $ticket_json;

}


function diplomatico_preticket($parametros_array)
{

    global $saltolinea;
    global $conexion;

    $idatc = intval($parametros_array['idatc']);
    $idtmpventares_cab = intval($parametros_array['idtmpventares_cab']);
    $diplo = strtoupper($parametros_array['diplo']);
    $idempresa = intval($parametros_array['idempresa']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $valido = "S";
    $errores = "";

    if ($idatc == 0 && $idtmpventares_cab == 0) {
        $valido = "N";
        $errores .= "- Debe indicar el pedido o el atc.".$saltolinea;
    }
    if ($diplo != 'S' && $diplo != 'N') {
        $valido = "N";
        $errores .= "- Debe indicar si es diplomatico o es una reversion.".$saltolinea;
    }
    if ($idempresa == 0) {
        $valido = "N";
        $errores .= "- Debe indicar la empresa.".$saltolinea;
    }
    if ($idsucursal == 0) {
        $valido = "N";
        $errores .= "- Debe indicar la sucursal.".$saltolinea;
    }
    if ($valido == 'S') {

        // filtros segun si es mesa o pedido
        if ($idatc > 0) {
            $whereadd = "	and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idatc = $idatc and idventa is null) ";
            $whereadd_cab = " and idatc = $idatc and idventa is null  ";

        } else {
            $whereadd = "	and idtmpventares_cab = $idtmpventares_cab and idventa is null ";
            $whereadd_cab = " and idtmpventares_cab = $idtmpventares_cab and idventa is null  ";
        }

        //transformar a diplomatico
        if ($diplo == 'S') {

            // calcula el iva
            $consulta = "
			update tmp_ventares 
			set 
			iva = (select tipoiva from productos where idprod_serial = tmp_ventares.idproducto),
			monto_iva=(subtotal-(subtotal/(1+iva/100))),
			monto_iva_unit=(precio-(precio/(1+iva/100)))
			where
			diplo is null
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // guarda los montos anteriores a ser diplomatico
            $consulta = "
			update tmp_ventares 
			set 
			precio_sindiplo = precio,
			subtotal_sindiplo = subtotal,
			iva_sindiplo = (select tipoiva from productos where idprod_serial = tmp_ventares.idproducto),
			monto_iva_unit_sindiplo = monto_iva_unit,
			monto_iva_sindiplo=monto_iva,
			iva_sindiplo=iva
			where
			diplo is null
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
			update tmp_ventares 
			set 
			diplo = 'S',
			precio = precio_sindiplo-round(monto_iva_unit,0),
			subtotal = (precio_sindiplo-round(monto_iva_unit,0))*cantidad,
			iva = 0,
			monto_iva = 0,
			monto_iva_unit = 0
			where
			diplo is null
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza cabecera de pedido
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
			idtmpventares_cab is not null
			$whereadd_cab
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza en mesas atc
            if ($idatc > 0) {
                $consulta = "
				update mesas_atc 
				set 
				diplomatico = 'S' 
				where 
				diplomatico = 'N'
				and idatc = $idatc
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }


        } // if($diplo == 'S'){

        // deshacer transformacion a diplomatico
        if ($diplo != 'S') {

            // actualiza el detalle
            $consulta = "
			update tmp_ventares
			set 
			diplo = NULL,
			precio = precio_sindiplo,
			subtotal = subtotal_sindiplo,
			iva = iva_sindiplo,
			monto_iva = monto_iva_sindiplo,
			monto_iva_unit = monto_iva_unit_sindiplo
			where
			diplo = 'S'
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza cabecera de pedido
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
			idtmpventares_cab is not null
			$whereadd_cab
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            // actualiza en mesas atc
            if ($idatc > 0) {
                $consulta = "
				update mesas_atc 
				set 
				diplomatico = 'N' 
				where 
				diplomatico = 'S'
				and idatc = $idatc
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }




        } // if($diplo != 'S'){

        $res = [
            'valido' => 'S',
            'errores' => ''
        ];


    } // if($valido == 'S'){
    if ($valido != 'S') {
        $res = [
            'valido' => 'N',
            'errores' => $errores
        ];
    }


    // devuelve la respuesta formateada
    return $res;


}
function diplomatico_ventadir($parametros_array)
{

    global $saltolinea;
    global $conexion;

    $idusu = intval($parametros_array['idusu']);
    $diplo = strtoupper($parametros_array['diplo']);
    $idempresa = 1;
    $idsucursal = intval($parametros_array['idsucursal']);
    $valido = "S";
    $errores = "";

    if ($idusu == 0) {
        $valido = "N";
        $errores .= "- Debe indicar el usuario.".$saltolinea;
    }
    if ($diplo != 'S' && $diplo != 'N') {
        $valido = "N";
        $errores .= "- Debe indicar si es diplomatico o es una reversion.".$saltolinea;
    }
    if ($idempresa == 0) {
        $valido = "N";
        $errores .= "- Debe indicar la empresa.".$saltolinea;
    }
    if ($idsucursal == 0) {
        $valido = "N";
        $errores .= "- Debe indicar la sucursal.".$saltolinea;
    }
    if ($valido == 'S') {

        // filtros
        $whereadd = "				
		and tmp_ventares.registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.borrado = 'N'
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idsucursal = $idsucursal
		and idventa is null 
		";

        //transformar a diplomatico
        if ($diplo == 'S') {




            // calcula el iva
            $consulta = "
			update tmp_ventares 
			set 
			iva = (select tipoiva from productos where idprod_serial = tmp_ventares.idproducto),
			monto_iva=(subtotal-(subtotal/(1+iva/100))),
			monto_iva_unit=(precio-(precio/(1+iva/100)))
			where
			diplo is null
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // guarda los montos anteriores a ser diplomatico
            $consulta = "
			update tmp_ventares 
			set 
			precio_sindiplo = precio,
			subtotal_sindiplo = subtotal,
			iva_sindiplo = (select tipoiva from productos where idprod_serial = tmp_ventares.idproducto),
			monto_iva_unit_sindiplo = monto_iva_unit,
			monto_iva_sindiplo=monto_iva,
			iva_sindiplo=iva
			where
			diplo is null
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
			update tmp_ventares 
			set 
			diplo = 'S',
			precio = precio_sindiplo-round(monto_iva_unit,0),
			subtotal = (precio_sindiplo-round(monto_iva_unit,0))*cantidad,
			iva = 0,
			monto_iva = 0,
			monto_iva_unit = 0
			where
			diplo is null
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza cabecera de pedido
            /*
            $consulta="
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
            idtmpventares_cab is not null
            $whereadd_cab
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            // actualiza en mesas atc
            if($idatc > 0){
                $consulta="
                update mesas_atc
                set
                diplomatico = 'S'
                where
                diplomatico = 'N'
                and idatc = $idatc
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            }
            */


        } // if($diplo == 'S'){

        // deshacer transformacion a diplomatico
        if ($diplo != 'S') {

            // actualiza el detalle
            $consulta = "
			update tmp_ventares
			set 
			diplo = NULL,
			precio = precio_sindiplo,
			subtotal = subtotal_sindiplo,
			iva = iva_sindiplo,
			monto_iva = monto_iva_sindiplo,
			monto_iva_unit = monto_iva_unit_sindiplo
			where
			diplo = 'S'
			$whereadd
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza cabecera de pedido
            /*$consulta="
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
            idtmpventares_cab is not null
            $whereadd_cab
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


            // actualiza en mesas atc
            if($idatc > 0){
                $consulta="
                update mesas_atc
                set
                diplomatico = 'N'
                where
                diplomatico = 'S'
                and idatc = $idatc
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            }*/




        } // if($diplo != 'S'){

        $res = [
            'valido' => 'S',
            'errores' => ''
        ];


    } // if($valido == 'S'){
    if ($valido != 'S') {
        $res = [
            'valido' => 'N',
            'errores' => $errores
        ];
    }


    // devuelve la respuesta formateada
    return $res;


}
function agregaespacio_tk($txt, $caracteresmax = 30, $direccion = 'der', $cortar = 'S')
{
    if (!function_exists(mb_list_encodings)) {
        return agregaespacio_tk_old($txt, $caracteresmax, $direccion, $cortar);
    } else {
        // se usa mb_strlen y mb_substr por que los otros dan problemas cuando el texto tiene caracteres especiales
        $len = mb_strlen($txt, "UTF-8");
        if ($len > 0) {
            if ($cortar == 'S') {
                if ($len > $caracteresmax) {
                    $txt = mb_substr($txt, 0, $caracteresmax, 'UTF-8');
                }
            }
            $faltan = $caracteresmax - $len;
            $espacios = "";
            for ($i = 1;$i <= $faltan;$i++) {
                $espacios = $espacios." ";
            }
            if ($direccion == 'der') {
                $txt = $espacios.$txt;
            } else {
                $txt = $txt.$espacios;
            }
        }
        return $txt;
    }
}
function agregaespacio_tk_old($txt, $caracteresmax = 30, $direccion = 'der', $cortar = 'S')
{
    $len = strlen($txt);
    if ($len > 0) {
        if ($cortar == 'S') {
            if ($len > $caracteresmax) {
                $txt = substr($txt, 0, $caracteresmax);
            }
        }
        $faltan = $caracteresmax - $len;
        $espacios = "";
        for ($i = 1;$i <= $faltan;$i++) {
            $espacios = $espacios." ";
        }
        if ($direccion == 'der') {
            $txt = $espacios.$txt;
        } else {
            $txt = $txt.$espacios;
        }
    }
    return $txt;
}
function calcular_iva($porcentaje_iva, $precio_iva_inc)
{
    $porcentaje_iva = floatval($porcentaje_iva);
    $precio_iva_inc = floatval($precio_iva_inc);
    $precio_sin_iva = floatval($precio_iva_inc / (1 + ($porcentaje_iva / 100)));
    $monto_iva = $precio_iva_inc - $precio_sin_iva;
    return $monto_iva;
}
function calcular_iva_noincluido($porcentaje_iva, $precio_sin_iva)
{
    $porcentaje_iva = floatval($porcentaje_iva);
    $precio_sin_iva = floatval($precio_sin_iva);
    $monto_iva = floatval($precio_sin_iva * ($porcentaje_iva / 100));
    return $monto_iva;
}
function calcular_iva_divisor($porcentaje_iva)
{
    // ejemplo 5% es 21 y 10% es 11
    $porcentaje_iva = floatval($porcentaje_iva);
    $divisor = (100 + $porcentaje_iva) / $porcentaje_iva;
    return $divisor;
}
/*function prorratear_descuento_impuesto($parametros_array){

    global $conexion;


    $idventa=$parametros_array['idventa'];
    $descuento=floatval($parametros_array['descuento']);

    if(intval($idventa) == 0){
        echo "- No envio el idventa.";
        exit;
    }
    if(floatval($descuento) == 0){
        echo "- No envio el descuento.";
        exit;
    }



    $disponible=$descuento;
    // no poner nunca DESC si o si debe ser ASC para que sea a favor de hacienda
    $consulta="
    select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva,
    sum(ivaml) as subtotal_monto_iva
    from ventas_detalles_impuesto
    where
    idventa = $idventa
    group by iva_porc_col
    order by iva_porc_col asc
    ";
    $rsivas=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    while(!$rsivas->EOF){
        $iva_porc=floatval($rsivas->fields['iva_porc']);
        $totaliva[$iva_porc]=$rsivas->fields['subtotal_poriva'];
        $totalporiva=$totaliva[$iva_porc];
        if($disponible > $totalporiva){
            $totalporiva_col[$iva_porc]=$totalporiva;
            $disponible=$disponible-$totalporiva;
        }else{
            $totalporiva_col[$iva_porc]=$disponible;
            $disponible=0;
        }
    $rsivas->MoveNext(); }
    // convierte a negativo
    foreach($totalporiva_col as $key => $value){
        $datosporiva_col[$key]['monto_columna']=$value*-1;
        $datosporiva_col[$key]['monto_iva']=calcular_iva($key,$value)*-1;
    }
    return $datosporiva_col;
}*/
function prorratear_descuento_impuesto($parametros_array)
{

    global $conexion;


    $idventa = $parametros_array['idventa'];
    $descuento = floatval($parametros_array['descuento']);

    if (intval($idventa) == 0) {
        echo "- No envio el idventa.";
        exit;
    }
    if (floatval($descuento) == 0) {
        echo "- No envio el descuento.";
        exit;
    }

    $consulta = "
	select sum(monto_col) as subtotal_poriva, min(iva_porc_col) as iva_min
	from ventas_detalles_impuesto 
	where 
	idventa = $idventa
	";
    $rsivastot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totalglobal = $rsivastot->fields['subtotal_poriva'];
    $iva_min = floatval($rsivastot->fields['iva_min']);
    $descuento_porc = $descuento / $totalglobal;
    // no poner nunca DESC si o si debe ser ASC para que sea a favor de hacienda
    $consulta = "
	select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, 
	sum(ivaml) as subtotal_monto_iva
	from ventas_detalles_impuesto 
	where 
	idventa = $idventa
	group by iva_porc_col
	order by iva_porc_col asc
	";
    $rsivas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsivas->EOF) {
        $iva_porc = floatval($rsivas->fields['iva_porc']);
        $totaliva[$iva_porc] = $rsivas->fields['subtotal_poriva'];
        $totalporiva = round($totaliva[$iva_porc] * $descuento_porc, 4); // 4 decimales
        $totalporiva_col[$iva_porc] = $totalporiva;
        $suma_cols += $totalporiva;
        $rsivas->MoveNext();
    }
    // si supera el monto del descuento (ajustar en el menor iva)
    if ($suma_cols > $descuento) {
        $diferencia = $suma_cols - $descuento;
        $totalporivaajustado = $totalporiva_col[$iva_min] + $diferencia;
        $totalporiva_col[$iva_min] = $totalporivaajustado;
        // si es menor al monto del descuento (ajustar en el menor iva)
    } elseif ($suma_cols < $descuento) {
        $diferencia = $descuento - $suma_cols;
        $totalporivaajustado = $totalporiva_col[$iva_min] + $diferencia;
        $totalporiva_col[$iva_min] = $totalporivaajustado;
    }
    // convierte a negativo
    foreach ($totalporiva_col as $key => $value) {
        $datosporiva_col[$key]['monto_columna'] = $value * -1;
        $datosporiva_col[$key]['monto_iva'] = calcular_iva($key, $value) * -1;
    }
    return $datosporiva_col;
}
function ticket_compra($idcompra)
{
    global $conexion;
    global $saltolinea;
    global $ahora;


    $consulta = "
	Select compras.idtran, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
	proveedores.nombre as proveedor, compras.facturacompra,
	(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
	compras.total as monto_factura, compras.ocnum, 
	(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
	(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
	registrado as registrado_el, compras.idcompra, compras.idproveedor
	from gest_depositos_compras
	inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
	inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
	inner join compras on compras.idcompra = gest_depositos_compras.idcompra
	where 
	revisado_por > 0 
	and compras.estado <> 6
	and compras.idcompra = $idcompra
	order by gest_depositos_compras.fecha_compra desc 
	limit 1
	";
    //echo $buscar;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= '           REGISTRO DE COMPRA           '.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'IDCOMPRA      : '.$idcompra.$saltolinea;
    $factura .= 'FECHA FACTURA : '.date("d/m/Y", strtotime($rs->fields['fecha_compra'])).$saltolinea;
    $factura .= 'FACTURA       : '.$rs->fields['factura_numero'].$saltolinea;
    $factura .= 'CONDICION     : '.$rs->fields['tipocompra'].$saltolinea;
    $factura .= 'PROVEEDOR     : '.$rs->fields['proveedor'].$saltolinea;
    $factura .= 'COD PROVEEDOR : '.$rs->fields['idproveedor'].$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;

    // detalle
    $consulta = "
	select * , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, 
	(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto
	from compras_detalles 
	inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
	where 
	idcompra = $idcompra
	order by insumos_lista.descripcion asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $factura .= 'Cant    Descripcion'.$saltolinea;
    $factura .= 'P.U.              P.T.             Tasa%'.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsdet->EOF) {
        $factura .= agregaespacio(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 8).agregaespacio($rsdet->fields['descripcion'], 32).$saltolinea;
        $factura .= agregaespacio(formatomoneda($rsdet->fields['costo'], 4, 'N'), 18).agregaespacio(formatomoneda($rsdet->fields['subtotal'], 4, 'N'), 17).agregaespacio(formatomoneda($rsdet->fields['iva'], 4, 'N'), 5).$saltolinea;

        $rsdet->MoveNext();
    }

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'TOTAL FACTURA    : '.formatomoneda($rs->fields['monto_factura']).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;

    $factura .= 'REGISTRADO POR   : '.$rs->fields['registrado_por'].$saltolinea;
    $factura .= 'REGISTRADO EL    : '.date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el'])).$saltolinea;

    $consulta = "
	select gest_depositos.descripcion as deposito, facturas_proveedores.iddeposito, sucursales.nombre as sucursal,
	facturas_proveedores.fecha_valida, facturas_proveedores.validado_por,
	(select usuario from usuarios where idusu = facturas_proveedores.validado_por) as usu_validado_por
	from facturas_proveedores 
	inner join gest_depositos on gest_depositos.iddeposito = facturas_proveedores.iddeposito
	inner join sucursales on sucursales.idsucu = gest_depositos.idsucursal
	where 
	idcompra = $idcompra
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($rs->fields['iddeposito'] > 0) {
        $factura .= 'ESTADO DEL STOCK : INGRESADO '.$saltolinea;
        $factura .= 'DEPOSITO         : '.$rs->fields['deposito'].' ['.$rs->fields['iddeposito'].']'.$saltolinea;
        $factura .= 'SUCURSAL         : '.$rs->fields['sucursal'].$saltolinea;
        $factura .= 'FECHA VALIDADO   : '.date("d/m/Y H:i:s", strtotime($rs->fields['fecha_valida'])).$saltolinea;
        $factura .= 'USUARIO VALIDADOR: '.$rs->fields['usu_validado_por'].$saltolinea;

    } else {
        $factura .= 'ESTADO DEL STOCK : PENDIENTE DE INGRESO'.$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'IMPRESO EL: '.date("d/m/Y H:i:s", strtotime($ahora)).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;



    return $factura;

}
function ticket_gasto($idfactura)
{
    global $conexion;
    global $saltolinea;
    global $ahora;


    $consulta = "
	select 
	facturas_proveedores.idgasto, facturas_proveedores.fecha_compra, facturas_proveedores.factura_numero,
	proveedores.nombre as proveedor, facturas_proveedores.total_factura, facturas_proveedores.id_proveedor,
	(select tipocompra from tipocompra where idtipocompra = facturas_proveedores.tipo_factura) as tipocompra,
	usuarios.usuario as registrado_por,
	facturas_proveedores.fecha_carga as registrado_el, facturas_proveedores.comentario
	from facturas_proveedores
	inner join proveedores on proveedores.idproveedor=facturas_proveedores.id_proveedor
	inner join usuarios on usuarios.idusu=facturas_proveedores.usuario_carga
	where
	facturas_proveedores.id_factura = $idfactura
	and facturas_proveedores.idgasto is not null
	and facturas_proveedores.estado <> 6
	and facturas_proveedores.estado_carga = 3
	limit 1
	";
    //echo $buscar;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= '           REGISTRO DE GASTOS           '.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'IDFACTURA     : '.$idfactura.$saltolinea;
    $factura .= 'FECHA FACTURA : '.date("d/m/Y", strtotime($rs->fields['fecha_compra'])).$saltolinea;
    $factura .= 'FACTURA       : '.$rs->fields['factura_numero'].$saltolinea;
    $factura .= 'CONDICION     : '.$rs->fields['tipocompra'].$saltolinea;
    $factura .= 'PROVEEDOR     : '.$rs->fields['proveedor'].$saltolinea;
    $factura .= 'COD PROVEEDOR : '.$rs->fields['id_proveedor'].$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;

    // detalle
    $consulta = "
	select * , facturas_proveedores_gastos.monto_gasto as costo, gastos_lista.descripcion as descripcion, 
	(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = facturas_proveedores_gastos.idconcepto) as concepto
	from facturas_proveedores_gastos 
	inner join gastos_lista on gastos_lista.idgasto = facturas_proveedores_gastos.id_gasto
	where 
	facturas_proveedores_gastos.id_factura = $idfactura
	order by gastos_lista.descripcion asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $factura .= 'Descripcion               | Monto '.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsdet->EOF) {

        $factura .= agregaespacio($rsdet->fields['descripcion'], 25).' | '.agregaespacio(formatomoneda($rsdet->fields['costo'], 4, 'N'), 12).$saltolinea;

        $rsdet->MoveNext();
    }

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'TOTAL FACTURA    : '.formatomoneda($rs->fields['total_factura']).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;

    $factura .= 'COMENTARIO       : '.$rs->fields['comentario'].$saltolinea;
    $factura .= 'REGISTRADO POR   : '.$rs->fields['registrado_por'].$saltolinea;
    $factura .= 'REGISTRADO EL    : '.date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el'])).$saltolinea;

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'IMPRESO EL: '.date("d/m/Y H:i:s", strtotime($ahora)).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;



    return $factura;

}
function abrir_url($parametros_array)
{
    $url = $parametros_array['url'];
    $postdata_array = $parametros_array['postdata'];
    $postdata = http_build_query($postdata_array);
    $parts = parse_url($url);
    $host = $parts['host'];
    $ch = curl_init();
    $header = [
        'GET /1575051 HTTP/1.1',
        "Host: {$host}",
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language:en-US,en;q=0.8',
        'Cache-Control:max-age=0',
        'Connection:keep-alive',
        'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
    ];
    $options = [
        CURLOPT_URL => $url,     // url
        CURLOPT_POST => true,     // POST
        CURLOPT_POSTFIELDS => $postdata,     // POST
        CURLOPT_HTTPHEADER => $header,     // header

        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING => "",       // handle all encodings
        //CURLOPT_USERAGENT      => "spider", // who am i
        CURLOPT_AUTOREFERER => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 0,      // 120 timeout on connect
        CURLOPT_TIMEOUT => 0,      // 120 timeout on response
        CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
        CURLOPT_FRESH_CONNECT => false,     // true para forzar el uso de una nueva conexión en lugar de usar una en caché.
    ];
    curl_setopt_array($ch, $options);
    //curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    //curl_setopt($ch, CURLOPT_POST, 1);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $result = curl_exec($ch);
    curl_close($ch);
    $res = [
        'respuesta' => $result
    ];

    return $res;
}
function fecha_dif_dias($fechainicio, $fechafinal)
{
    $start_date = new DateTime($fechainicio);
    $since_start = $start_date->diff(new DateTime($fechafinal));
    /*echo $since_start->days.' days total<br>';
    echo $since_start->y.' years<br>';
    echo $since_start->m.' months<br>';
    echo $since_start->d.' days<br>';
    echo $since_start->h.' hours<br>';
    echo $since_start->i.' minutes<br>';
    echo $since_start->s.' seconds<br>';*/
    /*
DateInterval Object
(
    [y] => 0 // year
    [m] => 0 // month
    [d] => 2 // days
    [h] => 0 // hours
    [i] => 0 // minutes
    [s] => 0 // seconds
    [invert] => 0 // positive or negative
    [days] => 2 // total no of days

)
Format: https://www.php.net/manual/es/dateinterval.format.php
    */
    $tdias = $since_start->format('%r%a');
    return $tdias;

}
function tiene_receta_prod($idinsumo)
{
    global $conexion;
    $consulta = "
	SELECT unicopkss as idobjetivo
	FROM prod_lista_objetivos
	inner join insumos_lista on insumos_lista.idinsumo = prod_lista_objetivos.idinsumo
	inner join recetas_produccion on recetas_produccion.idobjetivo = prod_lista_objetivos.unicopkss
	inner join recetas_detalles_produccion on recetas_detalles_produccion.idreceta = recetas_produccion.idreceta
	WHERE
	insumos_lista.idinsumo = $idinsumo
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs->fields['idobjetivo'] > 0) {
        $res = true;
    } else {
        $res = false;
    }

    return $res;

}

function subreceta_prod($parametros_array)
{
    global $conexion;

    $idinsumo = intval($parametros_array['idinsumo']);
    $nivel = intval($parametros_array['nivel']);
    $cod_recur = trim($parametros_array['cod_recur']);
    $idrecur_padre = trim($parametros_array['idrecur_padre']);
    $max_nivel = 10;

    if (tiene_receta_prod($idinsumo)) {
        // recorre ingredientes
        $consulta = "
		SELECT 
		recetas_detalles_produccion.idreceta, prod_lista_objetivos.idinsumo as idinsumo_padre,
		recetas_detalles_produccion.idinsumo as idinsumo_ing,
		insumos_lista.descripcion as descripcion_padre,
		(select descripcion from insumos_lista where idinsumo = recetas_detalles_produccion.idinsumo) as descripcion_ing,
		recetas_detalles_produccion.cantidad
		FROM prod_lista_objetivos
		inner join insumos_lista on insumos_lista.idinsumo = prod_lista_objetivos.idinsumo
		inner join recetas_produccion on recetas_produccion.idobjetivo = prod_lista_objetivos.unicopkss
		inner join recetas_detalles_produccion on recetas_detalles_produccion.idreceta = recetas_produccion.idreceta
		WHERE
		insumos_lista.idinsumo = $idinsumo
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // inserta ingredientes en tabla
        while (!$rs->EOF) {

            $idreceta = intval($rs->fields['idreceta']);
            $idinsumo_padre = intval($rs->fields['idinsumo_padre']);
            $idinsumo_ing = intval($rs->fields['idinsumo_ing']);
            $descripcion_padre = antixss($rs->fields['descripcion_padre']);
            $descripcion_ing = antixss($rs->fields['descripcion_ing']);
            $cantidad = floatval($rs->fields['cantidad']);
            if ($nivel == 1) {
                $idrecur_padre = antisqlinyeccion('', "int");
            } else {
                $idrecur_padre = antisqlinyeccion(trim($parametros_array['idrecur_padre']), "int");
            }


            $consulta = "
			INSERT INTO recur
			(cod_recur, idrecur_padre, nivel, idreceta, idinsumo_padre, idinsumo_ing, descripcion_padre, descripcion_ing, cantidad) 
			VALUES 
			('$cod_recur', $idrecur_padre, $nivel, $idreceta, $idinsumo_padre, $idinsumo_ing, '$descripcion_padre', '$descripcion_ing', $cantidad)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
			select max(idrecur) as idrecur_padre
			from  recur
			where 
			cod_recur = '$cod_recur'
			";
            $rsrecur = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idrecur_padre = intval($rsrecur->fields['idrecur_padre']);

            // si tiene llama a la funcion recursivamente
            if (tiene_receta_prod($idinsumo_ing)) {
                $nivel_new = $nivel + 1;
                // por seguridad limita los niveles de recursividad
                if ($nivel_new <= $max_nivel) {
                    $parametros_array_new['idinsumo'] = $idinsumo_ing;
                    $parametros_array_new['nivel'] = $nivel_new;
                    $parametros_array_new['cod_recur'] = $cod_recur;
                    $parametros_array_new['idrecur_padre'] = $idrecur_padre;

                    subreceta_prod($parametros_array_new);
                } else {
                    $consulta = "
						delete from recur where cod_recur = '$cod_recur';
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    echo "error! solo se permiten recetas hasta $max_nivel niveles de recursividad";
                    exit;
                }
            }



            $rs->MoveNext();
        }



    }



}
function recalcular_servicio_mesa($parametros_entrada)
{
    global $conexion;
    global $ahora;
    $buscar = "Select * from mesas_preferencias limit 1";
    $rsprefmesa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $usa_servicio = trim($rsprefmesa->fields['usa_servicio']);
    $porc_servicio = floatval($rsprefmesa->fields['porc_servicio']);
    $usar_iconos = trim($rsprefmesa->fields['usar_iconos']);
    $usar_cod_adm = trim($rsprefmesa->fields['usar_cod_adm']);
    $usar_cod_mozo = trim($rsprefmesa->fields['usar_cod_mozo']);
    $valido = "S";
    $errores = "";

    $idusu = intval($parametros_entrada['quien']);
    if ($idusu == 0) {
        $errores .= "Usuario no reconocido.";
    }
    $idatc = intval($parametros_entrada['idatc']);
    if ($idatc == 0) {
        $errores .= "Id atc No recibido.";
    }
    $idmesa = intval($parametros_entrada['idmesa']);
    if ($idmesa == 0) {
        $errores .= "Id mesa invalido.";
    }
    $idempresa = intval($parametros_entrada['idempresa']);
    if ($idempresa == 0) {
        $errores .= "Id empresa invalido.";
    }
    $idsucursal = intval($parametros_entrada['idsucursal']);
    if ($idsucursal == 0) {
        $errores .= "La sucursal no es valida.";
    }

    if ($errores == '') {
        if ($usa_servicio == 'S' && $porc_servicio > 0) {
            $buscar = "Select idprod_serial from mesas_servicio_cobrar where estado=1 order by ids desc limit 1";
            $rid = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idproductolo = intval($rid->fields['idprod_serial']);
            //si hay un prod del tipo servicio definido para la cobranza
            if ($idproductolo > 0) {
                //se va calcular el porc segun consumo y estipulado
                //verificamos si el producto ya existe en tmp ventares
                $buscar = "Select tmp_ventares.idproducto,productos.descripcion,tmp_ventares_cab.idatc,tmp_ventares_cab.idtmpventares_cab,
				tmp_ventares.idventatmp
				from productos
				inner join tmp_ventares on tmp_ventares.idproducto=productos.idprod_serial
				inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab=tmp_ventares.idtmpventares_cab
				where
				tmp_ventares_cab.idatc=$idatc 
				and tmp_ventares.idproducto=$idproductolo
				and tmp_ventares_cab.estado <> 6
				limit 1
				";
                $rconsul = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                //caanal 4 mesa
                if (intval($rconsul->fields['idproducto']) == 0) {
                    //debemos insertar en tmpventares
                    $buscar = "
					Select sum(tmp_ventares.subtotal) as tt 
					from tmp_ventares
					inner join tmp_ventares_cab on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
					where 
					tmp_ventares_cab.idatc=$idatc
					and tmp_ventares.borrado='N'
					and tmp_ventares.borrado_mozo='N'
					and tmp_ventares_cab.estado <> 6
					";
                    $tconsumo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $calcular = floatval($tconsumo->fields['tt']);
                    $resu = (($calcular * $porc_servicio) / 100);

                    $insertar = "
					Insert into tmp_ventares_cab 
					(ruc,razon_social,idatc,fechahora,estado,monto,idusu,idsucursal,idempresa,idcanal,idmesa,finalizado,cocinado,retirado)
					values
					('44444401-7','CONSUMIDOR FINAL',$idatc,'$ahora',1,$resu,$idusu,$idsucursal,$idempresa,4,$idmesa,'S','S','S')
					";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                    $buscar = "
					select idtmpventares_cab 
					from tmp_ventares_cab 
					where 
					fechahora='$ahora'
					and idatc=$idatc 
					order by idtmpventares_cab desc
					limit 1";
                    $rca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $id = intval($rca->fields['idtmpventares_cab']);
                    $insertar = "
					Insert into tmp_ventares 
					(idtmpventares_cab,idproducto,idmesa,cantidad,subtotal,usuario,fechahora,precio,idempresa,idsucursal,impreso_coc,finalizado,cocinado,retirado) 
					values
					($id,$idproductolo,$idmesa,1,$resu,$idusu,'$ahora',$resu,$idempresa,$idsucursal,'S','S','S','S')
					";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                } else {
                    $idcabecera = intval($rconsul->fields['idtmpventares_cab']);
                    $ppserial = intval($rconsul->fields['idproducto']);
                    $idventatmp = intval($rconsul->fields['idventatmp']);

                    $buscar = "
					Select sum(tmp_ventares.subtotal) as tt 
					from tmp_ventares
					inner join tmp_ventares_cab on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
					where 
					tmp_ventares_cab.idatc=$idatc
					and tmp_ventares.borrado='N'
					and tmp_ventares.borrado_mozo='N'
					and tmp_ventares_cab.estado <> 6
					and idproducto <> $ppserial
					";
                    $tconsumo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                    $calcular = floatval($tconsumo->fields['tt']);
                    $resu = (($calcular * $porc_servicio) / 100);

                    $update = "
					update tmp_ventares 
					set 
					subtotal=$resu,
					precio=$resu,
					fechahora='$ahora'
					where 
					idventatmp=$idventatmp
					";
                    $conexion->Execute($update) or die(errorpg($conexion, $update));





                }

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
            							tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
            							and tmp_ventares.borrado = 'N'
            							and tmp_ventares.borrado_mozo = 'N'
            							and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
            						)
            					,0)
            					
            				)
            	WHERE
            	idatc = $idatc
            	";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            } // if ($idproductolo > 0){

        }
    }
}
function ymd_to_datetimelocal($datetime)
{
    // Entrada 2021-01-01 20:30:15 // Salida "2021-05-27T10:00"
    $res = date("Y-m-d", strtotime($datetime))."T".date("H:i:s", strtotime($datetime));
    return $res;
}
function datetimelocal_to_ymd($datetimelocal)
{
    // Entrada "2021-05-27T10:00" // Salida 2021-01-01 20:30:15
    $fecha = explode("T", $datetimelocal);
    $fecha_sola = $fecha[0];
    $hora_sola = $fecha[1];
    $res = date("Y-m-d H:i:s", strtotime($fecha_sola.' '.$hora_sola));
    return $res;
}

function validar_cliente($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idempresa;



    // busca el ruc de hacienda
    $consulta = "
	select idcliente, ruc, razon_social from cliente where borrable = 'N' and estado <> 6 order by idcliente asc limit 1
	";
    $rsruc_pred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc_pred = trim($rsruc_pred->fields['ruc']);
    $razon_social_pred = trim($rsruc_pred->fields['razon_social']);
    $idcliehac = intval($rsex->fields['idcliente']);



    // validaciones basicas
    $valido = "S";
    $errores = "";

    // preferencias caja
    $consulta = "
	SELECT 
	valida_ruc
	FROM preferencias_caja 
	WHERE  
	idempresa = $idempresa 
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $valida_ruc = trim($rsprefcaj->fields['valida_ruc']);
    // si es el ruc de hacienda
    if (strtoupper(trim($parametros_array['ruc'])) == $ruc_pred) {
        $valida_ruc = 'N';
    }

    // preferencias
    $consulta = "
	SELECT 
	facturador_electronico
	FROM preferencias
	WHERE  
	idempresa = $idempresa 
	";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $facturador_electronico = trim($rspref->fields['facturador_electronico']);


    if (trim($parametros_array['ruc']) == '') {
        $valido = "N";
        $errores .= " - El campo ruc no puede estar vacio.".$saltolinea;
    }
    if (intval($parametros_array['idclientetipo']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo de cliente no puede estar vacio.".$saltolinea;
    }

    if (trim($parametros_array['razon_social']) == '') {
        $valido = "N";
        $errores .= " - El campo razon_social no puede estar vacio.".$saltolinea;
    }
    if (intval($parametros_array['idclientetipo']) != 1 && intval($parametros_array['idclientetipo']) != 2) {
        $valido = "N";
        $errores .= " - Tipo de cliente no valido.".$saltolinea;
    }
    // si es una persona
    if (intval($parametros_array['idclientetipo']) == 1) {
        if (trim($parametros_array['nombre']) == '') {
            $valido = "N";
            $errores .= " - El campo nombre no puede estar vacio.".$saltolinea;
        }
        if (trim($parametros_array['apellido']) == '') {
            $valido = "N";
            $errores .= " - El campo apellido no puede estar vacio.".$saltolinea;
        }
    }
    // si es una empresa
    if (intval($parametros_array['idclientetipo']) == 2) {
        if (trim($parametros_array['fantasia']) == '') {
            //$valido="N";
            //$errores.=" - El campo fantasia no puede estar vacio cuando es una persona juridica.".$saltolinea;
        }
    }
    $ruc_ar = explode("-", trim($parametros_array['ruc']));
    $ruc_pri = intval($ruc_ar[0]);
    $ruc_dv = intval($ruc_ar[1]);
    if ($parametros_array['ruc_especial'] != 'S') {
        if ($valida_ruc == 'S') {
            //print_r($ruc_ar);exit;
            if ($ruc_pri <= 0) {
                $errores .= "- El ruc no puede ser cero o menor.".$saltolinea;
                $valido = "N";
            }
            if (trim($ruc_ar[1]) == '') {
                $errores .= "- No se indico el digito verificador del ruc.".$saltolinea;
                $valido = "N";
            }
            if (strlen($ruc_dv) <> 1) {
                $errores .= "- El digito verificador del ruc no puede tener 2 numeros.".$saltolinea;
                $valido = "N";
            }
            if (calcular_ruc($ruc_pri) <> $ruc_dv) {
                $digitocor = calcular_ruc($ruc_pri);
                $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.".$saltolinea;
                $ruc = $ruc_pri.'-'.$digitocor;
                //echo $ruc;exit;
                $valido = "N";
            }
        } else {
            $ruc = substr(trim($parametros_array['ruc']), 0, 15);
        }
        /*if($ruc == $ruc_pred && trim($parametros_array['razon_social']) <> $razon_social_pred){
            //echo $razon_social;exit;
            $errores.="- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.".$saltolinea;
            $valido="N";
        }
        if(trim($parametros_array['ruc']) <> $ruc_pred && trim($parametros_array['razon_social']) == $razon_social_pred){
            $errores.="- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.".$saltolinea;
            $valido="N";
        }*/
    }
    $ruc = antisqlinyeccion($parametros_array['ruc'], "text");


    // validar solo si es facturador electronico
    if ($facturador_electronico == 'S') {
        /*if(trim($parametros_array['email']) == ''){
            $valido="N";
            $errores.=" - El campo email no puede estar vacio.".$saltolinea;
        }*/
    }


    // validar que el ruc no exista excepto si es el ruc generico
    $consulta = "
	select * 

	from cliente 
	where 
	ruc = $ruc 
	and estado = 1
	and borrable = 'S'
	and ruc <> '$ruc_pred'
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idcliente'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe un cliente con el ruc ingresado, editelo para evitar duplicidad.".$saltolinea;
    }

    // validar que el documento no exista
    if (intval($parametros_array['documento']) > 0) {
        $documento = antisqlinyeccion($parametros_array['documento'], "text");
        $consulta = "
		select * 
		from cliente 
		where 
		documento = $documento 
		and estado = 1
		and borrable = 'S'
		limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsex->fields['idcliente'] > 0) {
            $valido = "N";
            $errores .= " - Ya existe un cliente con el documento ingresado, editelo para evitar duplicidad.".$saltolinea;
        }
        if (solonumeros($parametros_array['documento']) != $parametros_array['documento']) {
            $valido = "N";
            $errores .= " - El campo documento debe ser numerico, no puede contener guiones, espacios u otros caracteres.".$saltolinea;
        }
    }



    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];
    return $res_array;

}

function registrar_cliente($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idempresa;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // preferencias caja
    $consulta = "
	SELECT  valida_ruc, linea_auto_creacliente, usa_maxmensual
	FROM preferencias_caja 
	WHERE  
	idempresa = $idempresa 
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $valida_ruc = trim($rsprefcaj->fields['valida_ruc']);
    $linea_auto_creacliente = floatval($rsprefcaj->fields['linea_auto_creacliente']);
    $usa_maxmensual = trim($rsprefcaj->fields['usa_maxmensual']);

    $consulta = "
	select ruc from cliente where borrable = 'N' order by idcliente asc limit 1
	";
    $rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc_gen = $rscligen->fields['ruc'];

    // recibe parametros
    $idusu = antisqlinyeccion($parametros_array['idusu'], "int");
    $idvendedor = antisqlinyeccion($parametros_array['idvendedor'], "int");
    $sexo = antisqlinyeccion($parametros_array['sexo'], "text");
    $codclie = antisqlinyeccion($parametros_array['codclie'], "int");
    $nombre = antisqlinyeccion(substr($parametros_array['nombre'], 0, 45), "text");
    $apellido = antisqlinyeccion(substr($parametros_array['apellido'], 0, 45), "text");
    $nombre_corto = antisqlinyeccion('', "text");
    $idtipdoc = antisqlinyeccion(1, "int");
    $documento = antisqlinyeccion(solonumeros(substr($parametros_array['documento'], 0, 20)), "text");
    $ruc = antisqlinyeccion(substr($parametros_array['ruc'], 0, 45), "text");
    $telefono = antisqlinyeccion(substr($parametros_array['telefono'], 0, 45), "text");
    $celular = antisqlinyeccion($parametros_array['celular'], "float");
    $email = antisqlinyeccion($parametros_array['email'], "text");
    $direccion = antisqlinyeccion(substr($parametros_array['direccion'], 0, 150), "text");
    $comentario = antisqlinyeccion($parametros_array['comentario'], "text");
    $fechanac = antisqlinyeccion($parametros_array['fechanac'], "text");
    $tipocliente = antisqlinyeccion($parametros_array['idclientetipo'], "int");
    $razon_social = antisqlinyeccion(substr($parametros_array['razon_social'], 0, 45), "text");
    $fantasia = antisqlinyeccion(substr($parametros_array['fantasia'], 0, 200), "text");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    $ruc_especial = antisqlinyeccion($parametros_array['ruc_especial'], "text");
    if ($parametros_array['ruc_especial'] == 'S') {
        $carnet_diplomatico = $ruc;
    }
    $carnet_diplomatico = antisqlinyeccion($carnet_diplomatico, 'text');
    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $idclientecat = antisqlinyeccion($parametros_array['idclientecat'], "int");
    $numero_casa = antisqlinyeccion($parametros_array['numero_casa'], "int");
    $departamento = antisqlinyeccion($parametros_array['departamento'], "int");
    $id_distrito = antisqlinyeccion($parametros_array['id_distrito'], "int");
    $idciudad = antisqlinyeccion($parametros_array['idciudad'], "int");
    $idtiporeceptor_set = antisqlinyeccion($parametros_array['idtiporeceptor_set'], "int");
    $idcobrador = antisqlinyeccion($parametros_array['idcobrtador'], "int");
    $numero_casa2 = antisqlinyeccion($parametros_array['numero_casa2'], "int");
    $departamento2 = antisqlinyeccion($parametros_array['departamento'], "int");
    $iddistrito2 = antisqlinyeccion($parametros_array['iddistrito'], "int");
    $idciudad2 = antisqlinyeccion($parametros_array['idciudad'], "int");
    $idtipooperacionset = antisqlinyeccion($parametros_array['idtipooperacionset'], "int");
    $codigoedi = antisqlinyeccion($parametros_array['codigoedi'], "int");
    $codigo_persona = antisqlinyeccion($parametros_array['codigo_persona'], "int");
    $motivo = antisqlinyeccion($parametros_array['motivo'], "text");
    $idcredito = antisqlinyeccion($parametros_array['idcredito'], "int");
    $idmoneda = antisqlinyeccion($parametros_array['idmoneda'], "int");
    $direccion2 = antisqlinyeccion(substr($parametros_array['direccion2'], 0, 150), "text");
    $dia_visita = antisqlinyeccion($parametros_array['dia_visita'], "int");
    $idcobrador = antisqlinyeccion($parametros_array['idcobrador'], "int");
    $idnaturalezapersona = antisqlinyeccion($parametros_array['idnaturalezapersona'], "int");
    $idlistaprecio = antisqlinyeccion($parametros_array['idlistaprecio'], "int");
    $limite_credito = antisqlinyeccion($parametros_array['limite_credito'], "int");
    $idcadena = antisqlinyeccion($parametros_array['idcadena'], "int");
    $permite_credito = antisqlinyeccion($parametros_array['permite_credito'], "int");


    // conversiones
    if (intval($parametros_array['idtiporeceptor_set']) == 0) {
        $idtiporeceptor_set = 1;
    }
    if ($ruc_gen == trim($parametros_array['ruc'])) {
        $idtiporeceptor_set = 2;
    }
    if ($parametros_array['ruc_especial'] == 'S') {
        $idtiporeceptor_set = 2;
    }
    if (intval($parametros_array['idtipooperacionset']) == 0) {
        $idtipooperacionset = 2;
    }



    $consulta = "
	insert into cliente
	(idempresa, idvendedor, codclie, nombre, apellido, nombre_corto, idtipdoc, documento, ruc, telefono, celular, email, direccion, comentario, fechanac, tipocliente, razon_social, fantasia,  estado,  registrado_el, registrado_por,  sucursal, diplomatico, carnet_diplomatico,
	sexo, idclientecat, numero_casa, idtiporeceptor_set, departamento, id_distrito, idciudad, idtipooperacionset,codigoedi,idcobrador, numero_casa2, departamento2, iddistrito2, idciudad2, codigo_persona, motivo, idcredito, idmoneda, direccion2, dia_visita, idnaturalezapersona, idlistaprecio,
	limite_credito, idcadena,permite_credito 
	)
	values
	($idempresa, $idvendedor, $codclie, $nombre, $apellido, $nombre_corto, $idtipdoc, $documento, $ruc, $telefono, $celular, $email, $direccion, $comentario, $fechanac, $tipocliente, $razon_social, $fantasia, $estado,  $registrado_el, $registrado_por,  $idsucursal, $ruc_especial, $carnet_diplomatico,
	$sexo, $idclientecat, $numero_casa, $idtiporeceptor_set, $departamento, $id_distrito, $idciudad, $idtipooperacionset,$codigoedi, $idcobrador, $numero_casa2, $departamento2, $iddistrito2, $idciudad2, $codigo_persona, $motivo, $idcredito, $idmoneda, $direccion2, $dia_visita, $idnaturalezapersona, $idlistaprecio, $limite_credito, $idcadena, $permite_credito	)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idcliente from cliente where registrado_por=$registrado_por order by idcliente desc limit 1
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rsmax->fields['idcliente']);

    // sucursal del cliente
    $consulta = "
	insert into sucursal_cliente
	(idcliente, sucursal, direccion, telefono, mail, estado, 
	registrado_por, registrado_el, borrado_por, borrado_el)
	SELECT 
	idcliente, 'CASA MATRIZ', cliente.direccion, NULL, NULL, cliente.estado, 
	1, '$ahora', NULL, NULL 
	FROM cliente
	where 
	idcliente not in (select sucursal_cliente.idcliente from sucursal_cliente)
	and idcliente = $idcliente
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idsucursal_clie, idcliente from sucursal_cliente where idcliente = $idcliente order by idcliente desc limit 1
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idsucursal_clie = intval($rsmax->fields['idsucursal_clie']);

    // si tiene linea automatica
    if ($linea_auto_creacliente > 0) {

        if ($usa_maxmensual != 'S') {
            $max_mensual = "99999999999";
            $linea_sobregiro = $linea_auto_creacliente;
        } else {
            $max_mensual = $linea_auto_creacliente;
            $linea_sobregiro = $linea_auto_creacliente;
        }

        $consulta = "
		UPDATE cliente 
		SET 
			permite_acredito='S',
			max_mensual = $max_mensual,
			saldo_mensual = $max_mensual,
			linea_sobregiro = $linea_sobregiro,
			saldo_sobregiro = $linea_sobregiro
		WHERE
			idcliente=$idcliente
			and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // registra en el log
        $consulta = "
		INSERT INTO clientes_lineas_log
		(idcliente, permite_acredito, max_mensual, linea_sobregiro, registrado_por, registrado_el) 
		VALUES 
		($idcliente,'S', $max_mensual, $linea_sobregiro, $idusu, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    } // if($linea_auto_creacliente > 0){

    $res_array = [
        'idcliente' => $idcliente,
        'idsucursal_clie' => $idsucursal_clie,
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res_array;
}
function texto_para_app($parametros_array)
{
    //print_r($parametros_array);
    global $saltolinea;
    global $conexion;



    $texto = $parametros_array['texto_imprime'];
    // salto de linea del pos no es lo mismo que el enter
    $texto = nl2br($texto);
    $texto = str_replace("<br />", '', $texto);
    $texto_ar = explode($saltolinea, $texto);
    //print_r($texto_ar);exit;
    $linea = [];
    // recorre cada linea
    $i = 0;
    foreach ($texto_ar as $texto) {
        $linea[$i] = $texto;
        $i++;
    }

    // conversiones
    if (trim($parametros_array['metodo']) == '') {
        // metodo por defecto si la impresora no envio algun metodo
        $consulta = "
		select metodo_app_pred
		from preferencias_caja 
		limit 1
		";
        $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $metodo_app_pred = trim($rsprefcaj->fields['metodo_app_pred']);
        if ($metodo_app_pred == '') {
            $metodo_app_pred = null;
        }
        //$parametros_array['metodo'] = 'SUNMI';
        $parametros_array['metodo'] = $metodo_app_pred;
    }

    // lista post debe ser un array de array
    $res = [
        'texto_imprime' => $linea,
        'url_redir' => $parametros_array['url_redir'],
        'gps_obtener' => $parametros_array['gps_obtener'],
        'lista_post' => [$parametros_array['lista_post']],
        'imp_url' => $parametros_array['imp_url'],
        'metodo' => $parametros_array['metodo']
    ];
    //print_r($res);
    // convierte a formato json
    $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    // devuelve la respuesta formateada
    return $respuesta;
}
function texto_para_app_mobile($parametros_array)
{
    //print_r($parametros_array);
    global $saltolinea;
    global $conexion;



    $texto_mobile = $parametros_array['texto_imprime_app'];
    // salto de linea del pos no es lo mismo que el enter



    $texto_mobile = nl2br($texto_mobile);
    $texto_mobile = str_replace("<br />", '', $texto_mobile);
    $texto_mobile_ar = explode($saltolinea, $texto_mobile);



    //print_r($texto_ar);exit;


    $linea2 = [];
    // recorre cada linea
    $i = 0;
    foreach ($texto_mobile_ar as $texto) {
        $linea2[$i] = $texto;
        $i++;
    }

    // conversiones
    if (trim($parametros_array['metodo']) == '') {
        // metodo por defecto si la impresora no envio algun metodo
        $consulta = "
		select metodo_app_pred
		from preferencias_caja 
		limit 1
		";
        $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $metodo_app_pred = trim($rsprefcaj->fields['metodo_app_pred']);
        if ($metodo_app_pred == '') {
            $metodo_app_pred = null;
        }
        //$parametros_array['metodo'] = 'SUNMI';
        $parametros_array['metodo'] = $metodo_app_pred;
    }

    // lista post debe ser un array de array
    $res = [
        'texto_imprime' => $linea2,
        'url_redir' => $parametros_array['url_redir'],
        'gps_obtener' => $parametros_array['gps_obtener'],
        'lista_post' => [$parametros_array['lista_post']],
        'imp_url' => $parametros_array['imp_url'],
        'metodo' => $parametros_array['metodo']
    ];
    //print_r($res);
    // convierte a formato json
    $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    // devuelve la respuesta formateada
    return $respuesta;
}
function buscar_cliente($parametros_array)
{

    global $conexion;

    $v_rz = trim($parametros_array['razon_social']);
    $v_ruc = trim($parametros_array['ruc']);
    $v_doc = trim($parametros_array['documento']);
    $limite = intval($parametros_array['limite']);

    // valores por defecto
    $order = "order by razon_social asc limit 20";
    $add = "";
    if ($limite == 0) {
        $limite = 20;
    }

    if ($v_rz != '') {
        $ra = antisqlinyeccion($parametros_array['razon_social'], 'text');
        $ra = str_replace("'", "", $ra);
        $len = strlen($ra);
        // armar varios likes por cada palabra
        $v_rz_ar = explode(" ", $v_rz);
        foreach ($v_rz_ar as $palabra) {
            $add .= " and razon_social like '%$palabra%' ";
        }
        $order = "
		order by 
		CASE WHEN
			substring(razon_social from 1 for $len) = '$ra'
		THEN
			0
		ELSE
			1
		END asc, 
		razon_social asc
		Limit $limite
		";
    }
    if ($v_doc != '') {
        $documento = antisqlinyeccion($parametros_array['documento'], 'int');
        $documento = intval($documento);
        $add = " and documento like '$documento%' ";
        $order = "order by razon_social asc limit $limite";
    }
    if ($v_ruc != '') {
        $ru = antisqlinyeccion($parametros_array['ruc'], 'text');
        $ru = str_replace("'", "", $ru);
        $add = " and ruc like '$ru%'";
        $order = "order by razon_social asc limit $limite";
    }
    $buscar = "
	Select 
	cliente.idcliente, cliente.razon_social, cliente.ruc, cliente.documento, 
	sucursal_cliente.idsucursal_clie, sucursal_cliente.direccion as direccion, 
	sucursal_cliente.telefono, sucursal_cliente.sucursal
	from cliente 
	inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
	where 
	cliente.estado = 1 
	and sucursal_cliente.estado = 1
	$add 
	$order
	";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcli = $rscli->RecordCount();
    $i = 1;
    while (!$rscli->EOF) {
        $clientes[$i] = [
            'idcliente' => $rscli->fields['idcliente'],
            'idsucursal_clie' => $rscli->fields['idsucursal_clie'],
            'razon_social' => $rscli->fields['razon_social'],
            'ruc' => $rscli->fields['ruc'],
            'documento' => $rscli->fields['documento'],
            'direccion' => $rscli->fields['direccion'],
            'telefono' => $rscli->fields['telefono'],
            'sucursal_cliente' => $rscli->fields['sucursal']
        ];
        $i++;
        $rscli->MoveNext();
    }


    return $clientes;
}
function csv_to_array($csv, $separador = ";")
{
    $csv_limpio = trim($csv);
    $csv_saltos = nl2br($csv_limpio);
    $lineas = explode("<br />", $csv_saltos);
    $array_res = []; // para evitar warning y error fatal en PHP 8
    $i = 1;
    // recorre las filas
    foreach ($lineas as $linea) {
        $columnas = explode($separador, trim($linea));
        $y = 1;
        // recorre las columnas
        foreach ($columnas as $columna) {
            $array_res[$i][$y] = trim($columna);
            $y++;
        }
        $i++;
    }
    return $array_res;
}
function caja_abierta_new($parametros)
{

    global $conexion;

    // datos entrada
    $idcajero = intval($parametros['idcajero']);
    $idsucursal = intval($parametros['idsucursal']);
    $idtipocaja = intval($parametros['idtipocaja']);

    // validar
    $valido = "S";
    $errores = "";

    if ($idcajero == 0) {
        $valido = "N";
        $errores .= " - No envio el codigo de cajero.<br />";
    }
    if ($idsucursal == 0) {
        $valido = "N";
        $errores .= " - No envio la sucursal.<br />";
    }
    if ($idtipocaja == 0) {
        $valido = "N";
        $errores .= " - No envio el tipo de caja.<br />";
    }

    // busca si ya tiene una caja abierta
    $consulta = "
		Select idcaja 
		from caja_super 
		where 
		estado_caja=1 
		and cajero=$idcajero 
		and sucursal = $idsucursal 
		and tipocaja = $idtipocaja 
		order by fecha desc 
		limit 1
		";
    $rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = intval($rscaj->fields['idcaja']);

    if ($idcaja == 0) {
        $valido = "N";
        $errores .= " - No hay caja abierta.<br />";
    }
    if ($valido == 'N') {
        $idcaja = 0;
    }


    $respuesta = [
        "valido" => $valido,
        "errores" => $errores,
        "idcaja" => $idcaja
    ];

    return $respuesta;
}
function ruc_hacienda($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idempresa;
    //parametros de entrada
    $ruc = trim($parametros_array['ruc']);
    $ruc_orig = trim($parametros_array['ruc']);
    $res_noexiste = trim($parametros_array['res_noexiste']);

    // quita el dv al ruc
    $vruc = intval($ruc);
    // calcula el dv del ruc
    $ruc_comp = $vruc.'-'.calcular_ruc($vruc);
    // limpia para  consultas sql
    $ruc_comp_sql = antisqlinyeccion($ruc_comp, "text");

    // parametros para validar
    $valido = "S";
    $errores = "";

    // validaciones
    if ($ruc == '') {
        $valido = "N";
        $errores .= "- El ruc no puede estar vacio.".$saltolinea;
    }



    // si todo es valido
    if ($valido == 'S') {
        // busca si es cliente
        $consulta = "select * 
		from cliente 
		inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
		where 
		cliente.estado = 1 
		and sucursal_cliente.estado = 1
		and ruc = $ruc_comp_sql
		limit 1";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rscli->fields['idcliente']);
        // si no es cliente busca en hacienda
        if ($idcliente == 0) {
            $postdata = [
                'ruc' => $ruc_comp,
            ];
            $url = 'http://ruc.servidor.com.py/ruc_ws.php?ruc='.$ruc_comp;
            $parametros_array_url = [
                'url' => $url,
                'postdata' => $postdata,
            ];
            $respuesta_url = abrir_url($parametros_array_url);
            $datos = $respuesta_url['respuesta'];
            $datos_ruc = json_decode($datos, true);

            $ruc = $datos_ruc['ruc'];
            $razon_social = $datos_ruc['razon_social'];
            $apellido_ruc = $datos_ruc['apellido_ruc'];
            $nombre_ruc = $datos_ruc['nombre_ruc'];
            $existe = $datos_ruc['existe'];
            $error = $datos_ruc['error']; // error hacienda
            $tipo_persona = $datos_ruc['tipo_persona'];
            $fuente = "HACIENDA";
            if ($existe != 'S') {

                //CLIENTE OCASIONAL
                $buscar = "
				Select ruc, razon_social, nombre, apellido
				from cliente 
				where 
				borrable = 'N' 
				and estado <> 6 
				order by idcliente asc 
				limit 1
				";
                //echo  $buscar;exit;
                $rsclioca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $fuente = 'NOEXISTE';
                // en caso que no exista en hacienda devolver el ruc generico
                if ($res_noexiste == 'RUC_GEN') {
                    $ruc = $rsclioca->fields['ruc'];
                    $razon_social = $rsclioca->fields['razon_social'];
                    $apellido_ruc == $rsclioca->fields['apellido'];
                    $nombre_ruc == $rsclioca->fields['nombre'];
                    $tipo_persona = 'FISICA';
                    $ruc_comp = $ruc; // ruc sin dv
                    // si no existe en hacienda devolver el mismo ruc enviado
                } else {
                    $ruc = '';
                    $razon_social = '';
                    $apellido_ruc = '';
                    $nombre_ruc = '';
                    $tipo_persona = 'FISICA';
                    $ruc_comp = $ruc_orig; // ruc sin dv
                }
            }
        } else { // si es cliente trae sus datos de la bd tabla de clientes

            // datos de la tabla de clientes
            $idcliente = intval($rscli->fields['idcliente']);
            $idsucursal_clie = intval($rscli->fields['idsucursal_clie']);
            $razon_social = $rscli->fields['razon_social'];
            $nombre_ruc = $rscli->fields['nombre'];
            $apellido_ruc = $rscli->fields['apellido'];
            $tipocliente = $rscli->fields['tipocliente'];
            if ($tipocliente == 1) {
                $tipo_persona = 'FISICA';
            }
            if ($tipocliente == 2) {
                $tipo_persona = 'JURIDICA';
            }
            $fuente = "CLIENTE";


        } // if($idcliente == 0){
        $documento = "";
        if (intval($ruc_comp) > 0) {
            $ruc_ar = explode("-", $ruc);
            $documento = intval($ruc_ar[0]);
        }

        // respuesta si es valido
        $res = [
        'idcliente' => $idcliente,
        'idsucursal_clie' => $idsucursal_clie,
        'ruc' => $ruc_comp,
        'documento' => $documento,
        'razon_social' => trim($razon_social),
        'apellido_ruc' => trim($apellido_ruc),
        'nombre_ruc' => trim($nombre_ruc),
        'tipo_persona' => trim($tipo_persona),
        'fuente' => $fuente,
        'valido' => $valido,
        'errores' => $errores,
        'error' => $errores // para la version vieja
        ];

    } // if($valido == 'S'){
    // si no fue valido
    if ($valido != 'S') {

        // respuesta si hay error
        $res = [
        'valido' => $valido,
        'errores' => $errores,
        'error' => $errores // para la version vieja
        ];
    }

    return $res;

}
function estado_pedidos_tiempos_valida_log($parametros_array)
{
    global $conexion;
    global $saltolinea;

    $valido = 'S';
    $errores = '';

    //parametros de entrada
    $idtmpventares_cab = trim($parametros_array['idtmpventares_cab']);
    $idestadodelivery = trim($parametros_array['idestadodelivery']);
    $fechahora_estado = trim($parametros_array['fechahora_estado']);
    $registrado_por = trim($parametros_array['registrado_por']);
    $registrado_el = trim($parametros_array['registrado_el']);

    if (intval($idtmpventares_cab) == 0) {
        $valido = 'N';
        $errores = '- No se envio el idpedido.'.$saltolinea;
    }
    if (intval($idestadodelivery) == 0) {
        $valido = 'N';
        $errores = '- No se envio el idestadodelivery.'.$saltolinea;
    }
    if (trim($fechahora_estado) == '') {
        $valido = 'N';
        $errores = '- No se envio el campo fechahora_estado.'.$saltolinea;
    }
    if (intval($registrado_por) == 0) {
        $valido = 'N';
        $errores = '- No se envio el campo registrado_por'.$saltolinea;
    }
    if (trim($registrado_el) == '') {
        $valido = 'N';
        $errores = '- No se envio el campo registrado_el'.$saltolinea;
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}
function estado_pedidos_tiempos_registra_log($parametros_array)
{
    global $conexion;
    //parametros de entrada
    $idtmpventares_cab = antisqlinyeccion($parametros_array['idtmpventares_cab'], "int");
    $idestadodelivery = antisqlinyeccion($parametros_array['idestadodelivery'], "int");
    $fechahora_estado = antisqlinyeccion($parametros_array['fechahora_estado'], "text");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");

    $consulta = "
	INSERT INTO delivery_estado_tiempos
	(idtmpventares_cab, idestadodelivery, fechahora_estado, registrado_por, registrado_el) 
	VALUES 
	($idtmpventares_cab, $idestadodelivery, $fechahora_estado, $registrado_por, $registrado_el)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select max(iddelivestiemp) as iddelivestiemp from delivery_estado_tiempos where idtmpventares_cab = $idtmpventares_cab
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddelivestiemp = $rsmax->fields['iddelivestiemp'];

    $res = [
        'iddelivestiemp' => $iddelivestiemp,
    ];

    return $res;

}
function obtener_turno($parametros_array)
{

    global $conexion;


    $hora_actual = $parametros_array['hora_actual'];
    $idsucursal = intval($parametros_array['idsucursal']);

    if (trim($hora_actual) == '') {
        echo "No se envio la hora actual";
        exit;
    }
    if (intval($idsucursal) == 0) {
        echo "No se envio la sucursal";
        exit;
    }

    // busca si hay algun turno que dentro del mismo dia que este en ese rango
    $consulta = "
	SELECT idturno, hora_desde, hora_hasta, descripcion
	FROM turnos
	WHERE 
	estado = 1 
	and  '$hora_actual' >= hora_desde
	and '$hora_actual' <= hora_hasta
	and idsucursal = $idsucursal
	";
    $rstur = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idturno_ex = intval($rstur->fields['idturno']);

    // si hay registros el turno no comienza al dia siguiente
    if ($idturno_ex > 0) {

        $idturno = $rstur->fields['idturno'];
        $hora_desde = $rstur->fields['hora_desde'];
        $hora_hasta = $rstur->fields['hora_hasta'];
        $descripcion = $rstur->fields['descripcion'];

        // si no hay registros el turno termina al dia siguiente
    } else {

        // busca el turno que comienza al dia siguiente
        $consulta = "
		SELECT idturno, hora_desde, hora_hasta, descripcion
		FROM turnos
		WHERE 
		estado = 1 
		and hora_hasta < hora_desde
		and idsucursal = $idsucursal
		order by idturno asc
		limit 1
		";
        $rstur = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $hora_desde_ds = $rstur->fields['hora_desde'];
        $hora_hasta_ds = $rstur->fields['hora_hasta'];

        // calculos
        $hora_actual_sola = explode(':', $hora_actual)[0];
        $hora_desde_sola = explode(':', $hora_desde_ds)[0];
        $hora_hasta_sola = explode(':', $hora_hasta_ds)[0];

        // puede haber 2 casos que sea el dia anterior o el dia siguiente
        if ($hora_actual_sola >= $hora_desde_sola) {
            //echo $hora_actual_sola;echo $hora_desde_sola;exit;
            //es el dia anterior
            $idturno = $rstur->fields['idturno'];
            $hora_desde = $rstur->fields['hora_desde'];
            $hora_hasta = $rstur->fields['hora_hasta'];
            $descripcion = $rstur->fields['descripcion'];

        } elseif ($hora_actual_sola <= $hora_hasta_sola) {
            //es el dia siguiente
            $idturno = $rstur->fields['idturno'];
            $hora_desde = $rstur->fields['hora_desde'];
            $hora_hasta = $rstur->fields['hora_hasta'];
            $descripcion = $rstur->fields['descripcion'];

        } else {
            $descripcion = 'SIN TURNO';
        }


    }


    $res = [
        'hora_actual' => $hora_actual,
        'idturno' => $idturno,
        'turno' => $descripcion,
        'hora_desde' => $hora_desde,
        'hora_hasta' => $hora_hasta
    ];

    return $res;

}
function parametros_url($url = '')
{
    if (trim($url) == '') {
        $url = $_SERVER['REQUEST_URI'];
    }
    $components = parse_url($url);
    parse_str($components['query'], $results);
    $parametros = [];
    // limpiar variables
    foreach ($results as $key => $value) {
        $parametros[htmlentities($key)] = htmlentities($value);
    }
    //print_r($ar);
    $res = $parametros;
    $res = http_build_query($res);
    if ($res != '') {
        $res = '?'.$res;
    }
    return $res;
}
function slot_vigente()
{

    global $conexion;
    global $ahora;

    $consulta = "
	select idslot, nombre_slot, inicio_vigencia, fin_vigencia
	from slot_firmadigital 
	where 
	estado = 1 
	and vigente = 'S' 
	and '$ahora' >= inicio_vigencia
	and '$ahora' <= fin_vigencia
	order by idslot asc
	limit 1
	";
    $rsslot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $respuesta = [
        'idslot' => $rsslot->fields['idslot'],
        'nombre_slot' => $rsslot->fields['nombre_slot'],
        'inicio_vigencia' => $rsslot->fields['inicio_vigencia'],
        'fin_vigencia' => $rsslot->fields['fin_vigencia']
    ];

    return $respuesta;
}
//////////////////////////////////
// Usados en tmp compras_add.php//
//////////////////////////////////
function fecha_sumar_dias($fechaString, $dias)
{
    $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaString); // Convierte el string en un objeto DateTime

    $fecha->modify("+$dias days"); // Suma 3 días a la fecha

    $fechaFinal = $fecha->format('Y-m-d'); // Obtiene la fecha resultante en formato string

    echo $fechaFinal;

}
function select_table_col_limit($table, $columnas, $limit)
{
    global $conexion;
    $consulta = "
	select $columnas from $table limit $limit
	";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $array = explode(",", $columnas);
    $rows = [];
    foreach ($array as $row) {
        $row = trim($row);
        $rows[trim($row)] = $rspref->fields["$row"];
    }
    return $rows;
}
function select_max_id_suma_uno($table, $id_name)
{
    global $conexion;
    $consulta = "
    select max($id_name) as $id_name from $table
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id = intval($rs->fields[$id_name]) + 1;
    $rows = [];
    if ($id == 0) {
        $id = 1;
    }
    $rows[trim($id_name)] = $id;
    return $rows;
}
function seleccionar_mayor_idtran()
{
    // busca si existe en transacciones_compras
    $rstranc = select_max_id_suma_uno("transacciones_compras", "numero");
    $idtran = $rstranc["numero"];
    // busca si existe en tmpcompras
    $rstrantmp = select_max_id_suma_uno("tmpcompras", "idtran");
    $idtran_tmp = $rstrantmp["idtran"];
    // busca si existe en compras
    $rstrancom = select_max_id_suma_uno("compras", "idtran");
    $idtran_com = $rstrancom["idtran"];

    return max($idtran, $idtran_tmp, $idtran_com);
}

function fact_num_facturas_proveedores($idproveedor)
{
    global $conexion;
    $consulta = "
		SELECT fact_num 
		FROM facturas_proveedores 
		where 
		estado <> 6
		 and id_proveedor = $idproveedor
		 order by fact_num desc limit 1
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return "001-001-".agregacero($rsfac->fields['fact_num'] + 1, 7);
}
////////////////////////////

function escapeApostrofes($cadena)
{
    // Escapar el apostrofe dentro del string utilizando barras invertidas
    $cadena = str_replace("'", "", $cadena);
    return $cadena;
}

function php_console_log($data, $comment = null)
{
    $output = '';
    if (is_string($comment)) {
        $output .= "<script>console.warn( '$comment' );";
    } elseif ($comment != null) {
        $comment == null;
    }//Si se pasa algo que no sea un string se pone a NULL para que no de problemas
    if (is_array($data)) {
        if ($comment == null) {
            $output .= "<script>console.warn( 'Array PHP:' );";
        }
        $output .= "console.log( '[" . implode(',', $data) . "]' );</script>";
    } elseif (is_object($data)) {
        $data = var_export($data, true);
        $data = explode("\n", $data);
        if ($comment == null) {
            $output .= "<script>console.warn( 'Objeto PHP:' );";
        }
        foreach ($data as $line) {
            if (trim($line)) {
                $line = addslashes($line);
                $output .= "console.log( '{$line}' );";
            }
        }
        $output .= "</script>";
    } else {
        if ($comment == null) {
            $output .= "<script>console.warn( 'Valor de variable PHP:' );";
        }
        $output .= "console.log( '$data' );</script>";
    }

    echo $output;
}
function grabar_log($texto, $tipo = null)
{
    $nomlogs = 'C:\Alfredo\logs\log.txt';
    if (strtoupper($tipo) != null) {
        unlink($nomlogs);
        $archlog = fopen($nomlogs, 'x');
    } else {
        $archlog = fopen($nomlogs, 'a');
    }
    // $fechahora = date('Y-m-d\TH:i:sP');
    // fwrite($archlog,$fechahora);
    fwrite($archlog, $texto);
    fwrite($archlog, "\n");
    fwrite($archlog, str_repeat("=", 100));
    fwrite($archlog, "\n");
    fclose($archlog);


}
