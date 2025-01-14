<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");


header("location: insumos_lista.php");
exit;

/*

update insumos_lista set idproducto = (select idprod_serial from productos where productos.descripcion = insumos_lista.descripcion)
SELECT * FROM `productos` WHERE idprod_serial not in (select idproducto from insumos_lista)
*/
//Vemos si posee activo el sistema contable o no
$consulta = "Select usa_concepto from preferencias limit 1";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_concepto = $rspref->fields['usa_concepto'];


if (isset($_POST['descripcion']) && ($_POST['descripcion'] != '')) {

    $valido = 'S';
    $errores = "";


    $describe = antisqlinyeccion($_POST['descripcion'], 'text');
    $idmedida = intval($_POST['medida']);
    $grupo = intval($_POST['grupo']);
    //$idcategoria=intval($_POST['categoria']);
    //$idsubcate=intval($_POST['subcatels']);
    $idcategoria = 0;
    $idsubcate = 0;
    $produ = 1;
    $mueve_stock = antisqlinyeccion($_POST['mueve_stock'], "text");
    $idconcepto = antisqlinyeccion($_POST['idconcepto'], "int");


    // si no es no, entonces es si, para evitar hack ;)
    if (trim($_POST['mueve_stock']) != 'N') {
        $mueve_stock = antisqlinyeccion('S', "text");
    }
    $tipoiva = intval($_POST['tipoiva']);

    // validaciones campos obligatorios
    if (intval($_POST['grupo']) == 0) {
        $errores .= "* Debe indicar el grupo del insumo.<br />";
        $valido = 'N';
    }

    if (trim($_POST['descripcion']) == '') {
        $errores .= "* Debe indicar el nombre del insumo.<br />";
        $valido = 'N';
    }
    if (intval($_POST['medida']) == 0) {
        $errores .= "* Debe indicar la medida del insumo.<br />";
        $valido = 'N';
    }
    if (trim($_POST['tipoiva']) == '') {
        $errores .= "* Debe indicar el tipo de iva.<br />";
        $valido = 'N';
    }
    if ($usa_concepto == 'S') {
        if (intval($_POST['idconcepto']) == 0) {
            $errores .= "* Debe indicar el concepto del articulo.<br />";
            $valido = 'N';
        }
    } else {
        $idconcepto = antisqlinyeccion('', "int");
    }


    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $describe and idempresa = $idempresa and borrado = 'N'
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si existe producto
    if ($rsexpr->fields['idprod_serial'] > 0) {
        $errores .= "* Ya existe un producto con este nombre.<br />";
        $errorexiste = $errores;
        $valido = 'N';
    }
    // validar que no hay insumo con el mismo nombre
    $buscar = "Select * from insumos_lista where descripcion=$describe and idempresa = $idempresa and estado = 'A'";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rsb->fields['idinsumo'] > 0) {
        $errores .= "* Ya existe un insumo con este nombre.<br />";
        $valido = 'N';
    }

    // si todo es valido
    if ($valido == 'S') {
        $buscar = "select max(idinsumo) as mayor from insumos_lista";
        $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mayor = intval($rsmayor->fields['mayor']) + 1;

        $insertar = "Insert into insumos_lista
		(idinsumo,idconcepto,descripcion,idcategoria,idsubcate,idmedida,produccion,tipoiva,idempresa,idgrupoinsu,mueve_stock)
		values
		($mayor,$idconcepto,$describe,$idcategoria,$idsubcate,$idmedida,$produ,$tipoiva,$idempresa,$grupo,$mueve_stock)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        //Insertar
        $insertar = "Insert into ingredientes (idinsumo,estado,idempresa) values ($mayor,1,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        header("location: gest_insumos.php");
        exit;

    }

}

//Categorias
$buscar = "Select * from categorias where estado = 1 order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas where estado = 1 order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

// desabilita ingredientes sin produccion
$consulta = "
update ingredientes
set estado = 2
WHERE idinsumo in (
SELECT idinsumo FROM insumos_lista where produccion = 2 and idempresa = $idempresa
)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// habilita ingredientes con produccion
$consulta = "
update ingredientes
set estado = 1
WHERE idinsumo in (
SELECT idinsumo FROM insumos_lista where produccion = 1 and idempresa = $idempresa
)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



$buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<!-------------<link rel="stylesheet" href="css/bootstrap.css" type="text/css" media="screen" /> ---------->
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" media="screen" /> 
<?php require("../includes/head.php"); ?>
<script>
	function recargar(idc){
		var parametros='idc='+idc;
		OpenPage('prod_subcate_define.php',parametros,'POST','subcate','pred');
		
}
function edita(id){
	var descripcion = document.getElementById('descripcion_ed').value;
	var produce = document.getElementById('produce_ed').value;
	var idgrupo= document.getElementById('gru').value;
	var tipoiva = document.getElementById('tipoiva_ed').value;
	var parametros='idi='+id+'&desc='+descripcion+'&pr='+produce+'&tipoiva='+tipoiva+'&gr='+idgrupo+'&edita=s';
	OpenPage('gest_insumo_edita.php',parametros,'POST','editar','pred');
}
function editar(valor){
	if (valor !=''){
		var parametros='idi='+valor;
		OpenPage('gest_insumo_edita.php',parametros,'POST','editar','pred');
	}
}
function alertar(titulo,error,tipo,boton){
		swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
function registra(cual){
	var errores='';
	var nombre=document.getElementById('descripcion').value;
	//var cate=parseInt(document.getElementById('categoria').value);
	//var subcate=0;
	var medida=parseInt(document.getElementById('medida').value);
	/*var produ=parseInt(document.getElementById('produce').value);*/
	var mueve_stock = document.getElementById('mueve_stock').value;
	//alert(mueve_stock);
	
	if (nombre==''){
		errores=errores+'Debe indicar nombre del insumo. \n';
	}
	//if (cate==0){
	//	errores=errores+'Debe indicar categoria del insumo. \n';
	//}	
	//if (document.getElementById('subcatels')){
		//subcate=parseInt(document.getElementById('subcatels').value);
		//if (subcate==0){
		//	errores=errores+'Debe indicar sub categoria del insumo. \n';
		//}
	//}
	if (medida==0){
		errores=errores+'Debe indicar unidad de medida p/ insumo. \n';
	}
	if (mueve_stock != 'N' && mueve_stock != 'S'){
		errores=errores+'Debe indicar si mueve stock. \n';
	}	
	if (errores==''){
		document.getElementById('procesar').submit();
		
	} else {
			alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
		
	}
	
}

		function filtrar(valor){
			var direccionurl='mini_ins.php';
			
				var parametros = {
				  "busca" : valor
				  
				};
				   $.ajax({
					  
							data:  parametros,
							url:   direccionurl,
							type:  'post',
							beforeSend: function () {
									$("#lista_insu").html('Cargando...');
									$("#codins").val('');
							},
							success:  function (response) {
								
									$("#lista_insu").html(response);
									
							}
					});
			
			
		}
		function filtrar_cod(){
			var direccionurl='mini_ins.php';
			var valor = $("#codins").val();
				var parametros = {
				  "cod" : valor
				  
				};
				   $.ajax({
					  
							data:  parametros,
							url:   direccionurl,
							type:  'post',
							beforeSend: function () {
									$("#busq").val('');
									$("#lista_insu").html('Cargando...');
							},
							success:  function (response) {
								
									$("#lista_insu").html(response);
									
							}
					});
			
			
		}
		
	</script>
<script src="js/sweetalert.min.js"></script>
 <link rel="stylesheet" type="text/css" href="css/sweetalert.css">

</head>
<body bgcolor="#FFFFFF">
<?php require("../includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
 <div align="center" >
 <?php require_once("../includes/menuarriba.php");?>
</div>

<div class="colcompleto" id="contenedor">
 	<!-- SECCION DONDE COMIENZA TODO -->
    <br /><br />
    
  <div class="divstd">
   	  <span class="resaltaditomenor">Listado de Insumos</span>
      <br />
  </div>
  <br />
  <!-- <div class="resumenmini">
   						<strong>Genera una lista global
                         de insumos utilizados por el sistema. Adem&aacute;s, debe indicar si el insumo forma parte de un proceso de producci&oacute;n.</strong>   
   						 
   
   
    <span class="divstd"><br />
    <strong>Si desea agregar nuevos grupos para los insumos, haga click en el icono.</strong><br /><a href="gest_grupoinsu.php"><img src="img/nueva_soli.png" width="32" height="32" title="Nuevo Grupo"/></a></span></div>
-->
   <div align="center">
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
     <p align="center">Agregar Insumo:<br />
     </p><br />
     <form  id="procesar" name="procesar" action="gest_insumos.php" method="post">
        <table width="500" height="271">
   			<tr>
            	<td width="91" height="48" align="right" bgcolor="#D0D0D0"><strong>Nombre Insumo</strong></td>
            	<td width="10"></td>
            	<td width="232"><input name="descripcion" type="text" required="required" id="descripcion" style="width:90%; height:40px;" /> </td>
            </tr>
            <tr>
              <td align="right" bgcolor="#D0D0D0"><strong>Medida</strong></td>
              <td></td>
              <td><select name="medida" required="required" id="medida" style="width:90%; height:40px;">
                <option value="0" selected="selected">Seleccionar</option>
                <?php while (!$rsmed->EOF) {?>
                <option value="<?php echo $rsmed->fields['id_medida']?>"><?php echo trim($rsmed->fields['nombre']) ?></option>
                <?php $rsmed->MoveNext();
                }?>
              </select></td>
            </tr>
             <tr>
              <td align="right" bgcolor="#D0D0D0"><strong><a href="gest_grupoinsu.php">[+]</a> Grupo</strong></td>
              <td></td>
              <td><select name="grupo" required="required" id="grupo" style="width:90%; height:40px;">
                <option value="0" selected="selected">Seleccionar</option>
                <?php while (!$gr->EOF) {?>
                <option value="<?php echo $gr->fields['idgrupoinsu']?>"><?php echo trim($gr->fields['nombre']) ?></option>
                <?php $gr->MoveNext();
                }?>
              </select></td>
            </tr>
            <tr>
                <td align="right" bgcolor="#D0D0D0"><strong>Mover Stock:</strong></td>
                <td></td>
                <td>
                <select name="mueve_stock" required="required" id="mueve_stock" style="width:90%; height:40px;" title="Mueve Stock">
                  	<option value="0" selected="selected">Seleccionar</option>
                  	<option value="S" selected="selected">SI</option>
					<option value="N">NO</option>
                </select></td>
           </tr>
<?php if ($usa_concepto == 'S') { ?>
            <tr>
              <td height="38" align="right" bgcolor="#D0D0D0"><a href="cn_conceptos.php">[+]</a> <strong>Concepto Compra:</strong></td>
              <td></td>
              <td><?php
// consulta
$consulta = "
SELECT idconcepto, descripcion
FROM cn_conceptos
where
estado = 1
and borrable = 'S'
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['idconcepto'])) {
        $value_selected = htmlentities($_POST['idconcepto']);
    } else {
        $value_selected = htmlentities($rs->fields['idconcepto']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idconcepto',
        'id_campo' => 'idconcepto',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idconcepto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => ' style="width:90%; height:40px;" ',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?></td>
            </tr>
<?php } ?>
            <tr>
              <td height="38" align="right" bgcolor="#D0D0D0"><strong>IVA Compra:</strong></td>
              <td></td>
              <td><select name="tipoiva" required="required" id="tipoiva" readonly="readonly" >
                <option value="" selected="selected">Seleccionar</option>
                <option value="0">Excenta</option>
                <option value="5">5%</option>
                <option value="10" selected="selected">10%</option>
              </select></td>
            </tr>
           <tr>
              <td colspan="3" align="center">&nbsp;</td>
          </tr>
           <tr>
           		<td colspan="3" align="center"><input type="button" value="Registrar" onclick="registra(1)"  /></td>
           
          </tr>
   		</table>
       </form>
     
        <br />
        

   </div>
   <br /><hr /><br />
   <p align="center"><strong>Busqueda:</strong><br /><br />
     <input type="text" name="busq" id="busq" style="height:40px; width:300px;" onKeyUp="filtrar(this.value)" placeholder="Producto"  />
    <input type="text" name="codins" id="codins" style="height:40px; width:100px;" onchange="filtrar_cod();" placeholder="Codigo"  />
    <input type="button" name="button" id="button" value="Buscar Codigo" onmouseup="filtrar_cod();" />
   </p>


  <br /> 
  <hr /><br />
   <div align="center" id="lista_insu">
    <?php require_once("mini_ins.php"); ?>
   </div>
</div> <!-- contenedor -->
  


   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("../includes/pie.php"); ?>
</body>
</html>


 