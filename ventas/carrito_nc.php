<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

//FUNCION: requerir funciones_stock
//  el 12 es nc prov (agregar al localhost y bases)
/*$codrefer='',$fecha_comprobante=''





// aumenta stock general
aumentar_stock_general($idinsumo,$cantidad,$iddeposito);
// aumenta stock costo
aumentar_stock($idinsumo,$cantidad,$costo_insumo,$iddeposito);
// registra el aumento // codrefer es idnotacredito y fechacomprobante es fecha nota de credito
movimientos_stock($idinsumo,$cantidad,$iddeposito,13,'+',$codrefer,$fecha_comprobante); // 13 nota de credito cliente

*/


///print_r($_POST);

$borrar = intval($_POST['serialborrar']);
$agregar = intval($_POST['agregar']);
$serial = intval($_POST['serialdetacompra']);
$serialdetaventa = intval($_POST['serialdetaventa']);
$cantidad = floatval($_POST['cantidad']);
$cantidadprecio = floatval($_POST['cantidadprecio']);
$monto = floatval($_POST['monto']);
/*if($cantidad <= 0){
    $cantidad=1;
}
if($cantidadprecio <= 0){
    $cantidadprecio=1;
}*/
/*if($cantidad <= 0 && $cantidadprecio <= 0){
    echo "- No se envio la cantidad.";
    exit;
}*/
$tipo = intval($_POST['tipo']);
$iddeposito = intval($_POST['iddeposito']);
$metodo = intval($_POST['metodo']);
//echo $metodo;
//print_r($_POST);
if ($agregar == 1) {
    if ($tipo == 1) {
        //Items de una compra (los que estan en la factura de compras)
        if ($metodo < 2) {
            //echo 'BUSCAR ITEM DE COMPRA';exit;
            //item de una compra
            $buscar = "Select compras.idcompra,proveedores.idproveedor,codprod,compras.fechacompra,compras.facturacompra,nombre as proveedor,descripcion as producto,compras_detalles.costo
				from compras_detalles 
				inner join compras on compras.idcompra=compras_detalles.idcompra 
				inner join proveedores on proveedores.idproveedor=compras.idproveedor 
				inner join insumos_lista on insumos_lista.idinsumo=compras_detalles.codprod 
				where compras_detalles.idregs=$serial
				";
            $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        } else {
            //item de una venta, no va entrar nunca x aca, a menos que el mod sea de cliente NC
            $buscar = "Select descripcion as producto,cantidad,subtotal,pventa,ventas.idventa,idventadet,factura,ventas_detalles.idprod
				from ventas_detalles
				inner join ventas on ventas.idventa=ventas_detalles.idventa
				inner join insumos_lista on insumos_lista.idinsumo=ventas_detalles.idprod
				where ventas_detalles.idventadet=$serial
				";
            $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo $buscar;exit;
        }
    } else {
        //de tipo==1
        if ($tipo == 2) {
            //Monto de factura, como se agrega, la primera vez toma el monto de la compra como base
            $buscar = "Select compras.total as costo,compras.idcompra,'MONTO S/ FACTURA' as producto,proveedores.idproveedor,compras.fechacompra,compras.facturacompra,nombre as proveedor
				from compras
				inner join proveedores on proveedores.idproveedor=compras.idproveedor 
				where compras.idcompra=$serial
				";
            $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        }

    }
    //final de TIPO - Final de busquedas segun tipo, compras o ventas

    //Verificamos que la clase este libre, es decir, si agrego una factura x item, y ahora quiere agregar monto global, no se puede
    //debe borrar uno para poder usar el otro
    if ($metodo < 2) {
        //si es item de una compra si o si entra aca

        if ($tipo < 3) {
            $fc = trim($rsdet->fields['facturacompra']);
            //verificamos el carrito para ver si no se agrego un item distinto
            $buscar = "Select * from carrito_nc where factura='$fc' and registrado_por=$idusu and clase<>$tipo";
            $rsbus = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tr = $rsbus->RecordCount();
            if ($tr == 0) {

                $idcompra = intval($rsdet->fields['idcompra']);
                $codprod = intval($rsdet->fields['codprod']);
                $pcosto = floatval($rsdet->fields['costo']);
                $des = trim($rsdet->fields['producto']);
                $idprov = intval($rsdet->fields['idproveedor']);

                $buscar = "Select codproducto from carrito_nc where idempresa=$idempresa and registrado_por=$idusu and codproducto=$codprod";
                $rsfil = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $tinsumos_lista = $rsfil->RecordCount();
                //Cambiamos x la cantidad y monto ingresados
                if ($cantidad > 0 && $iddeposito > 0) {
                    $muevestock = 'S'	;
                    $subtotal = $cantidad * $monto;
                    $ca = $cantidad;
                }
                if ($cantidadprecio > 0 && $monto > 0) {
                    $muevestock = 'N'	;
                    $subtotal = $cantidadprecio * $monto;
                    $ca = $cantidadprecio;
                }
                //$sub=$pcosto*$cantidad;
                if ($tinsumos_lista == 0 && $agregar == 1) {
                    $insertar = "Insert into carrito_nc
					(idcompra,codproducto,tipoitem,descripcion,cantidad,precio,subtotal,registrado_por,registrado_el,
					idempresa,idsucursal,idproveedor,factura,clase,iddeposito,muevestock,tiponc)
					values
					($idcompra,'$codprod',1,'$des',$ca,$monto,$subtotal,$idusu,current_timestamp,
					$idempresa,$idsucursal,$idprov,'$fc',$tipo,$iddeposito,'$muevestock','C')";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


                } else {




                }
            } else {
                //Intenta agregar un tipo de nc pero ya existe otro
                $errorcarrito = "Ya existe un ";
                if ($tipo == 1) {
                    $com = " monto global cargado para la factura $fc e intenta agregar un item. Elimine el  monto e ingrese el(los) items deseado(s).";
                }
                if ($tipo == 2) {

                    $com = " item cargado para la factura $fc e intenta agregar monto global de factura. Elimine el(los) articulos y agregue el monto deseado.";
                }
                $errorcarrito = $errorcarrito.$com;
            }
        } else {
            //entra porque metodo es menor a 2
            if ($tipo == 3) {
                //echo 'TIPO 3 COMPRA';exit;
                // ACA ENTRA CUANDO ES  UN PROD INDIVIDUAL, QUE NO ES DE UNA FACTURA DE COMPRA ESPECIFICA
                //Cambiamos x la cantidad y monto ingresados

                if ($cantidad > 0 && $iddeposito > 0) {
                    $muevestock = 'S'	;
                    $subtotal = $cantidad * $monto;
                    $ca = $cantidad;
                }
                if ($cantidadprecio > 0 && $monto > 0) {
                    $muevestock = 'N'	;
                    $subtotal = $cantidadprecio * $monto;
                    $ca = $cantidadprecio;
                }





                $idcompra = intval(0);
                $codprod = intval($_POST['serialdetacompra']);
                //$pcosto=floatval($_POST['precio']);
                //$cantidad=floatval($_POST['cantidad']);


                $buscar = "Select descripcion from insumos_lista where idinsumo=$codprod";
                $rsf1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $des = trim($rsf1->fields['descripcion']);
                $idprov = intval(0);
                $buscar = "Select codproducto from carrito_nc where idempresa=$idempresa and registrado_por=$idusu and codproducto=$codprod";
                $rsfil = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $tinsumos_lista = $rsfil->RecordCount();
                //$sub=$pcosto*$cantidad;
                if ($tinsumos_lista == 0 && $agregar == 1) {
                    $insertar = "Insert into carrito_nc
					(idcompra,codproducto,tipoitem,descripcion,cantidad,precio,subtotal,registrado_por,registrado_el,idempresa,idsucursal,idproveedor,factura,clase,iddeposito,muevestock,tiponc)
					values
					($idcompra,'$codprod',1,'$des',$cantidad,$monto,$subtotal,$idusu,current_timestamp,$idempresa,$idsucursal,$idprov,'$fc',$tipo,$iddeposito,'$muevestock','C')";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


                }


            }//FIN de tipo=3 metodo compra

        }//FINAL DE METODO COMPRA
    } else {
        //Aca entra si el metoso es 2 o mayor lo cual indica venta
        //echo 'Metodo venta 2';exit;
        if ($tipo < 3) {
            $fc = trim($rsdet->fields['factura']);
            $buscar = "Select * from carrito_nc where factura='$fc' and registrado_por=$idusu and clase<>$tipo";
            //echo $buscar;
            $rsbus = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tr = $rsbus->RecordCount();
            if ($tr == 0) {
                $idventa = intval($rsdet->fields['idventa']);
                $codprod = intval($rsdet->fields['idprod']);

                $pventa = floatval($rsdet->fields['pventa']);
                $des = trim($rsdet->fields['producto']);
                $sub = $rsdet->fields['subtotal'];
                $idprov = 0;
                //Como son articulos de una venta, tomamos el serial del detalle de venta para comprobar existencia y si no permitimos agregar
                $detaserie = intval($rsdet->fields['idventadet']);
                //echo $detaserie;exit;
                $buscar = "Select codproducto from carrito_nc where idempresa=$idempresa and registrado_por=$idusu and codproducto=$codprod and detvta=$detaserie";
                $rsfil = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $tinsumos_lista = $rsfil->RecordCount();

                if ($tinsumos_lista == 0 && $agregar == 1) {
                    $insertar = "Insert into carrito_nc
					(idcompra,idventa,codproducto,tipoitem,descripcion,cantidad,precio,subtotal,registrado_por,registrado_el,idempresa,idsucursal,idproveedor,factura,clase,iddeposito,detvta)
					values
					(0,$idventa,'$codprod',1,'$des',$cantidad,$pventa,$sub,$idusu,current_timestamp,$idempresa,$idsucursal,$idprov,'$fc',$tipo,$iddeposito,$detaserie)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


                } else {
                    //ver que hacemos



                }
            }//falta else
        }//de tipo <3

    }
}
//Nuevo solo para monto de factura

if ($agregar == 99) {
    ///print_r($_POST);
    $tipodocumento = trim($_POST['tipodocumento']);
    $idcompra = intval($_POST['serial']);
    if ($tipodocumento != 'G') {
        $buscar = "Select factura_numero from facturas_proveedores where idcompra=$idcompra";
    } else {
        $buscar = "Select factura_numero from facturas_proveedores where idgasto=$idcompra";

    }

    $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $fc = trim($rs1->fields['factura_numero']);
    $monto = floatval($_POST['monto']);
    $des = 'MONTO NETO S/ FACTURA';
    $ca = 1;

    $tipo = 4;
    $idprov = intval(0);
    $tipoiva = intval($_POST['tipoiva']);
    $iddeposito = 0;
    $muevestock = 'N';
    $insertar = "Insert into carrito_nc
		(idcompra,codproducto,tipoitem,descripcion,cantidad,precio,subtotal,registrado_por,registrado_el,
		idempresa,idsucursal,idproveedor,factura,clase,iddeposito,muevestock,tiponc,claseiva)
		values
		($idcompra,NULL,1,'$des',$ca,$monto,$monto,$idusu,current_timestamp,
		$idempresa,$idsucursal,$idprov,'$fc',$tipo,$iddeposito,'$muevestock','$tipodocumento',$tipoiva)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


}







if ($borrar > 0) {

    $delete = "Delete from carrito_nc where registro=$borrar";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

}

$buscar = "Select factura,codproducto,tipoitem,carrito_nc.descripcion as productodes,
(select gest_depositos.descripcion from gest_depositos where gest_depositos.iddeposito=carrito_nc.iddeposito limit 1)
as deposito,
cantidad,precio,subtotal,registro,clase,carrito_nc.iddeposito,claseiva,
(select tipoiva from insumos_lista where insumos_lista.idinsumo=carrito_nc.codproducto) as tipoivaprod

from carrito_nc 


where carrito_nc.idempresa=$idempresa and carrito_nc.registrado_por=$idusu order by registro desc";
$rscarr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tcarrito = $rscarr->RecordCount();

?>
<div >
	<div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorescod" <?php if ($errorcarrito == "") {?>style="display: none"<?php }?>>
						<strong>Errores:</strong><span id="errorescodcuerpo"><?php echo $errorcarrito;?></span>
						</div>
<?php if ($tcarrito > 0) {?>
<table class="table table-bordered">
	<thead>
		<tr>
			<th height="30" bgcolor="#E8E8E8">Factura</th>
			<th bgcolor="#E8E8E8">Descripci&oacute;n / Art&iacute;culo</th>
			<th bgcolor="#E8E8E8">Cantidad</th>
			<th bgcolor="#E8E8E8">Sub Total</th>
			<th bgcolor="#E8E8E8">Deposito</th>
			<th bgcolor="#E8E8E8"></th>
			</tr>
	</thead>
	<tbody>
		<?php
        $iva10 = 0;
    $iva5 = 0;
    $ex = 0;
    $tt = 0;
    while (!$rscarr->EOF) {
        if ($rscarr->fields['claseiva'] == '') {
            $tiva = intval($rscarr->fields['tipoivaprod']);
        } else {
            $tiva = intval($rscarr->fields['claseiva']);

        }
        $rr = intval($rscarr->fields['registro']);
        $clase = intval($rscarr->fields['clase']);
        $subtotal = $rscarr->fields['subtotal'];
        $tt = $tt + $subtotal;
        if ($tiva == 0) {

        }
        if ($tiva == 5) {
            $iva5 = $iva5 + ($subtotal / 21);
        }
        if ($tiva == 10) {
            $iva10 = $iva10 + ($subtotal / 11);
        }
        ?>
		<tr>
			<th height="29"><?php echo $rscarr->fields['factura']?></th>
			<th align="right"><?php echo $rscarr->fields['productodes']?></th>
			<td align="right"><?php echo formatomoneda($rscarr->fields['cantidad'], 4, 'N');?></td>
			<td align="right"><?php if ($clase == 1 or $clase == 3 or $clase == 4) {?><?php echo formatomoneda($rscarr->fields['subtotal'], 4, 'N');?><?php } else {?><?php if ($clase == 2) {?><input onKeyUp="actualizar(this.value)" type="text" name="octotal_<?php echo $rr?>" id="octotal_<?php echo $rr?>" value="<?php echo intval($subtotal)?>" />
			<?php } ?><?php }?>
			</td>
			<td align="right"><?php echo $rscarr->fields['deposito']?></td>
		  <td><a href="javascript:void(0);" onClick="eliminarcarrito(<?php echo $rscarr->fields['registro'] ?>)"><span class="fa fa-trash"></span></a></td>
			
		</tr>
		<?php $rscarr->MoveNext();
    }?>
		
		<tr>
		  <td height="35" colspan="4" align="right"><strong>Total Nota Cr&eacute;dito Gs:&nbsp;&nbsp; <?php echo formatomoneda($tt, 4, 'N') ?></strong></td>
			<td></td>
			<td></td>
	  </tr>
      <tr>
		  <td height="35" colspan="6" align="center"><strong>Desgloce IVA</strong><br />
             <h2> <strong>IVA 5% :<?php echo formatomoneda($iva5, 0, 'S'); ?>  &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;  IVA 10%: <?php echo formatomoneda($iva10, 0, 'S'); ?></strong></h2>
          </strong></td>
	  </tr>
		<tr>
		
		<td colspan="6" align="center">
			
				<input type="button" value="Generar NC" onclick="procederf();" />
			
			</td>
		</tr>
	</tbody>
</table>
	
<?php } else { ?>

	
	
<?php } ?>
</div>