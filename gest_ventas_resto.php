 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
$idvtmp = intval($_GET['vv']);
/*print_r($_REQUEST);
exit;
*/
//Comprobar apertura de caja en fecha establecida

$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

// script de impresion factura
$consulta = "
select script_factura from preferencias where idempresa = $idempresa limit 1
";
$rsscr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$script = $rsscr->fields['script_factura'];
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



$buscar = "
Select gest_zonas.idzona,descripcion,costoentrega
from gest_zonas
where 
gest_zonas.estado=1 
and gest_zonas.idempresa = $idempresa 
and gest_zonas.idsucursal = $idsucursal
order by descripcion asc
";

$rszonas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<!-------------<link rel="stylesheet" href="css/bootstrap.css" type="text/css" media="screen" /> ---------->
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" media="screen" /> 
<?php require("includes/head.php"); ?>
<script>
<?php if ($idvtmp > 0) {?>
    $( document ).ready(function() {
    var url = 'gest_imprime_factura_laser.php?vv=<?php echo $idvtmp?>';
    $("<a>").attr("href", url).attr("target", "_blank")[0].click();
    document.location.href='gest_ventas_resto.php';
});
<?php }?>    
</script>
<script>
    function filtrar(){
        var buscar=$("#blci").val();
        //var parametros='bus='+buscar;
        //OpenPage('gest_cliev4venta.php',parametros,'POST','clientereca','pred');
        
        var parametros = {
                "bus" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'gest_cliev4venta.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');  
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
    }
    function activar(cual){
        /*espera(1000);
        document.getElementById('clientesel').value=parseInt(cual);
        var parametros='mini='+cual;
        OpenPage('bcliemin4.php',parametros,'POST','adicio','pred');
        document.getElementById('adicio').hidden='';
        setTimeout(function(){ enfocar(); }, 300);
        document.getElementById('clientesel').value=parseInt(cual);*/
        
        var cualsel=parseInt(cual);
        $("#clientesel").val(cualsel);
        var idpedidoc = $("#pedidooc").val();
        idpedidoc = parseInt(idpedidoc);
        var parametros = {
                "mini" : cualsel,
                "id"   : idpedidoc
        };
        $.ajax({
                data:  parametros,
                url:   'bcliemin4.php',
                type:  'post',
                beforeSend: function () {
                      $("#adicio").html('Cargando datos del cliente...');  
                },
                success:  function (response) {
                        $("#adicio").html(response);
                        $("#adicio").show();
                        //alert(response);
                        //$("#clientesel").val(cualsel);
                        //enfocar();
                }
        });
        
    }
    
    function precio(cual){
        if (cual!=''){
            document.getElementById('tipoprecio').value=parseInt(cual);
            
        } else {
            document.getElementById('tipoprecio').value=0;
            
        }
        
        
    }
    function seleccionarp(valor1,valor2,valor3){
        var desc=valor3;
        var idp=valor1;
        var dis=valor2;
        
        
        if ((idp=='') || (dis=='')){
            alertar('ATENCION: Algo sali� mal.','Debe seleccionar un producto.','error','Lo entiendo!');    
        } else {
            
            document.getElementById('prod').value=desc;
            document.getElementById('codigo').value=idp;
            document.getElementById('dispo').value= dis;
            document.getElementById('flechita').hidden='';
            document.getElementById('listota').hidden='hidden'
            
            
        }
    }
    function des(){
        if (document.getElementById('listota').hidden){
            document.getElementById('listota').hidden='';
        } else {
            document.getElementById('listota').hidden='hidden';
        }
    }

    function seleccionarclie(quien){
        if (quien !=''){
            var parametros='tp=2&idcli='+quien;
            OpenPage('includes/formitoventa.php',parametros,'POST','clientito','pred');    
        
        }
        
    }
    function esplitear(valor){
        if (document.getElementById('ventatot').value !=''){
            var r=parseInt(document.getElementById('totalventaf').value);
            
            
            var txto=valor;
            var res = txto.split("-");
            var costo=parseInt(res[1]);
            //alert(costo);
            var netov=parseInt(document.getElementById('totalventaf2').value);
            
            //Valor del descuento si hubiere
            var valord=document.getElementById('descu').value;
            //parseInt(document.getElementById('desc').value);
            if (valord >0){
            
                descontar=parseInt(valord);
            } else {
                descontar=0;    
            }
            if (costo > 0){
                //document.getElementById('centrega').value=costo;
                //document.getElementById('totalventaf2').value=(netov+costo)-descontar;
                document.getElementById('ventatot').value=(netov+costo);
                document.getElementById('netos').value=(netov+costo)-descontar;
                document.getElementById('montogs').value=(netov+costo)-descontar;
                document.getElementById('efec2').value=(netov+costo)-descontar;
                document.getElementById('tarj').value='0';
            } else {
                var netov=parseInt(document.getElementById('totalventaf').value);
                document.getElementById('ventatot').value=(netov+costo);
                document.getElementById('netos').value=(netov+costo)-descontar;
                document.getElementById('montogs').value=(netov+costo)-descontar;
                document.getElementById('efec2').value=(netov+costo)-descontar;
                document.getElementById('tarj').value='0';
            }
        }
    }

    function mentrega(valor){
        //Total global de la venta
        var netov=parseInt(document.getElementById('ta').value);
        //Valor del descuento si hubiere
        var valord=parseInt(document.getElementById('desc').value);
        if (valord >0){
            //Monto a descontar
            //var descontar=parseInt(netov*valord);
            //descontar=descontar/100;
            descontar=0;
        } else {
            descontar=0;    
        }
        var medioentrega=parseInt(document.getElementById('medioentrega').value);
        if (medioentrega==1){
        
            document.getElementById('centrega').value=<?php echo intval($montodelivery)?>;
            document.getElementById('neto').value=(netov+<?php echo intval($montodelivery)?>)-descontar;
        } else {
            document.getElementById('centrega').value=0;
            document.getElementById('neto').value=netov-descontar;
            
        }
        
        
    }
    function registrarventa(){
        var errores='';
        var idcliente=parseInt(document.getElementById('idclioc').value);
        var idt=document.getElementById('idtoc').value;
        var fpago=parseInt(document.getElementById('formapago').value);
        var condicionvta=parseInt(document.getElementById('condventa').value);
        var medioentrega=parseInt(document.getElementById('medioentrega').value);
        var costoentrega=parseInt(document.getElementById('centrega').value);
        var totalabonar=parseInt(document.getElementById('ta').value);
        var netoabonar=document.getElementById('neto').value;
        var ruc=document.getElementById('ruc').value;
        var asignado=parseInt(document.getElementById('asignado').value);
        //controlamos
        
        if (idcliente==0){
            errores=errores+'Debes indicar un cliente. \n'    ;    
        }
        if (ruc==''){
            errores=errores+'Debes indicar ruc del cliente. \n'    ;    
        }
        if (medioentrega==0){
            errores=errores+'Debes indicar medio de entrega. \n'    ;    
        } else {
            if (medioentrega==1){
                //Es un delivery, debems exigir que se asigne a uno de ellos
                if (asignado==0){
                    errores=errores+'Debe asignar un delivery. \n'    ;    
                }
                if (costoentrega==0){
                    errores=errores+'Costo de entrega invalido. \n'    ;    
                }
            }
            
        }
        if (condicionvta==0){
            errores=errores+'Debe indicar tipo de venta. \n'    ;
            
        }
        if (condicionvta==1){
            if (fpago==0){
                errores=errores+'Debe indicar forma de pago p/ venta. \n'    ;
                
            }
        } 
        if (errores!=''){
            alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
        } else {
            document.getElementById('vta').submit();
            
        }
    }
    function most(valor){
        
        if (document.getElementById('descontar')){
            if (valor!=''){
                var md=parseInt(valor);
            } else {
                var md=0;
            }
            var tv=parseInt(document.getElementById('ventatot').value);
            if (md > tv){
                document.getElementById('descu').value=0;
                //document.getElementById('netos').value=tv;
                document.getElementById('rfg').hidden="hidden";
            } else {
                if (tv!=''){
                    //document.getElementById('netos').value=(tv-md);
                    document.getElementById('descu').value=md;
                } else {
                    //document.getElementById('netos').value=tv;
                    document.getElementById('descu').value=0;
                }
                document.getElementById('rfg').hidden="";
            }
            //Aco seguido debemos actualizar los boxes de los montos y el 
            
        }
        
        
    }
    function most_porc(valor){
        var totvent = $("#tvdes").val();
        var montodes =  (totvent*valor)/100;
        $("#descontar").val(montodes);
        most(montodes);
    }
    <!-----------------------DESCUENTOS------------->
    function registrardes(){
        if (document.getElementById('motiv')){
            
            //tomamos el motivo
            var motivo=document.getElementById('motiv').value;
            document.getElementById('ocmoti').value=motivo;
            if (motivo !=''){
                
                var descontar=document.getElementById('descontar').value;
                if (descontar !=''){
                    var tv=document.getElementById('ventatot').value;
                    //alert(tv);
                    var mg=tv-descontar;
                    document.getElementById('montogs').value=parseInt(mg);
                    document.getElementById('efec2').value=parseInt(mg);
                    document.getElementById('tarj').value='0';
                    document.getElementById('netos').value=parseInt(mg);
                    setTimeout(function(){ cerrar(1); }, 100);
                    
                    setTimeout(function(){ document.getElementById('montogs').focus(); }, 50);
                } else {
                    
                    alertar('ATENCION: Algo salio mal.','Debe indicar monto a descontar','error','Lo entiendo!');
                }
            } else {
                
                alertar('ATENCION: Algo salio mal.','Debe indicar motivo descuento','error','Lo entiendo!');
                
            }    
        }
    }
    /*---------------------------------------------DIVISION CUENTA EFE-CRED--------------------------------*/
    function dividir(cual,monto){
        //vemos si hay descuento
        var efe=0;
        var dc=document.getElementById('descu').value;
        var tventa=document.getElementById('ventatot').value;
        var neo=parseInt(tventa-dc);
        if (cual==1){
                //efectivo    
            var n=monto;
            var re=neo-n;
            document.getElementById('tarj').value=re;
                
        }
        if (cual==2){
            //tarjeta    
            var n=monto;
            var re=neo-n;
            document.getElementById('efec2').value=re;
                
        }
        
            
    
    
    
    
    }
    function alertar(titulo,error,tipo,boton){
    swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
    }
    
</script>
<script>
<?php /*if ($registrado=='S'){ ?>
//Asigna FAC o TK
function asignar(cual){
    var idventa=<?php echo $idventa?>;
    if (cual==1){
        var loadi = '<div style="background-color:#009900; font-weight:bold; width:180px; color:#FFFFFF; margin:0px auto; text-align:center;">Enviando Impresion...Aguarde</div>';
        var factura=document.getElementById('factun').value;
        if (factura !=''){
            document.getElementById('fac').hidden='hidden';

            var parametros='fc='+factura+'&idv='+idventa;
            OpenPage('gest_gen_fc.php',parametros,'POST','impresion',loadi);
        } else {
            alertar('ATENCION: Algo salio mal.','Debe ingresar numero de factura aser asignado. ','error','Lo entiendo!');
        }
    } else {
        document.getElementById('tic').hidden='hidden';
        var sucu=<?php echo $idsucursal?>;
        var pe=<?php echo $pe?>;
        var e=<?php echo $idempresa?>;
        parametros='vta='+idventa+'&s='+sucu+'&pe='+pe+'&e='+e;
        $("#impresion").delay(200).queue(function(n) {
        $.ajax({
               type: "POST",
                 url: "gest_gen_tk.php",
                 data: parametros,
                 dataType: "html",
                 error: function(){
                       alert("error petici�n ajax");
                 },
                  success: function(data){
                             r=$("#impresion").html(data);
                             if (document.getElementById('ocrec')){
                                 var re=(document.getElementById('ocrec').value);
                                 document.getElementById('recibo').value=re;
                                 espera(2000);
                                 envia(2);
                              } else {
                                document.getElementById('recibo').value='';

                              }
                             n();
                  }

                  });

         });
    }

}
<?php }*/ ?>
</script>
<script type="text/javascript">
//----------------------------------------------CONTROL DE TECLAS---------------------------------------------------//
function reca(){
    if(document.getElementById('totlaventaf')){
        var tv=document.getElementById('totlaventaf').value;
        document.getElementById('tventa').value=tv;
    }
}

//-------------------------------ASIGNAR POPUS-----------------------------------------------//
/*function popupasigna(){
         $(function mag() {
            $('a[href="#pop1"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });        
}*/
function popupasigna(){
         $(function mag() {
            $.magnificPopup.open({
                items: {
                    src: '#pop1',
                },
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });        
}
function popupasignabb(){
         $(function mag() {
            $.magnificPopup.open({
                items: {
                    src: '#pop2',
                },
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });    
}

function abrepop(){
    document.getElementById('enlace2').click();
    setTimeout(function(){ enfoquenuevo(); }, 80);
}

<!------------------------------------------>
function asignarv(cual){
    var parametros='';
    var parametros = {
              "id" : 0
    };
    
    if (cual==1){
        var direccionurl='gest_minic4.php';
    }
    if (cual==2){
        var direccionurl='clientesexistentes.php';
    }
    if (cual==3){
        var direccionurl='gest_aplicadescuento.php';
        var totvta=document.getElementById('ventatot').value;
        var pedi=document.getElementById('pedidooc').value;
        var parametros = {
             "tv" : totvta,
             "pedi" : pedi
        };
    }
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("Cargando...");
                },
                success:  function (response) {
                        popupasigna();
                        $("#pop1").html(response);
                }
        });
    
}
function asignardt(cual){
    
     var parametros = {
                "idpedido" : cual
        };
       $.ajax({
                data:  parametros,
                url:   'detapedidomini.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("Cargando...");
                },
                success:  function (response) {
                        popupasigna();
                        $("#pop1").html(response);
                }
        });
    
}
function asignardt_mesa(cual){
    
     var parametros = {
                "idmesa" : cual
        };
       $.ajax({
                data:  parametros,
                url:   'detapedidomini_mesa.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("Cargando...");
                },
                success:  function (response) {
                        popupasigna();
                        $("#pop1").html(response);
                }
        });
    
}
function nclie(){
    var errores='';
    var nombres=document.getElementById('nombreclie').value;
    
    var apellidos=document.getElementById('apellidos').value;
    var docu=0;
    var ruc=document.getElementById('ruccliente').value;
    var direclie=document.getElementById('direccioncliente').value;
    var telfo=document.getElementById('telefonoclie').value;
    
    if (nombres==''){
        errores=errores+'Debe indicar nombres del cliente. \n';
    }
    if (apellidos==''){
        errores=errores+'Debe indicar apellidos del cliente. \n';
    }
    if (docu==''){
        //errores=errores+'Debe indicar documento del cliente. \n';
    }
    if (ruc==''){
        errores=errores+'Debe indicar documento del cliente o ruc generico. \n';
    }
    if (errores==''){
        //var parametros='n=1&nom='+nombres+'&ape='+apellidos+'&dc='+docu+'&ruc='+ruc+'&dire='+direclie+'&telfo='+telfo;
        //OpenPage('gest_cliev4venta.php',parametros,'POST','clientereca','pred');
        
         var parametros = {
                    "n"     : 1,
                    "nom"   : nombres,
                    "ape"   : apellidos,
                    "dc"    : docu,
                    "ruc"   : ruc,
                    "dire"  : direclie,
                    "telfo" : telfo
            };
           $.ajax({
                    data:  parametros,
                    url:   'gest_cliev4venta.php',
                    type:  'post',
                    beforeSend: function () {
                            $("#clientereca").html("");
                    },
                    success:  function (response) {
                            $("#clientereca").html(response);
                            //$("#clientesel").val(response);
                            //alert(response);
                            if(response == 'duplicado'){
                                alertar('ATENCION:','Ya existe un cliente con el ruc seleccionado','error','Lo entiendo!');    
                                
                            }else{
                                activar(response);
                                setTimeout(function(){ cerrar(1); }, 1000);
                            }
                    }
            });
        
        
        //alert(1);
        

    } else {
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
        
    }
    
}
function selecciona_cliente(valor){
    ///alert("a"+valor);
    activar(valor);
    setTimeout(function(){ cerrar(1); }, 100);
}
function sel(prod){
    if (prod !=''){
        document.getElementById('prodbus').value='';
        document.getElementById('prodbus').value=prod;
        setTimeout(function(){ cerrar(1); }, 100);
        //setTimeout(function(){ enfocar(); }, 100);
        document.getElementById('prodbus').onkeypress=13;
        //var e = $.Event( "keypress", { which: 13 } );
        //$('#prodbus').trigger(e);
        //($('#prodbus').event.keycode==13){
                
        //setTimeout(function(){ enfocar(); }, 100);
        
    }
    
}
function cerrar(n){
    if (n==1){
         $.magnificPopup.close();
            
    }
    
}

<?php /* ?>function validar(tipo,numero){
    var cod=document.getElementById('codauto').value;
    var parametros='codauto='+cod+'&tp='+tipo;
    //OpenPage('gest_codauto.php',parametros,'POST','res','pred');
    $("#res").delay(200).queue(function(n) {
        $.ajax({
               type: "POST",
                 url: "gest_codauto.php",
                 data: parametros,
                 dataType: "html",
                 error: function(){
                       alert("error petici�n ajax");
                 },
                  success: function(data){
                             r=$("#res").html(data);
                             if (document.getElementById('resok')){
                                var resul=parseInt(document.getElementById('resok').value);
                                if (resul > 0){
                                    //autorizamos
                                    document.getElementById('autorizar_'+numero).innerHTML="<a href='javascrip:void(0);' onClick='eliminar("+numero+")'><img src='img/no.PNG' width='16' height='16' title='Eliminar Producto'/></a>";
                                    setTimeout(function(){ cerrar(1); }, 100);
                                } else {
                                    //no autoriza

                                }
                              }
                             n();
                  }

                  });

         });

}<?php */?>
function tipodocu(cual){
    document.getElementById('tipodocusele').value=parseInt(cual);    
    if (cual==1){
        //tk
        document.getElementById('tk').disabled="disabled";
        document.getElementById('fc').disabled="";
        document.getElementById('nf').value="";
        document.getElementById('nf').readOnly="readOnly";
        document.getElementById('metodo').value=1;
        //setTimeout(function(){ enfocar(); }, 300);
    }
    if (cual==2){
        //fc
        document.getElementById('nf').readOnly="";
        document.getElementById('nf').value="";
        
        document.getElementById('tk').disabled="";
        document.getElementById('fc').disabled="disabled";
        document.getElementById('metodo').value=2;
        //setTimeout(function(){ enfocar(); }, 300);
    }
}
function mediopago(medio){
    if (medio >1){
        document.getElementById('mediopagotr').innerHTML='';
        document.getElementById('mediopagotr').hidden='';
        var parametros='medio='+medio;
        OpenPage('mini_clase.php',parametros,'POST','mediopagotr','pred');
        
    } else {
        document.getElementById('mediopagotr').innerHTML='';
        document.getElementById('mediopagotr').hidden='hidden';
        
    }
    
}
function marca(pedido,tipo){
    //var cuadroact = $("#cuadroactivo").val();
    var cuadroact = tipo;
    if(cuadroact == 'Mesa'){
        var parametros = {
            "idmesa" : pedido
        };
    }else{
        /*var idt=<?php echo $idt?>;*/
        var idp=pedido;
        var parametros = {
            "pedido" : idp
            
        };
    }    
       $.ajax({
                data:  parametros,
                url:   'gest_mini_tmp.php',
                type:  'post',
                beforeSend: function () {
                        $("#detafinal").html("Cargando...");
                },
                success:  function (response) {
                        $("#detafinal").html(response);
                        var clientesel = $("#clientesel").val();
                        clientesel = parseInt(clientesel);
                        if(clientesel > 0){
                        }else{
                            clientesel=0;    
                        }
                        //alert(clientesel);
                        activar(0);
                        // delivery
                        esplitear($("#tipozona").val());
                }
        });
    
}
function chau(valor){
    if(window.confirm('Esta seguro que desea borrar el pedido '+valor+'?')){
        if (valor!=''){
            var parametros='chau='+valor;
            OpenPage('mini_pendientes.php',parametros,'POST','pendientes','pred');
            
        }
    }

}
function chau_mesa(valor,mesa){
    if(window.confirm('Esta seguro que desea borrar la cuenta de la mesa '+mesa+'?')){
        if (valor!=''){
            var parametros='mesa='+valor;
            OpenPage('mini_mesas_pendientes.php',parametros,'POST','mesas','pred');
            
        }
    }

}
function vuelto(monto){
    if (document.getElementById('totalventaf')){
        if (monto!=''){
            var monto=parseInt(monto);
            if (monto > 0){
                //var totalventa=parseInt(document.getElementById('totalventaf').value);
                var totalventa=parseInt(document.getElementById('netos').value);
                if (totalventa > 0){
                    if (parseInt(monto) >=totalventa){
                        var dife=parseInt(monto-totalventa);
                        document.getElementById('vueltogs').value=dife;
                    } else {
                        document.getElementById('vueltogs').value='0';
                    }
                    
                } else {
                    document.getElementById('vueltogs').value='0';
                    
                }
            }    
        } else {
            document.getElementById('vueltogs').value='0';
                
        }
    }
    setTimeout(function(){ reca(1); }, 100);
}

function llenar(){
    if (document.getElementById('totlaventaf')){
        var nf=document.getElementById('totlaventaf').value;
        document.getElementById('tventa').value=nf;
    
    }
}
//-------------------------------RESTO FUNCTIONS---------------------------------------//

    
    
function sele(produ){
    /*var idt=<?php echo $idt?>;*/
    
    var parametros='texto='+produ+'&transaccion='+idt+'&tp=1';
    OpenPage('gest_mini_tmp.php',parametros,'POST','detafinal','pred');
    setTimeout(function(){ reca(1); }, 100);    
    
    
    
}

function reca(){
    var tventa=document.getElementById('totalventaf').value;
    document.getElementById('tventaocgs').value=tventa;
    var recibe=document.getElementById('montogs').value;
    document.getElementById('montogsoc').value=recibe;
                             
    
    
}

//--------------------------------------------------------------------------------//
function finalizar(){
    
    $("#terminar").hide();
    var errores='';

    var totalventa=parseInt(document.getElementById('totalventaf').value);
    var neto=parseInt(document.getElementById('netos').value);
    var montorecibe=document.getElementById('montogs').value;
    if (montorecibe==''){
            errores=errores+'Debe indicar Monto recibido.';
    } else {
        //comprar los monos if (montorecibe < totalventa){
        if (montorecibe < neto){
            errores=errores+'Monto recibido es inferior. Verifique';
        } else {
            var dife=parseInt(montorecibe-totalventa);
            document.getElementById('vueltogs').value=dife;
        
        }
    }
        
    
    if (errores==''){
        $("#terminar").hide();
        document.getElementById('regventamini').submit();
    } else {
        $("#terminar").show();
        document.getElementById('montogs').focus();
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');    
        
    }

        //
}
function actualizar(){
    var cuadroact = $("#cuadroactivo").val();
    if(cuadroact == 'pedido'){
        var urlbusca='mini_pendientes.php';
    }else{
        var urlbusca='mini_mesas_pendientes.php';    
    }
    var htmlped = $("#pendientes").html();
    var htmlmes = $("#mesas").html();
        var parametros = {
                "act" : 'S'
        };
        $.ajax({
                data:  parametros,
                url:   urlbusca,
                type:  'post',
                beforeSend: function () {
                    $("#actualizando").show();
                },
                success:  function (response) {
                    $("#actualizando").hide();
                    if(cuadroact == 'pedido'){
                        if(htmlped != response){
                            $("#pendientes").html(response);
                        }
                    }
                    if(cuadroact == 'mesa'){
                        if(htmlmes != response){
                            $("#mesas").html(response);
                        }
                    }
                    actualizar_delivery();
                    
                }
        });
    
}
function actualizar_delivery(){
    //var cuadroact = $("#cuadroactivo").val();
    var urlbusca='mini_pendientes_delivery.php';
        var parametros = {
                "act" : 'S'
        };
        $.ajax({
                data:  parametros,
                url:   urlbusca,
                cache: false,
                type:  'post',
                beforeSend: function () {
                    $("#actualizando_del").show();
                },
                success:  function (response) {
                    $("#actualizando_del").hide();
                    $("#delivery_pend").html(response);
                }
        });
    
}

function reimpimir(id){
        var parametros = {
                "id" : id
        };
       $.ajax({
                data:  parametros,
                url:   'reimprimir.php',
                type:  'post',
                beforeSend: function () {
                        $("#reimpcoc").remove();
                        $("#reimprimebox").html("<br /><br />Enviando Impresion...<br /><br />");
                        //$("#lista_prod").html("Cargando Opciones...");
                        //$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
                        $("#reimprimebox").html(response);
                        //$("#reimpcoc").show();
                        //$("#prod_"+id).html(html);
                        //$("#lista_prod").html(response);
                        //$("#contador_"+id).html(response);
                        //actualiza_carrito();
                }
        });
        
}
function reimpimir_mesa(id){
    $("#reimpmes").remove();
    $("#reimprimebox").html('<iframe src="impresor_ticket_mesa.php?idmesa='+id+'" style="width:310px; height:500px;"></iframe>');
    //setTimeout(function(){$("#reimprimebox").html('')},3000);
}
function reimpimir_comp(id){
    $("#reimpcaj").remove();
    $("#reimprimebox").html('<iframe src="impresor_ticket_reimp.php?idped='+id+'" style="width:310px; height:500px;"></iframe>');        
}
function mesas(){
        $("#cuadroactivo").val('mesa');
        $("#mesasbtn").hide();
        $("#mesas").show();
        $("#pedidosbtn").show();
        $("#pendientes").hide();
        actualizar();
}
function pedidos(){
        $("#cuadroactivo").val('pedido');
        $("#pedidosbtn").hide();
        $("#pendientes").show();
        $("#mesasbtn").show();
        $("#mesas").hide();
        actualizar();
}

function formadepago(){
    $("#formapago option[value=2]").prop("selected",true);    
}
function tipo_fac(tipo){
    if(tipo == 'fac'){    
        //if($("#rad_fac").is(':checked')){ 
            $("#facturabox").show();
        //}
    }
    if(tipo == 'tk'){
        //if($("#rad_tk").is(':checked')){ 
            $("#facturabox").hide();
        //}
    }
}
$( document ).ready(function() {
    setInterval(function(){actualizar();},10000);
    var clientesel = $("#clientesel").val();
    clientesel = parseInt(clientesel);
    if(clientesel > 0){
    }else{
        clientesel=0;    
    }
    activar(0);
    //activar(0);
});
function registrar_venta(){
    // recibe parametros
    var errores='';
    var cual=0;
    var tipodoc = $('input[name=tkofac]:checked').val();
    var pedido_id = $("#pedido_id").val();
    var montomixto = $("#tarj").val();
    if(tipodoc == 'fac'){
        var tipo = 1; // factura
    }else{
        var tipo = 2;    // ticket
    }
    //var tipodocu=tipo;
    //var mpago=parseInt($("#mediopagooc").val());
    var tarj = $("#tarj").val();
    var efec2 = $("#efec2").val();
    // medio de pago
    if(tarj > 0 && efec2 == 0){
        var mpago = 2;
    }
    if(efec2 > 0 && tarj == 0){
        var mpago = 1;
    }
    if(efec2 > 0 && tarj > 0){
        var mpago = 3;
    }
    var idadherente=0;
    if (isNaN(mpago)){
        cual='';
    } else {
        cual=(mpago);
    }
    if (cual==''){
        cual=9;
    }
    //alert(cual);
    var totalventa= $("#totalventaf").val();
    if (isNaN(totalventa)){
        totalventa=0;
    }
    var montorecibido = parseFloat($("#montogs").val());
    var efectivo=0;
    var tarjeta=0;
    var tipotarjeta=0;
    var montocheque=0;
    var numcheque='';
    var vuelto=parseInt($("#vueltogs").val());//solo referencial
    if (isNaN(vuelto)){
        vuelto=0;
    }
    var tipozona = $("#tipozona").val();
    var pref1 = $("#suc").val();
    var pref2 = $("#pe").val();
    var fact = $("#nf").val();
    var mesa = $("#mesaoc").val();
    var cliente = 0;
    cliente=$("#clientesel").val();
    if (cliente==''){
        cliente = $("#occliedefe").val();
    }
    var domicilio=$("#domi").val();
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
    var condi=$("#condventa").val();
    var chapa='';
    var motivodesc = $("#ocmoti").val();
    var descuento = $("#descu").val();
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
            var suma=parseInt(montorecibido);
            //alert(efectivo);
            //alert(suma);
            //alert(totalventa);
            //alert(delivery);
            //alert(descuento);
            var totalcondescydel=(parseInt(totalventa)+parseInt(delivery)-parseInt(descuento));
            //alert(totalcondescydel);
            if (suma>(totalcondescydel)){
                // errores=errores+'Esta intentando cobrar mas del total de venta.\n';
            }
            if (suma<(totalcondescydel) && montorecibido >0){
                    errores=errores+'Esta intentando cobrar menos del total de venta.\n';
                    alert(suma);
                    alert(totalcondescydel);
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
            }
            
        }
    }
    
    if (cual==''){
        errores=errores+'Medio de pago incorrecto. \n';
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
            "pedido"           : pedido_id,
            "idzona"           : tipozona,
            "idadherente"      : idadherente,
            "banco"            : banco,
            "adicional"        : adicional,
            "condventa"        : condi,
            "mediopago"        : cual,
            "fac_suc"          : pref1,
            "domicilio"        : domicilio,
            "fac_pexp"         : pref2,
            "fac_nro"          : fact,
            "llevapos"         : llevapos,
            "cambiode"         : cambiopara,
            "observadelivery"  : obsdel,
            "mesa"             : mesa,
            "canal"            : 2,
            "fin"              : 3,
            "idcliente"        : cliente,
            "monto_recibido"   : montorecibido,
            "descuento"        : descuento,
            "motivo_descuento" : motivodesc,
            "chapa"            : chapa,
            "montocheque"      : montocheque,
            "montomixto"       : montomixto,
            "json"             : 'S',
            'modviejo'         : 'S',
            
            
        };
        
       $.ajax({
                data:  parametros,
                url:   'registrar_venta.php',
                type:  'post',
                beforeSend: function () {
                        $("#resumenmini").html("<br /><br />Registrando...<br /><br />");
                },
                success:  function (response) {
                    $("#resumenmini").html(response);
                    if(IsJsonString(response)){
                        //alert(response);
                        var obj = jQuery.parseJSON(response);
                        // borra_carrito();
                        <?php $script = "script_central_impresion.php";?>
                        if(obj.error == ''){
                            document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk='+tipo+'&clase=1&modventa=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
                        }else{
                            alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto.php');
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
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function borra_prod(id,idcab){
        var codigo_borra = $("#codigo_borra_"+id).val();
        var urlbusca='borra_prod_ped.php';
        var parametros = {
                "idventatmp" : id,
                "idtmpventares_cab" : idcab,
                "cod" : codigo_borra
        };
        $.ajax({
                data:  parametros,
                url:   urlbusca,
                cache: false,
                type:  'post',
                beforeSend: function () {
                    $("#pop1").html('Cargando...');
                },
                success:  function (response) {
                    if(response == 'OK'){
                        asignardt(idcab);
                        actualizar();
                    }else{
                        $("#pop1").html(response);
                        actualizar();
                    }
                }
        });
}
function borra_prod_mesa(id,idcab,idmesa){
        var codigo_borra = $("#codigo_borra_"+id).val();
        var urlbusca='borra_prod_ped.php';
        var parametros = {
                "idventatmp" : id,
                "idtmpventares_cab" : idcab,
                "mesa" : idmesa,
                "cod" : codigo_borra
        };
        $.ajax({
                data:  parametros,
                url:   urlbusca,
                cache: false,
                type:  'post',
                beforeSend: function () {
                    $("#pop1").html('Cargando...');
                },
                success:  function (response) {
                    if(response == 'OK'){
                        asignardt_mesa(idmesa);
                        actualizar();
                    }else{
                        $("#pop1").html(response);
                        actualizar();
                    }
                }
        });
}
</script>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
 <link rel="stylesheet" href="css/magnific-popup.css">

</head>
<body bgcolor="#FFFFFF" style="background-color:#FFFFFF">

<div class="clear"></div>
<a href="#pop2" id="enlace2" onClick="lprodu();" hidden="hidden">fff</a>
<div id="impresion">
        
</div>

<!---------------------------------------------------------------------------------------->
<div style="width:99%;height:610px; border: 0px solid; color:#4B1AF0; border-bottom-style:double; border-top-style:double; background-color:#FFFFFF; border-left-style:groove " id="centro">
   <!---------------------FRAME--------------------------------------------------->
   
    <!-----------------------------------UNO--------------------------------->
     <div style="float:left; border:0px solid #FF0509; width:33%;height:600px; overflow-y:scroll; overflow-x:hidden;">
        <div align="center">
               <span class="resaltaditomenor" style="text-align:center;">Pedidos y Mesas</span><br />
             <input type="button" value="Actualizar" onclick="actualizar()" />
             <input type="button" id="mesasbtn" value="Mesas" onclick="mesas();" />
             <input type="button" id="pedidosbtn" value="Pedidos" onclick="pedidos();" style="display:none;" />
             <input type="hidden" id="cuadroactivo" value="pedido" />
             <div align='center' id="actualizando" style=" position:fixed;  background-color:#FFFFFF; width:100px; text-align:center; border:0px solid; display:none;">Actualizando...<br /><img src='img/cargando.gif' width='32' height='32' /></div>
        </div>
        <div id="mesas" style="display:none;">
            <?php require_once("mini_mesas_pendientes.php")?>
        </div>
        <div id="pendientes">
            <?php require_once("mini_pendientes.php")?>
        </div>
    </div>
         <div style="float:left; border:0px solid #FF0509; width:33%;height:600px; overflow-y:scroll; overflow-x:hidden;">
        <div align="center">
               <span class="resaltaditomenor" style="text-align:center;">Delivery</span><br />
             <input type="button" value="Actualizar" onclick="actualizar_delivery()" />
             <input type="button" id="pedidosbtn" value="Pedidos" onclick="pedidos();" style="display:none;" />
             <input type="hidden" id="cuadroactivo" value="pedido" />
             <div align='center' id="actualizando_del" style=" position:fixed;  background-color:#FFFFFF; width:100px; text-align:center; border:0px solid; display:none;">Actualizando...<br /><img src='img/cargando.gif' width='32' height='32' /></div>
        </div>
        <div id="delivery_pend">
            <?php require_once("mini_pendientes_delivery.php")?>
        </div>
    </div>
    
       <!----------------------OTRO DIV--------------------->
     <div style="float:left; border:0px solid #50F168; width:33%; height:600px; overflow-y:auto; overflow-x:hidden;" id="detafinal">
        
        <?php require_once("gest_mini_tmp.php"); ?>    

    </div>
    
    
    
<div class="clear"></div>
</div> <!-- cuerpo -->
<div id="pop1" class="mfp-hide" style="background-color:#F9F7F7; width:800px; height:auto; margin-left:auto; margin-right:auto;">
</div>
<div id="pop2" class="mfp-hide" style="background-color:#F9F7F7; width:600px; height:auto; margin-left:auto; margin-right:auto;">     
</div>
 <script>
      
            $(function mag() {
                $('a[href="#login-popup"]').magnificPopup({
                    type:'inline',
                    midClick: false,
                    closeOnBgClick: false
                });
                
            }); 
     
            function buscar(cual){
                var texto=cual.trim();
                var parametros='texto='+texto;
                OpenPage('buscaprod.php',parametros,'POST','encontrados','pred');
                
                
            }
        </script>
        <script src="js/jquery.magnific-popup.min.js"></script>
<div class="clear"></div><!-- clear2 -->


</body>
</html>


 
