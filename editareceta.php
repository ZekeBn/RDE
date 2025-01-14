 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

/*
migrar_agregado_producto.php
*/

$consulta = "
select agregado_usaprecioprod from preferencias_caja limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$agregado_usaprecioprod = $rsprefcaj->fields['agregado_usaprecioprod'];

$idcanalventa = intval($_SESSION['idcanalventa']);
$idlistaprecio = intval($_SESSION['idlistaprecio']);
if ($idcanalventa > 0) {
    $consulta = "
    select *, canal_venta.canal_venta,
    (select lista_precios_venta.lista_precio from  lista_precios_venta where idlistaprecio = canal_venta.idlistaprecio) as lista_precio
    from canal_venta 
    inner join canal_venta_perm on canal_venta_perm.idcanalventa = canal_venta.idcanalventa
    where 
    canal_venta_perm.idusuario = $idusu
    and canal_venta_perm.estado = 1 
    and canal_venta.estado = 1 
    and canal_venta.idcanalventa = $idcanalventa
    order by canal_venta.canal_venta asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lista_precio = $rs->fields['lista_precio'];
    $idlistaprecio = $rs->fields['idlistaprecio'];
}
if ($idlistaprecio > 0) {

    $seladd = "
    COALESCE(
        (
        select 
        CASE redondeo_direccion
        WHEN 'A' THEN CEIL(((agregado.precio_adicional*(lista_precios_venta.recargo_porc/100))+agregado.precio_adicional)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
        WHEN 'B' THEN FLOOR(((agregado.precio_adicional*(lista_precios_venta.recargo_porc/100))+agregado.precio_adicional)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
        ELSE
            ROUND(((agregado.precio_adicional*(lista_precios_venta.recargo_porc/100))+agregado.precio_adicional)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
        END as redondeado
        from lista_precios_venta
        where
        lista_precios_venta.idlistaprecio = $idlistaprecio
        )
    ,0) as precio_adicional,
    ";
} else {
    $seladd = " agregado.precio_adicional,  ";

}
// usa precio del producto agregado
//$agregado_usaprecioprod="S";
if ($agregado_usaprecioprod == 'S') {
    if ($idlistaprecio > 0) {
        // para lista de precios
        $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
        // and productos_listaprecios.estado = 1
        $whereadd_lp = "
        and productos_listaprecios.idsucursal = $idsucursal 
        and productos_listaprecios.idlistaprecio = $idlistaprecio 
        
        ";
        $seladd_lp = " productos_listaprecios.precio ";
    } else {
        $seladd_lp = " productos_sucursales.precio ";
    }
    //and productos_sucursales.activo_suc = 1
    $seladd = "
    COALESCE(
    (
        select $seladd_lp as precio
        from productos 
        inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
        $joinadd_lp
        where
        productos.idprod_serial is not null
        and productos.idprod_serial = insumos_lista.idproducto
        and productos.borrado = 'N'

        and productos_sucursales.idsucursal = $idsucursal 

        $whereadd_lp

        order by productos.descripcion asc
    ),0) as precio_adicional,
    ";
    //echo $subtotal_ag;exit;

}



$idprod = intval($_GET['id']);
$total = 0;
if ($idprod > 0) {
    $consulta = "
    select tmp_ventares.*, productos.descripcion, cantidad
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idproducto = $idprod
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total = $rs->RecordCount();
    $idvt = intval($rs->fields['idventatmp']);
    if ($total == 1) {
        header("location: editareceta.php?idvt=$idvt");
        exit;
    }
}
$idvt = intval($_GET['idvt']);
if ($idvt > 0) {
    $consulta = "
    select tmp_ventares.*, productos.descripcion, cantidad
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idventatmp = $idvt
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idprod = $rs->fields['idproducto'];
}
if (intval($idprod) == 0 && intval($idvt) == 0) {
    header("location: index.php");
    exit;
}

// para saber si exise mas de 1
$consulta = "
    select tmp_ventares.*, productos.descripcion, cantidad,  productos.precio_abierto,
    productos.precio_min, productos.precio_max
    

    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idproducto = $idprod
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
//echo $consulta;
$rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalex = $rsex->RecordCount();


if (isset($_POST['observacion']) && trim($_POST['observacion']) != '') {
    if ($idvt > 0) {

        $observacion = antisqlinyeccion($_POST['observacion'], "text");
        $consulta = "
        update tmp_ventares
        set 
        observacion = $observacion
        where
        idventatmp = $idvt
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //header("location: editareceta.php?idvt=$idvt&ac=a");
        header("location: gest_ventas_resto_caja.php");
        exit;

    }

}

if (isset($_POST['precio_unitario']) && trim($_POST['precio_unitario']) != '') {
    if ($idvt > 0) {
        if ($rsex->fields['precio_abierto'] == 'S') {
            $valido = "S";
            $errores = "";

            $precio_new = floatval($_POST['precio_unitario']);
            $precio_min = floatval($rsex->fields['precio_min']);
            $precio_max = floatval($rsex->fields['precio_max']);


            $precio_new_txt = formatomoneda($precio_new, 4, 'N');
            $precio_min_txt = formatomoneda($precio_min, 4, 'N');
            $precio_max_txt = formatomoneda($precio_max, 4, 'N');

            if ($precio_new < $precio_min) {
                $valido = "N";
                $errores .= "- El precio unitario ($precio_new_txt) no puede ser menor al minimo ($precio_min_txt).<br />";
            }
            if ($precio_new > $precio_max) {
                $valido = "N";
                $errores .= "- El precio unitario ($precio_new_txt) no puede ser mayor al maximo ($precio_max_txt).<br />";
            }

            if ($valido == 'S') {


                $precio_unitario = antisqlinyeccion($_POST['precio_unitario'], "float");
                $consulta = "
                update tmp_ventares
                set 
                precio = $precio_unitario,
                descuento = 0,
                subtotal = cantidad*$precio_unitario
                where
                idventatmp = $idvt
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                //header("location: editareceta.php?idvt=$idvt&ac=a");
                header("location: gest_ventas_resto_caja.php");
                exit;

            }
        } else {
            echo "- Este producto no admite el uso de precio abierto.";
            exit;
        }

    }

}
?><!doctype html>
<html>
<head>
<title>Toma de Pedidos</title>
<?php require_once("includes/head_ventas.php");?>
<script>

function apretar(id,idprod,iding,prod1,prod2){
        //alert(id+'-'+idprod+'-'+iding);
        if(prod1 > 0){
            var precio = 0;
        }else{
            var html = document.getElementById("prod_"+id).innerHTML;
            var precio = document.getElementById("precio_"+id).value;
        }
        var parametros = {
                "idprod" : idprod,
                "idvt" : <?php echo $idvt; ?>,
                "iding": iding,
                "prod_1" : prod1,
                "prod_2" : prod2
        };
       $.ajax({
                data:  parametros,
                url:   'carrito_agregado.php',
                type:  'post',
                beforeSend: function () {
                        if(prod1 > 0){
                            //$("#lista_prod").html("Registrando...");
                        }else{
                            $("#prod_"+id).html("Registrando...");
                            $("#carrito").html("Actualizando Carrito...");
                        }
                },
                success:  function (response) {
                        if(prod1 > 0 && parseInt(response) > 0){
                            $("#lista_prod").html("Registrando...");
                            $("#carrito").html("Actualizando Carrito...");
                            document.location.href='index.php?cat=2';
                        }else{
                            $("#prod_"+id).html(html);
                            $("#contador_"+id).html(response);
                            actualiza_carrito();
                        }
                }
        });
    
}
function actualiza_carrito(){
        var parametros = {
                "act" : 'S'
        };
        $.ajax({
                data:  parametros,
                url:   'carrito_grilla_agregado.php?idvt=<?php echo $idvt; ?>',
                type:  'post',
                beforeSend: function () {
                        $("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
                        $("#carrito").html(response);
                }
        });
}
function borrar(idprod,iding,idvtag,txt){
            var parametros = {
                "idprod" : idprod,
                "iding" : iding,
                "idvt" : <?php echo $idvt; ?>,
                "idvtag" : idvtag
            };
    //if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){    
            $.ajax({
                    data:  parametros,
                    url:   'carrito_borra_agregado.php',
                    type:  'post',
                    beforeSend: function () {
                            $("#carrito").html("Actualizando Carrito...");
                    },
                    success:  function (response) {
                            $("#carrito").html(response);
                            // si existe el div de ese producto
                            if ($("#contador_"+idprod+'_'+iding).length > 0) {
                                var total = parseInt(document.getElementById('contador_'+idprod+'_'+iding).innerHTML);
                                var totalnuevo = total-1;
                                $("#contador_"+idprod+'_'+iding).html(totalnuevo);
                            }
                            
                    }
            });
    //}
}
</script>
</head>

<body>
<div class="cuerpo">
<?php

if ($total > 1) {
    $seleccionaing = "S";
    ?>
<!--<p align="center" onMouseUp="document.location.href='gest_ventas_resto_caja.php'"><strong><img src="tablet/gfx/iconos/atras.png" width="50"  alt="Favorito"/><br />
Volver</strong></p>-->
<table width="98%" border="1" class="categoria" bgcolor="#FFFFFF">
  <tbody>
    <tr>
      <td width="33%" align="center" bgcolor="#F8FFCC" onMouseUp="document.location.href='gest_ventas_resto_caja.php'"><strong><img src="tablet/gfx/iconos/atras.png" width="50"  alt="Favorito"/><br />
        Volver</strong></td>
      <td width="33%" align="center" >&nbsp;</td>
      <td width="33%" align="center"  >&nbsp;</td>
    </tr>
  </tbody>
</table>
<h1 align="center">Cambiar Ingredientes:</h1>
<p align="center">Existe mas de 1 pedido del mismo producto, seleccione el producto a Editar:</p>
<br />
<table width="98%" border="1" class="tablalinda">
  <tbody>
    <tr>
      <td bgcolor="#CCCCCC"><strong>Producto</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Agregados</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Eliminados</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Acciones</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $total = $rs->fields['precio'] * $rs->fields['total'];
    $totalacum += $total;

    $idvt = $rs->fields['idventatmp'];
    $consulta = "
select tmp_ventares_agregado.*,
tmp_ventares_agregado.precio_adicional*tmp_ventares_agregado.cantidad as precio_adicional
from tmp_ventares_agregado
where 
idventatmp = $idvt
order by alias desc
";
    $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
select tmp_ventares_sacado.*
from tmp_ventares_sacado
where 
idventatmp = $idvt
order by alias desc
";
    $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
    <tr>
      <td height="50"><?php echo Capitalizar($rs->fields['descripcion']); ?><?php
    if ($rs->fields['combinado'] == 'S') {

        $prod_1 = $rs->fields['idprod_mitad1'];
        $prod_2 = $rs->fields['idprod_mitad2'];
        $consulta = "
select *
from productos
where 
(idprod_serial = $prod_1 or idprod_serial = $prod_2)
order by descripcion asc
";
        $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rspcom->EOF) {

            ?><br /><span style="font-style:italic;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Mitad <?php echo Capitalizar($rspcom->fields['descripcion']); ?></span>
      <?php $rspcom->MoveNext();
        }
    } ?></td>
      <td align="center"><?php while (!$rsag->EOF) {?> 
      - <?php echo Capitalizar($rsag->fields['alias']); ?> (<?php echo formatomoneda($rsag->fields['precio_adicional']); ?>)<br />
      <?php $rsag->MoveNext();
      } ?>
      </td>
      <td align="center"><?php while (!$rssac->EOF) {?> 
      - Sin <?php echo Capitalizar($rssac->fields['alias']); ?><br />
      <?php $rssac->MoveNext();
      } ?></td>
      <td align="center"><input type="button" name="button2" id="button9" value="Seleccionar" class="boton" onClick="document.location.href='editareceta.php?idvt=<?php echo $rs->fields['idventatmp']; ?>'"></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<?php } else { ?>
<?php
    $consulta = "
SELECT agregado.idproducto, agregado.idingrediente, agregado.alias, 

$seladd

insumos_lista.descripcion, agregado.cantidad, medidas.nombre,
    (
    select count(idtmpventaresagregado)
    from tmp_ventares_agregado
    where
    idventatmp = $idvt
    and tmp_ventares_agregado.idingrediente = agregado.idingrediente
    and tmp_ventares_agregado.idproducto = agregado.idproducto
    ) as total
FROM agregado 
inner join ingredientes on ingredientes.idingrediente = agregado.idingrediente
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
inner join medidas on insumos_lista.idmedida=medidas.id_medida
WHERE
agregado.idproducto = $idprod
and insumos_lista.estado = 'A'
";
    //echo $consulta;
    $rsagregado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?><?php
    $ac = 'a';
    require_once("menu_ingredientes.php");
    ?><?php if (intval($rsagregado->fields['idproducto']) == 0) { ?><br /><br />
<h1 align="center">- No se cargaron agregados permitidos para este producto.</h1>
<br /><br />
<?php } ?>
<br />
<?php  while (!$rsagregado->EOF) {
    $img = "gfx/productos/agregado.jpg";

    ?>
<div id="prod_<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>" class="producto" onClick="apretar('<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>',<?php echo $rsagregado->fields['idproducto']; ?>,<?php echo $rsagregado->fields['idingrediente']; ?>,0,0);"><div class="contador" id="contador_<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>" ><?php echo intval($rsagregado->fields['total']); ?></div>
    <?php if (trim($rsagregado->fields['descripcion']) != '') { ?><img src="<?php echo $img ?>" height="81" width="163" border="0" alt="<?php echo $rsagregado->fields['alias']; ?>" title="<?php echo $rsagregado->fields['alias']; ?>" /><br /><?php echo $rsagregado->fields['alias']; ?><?php
        ?><br />Gs. <?php echo formatomoneda(trim($rsagregado->fields['precio_adicional'])); ?><input type="hidden" value="<?php echo $rsagregado->fields['precio_adicional']; ?>" name="precio_<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>" id="precio_<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>">
    <br /><?php } ?>
</div>
<?php  $rsagregado->MoveNext();
} ?>
<div class="clear"></div>
<br />
<?php if (trim($errores) != "") { ?><br />
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form action="editareceta.php?idvt=<?php echo intval($_GET['idvt']); ?>&ac=a" method="post">

Observacion: <input name="observacion" type="text" value="<?php echo antixss($rs->fields['observacion']); ?>"><input name="submit" type="submit" value="Guardar Observacion" >
</form>

<?php if ($rsex->fields['precio_abierto'] == 'S') { ?>

<br />
<form action="editareceta.php?idvt=<?php echo intval($_GET['idvt']); ?>&ac=a" method="post">
Precio Unitario (<?php echo formatomoneda($rsex->fields['precio_min']); ?> - <?php echo formatomoneda($rsex->fields['precio_max']); ?>): <input name="precio_unitario" type="number" value="<?php  if (isset($_POST['precio_unitario'])) {
    echo floatval($_POST['precio_unitario']);
} else {
    echo floatval($rs->fields['precio']);
}?>"  min="<?php echo floatval($rsex->fields['precio_min']); ?>" max="<?php echo floatval($rsex->fields['precio_max']); ?>"><input name="submit" type="submit" value="Cambiar Precio" >
</form>
<?php } ?>

  <?php } ?>
  <br />
<div class="clear"></div>


<br /><hr /><br />
</div>
<?php if ($seleccionaing <> 'S') { ?>
<div class="carrito" id="carrito">
<?php /*if($valido == "N"){ ?>
<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:600px; margin:0px auto; text-align:center;">
<strong>Errores:</strong><br />
<?php echo $errores; ?>
</div>
<?php }*/ ?>
<?php require_once("carrito_grilla_agregado.php"); ?>
</div>
<div class="clear"></div>
<?php } ?>
<div class="clear"></div>
</div>
</body>
</html>
