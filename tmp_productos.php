 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");

$ide = 1;
$tipo = intval($_POST['tp']);
if ($tipo == 1) {

    //registro
    //Agregar a temporal
    $cantidad = intval($_POST['ca']);
    $idp = antisqlinyeccion($_POST['idp'], 'text');
    $idtransaccion = intval($_POST['idtransaccion']);
    //Validamos que existan efectivamente esa cantidad para la venta
    $buscar = "Select * from productos where idprod=$idp";
    $rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    /* $pve=floatval($rsp->fields['precio_venta']);
    $pmin=floatval($rsp->fields['precio_min']);
    $pmax=floatval($rsp->fields['precio_max']); */
    $p1 = floatval($rsp->fields['p1']);
    $p2 = floatval($rsp->fields['p2']);
    $p3 = floatval($rsp->fields['p3']);
    $lp = ($rsp->fields['listaprecios']);
    $disponible = intval($rsp->fields['disponible']);
    $descripcion = trim($rsp->fields['descripcion']);
    $tipoprecio = intval($_POST['tipoprecio']);
    $iva = intval($rsp->fields['tipoiva']);

    if ($tipoprecio == 1) {
        $precioventa = floatval($rsp->fields['p1']);

    }
    if ($tipoprecio == 2) {
        $precioventa = floatval($rsp->fields['p2']);
        //calculamos neto a descontar para el producto
        $neto = $p1 - $p2;

    }
    if ($tipoprecio == 3) {
        $precioventa = floatval($rsp->fields['p3']);
    }
    if ($tipoprecio == 4) {
        $precioventa = floatval($_POST['inter']);
        $neto = $p1 - $precioventa;
    }

    $subtotal = $precioventa * $cantidad;
    $totdescu = $neto * $cantidad;
    if ($cantidad > $disponible) {
        $errordis = 1;

    }
    if ($errordis != 1) {
        //Buscamos a ver que el producto no este en el temporal, asi le sumamos a la grilla


        $buscar = "Select * from tmpventadeta where idtfk=$idtransaccion and idprod=$idp";

        $rstm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $p = trim($rstm->fields['idprod']);
        //Traemos costos
        $buscar = "Select * from costo_productos where id_producto=$idp and cantidad > 0 order by registrado_el asc";
        $rsco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $costofinal = floatval($rsco->fields['precio_costo']);
        $dispocosto = ($rsco->fields['cantidad']);

        if ($p == '') {
            if ($cantidad > $dispocosto) {
                //Existen dos o mas costos aun para este producto, por lo cual debemos hallar el costo nuevo
                $buscar = "Select precio_costo from costo_productos where id_producto=$idp order by registrado_el desc limit 1";
                $rscop = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $costofinal = floatval($rscop->fields['precio_costo']);
            }
            $utilidad = $precioventa - $costofinal;
            //Agregamos al temporal
            $insertar = "
            Insert into tmpventadeta
            (idprod,idemp,cantidad,costo,utilidad,disponible,precioventa,subtotal,pchar,idtfk,iva,descnetogs,p1,p2,p3)
            values
            ($idp,$ide,$cantidad,$costofinal,$utilidad,$disponible,$precioventa,$subtotal,'$descripcion',$idtransaccion,$iva,$totdescu,$p1,$p2,$p3)    
            ";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            //Generamos la primera vista
            $buscar = "Select * from tmpventadeta 
            where idtfk=$idtransaccion order by pchar asc";
            $rsdt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tempo = $rsdt->RecordCount();
        } else {
            //ya existe, updatear cantidad
            //Nuevo subtoal
            $subtmas = $cantidad * $precioventa;
            //Nuevo costo global
            $nuevocosto = $costofinal * $cantidad;

            $nuevautilidad = $subtmas - $nuevocosto;
            $update = "Update tmpventadeta set
            cantidad=(cantidad+$cantidad),subtotal=(subtotal+$subtmas),utilidad=(utilidad+$nuevautilidad),
            costo=(costo+$nuevocosto),descnetogs=(descnetogs+$totdescu) where idtfk=$idtransaccion and idprod=$idp";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //Generamos la primera vista
            $buscar = "Select * from tmpventadeta 
            where idtfk=$idtransaccion order by pchar asc";
            $rsdt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tempo = $rsdt->RecordCount();
        }
    } else {
        //NO Hay stock suficiente



    }
}
if ($tipo == 3) {
    $idtransaccion = intval($_POST['idtransaccion']);
    $idt = $idtransaccion;
    $idregistro = intval($_POST['reg']);
    if ($idregistro > 0) {
        $delete = "Delete from tmpventadeta where idreg=$idregistro"    ;
        $conexion->Execute($delete) or die(errorpg($conexion, $delete));
        //Generamos la primera vista
        $buscar = "Select * from tmpventadeta 
            where idtfk=$idtransaccion order by pchar asc";
        $rsdt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tempo = $rsdt->RecordCount();
    }

}


if ($tipo == 0) {

    $idtransaccion = $idt;
    //Solo traemos los productos aregados
    $buscar = "select * from tmpventadeta 
    where idtfk=$idtransaccion order by pchar asc";
    $rsdt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tempo = $rsdt->RecordCount();
}
//listadelivery


$buscar = "Select nombres,idusu from usuarios where delivery=1 order by usuario asc";
$rsdeliv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tdv = $rsdeliv->RecordCount();
?>
<?php if ($errordis == 1) {?>
    <div align="center">
    <img src="img/alerta_blue.jpg" width="40" height="40" alt=""/><br />
       <span class="resaltarojo">Producto no disponible.<br />Cantidad supera al stock actual</span>
    <br />
    </div>
<?php } ?>
<div class="resumenmini">
    <span class="resaltaazul">Productos seleccionados</span><br /><br />
    <?php if ($tempo > 0) {

        ?>
     <table width="450" border="1">
          <tbody>
            <tr>
              <td width="188" height="28" align="center" bgcolor="#D1D1D1"><strong>Producto</strong></td>
              <td width="60" align="center" bgcolor="#D1D1D1"><strong>Precio Venta</strong></td>
              <td width="60" align="center" bgcolor="#D1D1D1"><strong>Cantidad</strong></td>
              <td width="61" align="center" bgcolor="#D1D1D1"><strong>Sub Total</strong></td>
              <td width="47" align="center" bgcolor="#D1D1D1"><strong>Acci&oacute;n</strong></td>
              
            </tr>
            <?php
                //

                $buscar = "Select sum(subtotal) as sub10 from tmpventadeta where idtfk=$idtransaccion and iva=10";
        $rsiv1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $buscar = "Select sum(subtotal) as sub5 from tmpventadeta where idtfk=$idtransaccion and iva=5";
        $rsiv5 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $buscar = "Select sum(subtotal) as subex from tmpventadeta where idtfk=$idtransaccion and iva=0";
        $rsex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tventa10 = floatval($rsiv1->fields['sub10']);
        $iva10 = (floatval($rsiv1->fields['sub10']) / 11);
        $tventa5 = floatval($rsiv5->fields['sub5']);
        $iva5 = (floatval($rsiv5->fields['sub5']) / 21);
        $tventaex = floatval($rsiv1->fields['subex']);
        $tdescu = 0;

        while (!$rsdt->EOF) {
            $idreg = intval($rsdt->fields['idreg']);
            $total = $total + $rsdt->fields['subtotal'];
            $tdescu = $tdescu + $rsdt->fields['descnetogs']
            ?>
            <tr>
              <td align="left"><?php echo trim($rsdt->fields['pchar']) ?></td>
              <td align="center"><?php echo ($rsdt->fields['precioventa']) ?></td>
              <td align="right"><?php echo trim($rsdt->fields['cantidad']) ?></td>
              <td align="right"><?php echo formatomoneda($rsdt->fields['subtotal'], 2) ?></td>
              <td align="center"><a href="javascrip:void(0);" onClick="eliminar(<?php echo $idreg ?>)"><img src="img/no.PNG" width="16" height="16" title="Eliminar Producto"/></a></td>
              
            </tr>
            <?php $rsdt->MoveNext();
        } ?>
            <tr>
                <td height="34" colspan="4" align="right"><span class="resaltarojomini">Total Venta Gs: <?php echo formatomoneda($total); ?></span></td>
                <td></td>
            </tr>
            <tr>
                <td height="34" colspan="4" align="right"><span class="resaltarojomini">Total Descuento Gs: <?php echo formatomoneda($tdescu); ?></span></td>
                <td></td>
            </tr>
          </tbody>
        </table>

     <?php } else { ?>
     <span class="resaltarojomini">No agregaste ning&uacute;n producto.</span>
     <?php } ?>
    <br />
</div>
<br />
<?php if ($tempo > 0) {?>
<form id="vta" name="vta" action="gest_ventas.php" method="post">
 <div class="resumenmini" style="height:200px;">
    
        <?php require_once('includes/bus_cliente.php')?>
    
    
 </div>
 <br />
 
 <div class="resumenmini" style="height:550px;">
         <span class="resaltaditomenor">Resumen Venta</span>
        <br />
        <table width="327">
        <tr>
        <td height="35" colspan="4" align="center" bgcolor="#DFDFDF"><strong>Discriminaci&oacute;n de Valores
          <input type="hidden" name="tv" id="tv" value="<?php echo $total?>">
        </strong></td>
        </tr>
        <tr>
              <td height="30" align="right" bgcolor="#B9B5B5"><strong>Venta 10 %: &nbsp;</strong></td>
              <td align="right"><?php echo formatomoneda($tventa10, 2) ?></td>
                <td align="right"><strong>IVA 10%: &nbsp;</strong></td>
              <td align="right"><?php echo formatomoneda($iva10, 2) ?></td>
            
            </tr>
            <tr>
              <td height="30" align="right" bgcolor="#B9B5B5"><strong>Venta 5 %: &nbsp;</strong></td>
              <td align="right"><?php echo formatomoneda($tventa5, 2) ?></td>
                <td align="right"><strong>IVA 5%: &nbsp;</strong></td>
              <td align="right"><?php echo formatomoneda($iva5, 2) ?></td>
            
            </tr>
            <tr>
              <td height="30" align="right" bgcolor="#E3D6D6"><strong>Exenta: &nbsp;</strong></td>
              <td colspan="3" align="left"  bgcolor="#E3D6D6"><strong><?php echo formatomoneda($tventaex, 2);?></strong></td>
            </tr>
            <tr>
              <td width="101" height="40" align="right"  bgcolor="#B9B5B5"><strong>Total Venta:&nbsp;</strong></td>
              <td width="69" align="left"><span class="resaltarojo"><?php echo formatomoneda($total);?></span></td>
                <td width="95" align="right"><strong>Desc. GS:&nbsp;</strong></td>
              <td width="42" align="left"><input type="text" name="desc" id="desc" value="<?php echo $tdescu?>" size="5" readonly /></td>
            
            </tr>
            <tr>
                <td height="46" align="right" bgcolor="#E5E5E5"><strong>Medio Entrega: &nbsp;</strong></td>
                <td>
                <select id="medioentrega" name="medioentrega" onChange="mentrega(this.value)">
                    <option value="0" selected="selected">Seleccionar</option>
                    <option value="1" <?php if ($idpedido > 0) {
                        if ($medioentrega == 1) {?> selected="selected"<?php }
                        }?>>Delivery</option>
                    <option value="2" <?php if ($idpedido > 0) {
                        if ($medioentrega == 2) {?> selected="selected"<?php }
                        }?>>Pasa a retirar</option>
                     <option value="3" <?php if ($idpedido > 0) {
                         if ($medioentrega == 3) {?> selected="selected"<?php }
                         }?>>Encomienda</option>
                </select>
              </td>
                <td bgcolor="#E5E5E5"><strong>Costo Entrega: &nbsp;</strong></td>
                <td><input type="text" name="centrega" id="centrega"  size="5" value="<?php if ($idpedido > 0) {
                    echo $costoenvio;
                } else {
                    echo '0';
                } ?>" /></td>
            </tr>
            <tr>
             <td height="46" align="right" bgcolor="#E5E5E5"><strong>Asignar a: &nbsp;</strong></td>
             <td>
             <?php if ($rsdt > 0) {?>
             <select id="asignado" name="asignado">
               <option value="0" selected="selected">Seleccionar</option>
                  <?php while (!$rsdeliv->EOF) {?>
                <option value="<?php echo $rsdeliv->fields['idusu']?>"><?php echo $rsdeliv->fields['nombres']?></option>
                <?php $rsdeliv->MoveNext();
                  } ?>
             </select>
             <?php } else {?>
             <span class="reslatarojomini">Debe registrar un delivery antes de continuar</span>
             <?php } ?>
             </td>
              <td></td>
               <td></td>
            </tr>
            <tr>
              <td height="33" align="center"   bgcolor="#E3D6D6"><strong>Neto abonar: </strong></td>
              <td colspan="3" align="left" >&nbsp;&nbsp;&nbsp;<input type="text" name="neto" id="neto" value="<?php
             if ($idpedido > 0) {
                 echo($total + $costoenvio);
             } else {
                 echo($total);
             }
    ?>" style="border:0px; width:60px; font-weight:bold; color:#F70105; font-size:16px;" />
              <input type="hidden" name="ta" id="ta" value="<?php
    if ($idpedido > 0) {
        echo($total + $costoenvio);
    } else {
        echo $total;
    }?>">
              <input type="hidden" name="idtoc" id="idtoc" value="<?php echo $idtransaccion?>">
              
              
              </td>
            </tr>
             
           </table>
            <br />
            <span class="resaltaditomenor">Documentaci&oacute;n</span>
        <table width="400" height="100">
               
                <tr>
                <td align="right" bgcolor="#91FFCA"><strong> Cond. de Venta </strong></td>
                <td align="left"> <select id="condventa" name="condventa">
                <option value="0" selected="selected">Seleccionar</option>
                     <option value="1" >CONTADO</option>
                    <option value="2">CREDITO</option>
                </select></td>
                <td align="right" bgcolor="#8CF8CB"><strong>Forma de Pago</strong></td>
                <td><select id="formapago" name="formapago">
                  <option value="0" selected="selected">Seleccionar</option>
                  <option value="1">EFECTIVO</option>
                  <option value="2">TARJETA</option>
                  <option value="3">TRANSFERENCIA</option>
                  <option value="4">CHEQUE</option>
                </select></td>
          </tr>
          <tr>
                <td colspan="4" align="center"><img src="img/ok01.png" width="64" height="64" title="Registrar Venta" onClick="registrarventa()" style="cursor:pointer"/></td>
          </tr>
        </table>
 </div>
 </form>
 <?php } ?>
 
