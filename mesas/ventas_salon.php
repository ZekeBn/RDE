 <?php
/*-------------------------------
19/10/2023
Se agrega reversion para lista de precio
20/10/23
Se agregan otros valores para voucher
01/11/2023: Se revisa circuito
--------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../includes/funciones_mesas.php");

// preferencias
$borrar_ped_cod = trim($rsco->fields['borrar_ped_cod']);
$borrar_ped = trim($rsco->fields['borrar_ped']);

require_once("parches.php");


//vamos si hay una reversion de lista de precio
$idlistarev = intval($_GET['revlista']);
$idmesa = intval($_GET['idmesa']);
$idatc = intval($_GET['idatc']);
if ($idlistarev > 0 && $idatc > 0) {

    $update = "
    update tmp_ventares 
    set 
        precio=(select precio from productos_sucursales where idproducto=tmp_ventares.idproducto and idsucursal=$idsucursal),
        subtotal=((select precio from productos_sucursales where idproducto=tmp_ventares.idproducto and idsucursal=$idsucursal)*cantidad)-descuento,
        idlistaprecio=NULL
    where 
    idventatmp in
    (
        select idventatmp 
        from tmp_ventares 
        inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab=tmp_ventares.idtmpventares_cab
        where 
        tmp_ventares_cab.idatc=$idatc 
        and tmp_ventares.borrado='N' 
        and tmp_ventares.finalizado='S'
        and tmp_ventares.idlistaprecio is NOT NULL 
        and idlistaprecio=$idlistarev
    )
    and tmp_ventares.borrado='N' 
    and tmp_ventares.finalizado='S' 
    and tmp_ventares.idlistaprecio=$idlistarev
    and tmp_ventares.idproducto not in (select idprod_serial from productos where idtipoproducto in(3,4))
    ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    //ahora el atc
    $update = "update mesas_atc set idlista_aplicada=NULL where idatc=$idatc";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    /*
    //recargamos la misma mesa
    $idmesarecarga=intval($idmesa);
    $usacod=0;
    if($usar_cod_mozo=='S'){
        $usacod=1;
    }
    $volveramesa='S';
    //echo $update;exit;
    */
    header("location: ventas_salon.php");
    exit;

}


if (isset($_POST['ocliberaatc'])) {

    $valido = 'S';
    $errores = '';


    $liberaratc = intval($_POST['ocliberaatc']);
    //$liberarmesa=intval($_POST['ocliberamesa']);


    // validaciones primarias
    if ($valido == "S") {

        // parametros libera mesa
        $parametros_array['idatc'] = $liberaratc;
        $parametros_array['idsucursal'] = $idsucursal;
        $parametros_array['idusu'] = $idusu;



        // validar libera mesa
        $res = liberar_mesa_valida($parametros_array);
        $valido = $res['valido'];
        $errores .= $res['errores'];



        if ($res["valido"] == "S") {
            $res = liberar_mesa_registra($parametros_array);
            //print_r($res);exit;
            //Por ultimo recargamos
            header("location: ventas_salon.php");
            exit;
        } else {
            $errores = $res["errores"];
            echo $errores;
            exit;
        }
    } else {
        echo $errores;
        exit;
    }





}



$idsalon = intval($_REQUEST['ocsalon']);
//Preferencias de mesas
$buscar = "Select * from mesas_preferencias where idestadopref=1 and idempresa=$idempresa";
$rsprefmesa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$usar_iconos = trim($rsprefmesa->fields['usar_iconos']);
$usar_cod_adm = trim($rsprefmesa->fields['usar_cod_adm']);
$usar_cod_mozo = trim($rsprefmesa->fields['usar_cod_mozo']);
$pin_auto_al_abrir_atc = trim($rsprefmesa->fields['usar_cod_mozo']);
$usa_mesa_smart = trim($rsprefmesa->fields['usa_mesa_smart']);
$escajero = 'N';





//Notas sobre el codigo del mozo
//Primero: Si bien se parametriza que el mozo deba colocar su codigo o no en usar_cod_mozo, hay que ver que si es un mozo fijo o son multiples mozos.

//Segundo: Si el operador actualmente logueado, es luego cajero, esta habilitado para cobrar, x lo cual el codigo no se le debe solicitar para nada.

//Verificamos si el usuario en cuestion tiene permiso de caja
$consulta = "
    SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso,
    (select nombre from sucursales where idsucu=usuarios.sucursal and idempresa=$idempresa) as sucuchar
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa
    and idusu in (select idusu from modulo_usuario where idempresa = $idempresa and estado = 1 and submodulo = 22)
    and idusu=$idusu
    order by usuario asc
    ";

$rsccajacon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rsccajacon->fields['idusu']) > 0) {
    $usar_cod_mozo = 'N';
    $escajero = 'S';
}

if ($escajero == 'S') {
    //Verificamos si la caja esta abierta o cerrada. Si esta cerrada, debemos redireccionar al modulo de apertura de caja correspondiente
    $buscar = "Select * from caja_super where cajero=$idusu and estado_caja=1 order by idcaja desc limit 1";
    $rscontcaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    //echo $buscar;exit;
    if ($rscontcaja->fields['idcaja'] == 0) {
        //Se debe obligar a la apertura de una caja
        header("location: ../gest_administrar_caja.php");
        //exit;
    }
}

//TERCERO: Si el operador actual es CAJERO, no importa quien cargo el pedido, debe poder ver la cuenta, agregar y hacer cualquier cosa que desee, especialmente cobrar





if ($idsalon > 0) {
    //Para usar con las mesas
    $addsalonmesas = " and mesas.idsalon=$idsalon ";
}




//Agregar a consumo de mesa los valores

if (isset($_POST['mesaoc']) && intval($_POST['mesaoc']) > 0) {

    $valido = "S";
    $errores = "";

    // recibe parametros
    $idmozo = intval($_POST['ocmozopedido']);
    $cantidad_adultos = intval($_POST['adultos']);
    $cantidad_ninhos = intval($_POST['ninhos']);
    $cantidad_nopagan = intval($_POST['bebes']);
    $idmesa = intval($_POST['mesaoc']);


    $buscar = "Select numero_mesa from mesas where idmesa=$idmesa";
    $rsnmesa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $numeromesa = intval($rsnmesa->fields['numero_mesa']);

    //Id mesa: usamos para buscar si ya existe un atc o no.
    $buscar = "Select idatc from mesas_atc where idmesa=$idmesa and estado=1 order by registrado_el desc limit 1";
    $rsidatc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idatc = intval($rsidatc->fields['idatc']);
    if ($idatc == 0) {

        // datos para abrir atc
        $parametros_array = [
            'idmesa' => $idmesa,
            'idusu' => $idusu,
            'cantidad_adultos' => $cantidad_adultos,
            'cantidad_ninhos' => $cantidad_ninhos,
            'cantidad_nopagan' => $cantidad_nopagan,
            'idmozo' => $idmozo,
        ];

        // abrir atc si no existe
        $res = abrir_mesa($parametros_array);
        $idatc = $res['idatc'];
        if ($res['valido'] != 'S') {
            $valido = "N";
            $errores .= nl2br($res['errores']);
        }

        // inserta otro mozo mas para el mismo atc
    } else {
        // solo si envio idmozo
        if ($idmozo > 0) {
            $insertar = "Insert into mesas_atc_mozos (idmozo,idatc,registrado_el,registrado_por,abre_mesa) values ($idmozo,$idatc,'$ahora',$idusu,'N')";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }


    }

    $consulta = "
    select ruc, razon_social from cliente where borrable = 'N' and estado <> 6 order by idcliente asc limit 1
    ";
    $rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social = $rscligen->fields['razon_social'];
    $ruc = $rscligen->fields['ruc'];
    //$razon_social=trim(strtoupper("CONSUMIDOR FINAL"));
    //$ruc=trim(strtoupper("44444401-7"));
    $chapa = "";
    $observacion = trim($_POST['observacion']);
    $monto = $montototal;
    $mesa = intval($_POST['mesaoc']);
    $idmesa = $mesa;
    $canal = 4;


    // conversiones
    $finalizado = "S";
    $delivery = "N";



    $costodelivery = 0;
    // busca costo delivery si corresponde



    $chapa = "";


    //Totales
    $buscar = "
    Select sum(subtotal) as total 
    from  tmp_ventares 
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idsucursal = $idsucursal
    and tmp_ventares.idmesa = $idmesa
    ";
    $rstot = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $monto = floatval($rstot->fields['total']);

    // limpia variables para insertar
    $razon_social = antisqlinyeccion($razon_social, "text");
    $ruc = antisqlinyeccion($ruc, "text");
    $chapa = antisqlinyeccion($chapa, "text");
    $observacion = antisqlinyeccion($observacion, "text");

    $fechahora = date("Y-m-d H:i:s");
    $fechahora = antisqlinyeccion($fechahora, "text");
    $telefono = 0;
    $zona = 0;
    $direccion = "";
    $llevapos = "N";
    $cambio = 0;
    $observacion_delivery = "";
    $delivery_costo = 0;

    // si todo es valido
    if ($valido == 'S') {

        //Crear cabecera de tmpventarescab
        $consulta = "
        INSERT INTO tmp_ventares_cab
        (razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,
        telefono,delivery_zona,direccion,llevapos,cambio,observacion_delivery,delivery_costo,idatc,idmozo,idterminal) 
        VALUES 
        ($razon_social, $ruc, $chapa, $observacion, $monto, $idusu, $fechahora, $idsucursal, $idempresa,$canal,'$delivery',$mesa,
        $telefono,$zona,NULL,'$llevapos',$cambio,NULL,$delivery_costo,$idatc,$idmozo,$idterminal_usu)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // buscar ultimo id insertado
        $consulta = "
        select idtmpventares_cab
        from tmp_ventares_cab
        where
        idusu = $idusu
        and idmesa=$idmesa
        and idsucursal = $idsucursal
        order by idtmpventares_cab desc
        limit 1
        ";
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtmpventares_cab = $rscab->fields['idtmpventares_cab'];


        // marcar como finalizado el cuerpo y colocar el id de cabecera generado
        $consulta = "
        update tmp_ventares
        set 
        idtmpventares_cab = $idtmpventares_cab
        where
        registrado = 'N'
        and tmp_ventares.usuario = $idusu
        and tmp_ventares.borrado = 'N'
        and tmp_ventares.finalizado = 'N'
        and tmp_ventares.idsucursal = $idsucursal
        and tmp_ventares.idmesa = $idmesa
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //Por ultimo
        if ($idmozo > 0) {
            //Cambiamos el usuario al mozo que tomo el pedido, para que se permita ver quien fue el que levanto el pedido
            $update = "
            update tmp_ventares 
            set usuario=$idmozo 
            where 
            idtmpventares_cab = $idtmpventares_cab 
            ";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            $consulta = "
            update tmp_ventares_cab
            set 
            idusu=$idmozo
            where
            idtmpventares_cab = $idtmpventares_cab
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }

        $buscar = "Select * from mesas_preferencias where idempresa=$idempresa limit 1";
        $rsmespref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $consulta = "select imprime_directo_suc from sucursales  where idsucu = $idsucursal ";
        $rssucimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $imprime_directo = trim($rssucimp->fields['imprime_directo_suc']);
        if ($imprime_directo == 'S') {
            $impreso_coc = "'S'";
        } else {
            $impreso_coc = "'N'";
        }

        $consulta = "
        update tmp_ventares
        set 
        finalizado='$finalizado',
        impreso_coc = $impreso_coc
        where
        idtmpventares_cab=$idtmpventares_cab
        and borrado = 'N'
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        /* para corregir
        select * from tmp_ventares where finalizado = 'N' and borrado = 'N' and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where finalizado = 'S');
        update  tmp_ventares set finalizado = 'S' where finalizado = 'N' and borrado = 'N' and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where finalizado = 'S');
        */

        // marcar como finalizado en cabecera
        $consulta = "
        update tmp_ventares_cab
        set 
        finalizado = '$finalizado'
        where
        idtmpventares_cab = $idtmpventares_cab 
        and idmesa=$idmesa
        and idsucursal = $idsucursal
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo 'pedidotomado';exit;



        if ($imprime_directo == 'S') {
            header("location: imprime_mesa.php?id=".$idtmpventares_cab);
            exit;
        }


        $volveramesa = trim($rsmespref->fields['regresa_mesa_final']);
        if ($volveramesa == 'S') {
            $idmesarecarga = intval($idmesa);
            $usacod = 0;
            if ($usar_cod_mozo == 'S') {
                $usacod = 1;
            }


        }
    } else {
        echo $errores;
        exit;
    }
}




?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
      
     <style>
      .modal-dialog {
      margin-top: 0;
      margin-bottom: 0;
      height: 100vh;
      display: -webkit-box;
      display: -webkit-flex;
      display: -ms-flexbox;
      display: flex;
      -webkit-box-orient: vertical;
      -webkit-box-direction: normal;
      -webkit-flex-direction: column;
          -ms-flex-direction: column;
              flex-direction: column;
      -webkit-box-pack: center;
      -webkit-justify-content: center;
          -ms-flex-pack: center;
              justify-content: center;
         }

    .modal.fade .modal-dialog {
      -webkit-transform: translate(0, -100%);
              transform: translate(0, -100%);
    }
    .modal.in .modal-dialog {
      -webkit-transform: translate(0, 0);
              transform: translate(0, 0);
    }
    .contador{
        width:20px;
        height:20px;    
        position:absolute;
        font-size:16px;
        font-weight:bold;
        border:1px solid #000000;
        background-color:#CCC;
        text-align:center;
        margin:0px;
        float:left;
    
    }
}
.color-blanco {
  color: #fff; /* Color blanco */
}
.color-negro {
  color: #000; /* Color negro */
}
.cuadritomesanum{
    width:100px;
    height:100px;
    color:#FFF; 
    font-size:1.8em;
    font-weight:bold;
    margin-left:5px;
    float:left;
    margin-left:1px; 
    margin-top:2px;
    text-align:center;
}
</style>
<script>
function listadeprecios(idmesa,idatc){
    $("#modal_titulo").html("Lista de precios");
    var urlbusca='lista_precios.php';
     var parametros = {
            "idmesa"        : idmesa,
            "idatc"         : idatc
    };
    $.ajax({
        data:  parametros,
        url:   urlbusca,
        cache: false,
        type:  'post',
        beforeSend: function () {
            $("#modal_cuerpo").html('');
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);    
            $("#modpop").modal("show");
        }
    });
    
}
function aplicar_lista(idlistaprecio,idmesa,idatc){
    
    
    var urlbusca='mesas_aplica_lista.php';
    var parametros = {
            "idlistaprecio"     : idlistaprecio,
            "idmesa"             : idmesa,
            "idatc"              : idatc
    };
    $.ajax({
            data:  parametros,
            url:   urlbusca,
            cache: false,
            type:  'post',
            beforeSend: function () {
                $("#aplicalista").html("Aplicando lista...aguarde");
            },
            success:  function (response) {
                $("#aplicalista").html(response);
                //alert(response);
                if(response == 'OK'){
                    $("#msurge").html("Atencion: Lista de precio aplicada!");
                    $("#mensaje_urgente").show();
                    actualiza_lista_carrito(idmesa);
                    $('#modpop').modal('hide')  ;
                }else{
                    //$("#modpop").html(response);
                    
                }
            }
    });
    

}
function revertir_lista(idlistarev,idmesa,idatc){
    
    window.open("ventas_salon.php?revlista="+idlistarev+"&idatc="+idatc+"idmesa="+idmesa, "_self"); 
    
    
}
function aplicardescu(idmesa,idatc,obliga_pin){
        var errores='';
        $("#titulocuerpo").html("Aplicando descuento");
        $("#cuerpopop").html("");
        var cod_autoriza = $("#cod_autoriza").val();
        if (obliga_pin=='S' && cod_autoriza==''){
            errores=errores+"Debe indicar el codigo de acciones para cargar una cortesia. <br />";
        }
        if(errores==''){
            var parametros = {

                    "idatc" : idatc,
                    "idmesa" : idmesa,
                    "codigo_autorizacion"    : cod_autoriza

            };
            $.ajax({
                    data:  parametros,
                    url:   'mini_aplicadesc.php',
                    cache: false,
                    type:  'post',
                    beforeSend: function () {

                    },
                    success:  function (response) {
                        $("#cuerpopop").html(response);

                    }
            });
            $("#popupab").modal("show");
        } else {
            alert(errores);
        }
}
function registra_descuento_atc(idmesa,idatc,obliga_pin){
    $("#titulocuerpo").html("Aplicando descuento");
    //$("#cuerpopop").html("");
    var descuento_porc = $("#descuento_porcn").val();
    //alert(descuento_porc);
    var errores='';
    var cod_autoriza = $("#cod_autoriza").val();
    if (obliga_pin=='S' && cod_autoriza==''){
        errores=errores+"Debe indicar el codigo de acciones para aplicar descuento. <br />";
    }
    if(errores==''){
        var parametros = {

                "idatc" : idatc,
                "idmesa" : idmesa,
                "descuento_porc" : descuento_porc,
                "cod_autorizacion" : cod_autoriza,
                "MM_update" : 'form1'

        };
        $.ajax({
                data:  parametros,
                url:   'mini_aplicadesc.php',
                cache: false,
                type:  'post',
                beforeSend: function () {
                    $("#cuerpopop").html("Cargando...");
                },
                success:  function (response) {
                    //if(response=='OK'){
                        $("#cuerpopop").html(response);
                        actualiza_lista_carrito(idmesa);
                        //$("#popupab").modal("hide");
                    
                }
        });
    } else {
        alert(errores);
        
    }
}
function desagrupar(idmesa){
    var direccionurl='desagrupar.php';    
    var parametros = {
      "idmesa"        : idmesa
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_titulo").html('Liberar mesa Agrupada');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#modpop').modal('show');
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
function desagrupar_confirma(idmesa){
    var direccionurl='desagrupar.php';    
    var parametros = {
      "idmesa"        : idmesa,
      "conf" : 'S'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_titulo").html('Liberar mesa Agrupada');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            //$('#modpop').modal('show');
            if(response == 'OK'){
                document.location.href='ventas_salon.php';
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
</script>
  </head>
<!-- CUERPO / BODY -->
  <body class="nav-md">
        <div class="container body">
            <div class="main_container">
                <!-- top navigation -->
                  <?php //require_once("includes/menu_top_gen.php");?>
                <!-- /top navigation -->
                <!-- page content -->
                <div class="right_col" role="main">
                    <div class="">
                        
                            <div class="clearfix"></div>
                              <!-- SECCION -->
                                   <div class="row">
                                      <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="x_panel">
                                              <div class="x_title">
                                                   <?php
                                                        // crea imagen
                                                        $img = "../gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "../gfx/empresas/emp_0.png";
}
?>
                                                 <?php require_once("cabecera_ppal.php"); ?>
                                          
                                                  
                                                  
                                                  
                                                 <form id="formu01" action="ventas_salon.php" method="post">
                                                         <input type="hidden" name="ocsalon" id="ocsalon" value="0" />
                                                      <input type="hidden" name="occantidad" id="occantidad" value="0" />
                                                      <input type="hidden" name="ocserial" id="ocserial" value="0" />
                                                  </form>                                              
                                                  
                                             
                                                
                                              </div>
                                                <div class="x_content">
                                                          
                                                      <?php if (trim($errores) != "") { ?>
                                                        <div class="alert alert-danger alert-dismissible fade in" role="alert"><strong>Errores:</strong><br /><?php echo $errores; ?></div>
                                                  
                                                          <?php } ?>
                                                          
<?php if (date('d/m') == '25/08') { ?>
<strong>¡¡¡ FELIZ DIA DEL IDIOMA GUARANI  !!!</strong>  <a href="../25_agosto.php" target="_blank" class="btn btn-sm btn-default" title="Dia del Idioma Guarani" data-toggle="tooltip" data-placement="right"  data-original-title="Dia del Idioma Guarani"><span class="fa fa-search"></span></a>
<?php } ?>
                                                  </div>
                                            </div>
                                          </div>
                                    </div>
                                    <!-- ROW--->
                                       <div class="clearfix"></div>
                                      <!-- Mesas del Sistema -->
                                       <div class="row" id="mesascomponen" >
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <div class="x_panel">
                                                  <div class="x_title">
                                                    <h2><i class="fa fa-arrow-circle-o-right"></i> Mesas disponibles<small id="minitextomesas"></small></h2>
                                                    <div align="center">
                                                      
                                                    </div>  
                                             
                                                    <div class="clearfix"></div>
                                                  </div>
                                                  <div class="x_content" id="minimesas">
<?php require_once("mini_mesas.php")?>       
                                                   </div>
                                                 
                                            </div>
                                        </div>
                                        

                                   </div>
                                     <div class="clearfix"></div>
                                      <!-- acciones de mesa-->
                                       <div class="row" id="accionesmesa" style="display: none">
                                        <div class="col-md-12 col-sm-12 col-xs-12" >
                                            <div class="x_panel">
                                                
                                                 <!----------------TITULO-------->
                                                    <input type="hidden" name="idmozosele" id="idmozosele" value="<?php echo $idmozo; ?>" />
                                                  <div class="x_content" id="minimesasacciones">
                                                        
                                                          <?php require_once("mini_accionesmesas.php")?>
                                                     
                                                  </div>
                                            </div>
                                        </div>
                                        

                                   </div>
            
                          </div>
                  </div>
                <!-- The Modal -->
          <div class="modal fade" id="popupab"  role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <input type="hidden" name="ocidmesa" id="ocidmesa" value="<?php if ($volveramesa == '$') {
                        echo $idmesarecarga;
                    } ?> "/>
                    <div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorescod" style="display: none">
                        <strong>Errores:</strong><br /><span id="errorescodcuerpo"></span>
                        </div>
                  <h4 class="modal-title" id="titulocuerpo"><span id="nprodadm"></span></h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body" id="cuerpopop">
                  
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
            
                    <span  id="controlcito" style="display: none"></span>
                </div>

              </div>
            </div>
          </div>
        </div><!-- /main container -->  
            
           <!------------------------------     modal para cierre ---------------------------->
            
            <!-- The Modal -->
          <div class="modal fade" id="modpop"  role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <input type="hidden" name="ocidmesa" id="ocidmesa" value=""/>
                    <div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorescod" style="display: none">
                        <strong>Errores:</strong><br /><span id="errorescodcuerpo"></span>
                        </div>
                    <span  id="modal_titulo" style="font-weight:bold;"></span>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body" id="modal_cuerpo" style="height: 500px; overflow-y: scroll">
                  
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                
                    <span  id="controlcito" style="display: none"></span>
                </div>

              </div>
            </div>
          </div>
        </div><!-- /main container --> 
            
            
            
            
            
      </div> <!-- /body container -->  
       
        <!-- Impresiones y reimpresiones -->     
        <div  id="reimprimebox"></div>
              
       
        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
    <?php require_once("includes/footer_gen.php"); ?>
<script src="../js/shortcut.js"></script>
<script src="../js/ventas_mesas_nueva.js?nc=20240529153900<?php //echo date("Ymdhis");?>"></script>

<script>
function asignar_mozo_edit(idatc){
    var parametros = {
      "idatc"   : idatc,
      "edit"   : 'S',
    };
    $.ajax({          
        data:  parametros,
        url:   'mozo_asignado.php',
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#mozo_asig").html('Procesando...');
        },
        success:  function (response) {
            $("#mozo_asig").html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });    
}
function asignar_mozo(idatc){
    var idmozo_asignado = $("#idmozo_asignado").val();
    var parametros = {
      "idatc"   : idatc,
      "idmozo"   : idmozo_asignado,
      "asignar_mozo"   : 'S',
    };
    $.ajax({          
        data:  parametros,
        url:   'mozo_asignado.php',
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#mozo_asig").html('Procesando...');
        },
        success:  function (response) {
            $("#mozo_asig").html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });    
}
function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
    // error
    if(tipo == 'error'){
        if(jqXHR.status == 404){
            alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
        }else if(jqXHR.status == 0){
            alert('Se ha rechazado la conexión.');
        }else{
            alert(jqXHR.status+' '+errorThrown);
        }
    // fail
    }else{
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
    }
}
function genera_pin_mesa(idatc){
    var parametros = {
      "idatc"   : idatc,
      "gen_pin"   : 'S' 
    };
    $.ajax({          
        data:  parametros,
        url:   'mini_acciones_mesa.php',
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#pin_mesa").val('Procesando...');
            $("#error_box_pin").hide();
            $("#btn_genpin").hide();
        },
        success:  function (response) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    // hacer algo
                    $("#pin_mesa").val(obj.pin_actual);
                    
                }else{
                    alert('Errores: '+obj.errores);    
                    $("#error_box_pin").show();
                    $("#error_box_pin_msg").html(nl2br(obj.errores));
                    $("#pin_mesa").val(obj.pin_actual);
                    $("#btn_genpin").show();
                }
            }else{
                alert(response);
                $("#error_box_pin").show();
                $("#error_box_pin_msg").html(nl2br(obj.errores));
                $("#btn_genpin").show();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
<?php
$consulta = "
select idforma, idformapago_set ,vuelto_propina
from formas_pago 
where 
estado <> 6
";
$rsfpagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rsfpagset->EOF) {
    $idforma = $rsfpagset->fields['idforma'];
    $idformapago_set = $rsfpagset->fields['idformapago_set'];
    $vuelto_propina = trim($rsfpagset->fields['vuelto_propina']);
    $formapag[$idforma] = $idformapago_set;
    $formapagprop[$idforma] = $vuelto_propina;//Para propina automatica
    //$formapag[$idforma] =$vuelto_propina;
    $rsfpagset->MoveNext();
}

$json_fpag = json_encode($formapag, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$json_fpag_prop_auto = json_encode($formapagprop, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
function forma_pago(forma,idmesa){
    
    // funcion agregamedio()
    var json_fpag = '<?php echo $json_fpag; ?>';
    var json_fpag_propauto = '<?php echo $json_fpag_prop_auto; ?>';
    var obj = jQuery.parseJSON(json_fpag);
    var eleme=jQuery.parseJSON(json_fpag_propauto);
    //alert(eleme);
    var forma_str=forma.toString();
    //alert(forma_str);
    var forma_pago_set = obj[forma];
    var propina_automatica = eleme[forma];
    if(propina_automatica=='S'){
        //alert(propina_automatica);
        var monto_saldo=parseFloat($("#ocreal_monto").val());
        var recibido=parseFloat($("#abonar").val());
        if(recibido > monto_saldo){
            var propina=recibido-monto_saldo;
            $("#propinags").val(propina);
        } else {
            $("#propinags").val("");
        }
    } else {
        $("#propinags").val("");
    }
    //alert(obj[forma]);
    // cheque
    if(forma_pago_set == 2){
        $("#iddenominaciontarjeta_box").hide();
        $("#banco_box").show();
        $("#cheque_numero_box").show();
    // tarjeta credito y debito
    }else if(forma_pago_set == 3 || forma_pago_set == 4){
        $("#iddenominaciontarjeta_box").show();
        $("#banco_box").hide();
        $("#cheque_numero_box").hide();
    // anticipo
    }else if(forma_pago_set == 11){
        document.location.href='../anticipos_mesa.php?idmesa='+idmesa;
    }else{
        $("#iddenominaciontarjeta_box").hide();
        $("#banco_box").hide();
        $("#cheque_numero_box").hide();
    }
    
}
function actualiza_mapa_mesas(){
    var mapamesa = $("#mapamesa").val();
    if(mapamesa == 'mapa'){
        //alert('actualiza');
        var html_actu = $("#minimesas").html();
        var parametros = {
          "nc"   : '<?php echo date("YmdHis"); ?>', // no cache
        };
        $.ajax({          
            data:  parametros,
            url:   'mini_mesas.php',
            type:  'post',
            cache: false,
            timeout: 5000,  // I chose 3 secs for kicks: 5000
            crossDomain: true,
            beforeSend: function () {

            },
            success:  function (response) {
                if(response != html_actu){
                    $("#minimesas").html('Actualizando...');
                    $("#minimesas").html(response);
                    titilarElementos();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                //errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
            }
        }).fail( function( jqXHR, textStatus, errorThrown ) {
            //errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        });
    } 
}
function marca_atendido(idpedido){
    var html_ant=$("#idnotifica_"+idpedido).html();
    var parametros = {
      "idpedido"   : idpedido, // no cache
    };
    $.ajax({          
        data:  parametros,
        url:   'marca_atendido.php',
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#idnotifica_"+idpedido).html('Marcando como atendido...<br />');
        },
        success:  function (response) {
            
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    $("#idnotifica_"+idpedido).hide();
                }else{
                    $("#idnotifica_"+idpedido).html(html_ant);
                    $("#idnotifica_"+idpedido).show();
                }
            }else{
                alert(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function titilarElementos() {
    $(".titilar").fadeIn(1000, function() {
        $(this).fadeOut(1000, titilarElementos);
        //$(this).toggleClass("color-blanco color-negro").fadeOut(1000, titilarElementos);
    });
   /* $(".titilar").fadeIn(1000, function() {
        $(this).toggleClass("color-blanco color-negro").fadeOut(1000, function() {
            $(this).toggleClass("color-blanco color-negro").fadeIn(0, titilarElementos);
        });
    });*/
    /*$(".titilar").fadeIn(1000, function() {
        $(this).toggleClass("color-blanco color-negro").fadeOut(1000, function() {
            $(this).fadeIn(0, function() {
                $(this).toggleClass("color-blanco color-negro").fadeOut(1000, titilarElementos);
            });
        });
    });*/
}
function registra_peso(e,idmesa){
    if(e.keyCode == 13){
        var valido='S';
        
        var cantidad = parseFloat($("#menu_kg").val());
        var idprodkg = parseInt($("#idprodkg").val());
        if(parseInt(idprodkg) == 0){
            alert('No se recibio el idproducto');
            valido='N';
        }
        if(cantidad <= 0){
            alert('La cantidad debe ser mayor a cero usted envio: '+cantidad);
            valido='N';
        }
        if(isNaN(cantidad)){
            //alert('La cantidad debe ser un numero usted envio: '+cantidad);
            valido='N';
        }
        if(valido == 'S'){
            //alert(cantidad);
            <!--  <?php //print_r($_REQUEST);?> -->
            agregar_carrito(idprodkg,cantidad,idmesa);
            
            
        }
    }
}
function ver_foto_producto(idproducto){
    $("#modpop").modal('show'); 
    $("#modal_titulo").html('Foto del Producto'); 
    var parametros = {
      "idproducto"   : idproducto, // no cache
    };
    $.ajax({          
        data:  parametros,
        url:   'producto_foto.php',
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function alerta_modal(titulo,mensaje){
    $('#modpop').modal('show');
    $("#modal_titulo").html(titulo);
    $("#modal_cuerpo").html(mensaje);
    //$("#modal_pie").html(html_botones);
    /*
    Otros usos:
    $('#modal_ventana').modal('show'); // abrir
    $('#modal_ventana').modal('hide'); // cerrar
    */
    
}
function info_mesa_bloq(idmesa){
    $("#modpop").modal('show'); 
    $("#modal_titulo").html('Mesa Pendiente de Rendicion'); 
    var parametros = {
      "idmesa"   : idmesa, // no cache
    };
    $.ajax({          
        data:  parametros,
        url:   'mesa_pend_rend.php',
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
</script>
<?php
$idmesaclick = intval($_GET['me']);
if ($idmesaclick > 0) {
    ?>
<script>
var nme=<?php echo $idmesaclick ?>;    
$( document ).ready(function() {
        $("#mesa_n_"+nme).click();
});
</script>
<?php }?>
<script>

$(document).ready(function(){
    setInterval(function(){ mantiene_session(); }, 1200000); // 20min
    
<?php if ($usa_mesa_smart == 'S') { ?>
    setInterval(function(){ actualiza_mapa_mesas(); },5000); // 5000 milisegundos = 5 segundos
<?php } ?>
    
<?php if ($volveramesa == 'S') {

    ?>
$("#ocidmesa").val(<?php echo $idmesarecarga ?>);
setTimeout(function(e){ controlacod(<?php echo $idmesarecarga ?>,<?php echo $usacod ?>,<?php echo $numeromesa ?>); }, 1200);
 
//alert("hacer click");
<?php } ?>

});
</script>


  </body>
</html>
