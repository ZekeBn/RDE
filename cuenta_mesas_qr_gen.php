 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");


require_once("includes/funciones_mesas.php");


$consulta = "
select idbancardqrpref, usuario, codigo_comercio, clave 
from bancard_qr_preferencias 
where 
usuario is not null 
and clave is not null 
and codigo_comercio is not null
and trim(usuario) <> ''
and trim(clave) <> '' 
and trim(codigo_comercio) <> ''
";
$rsprefqr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsprefqr->fields['idbancardqrpref']) == 0) {
    echo "No se cargaron las credenciales de Bancard, cargar en gestion > formas de pago > Bancard QR Pantalla.";
    exit;
}
//exit;

$idqrtmpmonto = intval($_GET['id']);
if ($idqrtmpmonto == 0) {
    header("location: cuenta_mesas.php");
    exit;
}

$consulta = "
select * from qr_tmp_monto where idqrtmpmonto = $idqrtmpmonto and estado = 1
";
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idatc = intval($rscab->fields['idatc']);
$monto_abonar = $rscab->fields['monto_abonar'];
$monto_propina = $rscab->fields['monto_propina'];
$total_abonar = $rscab->fields['total_abonar'];
if ($idatc == 0) {
    header("location: cuenta_mesas.php");
    exit;
}

$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
where 
mesas.estadoex = 1 
and mesas_atc.idatc = $idatc
and mesas_atc.estado = 1
order by mesas.numero_mesa asc, salon.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rs->fields['idmesa']);
$idatc = intval($rs->fields['idatc']);
if ($idmesa == 0) {
    header("location: cuenta_mesas.php");
    exit;
}

$numero_mesa = $rs->fields['numero_mesa'];
$salon = $rs->fields['salon'];
$sucursal = $rs->fields['sucursal'];
$idatc = $rs->fields['idatc'];

// saldo de la mesa
$parametros_array_saldo['idatc'] = $idatc;
$saldo_mesa_res = saldo_mesa($parametros_array_saldo);
$saldo_mesa = floatval($saldo_mesa_res['saldo_mesa']);
$pagos_mesa = floatval($saldo_mesa_res['pagos_mesa']);
$monto_mesa = floatval($saldo_mesa_res['monto_mesa']);

//echo $pagos_mesa;exit;



$tiene_qr_hook_alias = "N";

$idusu_pedido = $idusu;
if ($idusu_pedido == 0) {
    echo "No se asigno ningun usuario del sistema para pedidos por QR.";
    exit;
}


require_once("includes/funciones_bancard_qr.php");

$segundos_inactividad = '300'; // 300 = 5 minutos recomendado por bancard





$consulta = "
select * 
from pos_tmp
where
idatc = $idatc
and idqrtmpmonto = $idqrtmpmonto
order by idpostmp desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpostmp = intval($rs->fields['idpostmp']);
$hook_alias_fijo = trim($rs->fields['hook_alias_fijo']);
$json_bancard_fijo = trim($rs->fields['json_bancard_fijo']);
//echo $json_bancard_fijo;exit;
// sino existe inserta
if ($idpostmp == 0) {
    $consulta = "
    INSERT INTO `pos_tmp`
    (`datos_json`, `estado`, `idsucursal`, `idventa`, idatc, idqrtmpmonto, `tipo`, `registrado_por`, `registrado_el`) 
    VALUES 
    (NULL,1,$idsucursal,NULL,$idatc,$idqrtmpmonto,'QRB',$idusu_pedido,'$ahora')
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

// busca si existe pero esta vez valida que no se haya usado anteriormente
$consulta = "
select * 
from pos_tmp
where
idatc = $idatc
and idqrtmpmonto = $idqrtmpmonto
and estado = 1
and idventa is null
order by idpostmp desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpostmp = intval($rs->fields['idpostmp']);



if ($idpostmp == 0) {
    echo "No se recibio el registro o el registro ya fue procesado.";
    exit;
}
$datos_post = json_decode($rs->fields['datos_json'], true);

$datos_get = $_GET;


//print_r($datos_post['monto_recibido']);exit;

// si no existe consulta en bancard
if ($hook_alias_fijo == '') {

    $consulta = "
    select codigo_sucursal_bancard from sucursales where idsucu = $idsucursal
    ";
    $rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $codigo_sucursal_bancard = $rssuc->fields['codigo_sucursal_bancard'];

    $idpedido = $idpostmp;
    $subtotal = floatval($total_abonar);
    $parametros_array = [
        'monto' => $subtotal,
        'descripcion' => 'COMPRA EN COMERCIO',
        'idpostmp' => $idpostmp,
        'codigo_sucursal_bancard' => $codigo_sucursal_bancard,
    ];
    $res = generar_qr_pago($parametros_array);
    //print_r($res);
    // si ya existe trae de la bd
} else {
    $res = $json_bancard_fijo;
}

$respuesta_bruta_bancard = $res;
$respuesta = json_decode($res, true);
$status = $respuesta['status'];
/*
        "amount": 500000,
        "hook_alias": "SXKBT74691",
        "description": "Coca Cola 1lt.",
        "url": "https://desa.infonet.com.py:8035/s4/public/selling_qr_images/SXKBT74691_1697829696.png",
        "created_at": "20/10/2023 16:21:36",
        "qr_data": "00020101021202035775204444453036005408500000.05802PY5918BELLINI PASTAS-SOL6008Asuncion62320510SXKBT746910814Coca Cola 1lt.630477CF"
*/
$qr_img = $respuesta['qr_express']['url'];
$qr_contenido = $respuesta['qr_express']['qr_data'];
$qr_hook_alias = $respuesta['qr_express']['hook_alias'];
$qr_description = $respuesta['qr_express']['description'];
$qr_created_at = $respuesta['qr_express']['created_at'];
$qr_amount = $respuesta['qr_express']['amount'];
$clientes_soportados = $respuesta['supported_clients'];

// para evitar el null
if (is_null($qr_hook_alias)) {
    $qr_hook_alias = '';
}

if (trim($qr_hook_alias) == '') {
    $error_bancard = $respuesta_bruta_bancard;
    $tiene_qr_hook_alias = "N";
} else {
    $error_bancard = ""; // ALTER TABLE `pos_tmp` ADD `json_bancard_fijo` TEXT NULL AFTER `hook_alias_fijo`
    $tiene_qr_hook_alias = "S";

    $respuesta_bruta_bancard_sql = antisqlinyeccion($respuesta_bruta_bancard, "textbox");
    $qr_hook_alias_sql = antisqlinyeccion($qr_hook_alias, "textbox");
    $consulta = "
    update  pos_tmp
    set
    json_bancard_fijo = $respuesta_bruta_bancard_sql,
    hook_alias_fijo = $qr_hook_alias_sql
    where
    estado = 1
    and idventa is null
    and idpostmp = $idpostmp
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}




//$pagos_mesa=13000;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php
    $title_personaliza = "Pagar Mesa";
require_once("includes/head_gen.php"); ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
var myTimer;
function inicio_comprueba(){
    myTimer = setInterval(function(){ comprueba_pago(); }, 3000); // 1200000=20min   300000=5min  3000=3seg 1000=1seg
    //comprueba_pago(); // se llama dentro de la funcion
}
function comprueba_pago(){
    var direccionurl='cuenta_mesas_qr_pos_comprueba_pago.php';    
    var parametros = {
      'MM_insert' : 'form1',
      'hook_alias' : '<?php echo $qr_hook_alias ?>',
      'idpostmp' : '<?php echo $idpostmp ?>',
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#pos_msg").html('Verificando si ingreso el pago...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#pos_msg").html('Comprobando si ingreso el pago...'+response);
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.encontrado == 'S'){
                    if(obj.pago_valido == 'S'){
                        clearTimeout(myTimer);
                        var idconfirmapago = obj.idconfirmapago;
                        registrar_pedido(idconfirmapago);
                    }else{
                        clearTimeout(myTimer);
                        //alert('Errores: '+obj.mensaje);    
                        $("#pos_msg").html(nl2br(obj.mensaje));
                        //$("#error_box").show();
                        $("#comprueba_pago_box").hide();
                        // mostrar boton generar otro QR
                        var btn_regenera  = '<a href="javascript:regenera_qr();" class="btn btn-sm btn-default" style="background-color:white;"><span class="fa fa-refresh"></span> Generar un nuevo QR</a>';
                        $("#qr_img").html('[QR FINALIZADO]<br /><br />'+btn_regenera);
                    // con ese boton hacer un redir a pagar_pedido_qr_regenera.php y que haga un update de hook_alias_fijo con el nuevo qr y luego volver a direccionar a esta pagina
                    }
                }else if(obj.encontrado == 'N'){    
                    $("#pos_msg").html('No se encontraron pagos registrados para este pedido.');
                }else{
                    clearTimeout(myTimer);
                    alert('Errores: '+obj.mensaje);    
                    //$("#error_box_msg").html(nl2br(obj.mensaje));
                    //$("#error_box").show();
                    // mostrar boton reintentar y que refresque la pagina
                }
            }else{
                clearTimeout(myTimer);
                alert(response);    
                // mostrar boton reintentar y que refresque la pagina
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function registrar_pedido(idconfirmapago){
    $("#pos_msg").html('Recibiendo respuesta de Bancard, favor aguarde...');
    registrar_pago(idconfirmapago);
}
function registrar_pago(idconfirmapago){
    $("#pos_msg").html('Registrando el Pago...');
    $("#qr_img").html('[QR FINALIZADO]');
    document.location.href='cuenta_mesas_qr.php?qr=ok&idmesa=<?php echo $idmesa; ?>';


}
function regenera_qr(){
    document.location.href='cuenta_mesas_qr_regenera.php?id=<?php echo $idqrtmpmonto; ?>';
}
function cancelar_qr(){
    // enviar reversa a bancard del hook_alias
    var direccionurl='../reversa_bancard.php';    
    var parametros = {
      'MM_insert' : 'form1',
      'hook_alias' : '<?php echo $qr_hook_alias ?>',
      'idpostmp' : '<?php echo $idpostmp ?>',
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            clearTimeout(myTimer);    
            clearTimeout(myTimer2);        
            $("#pos_msg").html('Reversando el QR...');
            $("#qr_img").html('[QR CANCELADO]');
        },
        success:  function (response, textStatus, xhr) {
            $("#pos_msg").html('Reversando la transaccion del QR...');
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.status == 'success'){
                    $("#pos_msg").html('QR reversado exitosamente...');
                    document.location.href='index.php';
                }else{
                    //alert(response);
                    var msg_res=obj.messages[0].dsc+' | key: '+obj.messages[0].key+' | level: '+obj.messages[0].level;
                    alert('Errores: '+obj.messages[0].dsc);    
                    $("#pos_msg").html('Error: '+msg_res);
                }
            }else{
                alert(response);
                $("#pos_msg").html('Error: '.response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    

}
var myTimer2;
function inicio_conteoprinc(){
    conteo_conteoprinc(<?php echo $segundos_inactividad; ?>);
}
function conteo_conteoprinc(tiempo){
    myTimer2 = setTimeout(function(){
        actualiza_tiempo_conteoprinc(tiempo);
    }, 1000);
}
function actualiza_tiempo_conteoprinc(tiempo){
    tiempo = parseInt(tiempo)-1;
    $('#segundos_conteoprinc').html(tiempo);
    if(tiempo > 0){
        conteo_conteoprinc(tiempo);
    }else{
        document.location.href='index.php';
    }
}
function reset_conteoprinc() {
    clearTimeout(myTimer2); // Limpiar el temporizador
    $('#segundos_conteoprinc').html(<?php echo $segundos_inactividad; ?>);
    inicio_conteoprinc();
}
function iniciar_todo(){
    inicio_comprueba();
    inicio_conteoprinc();
}
</script>
  </head>

  <body class="nav-md" onload="iniciar_todo();">
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
            
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Cobrar Mesa: <?php echo $numero_mesa; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                               
   <p align="center">          
 <strong>Mesa:</strong> <?php echo $numero_mesa; ?> | <strong>Salon:</strong> <?php echo antixss($salon); ?> | <strong>Sucursal:</strong> <?php echo antixss($sucursal); ?> <br />   
 <strong>Monto Mesa: </strong> <?php echo formatomoneda($monto_mesa); ?> | 
 <strong>Pagos Mesa: </strong> <?php echo formatomoneda($pagos_mesa); ?> | 
 <strong>Saldo Mesa: </strong> <?php echo formatomoneda($saldo_mesa); ?> </p>   
  <hr />
<p align="center">  
   <strong>Monto Abonar: </strong> <?php echo formatomoneda($monto_abonar); ?> | 
 <strong>Monto Propina: </strong> <?php echo formatomoneda($monto_propina); ?></p>
 <h3  align="center">Total Abonar:  <?php echo formatomoneda($total_abonar); ?>  </h3>  
  <hr />   
  
  
  <?php if (trim($tiene_qr_hook_alias) == 'N') {  ?>
    <?php if (trim($error_bancard) != "") { ?>
    <div style="color:red;">
    <strong>Respuesta de Bancard:</strong><br /><?php echo $error_bancard; ?>
    </div>
    <?php } else { ?>
    <div style="color:red;">
    <strong>Error de Bancard:</strong><br /> No se pudo conectar al servidor de Bancard, el servidor agoto el tiempo de espera.
    </div>
    <?php } ?>
<?php } ?>
<?php if (trim($tiene_qr_hook_alias) == 'S') { ?>
            <p align="center"><strong>Paso 1:</strong> Ingrese a la App de su Banco y utilice el lector de QR del banco</p>
            <P align="center" id="qr_img"><IMG src="<?php echo $qr_img; ?>" height="300" /><br /><?php  echo $qr_hook_alias ?><br />
            
            </P>
            <hr />
            <p align="center"><strong>Paso 2:</strong> Una vez abonado haga click en el boton comprobar pago:<br />
            
            <div id="comprueba_pago_box">
            <a href="javascript:comprueba_pago();" class="btn btn-sm btn-default" style="background-color:white;"><span class="fa fa-search"></span> Comprobar Pago</a>
            </div>
            
            <div id="pos_msg" style="width:100%; height:40px; margin: 0px auto; border: 1px solid #A0A0A0; text-align: center;background-color:aliceblue; color:#3B3B3B; "></div>
            
            
            
            </p>
<?php } ?>
                                    <br /><hr />
                                    <a href="cuenta_mesas.php" class="btn btn-sm btn-primary" ><span class="fa fa-ban"></span> Cancelar</a>
                                    <br /><br />
                                </div>
                                    
                                    
                                    
                                    
                                    
<div class="clearfix"></div>
<br />
<br /><br /><br /><br />
 

  
  
<div class="clearfix"></div>
<br /><br />
                <div class="row">

      

                      
                    </div>
                            
                      
                      
                      
                      
                      
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        
        <!-- POPUP DE MODAL OCULTO -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="estadocuentadet">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Detalle de la Cuenta</h4>
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
  </body>
</html>
