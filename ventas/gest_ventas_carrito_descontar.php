<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "244";
require_once("includes/rsusuario.php");

$origen = intval($_REQUEST['0']);

//Descuento parametros
$buscar = "select * from parametros_descuento where estado=1";
$rsparam = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$mostrardesc = trim($rsparam->fields['mostrar_dcto_porc']);
$usarpminimo = trim($rsparam->fields['usar_precio_minimo']);
if ($usarpminimo == 'N') {
    $condicional = " $minimo > 0 ";
} else {
    $condicional = " $minimo >= 0 ";
}

$descontar = intval($_REQUEST['dc']);
if ($descontar > 0) {
    if ($descontar == 1) {
        $dcto = 10;
    }
    if ($descontar == 2) {
        $dcto = 20;
    }
    if ($descontar == 3) {
        $dcto = 30;
    }
    if ($descontar == 4) {
        $dcto = 40;
    }
    if ($descontar == 5) {
        $dcto = 50;
    }
    //Descontamos

    $buscar = "Select *
	from carrito_tmp_descuentos where registrado_por=$idusu";
    $rsca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    while (!$rsca->EOF) {
        $idprodserial = intval($rsca->fields['idprodserial']);
        $idventatmp = intval($rsca->fields['idventatmp']);
        $canti = floatval($rsca->fields['cantidad']);
        $precio_normal = floatval($rsca->fields['precio_normal']);
        $precio_minimo = floatval($rsca->fields['precio_minimo']);
        $dctoneto = (floatval($rsca->fields['precio_normal']) * $dcto) / 100;
        $descunitario = $dctoneto;
        $nuevoprecio = ($rsca->fields['precio_normal'] - $dctoneto);
        $ns = $nuevoprecio * $canti;

        //FALTA ID MOTIVO DESC OJO


        $update = "update tmp_ventares set descuento=$dctoneto,subtotal=$ns where idventatmp=$idventatmp";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //logueamos el cambio
        $insertar = "Insert into log_descuentos_productos
				(idprodserial,cantidad,precio_normal,precio_cobrado,descuento_neto,idventatmp,registrado_el,registrado_por,subtotal_original,subtotal_nuevo)
				values 
				($idprodserial,$canti,$precio_normal,$nuevoprecio,$descunitario,$idventatmp,current_timestamp,$idusu,($canti*$precio_normal),$ns)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $rsca->MoveNext();
    }

}


if (isset($_POST['proceder'])) {
    $idmotivo = intval($_REQUEST['motivodesc']);
    //generamos cursor original
    $buscar = "Select *
	from carrito_tmp_descuentos where registrado_por=$idusu";
    $rsca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    while (!$rsca->EOF) {


        $idventatmp = intval($rsca->fields['idventatmp']);
        $idprodserial = intval($rsca->fields['idprodserial']);
        $nuevoprecio = floatval($_POST["precio_$idventatmp"]);
        $precio_normal = floatval($rsca->fields['precio_normal']);
        $precio_minimo = floatval($rsca->fields['precio_minimo']);
        $descunitario = ($precio_normal - $nuevoprecio);
        if ($usarpminimo == 'S') {
            if ($precio_minimo > 0) {
                //solo si hay precio minimo establecido, y si cumple condicion de ser igual o mayor al minimo precio
                if ($nuevoprecio >= $precio_minimo) {

                    $canti = floatval($rsca->fields['cantidad']);
                    $subtosindesc = $precio_normal * $cantidad;
                    $descutotal = floatval($descunitario * $canti);
                    $ns = floatval(($canti * $precio_normal) - $descutotal);
                    $update = "update tmp_ventares set descuento=$descutotal,subtotal=$ns where idventatmp=$idventatmp";
                    $conexion->Execute($update) or die(errorpg($conexion, $update));

                    //logueamos el cambio
                    $insertar = "Insert into log_descuentos_productos
					(idprodserial,cantidad,precio_normal,precio_cobrado,descuento_neto,idventatmp,registrado_el,registrado_por,subtotal_original,subtotal_nuevo,idmotivodesc)
					values 
					($idprodserial,$canti,$precio_normal,$nuevoprecio,$descunitario,$idventatmp,current_timestamp,$idusu,($canti*$precio_normal),$ns,$idmotivo)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                }
            }
        } else {
            //no se toma en cuenta el precio minimo, procedemos
            $canti = floatval($rsca->fields['cantidad']);
            $subtosindesc = $precio_normal * $cantidad;
            $descutotal = floatval($descunitario * $canti);
            $ns = floatval(($canti * $precio_normal) - $descutotal);
            $update = "update tmp_ventares set descuento=$descutotal,subtotal=$ns where idventatmp=$idventatmp";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //logueamos el cambio
            $insertar = "Insert into log_descuentos_productos
					(idprodserial,cantidad,precio_normal,precio_cobrado,descuento_neto,idventatmp,registrado_el,registrado_por,subtotal_original,subtotal_nuevo,idmotivodesc)
					values 
					($idprodserial,$canti,$precio_normal,$nuevoprecio,$descunitario,$idventatmp,current_timestamp,$idusu,($canti*$precio_normal),$ns,$idmotivo)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



        }




        $rsca->MoveNext();
    }

}



$consulta = "
select tmp_ventares.*, productos.descripcion,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado,
(select productos_sucursales.precio_minimo from productos_sucursales where productos_sucursales.idproducto=tmp_ventares.idproducto and productos_sucursales.idsucursal=tmp_ventares.idsucursal) as precio_minimo,
productos.idtipoproducto, tmp_ventares.idprod_mitad2, tmp_ventares.idprod_mitad1
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa

";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;

$delete = "delete from carrito_tmp_descuentos where registrado_por=$idusu";
$conexion->Execute($delete) or die(errorpg($conexion, $delete));

while (!$rs->EOF) {
    $idprodser = intval($rs->fields['idproducto']);
    $idventatmp = intval($rs->fields['idventatmp']);
    $describe = antisqlinyeccion(trim($rs->fields['descripcion']), "text");
    $precioventa = floatval($rs->fields['precio']);
    $subtotal = floatval($rs->fields['subtotal']);
    $cantidad = floatval($rs->fields['cantidad']);
    $pminimo = floatval($rs->fields['precio_minimo']);


    //rellenamos la tabla del carrito

    $insertar = "Insert into carrito_tmp_descuentos

	(registrado_por,idventatmp,descripcion,precio_normal,precio_minimo,precio_con_desc,subtotal,cantidad,idprodserial) 
	values 
	($idusu,$idventatmp,$describe,$precioventa,$pminimo,0,$subtotal,$cantidad,$idprodser)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    $rs->MoveNext();
}

//Rellenamos el carrito temporal de descuentos x producto para hacer los cambios
$buscar = "Select * from carrito_tmp_descuentos
where registrado_por=$idusu";
$rsc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcarrito = $rsc->RecordCount();

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
	function recalcular(idtempo){
		var valor=$("#precio_"+idtempo).val();
		valor=parseFloat(valor);
		var cantidad=$("#cantioc_"+idtempo).val();
		var porig=$("#porig_"+idtempo).val();
		var nsubto=parseFloat(valor*cantidad);
		
		var pminimo=$('#preciominimo_'+idtempo).val();
		pminimo=parseFloat(pminimo);
		<?php if ($usarpminimo == 'S') {?>
		if (valor >= pminimo){
			if (nsubto==0){
				nsubto=cantidad*porig;
			}
			$('#subto_'+idtempo).prop("disabled", false);
			$('#subto_'+idtempo).val(nsubto);
			$('#subto_'+idtempo).prop("disabled", true);
			setTimeout(function(){ recalculatotal(); }, 500);
			
		}
		<?php } else {?>
			if (nsubto==0){
				nsubto=cantidad*porig;
			}
			$('#subto_'+idtempo).prop("disabled", false);
			$('#subto_'+idtempo).val(nsubto);
			$('#subto_'+idtempo).prop("disabled", true);
			setTimeout(function(){ recalculatotal(); }, 500);
		
		<?php } ?>
	}
	function recalculatotal(){
		var nsub=0;
		var nv=0;
		 $(".subtclass").each(function()
				{
					if($(this).val()) 
						nv=parseFloat(nv)+parseFloat(($(this).val()));
				});
		$("#recalculado").val(nv);
	}
	function seleccionar(valor){
		var dc=parseInt(valor);
		window.open("gest_ventas_carrito_descontar.php?dc="+dc,"_self");
		
	}
</script>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      <br /><br />
   <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><?php ?><a href="gest_ventas_resto_caja.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar a ventas"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
      	<div class="divstd">
    		<span class="resaltaditomenor">
    			Descuentos
                <br />
                <div align="center">
                <?php

                $buscar = "Select count(idprodserial) as cantidad from log_descuentos_productos";
$rs4 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tc = $rs4->RecordCount();
if ($tc > 0) {
    ?>
                <a href="inf_descuentos_precios.php" target="_new">
				  <img src="img/1476042755_office-04.png" width="64" height="64" alt=""/>
                </a>
<?php
}
?>
                
                </div>
   		  </span>
			<?php if ($mostrardesc == 'S') { ?>
		  <div class="resumenmini">Se permiten aplicar descuentos sobre productos.
			  Puede utilizar los botones predefinidos y los descuentos seran aplicados a todos los productos del carrito activo
              
			  <br />
			  <form id="fg1" action="" method="post">
				<input type="button" name="dcto1" value="10%" onClick="seleccionar(1);"/> &nbsp;
				<input type="button" name="dcto2" value="20%" onClick="seleccionar(2);" /> &nbsp; 
				<input type="button" name="dcto3" value="30%" onClick="seleccionar(3);"/> &nbsp;
				<input type="button" name="dcto4" value="40%" onClick="seleccionar(4);"/> &nbsp; 
				<input type="button" name="dcto5" value="50%" onClick="seleccionar(5);"/> &nbsp;
			   </form>
				
			</div><?php } ?>
 		</div>
		<br />
		<hr />
        <?php if ($tcarrito > 0) {?>
		<form id="fgh1" method="post" action="gest_ventas_carrito_descontar.php">
        		<?php if ($usarpminimo == 'S') {?>	
				<div align="center"><span class="resaltarojomini">Atencion</span>: solo se permite descontar si el producto posee un precio m&iacute;nimo establecido</div><br />
                <?php } ?>
                <div align="center">
                <select style="height:40px;" name="motivodesc" id="motivodesc" required="required">
                	<option value="" selected="selected">Motivo Descuento</option>
                	<?php while (!$rsmotivos->EOF) {?>
                    <option value="<?php echo $rsmotivos->fields['idmotivodesc']?>" <?php if ($_REQUEST['motivodesc'] == $rsmotivos->fields['idmotivodesc']) {?>selected="selected"<?php }?>><?php echo $rsmotivos->fields['descripcion'] ?></option>
                    <?php $rsmotivos->MoveNext();
                	}?>
                </select>
                </div>
		<table width="600px;" border="1" class="tablalinda" id="tablacarrito">
  <tbody>
    <tr>
      <td width="201" height="36" bgcolor="#CCCCCC"><strong>Producto</strong></td>
      <td width="85" align="center" bgcolor="#CCCCCC"><strong>Cant.</strong></td>
      <td width="97" align="center" bgcolor="#CCCCCC"><strong>Precio Normal</strong></td>
      
      <td width="91" align="center" bgcolor="#CCCCCC"><strong>Precio Descontado</strong></td>
		<td width="92" align="center" bgcolor="#CCCCCC"><strong>Sub Total</strong></td>
    </tr>
<?php
    $cc = 0;
            while (!$rsc->EOF) {
                $idtmp = intval($rsc->fields['idventatmp']);

                $total = $rsc->fields['subtotal'];
                $totalacum += $total;
                $des = str_replace("'", "", $rsc->fields['descripcion']);
                $minimo = floatval($rsc->fields['precio_minimo']);


                ?>
    <tr >
      <td height="30"><?php echo Capitalizar($rsc->fields['descripcion']); ?></td>
		<td align="center"><?php echo formatomoneda($rsc->fields['cantidad'], 3, 'N'); ?><input id="cantioc_<?php echo $idtmp; ?>" type="hidden" value="<?php echo($rsc->fields['cantidad']); ?>" /></td>
		 <td align="center"><?php echo formatomoneda($rsc->fields['precio_normal'], 3, 'N'); ?>
		<input id="porig_<?php echo $idtmp; ?>" type="hidden" value="<?php echo($rsc->fields['precio_normal']); ?>" />
		</td>
      
     
		<td align="center">
        <?php if ($condicional) {?>
        <input type="text" name="precio_<?php echo $idtmp; ?>" id="precio_<?php echo $idtmp; ?>" size="7" onKeyUp="recalcular(<?php echo $idtmp; ?>)" />
         <?php } else {
             echo formatomoneda($rsc->fields['precio_normal'], 3, 'N');
         } ?>
         <input type="hidden" name="preciominimo_<?php echo $idtmp; ?>" id="preciominimo_<?php echo $idtmp; ?>" size="7" value="<?php echo $rsc->fields['precio_minimo']; ?>" />
        </td>
      <td align="center">
      	<input class="subtclass" type="text" name="subto_<?php echo $idtmp; ?>" id="subto_<?php echo $idtmp; ?>" value="<?php echo($rsc->fields['subtotal']); ?>" readonly="readonly" size="15"/>
        </td>
      
		
    </tr>
<?php $rsc->MoveNext();
            } ?>
<?php

            // buscar si hay agregados y mostrar el total global
            $consulta = "
SELECT sum(precio_adicional) as montototalagregados , count(idventatmp) as totalagregados
FROM 
tmp_ventares_agregado
where
idventatmp in (
select tmp_ventares.idventatmp
from tmp_ventares 
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa
)
";
            //echo $consulta;
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $montototalagregado = $rs->fields['montototalagregados'];
            $totalagregado = $rs->fields['totalagregados'];
            $totalacum += $montototalagregado;

            if ($totalagregado > 0) {
                ?>
    <tr>
      <td height="30">Agregados</td>
      <td align="center"><?php echo formatomoneda($totalagregado, 0); ?></td>
      <td align="center">&nbsp;</td>
      <td align="center"><?php echo formatomoneda($montototalagregado, 0); ?></td>
      <td align="center">&nbsp;</td>
    </tr>
<?php } ?>
    <tr>
		<td height="39" colspan="6" align="center">
			
			<strong>
				<span style="font-size: 16px;color: #DB171A">Total Venta:&nbsp;&nbsp; 
					<input type="text" name="recalculado" id="recalculado" value="<?php echo($totalacum); ?>" readonly="readonly"/>
					
				</span>
			</strong>
			<br /><br /><br />
			<input type="submit" value="Procesar cambios" />
			<input type="hidden" name="proceder" value="1" />
		</td>
    </tr>
  </tbody>
</table>
</form>		
<?php } else {?>
<div align="center">
	<span class="resaltarojomini">No existen productos en el carrito. Debe cargar al menos un producto, luego regrese para efectuar descuento</span>
</div>
<?php }?>
		  </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>