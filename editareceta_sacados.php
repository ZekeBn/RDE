 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idprod = intval($_GET['id']);
$idprod1 = 0;
$idprod2 = 0;
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
    $idprod1 = intval($rs->fields['idprod_mitad1']);
    $idprod2 = intval($rs->fields['idprod_mitad2']);
    $combinado = "N";
    if ($idprod1 > 0 && $idprod2 > 0) {
        $combinado = "S";
    }
}
if (intval($idprod) == 0 && intval($idvt) == 0) {
    header("location: index.php");
    exit;
}

// para saber si existe mas de 1
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
$rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalex = $rsex->RecordCount();

?><!doctype html>
<html>
<head>
<title>Toma de Pedidos</title>
<?php require_once("includes/head_ventas.php");?>
<script>
/*
$idventatmp=antisqlinyeccion($_POST['idvt'],"int");
$idproducto=antisqlinyeccion($_POST['prod'],"int");
$idingrediente=antisqlinyeccion($_POST['iding'],"int");
$precio_adicional=antisqlinyeccion($_POST['precioad'],"float");
$alias=antisqlinyeccion($_POST['alias'],"text");
$cantidad=antisqlinyeccion($_POST['cant'],"int");
*/
function apretar(id,idprod,iding,prod1,prod2){
        //alert(id+'-'+idprod+'-'+iding);
        if(prod1 > 0){
            var precio = 0;
        }else{
            var html = document.getElementById("prod_"+id).innerHTML;
            //var precio = document.getElementById("precio_"+id).value;
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
                url:   'carrito_sacado.php',
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
                    //alert(response);
                        if(prod1 > 0 && parseInt(response) > 0){
                            $("#lista_prod").html("Registrando...");
                            $("#carrito").html("Actualizando Carrito...");
                            document.location.href='editareceta_sacados.php?idvt=<?php echo $idvt; ?>&ac=e';
                        }else{
                            $("#prod_"+id).html(html);
                            $("#contador_"+id).html(response);
                            $("#prod_"+id).remove();
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
                url:   'carrito_grilla_sacado.php?idvt=<?php echo $idvt; ?>',
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
                    url:   'carrito_borra_sacado.php',
                    type:  'post',
                    beforeSend: function () {
                            $("#carrito").html("Actualizando Carrito...");
                    },
                    success:  function (response) {
                            document.location.href='editareceta_sacados.php?idvt=<?php echo $idvt;?>';                    
                            
                    }
            });
    //}
}
</script>
</head>

<body>
<div class="cuerpo">
<?php if ($total > 1) {
    $seleccionaing = "S";
    ?>
<?php } else { ?>
<?php
if ($combinado == 'S') {
    $whereadd = "
    (recetas_detalles.idprod = $idprod or recetas_detalles.idprod = $idprod1 or recetas_detalles.idprod = $idprod2)
    and recetas_detalles.ingrediente not in 
    (
        select tmp_ventares_sacado.idingrediente
        from tmp_ventares_sacado
        where
        idventatmp = $idvt
        and tmp_ventares_sacado.idingrediente = recetas_detalles.ingrediente
        and tmp_ventares_sacado.idproducto = recetas_detalles.idprod
    )
    ";
} else {
    $whereadd = "
    recetas_detalles.idprod = $idprod
    and recetas_detalles.ingrediente not in 
    (
        select tmp_ventares_sacado.idingrediente
        from tmp_ventares_sacado
        where
        idventatmp = $idvt
        and tmp_ventares_sacado.idingrediente = recetas_detalles.ingrediente
        and tmp_ventares_sacado.idproducto = recetas_detalles.idprod
    )
    and recetas_detalles.sacar = 'S'
    ";
}


        $consulta = "
SELECT recetas_detalles.idprod as idproducto, recetas_detalles.ingrediente as idingrediente, recetas_detalles.alias,  insumos_lista.descripcion, recetas_detalles.cantidad, medidas.nombre,
    (
    select count(idtmpventaressacado)
    from tmp_ventares_sacado
    where
    idventatmp = $idvt
    and tmp_ventares_sacado.idingrediente = recetas_detalles.ingrediente
    and tmp_ventares_sacado.idproducto = recetas_detalles.idprod
    ) as total
FROM recetas_detalles
inner join ingredientes on ingredientes.idingrediente = recetas_detalles.ingrediente
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
inner join medidas on insumos_lista.idmedida=medidas.id_medida
WHERE
$whereadd
and recetas_detalles.idempresa = $idempresa
and insumos_lista.idempresa= $idempresa
";
        //echo $consulta;
        $rsagregado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        ?><?php
        $ac = 'e';
        require_once("menu_ingredientes.php");
        ?><?php if (intval($rsagregado->fields['idproducto']) == 0) { ?>
<br /><br />
<h1 align="center">- No se cargaron excluidos permitidos para este producto.</h1>
<br /><br />
<?php } ?>
<br />
<?php  while (!$rsagregado->EOF) {
    $img = "gfx/productos/sacado.jpg";

    ?>
<div id="prod_<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>" class="producto" onClick="apretar('<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>',<?php echo $rsagregado->fields['idproducto']; ?>,<?php echo $rsagregado->fields['idingrediente']; ?>,<?php echo $idprod1; ?>,<?php echo $idprod2; ?>);"><div class="contador" id="contador_<?php echo $rsagregado->fields['idproducto'].'_'.$rsagregado->fields['idingrediente']; ?>" ><?php echo intval($rsagregado->fields['total']); ?></div>
    <?php if (trim($rsagregado->fields['descripcion']) != '') { ?><img src="<?php echo $img ?>" height="81" width="163" border="0" alt="<?php echo $rsagregado->fields['descripcion']; ?>" title="<?php echo $rsagregado->fields['descripcion']; ?>" /><br />SIN <?php echo $rsagregado->fields['descripcion']; ?><?php
        ?><br /><?php } ?>
</div>
<?php  $rsagregado->MoveNext();
} ?>

  <?php } ?>
  <br />
<div class="clear"></div>
<br /><hr /><br />
</div>
<?php if ($seleccionaing <> 'S') { ?>
<div class="carrito" id="carrito">
<?php if ($valido == "N") { ?>
<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:600px; margin:0px auto; text-align:center;">
<strong>Errores:</strong><br />
<?php echo $errores; ?>
</div>
<?php } ?>
<?php require_once("carrito_grilla_sacado.php"); ?>
</div>
<div class="clear"></div>
<?php } ?>
<div class="clear"></div>
</div>
</body>
</html>
