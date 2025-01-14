<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "109";
require_once("includes/rsusuario.php");


$consulta = "
Select idzona,descripcion,costoentrega, sucursales.nombre as sucursal
from gest_zonas
inner join sucursales on sucursales.idsucu = gest_zonas.idsucursal
where 
gest_zonas.estado=1 
and gest_zonas.idempresa = $idempresa 
order by descripcion asc
";
$rszonas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select * 
from tmp_ventares_cab
where 
	idtmpventares_cab in (
		select idtmpventares_cab from (select telefono, idtmpventares_cab 
		from tmp_ventares_cab 
		where
		idsucursal = $idsucursal
		and idempresa = $idempresa
		and finalizado = 'S'
		and registrado = 'S'
		group by telefono
		order by idtmpventares_cab desc
		limit 50) as utltel
	)
and idsucursal = $idsucursal
and idempresa = $idempresa
order by idtmpventares_cab desc
limit 50
";
$rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$zona = intval($rscab_old->fields['delivery_zona']);

if (intval($_GET['telefono']) > 0) {
    $tel = intval($_GET['telefono']);
    if ($_GET['nocobrado'] == 'S') {
        $add = "
		and registrado = 'S'
		";
    }
    $consulta = "
	select * 
	from tmp_ventares_cab 
	where
	idsucursal = $idsucursal
	and idempresa = $idempresa
	and finalizado = 'S'
	$add
	and telefono = $tel
	order by idtmpventares_cab desc
	limit 1
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
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
            <br /><br />
            <p align="center"><strong>Delivery Zonas</strong></p>
            <p align="center">&nbsp;</p>
            <p align="center"><a href="delivery_agrega_zona.php">[Agregar]</a></p>
            <p align="center">&nbsp;</p>
            <p align="center">&nbsp;</p>
    
    	<table width="900" border="1">
    	  <tbody>
    	    <tr>
    	      <td align="center" bgcolor="#F8FFCC"><strong>Zona</strong></td>
    	      <td align="center" bgcolor="#F8FFCC"><strong>Costo Entrega</strong></td>
    	      <td align="center" bgcolor="#F8FFCC"><strong>Sucursal</strong></td>
    	      <td align="center" bgcolor="#F8FFCC"><strong>[Editar]</strong></td>
   	        </tr>
<?php while (!$rszonas->EOF) {?>
    	    <tr>
    	      <td align="center"><?php echo $rszonas->fields['descripcion']?></td>
    	      <td align="center"><?php echo formatomoneda($rszonas->fields['costoentrega'], 0); ?></td>
    	      <td align="center"><?php echo $rszonas->fields['sucursal']?></td>
    	      <td align="center"><a href="delivery_edita_zona.php?id=<?php echo $rszonas->fields['idzona']?>">[Editar]</a></td>
   	        </tr>
<?php $rszonas->MoveNext();
} ?>
  	    </tbody>
  	  </table>

	<br /><br />
            
            <hr />
            <p align="center">&nbsp;</p>
            <p align="center">&nbsp;</p>
            <p align="center"><strong>Base de Datos de Delivery:</strong></p>
            <p align="center">(Ultimos 50 Registrados)</p>
            <p align="center">&nbsp;</p>
            <form id="form1" name="form1" method="get" action="">
            
            <table width="300" border="1">
              <tbody>
                <tr>
                  <td>Telefono</td>
                  <td><input name="telefono" type="text" id="telefono" placeholder="Telefono" value="<?php if (intval($_GET['telefono']) > 0) {
                      echo '0'.intval($_GET['telefono']);
                  } ?>" /></td>
                </tr>
                <tr>
                  <td>Incluir no cobrados?</td>
                  <td><input type="checkbox" name="nocobrado" id="nocobrado" value="S" <?php if (trim($_GET['nocobrado']) == 'S') { ?>checked="checked"<?php } ?> />
                  <label for="nocobrado">SI</label></td>
                </tr>
              </tbody>
            </table>
            <p align="center">
              <input type="submit" name="submit" id="submit" value="Buscar" />
			</form>
            </p>
            <p align="center">&nbsp;</p>
            <table width="900" border="1">
              <thead> 
                <tr>
                  <td align="center" bgcolor="#F8FFCC"><strong>Telefono</strong></td>
                  <td align="center" bgcolor="#F8FFCC"><strong>Direccion</strong></td>
                  <td align="center" bgcolor="#F8FFCC"><strong>Razon Social</strong></td>
                  <td align="center" bgcolor="#F8FFCC"><strong>RUC</strong></td>
                  <td align="center" bgcolor="#F8FFCC"><strong>Zona</strong></td>
                  <td align="center" bgcolor="#F8FFCC"><strong>Registrado el</strong></td>
                </tr>
               </thead>  
               <tbody> 
<?php while (!$rscab_old->EOF) {?>
                <tr>
                  <td align="center">0<?php echo $rscab_old->fields['telefono']; ?></td>
                  <td align="center" style="width:300px;"><textarea name="textarea" id="textarea" cols="45" rows="5" style="width:300px;"><?php echo $rscab_old->fields['direccion']; ?></textarea></td>
                  <td align="center"><?php echo $rscab_old->fields['razon_social']; ?></td>
                  <td align="center"><?php echo $rscab_old->fields['ruc']; ?></td>
                  <td align="center"><?php echo $rscab_old->fields['delivery_zona']; ?></td>
                  <td align="center"><?php echo date("d/m/Y H:i", strtotime($rscab_old->fields['fechahora'])); ?></td>
                 </tr>
<?php $rscab_old->MoveNext();
} ?>
              </tbody>
            </table>
            <p align="center">&nbsp;</p>
           




          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>