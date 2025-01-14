<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}

$id = intval($_GET['id']);
if ($id == 0) {
    header("location: deliverys_norendidos.php");
    exit;
}

// redirecciones
if ($_GET['m'] == 'gvr') {
    $redir = "gest_ventas_resto.php";
} else {
    $redir = "delivery_norendidos.php";
}


$consulta = "
	Select *, total_cobrado as totalpend  
	from gest_pagos 
		where 
		cajero=$idusu  
		and estado=1 
		and idcaja=$idcaja 
		and rendido ='N'
		and idempresa = $idempresa
		and idpago = $id
		order by fecha desc
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    if (intval($rs->fields['idpago']) == 0) {
        $valido = "N";
        $errores .= " - El delivery que intentas rendir no existe o ya fue rendido.<br />";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update gest_pagos
		set
			rendido='S',
			fec_rendido='$ahora'
		where
			cajero=$idusu  
			and estado=1 
			and idcaja=$idcaja 
			and rendido ='N'
			and idempresa = $idempresa
			and idpago = $id
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // direcciona donde corresponda
        header("location: $redir");
        exit;
    }

}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
            
            <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="delivery_norendidos.php?desde=<?php echo $desde ?>&hasta=<?php echo $hasta ?>"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>

 				<div class="divstd">
					<span class="resaltaditomenor">Macar como Rendido</span></div>
    <hr />            <p>&nbsp;</p>
    <table width="900" border="1">
      <tbody>
        <tr align="center" bgcolor="#F8FFCC">
          <td>Fecha/Hora</td>
          <td>Cod Venta</td>
          <td>Monto</td>
        </tr>
<?php while (!$rs->EOF) {	?>
        <tr>
          <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
          <td align="center"><?php echo $rs->fields['idventa']; ?></td>
          <td align="center"><?php echo formatomoneda($rs->fields['totalpend']); ?></td>
        </tr>
<?php $rs->MoveNext();
} ?>
      </tbody>
    </table>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form1" name="form1" method="post" action="">
    <p align="center">
      <input type="button" name="Cancelar" id="Cancelar" value="Cancelar" onmouseup="document.location.href='<?php echo $redir; ?>'" />
      <input type="submit" name="button" id="button" value="Marcar como Rendido" />
      <input type="hidden" name="MM_update" value="form1" />
    </p>
    <p>&nbsp;</p>
           
</form>



	      </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>