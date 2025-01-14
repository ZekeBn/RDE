<?php

// duracion de la sesion en segundos
// 8*60*60 = 8 hours
ini_set('session.gc_maxlifetime', 18000); // 5 horas  3600 = 1 hora
ini_set('session.cookie_lifetime', 18000);

if (!isset($_SESSION)) {
    session_start();
}
require_once("conexion.php");
// require_once("funciones.php");

function file_get_contents_curl($url, $postdata = "")
{
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
    //print_r($header);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;

}
// directorios superiores
$dir_antepone = "";
if ($dirsup == 'S') {
    $dir_antepone = "../";
}

// si esta iniciada la sesion
if ((isset($_SESSION['idusuario'])) && (intval($_SESSION['idusuario']) > 0) && (intval($_SESSION['idempresa']) > 0)) {


    // valida que sea el mismo navegador con el que se logueo o pudo haber robado el cookie
    if ($_SESSION['agente'] != $_SERVER['HTTP_USER_AGENT']) {
        header("location: ".$dir_antepone."logout.php");
        exit;
    }
    /* esto no conviene si la ip es dinamica
    if ($_SESSION['real'] != ip_real()){
        header("location: logout.php");
        exit;
    }*/
    $adminid = intval($_SESSION['idusuario']);
    $idusu_login = intval($_SESSION['idusuario']);

    // busca si existe el usuario
    $consulta = "
			select * from usuarios where idusu = $idusu_login
			";
    $rsusex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si existe
    if ($rsusex->fields['idusu'] > 0) {
        // valida que no este bloqueado
        if ($rsusex->fields['bloqueado'] == 'S') {
            echo "<strong>*Usuario Bloqueado!</strong><br />Su usuario fue bloqueado por muchos intentos fallidos de iniciar sesion, favor contacte con la administracion.<br /><a href=\"logout.php\">[volver]</a>";
            exit;
        }
        // valida que este activo
        if ($rsusex->fields['estado'] != 1) {
            echo "Tu usuario fue desactivado por la adeministracion. <a href=\"logout.php\">[volver]</a>";
            exit;
        }

    }

    //Buscamos si posee acceso al modulo del cual proviene el script
    $buscar = "
			Select modulo_usuario.estado,nombresub,submodulo,require_suc
			from modulo_usuario
			inner join modulo_detalle on modulo_detalle.idsubmod=modulo_usuario.submodulo
			where 
			modulo_usuario.idmodulo=$modulo 
			and modulo_usuario.submodulo=$submodulo
			and modulo_usuario.idusu=$adminid
			";
    $rsa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $estado = intval($rsa->fields['estado']);
    // si tiene permiso para el modulo actual
    if ($estado == 1) {
        //sigue, ver si tiene permisos de usar el sub
        $idempresa = $_SESSION['idempresa'];
        $idsucursal = $_SESSION['idsucursal'];
        $idusu = $adminid;
        $cajero = $_SESSION['usuariologin'];
        $actualmod = strtoupper($rsa->fields['nombresub']);
        $require_suc = $rsa->fields['require_suc'];

        //Traemos el nivel de acceso
        $buscar = "select nivel,pe,empresas.empresa,nombres,apellidos,empresas.ruc,empresas.dv,
				empresas.razon_social,sucursales.nombre as sucuchar, tipocaja,
				usuarios.super,empresas.idcliente_adm,usuarios.adm as usuadm, usuarios.idusu,
				empresas.atraso, empresas.mensaje_adm, empresas.mensaje_all, empresas.bloqueado, empresas.json_completo,
				usuarios.factura_suc, usuarios.factura_pexp, usuarios.venta_retroactiva, usuarios.idsalon_usu, usuarios.idterminal_usu,
				usuarios.sucursal as idsucursal_usu_bd,
				empresas.idfranquicia, empresas.bloqueado_fran, empresas.mensaje_fran, sucursales.estado as sucursal_estado,
				usuarios.soporte, usuarios.idterminal_obliga, usuarios.idterminal_usu,
				sucursales.idtipodesc_depo
				from usuarios 
				inner join empresas on empresas.idempresa=usuarios.idempresa 
				inner join sucursales on sucursales.idsucu=usuarios.sucursal
				where 
				idusu=$idusu 
				and usuarios.estado = 1
				";
        $rsn = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // variables facturacion
        $factura_suc = intval($rsn->fields['factura_suc']);
        $factura_pexp = intval($rsn->fields['factura_pexp']);
        $idsalon_usu = intval($rsn->fields['idsalon_usu']);
        $idterminal_usu = intval($rsn->fields['idterminal_usu']);
        $sucursal_usu_bd = intval($rsn->fields['idsucursal_usu_bd']);
        $idterminal_obliga = intval($rsn->fields['idterminal_obliga']);
        $idterminal_pc = intval($_SESSION['idterminal_usu']);
        $idtipodesc_depo = intval($rsn->fields['idtipodesc_depo']);


        // si la sucursal de la pc es diferente del usuario
        if ($idsucursal != $sucursal_usu_bd) {
            echo "Tu usuario tiene una sesion iniciada en otra sucursal. <a href='logout.php'>[Acceder]</a>";
            exit;
        }
        //print_r($_SESSION);
        // solo si se obliga terminal
        if ($idterminal_obliga > 0) {
            if ($idterminal_pc != $idterminal_obliga or $idterminal_pc == 0) {
                $consulta = "
						select terminal from terminal where idterminal = $idterminal_pc
						";
                $rster_pc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $teminal_pc = $rster_pc->fields['terminal'];
                $consulta = "
						select terminal from terminal where idterminal = $idterminal_obliga
						";
                $rster_ob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $teminal_ob = $rster_ob->fields['terminal'];
            }
            // si la pc no tiene terminal asignada
            if ($idterminal_pc == 0) {
                echo "Tu usuario solo tiene permitido acceder desde la terminal: '$teminal_ob [$idterminal_obliga]', ingrese desde la pc asignada a esa terminal. <a href='logout.php'>[Regresar]</a>";
                exit;
                // si la terminal obligada es diferente de la asignada a esta pc
            } elseif ($idterminal_pc != $idterminal_obliga) {
                echo "Estas intentando acceder a una terminal: '$teminal_pc [$idterminal_pc]' la cual es diferente a la asignada para tu usuario: '$teminal_ob [$idterminal_obliga]'. <a href='logout.php'>[Regresar]</a>";
                exit;
            }
        }


        // traemos variables que se usaran en muchos modulos
        $pe = intval($rsn->fields['pe']);
        $idusu = intval($rsn->fields['idusu']);
        $nombreempresa = strtoupper(trim($rsn->fields['empresa']));
        $nombresucursal = strtoupper(trim($rsn->fields['sucuchar']));
        $nivelacceso = intval($rsn->fields['nivel']);
        $tipocaja = trim($rsn->fields['tipocaja']);
        $superus = trim($rsn->fields['super']);
        $idcliente_adm = intval($rsn->fields['idcliente_adm']);
        $idfranquicia_adm = intval($rsn->fields['idfranquicia']);
        $bloqueado_fran = trim($rsn->fields['bloqueado_fran']);
        $mensaje_fran = trim($rsn->fields['mensaje_fran']);
        $usuadm = trim($rsn->fields['usuadm']);
        $atraso_rs = intval($rsn->fields['atraso']);
        $mensaje_adm_rs = trim($rsn->fields['mensaje_adm']);
        $mensaje_all_rs = trim($rsn->fields['mensaje_all']);
        $bloqueado_rs = trim($rsn->fields['bloqueado']);
        $json_completo = trim($rsn->fields['json_completo']);
        $venta_retroactiva = trim($rsn->fields['venta_retroactiva']);
        $sucursal_estado = intval($rsn->fields['sucursal_estado']);
        $soporte = intval($rsn->fields['soporte']);
        // mensaje licencia
        if ($usuadm == 'S') {
            $mensajelic_rs = $mensaje_adm_rs;
        } else {
            $mensajelic_rs = $mensaje_all_rs;
        }


        // si es modulo super usuario
        if ($modulo == 19 && $superus != 'S') {
            echo "Acceso Denegado.";
            exit;
        }
        // Si el usuario no esta activo
        if ($idusu == 0) {
            header("Location: ".$dir_antepone."logout.php");
            exit;
        }
        // si la sucursal esta borrada
        if ($sucursal_estado == 6 or $sucursal_estado == 0) {
            if ($asig_pag != 'S') {
                header("Location: ".$dir_antepone."asigna_sucursal_pc.php?sucex=n");
                exit;
            }
        }

        //Vemos si posee activo el sistema contable o no
        $buscar = "Select * from preferencias where idempresa=$idempresa";
        $rsco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $contable = intval($rsco->fields['contable']);
        $logo = $rsco->fields['logo'];
        $logo = str_replace("'", "", $logo);
        $subcategoria = intval($rsco->fields['subcategorias']);
        $obligacookie = $rsco->fields['obligacookie']; // si obliga cookie para abrir caja y facturar
        $RUTA_IMG_WEB = trim($rsco->fields['ruta_img_web']);
        $RUTA_IMG_WEB_DIR = trim($rsco->fields['ruta_img_web_dir']);
        $facturador_electronico = $rsco->fields['facturador_electronico'];

        // USAR HTTPS SSL si esta en preferencias como si
        if ($rsco->fields['https'] == 'S') {
            // si no se esta usando https
            if ($_SERVER['REQUEST_SCHEME'] == 'http') {
                // convierte a https
                $url_segura = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                //header("location: $url_segura");
                echo "<script> document.location.href='$url_segura'; </script>";
                exit;
            }
        }

        // si no tiene permiso para el modulo actual
    } else {
        // si la pagina no es la de inicio
        if ($pag != "index") {

            $buscar = "
					Select modulo_usuario.estado,nombresub,submodulo,require_suc,
					(select modulo from modulo where idmodulo = modulo_detalle.idmodulo) as modulo
					from modulo_usuario
					inner join modulo_detalle on modulo_detalle.idsubmod=modulo_usuario.submodulo
					where 
					modulo_usuario.idmodulo=$modulo 
					and modulo_usuario.submodulo=$submodulo
					";
            $rsnmod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            echo "<br /><br />
					<div align=\"center\" style=\"border:1px solid #000;width:400px; margin:4px auto;padding:5px;\">
                	<br /><img src=\"img/alerta1.png\"  height=\"64\" width=\"64\"/><br />
                    Su usuario no posee los permisos para accedera esta secci&oacute;n.<br />
					Modulo: ".antixss($rsnmod->fields['modulo']).".<br />
					Sub-Modulo: ".antixss($rsnmod->fields['nombresub']).".<br /><br />
                    Consulte con su administrador.<br />
                	<br /><a href=\"./index.php\" title=\"Regresar\">[INICIO]</a><br /><br />
                	</div>
					";
            exit;
            // si es la pagina de inicio
        } else {
            $idempresa = $_SESSION['idempresa'];
            $idsucursal = $_SESSION['idsucursal'];
            $idusu = $adminid;
            $cajero = $_SESSION['usuariologin'];
            $actualmod = strtoupper($rsa->fields['nombresub']);

            //Traemos el nivel de acceso
            $buscar = "select nivel,pe,empresa,sucursales.nombre as sucuchar,
					usuarios.factura_suc, usuarios.factura_pexp, usuarios.idsalon_usu, usuarios.idterminal_usu, empresas.idcliente_adm,
					empresas.idfranquicia, empresas.bloqueado_fran, empresas.mensaje_fran, usuarios.soporte,
					sucursales.idtipodesc_depo
					from usuarios 
					inner join empresas	on empresas.idempresa=usuarios.idempresa 
					inner join sucursales on sucursales.idsucu=usuarios.sucursal
					where 
					idusu=$idusu 
					and usuarios.estado = 1
					";
            $rsn = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            // variables facturacion
            $factura_suc = intval($rsn->fields['factura_suc']);
            $factura_pexp = intval($rsn->fields['factura_pexp']);
            $idsalon_usu = intval($rsn->fields['idsalon_usu']);
            $idterminal_usu = intval($rsn->fields['idterminal_usu']);
            $idtipodesc_depo = intval($rsn->fields['idtipodesc_depo']);

            $pe = intval($rsn->fields['pe']);
            $nombreempresa = strtoupper($rsn->fields['empresa']);
            $nombresucursal = strtoupper(trim($rsn->fields['sucuchar']));
            $nivelacceso = intval($rsn->fields['nivel']);
            $idcliente_adm = intval($rsn->fields['idcliente_adm']);
            $idfranquicia_adm = intval($rsn->fields['idfranquicia']);
            $bloqueado_fran = trim($rsn->fields['bloqueado_fran']);
            $mensaje_fran = trim($rsn->fields['mensaje_fran']);
            $soporte = intval($rsn->fields['soporte']);

            //Vemos si posee activo el sistema contable o no
            $buscar = "Select * from preferencias where idempresa=$idempresa";

            $rsco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $contable = intval($rsco->fields['contable']);
            $logo = $rsco->fields['logo'];
            $logo = str_replace("'", "", $logo);
            $subcategoria = intval($rsco->fields['subcategorias']);
            $facturador_electronico = $rsco->fields['facturador_electronico'];



        } //if ($pag!="index"){

    }
    /*} else {
        //proviene de un modulo especial de admin
        echo "Acceso Denegado";
        exit;

    }*/
    // si no esta iniciada la sesion sale del sistema
} else {
    header("Location: ".$dir_antepone."logout.php");
    exit;
}

// obliga cookie
if ($obligacookie == 'S') {
    if ($require_suc == 'S') {
        // si existe la cookie de sucursal
        if (isset($_COOKIE['csucursal']) && trim($_COOKIE['csucursal']) != '') {
            // busca en la bd si corresponde
            $csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
            $buscar = "
			select * 
			from sucursales 
			where 
			idempresa=$idempresa 
			and idsucu = $csucursal 
			order by nombre asc
			";
            $rspc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idsucuact = $rspc->fields['idsucu'];
            // si la sucursal actual es diferente de la asignada a la pc
            if ($idsucursal != $idsucuact) {
                $valido = "N";
            }
        } else {
            $valido = "N";
        }
        // si existe la cookie de factura sucursal
        if (isset($_COOKIE['cfactura_suc']) && trim($_COOKIE['cfactura_suc']) != '') {
            // busca en la bd si corresponde
            $cfactura_suc = antisqlinyeccion($_COOKIE['cfactura_suc'], 'int');
            // si la sucursal actual es diferente de la asignada a la pc
            if ($factura_suc != $cfactura_suc) {
                $valido = "N";
            }
        } else {
            $valido = "N";
        }
        // si existe la cookie de punto expedicion
        if (isset($_COOKIE['cfactura_pexp']) && trim($_COOKIE['cfactura_pexp']) != '') {
            // busca en la bd si corresponde
            $cfactura_pexp = antisqlinyeccion($_COOKIE['cfactura_pexp'], 'int');
            // si la sucursal actual es diferente de la asignada a la pc
            if ($factura_pexp != $cfactura_pexp) {
                $valido = "N";
            }
        } else {
            $valido = "N";
        }
        // si no es valido
        if ($valido == "N") {
            if ($nm != 'S') {
                //$submodulo="192";
                echo "Este modulo requiere que tu pc este asignada a una sucursal y punto de expedicion. <a href='asigna_sucursal_pc_auto.php'>[Asignar]</a>";
                exit;
            } else {
                $asignarpesuc = 1;
            }
        }
    }
}
// si tiene asginado cliente externo
if ($idcliente_adm > 0) {
    // apartir de las 8 AM
    if (date("H") >= 8) {
        // busca si ya se controlo la licencia hoy
        $hoy = date("Y-m-d");
        $consulta = "select * from licencia_comprueba where fecha = '$hoy' and idempresa = $idempresa order by idcomprueba desc limit 1";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si aun no se comprobo hoy
        if (intval($rs->fields['idcomprueba']) == 0) {
            $empurlenc = urlencode($nombreempresa);
            // informacion para ver que tran profundo usa el sistema y ayudar a usar lo que aun no usa
            //venta
            $consulta = "select max(fecha) as fecha from ventas where estado <> 6";
            $rsfmaxvent = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxventa = $rsfmaxvent->fields['fecha'];
            $maxventa = urlencode($maxventa);
            // compra
            $consulta = "SELECT max(fechacompra) as fecha FROM compras where estado <> 6";
            $rsfmaxcomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxcompra = $rsfmaxcomp->fields['fecha'];
            $maxcompra = urlencode($maxcompra);
            // gasto
            $consulta = "SELECT max(fecha) as fecha FROM gastos_registro where borrado = 'N'";
            $rsfmaxgast = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxgasto = $rsfmaxgast->fields['fecha'];
            $maxgasto = urlencode($maxgasto);
            // conteo
            $consulta = "SELECT max(fecha_inicio) as fecha FROM conteo where estado <> 6";
            $rsfmaxcont = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxconteo = $rsfmaxcont->fields['fecha'];
            $maxconteo = urlencode($maxconteo);
            // inventario
            $consulta = "SELECT max(fecha_inicio) as fecha FROM inventario where estado <> 6";
            $rsfmaxinv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxinventario = $rsfmaxinv->fields['fecha'];
            $maxinventario = urlencode($maxinventario);
            // produccion
            $consulta = "SELECT max(fecha_producido) as fecha FROM produccion_producido where estado <> 6";
            $rsfmaxprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxproduccion = $rsfmaxprod->fields['fecha'];
            $maxproduccion = urlencode($maxproduccion);
            // orden pago
            $consulta = "SELECT max(fecha_ordenpago) as fecha FROM orden_pago where estado <> 6";
            $rsfmaxord = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxordenpago = $rsfmaxord->fields['fecha'];
            $maxordenpago = urlencode($maxordenpago);
            // sucursales
            $consulta = "SELECT COUNT(*) as total FROM sucursales where estado <> 6";
            $rstotsuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $totsuc = $rstotsuc->fields['total'];
            $totsuc = urlencode($totsuc);

            $master_franq = $rsco->fields['master_franq'];
            $datos_de_uso = "&maxventa=$maxventa&maxcompra=$maxcompra&maxgasto=$maxgasto&maxconteo=$maxconteo&maxinventario=$maxinventario&maxproduccion=$maxproduccion&maxordenpago=$maxordenpago&master=$master_franq&sucursales_activas=$totsuc";


            $httphost = urlencode(trim($_SERVER['HTTP_HOST']));
            $urllic = "https://administracion.servidor.com.py/sys_consulta_estadocuenta_ws.php?id=$idcliente_adm&emp=$empurlenc&httphost=$httphost".$datos_de_uso;
            $json = file_get_contents_curl($urllic);
            $objar = json_decode($json, true);
            $bloquear = $objar['bloquea'];
            if (trim($json) != '') {
                if (strtoupper($bloquear) != 'N') {
                    $bloquear = 'S';
                }
            }
            $bloqueado_rs = $bloquear;
            $atraso_rs = $objar['atraso'];
            $mensaje_all_rs = $objar['mensaje_all'];
            $mensaje_adm_rs = $objar['mensaje_adm'];
            $json_completo = antisqlinyeccion($json, 'textbox');
            // mensaje licencia
            if ($usuadm == 'S') {
                $mensajelic_rs = $mensaje_adm_rs;
            } else {
                $mensajelic_rs = $mensaje_all_rs;
            }
            if ($bloqueado_rs == '') {
                $bloqueado_rs = 'N';
            }

            // registrar comprobacion
            $consulta = "
			INSERT INTO licencia_comprueba
			(idusu, fecha, fechahora, idempresa) 
			VALUES 
			($idusu,'$ahora','$ahora',$idempresa)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualizar en empresas
            $atraso_rs = antisqlinyeccion($atraso_rs, 'int');
            $consulta = "
			update empresas 
			set 
			bloqueado = '$bloqueado_rs',
			atraso = $atraso_rs,
			mensaje_adm = '$mensaje_adm_rs',
			mensaje_all = '$mensaje_all_rs',
			json_completo = $json_completo
			where
			idempresa = $idempresa
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // volver a consultar empresa
            $consulta = "
			select * 
			from empresas
			where
			idempresa = $idempresa
			";
            $rsemplic = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $atraso_rs = intval($rsemplic->fields['atraso']);
            $mensaje_adm_rs = trim($rsemplic->fields['mensaje_adm']);
            $mensaje_all_rs = trim($rsemplic->fields['mensaje_all']);
            $bloqueado_rs = trim($rsemplic->fields['bloqueado']);
            $json_completo = trim($rsemplic->fields['json_completo']);
            //$json_completo=str_replace(':NULL',':null',$json_completo);
            // mensaje licencia
            if ($usuadm == 'S') {
                $mensajelic_rs = $mensaje_adm_rs;
            } else {
                $mensajelic_rs = $mensaje_all_rs;
            }

            //si bloquear es si, mostrar mensaje de acuerdo al usuario
            if ($bloqueado_rs == 'S') {
                // si no esta en la pagina de licencia redirecciona
                if ($paglic != 'S') {
                    header("location: ".$dir_antepone."lic.php");
                    exit;
                }
            }
        } // if(intval($rs->fields['idcomprueba']) == 0){
    } // if(date("H") > 8){
} // if($idcliente_adm > 0){
// BLOQUEO DE LA MASTER  FRANQUICIA
if ($idfranquicia_adm > 0) {
    if ($bloqueado_fran == 'S') {
        if ($paglic != 'S') {
            // Si no tiene bloqueo de licencia del sistema
            if ($bloqueado_rs != 'S') {
                header("location: ".$dir_antepone."bloqueo_master.php");
                exit;
            }
        }
    }
} // if($idfranquicia_adm > 0){
// SI NO HAY LICENCIA CARGADA
if ($idcliente_adm == 0) {
    echo "Licencia no cargada.";
    exit;
}
// si esta bloqueada la licencia
if ($bloqueado_rs == 'S') {
    if ($paglic != 'S') {
        header("location: ".$dir_antepone."lic.php");
        exit;
    }
}
