<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "109";
require_once("includes/rsusuario.php");




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $zona = antisqlinyeccion(trim($_POST['zona']), 'text');
    $costoentrega = antisqlinyeccion($_POST['costoentrega'], 'float');
    $idsucu = antisqlinyeccion($_POST['idsucursal'], 'int');
    $errores .= "";
    $valido = "S";

    // valida
    if (trim($_POST['zona']) == '') {
        $valido = "N";
        $errores .= "- Debe completar la zona.<br />";
    }
    if (floatval($_POST['costoentrega']) < 0 or trim($_POST['costoentrega']) == '') {
        $valido = "N";
        $errores .= "- Debe indicar el costo de la entrega.<br />";
    }
    if (intval($_POST['idsucursal']) == 0) {
        $valido = "N";
        $errores .= "- Debe indicar la sucursal.<br />";
    }
    // valida que no exista una zona con el mismo nombre en la misma sucursal
    $consulta = " select * from gest_zonas where descripcion = $zona and idsucursal = $idsucu ";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idzona'] > 0) {
        $valido = "N";
        $errores .= "- Ya existe una zona con el mismo nombre en la sucursal seleccionada.<br />";
    }


    // actualiza
    if ($valido == "S") {

        // busca maxid zona
        $consulta = "
		select max(idzona) as proxid from gest_zonas
		";
        $rsproxid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $proxid = intval($rsproxid->fields['proxid']) + 1;


        //inserta
        $consulta = "
		INSERT INTO gest_zonas
		(idzona, descripcion, costoentrega, estado, latini, latfin, idciudad, observaciones, idempresa, idsucursal) 
		VALUES 
		($proxid,$zona,$costoentrega,1,NULL,NULL,1,NULL,$idempresa,$idsucu)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // redirecciona
        header("location: delivery_admin.php");
        exit;


    }

}





//lista de sucursales
$buscar = "
select * from sucursales 
where 
idempresa=$idempresa 
and estado = 1
order by nombre asc";
$rsfd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

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
            <br /><br />
            <p align="center"><strong>Delivery Zonas</strong></p>
            <p align="center">&nbsp;</p>
<?php if (trim($errores) != '') { ?>
<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;">
<strong>Errores:</strong> <br />
<?php echo $errores; ?>
</div><br />
<?php } ?>
            <form id="form1" name="form1" method="post" action="">
           
            <p align="center">&nbsp;</p>
            <table width="400" border="1">
              <tbody>
                <tr>
                  <td width="50%" align="right"><strong>Zona:</strong></td>
                  <td>
                  <input type="text" name="zona" id="zona" value="<?php echo antixss($_POST['zona']); ?>" /></td>
                </tr>
                <tr>
                  <td align="right"><strong>Costo Entrega:</strong></td>
                  <td>
                  <input type="text" name="costoentrega" id="costoentrega" value="<?php if (isset($_POST['costoentrega'])) {
                      echo intval($_POST['costoentrega']);
                  } ?>" /></td>
                </tr>
                <tr>
                  <td align="right"><strong>Sucursal:</strong></td>
                  <td>
                    <select name="idsucursal" id="idsucursal">
                            	<option value="0" selected="selected">Seleccionar</option>
                               <?php while (!$rsfd->EOF) {?>
                            	<option value="<?php echo $rsfd->fields['idsucu']?>" <?php if ($rsfd->fields['idsucu'] == $_POST['idsucursal']) {?>selected="selected"<?php }?>><?php echo $rsfd->fields['nombre']?></option>
                            
                            	 <?php $rsfd->MoveNext();
                               }?>
                            </select></td>
                </tr>
              </tbody>
            </table>
            <p>&nbsp;</p>
            <p align="center">&nbsp;</p>
            <p align="center"> <input type="submit" name="submit" id="submit" value="Guardar Cambios" />
              <input type="button" name="submit2" id="submit2" value="Cancelar" onmouseup="document.location.href='delivery_admin.php'" />
              <input name="MM_update" type="hidden" id="MM_update" value="form1" />
            </p>
            </form>
            <p align="center">&nbsp;</p>
           




          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>