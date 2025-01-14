<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $idp = antisqlinyeccion($_GET['id'], 'int');
    $prodlista = intval($_GET['id']);
    $id = $idp;
    $buscar = "Select * from productos where idprod_serial=$id and borrado = 'S'  and idempresa = $idempresa"	;
    $rsminip = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idproducto = intval($rsminip->fields['idprod_serial']);
    $descripcion = trim($rsminip->fields['descripcion']);
    $tr = $rsminip->RecordCount();
    if ($tr == 0) {
        echo "Producto Inexistente!";
        exit;
    }
    //calculamos el costo seguro
    $costoactual = floatval($rsminip->fields['costo_actual']);
    $porce = (($costoactual * 1) / 100);

    $costoseguro = $costoactual + $porce;

}
//print_r($_POST);
if (isset($_POST['produte']) && ($_POST['produte'] != '')) {
    $texto = antisqlinyeccion($_POST['produte'], 'text');
    $idp = antisqlinyeccion($_POST['psele'], 'text');
    $p1 = antisqlinyeccion($_POST['p1'], 'float');
    $p2 = antisqlinyeccion($_POST['p2'], 'float');
    $genero = intval($_POST['genero']);
    $favorito = antisqlinyeccion(trim($_POST['favorito']), 'text');

    $p3 = antisqlinyeccion($_POST['p3'], 'float');
    $subcategoria = intval($_POST['subcategoria']);
    $categoria = intval($_POST['categoria']);
    $desc = antisqlinyeccion($_POST['desc'], 'float');
    $webtext = ($_POST['editor1']);
    $mostrar = intval($_POST['mostrarprod']);
    $combinado = antisqlinyeccion(substr($_POST['combinado'], 0, 1), 'text');
    $idpantallacocina = antisqlinyeccion($_POST['idpantallacocina'], 'int');
    $idimpresoratk = antisqlinyeccion($_POST['idimpresoratk'], 'int');
    $barcode = antisqlinyeccion($_POST['barcode'], 'text');

    $consulta = "
	Select * 
	from productos 
	where 
	borrado = 'N' 
	and descripcion = '$descripcion'
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idprod_serial'] > 0) {
        echo "Ya existe otro producto activo con el mismo nombre, editelo o borrelo para poder restaurar.";
        exit;
    }

    $consulta = "
	Select * 
	from insumos_lista 
	where 
	estado = 'A' 
	and descripcion = '$descripcion'
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idinsumo'] > 0) {
        echo "Ya existe otro insumo activo con el mismo nombre, editelo o borrelo para poder restaurar.";
        exit;
    }



    if ($_POST['combinado'] == 'S') {
        $p1 = 0;
    }
    // si existe el producto borra
    if (intval($rsminip->fields['idprod_serial']) > 0) {
        $update = "
		update productos 
		set 
		borrado = 'N',
		restaurado_por=$idusu,
		restaurado_el='$ahora'
		where 
		idprod_serial=$idproducto 
		and idempresa = $idempresa
		and borrado = 'S'
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        // borra imagen
        $img = "gfx/productos/prod_".$idproducto.".jpg";
        if (file_exists($img)) {
            unlink($img);
        }

        // busca si existe un insumo vinculado
        $consulta = "
		select * 
		from insumos_lista 
		where 
		idproducto = $idproducto
		and idempresa = $idempresa
		";
        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idinsumo = intval($rsins->fields['idinsumo']);
        //echo $idinsumo;
        // si existe producto vinculado al insumo
        if ($idinsumo > 0) {

            // iguala el nombre
            $consulta = "
			update insumos_lista
			set 
			estado = 'A',
			restaurado_por=$idusu,
			restaurado_el='$ahora'
			where
			idproducto = $id
			and idempresa = $idempresa
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // actualiza en produccion
            /*$consulta="
            update prod_lista_objetivos
            set
            estado = 6,
            anulado_por = $idusu,
            anulado_el = '$ahora'
            where
            estado = 1
            and idinsumo in (select idinsumo from insumos_lista where estado = 'I')
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/

        }



        header("location: gest_listado_productos.php");
        exit;

    }


}
$buscar = "Select * from productos  where idempresa = $idempresa  order by descripcion asc";
$rsprodur = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Datos
//Categorias
$buscar = "Select * from categorias 
where 
estado = 1
and (idempresa = $idempresa or borrable = 'N')
and id_categoria not in (SELECT idcategoria FROM categoria_ocultar where idempresa = $idempresa and mostrar = 'N')
 order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Categorias
$buscar = "Select * from sub_categorias where 
(idempresa = $idempresa or idcategoria in (select id_categoria from categorias where especial = 'S'))
 order by descripcion ASC";
$rssubcate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Proveedor
$buscar = "Select * from proveedores where idempresa=$idempresa order by nombre ASC";
$rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprov = $rsprov->RecordCount();

//Genero
//$buscar="SELECT * FROM gest_genero order by descripcion asc";
//$rsgen=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));




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
	function seleccionar(){
		var idp=document.getElementById('prodlista').value;
		if (idp!='0'){
			document.getElementById('produ').submit();
			
		}
	
	}
	function buscar(){
		var valor=document.getElementById('productochar').value;
		if (valor!=''){
			var parametros='pc='+valor;
			OpenPage('gest_sel_prod.php',parametros,'POST','seleprod','pred');
		}
	}
	function recargar(valor){
		
		if (valor!=''){
			var parametros='pc='+valor;
			OpenPage('minisub.php',parametros,'POST','trsub','pred');
		}
	}
	function modificar(){
		var produ=document.getElementById('produte').value;
		if (produ!=''){
		
		document.getElementById('mp').submit();
		}
	}
	function calcular(cual){
		//tomar el precio
		if(cual==2){
		
			var errores='';
			var precio1=document.getElementById('p1').value;
			var precio2=document.getElementById('p2').value;
			precio1=parseFloat(precio1);
			precio2=parseFloat(precio2);
			if ((precio1 !='') && (precio2 !='')){
				//hallamos la diferencia de precios
				if (precio1 <= precio2){
					errores=errores+'Precio 1 no puede ser menor,o igual, a los demas valores. \n';
					document.getElementById('camb').hidden='hidden';
				} else {
					//esta correcto, pero debemos precautelar el costo seguro
					var costoseguro=parseFloat(document.getElementById('pcosto').value);	
					//vemos que el seg precio, no supere el costo+1%
					
					if (precio2 >= costoseguro){
						document.getElementById('camb').hidden='';
						var diferencia=precio1-precio2;
						//tenemos la diferencia neta a porc
						var porcent=((diferencia/precio1)*100);
						document.getElementById('desc').value=porcent;
					} else {
						errores=errores+'Precio No puede ser menor al costo seguro.\n';
						
					}
				}
				
				
				
			} else {
				errores=errores+'Debe indicar precio 1 y 2 para calcular descuento.\n';
				
			}
			if (errores!=''){
				alertar('ATENCION: Algo sali� mal.',errores,'error','Lo entiendo!');
			} else {
				
				
			}
		} else {
			//No es dos, es 3
			//Controlar que no pase el costoseguro
			var precio3=document.getElementById('p3').value;
			precio3=parseFloat(precio3);
			var costoseguro=parseFloat(document.getElementById('pcosto').value);
			if (precio3 >= costoseguro){
				document.getElementById('camb').hidden='';
				var diferencia=precio1-precio3;
						//tenemos la diferencia neta a porc
				var porcent=((diferencia/precio1)*100);
				document.getElementById('desc3').value=porcent;
			
			} else {
				errores=errores+'Precio No puede ser menor al costo seguro.\n';
				
			}
		}
	}
	//PREVENTS ENTER ON BRCODE
	$(document).ready(function(){
		$("#barcode").keydown(function(e){
			if(e.which==17 || e.which==74){
				e.preventDefault();
			}else{
				console.log(e.which);
			}
		})
	});
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
             <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="productos.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor">Restaurar Producto</span>
				</div>
<?php
if (!(isset($_GET['id']) && $_GET['id'] > 0)) { ?><br />
           		<div align="center" id="seleprod"></div>
<?php } else { ?><br />
<?php } ?>
<?php if ($_GET['editado'] == 'ok') { ?><br />
<div style="border:1px solid #000; text-align:center; width:500px; margin:0px auto; padding:5px;">
<h1 align="center" style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;">Cambios Guardados!!!</h1>
<input type="button" name="button" id="button" value="Regresar" onmouseup="document.location.href='gest_editar_productos.php'" />
</div><br />
<?php } ?>
            <hr />
            <?php if ($tr > 0) {?><br /><br />
            	<div class="resumenmini">
                 <br />
                 <strong>Esta seguro que desea restaurar el siguiente producto?</strong><br /><br />
                 <br /><br />
                <form id="mp" name="mp" method="post" action="gest_restaurar_productos.php?id=<?php echo intval($_GET['id']); ?>">
                <table width="358" border="1" class="tablaconborde">
                  <tbody>
                    <tr>
                      <td width="97" height="30" align="right" bgcolor="#878787"><strong>Producto</strong></td>
                      
                      <td align="left"><?php echo trim($rsminip->fields['descripcion']) ?>
                      <input type="hidden" name="produte" id="produte" value="<?php echo trim($rsminip->fields['idprod_serial']); ?>" />
                      </td>
                    </tr>
                   
                    
                  </tbody>
                </table>
<br />
                <table>
                	<tr>
                    	<td>
                    	  <input type="button" name="button2" id="button2" value="Regresar" onmouseup="document.location.href='gest_listado_productos.php'" /></td>
                    	
           		      <td  align="center"><input type="submit"  value="Restaurar" id="camb" name="camb" /></td>
                    	
                    </tr>	
                </table><br />
                
                
				</form>
                
                </div>
            
            
            <?php } ?>
  			</div> <!-- contenedor -->
  		
 

   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
   
	<?php require("../includes/pie.php"); ?>
</body>
</html>