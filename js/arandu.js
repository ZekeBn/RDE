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
	var loading ="<div align='center'><img src='img/cargando.gif' width='32' height='32' /></div>";
}else{
	if(loading == 'img'){
		
		var loading ="<div align='center' style='background-color:#FFFFFF;font-weight:bold; width:80px; color:#610B21;'><img src='img/cargando.gif' width='32' height='32' /><br /> Espere por favor</div>";
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
			// datepicker
			if (!Modernizr.inputtypes.date) {
				$(function() {
					$('input[type=date]').datepicker({dateFormat: 'yy-mm-dd'});
				});
			}
		}else{
			//alert("Error"+req.statusText)
			alert("Error: es posible que tu navegador no sea compatible con las funciones de esta pagina, proba ingresando desde un navegador actualizado.");
		}
	}
}

function sf(ID){
	document.getElementById(ID).focus();
}

function validar(e,tipo) { // 1
    tecla = (document.all) ? e.keyCode : e.which; // 2
    if (tecla==8) return true; // 3
    if (tecla==0) return true;
	if(tipo == 'numero'){
    		patron = /\d/; // Solo acepta números // 4
		//patron = /[1-9\t\s]/;
	}
	if(tipo == 'letra'){
		patron =/[A-Za-z\s]/; // Acepta solo letras, pero NO acepta las letras ñ y Ñ
	}	
	if(tipo == 'numeroyletra'){
		patron = /\w/; // Acepta números y letras sin simbolos ni ñ
	}
	if(tipo == 'numeroyletrayntilde'){
		patron =/[1-9A-Za-zñÑ\s]/; // Acepta solo letras, y SI acepta tambien las letras ñ y Ñ
	}
	if(tipo == 'sinnumero'){
		patron = /\D/; // No acepta números pero acepta cualquier letra o simbolo
	}
/*
Otros elementos a tener en cuenta son:
\d un dígito. Equivale a [0-9]
\D cualquier caracter que no sea un dígito.
\w Cualquier caracter alfanumérico. Equivalente a [a-zA-Z0-9_].
\W cualquier caracter no alfanumérico
\s espacio
\t tabulador
*/
    te = String.fromCharCode(tecla); // 5
    return patron.test(te); // 6
}



/// inicio valida fecha
function validafecha(id,fecha){

// configuracion
var anomax = 2050; // ano maximo incluido el definido aqui
var anomin = 1900; // ano maximo incluido el definido aqui

//definiendo variables
var caja = fecha;
var error = '';

/* 
Validafecha by Omar Albert - creado: 02/10/2009
www.asunfarra.com.py - ositopooh007@gmail.com

Formato: dd/mm/yyyy

Objeto html:
<input type="text" name="Fechita" id="Fechita" onChange="validafecha('Fechita',this.value);">
*/

if (caja){

// valido a menos que no cumpla con 1 de las validaciones de abajo  
valido='SI';

// obteniendo dia,mes,ano
	if ((caja.substr(2,1) == "/") && (caja.substr(5,1) == "/")){      
		for (i=0; i<10; i++){	
			if (((caja.substr(i,1)<"0") || (caja.substr(i,1)>"9")) && (i != 2) && (i != 5)){
				borrar = '';
				break;  
			}  
         }
	}
	
	ano = caja.substr(6,4);
	mes = caja.substr(3,2);
	dia = caja.substr(0,2);
	
	
	// valida que sean numericos
	if(isNaN(Number(dia))){
		valido='NO';
		error = '\n -Dia incorrecto, debe ingresar un dia entre 1 y 31 ';	
	}
	if(isNaN(Number(mes))){ 
		valido='NO';
		error = '\n -Mes incorrecto, debe ingresar un mes entre 1 y 12 ';
	}
	if(isNaN(Number(ano))){ 
		valido='NO';
		error = '\n -Ano incorrecto, debe ingresar un ano entre '+anomin+' y '+anomax+' ';
	}

	
	// valida que los meses sean mayor a cero
	if(Number(dia) <= 0){ 
		valido='NO';
		error = '\n -Dia incorrecto, debe ingresar un dia entre 1 y 31 ';
	}
	// valida que los meses sean mayor a cero
	if(Number(mes) <= 0){ 
		valido='NO';
		error = '\n -Mes incorrecto, debe ingresar un mes entre 1 y 12 ';
	}
	// valida que los meses sean menor a 13
	if(Number(mes) >= 13){ 
		valido='NO';
		error = '\n -Mes incorrecto, debe ingresar un mes entre 1 y 12 ';
	}
	// valida que los meses sean mayor a cero
	if(Number(ano) <= 0){ 
		valido='NO';
		error = '\n -Ano incorrecto, debe ingresar un ano entre '+anomin+' y '+anomax+' ';
	}
	
	////// febrero //////
	if(Number(mes) == 2){ 
		if(ano%4 != 0){ // ano no bisiesto
			if(Number(dia) > 28){
				valido = 'NO';
				error = '\n -Dia incorrecto, febrero tiene 28 dias en anos no bisiestos ';
			}
		}else{ // ano bisiesto
			if(Number(dia) > 29){
				valido = 'NO';
				error = '\n -Dia incorrecto, febrero tiene 29 dias en anos bisiestos ';
			}
		}
	}

	////// abril, junio, septiembre, noviembre //////
	if((Number(mes) == 4) || (Number(mes) == 6) || (Number(mes) == 9) || (Number(mes) == 11)){ 
		if(Number(dia) > 30){
			valido='NO';
			error = '\n -Dia incorrecto, este mes tiene 30 dias ';
		}
	}
	
	////// enero, marzo, mayo, julio, agosto, octubre, diciembre //////
	if((Number(mes) == 1) || (Number(mes) == 3) || (Number(mes) == 5) || (Number(mes) == 6) || (Number(mes) == 7) || (Number(mes) == 8) || (Number(mes) == 10) || (Number(mes) == 12)){ 
		if(Number(dia) > 31){
			valido='NO';
			error = '\n -Dia incorrecto, este mes tiene 31 dias';
		}
	}
	
	
//valida que la fecha no sea menor al año minimo
if(Number(ano) < Number(anomin)){
	valido='NO';
	error = '\n -El ano es menor al '+anomin+' ';
}

//valida que la fecha no sea mayor al año maximo
if(Number(ano) > Number(anomax)){
	valido='NO';
	error = '\n -El ano es mayor al '+anomax+' ';
}

	
// fecha resultante
var fechares=dia+'/'+mes+'/'+ano;

// valida el tamaño de la fecha para eviar ej: 29/02/200010 tome como 29/02/2000
if(fechares != fecha){
	valido="NO";
	error = '\n -El formato de la fecha es incorrecto - formato correcto: dd/mm/aaaa';
}



// si todo es valido deja la fecha como esta, sino borra y alerta sobre el error
if(valido == "SI"){
	document.getElementById(id).value=fechares;
}else{
	alert('La fecha '+fecha+' es incorrecta.'+error);
	document.getElementById(id).value='';
}

}
}
/// fin valida fecha

function valtamano(id,tammin,tammax){
	var tammin = parseInt(tammin);
	var tammax = parseInt(tammax);
	var txt = document.getElementById(id).value;
	var tamano = txt.length;
	var valido = 'SI';
	
	if(tamano > tammax){
		valido = 'NO';
		error = '"texto muy grande" tiene '+tamano+' caracteres.';
	}
	if(tamano < tammin){
		valido = 'NO';
		error = '"texto muy pequeno" tiene solo '+tamano+' caracteres.';
	}
	
	if(valido != 'SI'){
		alert('Error: '+error+'\n -Debe tener un minimo de '+tammin+' y un maximo de '+tammax+' caracteres ');
		resaltacampoerror(id);
	}else{
		desresaltacampoerror(id);
	}	
}

function resaltacampoerror(id){
	document.getElementById(id).style.background='#FFCCCC';
}
function desresaltacampoerror(id){
	document.getElementById(id).style.background='';
}
// si esta Bloqmayus activado
function capLock(e){
        kc=e.keyCode?e.keyCode:e.which;
        sk=e.shiftKey?e.shiftKey:((kc==16)?true:false);
        if(((kc>=65&&kc<=90)&&!sk)||((kc>=97&&kc<=122)&&sk)){
			document.getElementById('caplock').style.display = '';
		}else{ 
			document.getElementById('caplock').style.display = 'none';
		}
}

function validamail(id){
var s = document.getElementById(id).value;
var obj = document.getElementById(id);
var valido = 'NO';

var filter=/^[A-Za-z][A-Za-z0-9_]*@[A-Za-z0-9_]+\.[A-Za-z0-9_.]+[A-za-z]$/;
	if (filter.test(s)){
			valido = 'SI';
			desresaltacampoerror(id);
	}else{
			valido = 'NO';
			resaltacampoerror(id);
			alert('Mail invalido..');
	}
return valido;

}


function abrirpag(url){
	window.open(url);
}	


function ilumina(id){
	document.getElementById(id).style.background='#FFFF00';
}

function desilumina(id){
	document.getElementById(id).style.background='#FFF';
}

function cargaciudad(dpto){
	OpenPage('ciudadselect.php','dpto='+dpto,'POST','ciudadcont','pred');
}
function cargabarrio(ciudad){
	OpenPage('barrioselect.php','ciudad='+ciudad,'POST','barriocont','pred');
}
function get_numbers(txt) {
	var res = txt.replace(/\D/g, '');
	//alert(res);
	return res;
}
/// PARA VARIOS RELOJES
	
function mueveReloj(){
	if((hora == 'a') && (minuto == 'a') && (segundo == 'a')){
		hora = parseInt(horaserver);
		minuto = parseInt(minutoserver);
		segundo = parseInt(segundoserver);
	}else{
		hora = (parseInt(hora));
		minuto = (parseInt(minuto));
		segundo = (parseInt(segundo)+1);
		
		if(segundo == 60){
			segundo = 0;
			minuto = (parseInt(minuto)+1);
		}
		if(minuto == 60){
			segundo = 0;
			minuto = 0;
			hora = (parseInt(hora)+1);
		}
		if(hora == 24){
			hora = 0;
			minuto = 0;
			segundo = 0;
		}
			
	}
	
	
		// inicio formatear hora para que tenga 2 dijitos //
		segundoL='b';
		minutoL='b';
		horaL='b';
		segundoL='b'+segundo;
		minutoL='b'+minuto;
		horaL='b'+hora;
		//alert(segundoL);
	
		if(segundoL.length == 3){
			segundomuestra=''+''+segundo;
		}else{
			segundomuestra='0'+''+segundo;
		}
		if(minutoL.length == 3){
			minutomuestra=''+''+minuto;
		}else{
			minutomuestra='0'+''+minuto;
		}
		if(horaL.length == 3){
			horamuestra=''+''+hora;
		}else{
			horamuestra='0'+''+hora;
		}
// fin formatear hora para que tenga 2 dijitos //
	

	horaImprimible = horamuestra + ":" + minutomuestra + ":" + segundomuestra;
	//horaImprimible = hora + ":" + minuto + ":" + segundo;
	

	document.getElementById('reloj').innerHTML=horaImprimible+' hs.';
	setTimeout("mueveReloj()",1000);
}
function tCount(fieldid,cntfieldid,maxlimit){
	var field = document.getElementById(fieldid);
	var cntfield = document.getElementById(cntfieldid);
	if (field.value.length > maxlimit){ 
		field.value = field.value.substring(0, maxlimit);
	}else{
		cntfield.value = maxlimit - field.value.length;
	}
}
function marcar(source){
	checkboxes=document.getElementsByTagName('input'); //obtenemos todos los controles del tipo Input
	for(i=0;i<checkboxes.length;i++) { //recoremos todos los controles
		if(checkboxes[i].type == "checkbox"){ //solo si es un checkbox entramos
			checkboxes[i].checked=source.checked; //si es un checkbox le damos el valor del checkbox que lo llamÃ³ (Marcar/Desmarcar Todos)
		}
	}
}