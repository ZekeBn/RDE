<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$insertado = 'N';
$selected = "selected='selected'";
$checked = "checked='checked'";
$hiddensi = "hidden='hidden'";
$hiddenno = "hidden=''";
//Limpiar y almacenar
if (isset($_POST['codprod']) && trim($_POST['codprod']) != '') {
    $facompra = antisqlinyeccion($_POST['fcompra'], 'text');
    $fecompra = antisqlinyeccion($_POST['fecompra'], 'date');
    $codprod = antisqlinyeccion($_POST['codprod'], 'text');
    $nombre = antisqlinyeccion($_POST['nombre'], 'text');
    $cantidad = antisqlinyeccion($_POST['cantidad'], 'float');
    $costo = antisqlinyeccion($_POST['costobase'], 'float');
    $tipoprecio = intval($_POST['tipoprecio']);
    $pventa = antisqlinyeccion($_POST['precioventa'], 'float');
    $iva = intval($_POST['tipoiva']);
    if ($iva == 1) {
        $iva = 0;
    }
    if ($iva == 2) {
        $iva = 5;
    }
    if ($iva == 3) {
        $iva = 10;
    }
    $pminimo = antisqlinyeccion($_POST['preciomin'], 'float');
    $pmaximo = antisqlinyeccion($_POST['preciomax'], 'float');
    $listaprecio = listaprecios($_POST['listaprecio']);
    $listaprecio = antisqlinyeccion($listaprecio, 'text');
    $p1 = floatval($_POST['p1']);
    $p2 = floatval($_POST['p2']);
    $p3 = floatval($_POST['p3']);

    $d1 = intval($_POST['descp1']);
    $provee = intval($_POST['proveedor']);
    $catego = antisqlinyeccion($_POST['categoria'], 'int');
    $subcatego = antisqlinyeccion($_POST['subcatels'], 'int');
    $medida = antisqlinyeccion($_POST['medida'], 'int');
    $ubicacion = antisqlinyeccion($_POST['ubicacion'], 'text');
    $imagen = antisqlinyeccion($_POST['img'], 'text');
    $keyword = antisqlinyeccion($_POST['keyword'], 'text');
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], 'int');
    $garantia = antisqlinyeccion($_POST['garantia'], 'int');
    $genero = intval($_POST['genero']);
    $valido = 'S';
    $errores = '';
    if ($codprod == 'NULL') {
        $errores = $errores."* Debe ingresar el codigo del producto.<br />";
        $valido = 'N';
    }
    if ($nombre == 'NULL') {
        $errores = $errores."* Debe ingresar el nombre del producto.<br />";
        $valido = 'N';
    }



    if ($iva == 0) {
        $errores = $errores."* Debe indicar tipo de iva.<br />";
        $valido = 'N';
    }


    //Controlamos que el codigo de producto no exista ya en la BD para la empresa.
    $buscar = "Select descripcion from productos where idprod=$codprod and idempresa=$idempresa";
    $rsok = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if (trim($rsok->fields['descripcion'] != '')) {
        $errores = $errores."El codigo :$codprod ingresado, ya se encuentra asignado al producto: ".trim($rsok->fields['descripcion'])."<br />Favor ingrese un c&oacute;digo distinto para registrar el presente producto.";
        $valido = 'N';
    }

    if ($valido == 'S') {

        $inserta = "
			insert into productos
			(idempresa,idprod,descripcion,costo_actual,idcategoria,idmedida,ubicacion,
			imagen,controla_vencimiento,controla_garantia,registrado_el,keywords,idproveedor,disponible,
			precio_min,precio_max,lista_precios,precio_venta,registrado_por,idsubcate,facturacompra,fechacompra,tipoiva,
			p1,p2,p3,desc1,idgen)
			values 
			($idempresa,$codprod,$nombre,$costo,$catego,$medida,$ubicacion,$imagen,$vencimiento,
			$garantia,current_timestamp,$keyword,$provee,$cantidad,$pminimo,$pmaximo,$listaprecio,$pventa,$idusu,$subcatego,
			$facompra,$fecompra,$iva,$p1,$p2,$p3,$d1,$genero)";
        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        //No se registra en costos debido a cantidad CERO

        //De inmediato registramos el stock virtual
        $buscar = "Select * from productos where idprod=$codprod order by descripcion asc";
        $rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        while (!$rsp->EOF) {
            $facompra = $rsp->fields['facturacompra'];
            $fecompra = $rsp->fields['fechacompra'];
            $codprod = $rsp->fields['idprod'];
            $nombre = $rsp->fields['descripcion'];
            $cantidad = floatval($rsp->fields['disponible']);
            $costo = floatval($rsp->fields['costo_actual']);
            $tipoprecio = intval($rsp->fields['tipoprecio']);
            $pventa = floatval($rsp->fields['precio_venta']);
            $iva = intval($rsp->fields['tipoiva']);
            $pminimo = floatval($rsp->fields['precio_min']);
            $pmaximo = floatval($rsp->fields['precio_max']);
            $listaprecio = $_POST['lista_precios'];
            $listaprecio = $listaprecio;
            $p1 = floatval($rsp->fields['p1']);
            $p2 = floatval($rsp->fields['p2']);
            $p3 = floatval($rsp->fields['p3']);

            $regpor = intval($rsp->fields['registrado_por']);
            $provee = intval($rsp->fields['idproveedor']);
            $catego = intval($rsp->fields['idcategoria']);
            $subcatego = intval($rsp->fields['idsubcate']);
            $medida = intval($rsp->fields['idmedida']);
            $ubicacion = ($rsp->fields['ubicacion']);
            $imagen = ($rsp->fields['img']);
            $keyword = ($rsp->fields['keyword']);
            $vencimiento = ($rsp->fields['vencimiento']);
            $garantia = ($rsp->fields['garantia']);
            $d1 = intval($rsp->fields['descuento']);
            $inserta = "
					insert into productos_virtual
					(idempresa,idprod,descripcion,costo_actual,idcategoria,idmedida,ubicacion,
					imagen,controla_vencimiento,controla_garantia,registrado_el,keywords,idproveedor,disponible,
					precio_min,precio_max,lista_precios,precio_venta,registrado_por,idsubcate,facturacompra,fechacompra,tipoiva,
					p1,p2,p3,desc1)
					values 
					($idempresa,'$codprod','$nombre',$costo,$catego,$medida,'$ubicacion','$imagen','$vencimiento',
					'$garantia',current_timestamp,'$keyword',$provee,$cantidad,$pminimo,$pmaximo,'$listaprecio',$pventa,$regpor,$subcatego,
					'$facompra','$fecompra',$iva,$p1,$p2,$p3,$d1)";
            $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));


            $rsp->MoveNext();
        }

        //Insertamos si en el virtual
        $insertado = 'S';

    }

}



//Datos
//Categorias
$buscar = "Select * from categorias order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Proveedor
$buscar = "Select * from proveedores where idempresa=$idempresa order by nombre ASC";
$rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprov = $rsprov->RecordCount();
$selected = 'selected';

//genero
$buscar = "Select * from gest_genero";
$rsgen = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("../includes/head.php"); ?>
<script>
	function recargar(idc){
		var parametros='idc='+idc;
		OpenPage('subcate.php',parametros,'POST','subcate','pred');
		
	}
	
	function controlar(){
		
		var errores='';
		var prod=document.getElementById('codprod').value;
		if (prod=='')	{
			errores=errores+'Debe indicar codigo del producto. \n'	;
			
		}
		if (document.getElementById('nombre').value==' ')	{
			errores=errores+'Debe indicar nombre del producto. \n'	;
			
		}
		
		if (document.getElementById('medida').value=='0')	{
			errores=errores+'Debe indicar unidad de medida del producto. \n'	;
			
		}
		if (document.getElementById('categoria').value=='0')	{
			errores=errores+'Debe indicar categoria principal del producto. \n'	;
			
		}
		if (document.getElementById('subcatels')){
			
			if (document.getElementById('subcatels').value=='0'){
				errores=errores+'Debe indicar sub-categoria del producto. \n'	;
			}	
		} else {
			
			errores=errores+'Debe indicar sub-categoria del producto. \n'	;	
			
		}
		//Precios
		var p1=document.getElementById('p1').value;
		var p2=document.getElementById('p2').value;
		var p3=document.getElementById('p3').value;
		
		if (p1==''){
			errores=errores+'Debe indicar Precio de venta Principal (P1). \n'	;
			
		}
		if (document.getElementById('genero').value=='0')	{
			errores=errores+'Debe indicar genero del producto. \n'	;
			
		}		//Iva
		if (document.getElementById('tipoiva').value=='0')	{
				errores=errores+'Debe indicar tipo del iva. \n'	;
		}
		
		if (errores==''){
			document.getElementById('productos').submit();
		} else {
			alertar('ATENCION: Algo sali� mal.',errores,'error','Lo entiendo!');	
		}
	}
	
</script>
<script>
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
</script>

<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">

</head>
<body bgcolor="#FFFFFF">
	<?php require("../includes/cabeza.php"); ?>    
	<div class="clear"></div>
	<div class="cuerpo">
 		<div align="center">
		 	<?php require_once("../includes/menuarriba.php");?>
		</div>
		<div class="clear"></div><!-- clear1 -->
		<div class="colcompleto" id="contenedor">
 			<div class="divstd">
				<span class="resaltadito">Registrar Productos</span>
			</div>
            <div align="center"><img src="img/alerta1.png" width="64" height="64" /><br />
            <span class="resaltarojo">
				<strong>ATENCION</strong>: En esta secci&oacute;n se registran los productos con cantidad cero(para creaci&oacute;n de lista).
			</span>
		</div>
        <div align="center" id="errores" class="errorpost">
			<?php echo $errores ?>
		</div>
		<br  />   
        <div align="center">
       	  	<div class="sombreado4">
	<form id="productos" action="gest_lista_productos.php" method="post">  
		<table width="100%" border="0"> 
        <tr>
          <td height="34" colspan="5" align="center" bgcolor="#EBEBEB" ><strong>Datos del Producto</strong><br /></td>
        </tr>
		<tr>
			<td width="16%" height="37" align="right" valign="middle" ><strong>Cod. Producto</strong></td>
			<td width="31%" valign="middle"><input type="text" name="codprod" id="codprod" value="<?php echo trim($_POST['codprod']); ?>" title="Ingrese Codigo Identificador."  placeholder="Codigo unico del Prod." /></td>
			<td width="1%" valign="middle"></td>
			<td width="13%" align="right" valign="middle"><strong>Nombre</strong></td>
			<td width="39%" valign="middle"><input type="text" name="nombre" id="nombre"   value="<?php echo trim($_POST['nombre']); ?>" style="width:180px" placeholder="Nombre del producto" /></td>
		</tr>
		
		<tr>
			<td height="48" align="right" valign="middle" ><strong>Categoria</strong></td>
			<td valign="middle" id="categoriatd">
				<select name="categoria" id="categoria" onchange="recargar(this.value)">
					<option value="0" selected="selected">Seleccionar</option>
					<?php while (!$rscate->EOF) {?>
					<option value="<?php echo $rscate->fields['id_categoria']?>" <?php if (intval($_POST['categoria']) == intval($rscate->fields['id_categoria'])) { ?> selected="selected" <?php } ?>><?php echo trim($rscate->fields['nombre']) ?></option>
					<?php $rscate->MoveNext();
					}?>
				</select>
			</td>
			<td valign="middle">&nbsp;</td>
			<td align="right" valign="middle"><strong>Sub Categoria</strong></td>
		  <td valign="middle" id="subcate"><?php require_once('subcate.php')?></td>
		</tr>
        <tr>
        	
			<td height="29" align="right" valign="middle"><strong>Medida</strong></td>
			<td valign="middle" id="medidatd">
				<select name="medida" id="medida">
				<option value="0" selected="selected">Seleccionar</option>
				<?php while (!$rsmed->EOF) {?>
				<option value="<?php echo $rsmed->fields['id_medida']?>" <?php if (intval($_POST['medida']) == intval($rsmed->fields['id_medida'])) { ?> selected="selected" <?php } ?> ><?php echo trim($rsmed->fields['nombre']) ?></option>
				<?php $rsmed->MoveNext();
				}?>
				</select>
			</td>
			<td valign="middle" id="medidatd">&nbsp;</td>
			<td align="right" valign="middle" id="medidatd"><strong>G&eacute;nero</strong></td>
			<td valign="middle" id="medidatd"><select id="genero" name="genero">
			  <option value="0" selected="selected">Seleccionar</option>
			  <?php while (!$rsgen->EOF) {?>
			  <option value="<?php echo $rsgen->fields['idgen']?>" <?php if (($rsgen->fields['idgen']) == ($rsminip->fields['idgen'])) {?> selected="selected" <?php } ?> ><?php echo $rsgen->fields['descripcion']?></option>
			  <?php $rsgen->MoveNext();
			  }?>
			  </select></td>
        	
        </tr>
       
        </table>
        <br />
        <table width="100%">
		
		<tr>
		  <td height="33" align="center" bgcolor="#EBEBEB" colspan="6" ><strong>Adicionales</strong><br /></td>
		  </tr>
          <tr>
			<td height="40" colspan="6" align="center"  bgcolor="#FFFF99" style="color:#C00;font-weight:bold" ><strong>
				Tipos de Precio p/ Venta</strong>
			</td>
		</tr>
			<tr>
			  <td width="20%" height="29" align="right"  ><strong>Precio Venta 1</strong></td>
			  <td width="12%" height="29" align="right"  ><input type="text" name="p1" id="p1" size="10" value="<?php echo $_POST['p1'] ?>" /></td>
			<td width="20%" align="left" ><span style="color:#F00;font-weight:bold; "><strong>Precio Venta 2</strong></span></td>
			<td width="14%" align="right" ><input type="text" name="p2" id="p2" size="10" value="<?php echo $_POST['p2'] ?>"  /></td>
			<td width="20%" align="left" ><span style="color:#006;font-weight:bold"><strong>Precio Venta 3</strong></span></td>
			<td width="14%" ><input type="text" name="p3" id="p3" size="10" value="<?php echo ($_POST['p3']) ?>" /></td>
		</tr>
         <tr >
                <td height="38" align="center" valign="middle" colspan="6">
                	<strong>I.V.A</strong>
                    <select name="tipoiva" id="tipoiva" >
                        <option value="0" selected="selected">Seleccionar</option>
                        <option value="1">Excenta</option>
                        <option value="2">5%</option>
                        <option value="3">10%</option>
                    </select>
                </td>
         </tr>
			<tr>
				<td align="center" style="border-bottom:groove; " colspan="6"><br />
				<?php if ($insertado == 'N') {?>
				<img src="img/ok01.png" width="32" height="32" title="registrar" onclick="controlar()" style="cursor:pointer" /><br /><strong>Registrar</strong>
				<?php } else { ?>
                
<script>

alertar('Todo listo.','El Registro  ha sido correcto!','success','Aguarde!');	
</script>

				<meta http-equiv="refresh" content="3;URL=agregar_productos.php" />
				<?php }?>    
				</td>
			</tr>
		</table>
	</form>
    
</div>

<div id="login-popup" class="white-popup login-popup mfp-hide">
   	 		<?php //require_once('gest_proveedores.php');?>
</div><!-- /.login-popup -->
        </div>       
  		</div> <!-- contenedor -->
  		


   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("../includes/pie.php"); ?>
</body>
</html>