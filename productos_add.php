<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

require_once("includes/funciones_articulos.php");

/*--------------------------------------------CONTROL P/ DELIVERY ------------------------------------*/
// busca si existe el producto descuento debe estar borrado
$consulta = "
select idprod_serial from productos where idtipoproducto = 8 limit 1
";
$rsdesc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "Select * from preferencias_productos limit 1";
$rsprefprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;exit;
$usa_precio_abierto = trim($rsprefprod->fields['usa_precio_abierto']);
$usa_costo_referencial = trim($rsprefprod->fields['usa_costo_referencial']);
$tipo_precio_defecto = trim($rsprefprod->fields['tipo_precio_defecto']);
$muestra_web_auto = trim($rsprefprod->fields['muestra_web_auto']);//Marca el selector en Base a la preferencias del producto
$habilita_compra = trim($rsprefprod->fields['habilita_compra_auto']);//Marca el selector en Base a la preferencias del producto
if ($habilita_compra == 'S') {
    $habilita_compra_auto = 1;
} else {
    $habilita_compra_auto = 0;
}
//echo 'PA'.$usa_precio_abierto;
//exit;
// si no existe crea
if (intval($rsdesc->fields['idprod_serial']) == 0) {

    $parametros_array = [
        'idtipoproducto' => 8,
        'barcode' => '',
        'codplu' => '',
        'descripcion' => '*',
        'descripcion_larga' => '',
        'precio_venta' => 0,
        'costo' => 0,
        'idcategoria' => 1,
        'idsubcate' => 1,
        'idmedida' => 2,
        'idproveedor' => '',
        'precio_min' => '',
        'precio_max' => '',
        'lista_precios' => '',
        'idtipoiva' => 1,
        'idtipoiva_compra' => 1,
        'promo' => '',
        'favorito' => 'N',
        'idmarca' => '',
        'combinado_maxitem' => '',
        'combinado_tipoprecio' => '',
        'combinado_minitem' => '',
        'web_muestra' => 'N',
        'muestra_self' => 'N',
        'muestra_vianda' => 'N',
        'muestra_pedido' => 'N',
        'excluye_reporteventa' => 'N',
        'idimpresoratk' => '',

        'idconcepto' => 1,
        'paquete' => '',
        'cant_paquete' => '',
        'idgrupoinsu' => 1,
        'ajuste' => 'N',
        'hab_compra' => 0,
        'hab_invent' => 0,
        'acepta_devolucion' => 'N',
        'aplica_regalia' => 'S',
        'solo_conversion' => 'N',
        'respeta_precio_sugerido' => 'S',
        'idcentroprod' => '',
        'idubicacion' => '',
        'idbandeja' => '',
        'idorden' => '',
        'idpasillo' => '',
        'stock_minimo' => '',
        'stock_ideal' => '',


        'idubicacion_suc' => '',
        'idbandeja_suc' => '',
        'idorden_suc' => '',
        'idpasillo_suc' => '',
        'stock_minimo_suc' => '',
        'stock_ideal_suc' => '',

        'registrado_por' => $idusu,
        'registrado_el' => $ahora,

    ];

    //print_r($_POST);exit;

    $res = validar_producto($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = "N";
        $errores .= $res['errores'];
        echo $errores;
        exit;
    } else {
        $res = agregar_producto($parametros_array);
        $idproducto = $res['idproducto'];
        $idinsumo = $res['idinsumo'];

        $consulta = "
        update productos 
        set 
        borrado = 'S',
        borrado_por=0,
        borrado_el='2000-01-01'
        where 
        idprod_serial=$idproducto 
        and borrado = 'N'
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        update insumos_lista
        set 
        estado = 'I',
        borrado_por=0,
        borrado_el='2000-01-01'
        where
        idproducto = $idinsumo
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


}
/*------------------------------------FIN CONTROL P/ DELIVERY ------------------------------------*/

$usa_concepto = $rsco->fields['usa_concepto'];
$idtipoiva_venta_pred = $rsco->fields['idtipoiva_venta_pred'];
$idtipoiva_compra_pred = $rsco->fields['idtipoiva_compra_pred'];

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots
    $precio_abierto = antisqlinyeccion($_POST['precio_abierto'], "text");
    if ($_POST['precio_abierto'] == 'S') {
        if (floatval($_POST['precio_min']) < 0) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser negativo.<br />";
        }
        if (floatval($_POST['precio_max']) <= 0) {
            $valido = "N";
            $errores .= " - El campo precio max no puede ser cero o negativo.<br />";
        }
        if (floatval($_POST['precio_min']) > floatval($_POST['precio_max'])) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser mayor al precio max.<br />";
        }
    }
    $parametros_array = [
        'idtipoproducto' => $_POST['idtipoproducto'],
        'barcode' => $_POST['barcode'],
        'codplu' => $_POST['codplu'],
        'descripcion' => $_POST['descripcion'],
        'descripcion_larga' => $_POST['descripcion_larga'],
        'precio_venta' => $_POST['p1'],
        'costo' => $_POST['costo_actual'],
        'idcategoria' => $_POST['idcategoria'],
        'idsubcate' => $_POST['idsubcate'],
        'idmedida' => $_POST['idmedida'],
        'idproveedor' => $_POST['idproveedor'],
        'precio_abierto' => $_POST['precio_abierto'],
        'precio_min' => $_POST['precio_min'],
        'precio_max' => $_POST['precio_max'],
        'lista_precios' => $_POST['lista_precios'],
        'idtipoiva' => $_POST['idtipoiva'],
        'idtipoiva_compra' => $_POST['idtipoiva_compra'],
        'promo' => $_POST['promo'],
        'favorito' => 'N',
        'idmarca' => $_POST['idmarca'],
        'combinado_maxitem' => $_POST['combinado_maxitem'],
        'combinado_tipoprecio' => $_POST['combinado_tipoprecio'],
        'combinado_minitem' => $_POST['combinado_minitem'],
        'web_muestra' => $_POST['web_muestra'],
        'muestra_self' => $_POST['muestra_self'],
        'muestra_vianda' => $_POST['muestra_vianda'],
        'muestra_pedido' => $_POST['muestra_pedido'],
        'excluye_reporteventa' => $_POST['excluye_reporteventa'],
        'idimpresoratk' => $_POST['idimpresoratk'],

        'idconcepto' => $_POST['idconcepto'],
        'paquete' => $_POST['paquete'],
        'cant_paquete' => $_POST['cant_paquete'],
        'idgrupoinsu' => $_POST['idgrupoinsu'],
        'ajuste' => $_POST['ajuste'],
        'hab_compra' => $_POST['hab_compra'],
        'hab_invent' => $_POST['hab_invent'],
        'acepta_devolucion' => $_POST['acepta_devolucion'],
        'aplica_regalia' => $_POST['aplica_regalia'],
        'solo_conversion' => $_POST['solo_conversion'],
        'respeta_precio_sugerido' => $_POST['respeta_precio_sugerido'],
        'idcentroprod' => $_POST['cpr'],
        'idubicacion' => $_POST['idubicacion'],
        'idbandeja' => $_POST['idbandeja'],
        'idorden' => $_POST['idorden'],
        'idpasillo' => $_POST['idpasillo'],
        'stock_minimo' => $_POST['stock_minimo'],
        'stock_ideal' => $_POST['stock_ideal'],
        'idplancuentadet_compra' => $_POST['cuentacont'],
        'idplancuentadet_venta' => $_POST['cuentacont_ven'],
        'idagrupacionprod' => $_POST['idagrupacionprod'],

        'idmedida_referencial' => $_POST['idmedida_referencial'],
        'cantidad_referencial' => $_POST['cantidad_referencial'],
        'idprodexterno' => $_POST['idprodexterno'],
        'recargo_auto_costo' => $_POST['recargo_auto_costo'],


        'idubicacion_suc' => $_POST['idubicacion_suc'],
        'idbandeja_suc' => $_POST['idbandeja_suc'],
        'idorden_suc' => $_POST['idorden_suc'],
        'idpasillo_suc' => $_POST['idpasillo_suc'],
        'stock_minimo_suc' => $_POST['stock_minimo_suc'],
        'stock_ideal_suc' => $_POST['stock_ideal_suc'],

        'registrado_por' => $idusu,
        'registrado_el' => $ahora,

    ];

    //print_r($_POST);exit;

    $res = validar_producto($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = "N";
        $errores .= $res['errores'];
    }



    // si todo es correcto inserta
    if ($valido == "S") {

        $res = agregar_producto($parametros_array);
        $idproducto = $res['idproducto'];
        $idinsumo = $res['idinsumo'];

        //Por ultimo si usa contabilidad es si, y envio el cod de articulo contable,almacenamos en la tabla

        $codarticulocontable = intval($_POST['cuentacont']);
        if ($codarticulocontable > 0) {
            //traemos los datos del plan de cuentas activo
            $buscar = "Select * from cn_plancuentas_detalles where idserieun=$codarticulocontable and estado <> 6";
            $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idplan = intval($rsvv->fields['idplan']);
            $idsercuenta = intval($rsvv->fields['idserieun']);

            $insertar = "Insert into cn_articulos_vinculados
            (idinsumo,idplancuenta,idsercuenta,vinculado_el,vinculado_por) 
            values 
            ($idinsumo,$idplan,$idsercuenta,current_timestamp,$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        }





        //header("location: gest_listado_productos.php?idp=".$idproducto);
        header("location: productos_add.php?idp=".$idproducto);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


//echo $tipo_precio_defecto;exit;
// El tipo de aumento debe ser o porcentaje o cantidad, no ambos, si tiene definido ambos, tomamos como prioridad el porcentaje
$porc_aumento_costo = floatval($rsprefprod->fields['porc_aumento_costo']);
$cantidad_veces_pcosto = intval($rsprefprod->fields['cantidad_veces_pcosto']);
if (floatval($porc_aumento_costo) > 0 && ($cantidad_veces_pcosto > 0)) {
    $cantidad_veces_pcosto = 0;//solo tomaremos el porc
    $claseau = 'P';
} else {
    if (floatval($porc_aumento_costo) > 0) {
        $claseau = 'P';
    }
    if (intval($cantidad_veces_pcosto) > 0) {
        $claseau = 'C';
    }

}
$cr = 0;
$pmin = 0;
$pmax = 0;
if ($claseau == 'P') {
    if ($cr > 0) {
        $pmin = $cr;
        $val = floatval($porc_aumento_costo / 100);
        $pmax = ($cr * $val) + $cr;

    }
}
if ($claseau == 'C') {
    if ($cr > 0) {
        $pmin = $cr;
        $pmax = $cr * $cantidad_veces_pcosto;

    }
}


$consulta = "Select usa_recargo_precio_costo from preferencias_caja limit 1";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function subcategorias(idcategoria){
    var direccionurl='subcate_new.php';    
    var parametros = {
      "idcategoria" : idcategoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#subcatebox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#subcatebox").html(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
}
function tipo_producto(idtipoproducto){
    //producto
    if(idtipoproducto == 1){    

    }
    // combo
    if(idtipoproducto == 2){    
    
    }
    // combinado
    if(idtipoproducto == 3){    
        $("#div_combinado_tipoprecio").show();
    }
    // combinado extendido
    if(idtipoproducto == 4){    
        $("#div_combinado_minitem").show();
        $("#div_combinado_maxitem").show();
        $("#div_combinado_tipoprecio").show();    
    }else{
        $("#div_combinado_minitem").hide();
        $("#div_combinado_maxitem").hide();
        if(idtipoproducto != 3){
            $("#div_combinado_tipoprecio").hide();    
        }
    }
    // agregado
    if(idtipoproducto == 5){    
    
    }
    // delivery
    if(idtipoproducto == 6){    
    
    }    
    // servicio
    if(idtipoproducto == 7){    
    
    }    
    
    
}
function alerta_modal(titulo,mensaje){
    $('#dialogobox').modal('show');
    $("#myModalLabel").html(titulo);
    $("#modal_cuerpo").html(mensaje);

    
}
function ventana_categoria(){
    var direccionurl='categoria_prod_add.php';    
    var parametros = {
      "add"        : 'N'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function ventana_grupostock(){
    var direccionurl='grupo_stock_addmini.php';    
    var parametros = {
      "add"        : 'N'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Nuevo grupo stock');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
            
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function ventana_subcategoria(){
    var direccionurl='subcategoria_prod_add.php';    
    var parametros = {
      "add"        : 'N'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Sub-Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function agregar_categoria(){
    var direccionurl='categoria_prod_add.php';
    var categoria = $("#categoria").val();    
    var parametros = {
      "add"        : 'S',
      "categoria"  : categoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                recargar_categoria(obj.idcategoria);
                $("#modal_cuerpo").html('');
                $('#dialogobox').modal('hide');

            }else{
                $("#modal_cuerpo").html(response);    
            }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function agregar_grupostock(){
    var direccionurl='grupo_stock_addmini.php';
    var gruinsu = $("#gruinsu").val();

    var parametros = {
      "add"        : 'S',
      "grupo"  : gruinsu
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Grupo Stock - Agregando');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                recargar_grupostock(obj.idgrupoinsu);
                
                $("#modal_cuerpo").html('');
                $('#dialogobox').modal('hide');

            }else{
                $("#modal_cuerpo").html(response);    
            }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function agregar_subcategoria(){
    var direccionurl='subcategoria_prod_add.php';
    var categoria = $("#categoria").val();    
    var subcategoria = $("#subcategoria").val();
    var parametros = {
      "add"        : 'S',
      "categoria"  : categoria,
      "subcategoria"  : subcategoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Sub-Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                recargar_categoria(obj.idcategoria);
                recargar_subcategoria(obj.idcategoria,obj.idsubcategoria);
                $("#modal_cuerpo").html('');
                $('#dialogobox').modal('hide');

            }else{
                $("#modal_cuerpo").html(response);    
            }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function recargar_categoria(idcategoria){
    var direccionurl='cate_new.php';
    var parametros = {
      "idcategoria" : idcategoria,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {    
            $("#categoriabox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#categoriabox").html(response);    
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function recargar_grupostock(idgrupoinsu){
    var direccionurl='mini_lista_grupostk.php';
    var parametros = {
      "idgrupoinsu" : idgrupoinsu,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {    
            $("#grupostocklista").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#grupostocklista").html(response);    
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function recargar_subcategoria(idcategoria,idsubcategoria){
    var direccionurl='subcate_new.php';
    var parametros = {
      "idcategoria" : idcategoria,
      "idsubcate" : idsubcategoria,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {    
            $("#subcatebox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#subcatebox").html(response);    
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar Producto</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<strong>Informacion Basica:</strong>
<hr />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Producto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
        echo htmlentities($_POST['descripcion']);
    } else {
        echo htmlentities($rs->fields['descripcion']);
    }?>" placeholder="Producto" class="form-control" required autofocus />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio venta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="p1" id="p1" value="<?php  if (isset($_POST['p1'])) {
        echo floatval($_POST['p1']);
    } else {
        echo floatval($rs->fields['p1']);
    }?>" placeholder="P1" class="form-control" required />                    
    </div>
</div>
<?php

if ($usa_precio_abierto == 'S') {
    ?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Abierto </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
        // valor seleccionado
        if (isset($_POST['precio_abierto'])) {
            $value_selected = htmlentities($_POST['precio_abierto']);
        } else {
            if ($tipo_precio_defecto == 'T') {
                $value_selected = $rsminip->fields['precio_abierto'];
            } else {
                $value_selected = 'S';
            }
        }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'precio_abierto',
        'id_campo' => 'precio_abierto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" '.$disabled,
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);  ?>                  
    </div>
</div>


<?php if ($usa_costo_referencial == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Costo Referencial </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="costo_referencial" id="costo_referencial" value="<?php  if (isset($_POST['costo_referencial'])) {
        echo floatval($_POST['costo_referencial']);
    } else {
        echo floatval($rsinsumo->fields['costo_referencial']);
    }?>" placeholder="Costo Referencial" onkeyup="calcularcosto();" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>
<div class="clearfix"></div>
<?php } ?>




<div class="clearfix"></div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio abierto min </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="precio_min" id="precio_min" value="<?php  if (isset($_POST['precio_min'])) {
        echo floatval($_POST['precio_min']);
    } else {
        echo floatval($pmin);
    }?>" placeholder="Precio min" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio abierto max </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="precio_max" id="precio_max" value="<?php  if (isset($_POST['precio_max'])) {
        echo floatval($_POST['precio_max']);
    } else {
        echo floatval($pmax);
    }?>" placeholder="Precio max"  class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>
<?php } ?>
<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_categoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Categoria * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
require_once("cate_new.php");

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_subcategoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Subcategoria *</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
<?php
require_once("subcate_new.php");

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Medida </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT id_medida, nombre
FROM medidas
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idmedida'])) {
    $value_selected = htmlentities($_POST['idmedida']);
} else {
    $value_selected = 4;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmedida',
    'id_campo' => 'idmedida',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_medida',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">IVA Venta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
    // consulta
    $consulta = "
    SELECT idtipoiva, iva_porc, iva_describe
    FROM tipo_iva
    where
    estado = 1
    and hab_venta = 'S'
    order by iva_porc desc
     ";

// valor seleccionado
if (isset($_POST['idtipoiva'])) {
    $value_selected = htmlentities($_POST['idtipoiva']);
} else {
    $value_selected = $idtipoiva_venta_pred;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoiva',
    'id_campo' => 'idtipoiva',

    'nombre_campo_bd' => 'iva_describe',
    'id_campo_bd' => 'idtipoiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">IVA Compra *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
    SELECT idtipoiva, iva_porc, iva_describe
    FROM tipo_iva
    where
    estado = 1
    and hab_compra = 'S'
    order by iva_porc desc
     ";

// valor seleccionado
if (isset($_POST['idtipoiva_compra'])) {
    $value_selected = htmlentities($_POST['idtipoiva_compra']);
} else {
    $value_selected = $idtipoiva_compra_pred;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoiva_compra',
    'id_campo' => 'idtipoiva_compra',

    'nombre_campo_bd' => 'iva_describe',
    'id_campo_bd' => 'idtipoiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    
    <label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onclick="ventana_grupostock();" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Agregar"><span class="fa fa-plus"></span></a>Grupo Stock *</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="grupostocklista">
            <?php  require_once("mini_lista_grupostk.php"); ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Producto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipoproducto, tipoproducto
FROM productos_tipo
where 
estado_tipo = 1
order by idtipoproducto asc
 ";

// valor seleccionado
if (isset($_POST['idtipoproducto'])) {
    $value_selected = htmlentities($_POST['idtipoproducto']);
} else {
    $value_selected = 1;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoproducto',
    'id_campo' => 'idtipoproducto',

    'nombre_campo_bd' => 'tipoproducto',
    'id_campo_bd' => 'idtipoproducto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="tipo_producto(this.value);" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>
<?php
$contabilidad = intval($rsco->fields['contabilidad']);
if ($contabilidad == 1) {
    ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable (Compra) *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
SELECT idserieun, descripcion
FROM cn_plancuentas_detalles
where 
estado<>6 
and asentable='S' 
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['cuentacont'])) {
        $value_selected = htmlentities($_POST['cuentacont']);
    } else {
        $value_selected = htmlentities($rsinsumo->fields['idplancuentadet']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'cuentacont',
        'id_campo' => 'cuentacont',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idserieun',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  '.$disabled,
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
    </div>
</div>
<?php /*?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable (Venta) </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta="
SELECT cuenta, descripcion
FROM cn_plancuentas_detalles
where
estado<>6
and asentable='S'
order by idserieun asc
 ";

// valor seleccionado
if(isset($_POST['cuentacont'])){
    $value_selected=htmlentities($_POST['cuentacont_ven']);
}else{
    $value_selected=htmlentities($rsminip->fields['idplancuentadet']);
}

// parametros
$parametros_array=array(
    'nombre_campo' => 'cuentacont_ven',
    'id_campo' => 'cuentacont_ven',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'cuenta',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  '.$disabled,
    'autosel_1registro' => 'N'

);

// construye campo
echo campo_select($consulta,$parametros_array);

?>
    </div>
</div>
<?php */ ?>
<?php } ?>

<div class="col-md-6 col-sm-6 form-group" id="div_combinado_minitem" <?php if ($_POST['idtipoproducto'] != 4) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Min Piezas/Items *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="combinado_minitem" id="combinado_minitem" value="<?php  if (isset($_POST['combinado_minitem'])) {
        echo intval($_POST['combinado_minitem']);
    } else {
        echo intval($rs->fields['combinado_minitem']);
    }?>" placeholder="Combinado minitem" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="div_combinado_maxitem" <?php if ($_POST['idtipoproducto'] != 4) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Max Piezas/Items *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="combinado_maxitem" id="combinado_maxitem" value="<?php  if (isset($_POST['combinado_maxitem'])) {
        echo intval($_POST['combinado_maxitem']);
    } else {
        echo intval($rs->fields['combinado_maxitem']);
    }?>" placeholder="Combinado maxitem" class="form-control"  />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group"  id="div_combinado_tipoprecio" <?php if ($_POST['idtipoproducto'] != 3 && $_POST['idtipoproducto'] != 4) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Precio *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['combinado_tipoprecio'])) {
    $value_selected = htmlentities($_POST['combinado_tipoprecio']);
} else {
    $value_selected = 1;
}
// opciones
$opciones = [
    'Promedio' => 1,
    'Mayor' => 2,
    'Definido' => 3,
];
// parametros
$parametros_array = [
    'nombre_campo' => 'combinado_tipoprecio',
    'id_campo' => 'combinado_tipoprecio',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita compra *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['hab_compra'])) {
    $value_selected = htmlentities($_POST['hab_compra']);
} else {
    $value_selected = $habilita_compra_auto;
}
// opciones
$opciones = [
    'SI' => 1,
    'NO' => 0
];
// parametros
$parametros_array = [
    'nombre_campo' => 'hab_compra',
    'id_campo' => 'hab_compra',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita inventario *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['hab_invent'])) {
    $value_selected = htmlentities($_POST['hab_invent']);
} else {
    $value_selected = 1;
}
// opciones
$opciones = [
    'SI' => 1,
    'NO' => 0
];
// parametros
$parametros_array = [
    'nombre_campo' => 'hab_invent',
    'id_campo' => 'hab_invent',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>

    </div>
</div>

<div class="clearfix"></div>
<br />
<strong>Informacion Adicional:</strong>
<hr />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion larga </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="descripcion_larga" id="descripcion_larga" value="<?php  if (isset($_POST['descripcion_larga'])) {
        echo htmlentities($_POST['descripcion_larga']);
    } else {
        echo htmlentities($rs->fields['descripcion_larga']);
    }?>" placeholder="Descripcion larga" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Costo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="costo_actual" id="costo_actual" value="<?php  if (isset($_POST['costo_actual'])) {
        echo floatval($_POST['costo_actual']);
    } else {
        echo floatval($rs->fields['costo_actual']);
    }?>" placeholder="Costo actual" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de Barras </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="barcode" id="barcode" value="<?php  if (isset($_POST['barcode'])) {
        echo htmlentities($_POST['barcode']);
    } ?>" placeholder="Barcode" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Pesable </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="codplu" id="codplu" value="<?php  if (isset($_POST['codplu'])) {
        echo intval($_POST['codplu']);
    } ?>" placeholder="Codplu" class="form-control"  />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
SELECT idproveedor, nombre
FROM proveedores
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($rs->fields['idproveedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en Tienda WEB*</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['web_muestra'])) {
    $value_selected = htmlentities($_POST['web_muestra']);
} else {

    $value_selected = $muestra_web_auto;
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'web_muestra',
    'id_campo' => 'web_muestra',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar Self Service *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['muestra_self'])) {
    $value_selected = htmlentities($_POST['muestra_self']);
} else {
    $value_selected = 'S';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_self',
    'id_campo' => 'muestra_self',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en vianda *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['muestra_vianda'])) {
    $value_selected = htmlentities($_POST['muestra_vianda']);
} else {
    $value_selected = 'S';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_vianda',
    'id_campo' => 'muestra_vianda',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en Menu Digital *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['muestra_pedido'])) {
    $value_selected = htmlentities($_POST['muestra_pedido']);
} else {
    $value_selected = 'S';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_pedido',
    'id_campo' => 'muestra_pedido',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Marca </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idmarca, marca
FROM marca
where
idestado = 1
order by marca asc
 ";

// valor seleccionado
if (isset($_POST['idmarca'])) {
    $value_selected = htmlentities($_POST['idmarca']);
} else {
    $value_selected = htmlentities($rs->fields['idmarca']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmarca',
    'id_campo' => 'idmarca',

    'nombre_campo_bd' => 'marca',
    'id_campo_bd' => 'idmarca',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>




<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Excluye reporte venta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['excluye_reporteventa'])) {
    $value_selected = htmlentities($_POST['excluye_reporteventa']);
} else {
    $value_selected = 0;
}
// opciones
$opciones = [
    'SI' => 1,
    'NO' => 0
];
// parametros
$parametros_array = [
    'nombre_campo' => 'excluye_reporteventa',
    'id_campo' => 'excluye_reporteventa',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<?php if ($usa_concepto == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idconcepto, descripcion
FROM cn_conceptos
where
estado = 1
and idconcepto > 1
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['idconcepto'])) {
        $value_selected = htmlentities($_POST['idconcepto']);
    } else {
        $value_selected = htmlentities($rs->fields['idconcepto']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idconcepto',
        'id_campo' => 'idconcepto',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idconcepto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);
    ?>
    </div>
</div>
<?php } ?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Comanda Asignada </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
    // consulta
    $consulta = "
SELECT idimpresoratk, descripcion
FROM impresoratk
where
borrado = 'N'
and tipo_impresora = 'COC'
and idsucursal = $idsucursal
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idimpresoratk'])) {
    $value_selected = htmlentities($_POST['idimpresoratk']);
} else {
    $value_selected = htmlentities($rs->fields['idimpresoratk']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idimpresoratk',
    'id_campo' => 'idimpresoratk',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idimpresoratk',

    'value_selected' => $value_selected,

    'pricampo_name' => 'NINGUNA',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Medida Referencial </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT id_medida, nombre
FROM medidas
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idmedida_referencial'])) {
    $value_selected = htmlentities($_POST['idmedida_referencial']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmedida_referencial',
    'id_campo' => 'idmedida_referencial',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_medida',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Referencial </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad_referencial" id="cantidad_referencial" value="<?php  if (isset($_POST['cantidad_referencial'])) {
        echo floatval($_POST['cantidad_referencial']);
    } else {
        echo floatval($rs->fields['p1cantidad_referencia']);
    }?>" placeholder="cantidad referencial" class="form-control"  />                    
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Externo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idprodexterno" id="idprodexterno" value="<?php  if (isset($_POST['idprodexterno'])) {
        echo antixss($_POST['idprodexterno']);
    } else {
        echo antixss($rs->fields['idprodexterno']);
    }?>" placeholder="Cod Externo" class="form-control"  />                    
    </div>
</div>


<div class="clearfix"></div>
<br />
<strong>Informacion T&eacute;cnica:</strong>
<hr />



    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Centro Produccion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
Select idcentroprod,  descripcion
from produccion_centros 
where 
estado <> 6 
order by descripcion asc
";

// valor seleccionado
if (isset($_POST['cpr'])) {
    $value_selected = htmlentities($_POST['cpr']);
} else {
    $value_selected = htmlentities($rs->fields['idcpr']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'cpr',
    'id_campo' => 'cpr',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idcentroprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Agrupacion Produccion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
Select idagrupacionprod,  agrupacion_prod
from produccion_agrupacion 
where 
estado <> 6 
order by agrupacion_prod asc
";

// valor seleccionado
if (isset($_POST['idagrupacionprod'])) {
    $value_selected = htmlentities($_POST['idagrupacionprod']);
} else {
    $value_selected = htmlentities($rs->fields['idagrupacionprod']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idagrupacionprod',
    'id_campo' => 'idagrupacionprod',

    'nombre_campo_bd' => 'agrupacion_prod',
    'id_campo_bd' => 'idagrupacionprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Paquete </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="paquete" id="paquete" value="<?php  if (isset($_POST['paquete'])) {
        echo htmlentities($_POST['paquete']);
    } else {
        echo htmlentities($rs->fields['paquete']);
    }?>" placeholder="Paquete" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cant paquete </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cant_paquete" id="cant_paquete" value="<?php  if (isset($_POST['cant_paquete'])) {
        echo floatval($_POST['cant_paquete']);
    } else {
        echo floatval($rs->fields['cant_paquete']);
    }?>" placeholder="Cant paquete" class="form-control"  />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Acepta devolucion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['acepta_devolucion'])) {
    $value_selected = htmlentities($_POST['acepta_devolucion']);
} else {
    $value_selected = 'N';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'acepta_devolucion',
    'id_campo' => 'acepta_devolucion',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Aplica regalia </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['aplica_regalia'])) {
    $value_selected = htmlentities($_POST['aplica_regalia']);
} else {
    $value_selected = 'S';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'aplica_regalia',
    'id_campo' => 'aplica_regalia',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Solo conversion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['solo_conversion'])) {
    $value_selected = htmlentities($_POST['solo_conversion']);
} else {
    $value_selected = 0;
}
// opciones
$opciones = [
    'SI' => 1,
    'NO' => 0
];
// parametros
$parametros_array = [
    'nombre_campo' => 'solo_conversion',
    'id_campo' => 'solo_conversion',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Respeta precio sugerido *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['respeta_precio_sugerido'])) {
    $value_selected = htmlentities($_POST['respeta_precio_sugerido']);
} else {
    $value_selected = 'N';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'respeta_precio_sugerido',
    'id_campo' => 'respeta_precio_sugerido',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>
    
    
<?php
if ($rsprefcaj->fields['usa_recargo_precio_costo'] == 'S') {
    ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">% Recargo Precio basado en costo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="recargo_auto_costo" id="recargo_auto_costo" value="<?php  if (isset($_POST['recargo_auto_costo'])) {
        echo floatval($_POST['recargo_auto_costo']);
    } else {
        echo floatval($rs->fields['recargo_auto_costo']);
    }?>" placeholder="% Recargo Precio basado en costo" class="form-control" required />                    
    </div>
</div>    
<?php } ?>
<div class="clearfix"></div>
<br />
<strong>Stock Minimos y Ubicaciones Matriz:</strong>
<hr />


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Stock minimo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="stock_minimo" id="stock_minimo" value="<?php  if (isset($_POST['stock_minimo'])) {
        echo floatval($_POST['stock_minimo']);
    } else {
        echo floatval($rs->fields['stock_minimo']);
    }?>" placeholder="Stock minimo" class="form-control" required />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Stock ideal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="stock_ideal" id="stock_ideal" value="<?php  if (isset($_POST['stock_ideal'])) {
        echo floatval($_POST['stock_ideal']);
    } else {
        echo floatval($rs->fields['stock_ideal']);
    }?>" placeholder="Stock ideal" class="form-control"  />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ubicacion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idubicacion, descripcion
FROM ubicaciones
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idubicacion'])) {
    $value_selected = htmlentities($_POST['idubicacion']);
} else {
    $value_selected = htmlentities($rs->fields['idubicacion']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idubicacion',
    'id_campo' => 'idubicacion',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idubicacion',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Bandeja </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idbandeja" id="idbandeja" value="<?php  if (isset($_POST['idbandeja'])) {
        echo intval($_POST['idbandeja']);
    } else {
        echo intval($rs->fields['idbandeja']);
    }?>" placeholder="Idbandeja" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Orden </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idorden" id="idorden" value="<?php  if (isset($_POST['idorden'])) {
        echo intval($_POST['idorden']);
    } else {
        echo intval($rs->fields['idorden']);
    }?>" placeholder="Idorden" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pasillo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idpasillo" id="idpasillo" value="<?php  if (isset($_POST['idpasillo'])) {
        echo intval($_POST['idpasillo']);
    } else {
        echo intval($rs->fields['idpasillo']);
    }?>" placeholder="Idpasillo" class="form-control"  />                    
    </div>
</div>

<?php
$consulta = "
select * 
from sucursales 
where 
matriz = 'N'
and estado = 1
limit 1
";
$rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucu = intval($rssuc->fields['idsucu']);
if ($idsucu > 0) {
    ?>

<div class="clearfix"></div>
<br />
<strong>Stock Minimos y Ubicaciones Sucursales:</strong>
<hr />


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Stock minimo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="stock_minimo_suc" id="stock_minimo_suc" value="<?php  if (isset($_POST['stock_minimo_suc'])) {
        echo floatval($_POST['stock_minimo_suc']);
    } else {
        echo floatval($rs->fields['stock_minimo']);
    }?>" placeholder="Stock minimo" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Stock ideal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="stock_ideal_suc" id="stock_ideal_suc" value="<?php  if (isset($_POST['stock_ideal_suc'])) {
        echo floatval($_POST['stock_ideal_suc']);
    } else {
        echo floatval($rs->fields['stock_ideal']);
    }?>" placeholder="Stock ideal" class="form-control"  />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ubicacion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idubicacion, descripcion
FROM ubicaciones
where
estado = 1
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['idubicacion_suc'])) {
        $value_selected = htmlentities($_POST['idubicacion_suc']);
    } else {
        $value_selected = htmlentities($rs->fields['idubicacion_suc']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idubicacion_suc',
        'id_campo' => 'idubicacion_suc',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idubicacion',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  ',
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Bandeja </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idbandeja_suc" id="idbandeja_suc" value="<?php  if (isset($_POST['idbandeja_suc'])) {
        echo intval($_POST['idbandeja_suc']);
    } else {
        echo intval($rs->fields['idbandeja']);
    }?>" placeholder="Idbandeja" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Orden </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idorden_suc" id="idorden_suc" value="<?php  if (isset($_POST['idorden_suc'])) {
        echo intval($_POST['idorden_suc']);
    } else {
        echo intval($rs->fields['idorden']);
    }?>" placeholder="Idorden" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pasillo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idpasillo_suc" id="idpasillo_suc" value="<?php  if (isset($_POST['idpasillo_suc'])) {
        echo intval($_POST['idpasillo_suc']);
    } else {
        echo intval($rs->fields['idpasillo']);
    }?>" placeholder="Idpasillo" class="form-control"  />                    
    </div>
</div>

<?php } ?>

<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='productos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimos 10 Agregados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php
    $consulta = "
    Select productos.*, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria,
    (
    SELECT idprod
    FROM recetas_detalles
    inner join ingredientes on recetas_detalles.ingrediente = ingredientes.idingrediente
    inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    where
    recetas_detalles.idprod = productos.idprod_serial
    and coalesce(insumos_lista.idproducto,0) <> productos.idprod_serial
    limit 1
    ) as receta,
    (select idproducto from agregado where agregado.idproducto = productos.idprod_serial limit 1) as agregado,
    (select idproducto from producto_impresora where producto_impresora.idproducto = productos.idprod_serial limit 1) as tieneimpre,
    (select tipoproducto from productos_tipo where productos.idtipoproducto = productos_tipo.idtipoproducto) as tipoproducto,
    (select productos_sucursales.precio as p1 from productos_sucursales where productos_sucursales.idproducto = productos.idprod_serial and activo_suc = 1 and productos_sucursales.idsucursal = $idsucursal order by productos_sucursales.idsucursal asc limit 1) as p1
    from productos 
    inner join categorias on productos.idcategoria = categorias.id_categoria 
    inner join sub_categorias on productos.idsubcate = sub_categorias.idsubcate 
    where 
    productos.borrado = 'N'
    and productos.idempresa = $idempresa
    $whereadd
    order by idprod_serial desc 
    limit 10
    "    ;
//echo $consulta;
$prod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$num_total_registros = $prod->RecordCount();

?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr>
        <th ></th>
        <th  >Producto</th>
        <th >Precio</th>
        <th >Categoria</th>
        <th >Sub-Categoria</th>
      

        </tr>
        </thead>
        <tbody>
      <?php while (!$prod->EOF) {



          ?>
      <tr>
            <td>
                
                <div class="btn-group">
                    <a href="gest_editar_productos_new.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                     <?php  if ($prod->fields['idtipoproducto'] == 1 or $prod->fields['idtipoproducto'] == 5) { ?>
                    <a href="gest_recetas.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Receta" data-toggle="tooltip" data-placement="right"  data-original-title="Receta"><span class="fa fa-cutlery"></span></a>
                   <?php } ?>
                     <?php  if ($prod->fields['idtipoproducto'] == 2 or $prod->fields['idtipoproducto'] == 12) { ?>
                    <a href="gest_combo.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Armar Combo" data-toggle="tooltip" data-placement="right"  data-original-title="Armar Combo"><span class="fa fa-cogs"></span></a>
                     <?php } ?>
                    <?php  if ($prod->fields['idtipoproducto'] == 4) { ?>
                    <a href="gest_combinado.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Armar Combinado" data-toggle="tooltip" data-placement="right"  data-original-title="Armar Combinado"><span class="fa fa-cogs"></span></a>
                    <?php } ?>
                    <?php if ($prod->fields['idtipoproducto'] <> 2 && $prod->fields['idtipoproducto'] <> 6) { ?>
                    <a href="gest_agregados.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Agregados" data-toggle="tooltip" data-placement="right"  data-original-title="Agregados"><span class="fa fa-plus-square"></span></a>
                   
                    <?php } ?>
                    <a href="productos_foto.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Cambiar Imagen" data-toggle="tooltip" data-placement="right"  data-original-title="Cambiar Imagen"><span class="fa fa-picture-o"></span></a>
                    <a href="gest_eliminar_productos.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>

        <td height="27" align="center"><?php echo trim($prod->fields['descripcion']) ?></td>
        <td align="center"><?php echo formatomoneda($prod->fields['p1'], 0) ?></td>
        <td align="center"><?php echo trim($prod->fields['categoria']) ?></td>
        <td align="center"><?php echo trim($prod->fields['subcategoria']) ?></td>
       
        


        </tr>
      <?php $prod->MoveNext();
      } ?>
      </tbody>
</table>
 </div>
    <div class="clearfix"></div>
    <br />





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        <!-- POPUP DE MODAL OCULTO -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
                        ...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
<?php if ($usa_costo_referencial == 'S') { ?>
<script>
function calcularcosto(){
    
    var valor=parseInt($("#costo_referencial").val());
    var pmin=valor;
    var porce=0;
    var porce1=0;
    <?php if ($claseau == 'P') { ?>
        var vp=<?php echo $val ?>;
        porce1=(valor * vp);
        porce=parseInt(porce1)+parseInt(valor);
    <?php } ?>
    <?php if ($claseau == 'C') { ?>
        var vp=<?php echo $cantidad_veces_pcosto ?>;
        porce1=(valor * vp);
        porce=parseInt(porce1);
    <?php } ?>    
        
        
        $("#p1").val(porce);
        var pmax=porce;
        //colocamos el costo en min y el max este tambien
        
        $("#precio_min").val(pmin);
        $("#precio_max").val(pmax);

}
</script>
<?php } ?>
  </body>
</html>
