function cargar(valor){
	//Se utiliza para enviar form de submit
	$("#ocsalon").val(valor);
	//alert('ss');
	$("#formu01").submit();
}
//----------------CONVERSION JSON------------------------//
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

//-----------------ENFOQUE Y CONTROLES------------------//
function enfocar(eventito,tiempo){
	
	if (eventito==''){
		//evento x defecto
		eventito="codaccede";
	}
	if (tiempo==''){
		tiempo=1000;
	}
	//alert("eventito"+eventito);
	setTimeout(function(e){ $("#"+eventito).focus(); }, tiempo);
}
//--------------TECLAS Y ATAJOS------------------------//
function init() {
	//Enter
	shortcut.add("enter", function() {
		var ads=$("#observacion").is(":focus");
		if (ads==false){
			tecla_acciones('enter');
		}
	});
	shortcut.add("F4", function() {
		tecla_acciones('F4');
	});
	shortcut.add("F6", function() {
		tecla_acciones('F6');
	});
	shortcut.add("F8", function() {
		tecla_acciones('F8');
	});
	shortcut.add("F9", function() {
		tecla_acciones('F9');
	});
	shortcut.add("F2", function() {
		tecla_acciones('F2');
	});
}
function seleccionar(idprdser,valor){
	//Se usa previo al control de teclas de acciones para efectual los cambios deseados
	//colocamos los inp
	
	$("#ocserial").val(idprdser);
	//$("#occantidad").val(valor);
}
function tecla_acciones(tecla){
	if(tecla == 'enter'){
		var valor='';
		var nuevo=$("#comentario").is(":visible");
			if (nuevo==false){
				var prim=$("#codaccede").is(":visible");
				var seg=$("#occantidad").val();
				var ter=$("#mpago").is(":visible");
				var cuar=$("#filtrarprod").is(":focus");
				var cin=$("#listaproduselect").is(":focus");
				//alert('Primero:'+prim);alert('Segundo:'+seg);alert('Tercero:'+ter);alert('Cuarto:'+cuar);
				//segun el enfoque haremos las opciones
				if (prim==true && seg==false && ter==false && cuar==false){
					 controlar_codigo();
					 ocultarerrores(valor);
				} 
				if (seg!='' && prim==false && ter==false && cuar==false){
					//abrir filtro de lista prod
					var ss=$("#ocserial").val();
					$("#enla_"+ss).click();
					enfocar("filtrarprod",1000);
				}
				if (ter==true && seg==false && prim==false && cuar==false){
					 //Vemos si hay monto ingresado, si hay agregamos mpago
					 var mc=$("#abonar").val();
					 var mpago=$("#mpago").val();
					 var idmesa=$("#ocidmesan").val();
					 if (mc != ''){
						 var idatc=$("#ocidatcn").val();
						 //alert(idatc);
						 $("#errormpagoh").html("");
						 //agregar medio
						 agregamedio(idatc,mpago,idmesa);
					 } else{
						 //vemos si hay monto de propina
						  var mpago=$("#mpago").val();
						 var idatc=$("#ocidatcn").val();
						  var prop=$("#propinags").val();
						  if (prop!=''){
							  agregaprop(idatc,prop,idmesa,mpago);
						  } else {
							  $("#errormpagoh").html("Debe ingresar monto abonado.");
							  $("#errormpagoh").show();
							  enfocar("abonar",2000);
						  }
						
					 }
				}
				if (cuar==true && seg!='' && ter==false && prim==false){
					//alert('entra');
					//Por seguridad vemos si se apreto enter, es porque termino de escribir
					if ($("#filtrarprod").is(":focus")){
						$("#listaproduselect").focus();
						var valorse=$("#listaproduselect").val();
							
					} else {
						//Puede que el enfoque ya se encuentre en la lista
						if ($("#listaproduselect").is(":focus")){
							var valorse=$("#listaproduselect").val();
						}
					}
				}
				if (cin==true && seg!='' && ter==false && prim==false && cuar==false){
					//esta seleccionado ya el select 
					
					var idatc=$("#ocidatcmini").val();
					var idmesa=$("#ocidmesamini").val();
					//combinado seleccionado porcion
					var produ=$("#valordelprimero").val();
					if (produ==''){
						var produ=$("#listaproduselect").val();
					}
					var prodppal=$("#ocidprodppal").val();

					//al obtener el id del producto, debemos marcar las porciones seleccionadas
					var parametros = {
						 "idmesa" : idmesa,
						 "idatc" : idatc,
						 "prodppal"	: prodppal,
						 "porcion"	: produ,
						 "agregar"	:1
						};
						$.ajax({
									data:  parametros,
									url:   'mini_mesas_combinado_seleccionados.php',
									type:  'post',
									beforeSend: function () {
											
									},
									success:  function (response) {
										$("#seleccioncombinado").html(response);
										//mostrar();
									} 	
										
						 });

				}
			}
	}
	if (tecla == 'F4'){
		
		$("#pedidocarrito").click();
		//enfocarbusqueda();//devuelve el enfoque a buscar prod
	}
	if (tecla == 'F6'){
		//var vv=$("#bprodu").is(":focus");
		//if (vv==false){
			//no esta enfocado en la cantidad, asumimos preticket
			var idmesa=$("#ocidmesa").val();
			reimprimir_mesa(idmesa);
			enfocarbusqueda();//devuelve el enfoque a buscar prod
		//}
	}
	
	if (tecla == 'F8'){
		$("#cobramesaln").click();
		
	}
	if (tecla == 'F9'){
		//Cerrar mesa
		$("#cierremesa").click();
	}
	if (tecla == 'F2'){
		//codigo de barras
		$("#cbarbtn").click();
	}
}
function enviacarrito(){
	$("#carr1").submit();
}
function marcar(quien){
	$("#valordelprimero").val(quien);
}
//Funcion para dar por finalizado el combinado seleccionado en mesas se cambia la url actual terminar_combinado.php por carrito
function elcombi(prodppal,idatc,idmesa){
	
	var parametros = {
	 "idmesa" : idmesa,
	 "idatc" : idatc,
	 "prodppal"	: prodppal
	};
	$.ajax({
				data:  parametros,
				url:   'carrito.php',
				type:  'post',
				beforeSend: function () {
						
				},
				success:  function (response) {
					//alert(response);
					$("#registracombi").html(response);
					if (response=='LISTO'){
						//Refrescamos el sitio, o mejor el carrito
						//refrescar();
						$('#modpop').modal('hide')  ;
						//Debemos cerrar el popup y actualizar salgo gral de consumo
						actualiza_carrito(idmesa);
						//Inserta Perfecto, ahora vemos el update o refresh de lo consumido
						//actualiza_lista_carrito(idmesa);
						
						
						
					}
				} 	
					
	 });
}
function refrescar(){
	window.open("ventas_salon.php", "_self"); 
	
}
function cerrar_mesa(idmesa,idatc){
	window.open("cerrar_mesa.php?idatc="+idatc+"&idm="+idmesa, "_self");

}
//------------CODIGOS Y CONTROLES DE CODIGOS------------//
function pinmozo(accion){
	
	// M crea campo O borra campo
	if(accion == 'M'){
		
	
		var parametros = {
			 "a" : 'b'
		};
		$.ajax({
					data:  parametros,
					url:   'mozo_clave.php',
					type:  'post',
					beforeSend: function () {
						$("#cuerpopop").html('Cargando...');
						
					},
					success:  function (response) {
						
						$("#cuerpopop").html(response);
						
					} 	

		 });
	}else{
		
		$("#cuerpopop").html('');
	}
	
}
function controlacod(idmesa,usacod,numero){
	//alert(usacod);
	if (usacod==1){
		// crear campo pin
		pinmozo('M');
		$("#ocidmesa").val(idmesa);
		$("#popupab").modal("show");
		setTimeout(function(e){ $("#codaccede").focus(); }, 1200);
		//enfocar();
	} else {
		//mostramos directamente la mesa
		$("#ocidmesa").val(idmesa);
		var mozochar='<?php echo $cajero; ?>';
		var mm='<?php echo $idusu; ?>';
		habilitarmesa(idmesa,mm,mozochar,numero);
		enfocarbusqueda();
		//mostramos uso
		setTimeout(function(e){ mostraruso(); }, 1200);
	}	
}
//---------------- ACCIONES PRODUCTO ---------------//
function accionesprod(idunicotmp,idmesa,idatc,puedecobrar){
	$("#modal_titulo").html("Acciones sobre producto");
	var parametros = {
			 "idtemporal" : idunicotmp,
			 "idmesa"	  : idmesa,
			 "idatc"	  : idatc,
			 "permitecobrar": puedecobrar
	};
	$.ajax({
		data:  parametros,
		url:   'acciones_mesa_productos.php',
		type:  'post',
		beforeSend: function () {
			$("#cuerpopop").html('Cargando...');
			
		},
		success:  function (response) {
			
			$("#cuerpopop").html(response);
			$("#popupab").modal("show");
		} 	

	 });
	
	
}
function reimprime_comanda(idtmpvtares){
	
	var parametros = {
			 "idtemporal" : idtmpvtares
	};
	$.ajax({
		data:  parametros,
		url:   'reimprimir_comandas.php',
		type:  'post',
		beforeSend: function () {
			
			
		},
		success:  function (response) {
			
			$("#acciones_ocultas").html(response);
			//alert(response);
			if (response=='1'){
				$("#noti").show().delay(3000).fadeOut();
			}
			
		} 	

	 });
	
}

//--------------------------------------------------//
//OJO: Hay otra funcion controlacod
function controlar_codigo(){
	setTimeout(function(e){ $("#codaccede").focus(); }, 1200);
	
	var codigo=$("#codaccede").val();
	if (codigo==''){
		$("#errorescodcuerpo").html("Debe indicar codigo de acceso. ");
		$("#errorescod").show();
	} else {
		//cargar ajx
		var idmesa=$("#ocidmesa").val();
		//alert (idmesa);
		var valor='';
		 var parametros = {
                "codigo" : codigo,
				"idmesa" : idmesa
        };
       $.ajax({
                data:  parametros,
                url:   'controlarcodigomozo.php',
                type:  'post',
                beforeSend: function () {
						pinmozo('O'); // borrar campo pin
                },
                success:  function (response) {
					$("#controlcito").html(response);
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						//alert(response);
						if(obj.error == ''){
								pinmozo('O'); 
								//acciones de encontrado
								ocultarerrores(1);
								//habilita div mesa
								var numero=obj.numesa;
								//alert (numero);
								var mm=obj.nomape;
								var idm=obj.codigousumozo;
								habilitarmesa(idmesa,mm,idm,numero);
								//mostramos uso
								
								setTimeout(function(e){ mostraruso(); }, 1200);
						} else {
							//ocultarerrores(valor);
							$("#errorescodcuerpo").html(obj.error);
							$("#errorescod").show();
							pinmozo('M'); 
							
						}
					}else{
						//ocultarerrores(valor);
						$("#errorescodcuerpo").html(response);
						$("#errorescod").show();
						
					}
                }
        });

	}
}
function ocultarerrores(valor){
	//oculta coso de errorescod
	if (valor!=''  ){
		$("#errorescod").slideUp("slow");
	} else {
		$("#errorescod").slideDown("slow");
	}
}
//Habilitar una mesa si se usa mozo o no
function habilitarmesa(idmesa,mozochar,idmozo,numero){
	
	$("#popupab").modal("hide");
	//ocultamos todas las mesas
	$("#mesascomponen").hide();
	$("#clasemesanum").html("<span style='color:black'>"+numero+" mozo: "+mozochar+" </span>");
	//mostramos la mesa seleccionada #73879C
	var conca="";
	//<span style='color:red'>- atendida por "+mozochar+"</span>";
	//alert (conca);
	$("#minitext").html(conca);
	$("#idmozosele").val(idmozo);
	var parametros = {
         "idmesa" : idmesa,
		 "idmozo" : idmozo
    };
	$.ajax({
                data:  parametros,
                url:   'mini_accionesmesas.php',
                type:  'post',
                beforeSend: function () {
						
                },
                success:  function (response) {
					$("#minimesasacciones").html(response);
					mostrar();
				} 	
					
	 });
	
	
}

function mostrar(){
	$("#accionesmesa").show();		
}
//-----------------------PAGOS - MEDIOS - PRODUCTOS - CARRITO - ERRORES     ----------------------------------//
//-----------------Comensales y anexar mesas------------------//
function abrenew(idmesa){
	var adul=$("#comensalesad").val();
	var nin=$("#comensalesni").val();
	var seleccionado=$("#idmesasel").val();
	var nombre_mesa =  $("#nombre_mesa").val();
	//alert(idme);
	//alert(idat);
	//Recargamos el frame y enviamos la info
	var parametros = {
			"idmesa" : idmesa,
			"idatc"	 : 0,
			"rr"	:1,
			"adultos": adul,
			"nin"	 :nin,
			"mesaadd": seleccionado,
			"nombre_mesa" : nombre_mesa
			
	};
	$.ajax({
			data:  parametros,
			url:   'abrir_mesas_mini.php',
			type:  'post',
			beforeSend: function () {
						
	},
		success:  function (response) {
			$("#carrito").html(response);
			//alert(response);
			if (response>0){
				actualiza_lista_carrito(idmesa);
				
			}
			//$("#modal_titulo").html("Administrar Mesa");
			//$("#modal_cuerpo").html('Cambios Guardados!.');
			//actualiza_lista_carrito(idme);
		//
		}
	});
	
	
	
}


function guardar_info(idme,idat){
	var adul=$("#comensalesad").val();
	var nin=$("#comensalesni").val();
	var seleccionado=$("#idmesasel").val();
	var nombre_mesa =  $("#nombre_mesa").val();
	//alert(idme);
	//alert(idat);
	//Recargamos el frame y enviamos la info
	var parametros = {
			"idmesa" : idme,
			"idatc"	 : idat,
			"rr"	:1,
			"adultos": adul,
			"nin"	 :nin,
			"mesaadd": seleccionado,
			"nombre_mesa" : nombre_mesa
			
	};
	$.ajax({
			data:  parametros,
			url:   'adm_mesas_ajx.php',
			type:  'post',
			beforeSend: function () {
						
	},
		success:  function (response) {
			$("#modal_titulo").html("Administrar Mesa");
			$("#modal_cuerpo").html('Cambios Guardados!.');
			actualiza_lista_carrito(idme);
		//
		}
	});
	
}
function anexar_mesas(idmesa,idatc){
	
	var parametros = {
			"idmesa" : idmesa,
			"idatc"	 : idatc
	};
		$.ajax({
			data:  parametros,
			url:   'adm_mesas_ajx.php',
			type:  'post',
			beforeSend: function () {
						
	},
		success:  function (response) {
		$("#modal_titulo").html("Administrar Mesa");	
		$("#modal_cuerpo").html(response);
		$("#modpop").modal("show");
		//
		}
	});
}



//------------------------------------------------------------//
	

function cobrar_mesa(idmesa,idapertura){
	//alert(idapertura);
	var parametros = {
			"idmesa" : idmesa,
		"idatc"	 : idapertura
	};
		$.ajax({
			data:  parametros,
			url:   'mini_cobranzas_mesa.php',
			type:  'post',
			beforeSend: function () {
						
		},
		success:  function (response) {
			$("#modal_titulo").html("Cobrar Mesa");
			$("#modal_cuerpo").html(response);
			$("#modpop").modal("show");
			enfocar("abonar",1000);
		}
	});	
}


function agregamedio(idatc,idformap,idmesa){
	
	//var idmesa=$("#ocmarquita").val(); url:   'ajx_mesacobro.php',
	var montoabona=$("#abonar").val();
	//alert(idatc);
	//alert(montoabona);
	if (montoabona==''){
		$("#errormpagoh").html("<span class='fa fa-warning'>  Atencion: Debe ingresar monto abonar!</span>");
		$("#errorfpago").fadeIn("slow");
	} else {
		$("#errorfpago").fadeOut("slow");
		$("#abonar").focus();
		 var parametros = {
					"idmesa" : idmesa,
					"idatc"	 : idatc,
					"idformapago" : idformap,
					"montoabonado" : montoabona
			};
		   $.ajax({
					data:  parametros,
					url:   'ajx_agrega_eliminapago.php',
					type:  'post',
					beforeSend: function () {
						
					},
					success:  function (response) {
						
						//alert(response);
						$("#pagosagregados").html(response);
						if (response==3){
							//Eliminado correctamente
						}
						if (response==2){
							//Error en monto
							$("#errormpagoh").html("El monto ingresado supera la deuda!. No se permite cobrar");
							$("#errorfpago").show();
							//En el acti recargamos el medio de pago en ajx_mesacobro
							recargarcobranza(idmesa,idatc);
						}
						if (response==1){
							//Insercion correcta
							recargar(idmesa,idatc);
						}
						
						
						
					}
			});	
	}
}
function agregaprop(idatc,propina,idmesa,idformap){

	var propina=$("#propinags").val();
	if (propina==''){
		$("#errormpagoh").html("<span class='fa fa-warning'>  Atencion: Debe ingresar monto de propina!</span>");
		$("#errorfpago").fadeIn("slow");
	} else {
		$("#errorfpago").fadeOut("slow");
		$("#abonar").focus();
		 var parametros = {
					"idmesa" : idmesa,
					"idatc"	 : idatc,
					"idformapago" : idformap,
					"montoabonado" :0,
					"propina"	:propina
			};
		   $.ajax({
					data:  parametros,
					url:   'ajx_agrega_eliminapago.php',
					type:  'post',
					beforeSend: function () {
						
					},
					success:  function (response) {
						
						//alert(response);
						$("#pagosagregados").html(response);
						if (response==3){
							//Eliminado correctamente
						}
						if (response==2){
							//Error en monto
							$("#errormpagoh").html("El monto ingresado supera la deuda!. No se permite cobrar");
							$("#errorfpago").show();
							//En el acti recargamos el medio de pago en ajx_mesacobro
							recargarcobranza(idmesa,idatc);
						}
						if (response==1){
							//Insercion correcta
							recargar(idmesa,idatc);
						}
						
						
						
					}
			});	
	}
}
function recargarcobranza(idmesa,idatc){
	var idmesa=idmesa;
	var montoabona='0';
	var elimina='';
	var parametros = {
					"idmesa" : idmesa,
					"idatc"	 : idatc,
					"montoabonado" : '0',
					"eliminar"	:elimina
	};
	$.ajax({
			data:  parametros,
			url:   'ajx_mesacobro.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#pagosagregados").html(response);
				//recargar(idmesa,idatc);
			}
	});	

	
}
//-------------------------ENTRADA Y FONDO ---------------------------------//
function marcarplato(idvtatmp,idmesa,clas,ubi){
	//alert(ubi);
	var idmesa=idmesa;
	var marcar=idvtatmp;

	var parametros = {
					"idmesa" : idmesa,
					"marcar" : marcar,
					"clase"  :clas
	};
	$.ajax({
			data:  parametros,
			url:   'carrito.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#carrito").html(response);
				if (response=='PM'){
					actualiza_carrito(idmesa);
					
				}
				//recargar(idmesa,idatc);
			}
	});	
}


//-------------------------ENTRADA Y FONDO ---------------------------------//



function recargar(idmesa,idapertura){
	cobrar_mesa(idmesa,idapertura);
	
}
function ocultarerrorpago(){
	if ($("#errorfpago")){
		$("#errorfpago").fadeOut("slow");
	}
	
	
}
function filtrar(texto,idmesa){
	var parametros = {
			"texto" : texto,
			"idmesa": idmesa
	};
	$.ajax({
			data:  parametros,
			url:   'mini_productos_lista.php',
			type:  'post',
			beforeSend: function () {
					
			},
			success:  function (response) {
				$("#listaproductos").html(response);
			}
	});
}

//Funciones de Carrito
//APRETAR AUTO Y SIMPLE
function apretarauto(id,prod1,prod2,quien){
		
		var id=$("#ocserial").val();
		var cant_campo = $("#can_"+id).val();
		$("#occantidad").val(cant_campo);
		var cantidad=$("#occantidad").val();
		// borrar por seguridad
		$("#can_"+id).val('');
		$("#occantidad").val(0);
		$("#ocserial").val(0);
		
		var prod1=0;
		var prod2=0;
		var mozo=$("#idmozosele").val();
		var idmesa=quien;
		//alert(idmesa);
		/*if(cantidad ==''){
			cantidad=1;
		}*/
		if(prod1 > 0){
			var precio = 0;
		}else{
			//Lista de Productos
			//var html = document.getElementById("produlis_"+id).innerHTML;
			var precio = document.getElementById("preciolis_"+id).value;			
		}

		if(cantidad > 0){
			var parametros = {
					"prod" : id,
					"cant" : cantidad,
					"precio" : precio,
					"prod_1" : prod1,
					"prod_2" : prod2,
					"idmesa" : idmesa,
					"idmozosele" : mozo
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
					},
					success:  function (response) {
						//alert(response);
							if(prod1 > 0 && parseInt(response) > 0){
								
								//$("#carrito").html("Actualizando Carrito...");
								$("#can_"+id).val("");
								$("#bprodu").val("");
								actualiza_carrito(quien);
								$("#modpop").modal("hide");
								$("#bprodu").focus();
							}else{
								$("#can_"+id).val("");
								$("#bprodu").val("");
								//Cerramos popup
								$("#modpop").modal("hide");
								actualiza_carrito(quien);
								enfocarbusqueda();
							}
					}
			});
		}
	
}
function apretar(id,prod1,prod2,quien){
	//alert('llega');
		if(prod1 > 0){
			var precio = 0;
		}else{
			//Lista de Productos
			var html = document.getElementById("produlis_"+id).innerHTML;
			var precio = document.getElementById("preciolis_"+id).value;			
		}
        var parametros = {
                "prod" : id,
				"cant" : 1,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2,
				"idmesa" : quien
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
                },
                success:  function (response) {
					//alert(response);
						if(prod1 > 0 && parseInt(response) > 0){
							
							$("#carrito").html("Actualizando Carrito...");
							actualiza_carrito(quien);
						}else{
							actualiza_carrito(quien);
						}
                }
        });
	
}
//APRETAR COMBO,COMBINADO Y LISTA//

function apretar_combinado(idprodser,idmesa){
		//Combinado se permite solo de a 1
		//var canti=$("#can_"+idprodser).val();
		$("#can_"+idprodser).val("1");
		var canti=1;
		if (canti==0){
			canti=1;
		}	
        var parametros = {
                "idprodser" : idprodser,
				"cantidad"	: canti,
				"idmesa" :idmesa
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_seleccion.php',
                type:  'post',
                beforeSend: function () {
					
                },
                success:  function (response) {
					$("#modal_titulo").html("Producto Combinado");
					$("#modal_cuerpo").html(response);
					$("#modpop").modal("show");
								
                }
        });
}
//Filtrar producto combinado_seleccion

function filtrareste(idmesa,minimo,maximo,idcate,idsubcate,idprodserial,idatc){
	//alert(idatc);
	var valorbuscar=$("#filtrarprod").val();
	var parametros = {
		"palabra" : valorbuscar,
		"idmesa"	: idmesa,
		"minimo" :minimo,
		"maximo" :maximo,
		"idcategoria" : idcate,
		"idsubcate"	: idsubcate,
		"idprodserial" :idprodserial,
		"idatc"			: idatc
	};
	$.ajax({
			data:  parametros,
			url:   'mini_filtro_prod.php',
			type:  'post',
			beforeSend: function () {
				$("#productoslistacomb").html("Reca");
			},
			success:  function (response) {
				$("#productoslistacomb").html(response);
			}
	});
}
//Marcar Combinado: reemplaza  a marcar pizza
function marcar_combinado(id,idcomb){
	
	prodmitad = document.getElementById('mitad_'+id);
	
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
			alert('apretando');
			//COMENTAMOS PARA PROBAR DESCOMENTAR PARA ACTIVAR apretar(idcomb,document.getElementById('prod_1').value,document.getElementById('prod_2').value);
		}
		if($('input:checkbox:checked').size() > 2){	
			alert("Error! Solo puedes marcar 2 mitades.");
			$(".productoslistacomb").each(function(){
                $(this).prop('checked',false);
				document.getElementById('prod_1').value=0;
				document.getElementById('prod_2').value=0;
            });
		}
	}
		
}





function apretar_combo(idprodser){
	var canti=$("#can_"+idprodser).val();
	if (canti==0){
		canti=1;
	}	
	var idmesa=$("#idmesa").val();
	//para permitir agregar, debemos poner en un temporal y controlar accion de combos
	 var parametros = {
		"idprodserial" : idprodser,
		"cantidad" : canti,
		"idmesa" : idmesa
	 }
	 $.ajax({
		data:  parametros,
		url:   'combo_ventas.php',
		type:  'post',
		beforeSend: function () {
		
			
			//$("#modal_cuerpo").html("");
		},
		success:  function (response) {
			$("#modal_titulo").html("Seleccionando opciones de combo");
			$("#modal_cuerpo").html(response);
			$("#modpop").modal("show");
			setTimeout(function(e){ enfocar("filtrarprod",1000); }, 5000);
			
		}
	 });					 
}




function actualiza_carrito(idmesa){
		var mozoid=$("#idmozosele").val();
        var parametros = {
                "act" : 'S',
				"idmesa" :idmesa,
				"idmozosele": mozoid
        };
		$.ajax({
                data:  parametros,
                url:   'carrito_central.php',
                type:  'post',
                beforeSend: function () {
                       //$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						$("#carrito").html(response);
                }
        });
}
function actualiza_lista_carrito(idmesa){
		//alert(idmesa);
        var parametros = {
                "act" : 'S',
				"idmesa" :idmesa
        };
		$.ajax({
                data:  parametros,
                url:   'lista_carrito.php',
                type:  'post',
                beforeSend: function () {
                       $("#carrito").html("Actualizando Lista Carrito...");
                },
                success:  function (response) {
						$("#carrito").html(response);
                }
        });
}
//---------------------------BORRAR ITEMS CARRITO - PAGOS---------------------------//
function chaupago(idcobser,idatc,idmesa){
	
	var idmesa=idmesa;
	var montoabona='0';
	var elimina=idcobser;
	var parametros = {
					"idmesa" : idmesa,
					"idatc"	 : idatc,
					"montoabonado" : '0',
					"eliminar"	:elimina
	};
	$.ajax({
			data:  parametros,
			url:   'ajx_agrega_eliminapago.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#pagosagregados").html(response);
				//recargarcobranza(idmesa,idatc);
				recargar(idmesa,idatc);
			}
	});	

}
function chaupropina(unico,idatc,idmesa){
	var idpropina=unico;
	
	var parametros = {
					"idmesa" : idmesa,
					"idatc"	 : idatc,
					"montoabonado" : '0',
					"idpropina"	:idpropina
	};
	$.ajax({
			data:  parametros,
			url:   'ajx_agrega_eliminapago.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#pagosagregados").html(response);
				//recargarcobranza(idmesa,idatc);
				recargar(idmesa,idatc);
			}
	});	
	
}
function borrar_todo(quien){
		var idmesa=quien;
			var parametros = {
                "todo" : 'S',
				"idmesa": quien
			};
	if(window.confirm("Esta seguro que desea borrar TODO?")){	
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							//$("#carrito").html("Borrando...");
					},
					success:  function (response) {
							if(IsJsonString(response)){
							
							var obj = jQuery.parseJSON(response);
			
							if (obj.totalitems > 0){
								actualiza_carrito(idmesa);
								enfocarbusqueda();
							} else {
								
								actualiza_lista_carrito(idmesa);
								enfocarbusqueda();
							}
						}
					}
			});
	}
}
function borrar(idprod,txt){
			var idmesa=$("#ocidmesa").val();
			var parametros = {
                "prod" : idprod,
				"idmesa": idmesa
			};
	if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){	
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							//$("#carrito").html("Actualizando Carrito...");
					},
					success:  function (response) {
						if(IsJsonString(response)){
							
							var obj = jQuery.parseJSON(response);
			
							if (obj.totalitems > 0){
								actualiza_carrito(idmesa);
							} else {
								
								actualiza_lista_carrito(idmesa);
								
							}
						}
					}
			});
	}
}
//------------------------ELIMINAR COMPONENTE COMBINADO EX ----------------------------//
function eliminar(idunico,principal){
	var prodppal=principal;
	var idatc=$("#ocidatcmini").val();
	var idmesa=$("#ocidmesamini").val();
	//al obtener el id del producto, debemos marcar las porciones seleccionadas
	var parametros = {
	 "idmesa" : idmesa,
	 "prodppal":prodppal,
	 "idatc" : idatc,
	 "serial"	: idunico,
	 "eliminar"	:1
	};
	$.ajax({
				data:  parametros,
				url:   'mini_mesas_combinado_seleccionados.php',
				type:  'post',
				beforeSend: function () {

				},
				success:  function (response) {
					$("#seleccioncombinado").html(response);
					
				} 	

	 });
	
	
}




//---------------REIMPRESIONES-------------------------------------------------//
function reimprimir_mesa(id){
	$("#reimprimebox").html('<iframe src="../impresor_ticket_mesa.php?idmesa='+id+'" style="width:310px; height:500px;"></iframe>');
	
}
function enfocarbusqueda(){
	
	setTimeout(function(e){ $("#bprodu").focus(); }, 2000);
	
}
function mostraruso(){
	var visi=$("#divresumen").is(":visible");
	if (visi==true){
		var tt=$("#octiempo").val();//viene de mini_acciones_mesas
		$("#ttiempo").html(tt);
		
	}
	
}
function mudar_mesa(idatc_origen){
	var idmesa_destino = $("#idmesa_destino").val();
	var parametros = {
	 "idmesa_destino" : idmesa_destino,
	 "idatc_origen" : idatc_origen
	};
	$.ajax({
				data:  parametros,
				url:   'mesa_mudar.php',
				type:  'post',
				beforeSend: function () {

				},
				success:  function (response) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						var valido = obj.valido;
						var errores = obj.errores;
						if(valido == 'S'){
						
							$("#modal_titulo").html("Mudar Mesas");
							$("#modal_cuerpo").html('La mesa fue mudada exitosamente.');
							$("#modpop").modal("show");
							setTimeout(function(e){document.location.href='ventas_salon.php'},2000);
						
						}else{
							$("#modal_titulo").html("Mudar Mesas");
							$("#modal_cuerpo").html("<strong>Erroes:</strong><br />"+nl2br(errores));
							$("#modpop").modal("show");

						}
						
					}else{
						alert(response);	
					}
					
				} 	

	 });
}
function nl2br (str, is_xhtml) {
  // *     example 1: nl2br('Kevin\nvan\nZonneveld');
  // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
  // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
  // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
  // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
  // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function codbar_pop(idatc,idmesa){
	var parametros = {
	 "idatc" : idatc,
	 "idmesa" : idmesa
	};
	$.ajax({
		data:  parametros,
		url:   'codbar_mesa.php',
		type:  'post',
		beforeSend: function () {
					$("#modal_titulo").html("Codigo de Barras");
					$("#modal_cuerpo").html("Cargando...");
					$("#modpop").modal("show");		},
		success:  function (response) {
					$("#modal_titulo").html("Codigo de Barras");
					$("#modal_cuerpo").html(response);
					//$("#modpop").modal("show");
					$("#codbar").focus();

		} 	

	});
}
function buscar_producto_codbar(e){
	
	var codbar = $("#codbar").val();
	var idatc = $("#idatc").val();
	var idmesa = $("#idmesa").val();
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		//
		agregar_carrito_codbar(codbar,idatc,idmesa);
	
	}
}
function agregar_carrito_codbar(codbar,idatc,idmesa){
		var cant_cb = $("#cant_cb").val();
		if(!cant_cb > 0){
			cant_cb = 1;	
		}
		var direccionurl='carrito.php';	
		var parametros = {
		  "codbar"   : codbar,
		  "cant" : cant_cb,
		  "idmesa"   : idmesa,
		  "idatc"    : idatc
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			beforeSend: function () {
				$("#busqueda_prod").html('Cargando...');				
			},
			success:  function (response) {
				
				if(response == 'LISTO'){
					$("#busqueda_prod").html('');
					$("#codbar").val('');
					$("#cant_cb").val('1');
					actualiza_carrito(idmesa);
					//$("#codbar").focus();
				}else{
					$("#busqueda_prod").html(response);
					alert(response);
				}
				
			}
		});
}
function agrega_prod_grupo(idprod,idlista){
	//alert(idlista);
	var html = $("#prod_"+idprod+'_'+idlista).html();
	var idmesa = $("#idmesa").val();
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
		var idmesa = $("#idmesa").val();
        var parametros = {
                "idlista" : id,
				"idmesa" : idmesa
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
						$("#modpop").modal("hide");
					}else if(response == 'NOVALIDO'){
						$("#lista_prod").html(html);
						alert("Favor seleccione todos los productos antes de terminar.");
					}else{
						$("#lista_prod").html(response);
					}
                }
        });	
}
function cortesia(idventatmp,idmesa){
        var parametros = {
                "idventatmp" : idventatmp
        };
       $.ajax({
                data:  parametros,
                url:   'cortesia.php',
                type:  'post',
                beforeSend: function () {
					$("#accionesocultas").html('Registrando...');
                },
                success:  function (response) {
					
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						var valido = obj.valido;
						if(valido == 'S'){
							actualiza_lista_carrito(idmesa);
							$('#popupab').modal('hide');
						}else{
							$("#accionesocultas").html(nl2br(obj.errores,true));
							actualiza_lista_carrito(idmesa);
						}
					}else{
						$("#accionesocultas").html(response);
						actualiza_lista_carrito(idmesa);
					}
                }
        });
}
function muestra_rechazos(idtmp,idcab,idmesa,idatc){
        var parametros = {
                "idtmp"  : idtmp,
				"idcab"  : idcab,
				"idmesa" : idmesa,
				"idatc"  : idatc
        };
       $.ajax({
                data:  parametros,
                url:   'rechazo.php',
                type:  'post',
                beforeSend: function () {
					$("#accionesocultas").html('Registrando...');
                },
                success:  function (response) {
					
					$("#accionesocultas").html(response);
                }
        });
}
function rechazar(idtmp,idcab,idmesa,idatc){
		//var codigo_borra = $("#codigo_borra_"+id).val();
		var idmotivorecha = $("#idmotivorecha").val();
		var urlbusca='../borra_prod_ped.php';
	    var parametros = {
                "idventatmp" : idtmp,
				"idtmpventares_cab" : idcab,
				"mesa" : idmesa,
				"rechazo" : 'S',
				"idmotivorecha" : idmotivorecha
        };
		$.ajax({
                data:  parametros,
                url:   urlbusca,
				cache: false,
                type:  'post',
                beforeSend: function () {
					$("#accionesocultas").html('Cargando...');
                },
                success:  function (response) {
					//alert(response);
					if(response == 'OK'){
						$("#accionesocultas").html('');
						actualiza_lista_carrito(idmesa);
						$('#popupab').modal('hide')  ;
					}else{
						actualiza_lista_carrito(idmesa);
						$('#popupab').modal('hide')  ;
						//alert(response);
						//$("#pop1").html(response);
						//actualizar();
					}
                }
        });
}
function muestra_descuento(idtmp,idcab,idmesa,idatc){
        var parametros = {
                "idtmp"  : idtmp,
				"idcab"  : idcab,
				"idmesa" : idmesa,
				"idatc"  : idatc
        };
       $.ajax({
                data:  parametros,
                url:   'pedido_desc_mesa.php',
                type:  'post',
                beforeSend: function () {
					$("#accionesocultas").html('Registrando...');
                },
                success:  function (response) {
					
					$("#accionesocultas").html(response);
                }
        });
}
function registra_descuento(idtmp,idcab,idmesa,idatc){
		var descuento = $("#descuento").val();
        var parametros = {
                "idtmp"     : idtmp,
				"idcab"     : idcab,
				"idmesa"    : idmesa,
				"idatc"     : idatc,
				"descuento" : descuento,
				"MM_update" : "form1"
        };
       $.ajax({
                data:  parametros,
                url:   'pedido_desc_mesa.php',
                type:  'post',
                beforeSend: function () {
					$("#accionesocultas").html('Registrando...');
                },
                success:  function (response) {
					
					
					if(response == 'OK'){
						actualiza_lista_carrito(idmesa);
						$('#popupab').modal('hide')  ;
					}else{
						$("#accionesocultas").html(response);
						
					}
                }
        });
}
function calcula_subtotal(descuento){
	var precio = $("#precio_d").val();
	var cantidad = $("#cantidad_d").val();
	var subtotal_sindesc = precio*cantidad;
	var subtotal = subtotal_sindesc-descuento;
	$("#subtotal").val(subtotal);
}
function calcula_desc(desc_porc){
	var precio = $("#precio_d").val();
	var cantidad = $("#cantidad_d").val();
	var subtotal_sindesc = precio*cantidad;
	var desc_porc_100 = desc_porc/100;
	var descuento = subtotal_sindesc*desc_porc_100;
	$("#descuento").val(descuento);
	calcula_subtotal(descuento);
}
function diplomatico(idatc,accion){
	var idmesa = $("#idmesa").val();
	var parametros = {
	 "idatc" : idatc,
	 "diplo" : accion
	};
	$.ajax({
		data:  parametros,
		url:   '../diplomatico.php',
		type:  'post',
		beforeSend: function () {
	
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				var valido = obj.valido;
				var errores = obj.errores;
				if(valido == 'S'){
				
					//$("#modal_titulo").html("Diplomatico");
					if(accion == 'S'){
						$("#modal_cuerpo").html('Los productos fueron asignados como excentos por diplomatico.');
					}else{
						$("#modal_cuerpo").html('El iva se volvio a agregar a los productos, diplomatico revertido.');	
					}
					$("#modpop").modal("show");
					actualiza_lista_carrito(idmesa);
					//setTimeout(function(e){document.location.href='ventas_salon.php'},2000);
				
				}else{
					//$("#modal_titulo").html("Mudar Mesas");
					$("#modal_cuerpo").html("<strong>Erroes:</strong><br />"+nl2br(errores));
					$("#modpop").modal("show");
	
				}
				
			}else{
				alert(response);	
			}
			
		} 	

	 });
	
}
function mantiene_session(){
	var direccionurl='../mantiene_session.php';	
	var parametros = {
	  "MM_insert" : "form1"
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 10000,  // I chose 10 secs for kicks: 10000
		crossDomain: true,
		beforeSend: function () {
			//$("#busqueda_prod").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(xhr.status === 200){
				//$("#res").html('r: '+response);
				//alert("funciona");
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
//-----------------------------------------------------------------------------//
/*$( document ).ready(function() {
    //console.log( "ready!" );
	init;
	movertabla();
});*/
// INICIALIZA LOS ATAJOS DE TECLAS
window.onload=init;