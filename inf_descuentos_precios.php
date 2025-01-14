 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "245";
require_once("includes/rsusuario.php");

//Por ahora, llenamos la tabla de registros(logs con el id de venta)



if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
    $limite = " limit 50";

} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


$motivodesc = intval($_GET['motivodesc']);
if ($motivodesc == 0) {
    $add = " ";
} else {
    $add = " and idmotivodesc=$motivodesc ";
}
//Buscamos registros existentes
$buscar = "select log_descuentos_productos.idprodserial,(select descripcion from motivos_descuentos where idmotivodesc=log_descuentos_productos.idmotivodesc) as motivo,
descuento_neto,descripcion as producto,log_descuentos_productos.cantidad,precio_normal,precio_cobrado,descuento_neto,log_descuentos_productos.registrado_el,factura,
log_descuentos_productos.idventatmp,tmp_ventares_cab.idtmpventares_cab,tmp_ventares_cab.idventa,subtotal_original,subtotal_nuevo,(select usuario from usuarios where idusu=log_descuentos_productos.registrado_por) as usuario
from log_descuentos_productos
inner join tmp_ventares on tmp_ventares.idventatmp=log_descuentos_productos.idventatmp
inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab=tmp_ventares.idtmpventares_cab
inner join productos on productos.idprod_serial=log_descuentos_productos.idprodserial
inner join ventas on ventas.idventa=tmp_ventares_cab.idventa
where date(log_descuentos_productos.registrado_el) >='$desde' and  date(log_descuentos_productos.registrado_el) <='$hasta' $add
order by log_descuentos_productos.registrado_el desc $limite";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$texiste = $rsl->RecordCount();


$buscar = "Select * from motivos_descuentos where estado=1 order by descripcion asc";
$rsmotivos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



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
    
</script>
</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">
      <br /><br />
   <div align="center"></div>
          <div class="divstd">
            <h1><strong>Descuentos en precios de venta</strong></h1>
           <form action="" method="get">
            <table width="350px">
                <tr>
                       <td><select style="height:40px;" name="motivodesc" id="motivodesc" >
                    <option value="" selected="selected">Motivo Descuento</option>
                    <?php while (!$rsmotivos->EOF) {?>
                    <option value="<?php echo $rsmotivos->fields['idmotivodesc']?>" <?php if ($_REQUEST['motivodesc'] == $rsmotivos->fields['idmotivodesc']) {?>selected="selected"<?php }?>><?php echo $rsmotivos->fields['descripcion'] ?></option>
                    <?php $rsmotivos->MoveNext();
                    }?>
                </select></td>
                    <td><input type="date" name="desde" id="desde" style="height:40px; width:90%;" value="<?php echo $desde?>" /></td>
                    <td><input type="date" name="hasta" id="hasta" style="height:40px; width:90%;" value="<?php echo $hasta?>" /></td>
                    <td><input type="submit" value="Filtrar" /></td>
                </tr>
                
            </table>
            </form>
         </div>
        <br />

        <?php if ($texiste > 0) {?>
        
                
        <table width="890" border="1" class="tablalinda2" id="tablacarrito">
  <tbody>
    <tr>
      <td width="97" bgcolor="#CCCCCC"><strong>Motivo</strong></td>
          <td width="102" height="36" bgcolor="#CCCCCC"><strong>Producto</strong></td>
          <td width="70" align="center" bgcolor="#CCCCCC"><strong>Cant.</strong></td>
          <td width="82" align="center" bgcolor="#CCCCCC"><strong>Precio Normal</strong></td>  
          <td width="79" align="center" bgcolor="#CCCCCC"><strong>Precio Cobrado</strong></td>
        <td width="83" align="center" bgcolor="#CCCCCC"><strong>Descuento Neto</strong></td>
        <td width="76" align="center" bgcolor="#CCCCCC"><strong>Fecha Desc</strong></td>
        <td width="78" align="center" bgcolor="#CCCCCC"><strong>Usuario</strong></td>
        <td width="76" align="center" bgcolor="#CCCCCC"><strong>Id Venta</strong></td>
        <td width="83" align="center" bgcolor="#CCCCCC"><strong>Factura</strong></td>
    </tr>
<?php
    $cc = 0;
            while (!$rsl->EOF) {

                $netodesc = $netodesc + floatval($rsl->fields['descuento_neto']);
                ?>
    <tr >
      <td><?php echo Capitalizar($rsl->fields['motivo']); ?></td>
           <td height="30"><?php echo Capitalizar($rsl->fields['producto']); ?></td>
         <td align="center"><?php echo formatomoneda($rsl->fields['cantidad'], 3, 'N'); ?></td>
         <td align="center"><?php echo formatomoneda($rsl->fields['precio_normal'], 3, 'N'); ?></td>
           <td align="center"><?php echo formatomoneda($rsl->fields['precio_cobrado'], 3, 'N'); ?></td>
        <td align="center"><?php echo formatomoneda($rsl->fields['descuento_neto'], 3, 'N'); ?></td>
        <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsl->fields['registrado_el'])); ?></td>
        <td align="center"><?php echo trim($rsl->fields['usuario']); ?></td>
        <td align="center"><?php echo formatomoneda($rsl->fields['idventa'], 3, 'N'); ?></td>
        <td align="center"><?php echo trim($rsl->fields['factura']); ?></td>
    </tr>
   
<?php $rsl->MoveNext();
            } ?>
 <tr>
        <td colspan="5" align="right"><strong>Total descontado:</strong></td>
        <td colspan="5" align="left"><strong><?php echo formatomoneda($netodesc, 3, 'N'); ?></strong></td>
    </tr>
  </tbody>
</table>
</form>        
<?php } else {?>
<div align="center">
    <span class="resaltarojomini">No existe registro de cambios en precios</span>
</div>
<?php }?>
          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
