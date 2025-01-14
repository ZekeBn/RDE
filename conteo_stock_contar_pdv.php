 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "162";
require_once("includes/rsusuario.php");

require_once("includes/funciones_stock.php");

$idconteo = intval($_GET['id']);
if (intval($idconteo) == 0) {
    header("location: conteo_stock.php");
    exit;
}

// actualizar costeo
// update conteo_detalles set precio_costo = (select costo from insumos_lista where idinsumo = conteo_detalles.idinsumo) where precio_costo = 0;
//update conteo_detalles set diferencia_pc = diferencia*precio_costo WHERE diferencia_pc = 0;
//preferencias
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$afectastock_conteo_pdv = trim($rspref->fields['afectastock_conteo_pdv']);


$ahorad = date("Y-m-d");
$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo
from conteo
where
estado <> 6
and (estado = 1)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
and idempresa = $idempresa
and conteo.iniciado_por = $idusu
and conteo.fecha_inicio = '$ahorad'
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock_pdv.php");
    exit;
}

$consulta = "
select * from gest_depositos where iddeposito = $iddeposito limit 1
";
$rsdp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal = intval($rsdp->fields['idsucursal']);

//$fecha_inicio=date("Y-m-d");
$fecha_inicio = date("Y-m-d H:i:s", strtotime($rs->fields['inicio_registrado_el']));
$consulta = "
select *,
(SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) as grupo,
(SELECT nombre FROM medidas where id_medida = insumos_lista.idmedida) as medida,
(SELECT sum(disponible) FROM gest_depositos_stock_gral where idproducto = insumos_lista.idinsumo and iddeposito = $iddeposito and idempresa = $idempresa) as stock,
/*(
select sum(venta_receta.cantidad) as venta
from venta_receta 
inner join ventas on ventas.idventa = venta_receta.idventa
where 
venta_receta.idinsumo = insumos_lista.idinsumo  
and ventas.fecha >= '$fecha_inicio'
and (select iddeposito from gest_depositos where idsucursal = ventas.sucursal  and tiposala = 2) = $iddeposito
and ventas.estado <> 6
) as venta,*/
(select p1 from productos where idprod_serial = insumos_lista.idproducto and productos.borrado = 'N' and productos.idempresa = $idempresa) as pventa,
(select cantidad_contada from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as cantidad_contada
from insumos_lista 
where 
insumos_lista.idgrupoinsu in (SELECT idgrupoinsu FROM conteo_grupos where idconteo = $idconteo)
and insumos_lista.idempresa = $idempresa
and insumos_lista.estado = 'A'
order by (SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) asc, descripcion asc
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

/*
if(isset($_POST['accion']) && ($_POST['accion'] >= 1 && $_POST['accion'] <= 3)){
    $accion=intval($_POST['accion']);
    $sumavent=strtoupper(substr(trim($_POST['sumavent']),0,1));
    //echo "accion:".$accion."<br />";

    foreach($_POST as $key => $value){
        $idinsumo=intval(str_replace("cont_","",$key));
        $cantidad=$value;
        $cantidad_contada=$cantidad;
        //echo $accion;
        //exit;

        if(trim($cantidad) != '' && $idinsumo > 0){

            // por si hay texto en el campo cantidad
            $cantidad=floatval($cantidad);
            $cantidad_contada=$cantidad;

            // busca que exista el insumo
            $insumo=antisqlinyeccion($idinsumo,'text');
            $buscar="Select descripcion from insumos_lista where idinsumo=$idinsumo and idempresa = $idempresa";
            $rsin=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
            $descripcion=antisqlinyeccion($rsin->fields['descripcion'],'text');

            // stock disponible
            $consulta="
            select sum(disponible) as total_stock,
            (
            select productos_sucursales.precio
                from productos
            inner join insumos_lista on insumos_lista.idproducto  = productos.idprod_serial
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
            where
            gest_depositos_stock_gral.idproducto = insumos_lista.idinsumo
            and productos.idprod_serial = insumos_lista.idproducto
            and productos.borrado = 'N'
            and productos_sucursales.idsucursal = gest_depositos.idsucursal
            ) as pventa
            from gest_depositos_stock_gral
            inner join gest_depositos on gest_depositos.iddeposito = gest_depositos_stock_gral.iddeposito
            where
            gest_depositos_stock_gral.idproducto = $insumo
            and gest_depositos_stock_gral.iddeposito = $iddeposito
            and gest_depositos_stock_gral.idempresa = $idempresa
            ";
            $rsdisp=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            $disponible=floatval($rsdisp->fields['total_stock']);
            $pventa=floatval($rsdisp->fields['pventa']);
            $pcosto=0;
            $cantidad_sistema=$disponible;

            // busca si existe ese insumo en detalle para este conteo
            $consulta="
            select *
            from conteo_detalles
            where
            idconteo = $idconteo
            and idinsumo = $idinsumo
            and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo and idempresa = $idempresa)
            ";
            $rsex=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            //calculos
            $venta=floatval($rsdisp->fields['venta']);
            $cantidad_contada=$cantidad;
            $cantidad_teorica=floatval($disponible);
            $cantidad_teorica_cv=$cantidad_teorica+$venta;
            $diferencia=$cantidad_contada-$cantidad_teorica;
            $diferencia_cv=$cantidad_contada-$cantidad_teorica_cv;
            $cantidad_venta="0";
            if($sumavent == 'S'){
                $diferencia=$diferencia_cv;
                $cantidad_venta=$venta;
            }
            $precio_venta=$pventa;
            $precio_costo=$pcosto;
            $diferencia_pv=$diferencia*$precio_venta;


            // si no existe inserta
            if(intval($rsex->fields['idinsumo']) == 0){
                $consulta="
                insert into conteo_detalles
                (idconteo, idinsumo, descripcion, cantidad_contada, idusu, ubicacion, lote, vto, fechahora, cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv)
                values
                ($idconteo, $idinsumo, $descripcion, $cantidad_contada, $idusu, $iddeposito, NULL, NULL, '$ahora', $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv)
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            }else{
                // si existe actualiza
                $consulta="
                update conteo_detalles
                set
                    descripcion=$descripcion,
                    cantidad_contada=$cantidad_contada,
                    cantidad_sistema=$cantidad_sistema,
                    fechahora='$ahora',
                    precio_venta=$precio_venta,
                    precio_costo=$precio_costo,
                    diferencia=$diferencia,
                    diferencia_pv=$diferencia_pv
                where
                    idinsumo=$idinsumo
                    and idconteo=$idconteo
                    and    ubicacion=$iddeposito
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            }

            // Guardar
            if($accion == 1){

                // estado guardado
                $consulta="
                update conteo
                set
                estado = 2,
                ult_modif = '$ahora',
                sumoventa = '$sumavent'
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            }

        } // if(trim($cantidad) != '' && $idinsumo > 0){

    } //foreach($_POST as $key => $value){



    // redireccionar
    header("location: conteo_stock_pdv.php");
    exit;

}
*/

if (isset($_POST['accion']) && ($_POST['accion'] >= 1 && $_POST['accion'] <= 3)) {
    $accion = intval($_POST['accion']);
    $sumavent = strtoupper(substr(trim($_POST['sumavent']), 0, 1));
    //echo "accion:".$accion."<br />";
    if ($afectastock_conteo_pdv != 'S') {
        $accion = 1;
    }


    $consulta = "
    select * from conteo_detalles where idconteo = $idconteo
    ";
    $rs3 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //echo $consulta;
    //exit;
    while (!$rs3->EOF) {

        //foreach($_POST as $key => $value){
        //$idinsumo=intval(str_replace("cont_","",$key));
        $idinsumo = intval($rs3->fields['idinsumo']);
        //$cantidad=$value;
        $cantidad = floatval($rs3->fields['cantidad_contada']);
        $cantidad_contada = $cantidad;

        //echo $accion;
        //exit;

        if (trim($cantidad) != '' && $idinsumo > 0) {

            // por si hay texto en el campo cantidad
            $cantidad = floatval($cantidad);
            $cantidad_contada = $cantidad;

            // busca que exista el insumo
            $insumo = antisqlinyeccion($idinsumo, 'text');
            $buscar = "Select descripcion from insumos_lista where idinsumo=$idinsumo and idempresa = $idempresa";
            $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $descripcion = antisqlinyeccion($rsin->fields['descripcion'], 'text');

            // stock disponible
            $consulta = "
            select sum(disponible) as total_stock,
            (
            select productos_sucursales.precio
                from productos 
            inner join insumos_lista on insumos_lista.idproducto  = productos.idprod_serial
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
            where 
            gest_depositos_stock_gral.idproducto = insumos_lista.idinsumo
            and productos.idprod_serial = insumos_lista.idproducto
            and productos.borrado = 'N' 
            and productos_sucursales.idsucursal = gest_depositos.idsucursal
            ) as pventa,
            (select costo from insumos_lista where insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto) as pcosto
            from gest_depositos_stock_gral 
            inner join gest_depositos on gest_depositos.iddeposito = gest_depositos_stock_gral.iddeposito
            where 
            gest_depositos_stock_gral.idproducto = $insumo
            and gest_depositos_stock_gral.iddeposito = $iddeposito
            and gest_depositos_stock_gral.idempresa = $idempresa
            ";
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $disponible = floatval($rsdisp->fields['total_stock']);
            $pventa = floatval($rsdisp->fields['pventa']);
            $pcosto = floatval($rsdisp->fields['pcosto']);
            $cantidad_sistema = $disponible;

            // busca si existe ese insumo en detalle para este conteo
            $consulta = "
            select * 
            from conteo_detalles 
            where 
            idconteo = $idconteo
            and idinsumo = $idinsumo
            and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo and idempresa = $idempresa)
            ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //calculos
            $venta = floatval($rsdisp->fields['venta']);
            $cantidad_contada = $cantidad;
            $cantidad_teorica = floatval($disponible);
            $cantidad_teorica_cv = $cantidad_teorica + $venta;
            $diferencia = $cantidad_contada - $cantidad_teorica;
            $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
            $cantidad_venta = "0";
            if ($sumavent == 'S') {
                $diferencia = $diferencia_cv;
                $cantidad_venta = $venta;
            }
            $precio_venta = $pventa;
            $precio_costo = $pcosto;
            $diferencia_pv = $diferencia * $precio_venta;

            //echo $diferencia;
            //if($diferencia > 0){
            //    $cantidad_aumentar=$diferencia;
            //    echo "<br />Aum:".$cantidad_aumentar;
            //}
            //if($diferencia < 0){
            //    $cantidad_descontar=$diferencia*-1;
            //    echo "<br />Desc:".$cantidad_descontar;
            //}

            //exit;



            // si no existe inserta
            if (intval($rsex->fields['idinsumo']) == 0) {
                $consulta = "
                insert into conteo_detalles
                (idconteo, idinsumo, descripcion, cantidad_contada, idusu, ubicacion, lote, vto, fechahora, cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv)
                values
                ($idconteo, $idinsumo, $descripcion, $cantidad_contada, $idusu, $iddeposito, NULL, NULL, '$ahora', $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv)
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            } else {
                // si existe actualiza
                $consulta = "
                update conteo_detalles
                set
                    descripcion=$descripcion,
                    cantidad_contada=$cantidad_contada,
                    cantidad_sistema=$cantidad_sistema,
                    fechahora='$ahora',
                    precio_venta=$precio_venta, 
                    precio_costo=$precio_costo,
                    diferencia=$diferencia, 
                    diferencia_pv=$diferencia_pv
                where
                    idinsumo=$idinsumo
                    and idconteo=$idconteo
                    and    ubicacion=$iddeposito
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

            // Guardar
            if ($accion == 1) {

                // estado guardado
                $consulta = "
                update conteo 
                set 
                estado = 2,
                ult_modif = '$ahora',
                sumoventa = '$sumavent'
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }

            // Finalizar sin afectar stock
            if ($accion == 2) {

                // estado finalizado
                $consulta = "
                update conteo 
                set 
                estado = 3,
                ult_modif = '$ahora',
                fecha_final = '$ahora',
                final_registrado_el = '$ahora',
                finalizado_por = $idusu
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                and fecha_final is null
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }


            // Finalizar afectando stock
            if ($accion == 3) {

                // busca el costo del insumo
                $consulta = "select * from insumos_lista where idinsumo = $idinsumo and idempresa = $idempresa";
                $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ult_costo = floatval($rsnom->fields['costo']);

                // descontar si es menor a cero, si es igual a 0 no debe hacer nada
                if ($diferencia < 0) {
                    $cantidad_descontar = $diferencia * -1;
                    descontar_stock_general($idinsumo, $cantidad_descontar, $iddeposito);
                    descuenta_stock_inv($idinsumo, $cantidad_descontar, $iddeposito);
                    movimientos_stock($idinsumo, $cantidad_descontar, $iddeposito, 6, '-', $idconteo, $fecha_inicio);
                }
                // agregar si es mayor a cero, si es igual a 0 no debe hacer nada
                if ($diferencia > 0) {
                    $cantidad_aumentar = $diferencia;
                    aumentar_stock_general($idinsumo, $cantidad_aumentar, $iddeposito);
                    aumentar_stock($idinsumo, $cantidad_aumentar, $ult_costo, $iddeposito);
                    movimientos_stock($idinsumo, $cantidad_aumentar, $iddeposito, 6, '+', $idconteo, $fecha_inicio);
                }

                // estado finalizado
                $consulta = "
                update conteo 
                set 
                estado = 3,
                fecha_final = '$ahora',
                final_registrado_el = '$ahora',
                finalizado_por = $idusu,
                afecta_stock = 'S'
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                and fecha_final is null
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            }


        }
        //}
        $rs3->MoveNext();
    }



    // redireccionar
    header("location: conteo_stock_pdv.php");
    exit;

}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<script>
function accionbtn(cod){
    var totalpapel = $("#totalpapel").val();
    var totalpantalla = $("#totalpantalla").val();
    if(totalpapel == totalpantalla){
        if(totalpapel > 0){
            $("#accion").val(cod);    
            $("#form1").submit();
            $("#submit_1").hide();
            $("#submit_2").hide();
            $("#submit_3").hide();
        }else{
            alert("Debe cargar la suma que realizo manualmente en el papel.");    
        }
    }else{
        alert("No coinciden los totales entre el papel y lo cargado en el sistema.");
    }

}
function sumartodo(){
     var totinsu = parseFloat($("#totalinsu").val());
     var i = 0;
     var suma = 0;
     var instot = 0;
     //alert(totinsu);
     for(i=0;i<totinsu;i++){
        instot = parseFloat($("#cont_"+i).val());
         //var ht = $("#log").html()+'<br />';
        //var txt = ht+'instot:'+instot+'id:'+i;
        //$("#log").html(txt);
         if(instot > 0){
            suma = suma + instot;
            //alert();
            //alert('ins: '+instot+' tot: '+suma);
         }
     }
     suma = Math.round10(suma,-4);
     $("#totalpantalla").val(suma);
     //alert(suma);
}
function calcular_dif(id){
    /*coma_por_punto('cont_'+id);
    var stock = parseFloat($("#stock_"+id).val());
    var conteo = parseFloat($("#cont_"+id).val());
    var valido = 'S';
    // validar vacios
    if(isNaN(stock)){
        stock = 0;
        valido = 'N';
    }
    if(isNaN(conteo)){
        conteo = 0;
        valido = 'N';
    }
    if(valido == 'S'){
        var difer = conteo-stock;
        var difertxt = difer.toFixed(4);
        var difertxt = difertxt.replace('.',',');
        if(difer < 0){
            $("#dif_"+id).css('color','red');
        }else{
            $("#dif_"+id).css('color','black');
        }
        $("#dif_"+id).html(difertxt);
    }else{
        $("#dif_"+id).html('');
    }*/
    conteo_guarda_tmp(id);
}

/*
    function accionbtn(cod){
        //alert($("#tras_insumo").serialize());
        $("#accion").val(cod);
        $("#submit_1").hide();
        $("#submit_2").hide();
        $("#submit_3").hide();
       $.ajax({
                data:  $("#form1").serialize(),
                url:   'test.php',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        //$("#insumo_box").html("");
                        //$("#tmprodusmov").html('Cargando...');
                        $("#resp").html('Registrando...');
                },
                success:  function (response) {
                        //$("#tmprodusmov").html(response);
                        $("#resp").html(response);
                }
        });
        
    }*/
function conteo_guarda_tmp(id){
    var campo_cant = "cont_"+id;
    var campo_name = $('#'+campo_cant).attr('name');
    var idprod_s = campo_name.split("_");
    var idprod = idprod_s[1];
    //alert(idprod);
    
    var cantidad = $("#"+campo_cant).val();
        var parametros = {
                    "accion" : 1,
                    "cant" : cantidad,
                    "idprod" : idprod,
                    "id" : <?php echo $idconteo; ?>
                    
           };
          $.ajax({
                    data:  parametros,
                    url:   'conteo_guarda_tmp_pdv.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#dif_"+id).html('Guardando...');
                    },
                    success:  function (response) {
                        $("#dif_"+id).html(response);
                        sumartodo();
                    }
            });    

}
function inicio(){
    //setInterval('mantiene_session()',<?php echo $refresca_cada_miliseg_js; ?>);
}
function mantiene_session(){
    var f=new Date();
    cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds(); 
    var parametros = {
                "ses" : cad,
       };
      $.ajax({
                data:  parametros,
                url:   'mantiene_session.php',
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
                    //alert(response);
                }
        });    
}
function buscar_producto_codbar(e){
    
    // que tecla presiono
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla==13){
        var codbar = $("#codbar").val();
        var direccionurl='conteo_stock_filtra_pdv.php';        
        var parametros = {
          "codbar"   : codbar,
          "id"       : <?php echo $idconteo; ?>
        };
        $.ajax({          
            data:  parametros,
            url:   direccionurl,
            type:  'post',
            beforeSend: function () {
                $("#filtroprod").html('Cargando...');                
            },
            success:  function (response) {
                $("#filtroprod").html(response);
                if (tecla==13){
                    $("#cont_1").focus();
                }
            }
        });
    }
}
(function() {
  /**
   * Ajuste decimal de un n�mero.
   *
   * @param {String}  tipo  El tipo de ajuste.
   * @param {Number}  valor El numero.
   * @param {Integer} exp   El exponente (el logaritmo 10 del ajuste base).
   * @returns {Number} El valor ajustado.
   */
  function decimalAdjust(type, value, exp) {
    // Si el exp no est� definido o es cero...
    if (typeof exp === 'undefined' || +exp === 0) {
      return Math[type](value);
    }
    value = +value;
    exp = +exp;
    // Si el valor no es un n�mero o el exp no es un entero...
    if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
      return NaN;
    }
    // Shift
    value = value.toString().split('e');
    value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
    // Shift back
    value = value.toString().split('e');
    return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
  }

  // Decimal round
  if (!Math.round10) {
    Math.round10 = function(value, exp) {
      return decimalAdjust('round', value, exp);
    };
  }
  // Decimal floor
  if (!Math.floor10) {
    Math.floor10 = function(value, exp) {
      return decimalAdjust('floor', value, exp);
    };
  }
  // Decimal ceil
  if (!Math.ceil10) {
    Math.ceil10 = function(value, exp) {
      return decimalAdjust('ceil', value, exp);
    };
  }
})();
</script>
</head>
<body bgcolor="#FFFFFF" onload="sumartodo();">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">

           <div align="center">
            <table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="conteo_stock_pdv.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Conteo de Stock</span>
                </div>

<p align="center">&nbsp;</p>
<p align="center">&nbsp;</p>
<table width="900" border="1">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong># Conteo</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Deposito</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Estado</strong></td>
      </tr>
    <tr>
      <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
      </tr>
  </tbody>
</table>
<p align="center">&nbsp;</p>
<div id="resp"></div>


<div id="filtroprod">
<?php require_once("conteo_stock_filtra_pdv.php"); ?>
</div>

<input type="hidden" name="accion" id="accion" value="0" />
<input type="hidden" name="totalinsu" id="totalinsu" value="<?php echo $i; ?>" />
<table width="900" border="0">
  <tbody>
    <tr>
      <td width="200" align="center">&nbsp;</td>
      <td width="200" align="left">Total Contabilizado Papel: 
        <input type="text" name="totalpapel" id="totalpapel" /></td>
      <td width="200" align="left">Total Contabilizado Pantalla:
        <input type="text" name="totalpantalla" id="totalpantalla" readonly="readonly" style="background-color:#CCC; border:#FFFFFF; text-align:right;" value="0" /></td>
    </tr>
  </tbody>
</table>
<br />
</form>
<p>&nbsp;</p>
<table width="900" border="0">
  <tbody>
    <tr>
      <td align="center"><input type="submit" name="submit" id="submit_1" value="Guardar" style="width:200px;" onmouseup="accionbtn(1);" /></td>
      <?php if ($afectastock_conteo_pdv == 'S') { ?>
      <td align="center"><input type="submit" name="submit_" id="submit_3" value="Finalizar y Afectar Stock" style="width:200px;" onmouseup="accionbtn(3);" /></td>
      <?php } ?>
      </tr>
  </tbody>
</table><br /><br />

<p align="center" id="log">&nbsp;</p>
          </div> 
            <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
