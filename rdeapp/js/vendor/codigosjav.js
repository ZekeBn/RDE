
// JavaScript Document
function CrearXMLHttp(){
	XMLHTTP=false;
	if(window.XMLHttpRequest){
		return new XMLHttpRequest();
	}else if(window.ActiveXObject){
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
}
function OpenPage(url,variables,metodo,id,loading){
if(loading == 'pred'){
	var loading ="<div style='background-color:#FFFFFF;font-weight:bold; width:80px; color:#610B21;'><img src='img/iconos/cargando.gif' width='32' height='32' /><br /> Espere por favor</div>";
}else{
	if(loading == 'img'){
		
		var loading ="<div style='background-color:#FFFFFF;font-weight:bold; width:80px; color:#610B21;'><img src='img/iconos/cargando.gif' width='32' height='32' /><br /> Espere por favor</div>";
	} else {
		var loading = loading;
	}
}
 req=CrearXMLHttp();
                if(req){
                               req.onreadystatechange = function() { manejador(id); }; 
                               if(metodo == 'POST'){
                                               req.open("POST",url,true);
                                               req.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
                        req.send(variables);
                               }else if(metodo == 'FILE'){
                                               req.open("POST",url,true);
                                               req.setRequestHeader("Content-Type","multipart/form-data; charset=UTF-8");
                        req.send(variables);
                               }else{    
                                               req.open("GET",url+'?'+variables,true);
                                               req.send(null);
                               }
               // toggle(loading); // ojo aqui
                                                  document.getElementById(id).innerHTML=loading;
                }
}

function DescPage(url,variables,metodo,id,loading){
if(loading == 'pred'){
	var loading = '<div style="background-color:#FF0000; font-weight:bold; width:80px; color:#FFFFFF;">Cargando...</div>';
}else{
	if(loading == 'img'){
		
		var loading ="<div style='background-color:#FFFFFF;font-weight:bold; width:80px; color:#610B21;'><img src='img/loader2.gif' width='100' height='20' /><br /> Espere por favor</div>";
	} else {
		var loading = loading;
	}
}
	req=CrearXMLHttp();
	if(req){
		req.onreadystatechange = function() { manejador(id); }; 
		if(metodo == 'POST'){
			req.open("POST",url,true);
			req.setRequestHeader("Content-type: application/octet-stream;","Content-Disposition: attachment; filename=paises.xls","Pragma: no-cache; Expires: 0");
	        req.send(variables);
		}else{	
			req.open("GET",url+'?'+variables,true);
			req.send(null);
		}
               // toggle(loading); // ojo aqui
			   document.getElementById(id).innerHTML=loading;
	}
}
function manejador(id){
	if(req.readyState == 4){
		if(req.status == 200){
                       // toggle(loading); // ojo aca
			document.getElementById(id).innerHTML=req.responseText;
		}else{
			//alert("Error"+req.statusText)
			alert("Error: es posible que tu navegador no sea compatible con las funciones de esta pagina, proba ingresando de nuevo desde Google Chrome.");
		}
	}
}
//Interactuar
function TraeDatoAJAX(url,variables,metodo){
	
	req=CrearXMLHttp();
	if(req){
		req.onreadystatechange = function() { manejador2(); }; 
		if(metodo == 'POST'){
			req.open("POST",url,true);
			req.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
	        req.send(variables);
			
		}else{	
			req.open("GET",url+'?'+variables,true);
			req.send(null);
		}
               // toggle(loading); // ojo aqui
			   return req.responseText;
	}
}

