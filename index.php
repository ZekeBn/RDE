<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$pag = "index";
require_once("includes/rsusuario.php");

// crea imagen
$img = "gfx/empresas/emp_".$id_empresa.".png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_0.png";
}

// fondo y linea por defecto
if ($fondo == '') {
    $fondo = "#FFFFFF";
}
if ($linea == '') {
    $linea = "#000000";
}


// si la pc esta asignada a una sucursal actualiza el usuario
/*if(isset($_COOKIE['csucursal']) && trim($_COOKIE['csucursal']) != ''){
    $csucursal=antisqlinyeccion($_COOKIE['csucursal'],'int');
    //echo $csucursal;
    $buscar="select * from sucursales where idempresa=$idempresa and idsucu = $csucursal order by nombre asc";
    $rspc=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $sucursal_activa=strtoupper(trim($rspc->fields['nombre']));
    $idsucuact=$rspc->fields['idsucu'];

    // si la sucursal actual es diferente de la asignada a la pc
    if($idsucursal != $idsucuact){

        if($idsucuact > 0){

            // asignar a usuario la sucursal
            $consulta="
            UPDATE usuarios
            SET
                sucursal=$idsucuact
            WHERE
                idempresa=$idempresa
                and idusu=$idusu
                and estado=1
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            // tambien en la variable de sesion
            $_SESSION['idsucursal'] = $idsucuact;

            // registra cambio
            $consulta="
            INSERT INTO asignasucu_auto
            (idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa)
            VALUES
            ($idusu,$idsucursal,$idsucuact,'$ahora',$idempresa)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            header("location: index.php?s=ok");
            exit;

        }

    }

}*/
// busca los parametros locales y actualiza usuario si son diferentes
$csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
$cfactura_suc = antisqlinyeccion($_COOKIE['cfactura_suc'], 'int');
$cfactura_pexp = antisqlinyeccion($_COOKIE['cfactura_pexp'], 'int');

if ($csucursal > 0) {

    $diferente = "N";

    // conversioens
    if (intval($cfactura_suc) == 0) {
        $cfactura_suc = 1;
    }
    if (intval($cfactura_pexp) == 0) {
        $cfactura_pexp = 1;
    }

    // busca si son diferentes al del usuario
    if ($idsucursal != $csucursal) {
        $diferente = "S";
    }
    if ($factura_suc != $cfactura_suc) {
        $diferente = "S";
    }
    if ($factura_pexp != $cfactura_pexp) {
        $diferente = "S";
    }

    if ($diferente == 'S') {
        // asignar a usuario la sucursal
        $consulta = "
		UPDATE usuarios 
		SET
			sucursal=$csucursal,
			factura_suc = $cfactura_suc,
			factura_pexp = $cfactura_pexp
		WHERE
			idempresa=$idempresa
			and idusu=$idusu
			and estado=1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // tambien en la variable de sesion
        $_SESSION['idsucursal'] = $csucursal;


        // para el log
        $idsucursal_ant = $idsucursal;
        $idsucursal_asig = $csucursal;
        $factura_suc_ant = $factura_suc;
        $factura_suc_asig = $cfactura_suc;
        $factura_pexp_ant = $factura_pexp;
        $factura_pexp_asig = $cfactura_pexp;


        // registra cambio
        $consulta = "
		INSERT INTO asignasucu_auto
		(idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa, factura_suc_ant, factura_suc_asig, factura_pexp_ant, factura_pexp_asig) 
		VALUES 
		($idusu,$idsucursal_ant,$idsucursal_asig,'$ahora',$idempresa, $factura_suc_ant, $factura_suc_asig, $factura_pexp_ant, $factura_pexp_asig)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // asigna cookie
        setcookie("csucursal", intval($idsucursal_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_suc", intval($factura_suc_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_pexp", intval($factura_pexp_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años

        header("location: index.php?s=ok");
        exit;

    }
}

if ($rsco->fields['master_franq'] == 'S') {
    header("location: index_new.php");
    exit;
}

function getBrowser($user_agent)
{



    $browser = "Desconocido";

    $browser_array = [
                            '/msie/i' => 'Internet Explorer',
                            '/firefox/i' => 'Firefox',
                            '/safari/i' => 'Safari',
                            '/chrome/i' => 'Chrome',
                            '/edge/i' => 'Edge',
                            '/opera/i' => 'Opera',
                            '/netscape/i' => 'Netscape',
                            '/maxthon/i' => 'Maxthon',
                            '/konqueror/i' => 'Konqueror',
                            '/mobile/i' => 'Dispositivo Movil'
                     ];

    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }

    return $browser;
}

// si es un celular
$user_agent = $_SERVER['HTTP_USER_AGENT'];
if (getBrowser($user_agent) == 'Dispositivo Movil') {
    header("location: index_new.php");
    exit;
}


if ($rsco->fields['index_new'] == 'S') {
    header("location: index_new.php");
    exit;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
     <div align="center" >
     <?php require_once("includes/menuarriba.php");?>
    </div>
	<div class="colcompleto" id="contenedor">


<p align="center">&nbsp;</p><br /><br />
<p align="center"><img src="<?php echo $img; ?>" height="200" style="margin:5px;"  alt="<?php echo $nombreempresa ?>" title="<?php echo $nombreempresa ?>" /></p><br /><br /><br />
<p align="center" style="font-weight:bold; font-size:16px;"><?php echo $nombreempresa ?></p>
<p align="center" style="font-weight:bold; font-size:16px;">&nbsp;</p>
<p align="center" style="font-weight:bold; font-size:16px;">&nbsp;</p>
<p align="center" style="font-weight:bold; font-size:16px;">Sucursal Activa:</p>
<p align="center" style="font-weight:bold; font-size:24px; color:#FF0000;"><?php echo strtoupper($nombresucursal)?></p>


<br /><br />
<hr /><br /><br />
<p align="center"><a href="<?php echo $rsco->fields['web_sys']; ?>" target="_blank"><img src="<?php echo $rsco->fields['logo_sys_indnew']; ?>" height="70" class="img-thumbnail" alt="<?php echo $rsco->fields['nombre_sys'] ?>"/></a></p>
<br /><br />
 	<!-- SECCION DONDE COMIENZA TODO -->
  	</div> <!-- contenedor -->
   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>