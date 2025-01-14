 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "186";
require_once("includes/rsusuario.php");
$tipocarrito = 1;
//elimina exigencia de pin
$_SESSION['self'] = '';

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}

//-------------------POSTS-----------------------//
require_once('gest_verifica_post.php');
//---------------------------------------------//




//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$script = trim($rspref->fields['script_factura']);
$script_balanza = trim($rspref->fields['scipt_balanza']);
if ($script_balanza == '') {
    //direccion x defecto p servidor en cliente
    $script_balanza = 'http://localhost/balanza/balanza_ladocliente.php';
} else {
    $script_balanza = strtolower($script_balanza);
}
$usarbcode = trim($rspref->fields['usabcode']);
$balanza = trim($rspref->fields['usa_balanza']);
//Indica si el peso al ser capturado va hacer click solo
$autopeso = trim($rspref->fields['autopeso']);
$canal = 1; // tablet
if (intval($_GET['canal']) > 0) {
    $_SESSION['canal'] = intval($_GET['canal']); // elije canal
}
if (intval($_SESSION['canal']) > 0) {
    $canal = intval($_SESSION['canal']); // asigna canal
}
$activabtn = intval($rspref->fields['activa_reg_teclaesp']);
if ($activabtn == 0) {

    $style1 = "style=\"width: 100px;height: 40px;background-color: aliceblue;\"";
} else {
    $style1 = "style=\"width: 120px;height: 40px;background-color: orange;\"";

}

$buscar = "Select * from productos_codigos_rapidos order by orden asc";
$rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcod = $rscod->RecordCount();
if ($tcod > 0) {
    $arraycod = "";
    while (!$rscod->EOF) {

        $arraycod = $arraycod.'"'.$rscod->fields['orden'].'"'.',';
        //$arraycod=$arraycod.$rscod->fields['orden'].',';
        $rscod->MoveNext();
    }
    //$arraycod=str_replace(",","",$arraycod(-1));
    $arraycod = substr($arraycod, 0, (-1));
}
?>
<!doctype html>
<html>
<head>
    <style type="text/css">

html,body{

margin:0px;

height:100%;

}
    </style>
<meta charset="utf-8">
<title>Ventas tipo Registradora</title>
    <?php require_once("includes/head_ventas_registradora.php");?>
    <script src="js/shortcut.js"></script>
    <script src="js/atajos_registradora.js"></script>
    <script>
        
    function enviaform(cual){
        if (cual==1){
            document.getElementById('activateclas1').submit();
        }
        
    }
    function controlar(cual){
        var bgh=new Array();
        
        bgh=[<?php echo $arraycod?>];
        //alert(bgh);
        //alert(bgh.length);
        //buscar en array
        var encontrado=0;
        var tt=cual; 
        for(var i = 0; i < bgh.length; i++) {
            //alert(bgh[i]);
            if (bgh[i]==tt ) {
                encontrado=1;
                //alert('Encontrado '+cual);
                 existe(cual);
                break;
                
            } 
        }
        if (i==bgh.length && encontrado==0 && cual!=''){
            //alert('bu');
            bcode(cual);
            
        }
        //al finalizar limpiamos x seguridad
        $("#bcodet").val("");
        $("#bcodet2").val("");
    }
    function bcode(cual){
        
        var parametros = {
                "prod" : cual,
                "partir": 2
                
                };
                 $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
                        
                },
                success:  function (response) {
                    $("#carrito").html(response);
                        setTimeout(function(){actualiza_carrito();}, 100);
                        $("#bcodet").val("");
                        $("#bcodet2").val("");
                        setTimeout(function(){ var totalv=$("#totalventa").val();
                                            // alert(totalv);
                        formatnumber(totalv); }, 500);
                        setTimeout(function(){ var totalv=$("#totalventa").val();recargarsubsub(totalv); }, 1000);
                        
                    }
                });
        
        
        
        
        
    }
    function existe(cual){
        //si existe el objeto, envia
        var cantidad=document.getElementById('bcodet2').value;
        if (cantidad==''){
            cantidad=1;
        } else {
            cantidad=parseInt(cantidad);
            
        }
                var parametros = {
                "tecla" : cual,
                "cant"    : cantidad
                };
                 $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
                        
                },
                success:  function (response) {
                    $("#carrito").html(response);
                        setTimeout(function(){actualiza_carrito();}, 100);
                        $("#bcodet").val("");
                        $("#bcodet2").val("");
                        setTimeout(function(){ var totalv=$("#totalventa").val();
                                            // alert(totalv);
                        formatnumber(totalv); }, 500);
                    setTimeout(function(){ var totalv=$("#totalventa").val();recargarsubsub(totalv); }, 1000);
                        
                    }
                });
            
            
            
            
            
    }
    function borrar(idprod,txt){
            var parametros = {
                "prod" : idprod
            };
            swal({
                    title: "Esta seguro?",
                    text: "Borrar "+txt+"'?'",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#DD6B55',
                    confirmButtonText: 'Si, borrar',
                    cancelButtonText: "No borrar",
                    closeOnConfirm: false,
                    closeOnCancel: false
                 },
                 function(isConfirm){

                 if (isConfirm){
                         $.ajax({
                                data:  parametros,
                                url:   'carrito_borra.php',
                                type:  'post',
                                beforeSend: function () {
                                    
                                },
                                success:  function (response) {
                                    $("#carrito").html(response);
                                    var totalv=$("#totalventa").val();
                                    formatnumber(totalv);
                                      swal("Listo", txt+" borrado", "success");
                                      swal.close();
                                    recargarsubsub();
                                }
                        });

                    } else {
                            swal("Cancelado", "Producto no borrado", "error");
                    }
         });
}
    function borrar_fast(indice){
        document.getElementById('borrando').value=0;
         document.getElementById('lastelemento').value='';
        document.getElementById('borraele').style.backgroundColor="#F5F5F5";
        var producto=document.getElementById('onp_'+indice).value;
        var parametros = {
                "prod" : producto
        };
         $.ajax({
            data:  parametros,
            url:   'carrito_borra.php',
            type:  'post',
            beforeSend: function () {
                                    
            },
            success:  function (response) {
                $("#carrito").html(response);
                var totalv=    $("#totalventa").val();
                formatnumber(totalv);
                recargarsubsub(totalv);
            }
        });
        
    }    
        
        
        
    function recargarsubsub(tventa){
        var totalv=tventa;
        //alert(totalv);
        var parametros = {
                "ta" : totalv
        };
         $.ajax({
            data:  parametros,
            url:   'gest_minisub.php',
            type:  'post',
            beforeSend: function () {
                                    
            },
            success:  function (response) {
                $("#subneto").html(response);
                
                
            }
        });
        
        
    }    
    function formatnumber(num) {
    if (!num || num == 'NaN') return '-';
    if (num == 'Infinity') return '&#x221e;';
    num = num.toString().replace(/\$|\,/g, '');
    if (isNaN(num))
        num = "0";
    sign = (num == (num = Math.abs(num)));
    num = Math.floor(num * 100 + 0.50000000001);
    cents = num % 100;
    num = Math.floor(num / 100).toString();
    if (cents < 10)
        cents = "0" + cents;
    for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3) ; i++)
        num = num.substring(0, num.length - (4 * i + 3)) + '.' + num.substring(num.length - (4 * i + 3));
        //+ ',' + cents
    //return (((sign) ? '' : '-') + num );
        $("#subtnum").html((((sign) ? '' : '-') + num ));
        //document.getElementById('subtnum').innerHTML(((sign) ? '' : '-') + num);
    }    
    
    function chautr (i) {
             document.getElementsByTagName("table")[0].setAttribute("id","tableid");
             document.getElementById("tableid").deleteRow(i);
    }
    
    function actualiza_carrito(){
        var parametros = {
                "act" : 'S',
                "tipocarrito" :1
        };
        $.ajax({
                data:  parametros,
                url:   'gest_ventas_resto_carrito.php',
                type:  'post',
                beforeSend: function () {
                        //$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
                        $("#carrito").html(response);
                }
        });
}
        function seleccionarelemento(){
            var cuale=$("#lastelemento").val();
            
            
            if (cuale==''){
                //marcamos el primer elemento de la grilla
                var bb=document.getElementById('td_1');
                bb.style.backgroundColor="#FF5733";
                $("#lastelemento").val("1");
                
            } else {
                
            }
            
        }
        function buscarcliente(rucb){
            if ($("#rzc").is(":visible")){
                $("#rzbox").html("");
            }
            if(rucb!=''){
                 var parametros = {
                "ruc" : rucb
                };
                $.ajax({
                        data:  parametros,
                        url:   'mini_clienteaj.php',
                        type:  'post',
                        beforeSend: function () {
                                //$("#carrito").html("Actualizando Carrito...");
                            
                        },
                        success:  function (response) {
                            if(IsJsonString(response)){
                                var resp = jQuery.parseJSON(response);
                                // ejemplo: resp.valido
                                $("#rucbox").html(resp.ruc);
                                
                                if (resp.valido=='S'){
                                    $("#occliente").val(resp.idcliente);
                                    $("#rzc").val("");
                                    $("#rzc").hide();
                                    $("#rzbox").html(resp.razon_social);
                                } else {
                                    $("#rzc").show();
                                }
                            }else{
                                alert(response);
                            }    
                        }
                });
                
                
                
                
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
        function calcular(valor){
            var totalv=    $("#totalventa").val();
            if (valor!=''){
                valor=parseInt(valor);
                
                if ($('#vueltogs').show()){
                    var vuelto=(valor-totalv);
                    
                    if (vuelto < 0){
                        vuelto=0;
                    }
                    document.getElementById('vueltogsr').value=vuelto;
                    
                } else {
                    $('#vueltogs').hide();
                
                }
            
                
                
                
            }
            
            
            
            
        }
        function alertar(titulo,error,tipo,boton){
    swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
    }
    </script>
    
</head>

<body <?php if ($activabtn == 1) {?>enfocar();<?php }?>>
    
    <div align="center" style="height: 100%;width: 100%; border: 1px solid #000000;">
        <!----------DIV CONTENEDOR DE ITEMS-------------------->
        <div style=" width: 100%; margin-left: auto;margin-right: auto; background-color: black;height: 140px;">
            <!----------DISPLAY-------------------->
            <div style="float: left; width: 60%; font-size: 8.5em;color: #03D895; height: 140px; text-align: right;" id="subtnum">
            0
            </div>
            <!---------INPUT BARCODE---->
            <div style="float: left; width: 40%; font-size: 8.5em;color: #03D895; height: 140px; text-align: right;">
                
              <input type="text" name="bcodet2" id="bcodet2" value="" style="background-color: black; border: 1px solid #000000; outline: none;"  />
                <div id="cargaoculta">
                
                </div>
            </div>
        </div>
        <div align="center">
            <table width="70%;">
                <tr>
                    <td align="right">
                    <img src="img/barcode01.png" width="40" height="40" alt=""/>
                    </td>
                    <td width="80%;">
                     <input type="text" name="bcodet" id="bcodet" value="" style=" border: 1px solid #000000; outline: none; width:70%;height: 40px;"  />
                  </td>
              </tr>
            </table>
     </div>
      <div style="float: left;margin-left: 2px; width: 40%; border: 0px solid #F7474A; height: 99%;">
          <img src="img/carrito.png" width="40" height="40" alt=""/>
            
          <div id="carrito"><?php require_once("gest_ventas_resto_carrito.php"); ?></div>
      </div>
        <?php
        if ($activabtn == 0) {
            $tx = "F4 / ACTIVAR";
        } else {
            $tx = "F4 / DESACTIVAR";
        }

?>
      <!----------DIV CONTENEDOR DE NUMEROS Y FUNCIONES------------------->
        <div style="float: left;margin-left: 2px; width: 25%; border: 2px solid #2FF4F1; height: 80%; text-align: 
                    center;">
            <!----------DIV CONTENEDOR DE FUNCIONES ESPECIALES-------------------->
            <div style="width: 100%; height: 40px; float: left;" >
                <img src="img/key.png" width="40" height="40" alt=""/><br />
                  <input type="hidden" name="borrando" id="borrando" value="0" />
              <div style="float: left; width: 30%;">
                <form id="activateclas1" action="" method="post">
                            <input type="submit" value="<?php echo $tx;?>" <?php echo $style1;?> />
                            <input type="hidden"  name="fnteclasesp" id="fnteclasesp" value="<?php echo $activabtn?>" />
                </form>
                </div>
                <div style="float: left; width: 20%; margin-left: 2%; height: 40px;">
                    <input type="button" value="IZQ / Seleccionar Item p/ borrar" 
                           style=" height: 40px;" onClick="seleccionarelemento();" name="borraele" id="borraele" />    
                </div>
                 
                
          </div>
            
          <!----------DIV CONTENEDOR DE NUMEROS-------------------->
            <div style="margin-top: 25%; float: left; width: 100%;">
                <?php require_once('mini_funciones_especiales.php')?>
            </div>
        </div>
        <div style="float: left;margin-left: 2px; width: 30%; border: 2px solid #F7474A; height: 80%;">
            <img src="img/41958-200.png" width="40" height="40" alt=""/><br />
            <div style="width: 30%; float: left; height: 30px;"><input type="button" value="X / Cobrar cuenta" /></div>
            <div style="width: 30%; float: left; height: 30px;"><input type="button" value="V / Venta Rapida" /></div>
            <div style="width: 30%; float: left; height: 30px;"><input type="button" id="botoncliente" value="C / Cambiar cliente" onClick="desocultar(1)" /></div>
            <div style="width: 30%; float: left; height: 30px;"><input type="button" id="botoncliente" value="A - Administrar Caja" onClick="desocultar(1)" /></div>
            <div style="width: 30%; float: left; height: 30px;"><input type="button" id="reim" value="R - Reimpresiones"  /></div>
            <div style="width: 100%; float: left; height: 40px; display: none;" id="divbuscacliente" >
                <table width="100%;">
                    <tr>
                        <td><input type="text" style="width: 99%; height: 40px;" value="" name="bcliente" id="bcliente" placeholder="Ingrese RUC a buscar" onKeyUp="buscarcliente(this.value);" /></td>
                        <td id="imga">
                            <img src="img/checkbox.gif" width="13" height="13" alt="" id="imaok" style="display:none;"/>
                            <img src="img/no.PNG" width="16" height="16" alt="" id="imano" style="display:none;"/>
                        </td>
                    </tr>
                
                </table>
                </div>
            <div id="subneto" style="margin-top: 0px; " >
                <?php require_once('gest_minisub.php');?>
            </div>
            <?php




?>
          
            
      </div>
    </div>
    
    <script>
    window.onload=atajos();formatnumber(<?php echo $totalacum;?>);
        <?php if ($activabtn == 1) {?>
        $( document ).ready(function() {
              $( "#bcodet" ).focus();
             event.preventDefault();
        });
        <?php }?>
        
        $(document).keydown(function(tecla){ 
            var indice=document.getElementById('lastelemento').value;
            var telementoscarrito=document.getElementById('totalelementos').value;
            //alert(tecla.keyCode);
            if (tecla.keyCode==13){
                //retornar enfoque
                if ($("#bcodet").is(":focus")){
                    
                } else {
                    $("#bcodet").focus();
                    if ($("#bcliente").is(":focus")){
                        //enviamos el foco a tipoventa 
                        $("#tipoventa").focus();
                    } else {
                        if ($("#tipoventa").is(":focus")){
                            //presiona enter y enviamos el enfoque a razon social si esta visible o a monto recibido
                            if ($("#rzc").is(":visible")){
                                //se busco un ruc, que puede o no existir
                                $("#rzc").focus();
                            } else {
                                //enviamos el enfoque a monto recibido
                                $("#mrecibe").focus();
                            }
                        } else {
                            if ($("#rzc").is(":visible")){
                                if ($("#rzc").is(":focus")){
                                    $("#mrecibe").focus();
                                }
                            } else {
                                if ($("#mrecibe").is(":focus")){
                                    $("#mpago").focus();
                                } else {
                                    $("#mrecibe").focus();
                                }
                                
                            }
                            
                        }
                        
                    }
                }
                
            }
            if (tecla.keyCode==65){
                window.navigate ("gest_administrar_caja.php"); 
                
                
            }
            if (tecla.keyCode==86){
                //venta rapida
                var totalv=$("#totalventa").val();
                if (parseInt(totalv)>0){
                    //solo tickete
                    registrar_venta(1);
                    
                } else {
                    swal("ATENCION", "Debe ingresar al menos 1 item para vender", "error");
                    
                    
                }
                
            }
            if (tecla.keyCode==88){
                //Cobrar Cuenta
                    var totalv=$("#totalventa").val();
                if (parseInt(totalv)>0){
                    $("#tipoventa").focus();
                } else {
                    swal("ATENCION", "Debe ingresar al menos 1 item para vender", "error");
                    
                    
                }
            }
            if (tecla.keyCode==84){
                //TIckete
                    var totalv=$("#totalventa").val();
                if (parseInt(totalv)>0){
                    registrar_venta(2);
                } else {
                    swal("ATENCION", "Debe ingresar al menos 1 item para vender", "error");
                    
                    
                }
            }
            if (tecla.keyCode==70){
                //Venta con factura
                    var totalv=$("#totalventa").val();
                if (parseInt(totalv)>0){
                    registrar_venta(1);
                } else {
                    swal("ATENCION", "Debe ingresar al menos 1 item para vender", "error");
                    
                    
                }
            }
            if (tecla.keyCode==67){
                //cambiarcliente
                if ($("#bcliente").is(":focus")){
                    
                } else {
                    if ($("#divbuscacliente").is(":visible")){
                        $("#divbuscacliente").hide("linear");
                        document.getElementById('botoncliente').style.backgroundColor="#F5F5F5";
                    } else {
                        document.getElementById('botoncliente').style.backgroundColor="#E67E22";
                        $("#divbuscacliente").show("swing");
                        setTimeout(function(){ $("#bcliente").val("");});
                        
                        $("#bcliente").focus();
                    }
                }
            }
            if (tecla.keyCode==37){
                //IZQ ACTIVA BORRADO
                document.getElementById('borraele').style.backgroundColor="#e6f5ff";
                document.getElementById('borraele').click();
                document.getElementById('borrando').value=1;
                
            }
               if (tecla.keyCode==39){
                 //DER DESACTIVA BORRADO
                 
                document.getElementById('borraele').style.backgroundColor="#F5F5F5";
                 document.getElementById('td_'+indice).style.backgroundColor="#FFF";
                 document.getElementById('lastelemento').value='';
                 document.getElementById('borrando').value=0;
                
             }
             if (tecla.keyCode==38){
                 //ARRIBA
                 document.getElementById('td_'+indice).style.backgroundColor="#FFF";
                indice=parseInt(indice)-1;
                
                 if (indice <= 0){
                     indice=1;
                 }
                document.getElementById('lastelemento').value=indice;
                document.getElementById('td_'+indice).style.backgroundColor="#FF5733";
                 document.getElementById('borrando').value=indice;
             }
            if (tecla.keyCode==40){
                 //ABAJO
                document.getElementById('td_'+indice).style.backgroundColor="#FFF";
                indice=parseInt(indice)+1;
                if (indice >telementoscarrito){
                    
                    //dejamos en la ultima posicion marcada
                    indice=parseInt(indice)-1;
                    
                } 
                document.getElementById('lastelemento').value=indice;
                document.getElementById('td_'+indice).style.backgroundColor="#FF5733";
                document.getElementById('borrando').value=indice;
             }
        });
    </script>
    <script>
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
    function registrar_venta(tipo){
        var errores='';
        var cual=0;
        var tipodocu=tipo; // ticket o factura
        var mpago=parseInt($("#mpago").val());
        var idpedido = 0;
        var chapa = '';
        var idadherente=0;
        var idservcom = 0;
        if (isNaN(mpago)){
            cual='';
        } else {
            cual=(mpago);
        }
        if (cual==''){
            cual=9;
        }
    //alert(cual);
    var totalventa= $("#totalventaoc").val();
    if (isNaN(totalventa)){
        totalventa=0;
    }
    var montorecibido = parseFloat($("#montorecibido").val());
    var efectivo=0;
    var tarjeta=0;
    var tipotarjeta=0;
    var montocheque=0;
    var numcheque='';
    var vuelto=parseInt($("#vuelto").val());//solo referencial
    if (isNaN(vuelto)){
        vuelto=0;
    }
    var tipozona = $("#tipozona").val();
    var pref1 = $("#pref1").val();
    var pref2 = $("#pref2").val();
    var fact = $("#fact").val();
    var mesa = $("#mesa").val();
    var cliente = 0;
    cliente=$("#occliente").val();
        //alert(cliente);
    if (cliente==''){
        cliente = $("#occliedefe").val();
    }
    var domicilio=<?php echo intval($_COOKIE['dom_deliv']);?>;
    var delivery=$("#delioc").val();
    if (isNaN(delivery)){
        delivery=0;
    }
    var banco=$("#adicional2").val();
    if (isNaN(banco)){
        banco=0;
    }
    var adicional=$("#adicional1").val();
    if (isNaN(adicional)){
        adicional=0;
    }
    var llevapos=$("#llevapos").val();
    var cambiopara=$("#cambiopara").val();
    var obsdel=$("#observa").val();
    var condi=$("#tipoventa").val();
    var domiex = <?php echo intval($_COOKIE['dom_deliv']);?>;
    var motivodesc = $("#motivodesc").val();
    var descuento = $("#descuento").val();
    if (isNaN(descuento)){
        var descuento = '0';
        var motivodesc ='';
    } else {
        if (parseFloat(descuento) > 0 && motivodesc==''){
            errores=errores+'Debe indicar motivo del descuento.\n';
            
        }
    }
    //Comprobar disponible de productos  total de venta
    if (totalventa==0){
        errores=errores+'Debe agregar al menos un producto para vender. \n';
    }
    

    /*---------------------------------CONTROLES DE MONTOS***************************************/
    
    if (cual==1){
        
        //EFECTIVO
        if ((montorecibido)==0){
            errores=errores+'Debe indicar Monto recibido p/ efectivo.\n';
        } else {
            efectivo=(montorecibido-vuelto);    
        }    
    }
    if (cual==2){
        
        //TARJETA Credito
        tipotarjeta=1;
        if ((montorecibido)==0){
            errores=errores+'Debe indicar Monto para cobrar TC.\n';
        } else {
            tarjeta=(montorecibido);
        }
            
    }
    if (cual==3){
        
        //TMIXTO
        efectivo=efectivo=parseFloat(montorecibido);
        tarjeta=parseFloat($("#tarjeta").val());
        if ((efectivo)==0){
            errores=errores+'Debe indicar porcion efectivo para cobro mixto.\n';
        }
        if ((tarjeta)==0){
            errores=errores+'Debe indicar porcion TC para cobro mixto.\n';
        }        
    }
    if (cual==4){
        
        //TARJETA DEBITO
        tipotarjeta=2;
        if ((montorecibido)==0){
            errores=errores+'Debe indicar Monto para cobrar TD .\n';
        } else {
            tarjeta=(montorecibido);
        }
            
    }
    if (cual==5){
        //es cheque, exigir banco y numero
        efectivo=0;
        tarjeta=0;
        montocheque=parseFloat(montorecibido);
        numcheque=adicional;
        if (banco==0 && numcheque==0){
            errores=errores+'Debe indicar numero de cheque y seleccionar banco. \n';
        }
    } 
    if (cual==8){
        //es adherente
        efectivo=0;
        tarjeta=0;
        montocheque=0;
        numcheque=0;
        condi=2;
    } 
    
    if (cual==9){
        montorecibido=totalventa;
        efectivo=montorecibido;
        tarjeta=0;
        montocheque=0;
        numcheque=0;
        condi=1;
    }
    
    /*-----------------------------------------------------------------------------------------*/
    // CONTADO
    if (condi==1){
            //Validaciones
            var suma=parseFloat(montorecibido);
            //alert(efectivo);
            if (suma>(totalventa+delivery)){
                 //errores=errores+'Esta intentando cobrar mas del total de venta.\n';
            }
            if (suma<totalventa && montorecibido >0){
                errores=errores+'Esta intentando cobrar menos del total de venta.\n';
            }
    }
    // CREDITO
    if (condi==2){
        //Ultimo control de seguridad x venta credito
        var gen=$("#occliedefe").val();
        if (cliente==gen && cual!=8){
            errores=errores+'Debe registrar al cliente para acceder a linea de credito.\n';
        } else {
            if (cual==8){
                idadherente=$("#idadhetx").val();
                if (idadherente==0){
                        errores=errores+'Debe indicar un adherente para registrar la venta. \n';
                }
                idservcom=$("#idservcombox").val();
                if (idservcom==0){
                        errores=errores+'Debe indicar un tipo de servicio para registrar la venta. \n';
                }
            }
            
        }
    }
    
    if (cual==''){
        errores=errores+'Medio de pago incorrecto. \n';
    }
    if(domiex > 0){
        if(tipozona == '0 - 0'){
            errores=errores+'Debe indicar la zona cuando es delivery. \n';
        }
    }
    if (errores==''){
        $("#terminar").hide();
        //alert('enviar');
        if(tipo == 2){
            fact='';
        }
        
        // INICIO REGISTRAR VENTAS //
        //alert(cual);
        var parametros = {
            "pedido"         : idpedido,
            "idzona"         : tipozona, // zona costo delivery
            "idadherente"    : idadherente,
            "idservcom"      : idservcom, // servicio comida
            "banco"          : banco,
            "adicional"      :adicional, // numero de cheque, tarjeta, etc
            "condventa"      : condi, // credito o contado
            "mediopago"      : cual, // forma de pago
            "fac_suc"        : pref1,
            "fac_pexp"       : pref2,
            "fac_nro"        : fact,
            "domicilio"      : <?php echo intval($_COOKIE['dom_deliv']);?>, // codigo domicilio
            "llevapos"       :llevapos,
            "cambiode"       : cambiopara,
            "observadelivery" : obsdel,
            "mesa"           : 0,
            "canal"          : 2, // delivery, carry out, mesa, caja
            "fin"            : 3,
            "idcliente"      : cliente,
            "monto_recibido" : montorecibido,
            "descuento"      : descuento,
            "motivo_descuento": motivodesc,
            "chapa"          : chapa,
            "montocheque"    : montocheque,
            "json"           : 'S'
            
            
        };
        
       $.ajax({
                data:  parametros,
                url:   'registrar_venta.php',
                type:  'post',
                beforeSend: function () {
                        $("#carrito").html("<br /><br />Registrando...<br /><br />");
                },
                success:  function (response) {
                    $("#carrito").html(response);
                    if(IsJsonString(response)){
                        var obj = jQuery.parseJSON(response);
                         
                        if(obj.error == ''){
                            //borra_carrito();
                        if(obj.redirfac == 'S'){
                               document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?v='+obj.idventa+'&modventa=3">';
                             }else{
                                document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?v='+obj.idventa+'&modventa=3&tk=1">';
                             }
                        }else{
                            alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','ventas_registradora.php<?php echo $redirbus; ?>');
                        }
                    }else{
                        alert(response);
                    }
                }
        });
        
        // FIN REGISTRAR VENTAS //
        
        
        
        
    } else {
        $("#terminar").show();
        //document.getElementById('montorecibido').focus();
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');    
    }

}
    
    </script>
    
    
</body>
</html>
