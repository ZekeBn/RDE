 <?php
/*----------------------------------------------------------
    10/04/2023: Agregamos guardado automatico

-------------------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");




$idcerrar = intval($_REQUEST['idcerrar']);//es el id de la transaccion
if ($idcerrar > 0) {

    $idtransaccion = $idcerrar;
    $buscar = "Select regid from pedidos_eventos where idtransaccion=$idtransaccion";
    $rc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;exit;
    $idreg = intval($rc->fields['regid']);
    if ($idreg == 0) {
        echo "error al oobtener id del evento. no se Continua por seguridad";
        exit;

    }
    //traemos los datos del carrito, para actualizar
    $buscar = "SELECT * FROM tmp_carrito_pedidos WHERE idtransaccion=$idtransaccion and estado=1 ";
    $rscarrito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    while (!$rscarrito->EOF) {
        $iddetalle = intval($rscarrito->fields['iddetalle']);//para uso en pedidos_eventos_detalles
        $cantidad = floatval($rscarrito->fields['cantidad']);//para la cantidad referencial de las producciones
        $idprod_serial = intval($rscarrito->fields['idproducto']);//del carrito del producto de venta (idprod_serial)
        $subtotal = floatval($rscarrito->fields['subtotal']);//ya que se usa para reclaculos de saldo posteriores de funcion
        $precio = floatval($rscarrito->fields['precio']);//puede que el precio haya sido abierto y por lo cual cambio
        $obs = antisqlinyeccion($rscarrito->fields['obs'], 'text');

        $update = "update pedidos_eventos_detalles set cantidad=$cantidad,subtotal=$subtotal,
        precio_venta=$precio,observacion=$obs
        where iddeta=$iddetalle";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        /*--------------------------------------------------------------------------------
        ESTA LINEA DE TABLA TEMPORAL DE PRODUCCION  CAMBIAR POR NUEVA MODALIDAD UNICA
        DESPUES DE VERIFICAR EL PERTINENTE PASO A LA PRODUCCION 13/01/2023
        ----------------------------------------------------------------------------------*/
        if ($prod == 1) {
            //ya esta en produccion, actualizar todo lo que falte pedidos eventos detalles

            if (intval($idtemporal) > 0) {
                //solo si esta en prod y paso hacemos el update tmp_pasoprod_productos para la produccion (ya que este es el qque se muestra en el panel de paso a produccion

                $update = "update tmp_pasoprod_productos set cantidad_producir=$cantidad where idproducto=$idprod_serial and idcabeza=$idtemporal";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
            }
            //Ver si en el futuro hay que actuializar automatico a las producciones en curso, colocar debajo el codigo
            //********************SU CODIGO VA AQUI*****************************
        }
        /*------------------------------------------------------------------------


        ------------------------------------------------------------------------*/
        $rscarrito->MoveNext();
    }

    /*--------------------------------------------------------------------------*/

    $update = "update pedidos_eventos set ultimo_cambio='$ahora',ultimo_cambio_por=$idusu where regid=$idreg";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Calculamos los montos del evento
    require_once('includes/funciones_cobros.php');
    $parametros_array = [
        "idevento" => $idreg
    ];
    actualiza_saldo_evento($parametros_array);

    $update = "update pedidos_eventos_transacciones set editando=NULL,ultima_actualizacion='$ahora' where idtransaccion=$idcerrar";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    //header("Location: cat_adm_pedidos_new.php");exit;
    /*
    $buscar="Select regid from pedidos_eventos where idtransaccion=$idcerrar";
    $fdg=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $idunico=intval($fdg->fields['regid']);
    require_once('includes/funciones_cobros.php');
    $parametros_array=array(
        "idevento"=>$idunico
    );
    actualiza_saldo_evento($parametros_array);
    */

    echo "<script>window.close();</script>";
    exit;

}


$idpedido = intval($_REQUEST['id']);
if ($idpedido == 0) {
    echo "Error al obtener datos del pedido $idpedido";
    exit;
}

$buscar = "Select editando,(select usuario from usuarios where idusu=pedidos_eventos_transacciones.editando) as quienedita
 from pedidos_eventos_transacciones where idpedido=$idpedido";
//echo $buscar;
$rscabe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$editando = intval($rscabe->fields['editando']);
$quien = trim($rscabe->fields['quienedita']);
//echo $quien;exit;
if ($editando > 0) {
    if ($idusu != $editando) {
        $msg = "
        <div style=\"font-size:20px; text-align:center; width:100%;\">
        El pedido se encuentra en edicion por $quien<br />
        <a href=\"cat_adm_pedidos_new.php\" >Regresar</a>
        </div>";
        echo $msg;
        exit;
    }
} else {

    $update = "update pedidos_eventos_transacciones set editando=$idusu,ultima_actualizacion='$ahora' where idpedido=$idpedido";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}



//cabecera del pedido
$buscar = "Select *,
(select cliente_pedido from tmp_pedidos_cabecera where idtransaccion=pedidos_eventos.idtransaccion) as clientepedidof
 from pedidos_eventos where regid=$idpedido ";
$rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idtransaccion = intval($rscab->fields['idtransaccion']);//id unico de la transaccion
$estado_produccion = intval($rscab->fields['estado_pedido_int']);
$lugar_entrega = intval($rscab->fields['idlugar_entrega']);

$nombre_evento = trim($rscab->fields['nombre_evento']);
$ex = explode("|", $nombre_evento);
$idsucursal_cliente = intval($rscab->fields['id_cliente_sucu_pedido']);
$buscar = "Select * from sucursal_cliente where idsucursal_clie=$idsucursal_cliente";
$rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$rz = trim($rsvv->fields['sucursal']);
$idpc = intval($rscab->fields['id_cliente_solicita']);
$ne = $idpc."|".$idsucursal_cliente."|$rz";

//cuerpo del pedido de la tabla de detalles
$buscar = "Select *,descripcion
from pedidos_eventos_detalles
inner join productos on productos.idprod_serial=pedidos_eventos_detalles.idprodserial
where idpedidocatering=$idpedido ";
$rsccuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$iddecorador = intval($rscab->fields['iddecorador']);
$idorganizador = intval($rscab->fields['idorganizador']);


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

if (isset($_POST['fechaevento'])) {
    //print_r($_POST);exit;
    $idreg = intval($_REQUEST['id']);
    $estado_pedido_int = intval($_POST['idfoc']);
    //verificar si el pedido ya existe en la produccion
    $buscar = "Select * from produccion_orden_new where idunico=$idreg and pasado='S' ";
    $rsbp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtlocal = intval($rsbp->fields['idtransaccion']);
    $tr = $rsbp->RecordCount();
    if ($tr > 0) {
        $prod = 1;//ya esta en produccion
        //dtos de la cabeza en curso
        $buscar = "select * from tmp_pasoprod_cabeza where idtransaccion=$idtlocal";
        $rstda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtemporal = intval($rstda->fields['idcabeza']);//del tmp_pasoprod_cabeza
        $estado_pedido_int = 4;
    } else {
        $prod = 0;//n0 se envio a ninguna produccion
    }
    //echo $prod;exit;
    //echo $idtemporal;exit;
    //Si el parametro de envio es 3, es un pedido que YA esta en produccion, por lo cual debemos marcar que se cambio y colorear
    //if (intval($_POST['idfoc'])==3){
    //ya existe en produccion, registramos la alerta para la produccion segun la fecha del cambio
    $update = "update produccion_orden_new set alertar='S' where idtransaccion=$idtlocal ";
    //$conexion->Execute($update) or die(errorpg($conexion,$update));
    //}
    //traemos los datos del carrito, para actualizar
    $buscar = "SELECT * FROM tmp_carrito_pedidos WHERE idtransaccion=$idtransaccion and estado=1 ";
    $rscarrito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    while (!$rscarrito->EOF) {
        $iddetalle = intval($rscarrito->fields['iddetalle']);//para uso en pedidos_eventos_detalles
        $cantidad = floatval($rscarrito->fields['cantidad']);//para la cantidad referencial de las producciones
        $idprod_serial = intval($rscarrito->fields['idproducto']);//del carrito del producto de venta (idprod_serial)
        $subtotal = floatval($rscarrito->fields['subtotal']);//ya que se usa para reclaculos de saldo posteriores de funcion
        $precio = floatval($rscarrito->fields['precio']);//puede que el precio haya sido abierto y por lo cual cambio
        $obs = antisqlinyeccion($rscarrito->fields['obs'], 'text');

        $update = "update pedidos_eventos_detalles set cantidad=$cantidad,subtotal=$subtotal,
        precio_venta=$precio,observacion=$obs
        where iddeta=$iddetalle";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        /*--------------------------------------------------------------------------------
        ESTA LINEA DE TABLA TEMPORAL DE PRODUCCION  CAMBIAR POR NUEVA MODALIDAD UNICA
        DESPUES DE VERIFICAR EL PERTINENTE PASO A LA PRODUCCION 13/01/2023
        ----------------------------------------------------------------------------------*/
        if ($prod == 1) {
            //ya esta en produccion, actualizar todo lo que falte pedidos eventos detalles

            if (intval($idtemporal) > 0) {
                //solo si esta en prod y paso hacemos el update tmp_pasoprod_productos para la produccion (ya que este es el qque se muestra en el panel de paso a produccion

                $update = "update tmp_pasoprod_productos set cantidad_producir=$cantidad where idproducto=$idprod_serial and idcabeza=$idtemporal";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
            }
            //Ver si en el futuro hay que actuializar automatico a las producciones en curso, colocar debajo el codigo
            //********************SU CODIGO VA AQUI*****************************
        }
        /*------------------------------------------------------------------------


        ------------------------------------------------------------------------*/
        $rscarrito->MoveNext();
    }

    /*--------------------------------------------------------------------------*/

    $update = "update pedidos_eventos set ultimo_cambio='$ahora',ultimo_cambio_por=$idusu,estado_pedido_int=$estado_pedido_int where regid=$idreg";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //ahora las transacciones
    $update = "update pedidos_eventos_transacciones set editando=NULL where idpedido=$idreg ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Calculamos los montos del evento
    require_once('includes/funciones_cobros.php');
    $parametros_array = [
        "idevento" => $idreg
    ];
    actualiza_saldo_evento($parametros_array);
    //echo "PASO";exit;

    if (intval($_POST['envmail'] == 1)) {
        header("Location: cat_pedidos_envia_mail.php?idt=$idtransaccion");
        exit;
    } else {
        echo "<script>window.close();</script>";
        $final = 1;
        //header("Location: cat_adm_pedidos_new.php");exit;
    }

    /*-------------------------------------------------------------------------------*/
}






?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
<?php if ($final == 1) {
    echo "Esta ventana no se abrio de la manera tradicional y no se puede cerrar automaticamente. <br />Favor cerrar la pesta&ntilde;a de forma manual. Todos los procesos y actualizaciones han finalizado correctamente.";

    ?>
alert("Esta ventana no se abrio de la manera tradicional y no se puede cerrar automaticamente. <br />Favor cerrar la pesta&ntilde;a de forma manual. Todos los procesos y actualizaciones han finalizado correctamente.");

<?php exit;
} ?>
function controlar_req(){
    $("#lerr").html("");
    $("#errorjs").hide();
    var letra='S';
    var errores="";
    var fechaeventoloc="";
    var horaeventoloc="";
    fechaeventoloc=$("#fechaevento").val();
    horaeventoloc=$("#hora").val();
    if (fechaeventoloc==''){
        letra='N';
        errores=errores+"Debe indicar la fecha del evento.<br />";
    }
    if (horaeventoloc==''){
        letra='N';
        errores=errores+"Debe indicar la hora del evento.<br />";
    }
    if (letra=='N'){
        //alert('ingresa');
        $("#lerr").html(errores);
        $("#errorjs").show();
    } else {
        $("#evento_para").val(fechaeventoloc);
        $("#hora_entrega").val(horaeventoloc);
    }
    return(letra);
}
function busca_cliente(idcampo,tipo){
    var direccionurl='busqueda_cliente_cat.php';
    
    var sigue='';
    sigue=controlar_req();
    //alert(sigue);
    if (sigue=='S'){
        var parametros = {
          "m" : '1',
          "idcampo" : idcampo
        };
        $.ajax({          
            data:  parametros,
            url:   direccionurl,
            type:  'post',
            beforeSend: function () {
                $('#modal_ventana').modal('show');
                $("#modal_titulo").html('Busqueda de Cliente');
                $("#modal_cuerpo").html('Cargando...');                
            },
            success:  function (response) {
                $("#modal_cuerpo").html(response);
            }
        });
    } else {
        //Alert('Debe indicar fecha y hora del evento');
    }
}
function busca_cliente_res(tipo,idcampo){
    //alert(tipo);
    var ruc = $("#ruc_cat").val();
    var razon_social = $("#razon_social_cat").val();
    var fantasia = $("#fantasia_cat").val();
    var documento = $("#documento_cat").val();
    var sucucliente = $("#sucursal_cat").val();
    var email = $("#email_cat").val();
    if(tipo == 'ruc'){
        razon_social = '';
        fantasia = '';
        documento = '';
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
        $("#documento_cat").val('');
         $("#sucursal_cat").val('');
    }
    if(tipo == 'razon_social'){
        ruc = '';
        fantasia = '';
        documento = '';
        $("#ruc_cat").val('');
        $("#fantasia_cat").val('');
        $("#documento_cat").val('');
        $("#sucursal_cat").val('');
    }
    if(tipo == 'fantasia'){
        ruc = '';
        razon_social = '';
        documento = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#documento_cat").val('');
        $("#sucursal_cat").val('');
    }
    if(tipo == 'documento'){
        ruc = '';
        razon_social = '';
        fantasia = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
        $("#sucursal_cat").val('');
    }
    if(tipo == 'email'){
        ruc = '';
        razon_social = '';
        fantasia = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
        $("#sucursal_cat").val('');
    }
    if(tipo == 'sucucliente'){
        ruc = '';
        razon_social = '';
        fantasia = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
    }
    var direccionurl='busqueda_cliente_res_cat.php';        
    var parametros = {
      "ruc"            : ruc,
      "razon_social"   : razon_social,
      "fantasia"          : fantasia,
      "documento"      : documento,
      "idcampo"       : idcampo,
      "sucursal_cliente" : sucucliente
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#busqueda_cli").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_cli").html(response);
        }
    });
}
function seleccionar_item(id,idcampo){
    var valor = $("#idsucursal_clie_"+id).val();
    //alert(valor);
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        var idcampo = obj.idcampo;
        var ruc = obj.ruc;
        var razon_social = obj.razon_social;
        var nombres = obj.nombres;
        var apellidos = obj.apellidos;
        var facturador=parseInt(obj.facturador);
        if (facturador>0){
                //cargar_facturador(facturador);obj.fantasia+
        }
        
        $("#cliente_ped").val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+'|'+obj.sucuvirtual);
        $("#cliente_fac").val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+obj.razon_social);    
            
        //$("#"+idcampo).val(idcliente+'|'+idsucursal_clie+'|'+razon_social);
        $('#modal_ventana').modal('hide');
        var cli_ped = $("#cliente_ped").val();
        var cli_fac = $("#cliente_fac").val();
        if(cli_ped != '' && cli_fac != ''){
            llenarfechas();
            
            $("#cpedidonuevo").click();
            document.location.href='#cpedidonuevo';
            controlar_valores();
        }


    }else{
        alert("Error: "+valor);    
    }

}
function cargar_facturador(idunico){

    var direccionurl='busqueda_cliente_vinculado_cat.php';        
    var parametros = {
      "idunico"     :   idunico
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#ocbuscador").html('');                
        },
        success:  function (response) {
            $("#ocbuscador").html(response);
            $("#ocvalores").val(response);
            verificar_respuesta();
            
        }
    });

}
function cargar_comentario(idunico,iddetalle){
    
    $("#modal_titulo").html('Cargar Comentario de Producto');
    $('#modal_ventana').modal('show');
    var idtransaccion=<?php echo $idtransaccion ?>;
    var direccionurl='cat_mini_actualiza_comentario.php';        
    var parametros = {
      "idunico"     :   idunico,
      "idtransaccion" : idtransaccion,
      "iddetalle"    : iddetalle
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#modal_cuerpo").html('');                
        },
        success:  function (response) {
            
            $("#modal_cuerpo").html(response);
            
            
        }
    });

}
function guardar_comentario(idunico,iddetalle){
    var obsprod=$("#obsprod").val();
    var idtransaccion=<?php echo $idtransaccion ?>;
    var direccionurl='cat_mini_actualiza_comentario.php';        
    //alert(idunico);
    var parametros = {
      "idunico"        :idunico,
      "idtransaccion"  : idtransaccion,
      "registrar"       :1,
      "texto"            :obsprod,
       "iddetalle"    : iddetalle
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#modal_cuerpo").html('');                
        },
        success:  function (response) {
            
            $("#modal_cuerpo").html(response);
            
            
        }
    });
    
}
function llenarfechas(){
    var errores="";
    var fechaeventoloc="";
    var horaeventoloc="";
    fechaeventoloc=$("#fechaevento").val();
    horaeventoloc=$("#hora").val();
    $("#evento_para").val(fechaeventoloc);
    $("#hora_entrega").val(horaeventoloc);
}
function verificar_respuesta(){
var valor =$("#ocvalores").val();
    //alert(valor);
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        var idcampo = obj.idcampo;
        var ruc = obj.ruc;
        var razon_social = obj.razon_social;
        var nombres = obj.nombres;
        var apellidos = obj.apellidos;
        $("#cliente_fac").val(idcliente+'|'+idsucursal_clie+'|'+razon_social);
        var cli_ped = $("#cliente_ped").val();
        var cli_fac = $("#cliente_fac").val();
        if(cli_ped != '' && cli_fac != ''){
            llenarfechas();
            $("#cpedidonuevo").click();
            document.location.href='#cpedidonuevo';
        }
    }else{
        alert("Error: "+valor);    
    }


}
function agrega_cliente(idcampo,tipo){
        var direccionurl='cliente_agrega_cat_new.php';
        var parametros = {
              "new" : 'S',
              "idcampo" : idcampo,
              "tipo"    : tipo
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html(response);
                    if (document.getElementById('ruccliente')){
                        document.getElementById('ruccliente').focus();
                    }
                    $("#idcampo").html(idcampo);
                }
        });    
}
function registrar_cliente(idcampo){
    var ruc_add = $("#ruccliente").val();
    var nombres_add = $("#nombreclie").val();
    var apellidos_add = $("#apellidosclie").val();
    var razon_social_add = $("#rz1").val();
    var documento_add = $("#cedulaclie").val();
    var tipo_cliente_add = $("#ruccliente").val();
    var email = $("#email").val();
    var celular=$("#celular").val();
    var telefono = $("#telefono").val();
    if($('#r1').is(':checked')) { var idclientetipo=1; }
    if($('#r2').is(':checked')) { var idclientetipo=2; }
    
    if(idclientetipo == 1){
      // var razon_social_add = nombres_add+' '+apellidos_add;
           var clientepedido=$("#clientepedido_fisico").val();
    }
    if(idclientetipo==2){
        var clientepedido=$("#clientepedido_empresa").val();
        
    }
    //alert(clientepedido);
        var direccionurl='cliente_agrega_cat_new.php';
        var parametros = {
            "new" : 'S',
            "MM_insert"     : 'form1',
            "ruc"             : ruc_add,
            "nombre"         : nombres_add,
            "apellido"        : apellidos_add,
            "cliente_pedido" : clientepedido,
            "documento"     : documento_add,
            "razon_social"    : razon_social_add,
            "idclientetipo"    : idclientetipo,
            "idcampo"         : idcampo,
            "email"            :email,
            "telefono"        :telefono,
            "celular"        :celular
            
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    //alert(response);
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html(response);
                    //{"ruc":"222555-7","razon_social":"JUAN PEREZ","nombre_ruc":"JUAN","apellido_ruc":"PEREZ","idcliente":"352","idsucursal_clie":"355","valido":"S"}
                    //222555-7
                    //alert(response);
                    if(IsJsonString(response)){
                        var obj = jQuery.parseJSON(response);

                        //alert(obj.error);+obj.fantasia
                        //alert(obj.valido);
                        if(obj.valido == 'S'){
                            //alert(idcampo);
                            $("#"+idcampo).val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+'|'+obj.sucuvirtual);
                            $("#cliente_fac").val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+obj.razon_social);
                            $('#modal_ventana').modal('hide');
                            var cli_ped = $("#cliente_ped").val();
                            var cli_fac = $("#cliente_fac").val();
                            if(cli_ped != '' && cli_fac != ''){
                                llenarfechas();
                                $("#cpedidonuevo").click();
                                document.location.href='#cpedidonuevo';
                            }
                            

                        }else{

                            agrega_cliente(idcampo);
                            alert(obj.errores);
                        }
                    }else{
                        alert(response);
                    }
                }
        });    
}
function cambia(valor){
    if (valor==1){
        $("#nombreclie_box").show();
        $("#apellidos_box").show();
        $("#rz1").val("");
        $("#rz1_box").hide();
        $("#cedula_box").show();
    }
    if (valor==2){
        $("#nombreclie").val("");
        $("#apellidosclie").val("");
        $("#nombreclie_box").hide();
        $("#apellidos_box").hide();
        $("#rz1_box").show();
        $("#cedula_box").hide();
    }
    
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
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
            
        <form id="form1" name="form1" method="post" action="cat_pedidos_new_editar.php?id=<?php echo $idpedido ?>">
            <input type="hidden" name="idfoc" id="idfoc" value="" />
             <input type="hidden" name="envmail" id="envmail" value="" />
            <div class="row" >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Pedido N&deg; <?php echo $idpedido ?> | transacci&oacute;n :    <?php echo $idtransaccion ?></h2> &nbsp; &nbsp;<?php if ($estado_produccion == 1) { ?><button type="button" class="btn btn-success"  onclick="proceder(<?php echo $idtransaccion ?>,1);" ><span style="color:white;" class="fa fa-sign-out"></span> Salir</button> <?php } ?><?php if ($estado_produccion != 3) { ?><div class="col-md-4"><button type="button" class="btn btn-danger" onclick="proceder(<?php echo $idtransaccion ?>,2);" ><span class="fa fa-check-square-o"></span> Confirmar Pedido</button><?php } ?><?php if ($estado_produccion == 3) { ?><button type="button" class="btn " style="background-color:yellow" onclick="proceder(<?php echo $idtransaccion ?>,3);" ><span class="fa fa-check-square-o"></span> Modificado</button>&nbsp;&nbsp;| &nbsp; 
                            <button type="button" class="btn btn-success" onclick="proceder_salir(<?php echo $idtransaccion ?>);" ><span class="fa fa-sign-out"></span> Salir</button><?php } ?>    | <?php if ($estado_produccion > 2) {
                                echo "ESTADO: EN PRODUCCION";
                            }?>
                    <ul class="nav navbar-right panel_toolbox collapsed">
                      <li><a class="collapse-link " id="cabeceraid" ><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                        <div class="alert alert-danger alert-dismissible fade in" id="errorjs" role="alert" style="display:none">
                            
                            <strong>Errores:</strong><br /><span id="lerr" ></span>
                        </div>                        
            
                    <?php if (trim($errores) != "") { ?>
                    <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                                </button>
                                <strong>Errores:</strong><br /><?php echo $errores; ?>
                    </div>
                    <?php } ?>
                    
<?php
//Nomenclatura a respetar (idcliente+'|'+idsucursal_clie+'|'+razon_social);
$idcliente_pedido = intval($rscab->fields['idcliente_pedido']) ;
//echo $idcliente_pedido;exit;
$idcliente_facturacion = intval($rscab->fields['idcliente_factura']) ;
$hevi = date("H:i", strtotime($rscab->fields['hora_entrega']));
$cantidad_personas = intval($rscab->fields['cantidad_personas']);//cuantas personas asistena al evento, sobre el cual se efectuaan los calculos
$idtipoevento = intval($rscab->fields['idtipoev']);// id del tipo de evento seleccionado
$ub = trim($rscab->fields['ubicacion_comp']);//latitud y longitud copiada del maps pero la compuesta (ambas)
$direccion_entrega = trim($rscab->fields['dire_entrega']);//campo varchar para la direccion a ser mostrada
$tipo_envio = intval($rscab->fields['tipoenvio']);//el tipo de envio indica si es una de las sucursales de la matriz o bien delivery (se usa tipo 99 para delivery y el id de la sucursal para los que son pasa a buscar )
$tipoevento = intval($rscab->fields['idtipoev']);//tipo de evento almacenado (para uso en reportes)





?>
                        
<div class="clearfix"></div>                          
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Evento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input class="form-control" type="date" name="fechaevento" id="fechaevento" required="required" onchange="controlar_valores();"   value="<?php echo date("Y-m-d", strtotime($rscab->fields['evento_para']));
$fev = date("Y-m-d", strtotime($rscab->fields['evento_para']));
?>"/>                    
    </div>
</div>
                      
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora Evento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input class="form-control" type="time" name="hora" id="hora" required="required" value="<?php echo $hevi; ?>" onchange="controlar_valores();"  />
    </div>
</div>
<?php


$buscar = "Select documento,(select idsucursal_clie from sucursal_cliente where idcliente=cliente.idcliente order by idsucursal_clie desc limit 1) as idsucursal_clie,razon_social from cliente where idcliente=$idcliente_pedido";
$rsc1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idc1 = $idcliente_pedido."|".intval($rsc1->fields['idsucursal_clie'])."|".trim($rsc1->fields['razon_social']);

$buscar = "Select documento,(select idsucursal_clie from sucursal_cliente where idcliente=cliente.idcliente order by idsucursal_clie desc limit 1) as idsucursal_clie,razon_social from cliente where idcliente=$idcliente_facturacion";
$rsc2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idc2 = $idcliente_facturacion."|".intval($rsc2->fields['idsucursal_clie'])."|".trim($rsc2->fields['razon_social']);

?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Pedido *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="cliente_ped" id="cliente_ped" class="form-control" value="<?php echo $ne ?>" onClick="busca_cliente('cliente_ped',<?php echo '1'; ?>);" readonly style="cursor: pointer;"  />
         
    </div>
</div>
                      
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Facturacion *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="cliente_fac" id="cliente_fac" readonly  class="form-control" value="<?php echo $idc2 ?>" onClick="busca_cliente('cliente_ped',<?php echo '2'; ?>);" readonly style="cursor: pointer;" />

    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Lugar Entrega  </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <select name="suculista" id="suculista" class="form-control"  required="required" onClick="seledire(this.value);">
        
            <option value="99"
            <?php if ($tipo_envio == 99) {
                echo "selected";
            }?>
                <?php if ($lugar_entrega == 99) {
                    echo "selected";
                }    ?>        
            >Delivery</option>
            <?php
                $buscar = "Select * from sucursales where estado <> 6 
                
                order by nombre asc";
$rsuc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tsuc = $rsuc->RecordCount();
if ($tsuc > 0) {
    while (!$rsuc->EOF) {
        if ($tipo_envio == $rsuc->fields['idsucu']) {
            //$comando="selected='selected'";
        }
        if ($lugar_entrega == $rsuc->fields['idsucu']) {

            $comando = "selected='selected' ";
        } else {
            $comando = " ";
        }

        ?>
                    <option value="<?php echo $rsuc->fields['idsucu'] ?>"<?php echo $comando ?>><?php echo $rsuc->fields['nombre'] ?></option>
                    
                    <?php
            $rsuc->MoveNext();
    }
    $rsuc->MoveFirst();
}

?>
        </select>
        
        <?php while (!$rsuc->EOF) { ?>
        <input type="hidden" name="direh_<?php echo $rsuc->fields['idsucu']; ?>" id="direh_<?php echo $rsuc->fields['idsucu']; ?>" value="<?php echo $rsuc->fields['direccion'];?>" />
        <?php $rsuc->MoveNext();
        } ?>
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Evento</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="tiposeventosdiv">
        <?php
        $buscar = "Select * from tipos_eventos where estado <> 6 order by descripcion asc"    ;
$rstipoev = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));?>
        <select name="idtipoev" id="idtipoev" class="form-control" onchange="controlar_valores();"  >
            <option value="" selected="selected">Seleccionar</option>
            <?php while (!$rstipoev->EOF) {?>
            <option value="<?php echo $rstipoev->fields['idtipoevento']?>" <?php if ($tipoevento == $rstipoev->fields['idtipoevento']) { ?> selected="selected"<?php } ?>><?php echo $rstipoev->fields['descripcion']?></option>

            <?php $rstipoev->MoveNext();
            }?>
        </select>
        <button type="button" class="btn btn-dark go-class" onClick="agregar(1);"><span class="fa fa-plus"></span></button>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
                                
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Dir. Entrega <?php echo $aste;?></label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="domicilio" id="domicilio" onchange="controlar_valores();"  value="<?php echo $direccion_entrega  ?>" placeholder="Direccion de entrega / Domicilio"  style="height: 40px;width:100%;"  />    <?php if ($gg == 1) { ?> <button type="button"  class="btn btn-dark go-class" onClick="registradireccion();"><span class="fa fa-plus"></span> </button>  <?php } ?>
        <input type="hidden" name="iddomicilio" id="iddomicilio" value="" />
    </div>
</div>        
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ubicacion / Gmaps  </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <textarea name="ubicacion" id="ubicacion" onkeyup="controlar_valores();" class="form-control" ><?php echo trim($ub) ?></textarea>
        
    </div>
</div>    

<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Ciudad  </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <select name="ciudades" id="ciudades" class="form-control" onchange="controlar_valores();"  >
                <option value="" selected="selected">Seleccionar</option>
                
                <?php
                    $buscar = "Select * from ciudades where estado ='A'
                    
                    order by nombre asc";
$rsciu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$ts1 = $rsciu->RecordCount();
if ($ts1 > 0) {
    while (!$rsciu->EOF) {
        ?>
                        <option value="<?php echo $rsciu->fields['idciudad'] ?>" <?php if ($rscab->fields['idciudad'] == $rsciu->fields['idciudad']) { ?> selected="selected"<?php } ?>><?php echo $rsciu->fields['nombre'] ?></option>
                        
                        <?php
            $rsciu->MoveNext();
    }

}

?>
            </select>
            
            <?php while (!$rsuc->EOF) { ?>
            <input type="hidden" name="direh_<?php echo $rsuc->fields['idsucu']; ?>" id="direh_<?php echo $rsuc->fields['idsucu']; ?>" value="<?php echo $rsuc->fields['direccion'];?>" />
            <?php $rsuc->MoveNext();
            } ?>
        </div>
    </div>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Barrio</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            
            <?php require_once("listado_barrios.php"); ?>
            
        </div>
    </div>




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <!-------------------------------------------------------DESDE ACA NUEVA FORMA DE CARGA---------------------------------------->
            <?php
            $usa_plantilla_articulos = trim($rspref->fields['usa_plantilla_articulo']);//
$usa_lista_precio = trim($rspref->fields['usa_listaprecio']);//


?>
            <div class="row" id="cuerpopedido"  >
              <div class="col-md-12 col-sm-12 col-xs-12" style="display:none;">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-money"></span></span>&nbsp;Datos de Cliente , Facturaci&oacute;n y Evento</h2>    
                    <input type="hidden" name="ocvalores" id="ocvalores" value="" />            
                    <ul class="nav navbar-right panel_toolbox">
                      <li ><a id="cpedidonuevo" class="collapse-link"><i class="fa fa-chevron-down"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" style="" >
                    <div class="col-md-12">
                        <?php if (trim($errores) != "") { ?>
                            <div class="alert alert-danger alert-dismissible fade in" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                            </button>
                            <strong>Errores:</strong><br /><?php echo $errores; ?>
                            </div>
                        <?php } ?>
                        
                                        
                                            <!------------------------------------------------------------------------------------------------------>
                                        
                                            <div class="col-md-4 col-sm-4 form-group">
                                            

                                            <?php if ($usa_lista_precio == 'S') { ?>
                                            <div class="col-md-4 col-sm-4 form-group">
                                                
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <label >Lista Precio</label>
                                                <?php
                                        $consulta = "
                                            select *
                                            from lista_precios_venta 
                                            where 
                                             estado = 1 
                                            order by idlistaprecio asc
                                            ";
                                                $rsl = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                                                $tl = $rsl->RecordCount();
                                                if ($tl == 1) {
                                                    $selected = "selected='selected' ";
                                                }
                                                if ($tl > 0) {

                                                    ?>   
                                                    <select name="listapre" id="listapre" class="form-control" >
                                                        <option value="" selected="selected">Seleccionar</option>
                                                        <?php while (!$rsl->EOF) {?>
                                                        <option value="<?php echo $rsl->fields['idlistaprecio']?>" <?php if ($_POST['listapre'] == $rsl->fields['idlistaprecio']) { ?> selected="selected"<?php } ?><?php echo $selected ?>><?php echo $rsl->fields['lista_precio']?></option>

                                                        <?php $rsl->MoveNext();
                                                        }?>
                                            </select>
                                                    <?php }?>
                                                </div>
                                            </div>    
                                            <?php } ?>
                                            
    
                                    
                                    <!----------------------------------------------------------->
                    </div>
                    <div class="col-md-12">
                        <div class="clearfix"></div>
                        <hr />        
                                            
                            
                            
                            <div class="clearfix"></div>
                    

                            
                            

                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php
                //Lista organizadores
$buscar = "Select nombre,idorganizador from organizador_eventos where estado <> 6 order by nombre asc";
$rslo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Lista decoradores
$buscar = "Select nombre,decorador_eventos.iddecorador from decorador_eventos where estado <> 6 order by nombre asc";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
            <!------------------------------------------------------------>
            <div id="ocbuscador" style="display:none;" ></div>
            <div class="row" id="cuerpoeventos" style="display:none" >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-dashboard"></span></span>&nbsp; </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link" onclick="desocultar();"><i class="fa fa-chevron-down"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" id="" style="display:none">
                    
                    


                  </div>
                </div>
              </div>
            </div>
            
            
            
            <div class="row" id="cuerpocolores">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-edit"></span></span>&nbsp; Comentarios Pedido</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a ><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" id="" style="">
                    
                        <div class="col-md-6 col-sm-6 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Organizador </label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                                <select name="listaorg" id="listaorg" class="form-control" onchange="controlar_valores();"  >
                                <option value="" selected="selected">Seleccionar</option>
                                <?php while (!$rslo->EOF) {?>
                                <option value="<?php echo $rslo->fields['idorganizador']?>" <?php if ($_POST['listaorg'] == $rslo->fields['idorganizador']) { ?> selected="selected"<?php } ?>
                                <?php if ($idorganizador == $rslo->fields['idorganizador']) { ?>selected<?php } ?>
                                ><?php echo $rslo->fields['nombre']?></option>

                                <?php $rslo->MoveNext();
                                }?>
                                    </select>                 
                        </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Decorador</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                     <select name="listadeco" id="listadeco" class="form-control" onchange="controlar_valores();"  >
                                    <option value="" selected="selected">Seleccionar</option>
                                    <?php while (!$rsd->EOF) {?>
                                    <option value="<?php echo $rsd->fields['iddecorador']?>" <?php if ($_POST['listadeco'] == $rsd->fields['iddecorador']) { ?> selected="selected"<?php } ?>
                                    <?php if ($iddecorador == $rsd->fields['iddecorador']) { ?>selected<?php } ?>
                                    ><?php echo $rsd->fields['nombre']?></option>

                                    <?php $rsd->MoveNext();
                                    }?>
                                    </select>        
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                Detalles  / Colores
                            </label>
                            <?php
                            $buscar = "Select * from pedidos_eventos where idtransaccion=$idtransaccion";
$rscomenta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


?>
                            
                            
                            
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="detalles" id="detalles" rows="4" style="width: 90%;" onchange="controlar_valores();" ><?php echo $rscomenta->fields['comentarios'] ?></textarea>                
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario Interno</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="interno" id="interno" rows="4" onchange="controlar_valores();"  style="width: 90%;"><?php echo $rscomenta->fields['comentario_interno'] ?></textarea>                    
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr />
                        <?php
                        $buscar = "Select * from pedidos_eventos_mensajes where idtransaccion=$idtransaccion limit 1";
$rsmensa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


?>
                        <div class="col-md-4 col-sm-4 form-group">
                        <input type="text" class="form-control has-feedback-left" name="remitente" id="remitente" placeholder="Remitente" value="<?php echo $rsmensa->fields['remitente'] ?>" onchange="controlar_valores();" >
                            <span class="fa fa-user form-control-feedback left" aria-hidden="true"></span>
                        </div>
                        <div class="col-md-4 col-sm-4 form-group">
                            <input type="text" class="form-control has-feedback-left" name="telefono_remitente" id="telefono_remitente" placeholder="Numero Remitente" value="<?php echo $rsmensa->fields['celular'] ?>" onchange="controlar_valores();" >
                            <span class="fa fa-user form-control-feedback left" aria-hidden="true"></span>
                        </div>
                        <div class="col-md-4 col-sm-4 form-group">
                           <textarea class="form-control" name="textotarjeta" id="textotarjeta" onchange="controlar_valores();"  placeholder="Texto del mensaje...."><?php echo $rsmensa->fields['mensaje'] ?></textarea>
                           
                        </div>
                        <div class="col-md-12" style="text-align:center;display:none;" >
                        
                            <a href="javascript:void(0);" class="btn btn-warning" onclick="controlar_valores();">Guardar Cabecera</a>
                            
                            </div>
                        <div class="clearfix"></div>

                  </div>
                </div>
              </div>
            </div>
            <div id="varios_upd" style="display:none;"></div>
            <!------------------------------CUERPO DEL PEDIDO-------------------------------->
            <div class="row" id="enviocuerpotmp">
              <?php require_once("cat_tmp_cuerpucho_editar_new.php"); ?>
            </div>
             <!------------------------------CUERPO DEL PEDIDO-------------------------------->
            <div class="row" id="enviocuerpo"  >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2></h2>
                    <ul class="nav navbar-right panel_toolbox collapsed">
                      <li><a class="collapse-link "  ><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="form-group">
                        <?php if ($estado_produccion == 1) { ?>
                        <div class="col-md-4"><button type="button" class="btn btn-success" onclick="proceder(<?php echo $idtransaccion ?>,1);" ><span class="fa fa-sign-out"></span> Salir</button></div>
                        <?php } ?>
                        <?php if ($estado_produccion != 3) { ?>
                        <div class="col-md-4"><button type="button" class="btn btn-danger" onclick="proceder(<?php echo $idtransaccion ?>,2);" ><span class="fa fa-check-square-o"></span> Confirmar Pedido</button></div>
                        <?php } ?>
                        <div class="col-md-4">
                        <?php if ($estado_produccion == 3) { ?>
                            <button type="button" class="btn " style="background-color:yellow" onclick="proceder(<?php echo $idtransaccion ?>,3);" ><span class="fa fa-check-square-o"></span> Modificado</button>
                            
                            </div>
                        <?php } ?>
                        <div class="col-md-4"></div>
                    
                    
                        
                    </div>
                    
                    <input type="hidden" name="ocidclientefactural" id="ocidclientefactural" value="" />
                    <input type="hidden" name="ocidclientepedidol" id="ocidclientepedidol" value="" />
                                      <input type="hidden" name="mapz" id="mapz" value="<?php echo trim($_REQUEST['ubicacion']);?>" />
                                    <input type="hidden" name="tipoenviooc" id="tipoenviooc" value="<?php echo $metodoentrega?>" />
                    <input type="hidden" name="MM_insert" value="form1" />
                    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
                    <br />

                  </div>
                </div>
              </div>
            </div>
            
                
        </form>
            <div id="carrito" style="display: none"></div>
            
                      
        <!-- POPUP DE MODAL OCULTO -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
                        ...
                        </div>
                        <div class="modal-footer"  id="modal_pie">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
                <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodal">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title" id="titulov"></h4>
                        </div>
                        <div class="modal-body" id="cuerpov" >
                            <div id="erroralerta" class="alert alert-danger alert-dismissible fade in" role="alert" style="display:none">
                                <span id="alertando">
                                </span>
                            </div>
                            <div class="form-control" style="height: 80px;">
                                <div class="col-md-6">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad Personas</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="number" id="cpersonas_plantilla" name="cpersonas_plantilla" class="form-control" />        
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <a id="btn_agrega" class="btn btn-success" onclick="agregar_plantilla()"><span class="fa fa-success"></span>&nbsp; [Agregar]</a>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer"  id="piev">
                          
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
                          
                        </div>

                      </div>
                    </div>
             </div>






        <!-- POPUP DE MODAL OCULTO -->
            <div id="datos_anexos" style="display:none">
            </div>
            <div id="recalculos" style="display:none">
            </div>
          </div>
        </div>
        <!-- /page content -->
        <script>
        function simulateKeyPress(character) {
          jQuery.event.trigger({ type : 'keypress', which : character.charCodeAt(9) });
        }
        function guardar_orden(numero,idunico){
            var idtransaccion=<?php echo $idtransaccion ?>;
            if (numero!='' && idunico!=""){
                var parametros = {
                    "orden"    :numero,
                    "idtransaccion"    : idtransaccion,
                    "idunico"        : idunico,
                    "origen"        : 1
                };
                if (numero!=''){
                    $.ajax({          
                        data:  parametros,
                        url:   'cat_mini_actualiza_orden.php',
                        type:  'post',
                        cache: false,
                        timeout: 3000,  // I chose 3 secs for kicks: 3000
                        crossDomain: true,
                        beforeSend: function () {    
                                            
                        },
                        success:  function (response) {
                            $("#datos_anexos").html(response);    
                            if (response=='OK'){
                                actualiza_carrito();
                            } else  {
                                alert(response);
                            }
                        }
                    });
                
                }
            }
            
        }
        
        function verificacantidad(idp,idtransaccion){
            //alert("Llega");
            $("#errorhtml").hide();
            var comple="";
            var nperso=$("#canti_"+idp).val();
            //alert(nperso);
            comple=idp+"&cp="+nperso+"&idtran="+idtransaccion;
            var errores="";
            if (nperso==''){
                errores=errores+"Debe indicar cantidad de personas para evento!.";
                
            }
            if (nperso==0){
                errores=errores+"Debe indicar cantidad de personas para evento!.";
                
            }
            if (errores==''){
                //enviar
                var url='cat_combo_catering_ventas_edicion.php?idp='+comple;
                //window.location = ('cat_combo_catering_ventas.php?idp='+comple);
                
                window.open(url , "_blank");
            } else {
                //mostrar error en busqueda
                $("#errortxt").html("Debe indicar la cantidad de personas para el evento");
                $("#errorhtml").show();
                
            }
    
        }
        function esteproducto(valorbuscar,e){
            var tecla= (document.all) ? e.keyCode : e.which;
            if (tecla==13){
                //eventos 
                simulateKeyPress("Tab");
                
                
                
            } else {
                var idpedido=<?php echo $idpedido ?>;
                var idtransaccion=<?php echo $idtransaccion ?>;
                var parametros = {
                    "valor"    :valorbuscar,
                    "idtransaccion"    : idtransaccion,
                    "idpedido"        : idpedido
                };
                if (valorbuscar!=''){
                    $.ajax({          
                        data:  parametros,
                        url:   'cat_mini_filtroprod_busqueda_edita.php',
                        type:  'post',
                        cache: false,
                        timeout: 3000,  // I chose 3 secs for kicks: 3000
                        crossDomain: true,
                        beforeSend: function () {    
                                            
                        },
                        success:  function (response) {
                            $("#filtradoprodli").html(response);    
                        }
                    });
                
                }
            }
            
        }
        function actualizar(idtemporal,idprodu,cual,e){
            var tecla= (document.all) ? e.keyCode : e.which;
            var idtransaccion=<?php echo $idtransaccion ?>;
            var idpedido=<?php echo $idpedido ?>;
            var cantidad_personas=<?php echo $cantidad_personas ?>;
            
            
            
            if (tecla==13){
                var cantidad=$("#cantidad_"+idtemporal).val();
                //alert(cantidad);
                var parametros = {
                    "cantidad"    :cantidad,
                    "cual"        :cual,
                    "idtemporal"    :idtemporal,
                    "idprodserial"  :idprodu,
                    "idtransaccion"    : idtransaccion,
                    "cantidad_personas" : cantidad_personas,
                    "idpedido"            : idpedido
                };
                
                        $.ajax({          
                            data:  parametros,
                            url:   'cat_tmp_cuerpucho_editar_new.php',
                            type:  'post',
                            cache: false,
                            timeout: 3000,  // I chose 3 secs for kicks: 3000
                            crossDomain: true,
                            beforeSend: function () {    
                                                
                            },
                            success:  function (response) {
                                $("#enviocuerpotmp").html(response);    
                                //setTimeout(function(){ buscarprod(); }, 1000);
                    
                            }
                        });
            } 
        }
        function recalcular_personas_catering(idpadre,idunico){
        
             var idtransaccion=<?php echo $idtransaccion ?>;
             var idpedido=<?php echo $idpedido ?>;
             var cp=$("#cantidad_"+idunico).val();
             alert(cp);
             var parametros = {
                    "idtransaccion"        :idtransaccion,
                    "idpedido"            :idpedido,
                    "cantidad_personas" : cp,
                    "idpadre"            : idpadre,
                    "combo_catering"    : 'S'
                    
                    
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_recalcular_cantidad_personas.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    //alert(response);
                    $("#recalculo_personas").html(response);    
                    
                }
            });
             
        
        }
        function editar(idprodcatering,idunico){
            var idtransaccion=<?php echo $idtransaccion ?>;
            var idpedido=<?php echo $idpedido ?>;
            //var cantidad_personas=<?php echo $cantidad_personas ?>;
             var cp=$("#cantidad_"+idunico).val();
            var comple="idtran="+idtransaccion+"&cp="+cp+"&idp="+idprodcatering;
            var url='cat_combo_catering_ventas_edicion.php?'+comple;
            window.open(url , "_blank");
            
            
        }
        function eliminar(idtemporal,cual,producto){
            
            var idtransaccion=<?php echo $idtransaccion ?>;
            var idpedido=<?php echo $idpedido ?>;
            var cantidad_personas=<?php echo $cantidad_personas ?>;
            var parametros = {
                    "cual"        :2,
                    "idtemporal"    :idtemporal,
                    "idtransaccion"    : idtransaccion,
                    "cantidad_personas" : cantidad_personas,
                    "idpedido"            : idpedido,
                    "idprodserial"        : producto
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_tmp_cuerpucho_editar_new.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    //alert(response);
                    $("#enviocuerpotmp").html(response);    
                    //setTimeout(function(){ buscarprod(); }, 1000);
        
                }
            });
            
        }
        function verificar(idprodserial,e){
            //toma y verifica si se hizo enter en la casilla de cantidad al buscar u producto
            var tecla= (document.all) ? e.keyCode : e.which;
            if (tecla==13){
                apretarauto(idprodserial);
                    
            }
            
        }
        /*------------------------PLANTILLA-------------------------*/
        function seleccionar_plantilla(){
            var plantilla=$("#idplantilla").val();
            var explo = plantilla.split("-");
            var nombre=explo[1];
            var idplantilla=explo[0];
            if (idplantilla!=''){
                $("#alertando").html("");
                $("#btn_agrega").show();
                $("#erroralerta").hide();
                $("#titulov").html(nombre);
            } else {
                $("#btn_agrega").hide();
                $("#alertando").html("<span class='fa fa-info-circle'>&nbsp;Debe seleccionar la plantilla antes de continuar");
                $("#erroralerta").show();
            }
                
            $("#ventanamodal").modal('show');
            
        }        
        function agregar_plantilla(){
            var errores="";
            var plantilla=$("#idplantilla").val();
            var explo = plantilla.split("-");
            var nombre=explo[1];
            var idplantilla=explo[0];
            var idtransaccion=<?php echo $idtransaccion ?>;
            var idpedido=<?php echo $idpedido ?>;
            if (idplantilla==''){
                errores="Debe indicar plantilla para agregar!.";
            }
            var nperso=$("#cpersonas_plantilla").val();

            if (nperso==''){
                errores="Debe indicar cantidad de personas para plantilla!.";
            }
            if (nperso== 0){
                errores="Debe indicar cantidad de personas para plantilla!.";
            }
            if (errores==''){
                var parametros = {
                    "idtransaccion"        : idtransaccion,
                    "idplantilla" : idplantilla,
                    "cantidad_personas" : nperso,
                    "idpedido"            : idpedido
                };
                                    
                $.ajax({
                    data:  parametros,
                    url:   'cat_pedido_carrito_tmp_editar.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#carrito").html("");
                    },
                    success:  function (response) {
                        //alert(response);
                        $("#carrito").html(response);
                        setTimeout(function(e){ actualiza_carrito(); }, 1500);
                    }

                });
            } else {
                alert("error"+errores);
            }

        }

        /*------------------------PLANTILLA-------------------------*/
        
        
        
        
        function agregararplantilla(){
            var idtransaccion=<?php echo $idtransaccion ?>;
            var idpedido=<?php echo $idpedido ?>;
            var cantidad_personas=<?php echo $cantidad_personas ?>;
            
            
            
            var errores="";
            var idplantilla=$("#idplantilla").val();
            if (idplantilla==''){
                errores="Debe indicar plantilla para agregar!.";
            }
            var nperso=$("#cantidad_personas").val();
            if (nperso==''){
                errores="Debe indicar cantidad de personas para evento!.";
            }
            if (nperso== 0){
                errores="Debe indicar cantidad de personas para evento!.";
            }
            if (errores==''){
                var parametros = {
                    "idplantilla" : idplantilla,
                    "cantidad_personas" : nperso,
                    "idtransaccion"        : idtransaccion,
                    "cantidad_personas" : cantidad_personas,
                    "idpedido"            : idpedido
                };
                $.ajax({
                    data:  parametros,
                    url:   'cat_pedido_carrito_tmp_editar.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#carrito").html("");
                    },
                    success:  function (response) {
                        
                        $("#carrito").html(response);
                        actualiza_carrito();
                    }

                });
            } else {
                alert("error"+errores);
            }
        }
        function cambiarprecio(idventatmp){
            var parametros = {
               "idventatmp"   : idventatmp
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_mini_producto_cambiarprecio.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    $("#modal_cuerpo").html(response);    
                    $("#modal_titulo").html("Producto con precio variable");
                    $("#modal_ventana").modal("show");
                    //setTimeout(function(){ $("#busquedatxt").focus(); }, 1000);
                }
            });
            
            
        }
        function verificarprecio(minimo,maximo){
            var valorcito=parseFloat($("#precio").val());
            var sigue='S';
            if (valorcito < minimo){
                //alert('precio por debajo del minimo permitido');
                sigue='N';
            }
            if (valorcito > maximo){
                //alert('precio por encima del maximo permitido');
                sigue='N';
            }
            if (sigue=='S'){
                $("#regprecio").show();
                    //alert('desocultar');
            } else {
                $("#regprecio").hide();
            }
            
            
            
        }
        function desocultar(){
            $("#cuerpocolores").show();
            $("#enviocuerpo").show();
        }
        function buscarprod(){
            //buscador de productos
            var parametros = {
               
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_mini_filtroprod.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    $("#modal_cuerpo").html(response);    
                    $("#modal_titulo").html("Buscando productos.");
                    $("#modal_ventana").modal("show");
                    setTimeout(function(){ $("#busquedatxt").focus(); }, 1000);
                }
            });
            
            
        }
        /*-------------------------------APRETADOS-------------------------------------------*/
        function apretarauto(id,prod1,prod2,quien){
            //realiza el evento de agregar al carrito
        
            var idtransaccion=<?php echo $idtransaccion ?>;
            var idpedido=<?php echo $idpedido ?>;
            var cantidad_personas=<?php echo $cantidad_personas ?>;
            var cantidad=$("#canti_"+id).val();
            var prod1=0;
            var prod2=0;
            var precio=0;
            //alert(cantidad);
            if(cantidad ==''){
                cantidad=1;
            }
            if(prod1 > 0){
                var precio = 0;
            }else{
                //Lista de Productos
                //var html = document.getElementById("produlis_"+id).innerHTML;
                //var precio = document.getElementById("preciolis_"+id).value;            
            }
            var parametros = {
                    "prod" : id,
                    "cant" : cantidad,
                    "precio" : precio,
                    "prod_1" : prod1,
                    "prod_2" : prod2,
                    "idtransaccion": idtransaccion,
                    "idpedido"    : idpedido
            };
           $.ajax({
                    data:  parametros,
                    url:   'cat_pedido_carrito_tmp_editar.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#carrito").html("");
                    },
                    success:  function (response) {
                        //alert(response);
                        $("#carrito").html(response);
                        actualiza_carrito();
                    }
            });
    }

    function actualiza_carrito(){
        var idtransaccion=<?php echo $idtransaccion ?>;
        var idpedido=<?php echo $idpedido ?>;
        var cantidad_personas=<?php echo $cantidad_personas ?>;
        var parametros = {
                "act" : 'S',
                "idtransaccion" : idtransaccion,
                "idpedido" : idpedido,
                "cantidad_personas": cantidad_personas
            
                
        };
        $.ajax({
                data:  parametros,
                url:   'cat_tmp_cuerpucho_editar_new.php',
                type:  'post',
                beforeSend: function () {
                       $("#enviocuerpotmp").html("");
                },
                success:  function (response) {
                        $("#enviocuerpotmp").html(response);
                }
        });
}
    
    
    


function apretar_combo(idprodser){
    var canti=$("#can_"+idprodser).val();
    if (canti==0){
        canti=1;
    }    
    var idmesa=0;
    //para permitir agregar, debemos poner en un temporal y controlar accion de combos
     var parametros = {
        "id" : idprodser,
        "cantidad" : canti,
        "idmesa" : idmesa
     }
     $.ajax({
        data:  parametros,
        url:   'combo_ventas.php',
        type:  'post',
        beforeSend: function () {
        
            
            
        },
        success:  function (response) {
            $("#modal_titulo").html("Seleccionando opciones de combo");
            $("#modal_cuerpo").html(response);
            $("#modal_ventana").modal("show");
            setTimeout(function(e){ enfocar("filtrarprod",1000); }, 5000);
            
        }
     });                     
}
function agrega_prod_grupo(idprod,idlista){
    //alert(idlista);
    var html = $("#prod_"+idprod+'_'+idlista).html();
    var idmesa = 0;
    //var cant = $('cant_'+idprod+'_'+idlista).val();
    //alert(cant);
    var parametros = {
        "idlista" : idlista,
        "idprod" : idprod,
        "idmesa" : idmesa
    };
    $.ajax({
        data:  parametros,
        url:   'combo_ventas_add.php',
        type:  'post',
        beforeSend: function () {
            //$("#prod_"+idprod+'_'+idlista).html("Cargando Opciones...");
        },
        success:  function (response) {
            //alert(response);
            if(response == 'MAX'){
                $("#grupo_"+idlista).html('Cantidad Maxima Alcanzada');
            }else if(response == 'LISTO'){
                $("#grupo_"+idlista).html('Listo!');
            }else{
                $("#prod_"+idprod+'_'+idlista).html(html);
                $("#contador_"+idprod+'_'+idlista).html(response);
            }
        }
    });
}
function reinicia_grupo(id,prod_princ){
        var idmesa = 0;
        var parametros = {
                "idlista" : id
                
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas_del.php',
                type:  'post',
                beforeSend: function () {
                    //$("#lista_prod").html("Cargando Opciones...");
                },
                success:  function (response) {
                    if(response == 'OK'){
                        apretar_combo(prod_princ);
                    }else{
                        $("#lista_prod").html(response);
                    }
                }
        });
}
function terminar_combo(idprod_princ,cat){
        var html = $("#lista_prod").html();
        var idmesa = $("#idmesa").val();
        //alert(idmesa);
        //alert(idatc);
        var parametros = {
                "idprod_princ" : idprod_princ,
                "idmesa" : idmesa
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas_termina.php',
                type:  'post',
                beforeSend: function () {
                    $("#lista_prod").html("Registrando...");
                },
                success:  function (response) {
                    if(response == 'OK'){
                        //document.location.href='?cat='+cat;
                        $("#busqueda_prod").html('');
                        $("#codbar").val('');
                        $("#cant_cb").val('1');
                        actualiza_carrito(idmesa);
                        $("#modal_ventana").modal("hide");
                    }else if(response == 'NOVALIDO'){
                        $("#lista_prod").html(html);
                        alert("Favor seleccione todos los productos antes de terminar.");
                    }else{
                        $("#lista_prod").html(response);
                    }
                }
        });    
}
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        /*------------------------------------------------------------------------------------*/
        function seleccionarproducto(idprodserial){
            
            
            
            
            
            
        }
        //-------------------------DIRECCIONES---------------------//
        function seledire(valor){
            var direccion="";
            if (valor!=''){
                //alert(direccion);
                if (valor==99){
                    //required
                    direccion="";
                     $('#domicilio').prop("required", true);
                }  else {
                    direccion=$("#direh_"+valor).val();
                    //alert(direccion);
                     $('#domicilio').removeAttr("required");
                }
                $("#domicilio").val(direccion);
                controlar_valores();
            }    
        }
        //------------------------ Clientes------------------------->    
        function seleccionarcliente(){    
        //muestra los inputs para buscar el cliente
            $("#modal_titulo").html("<span class='fa fa-search'></span>&nbsp;&nbsp;Buscar cliente");
            var parametros = {
               
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_mini_eventos_clientes.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    $("#modal_cuerpo").html(response);    
                }
            });
          
            $("#modal_ventana").modal("show");
            setTimeout( enfocar(),300 );
            
        }
        function clientelista(datos){
            //es para seleccionar un cliente, basados en busqueda x like  de una lista
            var explo = datos.split(":");
            var idcli=explo[0];
            var nombre=explo[1];
            var errores='';
            //verificamos si selecciono la fecha y hora del evento_para
            //var pchar=$("#clientepedidochar").val();
            //var fchar=$("#clientefactuchar").val();
            var fechaev=$("#fechaevento").val();
            var horaev=$("#hora").val();
            //alert(horaev);
            if (fechaev===""){
                errores=errores+'-> Debe indicar la fecha del evento. \n';
            }
            if (horaev===""){
                errores=errores+'-> Debe indicar horario para evento. \n';
            }
            
            if (errores===''){
                    $("#clientel").val(nombre);
                    $("#clientelfac").val(nombre);
                    $("#evento_para").val(fechaev);
                    $("#hora_entrega").val(horaev);
                    $("#ocidclientefactural").val(idcli);
                    $("#ocidclientepedidol").val(idcli);
                    $("#cliente").val(nombre);
                    $("#modal_ventana").modal("hide");
                    $("#cuerpopedido").show();
                    $("#cpedidonuevo").click();
                    $("#cuerpocolores").show();
                    $("#enviocuerpo").show();
                    $("#cabeceraid").click();
                
                
            } else {
                alert(errores);
                
            }
            
        }
        function enfocar(){
            if (document.getElementById("dcu")){
                document.getElementById("dcu").focus();
            }
            
        }
        function verificartc(cual){
            
            var res='';
            
            
            
            
            
            if (cual==1){
                res=$('#fisico').prop("checked"); 
                if (res==true){
                    $('#juridico').prop("checked",false);
                    
                } 
                //$vv=$("#docum").val();
                    
            }
            if (cual==2){
                //juridico
                res=$('#juridico').prop("checked"); 
                if (res==true){
                    $('#fisico').prop("checked",false);
                    
                } 
                
                
            }
            
            
        }
        function bcliente(valorbusca,metodobusca){
            var bus_rz="";
            var bus_ruc="";
            var bus_doc="";
            bus_rz=$("#ncc").val();
            bus_ruc=$("#rucc").val();
            bus_doc=$("#dcum").val();
            
            if (metodobusca==1){
                //documento
                $("#rucc").val("");
                $("#ncc").val("");
                
            }
            if (metodobusca==2){
                //razon social, nombre o apellido
                $("#dcum").val("");
                $("#rucc").val("");
            }
            if (metodobusca==3){
                //ruc
                $("#dcum").val("");
                $("#ncc").val("");
                
            }
            
            
            
            var parametros = {
                "bus_rz" : bus_rz,
                "bus_ruc" :bus_ruc,
                "bus_doc": bus_doc
            };
            
            if (valorbusca!=''){
                $.ajax({          
                    data:  parametros,
                    url:   'cat_miniadd_clie.php',
                    type:  'post',
                    cache: false,
                    timeout: 3000,  // 
                    crossDomain: true,
                    beforeSend: function () {    
                                
                    },
                    success:  function (response) {
                        $("#cuerpoclientebusca").html(response);
                        var respu=$("#occlietot").val();
                        if (parseInt(respu)==0){
                                
                                $("#primero").hide();
                                $("#segundo").show();
                                
                                
                        } else {
                            if (parseInt(respu)>1){
                                $("#segundo").hide();
                                $("#primero").hide();
                            } else {
                                $("#primero").hide();
                                $("#segundo").show();
                            }
                        }
                    }
                });
            } else {
                $("#segundo").hide();
                $("#primero").hide();
                $("#cuerpoclientebusca").html("");
            }
        }
        function actualizar_precio(idunicocarrito){
            //alert(idunicocarrito);
            var precio=$("#precio_"+idunicocarrito).val();
            //alert(precio);
            var parametros = {
                   "idunico" : idunicocarrito,
                    "precio" :precio
                };
                $.ajax({
                        data:  parametros,
                        url:   'mini_cat_actualiza_precio.php',
                        type:  'post',
                        beforeSend: function () {

                        },
                        success:  function (response) {
                            //alert(response);
                            $("#varios_upd").html(response);
                            actualiza_carrito();
                        }
                });
        }     
        function actualizarcliente(idcliente){
            var dc=$("#dcu").val();
            var rz=$("#rz").val();
            var ruc=$("#ruc").val();
            var cel=$("#cel").val();
            var ema=$("#em").val();
            
                var parametros = {
                      "dc" : dc,
                    "rz" : rz,
                    "ruc" : ruc,
                    "cel" : cel,
                    "ema" : ema,
                    "add"    :1,
                    "idc"    :idcliente
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_miniadd_clie.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                            
                },
                success:  function (response) {
                    $("#cuerpoclientebusca").html(response);
                    if (response!=''){
                        var nm=response.split("|");
                        $("#ocidc").val(nm[0]);
                        $("#cliente").val(nm[1]);
                        $("#modal_ventana").modal("hide");
                    }
                }
            });
        }
        function registrarcliente(){
            var res="";
            
            var dc=$("#docu").val();
            var rz=$("#rz").val();
            var ape=$("#apellidos").val();
            var nom=$("#nombres").val();
            var ruc=$("#ruc").val();
            var cel=$("#celu").val();
            var ema=$("#em").val();
            var obs=$("#obclie").val();
            if ($('#fisico').prop("checked")){
                res=1;
            }
            if ($('#juridico').prop("checked")){
                res=2;
            }
                var parametros = {
                      "dc" : dc,
                    "rz" : rz,
                    "nombres" : nom,
                    "apellidos" : ape,
                    "ruc" : ruc,
                    "cel" : cel,
                    "ema" : ema,
                    "obs"  : obs,
                    "tipocliente" : res,
                    "add"    :1
                    
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_miniadd_clie.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                            
                },
                success:  function (response) {
                    //alert(response);
                    $("#cuerpoclientebusca").html(response);
                    
                    if (response!='error'){
                        if ($("#erroresvv").length > 0){
                            //alert('llego1');
                            var comprobar=$("#erroresvv").val();
                            //alert(comprobar);
                            if (comprobar==''){
                                
                                var nm=response.split("|");
                                //$("#clientel").val(nm[3]);
                              // $("#clientelfac").val(nm[1]);
                                $("#ocidc").val(nm[0]);
                                $("#cliente").val(nm[3]);
                                $("#ocidpedi").val(nm[2]);
                            
                                var idcliente=$("#ocidc").val();
                                var idclientepedido=$("#ocidpedi").val();
                                seleccionar(idcliente,idclientepedido,nm[3],nm[1]);
                                $("#modal_ventana").modal("hide");
                            }
                        } else {
                                var nm=response.split("|");
                                alert(nm[3]);
                              // $("#clientel").val(nm[3]);
                               //$("#clientelfac").val(nm[1]);
                                $("#ocidc").val(nm[0]);
                                $("#cliente").val(nm[3]);
                                $("#ocidpedi").val(nm[2]);
                                var idcliente=$("#ocidc").val();
                                var idclientepedido=$("#ocidpedi").val();
                                //alert(idcliente);alert(idclientepedido);
                                seleccionar(idcliente,idclientepedido,nm[3],nm[1]);
                                $("#modal_ventana").modal("hide");
                            
                        }
                    } else {
                        //alert('llego3');
                        $("#cuerpoclientebusca").html(response);
                    }
                }
            });
        }
        //Seleccionar el cliente al buscar
        function seleccionar(idcliente,idclientepedido,qp,fc){
            
        
            var dc=$("#dcu").val();
            var rz=$("#rz").val();
            var ruc=$("#ruc").val();
            var cel=$("#cel").val();
            var ema=$("#em").val();
            var errores='';
            //verificamos si selecciono la fecha y hora del evento_para
            var pchar=$("#clientepedidochar").val();
            var fchar=$("#clientefactuchar").val();
            var fechaev=$("#fechaevento").val();
            var horaev=$("#hora").val();
            //alert(horaev);
            if (fechaev===""){
                errores=errores+'-> Debe indicar la fecha del evento. \n';
            }
            if (horaev===""){
                errores=errores+'-> Debe indicar horario para evento. \n';
            }
            
            if (errores===''){
                $("#clientel").val(pchar);
                $("#clientelfac").val(fchar);
                $("#evento_para").val(fechaev);
                $("#hora_entrega").val(horaev);
                $("#ocidclientefactural").val(idcliente);
                $("#ocidclientepedidol").val(idclientepedido);
                $("#cliente").val(rz);
                $("#modal_ventana").modal("hide");
                $("#cpedido").click();
                //$("#cuerpoeventos").show();
                //$("#cuerpocolores").show();
                $("#enviocuerpo").show();
                setTimeout(function(){ $("#cabeceraid").click(); }, 500);
                setTimeout(function(){ $("#cpedidonuevo").click(); }, 1000);
                //$("#cabeceraid").click();
                
                //$("#clientel").val();
            } else {
                //alert(errores);
                $("#errordetalle1").html(errores);
                $("#errorcontinua").show();
            }
                    
        }
        function agregar(cual){
          
          $("#modal_titulo").html("Agregar tipos eventos");
          var parametros = {
              
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_mini_eventos_tipos_add.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                    //$("#modal_cuerpo").html('Cargando...');                
                },
                success:  function (response) {
                    $("#modal_cuerpo").html(response);    
                }
            });
          
          
          
         
          $("#modal_ventana").modal("show");
      }
       function registrar(cual){
          var describe=$("#describetipoev").val();
          if (describe!=""){
               var parametros = {
                      "describe" : describe,
                    "agregar" : 1
                };
                $.ajax({          
                    data:  parametros,
                    url:   'cat_mini_eventos_tipos.php',
                    type:  'post',
                    cache: false,
                    timeout: 3000,  // I chose 3 secs for kicks: 3000
                    crossDomain: true,
                    beforeSend: function () {    
                                        
                    },
                    success:  function (response) {
                        $("#tiposeventosdiv").html(response);
                        
                    }
                });

              
          }
         
         
          
          
         
          $("#modal_ventana").modal("hide");
      }
      function controlar_valores(){
          var idtransaccion=<?php echo $idtransaccion ?>;
          var fecha_evento=$("#fechaevento").val();
          var hora_evento=$("#hora").val();
          var cliente_ped=$("#cliente_ped").val();
          var cliente_fac=$("#cliente_fac").val();
          var cantidad_personas=$("#cantidad_personas").val();
          var idtipoevento=$("#idtipoev").val();
          var suculista=$("#suculista").val();
          var direcharentrega=$("#domicilio").val();
          var ubicacion=$("#ubicacion").val();
          var colores=$("#detalles").val();
          var comentarios=$("#interno").val();
          var iddecorador=$("#listadeco").val();
          var idorganizador=$("#listaorg").val();
          var remitente=$("#remitente").val();
          var celuremi=$("#telefono_remitente").val();
          var textoremi=$("#textotarjeta").val();
          var ciudad=$("#ciudades").val();
          var barrio=$("#barrios").val();
          
         var parametros = {
              "idtransaccion"     :idtransaccion,
              "fecha_evento"      : fecha_evento,
              "hora_evento"          :hora_evento,
              "cliente_ped"          :cliente_ped,
              "cliente_fac"          :cliente_fac,
              "cantidad_personas" :cantidad_personas,
              "idtipoevento"       :idtipoevento,
              "suculista"          :suculista,
              "direcharentrega"      :direcharentrega,
              "ubicacion"          :ubicacion,
              "colores"              : colores,
              "comentarios"       : comentarios,
              "iddecorador"        : iddecorador,
              "idorganizador"    :idorganizador,
              "remitente"        : remitente,
              "celuremi"        : celuremi,
              "textoremi"        : textoremi,
              "ciudad"            : ciudad,
              "barrio"            : barrio
              
        };
                $.ajax({          
                    data:  parametros,
                    url:   'cat_mini_guarda_cabecera.php',
                    type:  'post',
                    cache: false,
                    timeout: 5000,  // I chose 3 secs for kicks: 5000
                    crossDomain: true,
                    beforeSend: function () {
                        $("#varios_upd").html('Cargando...');                
                    },
                    success:  function (response) {
                        //alert(response);
                        $("#varios_upd").html(response);
                        //setTimeout(function(){ recalculin(idtransaccion); }, 3000);
                        //
                            
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
      
        //document.onkeydown = function(e){
            //var ev = document.all ? window.event : e;
            //if(ev.keyCode==13) {
            //    alert("asdasd");
            //}
       // }
       function boton_recalculo(idt){
           recalculin(idt);
       }
       function boton_recalculo2(valor,idplanti){
           recalculin_plantilla(valor,idplanti);
       }
       function boton_recalculo_catering(valor){
           recalculin_combo(valor);
       }
       function recalculin(idtransaccion){
           var cp=$("#cantidad_personas").val();
           var idpedido=<?php echo $idpedido ?>;
            var parametros = {
              "idtransaccion"     :idtransaccion,
              "cantidad_personas"    :cp,
              "clase"            :2
        };
        $.ajax({          
            data:  parametros,
            url:   'cat_recalculin.php',
            type:  'post',
            cache: false,
            timeout: 5000,  // I chose 3 secs for kicks: 5000
            crossDomain: true,
            beforeSend: function () {
                $("#varios_upd").html('Cargando...');                
            },
            success:  function (response) {
            //alert(response);
                $("#varios_upd").html(response);
                //actualizacarrito();
                window.open("cat_pedidos_new_editar.php?id="+idpedido,"_self"); 
                
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
       function recalculin_plantilla(idunico,idplantilla){
           var idtransaccion=<?php echo $idtransaccion ?>;
           var cp=$("#cantidad_"+idunico).val();
           var idpedido=<?php echo $idpedido ?>;
            var parametros = {
              "idtransaccion"     :idtransaccion,
              "cantidad_personas"    :cp,
              "clase"            :2,
              "idplantilla"        :idplantilla
        };
                $.ajax({          
                    data:  parametros,
                    url:   'cat_recalculin_individual.php',
                    type:  'post',
                    cache: false,
                    timeout: 5000,  // I chose 3 secs for kicks: 5000
                    crossDomain: true,
                    beforeSend: function () {
                        $("#varios_upd").html('Cargando...');                
                    },
                    success:  function (response) {
                    //alert(response);
                        $("#varios_upd").html(response);
                        //actualizacarrito();
                        window.open("cat_pedidos_new_editar.php?id="+idpedido,"_self"); 
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
       function recalculin_combo(idunico){
           var idtransaccion=<?php echo $idtransaccion ?>;
           var cp=$("#cantidad_"+idunico).val();
           var idpedido=<?php echo $idpedido ?>;
           var idunicopadre=idunico;
            var parametros = {
              "idtransaccion"     :idtransaccion,
              "cantidad_personas"    :cp,
              "clase"            :2,
              "idplantilla"        :0,
              "idunicopadre"    : idunicopadre
        };
                $.ajax({          
                    data:  parametros,
                    url:   'cat_recalculin_combo.php',
                    type:  'post',
                    cache: false,
                    timeout: 5000,  // I chose 3 secs for kicks: 5000
                    crossDomain: true,
                    beforeSend: function () {
                        $("#varios_upd").html('Cargando...');                
                    },
                    success:  function (response) {
                        //alert(response);
                        $("#varios_upd").html(response);
                        //actualizacarrito();
                        //window.open("cat_pedidos_new_editar.php?id="+idpedido,"_self"); 
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
       
       
       
       
       
       function recalcular_total(){
          
           var idt=<?php echo $idtransaccion ?>;
           var porcen=$("#descuento").val();
           var parametros = {
                      "idt" : idt,
                    "porcenta"  : porcen,
                    "idpedido"    : <?php echo $_REQUEST['id'] ?>
                };
                $.ajax({          
                    data:  parametros,
                    url:   'cat_mini_subtotal.php',
                    type:  'post',
                    cache: false,
                    timeout: 3000,  // I chose 3 secs for kicks: 3000
                    crossDomain: true,
                    beforeSend: function () {    
                                        
                    },
                    success:  function (response) {
                        //alert(response);
                        $("#recalculo").html(response);
                        
                    }
                });
          
           
       }
        function proceder(idt,clase){
           var message="Desea enviar el email?"
           $("#idfoc").val(clase);
            
           var clientepedido=$("#cliente_ped").val();
           if (clientepedido!=''){
               var result = window.confirm(message);
               if (result==true){
                   $("#envmail").val(1);
                    $("#form1").submit();
               } else {
                   $("#envmail").val(0);
                    $("#form1").submit();
                   
               }
            } else {
                alert("Debe indicar el cliente del pedido antes de finalizar el presupuesto.");
                
                
                
            }
          
           
           
       }
       function proceder_salir(idt){
          window.open("cat_pedidos_new_editar.php?idcerrar="+idt,"_self");
          
           
           
       }
        </script>
        <!-- footer content -->
        <script src="../js/shortcut.js"></script>
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
        
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
<script>
            $( document ).ready(function() {
            setInterval(function(){actualiza_carrito();},70000);
        });

        
        </script>
  </body>
</html>
