<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//elimina exigencia de pin
$_SESSION['self'] = '';

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}

//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$script = trim($rspref->fields['script_factura']);
$script_balanza = trim($rspref->fields['scipt_balanza']);
$alerta_ventas = trim($rspref->fields['alerta_ventas']);
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


// tipo de favoritos
$consulta = "
SELECT tipofavorito, maxprod, maxdias
FROM tipofavorito
where 
idempresa = $idempresa
limit 1
";
$rsfav = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$maxprod = intval($rsfav->fields['maxprod']);
$maxdias = intval($rsfav->fields['maxdias']);




// rellenar favorito si no se relleno hoy
$consulta = "
select actualizado 
from favoritos 
where
idempresa = $idempresa 
and idsucursal = $idsucursal 
order by actualizado asc 
limit 1
";
$rsfav2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si aun no se lleno hoy para esta sucursal
if ($rsfav2->fields['actualizado'] != date("Y-m-d")) {
    // borra los favoritos de la tabla temporal
    $consulta = "
	delete from favoritos 
	where 
	idempresa = $idempresa 
	and idsucursal = $idsucursal 
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // tipo favorito seleccionados
    if (strtoupper($rsfav->fields['tipofavorito']) == 'V') {
        // lista los productos favoritos
        $consulta = "
		INSERT INTO favoritos (idproducto, idsucursal, idempresa, cantidad_venta, actualizado)
		select p.idprod_serial, $idsucursal, $idempresa, sum(ventas_detalles.cantidad), current_date
		from ventas 
		inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
		inner join productos p on p.idprod_serial = ventas_detalles.idprod
		inner join productos_sucursales on productos_sucursales.idproducto = p.idprod_serial
		where
		date(ventas.fecha) >= date_sub(date(NOW()), INTERVAL $maxdias DAY)
		and ventas.estado <> 6
		and ventas.sucursal = $idsucursal
		and p.idempresa = $idempresa
		and p.borrado = 'N'
		and productos_sucursales.idsucursal = $idsucursal 
		and productos_sucursales.idempresa = $idempresa
		and productos_sucursales.activo_suc = 1
		group by p.idprod_serial
		order by count(ventas_detalles.idprod) desc, p.descripcion asc
		limit $maxprod
		";
        $rsfavrell = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    } else {

        // lista los productos favoritos
        $consulta = "
		INSERT INTO favoritos (idproducto, idsucursal, idempresa, cantidad_venta, actualizado)
		SELECT idprod_serial, $idsucursal, $idempresa, 1, current_date
		from productos 
		where 
		borrado = 'N' 
		and productos.favorito = 'S'
		";
        $rsfavrell = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


}




$whereadd = "";
$cat = intval($_GET['cat']);
$busqueda = intval($_GET['bus']);
if ($busqueda == 0) {
    if ($cat > 0) {
        $whereadd .= " and idcategoria = $cat";
    } else {
        if (strtoupper($rsfav->fields['tipofavorito']) == 'F') {
            $whereadd .= " and favorito = 'S'";
        } else {
            $whereadd .= "
			and productos.idprod_serial in (
			select idproducto 
			from favoritos 
			where 
			idsucursal = $idsucursal 
			and idempresa = $idempresa
			)
			";
        }
    }
} else {



}
$t = intval($_GET['t']);
if ($t > 0) {
    $whereadd .= " and idsubcate = $t";
}


// productos
$consulta = "
select *, productos_sucursales.precio as p1,
	(
	select 
	sum(cantidad) as total
	from tmp_ventares 
	where 
	tmp_ventares.registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado='N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idproducto = productos.idprod_serial
	and tmp_ventares.idempresa = $idempresa
	and tmp_ventares.idsucursal = $idsucursal
	) as total
from productos 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
where
idprod_serial is not null
and productos.idempresa = $idempresa
and productos.borrado = 'N'

and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.idempresa = $idempresa
and productos_sucursales.activo_suc = 1

$whereadd
order by combo desc, combinado desc, productos.descripcion asc
";
$rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



// total productos en carrito
$consulta = "
select count(*) as total
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalprod = intval($rs->fields['total']);

// monto total de productos en carrito
$consulta = "
select sum(precio) as total_monto
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalprod = intval($rs->fields['total_monto']);
// monto total agregados
$consulta = "
SELECT sum(precio_adicional) as montototalagregados, count(idventatmp) as totalagregados
FROM 
tmp_ventares_agregado
where
idventatmp in (
select tmp_ventares.idventatmp
from tmp_ventares 
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
)
";
$rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalag = intval($rsag->fields['montototalagregados']);
$montototalag = 0;
// productos mas agregados
$montototal = $montototalprod + $montototalag;
//echo $montototal;





$redirbus = "";
$redirbus2 = "";
if ($_GET['bus'] == 1) {
    $redirbus = "?bus=1";
    $redirbus2 = "&bus=1";
}
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Ventas</title>
<?php require_once("includes/head_ventas.php");?>
<style>
body{
	margin:0px;
	padding:0px;
	font-family:Verdana, Geneva, sans-serif;
	font-size:12px;
}
.vta_contenedor{
	width:1200px; 
	/*border:1px solid #000000;*/ 
	margin:0px auto;
	/*background-color:#FFFFFF;*/
	/*border: 1px solid #000000;*/
}
.vta_izquierda{
	float:left; width:370px;
	min-height:785px;
	background-color:#FFFFFF;
	border-bottom:1px solid #000000;
	border-left:1px solid #000000;
	border-top:1px solid #000000;
	border-radius: 10px 0px 0px 10px;
	-moz-border-radius: 10px 0px 0px 10px;
	-webkit-border-radius: 10px 0px 0px 10px;
}
.vta_derecha{
	float:left; width:826px; 
	min-height:787px;
	background-color:#FFFFFF;
	border:1px solid #000000;
	border-radius: 0px 10px 10px 0px;
	-moz-border-radius: 0px 10px 10px 0px;
	-webkit-border-radius: 0px 10px 10px 0px;
}
.productos{
	border-radius: 0px 10px 10px 0px;
	-moz-border-radius: 0px 10px 10px 0px;
	-webkit-border-radius: 0px 10px 10px 0px;
}
.divconbordea{
	border:1px solid #000000;
	border-radius: 30px 30px 0px 0px;
	-moz-border-radius: 30px 30px 0px 0px;
	-webkit-border-radius: 30px 30px 0px 0px;
}
</style>
<script src="js/shortcut.js"></script>

<script>

function agregon(posicion,producto,precio){
	if (posicion!=''){
		
		//document.getElementById('cv_'+posicion).hidden='hidden';
		//var cantidad=document.getElementById('cvender_'+posicion).value;
		var cantidad=$("#cvender_"+posicion).val();
		
		var precio='';
		var prod1='';
		var prod2='';
		
		if (cantidad==''){
			cantidad=1;
			
		}
        var parametros = {
                "prod" : producto,
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
                
							$("#carrito").html("Actualizando Carrito...");
						}
						$("#cvender_"+posicion).val("");
						$("#cv_"+posicion).hide();
                },
                success:  function (response) {
					$("#cv_"+posicion).show();
					$("#carrito").html(response);
					//alert(response);
					actualiza_carrito();
                }
        });
	
		
	}
	
	
}





//PREVENTS ENTER ON BRCODE
$(document).ready(function(){
		$("#codigo").keydown(function(e){
			if(e.which==17 || e.which==74){
				e.preventDefault();
			}else{
				console.log(e.which);
			}
		})
		<?php if ($usarbcode == 'S' && intval($_GET['bus']) == 0) { ?>
			$("#bccode").focus();
		<?php } ?>
});	

/* var eventoControlado = false;
window.onload = function() { 
document.onkeypress = mostrarInformacionCaracter;
	//document.onkeyup = mostrarInformacionTecla; 
}
function mostrarInformacionCaracter(evObject) {
	var elCaracter = String.fromCharCode(evObject.which);
	alert (elCaracter);
	
	
}*/

function apretar(id,prod1,prod2){
		//alert(id+'-'+prod1+'-'+prod2);
		if(prod1 > 0){
			var precio = 0;
		}else{
			var html = document.getElementById("prod_"+id).innerHTML;
			var precio = document.getElementById("precio_"+id).value;	
		}
        var parametros = {
                "prod" : id,
				"cant" : 1,
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
							document.location.href='gest_ventas_resto_caja.php?cat=<?php echo $cat; ?>';
						}else{
							$("#prod_"+id).html(html);
							$("#contador_"+id).html(response);
							actualiza_carrito();
						}
                }
        });
	
}
function carritocodigo(){
	var cual=document.getElementById('buscador').value;
	var cantidad=document.getElementById('cantibuscador').value;
	var precio='';
	var prod1='';
	var prod2='';
		
	if (cantidad==''){
			cantidad=1;
			
	}
	var parametros = {
                "prod" : cual,
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
                        	//$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
						}
                },
                success:  function (response) {
						if (document.getElementById('cantibuscador')){
							document.getElementById('cantibuscador').value='';
							document.getElementById('buscador').value='';
							document.getElementById('buscador').focus();
							document.getElementById('recarga').innerHTML='';
						}
						actualiza_carrito();
					   
                }
        });
	
}
	//Enviamos como parametro partir 2 para indicar que puede ser un producto pesable
function carritocodigonew(){
	
	var cual=document.getElementById('bccode').value;
	var cantidad=document.getElementById('cantcode').value;
	var precio='';
	var prod1='';
	var prod2='';
		
	if (cantidad==''){
			cantidad=1;
			
	}
	var parametros = {
                "prod" : cual,
				"cant" : cantidad,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2,
		        "partir"  :2
        };
	  $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
						if(prod1 > 0){
							
						}else{
                        	//$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
						}
                },
                success:  function (response) {
					//alert(response);
						 if (document.getElementById('cantcode')){
							document.getElementById('cantcode').value='';
							document.getElementById('bccode').value='';
							document.getElementById('bccode').focus();
							document.getElementById('recarga').innerHTML='';
							//alert(response);
						}
						actualiza_carrito(); 
					   
                }
        });
	
}
function seleccionar(producto){
		var cantidad=document.getElementById('cantidad').value;
		var precio='';
		var prod1='';
		var prod2='';
		
		if (cantidad==''){
			cantidad=1;
			
		}
        var parametros = {
                "prod" : producto,
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
							//$("#lista_prod").html("Registrando...");
						}else{
                        	//$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito..");
						}
                },
                success:  function (response) {
					
					//alert(response);
						/*if(prod1 > 0 && parseInt(response) > 0){
						//	$("#lista_prod").html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
							//document.location.href='gest_ventas_resto_caja.php?cat=<?php echo $cat; ?>';
						}else{
							//$("#prod_"+id).html(html);
							//$("#contador_"+id).html(response);
							
						}*/
						if (document.getElementById('cantidad')){
							document.getElementById('cantidad').value='';
							document.getElementById('busqueda').value='';
							document.getElementById('recarga').innerHTML='';
						}
						actualiza_carrito();
                }
        });
	
}
function apretar_pizza(id){
		var html = document.getElementById("prod_"+id).innerHTML;
        var parametros = {
                "id" : id
        };
       $.ajax({
                data:  parametros,
                url:   'pizza.php',
                type:  'post',
                beforeSend: function () {
                      //  $("#prod_"+id).html("Cargando Opciones...");
						//$("#lista_prod").html("Cargando Opciones...");
						//$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						$("#prod_"+id).html(html);
						$("#lista_prod").html(response);
						//$("#contador_"+id).html(response);
						//actualiza_carrito();
                }
        });
	
}
function marcar_pizza(id,idcomb){
	prodmitad = document.getElementById('mitad_'+id);
	//alert(id);
	//alert(prodmitad.checked);
	if(prodmitad.checked){
		//prodmitad.checked='';
		$('#mitad_'+id)[0].checked = false;
		// si no queda ninguno sin marcar
		if($('input:checkbox:checked').size() == 0){
			document.getElementById('prod_1').value=0;
			document.getElementById('prod_2').value=0;
		}
		// si queda 1 sin marcar
		if($('input:checkbox:checked').size() == 1){
			// busca cual es el que desmarco
			if(document.getElementById('prod_1').value == id){
				document.getElementById('prod_1').value=document.getElementById('prod_2').value;
				document.getElementById('prod_2').value=0;
			}
			if(document.getElementById('prod_2').value == id){
				document.getElementById('prod_2').value=0;
			}		
		}
	}else{
		//prodmitad.checked='checked';
		$('#mitad_'+id)[0].checked = true;
		if($('input:checkbox:checked').size() == 1){
			document.getElementById('prod_1').value=id;
			//alert(producto1);
			//apretar(id,prod1=0,prod2=0);
		}
		if($('input:checkbox:checked').size() == 2){
			document.getElementById('prod_2').value=id;
			apretar(idcomb,document.getElementById('prod_1').value,document.getElementById('prod_2').value);
		}
		if($('input:checkbox:checked').size() > 2){	
			alert("Error! Solo puedes marcar 2 mitades.");
			$(".cajitasbox").each(function(){
                $(this).prop('checked',false);
				document.getElementById('prod_1').value=0;
				document.getElementById('prod_2').value=0;
            });
		}
	}
		
}
function actualiza_carrito(){
        var parametros = {
                "act" : 'S'
        };
		$.ajax({
                data:  parametros,
                url:   'gest_ventas_resto_carrito.php',
                type:  'post',
                beforeSend: function () {
                        $("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						$("#carrito").html(response);
                }
        });
}
function borrar(idprod,txt){
			var parametros = {
                "prod" : idprod
			};
	if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){	
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							$("#carrito").html("Actualizando Carrito...");
					},
					success:  function (response) {
							$("#carrito").html(response);
							// si existe el div de ese producto
							if ($("#contador_"+idprod).length > 0) {
								$("#contador_"+idprod).html(0);
							}
							if (document.getElementById('filtrar')){
								var al=document.getElementById('filtrar').value;
								filtra(al);
							
							}
					}
			});
	}
}
function borrar_todo(){
			var parametros = {
                "todo" : 'S'
			};
	if(window.confirm("Esta seguro que desea borrar TODO?")){	
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							$("#carrito").html("Borrando...");
					},
					success:  function (response) {
							document.location.href='gest_ventas_resto_caja.php';
					}
			});
	}
}
function valida_ruc(){
	var ruc = document.getElementById('ruc').value;
	if(ruc == ''){
		document.getElementById('ruc').value = '44444401-7';
	}
}
function valida_rz(){
	var raz = document.getElementById('razon_social').value;
	if(raz == ''){
		document.getElementById('razon_social').value = 'Consumidor Final';
	}
}
/*
$(window).scroll(function() {
   if($(window).scrollTop() + $(window).height() == $(document).height()) {
       enfocar('chapa');
	   //setTimeout(function(){document.getElementById('chapa').click()},50);
   }
});*/
function cambiar_canal(canal){
	document.body.innerHTML='Cambiando de Canal...';
	document.location.href='gest_ventas_resto_caja.php?canal='+canal;
}
function filtrar_pizza(subcat){
	document.location.href='gest_ventas_resto_caja.php?cat=2&t='+subcat;
}
function filtrar_lomito(subcat){
	document.location.href='gest_ventas_resto_caja.php?cat=1&t='+subcat;
}
function filtrar_subcat(cat,subcat){
	document.location.href='gest_ventas_resto_caja.php?cat='+cat+'&t='+subcat;
}
// manejar cookie
function setCookie(cname,cvalue,exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires=" + d.toGMTString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function checkCookie() {
    var user=getCookie("username");
    if (user != "") {
        alert("Welcome again " + user);
    } else {
       user = prompt("Please enter your name:","");
       if (user != "" && user != null) {
           setCookie("username", user, 30);
       }
    }
}
// manejar cookie

function mantiene_carrito(){
	// campos
	<?php if (intval($_SESSION['canal']) == 1 or intval($_SESSION['canal']) == 0) { ?>
	var v_chapa = $("#chapa").val();
	var v_ruc = $("#ruc").val();
	var v_razon_social = $("#razon_social").val();
	var v_delivery = $("#delivery").val();
	setCookie('chapa_cookie',v_chapa,2);
	setCookie('ruc_cookie',v_ruc,2);
	setCookie('razon_social_cookie',v_razon_social,2);
	setCookie('delivery_cookie',v_delivery,2);
	// ocultar chapa si es delivery
	if(v_delivery == 'N'){
		if(v_chapa == 'DELIVERY'){
			$("#chapa").val('');
		}
		$("#chapatr").show();
	}else{
		$("#chapatr").hide();
		$("#chapa").val('DELIVERY');
	}
	// validar ruc
	if(v_ruc == ''){
		$("#ruc").val('<?php echo $ruc_pred; ?>');
	}
	if(v_razon_social == ''){
		$("#razon_social").val('<?php echo $razon_social_pred; ?>');
	}
	
	<?php } else { ?>
	var v_mesa = $("#mesa").val();
	setCookie('mesa_cookie',v_mesa,2);
	<?php } ?>
	var v_observacion = $("#observacion").val();
	setCookie('observacion_cookie',v_observacion,2);	
}
function borra_carrito(){
	setCookie('chapa_cookie',"",-1);
	setCookie('ruc_cookie',"",-1);
	setCookie('razon_social_cookie',"",-1);
	setCookie('delivery_cookie',"",-1);
	setCookie('mesa_cookie',"",-1);
	setCookie('observacion_cookie',"",-1);	
}
function Moneda(valor){
	valor = valor+'';
var num = valor.replace(/\./g,"");
	if(!isNaN(num)){
		num = num.toString().split("").reverse().join("").replace(/(?=\d*\.?)(\d{3})/g,"$1.");
		num = num.split("").reverse().join("").replace(/^[\.]/,"");
		res = num;
	}else{
		res = valor.replace(/[^\d\.]*/g,"");
	}
	return res;
}
function esplitear(valor){
	var txto=valor;
	var res = txto.split("-");
	var costo=parseInt(res[1]);
	var totalventa_real = $("#totalventa_real").val();
	var totalventa_condelivery = parseInt(totalventa_real)+parseInt(costo);
	$("#totalventa").val(totalventa_condelivery);
	$("#totalventa_box").html(Moneda(totalventa_condelivery));
	$("#montorecibido").val(totalventa_condelivery);
	$("#delioc").val(costo);
	if (costo> 0){
		//es delivery
		//$("#llevapos").show();
		//$("#cambiopara").show();
		$("#obstr").show();
		$("#vueltotr").hide();
		$("#recibidotr").hide();
		
	} else {
		//$("#llevapos").hide();
		//$("#cambiopara").hide();
		$("#obstr").hide();
		$("#vueltotr").show();
		$("#recibidotr").show();
	}
	actualiza_saldos();
}

function actualiza_saldos(){
	
	// recibe parametros
	var totalventa = parseInt($("#totalventa").val());
	var descuento = parseInt($("#descuento").val());
	var montorecibido = parseInt($("#montorecibido").val());
	var tarjeta = parseInt($("#tarjeta").val());
	var mediopago = $("#mediopagooc").val();
	var vueltotxt = '';
	// convierte nan
	if (isNaN(totalventa)){
		totalventa=0;
	}
	if (isNaN(descuento)){
		descuento=0;
	}
	if (isNaN(montorecibido)){
		montorecibido=0;
	}
	if (isNaN(tarjeta)){
		tarjeta=0;
	}
	
	
	// neto a cobrar
	//alert(descuento);
	//alert(totalventa);
	if(descuento <= totalventa){
		var netocobrar = totalventa-descuento;
		//alert(netocobrar);
	}else{
		descuento = 0;
		$("#descuento").val(0);
		var netocobrar = totalventa;
	}
	$("#netocobrar").html(netocobrar);
	

	// validaciones y conversiones segun medio de pago
	// efectivo
	if(mediopago == 1){
		$("#vueltotd").show();
		var vuelto = montorecibido-netocobrar;
		if (vuelto > 0){
			vueltotxt = Moneda(vuelto);
			$("#vueltocnt").html(vueltotxt);
			
		} else {
			vueltotxt = 0;
			vuelto = 0;
			$("#vueltocnt").html(vueltotxt);
				
		}
		$("#vuelto").val(vuelto);
		if(montorecibido < 0){
			$("#montorecibido").val(netocobrar);
		}
	}
	// tarjeta
	if(mediopago == 2){
		if(montorecibido >= netocobrar){
			$("#montorecibido").val(netocobrar);
			$("#vueltocnt").html(0);
		}
	}
	// mixto
	if(mediopago == 3){
		if(montorecibido > netocobrar){
		   $("#montorecibido").val(netocobrar);
			montorecibido = netocobrar;
		}
		tarjeta = netocobrar-montorecibido;
		$("#tarjeta").val(tarjeta);
	}
	// motivo descuento
	if($("#descuento").val() > 0){
		$("#motivodesc_box").show();
	}else{
		$("#motivodesc_box").hide();	
		$("#motivo_descuento").val('');
	}
	
	
}
	
function registrar_venta(tipo){
	// recibe parametros
	var errores='';
	var cual=0;
	var tipodocu=tipo; // ticket o factura
	var mpago=parseInt($("#mediopagooc").val());
	var observacion=$("#observacion").val();
	var idpedido = $("#idpedido").val();
	var chapa = $("#chapa").val();
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
	var totalventa= $("#totalventa_real").val();
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
	cliente=$("#idcliente").val();
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
	if(tipo == 8){
		$("#rghbtn").hide();
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
	if(tipo == 1){
		if(!fact>0){
			errores=errores+'Debe indicar el numero de factura. \n';
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
			"llevapos"       : llevapos,
			"cambiode"       : cambiopara,
			"observadelivery": obsdel,
			"observacion"    : observacion,
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
						 borra_carrito();
						 <?php $script = "script_central_impresion.php";?>
						if(obj.error == ''){
								document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk='+tipo+'&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
						}else{
							alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');
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
function popupasigna(){
		 $(function mag() {
            $('a[href="#pop1"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function popupasigna2(){
		 $(function mag() {
            $('a[href="#pop2"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function popupasigna3(){
		 $(function mag() {
            $('a[href="#pop3"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function popupasigna4(){
		 $(function mag() {
            $('a[href="#pop4"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function popupasigna5(){
		 $(function mag() {
            $('a[href="#pop5"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function popupasigna6(){
		 $(function mag() {
            $('a[href="#pop6"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function popupasigna7(){
		 $(function mag() {
            $('a[href="#pop7"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });	
}
function busca_cliente(tipopago,idpedido){
		shortcut.remove('space');
		var direccionurl='clientesexistentes2.php';
		var parametros = {
              "id" : 0,
			  "tipopago" : tipopago,
			  "idpedido" : idpedido
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                        //$("#pop1").html("Cargando...");
                },
                success:  function (response) {
						popupasigna();
						$("#pop1").html(response);
						if (document.getElementById('blci')){
							document.getElementById('blci').focus();
						}
                }
        });	
}
function cobranza(cual,idpedido,pri){
	    if (isNaN(cual)){
			cual='';
		}
	    if (isNaN(idpedido)){
			idpedido=0;
		}
		var totzonas = $("#totzonas").val();
		//Traemos el total deventa acumulada
		var tprod=parseInt(($("#totalventa").val()));
		var tvreal = parseInt($("#totalventa_real").val());
		
		// si no hay pedido
		if(idpedido == 0 && pri == 1){
			$("#totalventa_real").val(tprod);
			tvreal = tprod;
		}
		
		if (tprod > 0){
			
			var domicilio=($("#ubica").val());
			var direccionurl='cobramini.php';
			var parametros = {
				  "idpedido" : idpedido,
				  "tipocobro": cual,
				  "domicilio": <?php echo intval($_COOKIE['dom_deliv']);?>

		   };
		   $.ajax({
					data:  parametros,
					url:   direccionurl,
					type:  'post',
					beforeSend: function () {
						   // $("#pop4").html("Cargando...");
					},
					success:  function (response) {
						var primero = $("#pop1").is(":visible");
						var segundo = $("#pop2").is(":visible");
						var tercero = $("#pop3").is(":visible");
						var cuarto = $("#pop4").is(":visible");
						var sexto = $("#pop6").is(":visible");
						if (primero==true){
							$("#pop1").html(response);
						}
						if (segundo==true){
							$("#pop2").html(response);
						}
						if (tercero==true){
							$("#pop3").html(response);
						}
						if (cuarto==true){
							$("#pop4").html(response);
						}
						if (sexto==true){
							$("#pop6").html(response);
						}
								
							//$("#pop4").html(response);
							if (cual !=''){
								$("#ocultoad").hide();
								$("#oculto1").show();
								$("#oculto2").show();
								$("#warpago").hide();
								$("#cuerpo3").show();
								$("#mediopagooc").val(cual);
								var totalventa = parseInt($("#totalventa").val());
								$("#montorecibido").val(totalventa);
								$("#montorecibido").select();
								if (cual==1){
									//EF
									$("#adicional1").hide();
									$("#adicional2").hide();



								} else {
									if (cual!=7){
										//$("#montorecibido").val(totalventa);
										$("#adicional1").show();
										$("#adicional2").show();
									} else {
										$("#adicional1").hide();
										$("#adicional2").hide();
										
									}
									if (cual==5){
									//CHEQUE
										$("#warpago").show();

									}
									if (cual==8){
											//adherente
											$("#adicional1").hide();
											$("#adicional2").hide();
											$("#oculto1").hide();
											$("#oculto2").hide();
											$("#ocultoad").show();
											$("#cuerpo3").hide();
											$("#adherentebus").focus();
									}
								} 
							}
						
						esplitear($("#tipozona").val());
						$("#montorecibido").select();
					}
			});	
		} else {
			
			alertar('ATENCION: Algo salio mal.','Debe agregar al menos un producto al carrito.','error','Lo entiendo!');
		}
		
		
} 
	//ADHERENTES
function carga_adherentes(texto){
	if(texto != ''){
		shortcut.remove('space');
	}
	var direccionurl='mini_buscar_adherente.php';
	var parametros = {
              
			  "palabra" : texto
	   };
		$.ajax({
                data:  parametros,
                url:  direccionurl,
                type:  'post',
                beforeSend: function () {
					if($("#cargaad").html() != 'Cargando...'){
                      $("#cargaad").html('Cargando...');
					  $("#adherentebus").focus();
					}
                },
                success:  function (response) {
					  $("#adherentebus").focus();
					  $("#cargaad").html(response);
					  
                }
        });	
	
	
	
}
function carga_adherentes2(texto){
	if(texto.length > 3){
		carga_adherentes(texto);
		$("#adherentebus").focus();
	}
}
function este(valor,servcomb){
	var direccionurl='mini_buscar_adherente.php';
	var parametros = {
              
			  "idadhiere"    : valor,
			  "idservcombox" : servcomb
	   };
		$.ajax({
                data:  parametros,
                url:  direccionurl,
                type:  'post',
                beforeSend: function () {
                      
                },
                success:  function (response) {
					
						$("#cargaad").html(response);
						
                }
        });	
	
	
	
}
function agrega_cliente(tipopago,idpedido){
		var direccionurl='cliente_agrega.php';
		var parametros = {
              "idpedido" : idpedido,
			  "mediopago" : tipopago
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                       // $("#pop2").html("Cargando...");
                },
                success:  function (response) {
						popupasigna2();
						$("#pop2").html(response);
						if (document.getElementById('ruccliente')){
							document.getElementById('ruccliente').focus();
						}
						$("#idpedido").html(idpedido);
                }
        });	
}
function carga_ruc_h(idpedido){
		var vruc = $("#ruccliente").val();
		var tipocobro=$("#mediopagooc").val();
		//alert(tipocobro);
		var parametros = {
                "ruc" : vruc
        };
		$.ajax({
                data:  parametros,
                url:   'ruc_extrae.php',
                type:  'post',
                beforeSend: function () {
					$("#ruccliente").val('Buscando...');
                },
                success:  function (response) {
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
						if(parseInt(idcli)>0){
							nclie(tipocobro,idpedido);
						}
					}else{
						$("#ruccliente").val(vruc);
						$("#nombreclie").val('');
						$("#apellidos").val('');
					}
                }
        });
}
function filtrar_rz(tpago,idpedido){
		var buscar=$("#blci").val();
		var parametros = {
                "bus_rz" : buscar,
				"tpago" : tpago,
				"idpedido" : idpedido 
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
function filtrar_ruc(tpago,idpedido){ 
		var buscar=$("#blci2").val();
		var parametros = {
                "bus_ruc" : buscar,
				"tpago" : tpago,
				"idpedido" : idpedido 
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
function retornar(mediopago,idpedido){
		var cual=mediopago;
	     
		//retorno sin cambios la menu de pago
		var direccionurl='cobramini.php';
		var parametros = {
              "idpedido" : idpedido,
			  "tipocobro" : mediopago
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    if (document.getElementById('pop2')){    
						//$("#pop2").html("Cargando...");
					}
					if (document.getElementById('pop1')){
						//$("#pop1").html("Cargando...");
						
						
					}
                },
                success:  function (response) {
					if (document.getElementById('pop1')){
						$("#pop1").html(response);
						
						
					}
					if (document.getElementById('pop2')){    
						popupasigna2();
						$("#pop2").html(response);
					} 
					//si hay medio pago, mostramos
					if (cual !=''){
						$("#oculto1").show();
						$("#oculto2").show();
						$("#warpago").hide();
						$("#cuerpo3").show();
						//$("#mediopagooc").val(cual);
						//var totalventa = parseInt($("#totalventa").val());
						//$("#montorecibido").val(totalventa);
						if (cual==1){
							//EF
							$("#adicional1").hide();
							$("#adicional2").hide();
						} else {
								//$("#montorecibido").val(totalventa);
								$("#adicional1").show();
								$("#adicional2").show();
								if (cual==8){
								//CHEQUE
									$("#warpago").show();
								}
						} 
                	}
					
                }
        });	
		
		
		
}
function selecciona_cliente(valor,tipocobro,idpedido){
	//tmp del medio pago
	var tmptp='';
	var tmptp=$("#octpp").val();
	if (isNaN(tmptp)){
		//vemos si hay 
		//alert('nan1');
		var tmptp=$("#tipopagoselec").val();
	}
	if (isNaN(tmptp)){
		tmptp=tipocobro;
	}
	//alert('seleclie'+tmptp);
	
	mostrar_cliente(valor,tmptp,idpedido);
	$("#idcliente").val(valor);
	//setTimeout(function(){ cerrar(1); }, 100);
}
function mostrar_cliente(idclie,med,idpedido){
		var parametros = {
				"id"   : idclie
			    
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
					//alert('ok');
					
					recargacliente(idclie,ruc,ruc_dv,razon_social,med,idpedido);
					
		
                }
        });
		
}
function recargacliente(idclie,ruc,ruc_dv,rz,medio,idpedido){
	//alert('si');
	var cual=medio;
	var parametros = {
				"idcliente"   : idclie,
				"razon" : rz,
				"ruc" :ruc,
				"digito" :ruc_dv,
		        "tipocobro"  :medio,
				"idpedido"  :idpedido
        };
		$.ajax({
                data:  parametros,
                url:   'cobramini.php',
                type:  'post',
                beforeSend: function () {
                     //$("#adicio").html('Cargando datos del cliente...');  
                },
                success:  function (response) {
					//alert(response);
					if (document.getElementById('agrega_clie')){
						$("#agrega_clie").html(response); 
						$("#pop1").html(response);
					} else {
						if (document.getElementById('pop1')){
							$("#pop1").html(response); 
						}
					}
					//si hay medio pago, mostramos
					if (cual !=''){
						$("#oculto1").show();
						$("#oculto2").show();
						$("#warpago").hide();
						$("#cuerpo3").show();
						
						$("#mediopagooc").val(medio);
						var totalventa = parseInt($("#totalventa").val());
						$("#montorecibido").val(totalventa);
						if (cual==1){
							//EF
							$("#adicional1").hide();
							$("#adicional2").hide();
						} else {
								//$("#montorecibido").val(totalventa);
								$("#adicional1").show();
								$("#adicional2").show();
								if (cual==8){
								//CHEQUE
									$("#warpago").show();
								}
						} 
                	}
					cerrar(0);
				}
        });	
		
}
function cerrar(n){
	if (n==1){
		 $.magnificPopup.close();
			
	}
}
function nclie(tipocobro,idpedido){
	//alert(tipocobro+'-'+idpedido);
	var errores='';
	var nombres=document.getElementById('nombreclie').value;
	//alert('nclie'+tipocobro);
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
		 var html_old = $("#agrega_clie").html();
		//alert(html_old);
		 var parametros = {
					"n"     : 1,
					"nom"   : nombres,
					"ape"   : apellidos,
					"dc"    : docu,
					"ruc"   : ruc,
					"dire"  : direclie,
					"telfo" : telfo,
			 		"tipocobro" : tipocobro,
					"idpedido" : idpedido
			};
		   $.ajax({
					data:  parametros,
					url:   'cliente_registra.php',
					type:  'post',
					beforeSend: function () {
							$("#agrega_clie").html("<br /><br />Registrando, favor espere...<br /><br />");
					},
					success:  function (response) {
							$("#agrega_clie").html(response);
							if(response == 'duplicado'){
								alertar('ATENCION:','Ya existe un cliente con el ruc seleccionado','error','Lo entiendo!');	
								$("#agrega_clie").html(html_old);
								$("#nombreclie").val(nombres);
								$("#apellidos").val(apellidos);
								$("#ruccliente").val(ruc);
								$("#direccioncliente").val(direclie);
								$("#telefonoclie").val(telfo);
							}else if(response == 'error-ruc'){
								alertar('ATENCION:','El digito verificador del ruc ingresado es incorrecto','error','Lo entiendo!');
								$("#agrega_clie").html(html_old);
								$("#nombreclie").val(nombres);
								$("#apellidos").val(apellidos);
								$("#ruccliente").val(ruc);
								$("#direccioncliente").val(direclie);
								$("#telefonoclie").val(telfo);
								//ACA DEBE VOLVER A MOSTRAR TODO LO ESCRITO
								//alert(html_old);s
							}else{
								
								selecciona_cliente(response,tipocobro,idpedido)
								//setTimeout(function(){ cerrar(1); }, 1000);
							}
					}
			});
	} else {
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
		
	}
	
}
function abrecodbarra(){
	var valor='';
	var parametros = {
                "id" : valor
        };
       $.ajax({
                data:  parametros,
                url:   'busca_produ_barra_nombre.php',
                type:  'post',
                beforeSend: function () {
                     //$("#pop3").html("Cargando Opciones...");
												
                },
                success:  function (response) {
					$("#pop3").html(response);
					popupasigna3();
					
                }
        });
	
	
	
	
}
function abreadherente(){
	var valor='';
	var parametros = {
                "id" : valor
        };
       $.ajax({
                data:  parametros,
                url:   'busca_adherente_cod.php',
                type:  'post',
                beforeSend: function () {
                     //$("#pop3").html("Cargando Opciones...");
												
                },
                success:  function (response) {
					$("#pop7").html(response);
					popupasigna7();
					
                }
        });
	
	
	
	
}
function apretar_combo(id){
		//var html = document.getElementById("prod_"+id).innerHTML;
        var parametros = {
                "id" : id
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas.php',
                type:  'post',
                beforeSend: function () {
                        //$("#prod_"+id).html("Cargando Opciones...");
						$("#lista_prod").html("Cargando Opciones...");
						//$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						//$("#prod_"+id).html(html);
						$("#lista_prod").html(response);
                }
        });
	
}
function agrega_prod_grupo(idprod,idlista){
	var html = $("#prod_"+idprod+'_'+idlista).html();
	var cant = $('cant_'+idprod+'_'+idlista).val();
	var parametros = {
		"idlista" : idlista,
		"idprod" : idprod
	};
	$.ajax({
		data:  parametros,
		url:   'combo_ventas_add.php',
		type:  'post',
		beforeSend: function () {
			//$("#prod_"+idprod+'_'+idlista).html("Cargando Opciones...");
		},
        success:  function (response) {
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
        var parametros = {
                "idprod_princ" : idprod_princ
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
						document.location.href='?cat='+cat;
					}else if(response == 'NOVALIDO'){
						$("#lista_prod").html(html);
						alert("Favor seleccione todos los productos antes de terminar.");
					}else{
						$("#lista_prod").html(response);
					}
                }
        });	
}
function apretar_combinado(prodprinc){
		//var html = document.getElementById("prod_"+id).innerHTML;
        var parametros = {
                "prodprinc" : prodprinc
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_ventas.php',
                type:  'post',
                beforeSend: function () {
                        //$("#prod_"+id).html("Cargando Opciones...");
						$("#lista_prod").html("Cargando Opciones...");
						//$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						//$("#prod_"+id).html(html);
						$("#lista_prod").html(response);
                }
        });
	
}
function agrega_prod_combinado(idproducto_principal,idproducto_partes){
	var html = $("#prod_"+idproducto_principal+'_'+idproducto_partes).html();
	var cant = $('cant_'+idproducto_principal+'_'+idproducto_partes).val();
	var parametros = {
		"prodprinc" : idproducto_principal,
		"prodpart" : idproducto_partes
	};
	$.ajax({
		data:  parametros,
		url:   'combinado_ventas_add.php',
		type:  'post',
		beforeSend: function () {
			//$("#prod_"+idprod+'_'+idlista).html("Cargando Opciones...");
		},
        success:  function (response) {
			//alert(response);
			if(response == 'MAX'){
				$("#grupo_"+idproducto_principal).html('Cantidad Maxima Alcanzada');
			}else if(response == 'LISTO'){
				$("#grupo_"+idproducto_principal).html('Listo!');
			}else{
				$("#prod_"+idproducto_principal+'_'+idproducto_partes).html(html);
				$("#contador_"+idproducto_principal+'_'+idproducto_partes).html(response);
			}
		}
	});
}
function reinicia_combinado(idproducto_principal){
        var parametros = {
                "prodprinc" : idproducto_principal
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_ventas_del.php',
                type:  'post',
                beforeSend: function () {
					//$("#lista_prod").html("Cargando Opciones...");
                },
                success:  function (response) {
					if(response == 'OK'){
						apretar_combinado(idproducto_principal);
					}else{
						$("#lista_prod").html(response);
					}
                }
        });
}
function terminar_combinado(prodprinc,cat){
		var html = $("#lista_prod").html();
        var parametros = {
                "prodprinc" : prodprinc
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_ventas_termina.php',
                type:  'post',
                beforeSend: function () {
					$("#lista_prod").html("Registrando...");
                },
                success:  function (response) {
					if(response == 'OK'){
						document.location.href='?cat='+cat;
					}else if(response == 'NOVALIDO'){
						$("#lista_prod").html(html);
						alert("Favor seleccione todos los productos antes de terminar.");
					}else{
						$("#lista_prod").html(response);
					}
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
function borra_delivery(){
	setCookie('dom_deliv',null,-1);
	document.location.href='gest_ventas_resto_caja.php';
}
function carry_out(){
	var deliv = <?php echo intval($_COOKIE['dom_deliv']); ?>;
	// si hay delivery
	if(deliv > 0){
		if(window.confirm('Se borrara el delivery, esta seguro?')){
			//$("#pop6").hide();
			$("#pop6").html('Borrando Delivery...');
			borra_delivery();	
			//setCookie('dom_deliv',null,-1);
		}else{
			$("#pop6").hide();
			document.location.href='gest_ventas_resto_caja.php';
		}
	}else{
	 var parametros = {
                "idpedido" : 0
        };
       $.ajax({
                data:  parametros,
                url:   'carry_out.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop6").html("Cargando...");
                },
                success:  function (response) {
						popupasigna6();
						$("#pop6").html(response);
                }
        });
	}
}

function registrar_pedido(){
	var razon_social_carry = $("#razon_social_carry").val();
	var ruc_carry = $("#ruc_carry").val();
	var chapa_carry = $("#chapa_carry").val();
	var observacion_carry = $("#observacion_carry").val();
	var valido = 'S';
	var errores = '';
	// validaciones
	if(ruc_carry == ''){
		valido = 'N';
		errores=errores+'Debe completar el ruc. \n';
	}
	if(razon_social_carry == ''){
		valido = 'N';
		errores=errores+'Debe completar la razon social. \n';
	}
	if(chapa_carry == ''){
		valido = 'N';
		errores=errores+'Debe completar el nombre. \n';
	}
	
	if(valido == 'S'){
		var parametros = {
			"razon_social"       : razon_social_carry,
			"ruc"                : ruc_carry,
			"chapa"              : chapa_carry,
			"observacion"        : observacion_carry,
			"mesa"               : 0,
			"canal"              : 1,
			"MM_insert"          : 'form1'
		};
		$.ajax({
			data:  parametros,
			url:   'registrar_pedido.php',
			type:  'post',
			beforeSend: function () {
				$("#regpedido").hide();
				$("#regpedidobox").html('Registrando...');
			},
			success:  function (response) {
				$("#carrito").html(response);
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					borra_carrito();
					if(obj.error == ''){
						 document.body.innerHTML='<meta http-equiv="refresh" content="0; url=gest_ventas_resto_caja.php">';
					}else{
						alertar_redir('NO SE REGISTRO EL PEDIDO',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');
					}
				}else{
					alert(response);
				}
				//alert(response);
	
			}
			
		});
		
	}else{ // if(valido == 'S'){
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
	}
		
}
function cobrar_pedido(id,monto){
		var parametros = {
                "idpedido" : id
        };
       $.ajax({
                data:  parametros,
                url:   'cobramini.php',
                type:  'post',
                beforeSend: function () {
					$("#pop6").html("Cargando...");
                },
                success:  function (response) {
					$("#pop6").html(response);
					$("#totalventa_real").val(monto);
                }
        });	
}
function chau(valor){
	if(window.confirm('Esta seguro que desea borrar el pedido '+valor+'?')){
		if (valor!=''){
			//var parametros='chau='+valor;
			var parametros = {
					"chau" : valor
			};
		   $.ajax({
					data:  parametros,
					url:   'carry_out.php',
					type:  'post',
					beforeSend: function () {
						$("#pop6").html("Cargando...");
					},
					success:  function (response) {
						$("#pop6").html(response);
					}
			});	
			//OpenPage('carry_out.php',parametros,'POST','pop6','pred');
			
		}
	}

}
function obtener_peso(posicion){
		var direccionurl='<?php echo $script_balanza?>';
		var parametros = {
              "id" : 0
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                        //$("#obtenido").html("Cargando...");
					<?php if ($autopeso == 'S') {?>
						$("#obtp_"+posicion).hide();
					<?php }?>
                },
                success:  function (response) {
						$("#cvender_"+posicion).val(response);
						<?php if ($autopeso == 'S') {?>
						$("#cv_"+posicion).click();
						setTimeout(function(){ $("#obtp_"+posicion).show(); }, 2000);
						<?php }?>
                }
        });	
}
function busque_adh(codigoadh){
	var parametros = {
		"cod" : codigoadh
	};
	$.ajax({
		data:  parametros,
		url:   'busca_adherente_cod_res.php',
		type:  'post',
		beforeSend: function () {
			$("#recarga_adh").html("Cargando...");
		},
		success:  function (response) {
			$("#recarga_adh").html(response);
		}
	});	
}
function cambiacheckserv(servicio){
	$("#idservcombox").val(servicio);
}
function reimpimir_comp(id){
    $("#reimprimebox").html('<iframe src="impresor_ticket_reimp.php?idped='+id+'" style="width:310px; height:500px;"></iframe>');		
}
function descuento_asigna(){
	$("#descuento_box").show();
	$("#descuento").focus();
	var vdescuento = $("#descuento").val();
	var vdescuento_motiv = $("#motivodesc").val();
}
function moneda_extrangera(idmoneda){
	var v_totalventa = $("#totalventa").val();
	var parametros = {
		"idmoneda" : idmoneda
	};
	$.ajax({
		data:  parametros,
		url:   'cotizacion_mini.php',
		type:  'post',
		beforeSend: function () {
			$("#monto_extrangero").hide();
			$("#monto_extrangero").val(0);
		},
		success:  function (response) {
			if(response == 'N'){
				$("#monto_extrangero").val(0);
				$("#monto_extrangero").hide();
			}else{
				var vextrangero=parseInt(v_totalventa)/parseInt(response);
				//vextrangero = parseFloat(vextrangero).toFixed(2);
				vextrangero = vextrangero.toFixed(2);
				$("#monto_extrangero").val(vextrangero);
				$("#monto_extrangero").show();
			}

		}
	});	
}
function transfer_mesa(idpedido){
	 var parametros = {
                "idpedido" : idpedido
        };
       $.ajax({
                data:  parametros,
                url:   'transfer_mesa.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop6").html("Cargando...");
                },
                success:  function (response) {
						popupasigna6();
						$("#pop6").html(response);
                }
        });
}
function transferir_mesa(idpedido){
	var idmesa_destino = $("#idmesa_destino").val();
	 var parametros = {
			"idpedido"       : idpedido,
			"idmesa_destino" : idmesa_destino
        };
       $.ajax({
                data:  parametros,
                url:   'transferir_mesa.php',
                type:  'post',
                beforeSend: function () {
					$("#pop6").html("Cargando...");
                },
                success:  function (response) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						if(obj.valido == 'S'){
							$("#pop6").html('Transferencia Exitosa!');
						}else{
							alert(obj.errores);	
							$("#pop6").html(obj.errores);	
						}
					}else{
						alert(response);
						$("#pop6").html(response);	
					}
                }
        });
}
<?php if ($alerta_ventas != '') { ?>
setInterval(function() {
    $('.alerta_recuerda').fadeIn(700).fadeOut(700);
}, 1000);
<?php }?>
</script>
<?php if ($alerta_ventas != '') { ?>
<style>
.alerta_recuerda{
	text-align:center;
	height:25px;
	padding:5px;
	width:90%;
	border:1px solid #000;
	background-color:#FF0;
	color:#F00;
	font-weight:bold;
	font-size:16px;
	margin:0px auto;
}
</style>
<?php }?>
<link rel="stylesheet" href="fonts/ekaruff.css" />
<script>
function inicio(){
	/*
// 60000 = 1 minuto
// 600000 = 10 minutos
// 1200000 = 20 minutos
// 3600000 = 1 hora
	*/
	setInterval('mantiene_session()',600000); // actualizar
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
</script>
</head>
<body bgcolor="#D4D4D4" onLoad="inicio();">
<div class="vta_contenedor">
<div class="vta_izquierda" id="carrito">
	
    <?php require_once("gest_ventas_resto_carrito.php"); ?>
</div>
<div class="vta_derecha">



<div class="productos">
<?php
$ahora = date("Y-m-d H:i:s");
$ahorad = date("Y-m-d");
$ahoratime = date("H:i:s");
$diasemanahoy = strtolower(diasemana(date("Y-m-d")));
$consulta = "
select * , productos_sucursales.precio as p1
from productos 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
where
productos.idprod_serial in 
	(
		select idproducto 
		from promociones
		where
		idproducto is not null
		and estado = 1
		and '$ahora' >= desde
		and '$ahora' <= hasta
		and '$ahoratime' >= hora_activa_desde 
		and '$ahoratime' <= hora_activa_hasta 
		and (
				(
					$diasemanahoy = 'S'
				)
			OR
				(
					CASE WHEN 
						feriados = 'S'
					THEN
						(SELECT idferiado FROM feriados WHERE fecha = '$ahorad' limit 1) 
					ELSE
						'0'
					END > 0
				
				)
			)
	)

and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.idempresa = $idempresa
and productos_sucursales.activo_suc = 1

order by descripcion asc
limit 1
";
//echo $consulta;
$rspromo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tr = $rspromo->RecordCount();

// hay promo
if ($rspromo->fields['idprod_serial'] > 0) {
    $haypromo = "S";
}


$consulta = "
SELECT * 
FROM categorias
where
estado = 1
and idempresa = $idempresa
order by orden asc, nombre asc
";
//echo $consulta;
$rscateg = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalcat = $rscateg->RecordCount();
if ($totalcat <= 8) {
    ?>
<table width="98%" border="1" class="categoria" bgcolor="#FFFFFF">
  <tbody>
    <tr>
    
	  <td align="center" <?php if (intval($_GET['bus']) == 1) { ?>bgcolor="#F8FFCC"<?php } ?>  onClick="document.location.href='?bus=1'"><img src="img/buscar1.png" width="50"  title="B&uacute;squeda"/></td>
	  <td align="center" <?php if (($cat == 0) && (intval($_GET['bus']) == 0) && (intval($_GET['prom']) == 0)) { ?>bgcolor="#F8FFCC"<?php } ?> onClick="document.location.href='?cat=0'"><img src="tablet/gfx/iconos/estrella.fw.png" width="50"  alt="Favorito"/></td>
<?php if ($haypromo == 'S') { ?>
      <td align="center" <?php if (intval($_GET['prom']) == 1) { ?>bgcolor="#F8FFCC"<?php } ?> onClick="document.location.href='?prom=1'"><img src="tablet/gfx/iconos/promo_mini.jpg" width="50"  alt="Promociones"/></td>
<?php } ?>
<?php while (!$rscateg->EOF) {

    $img = "tablet/gfx/iconos/cat_".$rscateg->fields['id_categoria'].".png";
    if (!file_exists($img)) {
        $img = "tablet/gfx/iconos/cat_0.png";
    }

    ?>
      <td align="center" <?php if ($cat == $rscateg->fields['id_categoria']) { ?>bgcolor="#F8FFCC"<?php } ?>  onClick="document.location.href='?cat=<?php echo $rscateg->fields['id_categoria']; ?>'"><img src="<?php echo $img; ?>" width="50" alt="<?php echo $rscateg->fields['nombre']; ?>"/></td>
<?php $rscateg->MoveNext();
}?>
    </tr>
  </tbody>
</table>
<?php } else { ?>
<div style="width:98%; margin:0px auto;">
<img src="img/buscar1.png" width="50"  title="B&uacute;squeda" onClick="document.location.href='?bus=1'" style="float:left;" /><select onChange="document.location.href='?cat='+this.value+'';" style="width:90%; height:60px; font-size:16px;">
  <option value="0">Favoritos</option>
<?php while (!$rscateg->EOF) { ?>
  <option value="<?php echo $rscateg->fields['id_categoria']; ?>" <?php if ($cat == $rscateg->fields['id_categoria']) { ?>selected="selected"<?php } ?>><?php echo capitalizar($rscateg->fields['nombre']); ?></option>
<?php $rscateg->MoveNext();
}?>
</select>

</div>
<?php } ?>
<?php if ($usarbcode == 'S') {?>
<br />
<div id='bcode'>
	<table width="99%;">
		<tr>
			<td width="6%"><img src="img/bar-code.png" width="32" height="32" alt=""/></td>
			<td width="68%"><input type="text" name="bccode" id="bccode"  style="width: 99%; height: 40px;" placeholder="Ingrese codigo de barras "/></td>
			<td width="26%"><input type="text" name="cantcode" id="cantcode" style="width: 99%; height: 40px;" placeholder="Cantidad"/></td>
		</tr>
	</table>
	
</div>
<?php }?>
	<hr />
<div class="lista_prod" id="lista_prod">
<?php
if (intval($_GET['prom']) == 0) {
    if ($busqueda == 0) {
        ?><?php if (intval($cat) > 0 && intval($t) == 0) {
            $consulta = "
SELECT * 
FROM sub_categorias
where
idcategoria = $cat
and estado = 1
and muestrafiltro = 'S'
and idempresa = $idempresa
order by descripcion asc
";
            $rssub = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            while (!$rssub->EOF) {
                ?>
<div id="" class="producto" onClick="filtrar_subcat('<?php echo $cat; ?>','<?php echo $rssub->fields['idsubcate']; ?>');">
   <img src="tablet/gfx/images/filter.png" height="81" width="163" border="0" alt="Filtrar" title="Filtrar" /><br />
   <strong><?php echo $rssub->fields['descripcion']; ?><br />[Filtrar]
    </strong><br />
</div>
<?php
                $rssub->MoveNext();
            }
        }
        ?><?php
        $col = intval(ceil($totalprod / $totalcol));
        $x = 1;

        while (!$rsprod->EOF) {
            $img = "gfx/productos/prod_".$rsprod->fields['idprod_serial'].".jpg";
            if (!file_exists($img)) {
                $img = "gfx/productos/prod_0.jpg";
            }

            ?><div id="prod_<?php echo $rsprod->fields['idprod_serial']; ?>" class="producto" <?php
if ($rsprod->fields['idtipoproducto'] == 1) { // producto
    echo "onClick=\"apretar('".$rsprod->fields['idprod_serial']."',0,0);\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 2) {  // combo
    echo "onClick=\"apretar_combo('".$rsprod->fields['idprod_serial']."');\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 3) {  // combinado simple
    echo "onClick=\"apretar_pizza('".$rsprod->fields['idprod_serial']."');\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 4) {  // combinado extendido
    echo "onClick=\"apretar_combinado('".$rsprod->fields['idprod_serial']."');\" ";
} else { // por defecto producto
    echo "onClick=\"apretar('".$rsprod->fields['idprod_serial']."',0,0);\" ";
}
            ?> ><div class="contador" id="contador_<?php echo $rsprod->fields['idprod_serial']; ?>" ><?php echo intval($rsprod->fields['total']); ?></div>
    <?php if (trim($rsprod->fields['descripcion']) != '') { ?><img src="<?php echo $img ?>" height="81" width="163" border="0" alt="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" title="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" /><br /><?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?><br />Gs. <?php if (trim($rsprod->fields['combinado']) == 'N') { ?><?php echo formatomoneda(trim($rsprod->fields['p1'])); ?><?php } else {?>Precio Variable<?php } ?><input type="hidden" value="<?php echo $rsprod->fields['p1']; ?>" name="precio_<?php echo $rsprod->fields['idprod_serial']; ?>" id="precio_<?php echo $rsprod->fields['idprod_serial']; ?>">
    <br /><?php } ?>
</div>
    <?php $x++;
            $rsprod->MoveNext();
        }?>
<?php /*}*/
    } else { //if ($busqueda==0){

        ?>

    <div align="center">
        <input type="text" name="filtrar" id="filtrar" placeholder="Ingrese producto a buscar" style="height:40px; width:70%;" onKeyUp="filtra(this.value);"  />
    
    </div>
    <br />
    <div id="minicentro" class="">
        
        
        
    </div>
<?php } // if ($busqueda==0){?>
<?php

} else {

    require_once("lista_promo_vta.php");

} // if(intval($_GET['prom']) == 0){?>
</div>

<div class="clear"></div>
<br />
</div>
<div class="clear"></div>
</div>
<div class="clear"></div><br /><br />
</div>
<!-------------------MAGNIFICO!!!------------------------>
<div id="pop1" class="mfp-hide"></div>
<div id="pop2" class="mfp-hide"></div>
<div id="pop3" class="mfp-hide" style="width:400px; height:450px; margin-left:auto; margin-right:auto; background-color:#FFFFFF"></div>
<!------------------CONTENEDOR PARA PAGO X CAJA-------------------------->
<div id="pop4" class="mfp-hide" ><?php require_once('cobramini.php');?></div>
<div id="pop5" class="mfp-hide"  ><?php require_once('gest_buscar_codigos.php');?></div>
<div id="pop6" class="mfp-hide" ><?php require_once('carry_out.php');?></div>
<div id="pop7" class="mfp-hide" ><div id="adh_box">Adherente</div></div>
<script>
function busque(valor){
	
		var cantidad=document.getElementById('cantidad').value;
		
		
		var parametros = {
                "bb" 		: valor,
				"cantidad"	: cantidad
        };
       $.ajax({
                data:  parametros,
                url:   'lgbp.php',
                type:  'post',
                beforeSend: function () {
                    // $("#recarga").html("Cargando Opciones...");
												
                },
                success:  function (response) {
					var res=response.substr(0,2);
				
					if (res=='cp'){
						var idprod=response.split('=');
						seleccionar(idprod[1]);
						//alert(idprod[1]);
						//$("#recarga").html(response);
					} else {
						$("#recarga").html(response);
						
					}
					
                }
        });
		
			
	
	
}
// INICIALIZA LOS ATAJOS DE TECLAS
window.onload=init;
abrecodbarra();
function init() {
	shortcut.add("F4", function() {
		tecla_acciones('F4');
	});
	//Enfoque a codigo barras en ventana ppal
	shortcut.add("F9", function() {
		tecla_acciones('F9');
	});
	//Enfoque a codigo barras en ventana ppal
	shortcut.add("F10", function() {
		tecla_acciones('F10');
	});
	//abre buscador con barcode x defecto (metodo no-tradicional viejo)
	shortcut.add("F8", function() {
		tecla_acciones('F8');
	});
	//Agregar cantidad
	shortcut.add("plus", function() {
		tecla_acciones('plus');
		
	});
	//Enter
	shortcut.add("enter", function() {
		tecla_acciones('enter');
	});
	//Espacio	
	shortcut.add("space", function() {
		tecla_acciones('space');
	});
	

}
// INICIALIZA LOS ATAJOS DE TECLAS
// DEFINE LAS ACCIONES DE CADA TECLA
function tecla_acciones(tecla){
	if(tecla == 'F4'){
		popupasigna7();
		if($("#totalventa_real").val() > 0){
			document.getElementById('adhbtn').click();
			setTimeout(function(){ enfoque(7); }, 200);
			setTimeout(function(){ enfoque(7); }, 400);
		}
	}
	if(tecla == 'F8'){
		document.getElementById('ocb').click();
		setTimeout(function(){ enfoque(1); }, 200);
	}
	if(tecla == 'F9'){
		setTimeout(function(){ enfoque(4); }, 200);
	}
	if(tecla == 'F10'){
		//setTimeout(function(){ enfoque(4); }, 200); // aqui la funcion para venta rapida efectivo
	}
	if(tecla == 'plus'){
		setTimeout(function(){ enfoque(2); }, 200);
	}
	if(tecla == 'space'){
		var primero = $("#pop1").is(":visible");
		var segundo = $("#pop2").is(":visible");
		var tercero = $("#pop3").is(":visible");
		var cuarto = $("#pop4").is(":visible");
		var cinco=$("#filtrar").is(":visible");
		var seis=$("#pop6").is(":visible");
		var siete=$("#pop7").is(":visible");
		if(primero==false && segundo==false && tercero==false && cuarto==false && cinco==false  && seis==false && siete==false){
			popupasigna5();
			document.getElementById('occ').click();
			setTimeout(function(){ enfoque(3); }, 200);
		} else {
			shortcut.remove("space");
		}
	}
	if(tecla == 'enter'){
		var uno=$("#busqueda").is(":visible");
		var dos=$("#buscador").is(":visible");
		var tres=$("#bccode").is(":visible");
		//para la cantidad en ventana ppal
		var cuatro=$("#cantcode").is(":visible");
		var cinco='';
		var siete = $("#terminaad").is(":visible");
		//Tradicional x F8
		if (uno==true){
			 document.getElementById('busqueda').focus();
		} 
		//Fin tradicional x f8
		//Inserta en carrito x codigo directo x espaciadora
		if (dos==true){
			 var valor=$("#buscador").val();
			 if ((valor!='')){
				 carritocodigo();
			 } 
		
		}
		//Inserta en carrito x codigo de barras nuevo en ventana ppal
		if (tres==true){
				var valor=$("#bccode").val();
				if ((valor!='')){
					carritocodigonew();
			 	} 
		
		}
		//ahora la cantidad para enfoqu solamente
		if (tres==true && cuatro==true && dos==false && uno==false && siete == false){
			$("#bccode").focus();
		}
		if(siete == true){
			$("#rghbtn").click();
		}
	}
		
}

//Enfoques
function enfoque(cual){
	var uno='';
	var dos='';
	var tres='';
	var cuatro='';
	var cinco='';
	var siete='';
	if (cual==1){
		uno = $("#busqueda").is(":visible");
		if (uno==true){
			 document.getElementById('busqueda').focus();
		}
	}
	if (cual==2){
		dos = $("#cantcode").is(":visible");
		tres=$("#cantidad").is(":visible");
		//Evaluamos primero por el que siempre va estar visible
		if ((dos==true) && (tres==false)){
			
			document.getElementById('cantcode').focus();
		}
		//si ambos estan visibles, se abro por f8
		if ((dos==true) && (tres==true)) {
			document.getElementById('cantcode').value='';
			document.getElementById('cantidad').focus();
		} 
			
		
	}
	if (cual==3){
		document.getElementById('buscador').focus();
	}
	if (cual==4){
		document.getElementById('bccode').focus();
	}
	if(cual==7){
		document.getElementById('busqueda_adhb').focus();
	}
}
//Buscador x F8
function abrebusca(){
	popupasigna5();
}

<?php if ($busqueda == 1) {?>
document.getElementById('filtrar').focus();

<?php } ?>
function filtra(valor){
	if (valor!=''){
		shortcut.remove('space');
		if (document.getElementById('minicentro')){	
			
			var parametros = {
					"bb" 		: valor
			};
		   $.ajax({
					data:  parametros,
					url:   'filtromini.php',
					type:  'post',
					beforeSend: function () {
						 $("#minicentro").html("....");
													
					},
					success:  function (response) {
						
							$("#minicentro").html(response);
							
						
						
					}
			});
			
		
		}
	} else {
		$("#minicentro").html('');
		
		
	}
}
</script>
<!-------------------MAGNIFICO!!!------------------------>
</body>
</html>