<?php
require_once('includes/conexion.php');
require_once('includes/funciones.php');
//pregunta si se envio el form
if (isset($_POST['envia'])) {
    // obtiene ip real
    $ipreal = ip_real();
    // print_r($_POST);

    // $intentos=intval($_POST['envia'])+1;
    // echo $intentos;
    // variables externas
    $usuariologin = strtoupper(trim($_POST['nombre']));
    $clave = trim($_POST['clave']);


    // inicializando variables bandera
    $valido = "S";
    $errores = "";
    //echo $_POST['clave'];

    // validaciones
    if (trim($_POST['clave']) == '') {
        $errores .= "Clave no puede estar Vacia.<br /><br />";
        $valido = "N";
    }
    if (trim($_POST['nombre']) == '') {
        $errores .= "Usuario no puede estar Vacio.<br /><br />";
        $valido = "N";
    }
    // conversiones
    $clave = md5($clave);

    if ($valido == "S") {
        //Limpiar antes de acceder
        $usuariologin = antisqlinyeccion($usuariologin, 'text');
        $clave = antisqlinyeccion($clave, 'clave', 'N');
        // busca si existe en bd
        $buscar = "Select * from usuarios where usuario=$usuariologin and clave=$clave";
        $rsu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $encontrado = $rsu->RecordCount();
        // si existe
        if ($rsu->fields['idusu'] > 0) {
            // valida que no este bloqueado
            if ($rsu->fields['bloqueado'] == 'S') {
                echo "<strong>*Usuario Bloqueado!</strong><br />Su usuario fue bloqueado por muchos intentos fallidos de iniciar sesion,
				 favor contacte con la administracion.<br /><a href=\"logout.php\">[volver]</a>";
                exit;
            }
            // valida que este activo
            if (intval($rsu->fields['estado']) != 1) {
                echo "Tu usuario fue desactivado por la administracion. <a href=\"logout.php\">[volver]</a>";
                exit;
            }

        }

        // si existe en la BD y no esta bloqueado o inactivo loguear
        if ($encontrado > 0) {

            $iduserlogin = intval($rsu->fields['idusu']);
            $empresa = intval($rsu->fields['idempresa']);
            $idsucursal = intval($rsu->fields['sucursal']);


            //Comprobamos que su licencia sea valida si no, no dejamos seguir
            $buscar = "select * from empresas where idempresa=$empresa";
            $dt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tipolice = intval($dt->fields['tipo_sistema']);
            $licenciavalida = trim($dt->fields['licencia_valida']);
            if ($licenciavalida == 'N') {
                header("Location: logout.php?ef=2");
                exit;
            }
            //Validar  que posea al menos un modulo permitido
            $total = intval(consultar_permisos($iduserlogin, $empresa));
            ;
            if ($total == 0) {
                header("Location: logout.php?ef=1");
                exit;
            }
            if (!isset($_SESSION)) {
                session_start();
            }


            // si esta todo corecto

            // registra logueo
            $nombre_host = antisqlinyeccion(gethostbyaddr($_SERVER["REMOTE_ADDR"]), "text");
            $agente = antisqlinyeccion($_SERVER['HTTP_USER_AGENT'], "text");
            $idusu = $rsu->fields['idusu'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $ahora = date("Y-m-d H:i:s");
            $latitud = antisqlinyeccion(floatval($_POST['lt']), "text");
            $longitud = antisqlinyeccion(floatval($_POST['lg']), "text");
            $consulta = "
			INSERT INTO usuarios_accesos
			(idusuario, ip, ip_real, fechahora, latitud, longitud, hostname, agente)
			 VALUES
			 ($idusu,'$ip','$ipreal','$ahora',$latitud,$longitud, $nombre_host, $agente)
			 ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // inicia sesiones
            session_regenerate_id(true);
            $_SESSION['idusuario'] = $rsu->fields['idusu'];
            $_SESSION['idempresa'] = $empresa;
            $_SESSION['licencia'] = $tipolice;
            $_SESSION['idsucursal'] = $idsucursal;
            $_SESSION['agente'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['real'] = $ipreal;
            $_SESSION['usuariologin'] = $_POST['nombre'];
            if (!isset($_SESSION["timeout"])) {
                $_SESSION['timeout'] = time();
            };
            // crea cockie que expira en 1 anho
            $cempresa = md5($empresa);
            setcookie("cempresa", $cempresa, time() + (1 * 365 * 24 * 60 * 60));
            //setcookie("cempresa",$cempresa,time()-1);

            header("Location: index.php?ingreso=1");
            exit;
        } else {
            // error
            $errores .= "Usuario o Clave incorrecta.<br />";

            //busca si existe el usuario
            $buscar = "Select * from usuarios where usuario=$usuariologin";
            $rsu2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $iduserloginex = intval($rsu2->fields['idusu']);
            $ip = antisqlinyeccion($_SERVER['REMOTE_ADDR'], 'text');
            $ipreal = antisqlinyeccion($ipreal, 'text');
            $nombre_host = antisqlinyeccion(gethostbyaddr($_SERVER["REMOTE_ADDR"]), "text");
            $agente = antisqlinyeccion($_SERVER['HTTP_USER_AGENT'], "text");
            $latitud = antisqlinyeccion(floatval($_POST['lt']), "text");
            $longitud = antisqlinyeccion(floatval($_POST['lg']), "text");
            // registra el intento
            $consulta = "
			INSERT INTO intentos
			(fecha, fechahora, estado, ip, ip_real, idusuario, usuario_campo, hostname, agente, latitud, longitud) 
			VALUES
			('$ahora','$ahora','A',$ip,$ipreal,$iduserloginex,$usuariologin,$nombre_host, $agente, $latitud, $longitud)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe el usuario
            if ($iduserloginex > 0) {
                // consulta los intentos en la fecha actual
                $ahorad = date("Y-m-d");
                $consultaintentos = "
				select  count(*) as totalintentos
				from intentos
				where
				estado = 'A'
				and fecha = '$ahorad'
				and idusuario = $iduserloginex
				";
                $row_intentos = $conexion->Execute($consultaintentos) or die(errorpg($conexion, $consultaintentos));
                // si hay mas de 5 intentos fallidos en la fecha, bloquear
                if (intval($row_intentos->fields['totalintentos']) >= 5) {
                    $consultablock = "
					update usuarios set bloqueado = 'S' where idusu = $iduserloginex
					";
                    $conexion->Execute($consultablock) or die(errorpg($conexion, $consultablock));
                    echo "<strong>*Usuario Bloqueado!</strong><br />Su usuario fue bloqueado por muchos intentos fallidos de iniciar sesion, 
					favor contacte con la administracion. <br /><a href=\"logout.php\">[volver]</a><br />";
                    exit;
                }
            } // if($iduserloginex > 0){

        }// si existe en bd y esta activo if (($encontrado > 0) && ($mostrarempresa=='N')){

    } // si completo ambos campos usuario y clave

} // si se envio post

// si existe el coockie, busca para mostrar el logo
$margintop = "12%";

//if(isset($_COOKIE['cempresa']) && trim($_COOKIE['cempresa']) != ''){
//$cempresa=antisqlinyeccion($_COOKIE['cempresa'],'text');
$cempresa = 1;
//$consulta="select * from empresas where md5(idempresa)=$cempresa";
$consulta = "select * from empresas where idempresa=$cempresa";
$rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$nombre_empresa = trim($rsemp->fields['empresa']);
$id_empresa = trim($rsemp->fields['idempresa']);
$fondo = substr(trim($rsemp->fields['fondo']), 0, 7);
$linea = substr(trim($rsemp->fields['linea']), 0, 7);
$margintop = "4%";

// crea imagen
$img = "gfx/empresas/emp_".$id_empresa.".png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_0.png";
}

// fondo y linea por defecto
if ($fondo == '') {
    $fondo = "#FFF";
}
if ($linea == '') {
    $linea = "#000";
}


//}
$consulta = "
select * from preferencias limit 1
";
$rsco = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

//print_r($_SERVER);
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

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo antixss($rsco->fields['nombre_sys']); ?> | Login</title>

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="vendors/animate.css/animate.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom.min.css" rel="stylesheet">
<!-- favicon -->
<link rel="apple-touch-icon" sizes="57x57" href="favicon/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="favicon/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="favicon/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="favicon/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="favicon/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="favicon/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="favicon/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="favicon/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="favicon/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
<link rel="manifest" href="favicon/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="favicon/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">
<link rel="shortcut icon" type="image/x-icon" href="favicon/favicon.ico">
<style>
#footer{
	position: fixed;
	bottom: 0;
	width: 100%;
	height: 95px;
	padding-bottom: 16px;
	padding-top:4px;
	background:#FFF;
	/*background: rgba(0,0,0,.90);*/
	/*border-top: 2px solid #E21B35;*/
	border-top: 2px solid <?php echo $linea ?>;
	z-index:2000;
	text-align:center;
}
</style>
<!-- favicon -->
<script>
function guarda_gps(coordenadas){
	if(typeof coordenadas != 'undefined' && typeof coordenadas != null && coordenadas != ''){
		var coord = coordenadas.split(";");
		var latitud = coord[0];
		var longitud = coord[1];
		$("#lt").val(latitud);
		$("#lg").val(longitud);
		alert('Lt: '+latitud+' Lg: '+longitud);
	}else{
		alert('No se enviaron parametros. '+coordenadas);	
	}

}
function getLocation(){
	// por APP
	if(!(typeof ApiChannel === 'undefined')){
		ApiChannel.postMessage('<?php

        $parametros_array_tk = [
            'gps_obtener' => 'S', // S / N
        ];
echo texto_para_app($parametros_array_tk);

?>');
	// por NAVEGADOR
	}else{
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(showPosition);
		} else { 
		   // x.innerHTML = "Geolocation is not supported by this browser.";
		   document.getElementById('lt').value='0';
		   document.getElementById('lg').value='0';
		}
	}
}
function showPosition(position) {
	document.getElementById('lt').value=position.coords.latitude;
   	document.getElementById('lg').value=position.coords.longitude;
}
function envia_form(){
	document.getElementById('lof').submit();
}
</script>
  </head>

  <body class="login"  onload="getLocation();">
    <div>
<?php if ($id_empresa > 0) {?>
<div align="center">

    <div align="center" style="background-color:<?php echo $fondo; ?>; border-bottom:2.5px solid <?php echo $linea; ?>; padding:0px;">
    <img src="<?php echo $img; ?>" height="150" style="margin:5px;"  alt="TU LOGO" title="TU LOGO" />
     </div>
</div>
<?php } ?>
      <a class="hiddenanchor" id="signup"></a>
      <a class="hiddenanchor" id="signin"></a>

      <div class="login_wrapper">
        <div class="animate form login_form">
          <section class="login_content">
            <form id="lof" action="" method="post">
              <h1>Acceso al Sistema</h1>
        <?php if ($errores != '') { ?>
        	<span style="color:#FF0000;"><?php echo $errores; ?></span>
            <br />
        <?php } ?>
              <div>
                <input type="text" name="nombre" class="form-control" placeholder="Usuario" value="" required />
              </div>
              <div>
                <input type="password" name="clave" class="form-control" placeholder="Clave" value="" required />
              </div>

              <div>
              <button type="submit" name="ingresar" class="btn btn-default submit">Acceder</button>
               <!-- <a class="btn btn-default submit" href="#" onMouseUp="envia_form();">Acceder</a>->
                <!--<a class="reset_pass" href="#">Lost your password?</a>-->
              </div>

              <div class="clearfix"></div>

  				

                <div class="clearfix"></div>
                <br />

  
                
              			<input type="hidden" name="lt" id="lt" /><input type="hidden" name="lg" id="lg" />
            <input type="hidden" name="envia" id="envia" value="<?php echo intval($intentos); ?>" />
            </form>
            <!-- NO BORRAR, ES PARA LOS CELULARES CON PANTALLA MUY CHICA QUE LES PERMITA EL SCROLL -->
            <br /><br /><br /><br /><br /><br /><br />
            <!-- NO BORRAR, ES PARA LOS CELULARES CON PANTALLA MUY CHICA QUE LES PERMITA EL SCROLL -->
          </section>
        </div>

         </div>
      </div>
    </div>
<div id="footer"><a href="<?php echo $rsco->fields['web_sys']; ?>" target="_blank"><img src="<?php echo $rsco->fields['logo_sys_indnew']; ?>" height="80"  alt="<?php echo $rsco->fields['nombre_sys'] ?>"/></a></div>
  </body>
</html>