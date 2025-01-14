<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
require_once("includes/rsusuario.php");

// campos busqueda
if (isset($_GET['telefono'])) {

    // recibe variables
    $telefono = antisqlinyeccion($_GET['telefono'], "int");

    $consulta = "
	select * 
	from cliente_delivery
	where
	idclientedel is not null
	and telefono = $telefono
	and estado <> 6
	";
    $rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
// campos busqueda
if (trim($_GET['nombre']) != '' && intval($_GET['telefono']) == 0) {

    //	ALTER TABLE `cliente_delivery` ADD `nomape` VARCHAR(500) NULL AFTER `apellidos`;

    //
    $consulta = "update cliente_delivery set nomape = CONCAT(nombres, ' ', apellidos) where nomape is null";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recibe variables
    $nombre = antisqlinyeccion($_GET['nombre'], "like");

    $consulta = "
	select * 
	from cliente_delivery
	where
	idclientedel is not null and estado <> 6
	and nomape like '%$nombre%'
	order by nomape asc
	";
    //echo $consulta;
    //exit;
    $rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
if (trim($_GET['ruc']) != '' && intval($_GET['telefono']) == 0) {

    $consulta = "update cliente_delivery set nomape = CONCAT(nombres, ' ', apellidos) where nomape is null";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recibe variables
    $ruc = antisqlinyeccion($_GET['ruc'], "like");

    $consulta = "
	select cliente_delivery.* 
	from cliente_delivery
	inner join cliente on cliente.idcliente = cliente_delivery.idcliente
	where
	cliente_delivery.idclientedel is not null
	and cliente.ruc like '%$ruc%' and cliente_delivery.estado <> 6
	order by cliente_delivery.nomape asc
	";
    $rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
<script>
$(document).ready(function(){
    $("#telefono").focus();
	$("#telefono").select();
});
</script>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
<br />
 				<div class="divstd">
					<span class="resaltaditomenor">Delivery</span>
				</div>

            <p align="center">&nbsp;</p>
            <p align="center">Busqueda:</p>
            <p align="center">&nbsp;</p>
            <form id="form1" name="form1" method="get" action="">
            
            <table width="800" border="1">
              <tbody>
                <tr>
                  <td>Telefono</td>
                  <td><input name="telefono" type="text" id="telefono" placeholder="Telefono" value="<?php if (intval($_GET['telefono']) > 0) {
                      echo '0'.intval($_GET['telefono']);
                  } ?>" style="height:40px; width:100%;" onchange="this.value = get_numbers(this.value)" onkeypress="return validar(event,'numero');" /></td>
                  <td>Nombre</td>
                  <td><input name="nombre" type="text" id="nombre" placeholder="Nombre" value="<?php if (htmlentities(trim($_GET['nombre'])) != '') {
                      echo htmlentities($_GET['nombre']);
                  } ?>" style="height:40px; width:100%;" /></td>
                  <td>RUC</td>
                  <td><input name="ruc" type="text" id="ruc" placeholder="RUC" value="<?php if (htmlentities(trim($_GET['ruc'])) != '') {
                      echo htmlentities($_GET['ruc']);
                  } ?>" style="height:40px; width:100%;" /></td>
                  
                </tr>
              </tbody>
            </table>
            <p align="center">
              <input type="submit" name="submit" id="submit" value="Buscar" />
			  <input type="hidden" name="MM_search" id="MM_search" value="form1" />
            </form>
            </p>
            <p align="center">&nbsp;</p>
<?php if (isset($_GET['telefono'])) {
    if ($rscab_old->fields['idclientedel'] > 0) {
        ?>
            <table width="900" border="1">
              <thead> 
                <tr>
                  <td align="center" bgcolor="#F8FFCC"><strong>Telefono</strong></td>
                  <td align="center" bgcolor="#F8FFCC"><strong>Nombre y Apellido</strong></td>
                  <td width="50" align="center" bgcolor="#F8FFCC"><strong>Editar</strong></td>
                  <td width="50" align="center" bgcolor="#F8FFCC"><strong>Seleccionar</strong></td>
                </tr>
               </thead>  
               <tbody> 
<?php while (!$rscab_old->EOF) {
    $idclientedel = $rscab_old->fields['idclientedel'];

    ?>
                <tr>
                  <td align="center">0<?php echo $rscab_old->fields['telefono']; ?></td>
                  <td align="center"><?php echo $rscab_old->fields['nombres']; ?> <?php echo $rscab_old->fields['apellidos']; ?></td>
                  <td align="center"><input type="button" name="button2" id="button2" value="Editar" onmouseup="document.location.href='delivery_clie_edita.php?id=<?php echo $idclientedel; ?>'"  /></td>
                  <td align="center"><input type="button" name="button" id="button" value="Seleccionar" onmouseup="document.location.href='delivery_pedidos_dir.php?id=<?php echo $idclientedel; ?>'" /></td>
                 </tr>
<?php $rscab_old->MoveNext();
} ?>
              </tbody>
            </table>
            <p align="center">&nbsp;</p>
<?php } else { ?> <br /> <hr /> <br />
<p align="center">* No se encontraron registros con este telefono.<a href="delivery_clie_agrega.php?tel=<?php if (intval($_GET['telefono']) > 0) {
    echo '0'.intval($_GET['telefono']);
} ?>"> [Agregar] </a></p>
 <br />
<?php } ?> 
        <?php } ?>   




          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>