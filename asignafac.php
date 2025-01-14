 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

if ($facturador_electronico == 'S') {
    echo "Esta operacion no esta permitida para facturadores electronicos.";
    exit;
}

//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja_new.php'/>"     ;
    exit;
}

// rellenar retroactivo
$consulta = "
insert into sucursal_cliente
(idcliente, sucursal, direccion, telefono, mail, estado, registrado_por, registrado_el, `borrado_por`, `borrado_el` )
SELECT 
idcliente, 'CASA MATRIZ', cliente.direccion, NULL, NULL, cliente.estado, 1, '$ahora', NULL, NULL 
FROM `cliente` 
where 
idcliente not in (select sucursal_cliente.idcliente from sucursal_cliente)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idventa = intval($_GET['vta']);

$buscar = "Select factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,ventas.tipo_venta,
ventas.idcliente, ventas.idsucursal_clie
from ventas
inner join cliente on cliente.idcliente=ventas.idcliente
where 
cliente.idempresa=$idempresa 
and ventas.idempresa=$idempresa 
and ventas.idcaja=$idcaja
and ventas.idventa = $idventa


and (ventas.factura is null or ventas.factura = '')

order by fecha desc
limit 10
";
$rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idventa = intval($rsvv->fields['idventa']);
$tdata = $rsvv->RecordCount();
$tipo_venta = intval($rsvv->fields['tipo_venta']);
$idcliente = intval($rsvv->fields['idcliente']);
$idsucursal_clie = intval($rsvv->fields['idsucursal_clie']);
if (intval($idsucursal_clie) == 0) {
    $consulta = "
    update sucursal_cliente set estado = 1 where idcliente = 1 and estado = 6
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    SELECT idsucursal_clie 
    FROM sucursal_cliente 
    WHERE 
    idcliente = $idcliente 
    and estado = 1
    order by idsucursal_clie asc 
    limit 1
    ";
    $rssuccli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idsucursal_clie = intval($rssuccli->fields['idsucursal_clie']);
}


if ($idventa == 0) {
    header("location: gest_impresiones.php");
    exit;
}


// PROXIMA FACTURA
$ano = date("Y");
// busca si existe algun registro
$buscar = "
Select idsuc, numfac as mayor 
from lastcomprobantes 
where 
idsuc=$factura_suc 
and pe=$factura_pexp 
and idempresa=$idempresa 
order by ano desc 
limit 1";
$rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//$maxnfac=intval(($rsfactura->fields['mayor'])+1);
// si no existe inserta
if (intval($rsfactura->fields['idsuc']) == 0) {
    $consulta = "
    INSERT INTO lastcomprobantes
    (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
    numhoja, hojalevante, idempresa) 
    VALUES
    ($factura_suc, 0, 0, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
    NULL, 0, '', $idempresa)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
$ultfac = intval($rsfactura->fields['mayor']);
if ($ultfac == 0) {
    $maxnfac = 1;
} else {
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
}
// verificar si es autoimpresor o preimpreso para el formulario
$timbradodatos_prev = timbrado_tanda($factura_suc, $factura_pexp, $idempresa);
$tipoimpreso = trim($timbradodatos_prev['tipoimpreso']);

if ($_POST['MM_update'] == 'form1') {

    $valido = "S";
    $errores = "";

    $factura = antisqlinyeccion(solonumeros(trim($_POST['factura'])), "text");
    $idcliente = antisqlinyeccion(trim($_POST['idcliente']), "int");
    $idsucursal_clie = antisqlinyeccion(trim($_POST['idsucursal_clie']), "int");


    if (strlen(trim($_POST['factura'])) < 13 or strlen(trim($_POST['factura'])) > 15) {
        $valido = "N";
        $errores .= " - La factura debe tener al menos 13 digitos.<br />";
    }
    if (intval($_POST['idcliente']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar el cliente.<br />";
    }
    if (intval($_POST['idsucursal_clie']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar la sucursal del cliente.<br />";
    }


    $consulta = "
    select ruc from cliente where idcliente = $idcliente
    ";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc = trim($rscli->fields['ruc']);
    $ruc_array = explode("-", $ruc);
    $ruchacienda = intval(solonumeros($ruc_array[0]));
    $dv = intval($ruc_array[1]);
    $ruc_completo = $ruchacienda.'-'.$dv;





    $suc_enviado = substr(trim($_POST['factura']), 0, 3);
    $pexp_enviado = substr(trim($_POST['factura']), 3, 3);
    $numero_enviado = substr(trim($_POST['factura']), 6, 7);

    if (intval($suc_enviado) != $factura_suc) {
        $valido = "N";
        $errores .= " - La sucursal (".agregacero(antixss($suc_enviado), 3).") no corresponde a la asignada a esta pc (".agregacero($factura_suc, 3).").<br />";
    }

    if (intval($pexp_enviado) != $factura_pexp) {
        $valido = "N";
        $errores .= " - El punto de expedicion (".agregacero(antixss($pexp_enviado), 3).") no corresponde a la asignada a esta pc (".agregacero($factura_pexp, 3).").<br />";
    }

    // verificar si es autoimpresor o preimpreso
    $factura_suc = intval($factura_suc);
    $factura_pexp = intval($factura_pexp);

    // verificar si es autoimpresor o preimpreso
    $timbradodatos_prev = timbrado_tanda($factura_suc, $factura_pexp, $idempresa);
    $tipoimpreso = trim($timbradodatos_prev['tipoimpreso']);
    if ($tipoimpreso == 'AUT') {
        $proxfactura = prox_factura_auto($suc_enviado, $pexp_enviado, $idempresa);
        $numero_enviado = $proxfactura;
    }




    $factura = antisqlinyeccion(trim(agregacero($factura_suc, 3).agregacero($factura_pexp, 3).agregacero($numero_enviado, 7)), 'text');

    $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa, $proxfactura);
    $fac_nro = $proxfactura;
    $fac_suc = $factura_suc;
    $fac_pexp = $factura_pexp;



    $idtandatimbrado = $timbradodatos['idtanda'];
    $timbrado = solonumeros($timbradodatos['timbrado']);
    $valido_hasta = $timbradodatos['valido_hasta'];
    $valido_desde = $timbradodatos['valido_desde'];
    $inicio_timbrado = $timbradodatos['inicio'];
    $fin_timbrado = $timbradodatos['fin'];
    $tipoimpreso = trim($timbradodatos['tipoimpreso']);
    if (intval($idtandatimbrado) == 0) {
        $valido = 'N';
        $errores .= '- No existe tanda de timbrado para este punto de expedicion.'.$saltolinea;
    }

    // si  es autoimpresor
    //if($tipoimpreso == 'AUT'){
    // busca si ya existe otra factura duplicada
    $consulta = "
        select idventa, factura, fecha 
        from ventas
         where 
         factura = $factura 
         and estado <> 6 
         and idventa <> $idventa
         and timbrado = $timbrado 
         limit 1;
        ";
    $rsfacex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $fechafacex = date("d/m/Y", strtotime($rsfacex->fields['fecha']));
    if ($rsfacex->fields['idventa'] > 0) {
        $valido = 'N';
        $errores .= '- Ya existe otra factura con la misma numeracion: '.$factura.', registrada en fecha: '.$fechafacex.' '.$saltolinea;
    } // if($rsfacex->fields['idventa'] > 0){

    //} // if($tipoimpreso == 'AUT'){

    $consulta = "
    SELECT idcliente , idsucursal_clie
    FROM sucursal_cliente
    where 
    idcliente = $idcliente
    and idsucursal_clie = $idsucursal_clie
    ";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idsucursal_clie']) == 0) {
        $valido = 'N';
        $errores .= '- La sucursal del cliente no corresponde al cliente seleccionado.'.$saltolinea;
    }

    if ($valido == 'S') {


        $consulta = "
        update ventas
        set 
        factura = $factura,
        idcliente = $idcliente,
        ruchacienda = $ruchacienda,
        dv = $dv,
        ruc = (select ruc from cliente where cliente.idcliente=ventas.idcliente),
        razon_social = (select razon_social from cliente where cliente.idcliente=ventas.idcliente),
        factura_puntoexpedicion = $factura_pexp,
        factura_sucursal = $factura_suc,
        timbrado = $timbrado,
        idtandatimbrado = $idtandatimbrado,
        idsucursal_clie = $idsucursal_clie
        where
        idventa = $idventa
        and idempresa = $idempresa
        and idcaja = $idcaja    
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($tipo_venta == 2) {
            $consulta = "
            update cuentas_clientes
            set 
            idcliente = $idcliente,
            idsucursal_clie = $idsucursal_clie
            where
            idventa = $idventa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        if ($tipoimpreso == 'AUT') {
            $consulta = "
            update lastcomprobantes
            set 
            numfac = $proxfactura,
            factura = $factura
            where
            idempresa = $idempresa
            and idsuc=$factura_suc
            and pe=$factura_pexp
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        header("location: gest_impresiones.php");
        exit;

    }

}


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
<script>
function agrega_cliente(){
        var direccionurl='cliente_agrega_asig.php';
        var parametros = {
              "new" : 'S',
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
                    $("#idpedido").html(idpedido);
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
        $("#apellidos").val("");
        $("#nombreclie_box").hide();
        $("#apellidos_box").hide();
        $("#rz1_box").show();
        $("#cedula_box").hide();
    }
    
}
function busca_cliente(){
        var direccionurl='clientesexistentes_fac.php';
        var parametros = {
              "id" : 0
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $("#modal_titulo").html('Busqueda de Clientes');
                    $("#modal_cuerpo").html("Cargando...");
                    $('#modal_ventana').modal('show');
                },
                success:  function (response) {
                        $("#modal_titulo").html('Busqueda de Clientes');
                        $("#modal_cuerpo").html(response);
                        $("#blci").focus();

                }
        });    
}
function selecciona_cliente(valor){
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        mostrar_cliente(valor);
        $("#idcliente").val(idcliente);
        $("#idsucursal_clie").val(idsucursal_clie);
        $('#modal_ventana').modal('hide');
    }else{
        alert(valor);    
    }
    
}
function mostrar_cliente(valor){
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
    }else{
        alert(valor);    
    }
    
        var parametros = {
                "id"   : idcliente,
                "idsucursal_clie"   : idsucursal_clie
                
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_datos.php',
                type:  'post',
                beforeSend: function () {
                     //$("#adicio").html('Cargando datos del cliente...');  
                },
                success:  function (response) {
                    var datos = response;
                    var dato = datos.split("-/-");
                    var ruc_completo = dato[0];
                    var ruc_array = ruc_completo.split("-");
                    var ruc = ruc_array[0];
                    var ruc_dv = ruc_array[1];
                    var razon_social = dato[1];
                    //cargar de nuevo el pop4
                    //alert(response);
                    
                    //$("#razon_social_box").html(razon_social);
                    $("#razon_social").val(razon_social);
                    
        
                }
        });
        
}
function filtrar_rz(){
        var buscar=$("#blci").val();
        var parametros = {
                "bus_rz" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
                      $("#blci2").val('');
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
}
function filtrar_ruc(){ 
        var buscar=$("#blci2").val();
        var parametros = {
                "bus_ruc" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
                      $("#blci").val('');
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
}
function filtrar_doc(){ 
        var buscar=$("#blci3").val();
        var parametros = {
                "bus_doc" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
                      $("#blci").val('');
                      $("#blci2").val('');
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
}
function carga_ruc_h(idpedido){
    var vruc = $("#ruccliente").val();
    var txtbusca="Buscando...";
    var tipocobro=$("#mediopagooc").val();
    if(txtbusca != vruc){
    var parametros = {
            "ruc" : vruc
    };
    $.ajax({
            data:  parametros,
            url:   'cliente_add.php',
            type:  'post',
            beforeSend: function () {
                $("#ruccliente").val('Buscando...');
            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    //alert(obj.error);
                    if(obj.error == ''){
                        var new_ruc = obj.ruc;
                        var new_rz = obj.razon_social;
                        var new_nom = obj.nombre_ruc;
                        var new_ape = obj.apellido_ruc;
                        var idcli = obj.idcliente;
                        $("#ruccliente").val(new_ruc);
                        $("#nombreclie").val(new_nom);
                        $("#apellidos").val(new_ape);
                        $("#rz1").val(new_rz);
                        if(parseInt(idcli)>0){
                            //nclie(tipocobro,idpedido);
                            var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
                            selecciona_cliente(obj_json,tipocobro,idpedido);
                        }
                    }else{
                        $("#ruccliente").val(vruc);
                        $("#nombreclie").val('');
                        $("#apellidos").val('');
                    }
                }else{
    
                    alert(response);
            
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if(jqXHR.status == 404){
                    alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
                }else if(jqXHR.status == 0){
                    alert('Se ha rechazado la conexiÃ³n.');
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
}
function nclie(tipocobro,idpedido){
    var p=0;

    if($('#r1').is(':checked')) { p=1; }
    if($('#r2').is(':checked')) { p=2; }
    
    //alert(tipocobro+'-'+idpedido);
    var errores='';
    var nombres=document.getElementById('nombreclie').value;
    var razg="";
    razg=$("#rz1").val();
    var apellidos=document.getElementById('apellidos').value;
    var docu=$("#cedula").val();
    var ruc=document.getElementById('ruccliente').value;
    var direclie=document.getElementById('direccioncliente').value;
    var telfo=document.getElementById('telefonoclie').value;
    var ruc_especial = $("#ruc_especial").val();
    if (p==1){
        if (nombres==''){
            errores=errores+'Debe indicar nombres del cliente. \n';
        }
        if (apellidos==''){
            errores=errores+'Debe indicar apellidos del cliente. \n';
        }
    }
    if (p==2){
        if (razg==''){
            errores=errores+'Debe indicar razon social del cliente juridico. \n';
        }
        
    }
    if (docu==''){
        //errores=errores+'Debe indicar documento del cliente. \n';
    }
    if (ruc==''){
        errores=errores+'Debe indicar documento del cliente o ruc generico. \n';
    }
    if (errores==''){
         var html_old = $("#agrega_clie").html();
        //alert(html_old);
         var parametros = {
                    "n"     : 1,
                    "nom"   : nombres,
                    "ape"   : apellidos,
                    "rz1"    :  razg,
                    "dc"    : docu,
                    "ruc"   : ruc,
                    "dire"  : direclie,
                    "telfo" : telfo,
                     "tipocobro" : tipocobro,
                    "idpedido" : idpedido,
                    "tc"    : p,
                    "ruc_especial" : ruc_especial
            };
           $.ajax({
                    data:  parametros,
                    url:   'cliente_registra.php',
                    type:  'post',
                    beforeSend: function () {
                            $("#agrega_clie").html("<br /><br />Registrando, favor espere...<br /><br />");
                    },
                    success:  function (response) {
                        
                        if(IsJsonString(response)){
                            var obj = jQuery.parseJSON(response);
                            if(obj.valido == 'S'){
                                var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
                                selecciona_cliente(obj_json,tipocobro,idpedido);
                            }else{
                                alertar('ATENCION:',obj.errores,'error','Lo entiendo!');
                                $("#agrega_clie").html(html_old);
                                $("#nombreclie").val(nombres);
                                $("#apellidos").val(apellidos);
                                $("#ruccliente").val(ruc);
                                $("#direccioncliente").val(direclie);
                                $("#telefonoclie").val(telfo);
                                $("#cedula").val(docu);
                                $("#rz1").val(razg);
                                if(p == 1){
                                    $("#r1").prop("checked", true); 
                                    $("#r2").prop("checked", false); 
                                }else{
                                    $("#r1").prop("checked", false); 
                                    $("#r2").prop("checked", true); 
                                }
                            }
                        }else{
                            alert(response);
                            $("#agrega_clie").html(html_old);
                            $("#nombreclie").val(nombres);
                            $("#apellidos").val(apellidos);
                            $("#ruccliente").val(ruc);
                            $("#direccioncliente").val(direclie);
                            $("#telefonoclie").val(telfo);    
                            $("#cedula").val(docu);
                            $("#rz1").val(razg);
                            if(p == 1){
                                $("#r1").prop("checked", true); 
                                $("#r2").prop("checked", false); 
                            }else{
                                $("#r1").prop("checked", false); 
                                $("#r2").prop("checked", true); 
                            }
                        }
                        

                        //$("#agrega_clie").html(response);

                    }
            });
    } else {
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
        
    }
    
}
function alertar(titulo,error,tipo,boton){
    swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
}
function alertar_redir(titulo,error,tipo,boton,redir){
    swal({
      title: titulo,
      text: error,
      type: tipo,
      /*showCancelButton: true,*/
      confirmButtonClass: "btn-danger",
      confirmButtonText: boton,
     /* cancelButtonText: "No, cancel plx!",*/
      closeOnConfirm: false,
     /* closeOnCancel: false*/
    },
    function(isConfirm) {
      if (isConfirm) {
        //swal("Deleted!", "Your imaginary file has been deleted.", "success");
          document.location.href=redir;
      } else {
        //swal("Cancelled", "Your imaginary file is safe :)", "error");
          document.location.href=redir;
      }
    });
    
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
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
                    <h2>Asignar Factura a Venta</h2>
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
<p><a href="gest_impresiones.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />


<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Idventa *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="app" id="app" value="<?php  if (isset($_POST['idventa'])) {
        echo htmlentities($_POST['idventa']);
    } else {
        echo htmlentities($rsvv->fields['idventa']);
    }?>" placeholder="App" class="form-control" required readonly />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Cliente * </label>
    <div class="col-md-9 col-sm-9 col-xs-12 input-group mb-3">
        <input type="text" name="razon_social" id="razon_social" value="<?php echo $rsvv->fields['razon_social']; ?>" placeholder="razon_social" class="form-control". style="width:80%" readonly onMouseUp="busca_cliente();"  />
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onMouseUp="busca_cliente();" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span></button>
            <input type="hidden" name="idcliente" id="idcliente" value="<?php if (isset($_POST['idcliente'])) {
                echo htmlentities($_POST['idcliente']);
            } else {
                echo htmlentities($rsvv->fields['idcliente']);
            } ?>" />
            <input type="hidden" name="idsucursal_clie" id="idsucursal_clie" value="<?php if (isset($_POST['idsucursal_clie'])) {
                echo htmlentities($_POST['idsucursal_clie']);
            } else {
                echo htmlentities($idsucursal_clie);
            } ?>" />
        </div>        
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="factura" id="factura" value="<?php  if (isset($_POST['factura'])) {
        echo htmlentities($_POST['factura']);
    } else {
        echo agregacero($factura_suc, 3).agregacero($factura_pexp, 3).agregacero($maxnfac, 7);
    }?>" placeholder="001001000xxxx" class="form-control" onchange="this.value = get_numbers(this.value)" onkeypress="return validar(event,'numero');" required <?php if ($tipoimpreso == 'AUT') { ?>readonly<?php } ?> />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_impresiones.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

<input name="MM_update" type="hidden" value="form1" />
</form>

<div class="clearfix"></div>
<br /><br /><br /><br />



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
       <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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
  </body>
</html>
