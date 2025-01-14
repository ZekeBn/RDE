 <?php
/*----------------------------------------------------------
    29/04/2022: se agrega plantilla de articulos y se le pasa al carrito
    06/05/2022: se mejora operativa de  busqueda de productos
    y edicion de cantidades en carrito

    Nueva modalidad de carga y toma de pedidos para catering

-----------------------------------------------------------
*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");

$buscar = "Select usa_listaprecio,usa_plantilla_articulo from preferencias";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



//print_r($_POST);exit;
//Seccion de finalizacion de un pedido en curso
if (isset($_POST['ocregev'])) {
    $idreg = intval($_POST['ocregev']);
    if ($idreg > 0) {
        //almacenamos los items y cerramos el pedido activo
        $update = "update pedidos_eventos set estado=3,confirmado_pedido_el=current_timestamp where regid=$idreg";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //Marcamos los componentes en tmpventares del usuario
        $update = "update tmp_ventares set idpedidocat=$idreg,finalizado='S',impreso_coc='S' where usuario=$idusu and finalizado='N'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        header("location: cat_pedidos_new.php");
    }
}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

if (isset($_POST['fechaevento'])) {
    //print_r($_POST);
    //exit;
    /*--------------------------------------------------------------------------*/
    //Identificador del presupuesto: si es que hay
    $idpresupuesto = intval($_POST['idpresupuesto']);//so lo si ya se genero previamnete un presu
    $idtipoevento = intval($_POST['idtipoev']);
    $fechaevento = date("Y-m-d", strtotime($_POST['evento_para']));
    $horaevento = date("H:i:s", strtotime($_POST['hora_entrega']));
    $cantpersona = intval($_POST['cantidad_personas']);
    $fechavalidez = antisqlinyeccion($_POST['valido_hasta'], 'date');
    //-----------------------------------------EXPLOTAR---------------------------------------------------//
    $ex1 = explode("|", $_REQUEST['cliente_ped']);
    $idclientepedido = intval($ex1[0]);//Id del cliente que hace el pedido
    $idsucursalcpedido = intval($ex1[1]);//Id de la sucursal del cliente que hace el pedido
    //Traemos la razon social del cliente que hace el pedido
    $buscar = "Select razon_social from cliente where idcliente=$idclientepedido ";
    $rz1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $clienterz = antisqlinyeccion($rz1->fields['razon_social'], 'text');
    //---------------------------------------------------------------------------------------------------//

    $ex2 = explode("|", $_REQUEST['cliente_fac']);
    $idclientefactura = intval($ex2[0]);//Id del cliente que hace el pedido
    $idsucursalcfactura = intval($ex2[1]);//Id de la sucursal del cliente que hace el pedido
    //----------------------------------------------------------------------------------------------//

    //$idclientepedido=intval($_POST['ocidclientepedidol']);//CLiente que generÃ³ el pedido   ocidcsoli
    //$idclientefactura=intval($_POST['ocidclientefactural']);//ocidc Cliente de facturacion
    $idclienterecibe = intval($_POST['ocidclientefactural']); //ocidcrec quien va recibir el pedido actual


    if ($idclienterecibe == 0) {
        $idclienterecibe = $idclientepedido;//si no vino x post, le asignamos al que genero el pedido
    }

    $direccion = antisqlinyeccion($_POST['domicilio'], 'text');

    $iddireccion = intval($_POST['iddomicilio']);
    $listaprecio = intval($_POST['listapre']);

    $idorganizador = intval($_POST['listaorg']);
    $iddecorador = intval($_POST['listadeco']);

    $comentariovisible = antisqlinyeccion($_POST['detalles'], 'text');

    $comentariointerno = antisqlinyeccion($_POST['interno'], 'text');
    $tipoenvio = intval($_POST['tipoenviooc']);



    //echo "$idtmpventarescab";exit;




    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        //$errores.="- Se detecto un intento de envio doble, recargue la pagina.<br />";
        //$valido="N";
    }
    if (trim($_POST['form_control']) == '') {
        //$errores.="- Control del formularios no activado.<br />";
        //$valido="N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    if ($tipoenvio == 99 && $direccion == '') {
        $valido = "N";
        $errores .= " - Al ser delivery debe indicar la direcci&oacote;n de env&iacute;o.<br />";
    }

    $ubicacion = trim($_POST['ubicacion']);
    //echo $ubicacion;exit;

    // si todo es correcto inserta
    if ($valido == "S") {
        //Marcamos los componentes en tmpventares del usuario
        $buscar = "select sum(subtotal) as total from  tmp_ventares where usuario=$idusu and finalizado='N' and borrado='N'";
        $rgt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mt = floatval($rgt->fields['total']);
        //echo terminado;exit;

        //Creamos la cabecera del tmpventarescab
        $consulta = "
        INSERT INTO tmp_ventares_cab
        (razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,clase,tipoventa,delivery_zona,delivery_costo,nombre_deliv,apellido_deliv,idclientedel,iddomicilio,idmotoristaped,idcanalventa,direccion,telefono) 
        VALUES 
        ($clienterz, '$ruc_pred', NULL, NULL, $mt, $idusu, '$ahora', $idsucursal, $idempresa,7,'N',0,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)
        ";
        //echo $consulta;
        //exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $buscar = "Select max(idtmpventares_cab) as mayor from tmp_ventares_cab where idusu=$idusu and estado =1";
        $tl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtmpventarescab = intval($tl->fields['mayor']);

        //Verificamos la direccion ingresada si es que ya no existe en la base, si la misma ya esxiste
        if ($direccion != '') {
            $ub = trim($_REQUEST['ubicacion']);
            $ex = explode(",", $ub);
            $lat = trim($ex[0]);
            $lon = trim($ex[1]);

            if ($iddomicilio > 0) {

                $buscar = "Select * from clientes_direcciones where iddireccion=$iddomicilio  ";
            } else {

                $buscar = "Select * from clientes_direcciones where descripcion=$direccion  ";

            }
            $rdir = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo "DIRECCION: ".$buscar;exit;
            $iddireccion = intval($rdir->fields['iddireccion']);
            //echo $direccion;exit;
            /*if ($iddireccion==0 &&  !empty($direccion)){
                //Damos de alta la direccion
                $insertar="Insert into clientes_direcciones
                (descripcion,latitud,longitud,compuesta,observacion,idcliente,estado,registrado_por,registrado_el)
                values
                ($direccion,'$lat','$lon','$ub',NULL,$idclientefactura,1,$idusu,current_timestamp)

                ";
                $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));

                //traemos el id de la direccion
                $buscar="Select iddireccion from clientes_direcciones where registrado_por=$idusu and idcliente=$idclientefactura limit 1";
                $rdir=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
                $iddireccion=intval($rdir->fields['iddireccion']);
            }*/

        }

        $tipoenvio = intval($_POST['tipoenviooc']);

        $consulta = "
        insert into pedidos_eventos
        (idpresupuesto, idtipoev, id_cliente_solicita, fecha_solicitud, evento_para, hora_entrega, iddomicilio, cantidad_personas, adultos, ninhos, comentarios, idlistaprecio, estado, registrado_el, registrado_por, valido_hasta,
        idorganizador,iddecorador,comentario_interno,idcliente_recibe,idcliente_factura,tipoenvio,dire_entrega)
        values
        ($idpresupuesto, $idtipoevento, $idclientepedido,current_timestamp, '$fechaevento', '$horaevento', $iddireccion, $cantpersona, 0,
        0, $comentariovisible, $listaprecio, 1, current_timestamp, $idusu,$fechavalidez,
        $idorganizador,$iddecorador,$comentariointerno,$idclienterecibe,$idclientefactura,$tipoenvio,$direccion)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //traemos el id de la cabecera
        $buscar = "Select regid from pedidos_eventos where id_cliente_solicita=$idclientepedido and registrado_por=$idusu order by regid desc limit 1";
        $rg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $idreg = intval($rg->fields['regid']);
        //header("location: cat_pedidos_cuerpo.php?evreg=$idreg");
        //almacenamos los items y cerramos el pedido activo
        $update = "update pedidos_eventos set estado=3,confirmado_pedido_el=current_timestamp,ubicacion_comp='$ub',lati='$lat',longi='$lon'
        where regid=$idreg";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $ahora = date("Y-m-d H:i:s");
        $delivery = 'N';

        //Marcamos los componentes en tmpventares del usuario
        $update = "update tmp_ventares set idpedidocat=$idreg,finalizado='S',impreso_coc='S',idtmpventares_cab=$idtmpventarescab where usuario=$idusu and finalizado='N'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //echo terminado;exit;

    }
    /*-------------------------------------------------------------------------------*/

}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
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
function busca_cliente(idcampo){
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
    if(tipo == 'ruc'){
        razon_social = '';
        fantasia = '';
        documento = '';
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
        $("#documento_cat").val('');
    }
    if(tipo == 'razon_social'){
        ruc = '';
        fantasia = '';
        documento = '';
        $("#ruc_cat").val('');
        $("#fantasia_cat").val('');
        $("#documento_cat").val('');
    }
    if(tipo == 'fantasia'){
        ruc = '';
        razon_social = '';
        documento = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#documento_cat").val('');
    }
    if(tipo == 'documento'){
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
      "idcampo"       : idcampo
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
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        var idcampo = obj.idcampo;
        var ruc = obj.ruc;
        var razon_social = obj.razon_social;
        var nombres = obj.nombres;
        var apellidos = obj.apellidos;
        
        $("#"+idcampo).val(idcliente+'|'+idsucursal_clie+'|'+razon_social);
        $('#modal_ventana').modal('hide');
        var cli_ped = $("#cliente_ped").val();
        var cli_fac = $("#cliente_fac").val();
        if(cli_ped != '' && cli_fac != ''){
            llenarfechas();
            $("#cpedidonuevo").click();
            document.location.href='#cpedidonuevo';
        }


    }else{
        alert(valor);    
    }

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
function agrega_cliente(idcampo){
        var direccionurl='cliente_agrega_cat.php';
        var parametros = {
              "new" : 'S',
              "idcampo" : idcampo,
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
    
    if($('#r1').is(':checked')) { var idclientetipo=1; }
    if($('#r2').is(':checked')) { var idclientetipo=2; }
    
    if(idclientetipo == 1){
       var razon_social_add = nombres_add+' '+apellidos_add;
    }
    
    
        var direccionurl='cliente_agrega_cat.php';
        var parametros = {
            "new" : 'S',
            "MM_insert" : 'form1',
            "ruc" : ruc_add,
            "nombre" : nombres_add,
            "apellido" : apellidos_add,
            "documento" : documento_add,
            "razon_social" : razon_social_add,
            "idclientetipo" : idclientetipo,
            "idcampo" : idcampo,
            
            
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
                    //{"ruc":"222555-7","razon_social":"JUAN PEREZ","nombre_ruc":"JUAN","apellido_ruc":"PEREZ","idcliente":"352","idsucursal_clie":"355","valido":"S"}
                    //222555-7
                    //alert(response);
                    if(IsJsonString(response)){
                        var obj = jQuery.parseJSON(response);

                        //alert(obj.error);
                        //alert(obj.valido);
                        if(obj.valido == 'S'){
                            //alert(idcampo);
                            $("#"+idcampo).val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+obj.razon_social);
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
            
            <form id="form1" name="form1" method="post" action="cat_pedidos_new.php">
            
            <div class="row" >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar / Registrar Pedidos </h2>
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
                    
                          
                        
                          
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Evento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input class="form-control" type="date" name="fechaevento" id="fechaevento" required="required"   />                    
    </div>
</div>
                      
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora Evento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input class="form-control" type="time" name="hora" id="hora" required="required"  />
    </div>
</div>
                      
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Pedido *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="cliente_ped" id="cliente_ped" class="form-control" value="" onClick="busca_cliente('cliente_ped');" readonly style="cursor: pointer;" />
 
    </div>
</div>
                      
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Facturacion *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="cliente_fac" id="cliente_fac" readonly  class="form-control" value="" onClick="busca_cliente('cliente_fac');" readonly style="cursor: pointer;" />

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
            <div class="row" id="cuerpopedido" >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-money"></span></span>&nbsp;Datos de Cliente , Facturaci&oacute;n y Evento</h2>    
                                
                    <ul class="nav navbar-right panel_toolbox">
                      <li ><a id="cpedidonuevo" class="collapse-link"><i class="fa fa-chevron-down"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" style="display:none" >
                    <div class="col-md-12">
                        <?php if (trim($errores) != "") { ?>
                            <div class="alert alert-danger alert-dismissible fade in" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                            </button>
                            <strong>Errores:</strong><br /><?php echo $errores; ?>
                            </div>
                        <?php } ?>
                        
                                        <form id="form1" name="form1" method="post" action="cat_pedidos_cuerpo.php">
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
                                                    <select name="listapre" id="listapre" style="height:40px; width: 80%;">
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
                                            <?php if ($usa_plantilla_articulos == 'S') { ?>
                                            <div class="col-md-4 col-sm-4 form-group">
                                                
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <label >Agregar Plantilla </label>
                                                <?php
                                                    $consulta = "
                                            select *
                                            from plantilla_articulos 
                                            where 
                                             estado = 1 
                                            order by nombre_plantilla asc
                                            ";
                                                $rsl = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                                                $tl = $rsl->RecordCount();
                                                if ($tl == 1) {
                                                    $selected = "selected='selected' ";
                                                }
                                                if ($tl > 0) {

                                                    ?>   
                                                    <select name="idplantilla" id="idplantilla" style="height:40px; width: 60%;">
                                                        <option value="" selected="selected">Seleccionar</option>
                                                        <?php while (!$rsl->EOF) {?>
                                                        <option value="<?php echo $rsl->fields['idplantillaart']?>" <?php if ($_POST['idplantilla'] == $rsl->fields['idplantillaart']) { ?> selected="selected"<?php } ?><?php echo $selected ?>><?php echo $rsl->fields['nombre_plantilla']?></option>

                                                        <?php $rsl->MoveNext();
                                                        }?>
                                                    </select>
                                                    <button type="button" onclick="agregararplantilla()" class="btn btn-sm-secondary"><span class="fa fa-plus"></span></button>
                                                    <?php }?>
                                                </div>
                                            </div>    
                                            <?php } ?>
    
                                    
                                    <!----------------------------------------------------------->
                    </div>
                    <div class="col-md-12">
                        <div class="clearfix"></div>
                        <hr />        
                                            
                            <div class="col-md-3 col-sm-3 form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Evento *</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="date" name="evento_para" id="evento_para" value="<?php  if (isset($_POST['evento_para'])) {
                                    echo htmlentities($_POST['evento_para']);
                                } else {
                                    if ($_REQUEST['fechaevento']) {
                                        echo htmlentities($_REQUEST['fechaevento']);
                                    } else {
                                        echo htmlentities($rs->fields['evento_para']);
                                    }
                                }?>"
                                       placeholder="Evento para" class="form-control" required="required" />                    
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-3 form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora entrega *</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="time" name="hora_entrega" id="hora_entrega" value="<?php  if (isset($_POST['hora_entrega'])) {
                                    echo htmlentities($_POST['hora_entrega']);
                                } else {

                                    if ($_REQUEST['fechaevento']) {
                                        echo htmlentities($_REQUEST['hora']);
                                    } else {
                                        echo htmlentities($rs->fields['hora_entrega']);
                                    }
                                }?>" placeholder="Hora entrega" class="form-control" required="required" />                    
                                </div>
                            </div>    
                            <div class="col-md-3 col-sm-3 form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad personas </label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="text" name="cantidad_personas" id="cantidad_personas" value="<?php  if (isset($_POST['cantidad_personas'])) {
                                    echo intval($_POST['cantidad_personas']);
                                } else {
                                    echo intval($rs->fields['cantidad_personas']);
                                }?>" placeholder="Cantidad personas" class="form-control" />                    
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3 form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Evento</label>
                                <div class="col-md-9 col-sm-9 col-xs-12" id="tiposeventosdiv">
                                    <?php require_once("cat_mini_eventos_tipos.php"); ?>

                                </div>
                            </div>
                            <div class="clearfix"></div>
                    

                            <div class="col-md-3 col-sm-3 form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Lugar Entrega  </label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <select name="suculista" id="suculista" style="height: 40px; width: 90%;" required="required" onClick="seledire(this.value);">
                                        <option value="" selected="selected">Seleccionar</option>
                                        <option value="99" >Delivery</option>
                                        <?php
                                            $buscar = "Select * from sucursales where estado <> 6 
                                            and idsucu in(select idsucursal from sucursal_parametros where idsucursal=sucursales.idsucu)
                                            order by nombre asc";
$rsuc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tsuc = $rsuc->RecordCount();
if ($tsuc > 0) {
    while (!$rsuc->EOF) {
        ?>
                                                <option value="<?php echo $rsuc->fields['idsucu'] ?>"><?php echo $rsuc->fields['nombre'] ?></option>
                                                
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
                            <div class="col-md-3 col-sm-3 form-group">
                                
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Dir. Entrega <?php echo $aste;?></label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" name="domicilio" id="domicilio" value="<?php  if (isset($_POST['domicilio'])) {
                                        echo trim($_POST['domicilio']);
                                    }?>" placeholder="Direccion de entrega / Domicilio"  style="height: 40px;width:100%;"  />    <?php if ($gg == 1) { ?> <button type="button"  class="btn btn-dark go-class" onClick="registradireccion();"><span class="fa fa-plus"></span> </button>  <?php } ?>
                                    <input type="hidden" name="iddomicilio" id="iddomicilio" value="" />
                                </div>
                            </div>        
                            <div class="col-md-3 col-sm-3 form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Ubicacion / Gmaps  </label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" name="ubicacion" id="ubicacion" class="form-control" style="width: 100%;">
                                </div>
                            </div>    

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
            
            
            
            <div class="row" id="cuerpocolores"  >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-edit"></span></span>&nbsp; Comentarios Pedido</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link" onclick="desocultar();"><i class="fa fa-chevron-down"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" id="" style="display:none">
                    
                        <div class="col-md-6 col-sm-6 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Organizador </label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                                <select name="listaorg" id="listaorg" style="height:40px; width: 80%;">
                                <option value="" selected="selected">Seleccionar</option>
                                <?php while (!$rslo->EOF) {?>
                                <option value="<?php echo $rslo->fields['idorganizador']?>" <?php if ($_POST['listaorg'] == $rslo->fields['idorganizador']) { ?> selected="selected"<?php } ?>><?php echo $rslo->fields['nombre']?></option>

                                <?php $rslo->MoveNext();
                                }?>
                                    </select>                 
                        </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Decorador</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                     <select name="listadeco" id="listadeco" style="height:40px; width: 80%;">
                                    <option value="" selected="selected">Seleccionar</option>
                                    <?php while (!$rsd->EOF) {?>
                                    <option value="<?php echo $rsd->fields['iddecorador']?>" <?php if ($_POST['listadeco'] == $rsd->fields['iddecorador']) { ?> selected="selected"<?php } ?>><?php echo $rsd->fields['nombre']?></option>

                                    <?php $rsd->MoveNext();
                                    }?>
                                    </select>        
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Detalles  / Colores</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="detalles" id="detalles" rows="4" style="width: 90%;"></textarea>                
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario Interno</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="interno" id="interno" rows="4" style="width: 90%;"></textarea>                    
                            </div>
                        </div>
                        <div class="clearfix"></div>

                  </div>
                </div>
              </div>
            </div>
            <div class="row" id="enviocuerpotmp">
              <?php require_once("cat_tmp_cuerpucho.php"); ?>
            </div>
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
                        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
                            <button type="button" class="btn btn-success" onclick="proceder();" ><span class="fa fa-check-square-o"></span> Continuar</button>
                        </div>
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
            
            
            <!-- SECCION --> 
           <!-- <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo"  style="height: auto;" >
                        ...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>--> 
          
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

        <!-- POPUP DE MODAL OCULTO -->
          
          
          </div>
        </div>
        <!-- /page content -->
        <script>
        function simulateKeyPress(character) {
          jQuery.event.trigger({ type : 'keypress', which : character.charCodeAt(9) });
        }
        function verificacantidad(idp){
            //alert("Llega");
            $("#errorhtml").hide();
            var comple="";
            var nperso=$("#cantidad_personas").val();
            comple=idp+"&cp="+nperso;
            var errores="";
            if (nperso==''){
                errores="Debe indicar cantidad de personas para evento!.";
                
            }
            if (nperso== 0){
                errores="Debe indicar cantidad de personas para evento!.";
                
            }
            if (errores==''){
                //enviar
                window.location = ('combo_catering_ventas.php?idp='+comple);
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
                var parametros = {
                    "valor"    :valorbuscar
                };
                if (valorbuscar!=''){
                    $.ajax({          
                        data:  parametros,
                        url:   'cat_mini_filtroprod_busqueda.php',
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
            if (tecla==13){
                var cantidad=$("#cantidad_"+idtemporal).val();
                //alert(cantidad);
                var parametros = {
                    "cantidad"    :cantidad,
                    "cual"        :cual,
                    "idtemporal"    :idtemporal,
                    "idprodserial"  :idprodu
                };
                
                        $.ajax({          
                            data:  parametros,
                            url:   'cat_tmp_cuerpucho.php',
                            type:  'post',
                            cache: false,
                            timeout: 3000,  // I chose 3 secs for kicks: 3000
                            crossDomain: true,
                            beforeSend: function () {    
                                                
                            },
                            success:  function (response) {
                                $("#enviocuerpotmp").html(response);    
                                setTimeout(function(){ buscarprod(); }, 1000);
                    
                            }
                        });
            } 
        }
        function eliminar(idprodserial,cual){
            var parametros = {
                    "cual"        :2,
                    "idprodserial"    :idprodserial
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_tmp_cuerpucho.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    $("#enviocuerpotmp").html(response);    
                    setTimeout(function(){ buscarprod(); }, 1000);
        
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
        function agregararplantilla(){
            var idplantilla=$("#idplantilla").val();
            if (idplantilla!=''){
                
                var parametros = {
                        "idplantilla" : idplantilla
                };
               $.ajax({
                        data:  parametros,
                        url:   'carrito.php',
                        type:  'post',
                        beforeSend: function () {
                                
                                    $("#carrito").html("");
                                
                        },
                        success:  function (response) {
                            //alert(response);
                            actualiza_carrito();
                        }
                        
                });
                
                
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
                    "prod_2" : prod2
            };
           $.ajax({
                    data:  parametros,
                    url:   'carrito.php',
                    type:  'post',
                    beforeSend: function () {
                            if(prod1 > 0){
                                
                            }else{
                                
                                $("#carrito").html("");
                            }
                    },
                    success:  function (response) {
                        //alert(response);
                            if(prod1 > 0 && parseInt(response) > 0){
                                
                                //$("#carrito").html("Actualizando Carrito...");
                                $("#can_"+id).val("");
                                $("#busquedatxt").val("");
                                actualiza_carrito();
                                $("#modpop").modal("hide");
                                $("#bprodu").focus();
                            }else{
                                $("#canti_"+id).val("");
                                $("#busquedatxt").val("");
                                $("#busquedatxt").focus();
                                //Cerramos popup
                                $("#modpop").modal("hide");
                                actualiza_carrito();
                                //enfocarbusqueda();
                            }
                    }
            });
    }

    function actualiza_carrito(){
    //alert('llega');
        var parametros = {
                "act" : 'S'
        };
        $.ajax({
                data:  parametros,
                url:   'cat_tmp_cuerpucho.php',
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
      
        //document.onkeydown = function(e){
            //var ev = document.all ? window.event : e;
            //if(ev.keyCode==13) {
            //    alert("asdasd");
            //}
       // }
       function proceder(){
           $("#form1").submit();
           
           
       }
        </script>
        <!-- footer content -->
        <script src="../js/shortcut.js"></script>
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
