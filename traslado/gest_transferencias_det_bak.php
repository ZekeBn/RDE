<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_traslados.php");
require_once("includes/funciones_stock.php");
require_once("includes/funciones_produccion.php");

$idtanda = intval($_GET['id']);


// busca en preferencias si quiere validar o no el disponible de stock
$consulta = "
SELECT 	traslado_nostock FROM preferencias where idempresa = $idempresa
";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rspref->fields['traslado_nostock'] == 2) {
    $valida_stock = "S";
} else {
    $valida_stock = "N";
}



//Buscamos tanda activa
$buscar = "select * from gest_transferencias where estado=1 and idtanda = $idtanda";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtanda = intval($rstanda->fields['idtanda']);
$fecha_transferencia = $rstanda->fields['fecha_transferencia'];
$idpedidorepo = $rstanda->fields['idpedidorepo'];
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
    exit;
}

// valida deposito de transito
$consulta = "
select * from gest_depositos where tiposala = 3 order by iddeposito asc limit 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito_transito = intval($rstran->fields['iddeposito']);
if ($iddeposito_transito == 0) {
    echo "Deposito de transito inexistente.";
    exit;
}

// datos de transferencia
$estado = intval($rstanda->fields['estado']);
$origen = intval($rstanda->fields['origen']);
$destino = intval($rstanda->fields['destino']);
if ($rstanda->fields['fecha_transferencia'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fecha_transferencia']));
}

// descripcion de depositos
$buscar = "select *,
(Select descripcion as origen from gest_depositos where iddeposito=$origen) as origen,
(Select descripcion as dst from gest_depositos where iddeposito=$destino) as destino
from gest_depositos
limit 1
";
$rsdd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$orichar = trim($rsdd->fields['origen']);
$deschar = trim($rsdd->fields['destino']);







// trae los depositos para origen y destino
$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where usuarios.idempresa=$idempresa and gest_depositos.idempresa=$idempresa 
and tiposala <> 3
order by descripcion ASC ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado
where usuarios.idempresa=$idempresa and gest_depositos.idempresa=$idempresa 
and tiposala <> 3
order by descripcion ASC";
$rsd2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



//Post Final
if (isset($_POST['ter']) && ($_POST['ter']) != '') {
    $tfin = $idtanda;
    $valido = "S";
    $errores = "";

    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());


    // parametros para validar
    $parametros_array = [
        'iddeposito_origen' => $origen,
        'iddeposito_destino' => $destino,
        'fecha_traslado' => $fecha_transferencia,
        'generado_por' => $idusu,
        'detalle' => '',
        'detalle_precargado' => 'S',
        'idtanda' => $idtanda

    ];


    /*$detalle[]=array(
        'idinsumo' => 7,
        'cantidad' => 10,
        'necesidad' => 0,
        'lote' => 770,
        'vencimiento' => '2021-05-01',
    );
    $detalle[]=array(
        'idinsumo' => 15,
        'cantidad' => 8,
        'necesidad' => 0,
        'lote' => 585,
        'vencimiento' => '2021-06-01',
    );*/
    /*$parametros_array=array(
        'iddeposito_origen' => $origen,
        'iddeposito_destino' => $destino,
        'fecha_traslado' => $fecha_transferencia,
        'generado_por' => $idusu,
        'detalle' => $detalle,
        'detalle_precargado' => 'N',
        'idtanda' => $idtanda

    );*/
    //print_r($parametros_array);exit;
    $res = valida_traslado_entre_depositos($parametros_array);
    // si algo no es valido
    if ($res['valido'] != 'S') {
        $errores .= $res['errores'];
        $valido = "N";
    }
    //print_r($res);exit;
    // si todo es valido
    if ($valido == "S") {


        $res = registra_traslado_entre_depositos($parametros_array);
        //print_r($res);exit;

        header("Location: gest_transferencias.php?l=1");
        exit;


    } //if($valido=="S"){

}
// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());
/*
$listot=intval($_GET['l']);
if ($listot==1){
    //traemos la tanda anterior
    $buscar="Select max(idtanda) as mayor
    from gest_transferencias where generado_por=$idusu and estado=2 and idempresa=$idempresa";

    $rsante=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    //echo $buscar;
    $tandante=intval($rsante->fields['mayor']);

    if ($tandante > 0){
        //cabecera p matriz
        $buscar="Select * from gest_transferencias where idtanda=$tandante";
        $rstandacabe=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));


    }


}
*/



?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<?php require("includes/head.php"); ?>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" media="screen" /> 
<script>
function comenzar(){
	//NUeva tandade transferencias
	var fechatr=document.getElementById('fechatrans').value;
	var origen=parseInt(document.getElementById('origen').value);
	var destino=parseInt(document.getElementById('destino').value);
	var errores='';
	if (fechatr==''){
		errores=errores+'Debe indicar fecha para transferencia. \n';
		
	}
	if (origen==0){
		errores=errores+'Debe indicar deposito de origen. \n';
		
	}
	if (destino==0){
		errores=errores+'Debe indicar deposito destino. \n';
		
	}
	if (origen==destino){
		errores=errores+'No se puede mover al mismo lugar. \n'	;
	}
	if (errores==''){
			document.getElementById('comenzart').submit();	
	} else {
		
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
	}
	
	
}
function enviar(){
	//controlamos cantidad a mover
	
	document.getElementById('cambiar').submit();
	
	
}
function buscar(){
	var producto=(document.getElementById('codigop').value);
	var productocod=(document.getElementById('codigoprod').value);
	var errores='';
	
	if (producto !='' || productocod!=''){
		document.getElementById('sc2').submit();
		
	} else {
		errores=errores+'Debe indicar producto a buscar.'	;
		
	}
	if (errores!=''){
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
		
	}
	
}
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
}
function addtmp(posicion,producto,tanda){
	var cantidad=document.getElementById('cantimov_'+posicion).value;
	var insu=producto;
	
	
	var parametros="insu="+insu+'&ca='+cantidad+'&tp=1&id='+tanda;
	enlace='add_tmp_traslado_new.php';
	OpenPage(enlace,parametros,'POST','tmprodusmov','pred');
	//setTimeout(function(){ abrecos(idpsele,cod); }, 500);
	
	
}
/*function addtmp_todo(){
	var totalres = $("#totres").val();
	var i = 1;
	var final = $("#totres").val();
	var insumos_cant = Array();
	var insumos_id = Array();
	var nombre = '';
	if(final > 0){
		while (i < final) {
			nombre = $("#cantimov_"+i).attr("name");
			insumos_cant[i] = $("#cantimov_"+i).val();
			insumos_id[i] = $("#idinsres_"+i).val();
			
			i++;
		}
	}
	insumos_cant.shift();
	insumos_id.shift();
	alert(JSON.stringify(insumos_cant, null, 2));
	alert(JSON.stringify(insumos_id, null, 2));
}*/
function addtmp_todo(tipo){
	//alert($("#tras_insumo").serialize());
   $.ajax({
			data:  $("#tras_insumo").serialize(),
			url:   'add_tmp_traslado_new.php',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
					$("#insumo_box").html("");
					$("#tmprodusmov").html('Cargando...');
			},
			success:  function (response) {
					$("#tmprodusmov").html(response);
					if(tipo == 2){
						$("#codigobarra").focus();
					}else{
						$("#codigoprod").focus();	
					}
			}
	});
	
}
function chau(cual,tanda){
	if (cual !=''){	
		enlace='add_tmp_traslado_new.php';
		var parametros="cual="+cual+'&tp=3&id='+<?php echo $idtanda ?>;
		OpenPage(enlace,parametros,'POST','tmprodusmov','pred');
	}
}
function terminar(tandafin){
	$("#cerrartrans").hide();
	var tfp=document.getElementById('ter').value;
	if (tfp !=''){
		document.getElementById('fin').submit();
	}
	
}
function imprematriz(pregunta_duplicar='S'){
	var texto = document.getElementById("ocidu").value;
	var duplic = 'N';
	if(pregunta_duplicar == 'S'){
		if(window.confirm('Imprimir con duplicado?')){
			var duplic = 'S';	
		}
	}
	var parametros = {
			"tk" : texto,
			"id" : <?php echo $idtanda ?>
	};
   $.ajax({
			data:  parametros,
			url:   'http://localhost/impresorweb/lcorden_compra.php',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
					$("#imprimeoc").html("Enviando impresion...");
			},
			crossDomain: true,
			success:  function (response) {
					//$("#impresion_box").html(response);	
					//si impresion es correcta marcar
					//var str = response;
					//var res = str.substr(0, 18);
					//;
					if(duplic == 'S'){
						imprematriz('N');
					}
					$("#imprimeoc").html('Impresion Enviada!');
					
			}
	});

	
	
	
}
function busca_insumo(valor){
	var n = valor.length;
	//alert(valor);
	if(n > 2){
		$("#codigoprod").val('');
	   var parametros = {
              "codigop" : valor,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_mover_stock_cuadro_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
	}
}
function busca_insumo_cod(){
	   var valor = $("#codigoprod").val();
	  $("#codigop").val(''); 
	   var parametros = {
              "codigoprod" : valor,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_mover_stock_cuadro_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
						$("#codigoprod").val('');
						$(".insu_focus_1").focus();
                }
        });	
}
function busca_insumo_cbar(e){
	var codbar = $("#codigobarra").val();
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		var valor = $("#codigobarra").val();
		$("#codigop").val(''); 
		var parametros = {
		  "codigobarra" : valor,
		  "id" : <?php echo $idtanda ?>
		};
		$.ajax({
			data:  parametros,
			url:   'gest_mover_stock_cuadro_new.php',
			type:  'post',
			beforeSend: function () {
					$("#insumo_box").html("Cargando...");
			},
			success:  function (response) {
					$("#insumo_box").html(response);
					$("#codigobarra").val('');
					$(".insu_focus_1").focus();
			}
		});	
	}
}
function busca_insumo_cod_enter(e){
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		busca_insumo_cod();
	}
}
function agregaitem(e,tipo){
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		
		addtmp_todo(tipo);
	}
}
function busca_insumo_grup(valor){
	  $("#codigoprod").val('');
	  $("#codigop").val(''); 
	   var parametros = {
              "grupo" : valor,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_mover_stock_cuadro_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
}
function recarga_tmp(){
	   var parametros = {
              "tp" : 0,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'add_tmp_traslado_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#tmprodusmov").html("Cargando...");
                },
                success:  function (response) {
						$("#tmprodusmov").html(response);
                }
        });	
}
function genera_auto(){
	if(window.confirm('Se generara automaticamente en base al stock ideal y stock minimo. esta seguro?')){
		document.location.href='gest_transferencias_gen.php?id=<?php echo $idtanda; ?>';
	}
}
function genera_auto_ped(){
	document.location.href='gest_transferencias_gen_ped.php?id=<?php echo $idtanda; ?>';
}
</script>
</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
 <div align="center" >
 <?php require_once("includes/menuarriba.php");?>
</div>

<div class="colcompleto" id="contenedor">
 	<!-- SECCION DONDE COMIENZA TODO -->
    <div align="center">
	<a href="gest_transferencias.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar" style="cursor:pointer" /></a></div>
    <div class="divstd">
	  <span class="resaltaditomenor">Traslado de stock entre depositos<br /></span>
        <?php echo $logppal;?>
    </div>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
    <div class="resumenmini"><h3><br />
        <strong> Eect&uacute;e las transferencias entre los dep&oacute;sitos/salones de venta.
        </strong>
   		<strong>Solo ser&aacute;n mostrados aqu&iacute; los productos que hayan sido asignados a los dep&oacute;sitos primeramente</strong>.<span class="resaltarojomini">Ingresar el c&oacute;digo / nombre del producto no es obligatorio.</span></h3>
        <br />
       
      <form id="comenzart" name="comenzart" action="gest_transferencias_det.php" method="post">
      
        <table width="200" >
     	<tr>
        	<td height="37" align="right" bgcolor="#EBEBEB"><strong>Fecha transferencia</strong></td>
            <td><input type="date" name="fechatrans" id="fechatrans" style="width:99%; height:40px;" required="required" value="<?php echo $fechis?>" disabled="disabled" readonly="readonly"></td>
        </tr>
        <tr>
        	<td height="22" align="left" bgcolor="#EBEBEB"><strong>Origen</strong></td>
            <td align="left" bgcolor="#EBEBEB"><strong>Destino</strong></td>
        </tr>
        <tr>
       	  <td height="48">
           <select name="origen" id="origen" style="height:40px;" required="required" disabled="disabled" readonly="readonly">
          		<option value="0" selected="selected"  >Seleccionar Origen</option>
          			<?php while (!$rsd->EOF) {?>
                		<option value="<?php echo $rsd->fields['iddeposito']?>"
						<?php if ($origen == $rsd->fields['iddeposito']) {?> selected="selected"<?php } ?>>
						<?php echo $rsd->fields['descripcion']?></option>
         			 <?php $rsd->MoveNext();
          			} ?>
         	 </select>
          </td>
        	<td><select name="destino" id="destino" style="height:40px;" required="required"  disabled="disabled" readonly="readonly">
        	  <option value="0" selected="selected" >Seleccionar Destino</option>
        	  <?php while (!$rsd2->EOF) {?>
        	  <option value="<?php echo $rsd2->fields['iddeposito']?>" <?php if ($destino == $rsd2->fields['iddeposito']) {?> selected="selected"<?php } ?>><?php echo $rsd2->fields['descripcion']?></option>
        	  <?php $rsd2->MoveNext();
        	  } ?>
      	  </select></td>
           
        </tr>
      	<tr>
        	<td colspan="2" align="center" id="cerrartrans">
            <?php if ($estado == 1) {?>
            Finalizar la transferencia activa ->
        <a href="javascript:void(0);" onclick="terminar(<?php echo $idtanda?>)" title="Cerrar Transferencia">
        <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
        <img src="img/1495723082_Close.png" width="32" height="32" alt=""/></a>
        	<?php }?>
          </tr>
        </table>
			
      </form>
        
    </div>
    <form id="fin" name="fin" action="gest_transferencias_det.php?id=<?php echo $idtanda ?>" method="post">
    	<input type="hidden" name="ter" id="ter" value="<?php echo $idtanda?>"  />
    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
    </form>
   	<br />
    <?php if ($estado == 1) {?>
    <div id="productos" align="center">
   		<span class="resaltaditomenor"><?php echo $orichar ?> -> <?php echo $deschar?></span>
    	<br />
        <form id="sc2" name="sc2" action="gest_transferencias_det.php?id=<?php echo $idtanda ?>" method="post" >
        <table width="400">

        	   <tr>
        	     <td colspan="5" align="center"><input type="button" name="button" id="button" value="Generacion Automatica" onmouseup="genera_auto();" />
       	         <input type="button" name="button2" id="button2" value="Generar en base a Pedido" onmouseup="genera_auto_ped();" /></td>
      	     </tr>
        	   <td><input type="text" name="codigoprod" id="codigoprod" style="width:99%; height:40px;" value="<?php echo htmlentities($_POST['codigoprod']);?>" placeholder="codigo" onkeyup="busca_insumo_cod_enter(event);"   /></td>
        		<td height="28" colspan="2"><input type="text" name="codigop" id="codigop" style="width:99%; height:40px;" value="<?php if ($_POST['codigoprod'] == '') {
        		    echo htmlentities($_POST['codigop']);
        		} ?>" placeholder="Ingrese producto a buscar" onkeyup="busca_insumo(this.value);"   />
                </td>
        		<td width="26"><select name="grupo" required="required" id="grupo" style="width:90%; height:40px;" onchange="busca_insumo_grup(this.value);">
                <option value="0" selected="selected">Seleccionar</option>
                <?php
                $buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
        $gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        while (!$gr->EOF) {?>
                <option value="<?php echo $gr->fields['idgrupoinsu']?>" <?php if ($gr->fields['idgrupoinsu'] == $_GET['gr']) { ?>selected="selected"<?php } ?>><?php echo trim($gr->fields['nombre']) ?></option>
                <?php $gr->MoveNext();
        }?>
              </select></td>	
        		<td width="26"><a href="javascript:void(0);" onclick="busca_insumo_cod();"><img src="img/buscar.png" width="32" height="32" alt=""/></a>
                	
                </td>
        </tr>
         <tr>
                        <td colspan="5"><input type="text" name="codigobarra" id="codigobarra" style="width:99%; height:40px;" value="<?php echo htmlentities($_POST['codigobar']);?>" placeholder="codigo barras" onkeyup="busca_insumo_cbar(event);"   /></td>
                </td>
         </tr>
        </table>
    	</form>
    </div>
     <br />
     
     <?php } ?>
     <div align="center"><?php echo $errorcantidaad;?></div>
    	<div align="center" id="insumo_box"></div>
        
    <div id="tmprodusmov" align="center">
    <?php require_once('add_tmp_traslado_new.php');?>
    
    </div>
    
    <?php


$tanda = $tandante;
if ($tanda > 0) {
    //traemos la tanda anterior
    $buscar = "Select *,(select usuario from usuarios where idusu=gest_transferencias.generado_por) as responsable,
	(Select descripcion from gest_depositos where iddeposito=gest_transferencias.origen) as origenc,
	(Select descripcion from gest_depositos where iddeposito=gest_transferencias.destino) as destinoc
	 from gest_transferencias where idtanda=$tanda";
    $rscabe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $fechatrans = date("d/m/Y", strtotime($rscabe->fields['fecha_transferencia']));

    $idgenera = intval($rscabe->fields['generado_por']);
    $origen = trim($rscabe->fields['origenc']);
    $destino = trim($rscabe->fields['destinoc']);
    $dst = intval($rscabe->fields['destino']);
    $or = intval($rscabe->fields['origen']);
    $responsable = ($rscabe->fields['responsable']);

    //cuerpo
    $buscar = "select *,(select descripcion from insumos_lista where idinsumo=gest_depositos_mov.idproducto) as descripcion
	 from gest_depositos_mov where idtanda=$tanda and idempresa=$idempresa
	 order by descripcion asc";
    $rscuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if ($mostrarcosto == 'S') {
        $cabezacosto = '|Costo Gs';
        $cabezasub = '|Subtotal Gs';

    }

    $texto = "********************************************************************************
  $nombreempresa - Traslado N&deg; $tanda 
********************************************************************************
Fecha Traslado   : $fechatrans
Deposito Origen  : $origen
Deposito Destino : $destino
--------------------------------------------------------------------------------
Codigo |Producto               |Cantidad     $cabezacosto        $cabezasub
--------------------------------------------------------------------------------
";
    $to = 0;
    while (!$rscuerpo->EOF) {
        //Buscamos el precio de compras
        $pp = antisqlinyeccion($rscuerpo->fields['idproducto'], 'texto');

        $buscar = "Select costogs from gest_depositos_stock
		 where iddeposito=$dst and idproducto=$pp and disponible > 0 order by idseriecostos asc";
        $buscar = "Select precio_costo as costogs from costo_productos
             where id_producto=$pp and precio_costo > 0 order by idseriepkcos desc limit 1";
        $rscos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $costo = floatval($rscos->fields['costogs']);


        $subt = floatval($rscuerpo->fields['cantidad']) * $costo;
        $to = $to + $subt;
        $texto .= agregaespacio($rscuerpo->fields['idproducto'], 7)."|".agregaespacio($rscuerpo->fields['descripcion'], 23)."|".agregaespacio(formatomoneda($rscuerpo->fields['cantidad'], 4, 'N'), 13);
        if ($mostrarcosto == 'S') {
            $texto .= "|".agregaespacio(formatomoneda($costo), 16)."|".agregaespacio(formatomoneda($subt), 16);

        }
        $texto .= "".$saltolinea;
        $rscuerpo->MoveNext();
    }
    $texto .= "------------------------------------------------------------------------";
    if ($mostrarcosto == 'S') {

        $texto = $texto."	
Total Enviado Gs: ".formatomoneda($to);
    }
    $texto .= "	
Encargado Compras:..................... Firma:.................................
Recibido por: ......................... Firma: ................................
Observaciones: ................................................................
Responsable :".$responsable." Impreso el  :".date("d/m/Y H:i:s")."
";
    /*Fecha: ....../...../.....               Hora: ..... : .....*/
    $ah = date("YmdHis");
    $hh = rand();

    $final = $texto;

    $textooc = $final;
    ?>
<div style="width:500px; margin:0px auto;" id="imprimeoc"> <strong>Traslado:</strong><br /><pre><?php echo $textooc; ?></pre></div>
    <textarea name="ocidu" id="ocidu" style="display:none; width:800px; height:500px;"><?php echo $textooc; ?><?php //echo trim($textooc);?></textarea>
<?php } ?> 





</div> 
<!-- contenedor -->

            
<div id="pop1" class="mfp-hide" style="background-color:#F9F7F7; width:800px; height:auto; margin-left:auto; margin-right:auto;">
</div>
<script>
function registrar_cambio_cant(unicaser){
	var cantidad_modif = $("#cantidad_modif").val();
	var lote_modif = $("#lote_modif").val();
	var vto_modif = $("#vto_modif").val();
	
	 var parametros = {
                "id"         : unicaser,
				"MM_update"  : "form1",
				"cantidad"   : cantidad_modif,
				"lote"  	 : lote_modif,
				"vto"  		 : vto_modif
        };
       $.ajax({
                data:  parametros,
                url:   'traslado_edit_cant.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("<br /><br /><br />Registrando...<br /><br /><br />");
                },
                success:  function (response) {
						//$("#pop1").html(response);
						if(response == 'OK'){
							document.location.href='gest_transferencias_det.php?id=<?php echo $idtanda ?>';
						}else{
							$("#pop1").html(response);
						}
                }
        });
}
function asignardt(cual){
	
	 var parametros = {
                "id" : cual
        };
       $.ajax({
                data:  parametros,
                url:   'traslado_edit_cant.php',
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

$(function mag() {
	$('a[href="#login-popup"]').magnificPopup({
		type:'inline',
		midClick: false,
		closeOnBgClick: false
	});
	
}); 


</script>
<script src="js/jquery.magnific-popup.min.js"></script>
 	 
   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>


