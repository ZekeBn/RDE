<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";

require_once("includes/rsusuario.php");
if (intval($_REQUEST['cantidad']) > 0) {
    $lim = intval($_REQUEST['cantidad']);
} else {
    $lim = 50;

}
if (isset($_POST['oc']) && ($_POST['oc'] > 0)) {

    $metodo = intval($_POST['forma']);

    if ($metodo == 1) {
        $valor = antisqlinyeccion($_POST['valor'], 'text');
        $valor = str_replace("'", "", $valor);
        //Apellido de cliente
        $add2 = " where apellido like '%$valor%'";
        $add1 = " ";
    }
    if ($metodo == 2) {
        $valor = intval($_POST['valor']);
        //Id cliente
        $add2 = " where idcliente=$valor";

    }
    if ($metodo == 3) {
        $valor = intval($_POST['valor']);
        //Documento de cliente
        $add2 = " where documento=$valor";

    }
    $buscar = "Select * from cliente $add2 and  cliente.idempresa=$idempresa and estado <> 6 order by nombre asc";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $total = $rs->RecordCount();
    if ($total == 0) {
        $errorbusqueda = 1;
    }
} else {
    //Filtros
    $tipocliente = intval($_REQUEST['tiporeg']);
    if ($tipocliente == 0) {
        $add = '';
    } else {
        $add = " and tipocliente=$tipocliente ";
    }
    //Lista de clientes registrados
    $buscar = "Select * from cliente where idempresa=$idempresa and borrable='S' $add  and estado <> 6 order by razon_social asc limit $lim";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $total = $rs->RecordCount();

}
$buscar = "Select * from preferencias";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usaportal = trim($rspref->fields['habilita_portal']);

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<?php require("includes/head.php"); ?>
<script>
	function desocultar(){
		
		if (document.getElementById('oc')){
			
			var oc=parseInt(document.getElementById('oc').value);
		
			document.getElementById('buscar').innerHTML='';
			document.getElementById('buscar').hidden='hidden';
		} else {
		
			document.getElementById('buscar').hidden='';
			var parametros='';
			OpenPage('buscar_cliente.php',parametros,'POST','buscar','pred');
		}
		
	}
	function filtrar(cual){
		var errores='';
		var tipo=cual;
		var razon=$("#buscanombre").val();
		var ruc=$("#buscaruc").val();
		if (cual==1 && razon==''){
			errores=errores+'* Debe indicar razon social a filtrar';
		}
		if (cual==2 && ruc==''){
			errores=errores+'* Debe indicar ruc a filtrar';
		}
		if (errores==''){
			var parametros='tipo='+tipo+'&rz='+razon+'&ru='+ruc;
			OpenPage('buscar_cliente_listota.php',parametros,'POST','lclie','pred');
		} else {
			alert(errores);
		}
			
		
	}
	function eliminar(quien){
	if (quien!=''){
		var parametros='idc='+quien;
		
			swal({
			title: "Desea eliminar al cliente?",
			text: "No podra recuperar al cliente",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Si, borrarlo.",
			cancelButtonText: "No, cancelar.",
			closeOnConfirm: false,
			closeOnCancel: false 
			},
			  function(isConfirm) {
					if (isConfirm) {
						OpenPage('includes/eliminarc.php',parametros,'POST','lclie','pred');
						swal("Listo!", "Cliente eliminado.", "success");
					} else {
						swal("Cancelado", "Cliente sigue activo :)", "error");
					}
				}
	
			);
		
	}
}
	function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
</script>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
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
    <br /><br />
    <div class="divstd">
    <span class="resaltaditomenor">Clientes Existentes</span>
		<br />
    <a href="gest_agregar_cliente.php" target="_self"><img src="img/1444616400_plus.png" width="32" height="32" title="Agregar CLiente"/> </a></div>
	
	<br />
    <div class="resumenmini">Por defecto se muestran <?php echo $lim ?> resultados. Puede modificar el valor filtrando la lista.
	<form id="fc2" action="clientes_listado.php" method="post"><table width="300" height="127" border="1">
  <tbody>
    <tr>
      <td width="53" height="32" align="center" bgcolor="#F4F4F4"><strong>Limite de Reg</strong></td>
      <td width="78" align="center" bgcolor="#F4F4F4"><strong>Mostrar</strong></td>
     
    </tr>
    <tr>
      <td height="42">
		  <input type="number" name="cantidad" id="cantidad"  style="height: 40px; width: 100%" value="<?php if (isset($_REQUEST['cantidad'])) {
		      echo $_REQUEST['cantidad'];
		  } else {
		      echo $lim ;
		  }?>" /></td>
      <td>
		  <select name="tiporeg" id="tiporeg" style="height: 40px; width: 100%">
		  <option value="0" selected="selected">Todos</option>
		  <option value="1" <?php if (isset($_REQUEST['tiporeg']) && ($_REQUEST['tiporeg'] == 1)) {?>selected="selected"  <?php }?>>Particulares</option>
		   <option value="2" <?php if (isset($_REQUEST['tiporeg']) && ($_REQUEST['tiporeg'] == 2)) {?>selected="selected"  <?php }?>>Empresas</option>
		  </select></td>
      
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" value="Modificar" /></td>
      </tr>
  </tbody>
</table>
	</form>
	</div>

   <br />
   <div align="center" id="buscar" hidden="hidden">
   
   
   </div>
  <div align="center" id="errores" class="errorpost">
  <?php echo "<br />".$errores ?>
  </div>
  <br  />
	<div align="center">
		<input name="buscanombre" id="buscanombre" type="text" style="width: 150px;height: 40px;" placeholder="Filtrar por nombre" onkeyup="filtrar(1);"/> &nbsp;&nbsp;
		<input name="buscaruc" id="buscaruc"  type="text" style="width: 150px;height: 40px;" placeholder="Filtrar por ruc"  onkeyup="filtrar(2);"/>
		
      
	</div>
    <br />
<p align="center"><a href="clientes_listado_xls.php">[Descargar Clientes]</a></p>
	<br />
  <div align="center" id="lclie">
 	<?php if ($total > 0) {?>
  <table width="802" border="1" style="padding:2px;margin:2px;">
  <tr>
    <td height="25" colspan="6" align="center" bgcolor="#ECECEC"><strong>Total Registros <?php echo $total ?></strong></td>
    </tr>
  <tr>
    <td width="166" height="25" align="center" bgcolor="#ECECEC"><strong>Razon Social</strong></td>
    <td width="97" align="center" bgcolor="#ECECEC"><strong>Ruc</strong></td>
    <td width="137" align="center" bgcolor="#ECECEC"><strong>CI</strong></td>
    <td width="137" align="center" bgcolor="#ECECEC"><strong>Direcci&oacute;n</strong></td>
    <td width="101" align="center" bgcolor="#ECECEC"><strong>Celular / Otros</strong></td>
    <td width="124" align="center" bgcolor="#ECECEC"><strong>Acciones</strong></td>
  </tr>
  <?php while (!$rs->EOF) {

      $idcli = $rs->fields['idcliente'];
      $tipocliente = intval($rs->fields['tipocliente']);
      ?>
  <tr>
    <td>
		<?php
         if (trim($rs->fields['razon_social']) == '') {
             echo(trim($rs->fields['nombre']).' '.trim($rs->fields['apellido']));
         } else {
             echo(trim($rs->fields['razon_social']));

         }
      ?></td>
    <td align="center"><?php echo($rs->fields['ruc']); ?></td>
    <td><?php echo formatomoneda($rs->fields['documento'])?></td>
    <td><?php echo capitalizar(trim($rs->fields['direccion'])); ?></td>
    <td align="center" style="margin-left:2px"><?php echo trim($rs->fields['celular']); ?></td>
    <td align="center"><a href="gest_editar_clientesv2.php?idc=<?php echo $idcli?>" ><img src="img/1445735221_file.png" width="20" height="20" title="Editar" /></a><img src="img/dropdb.png" width="22" height="22" title="Eliminar" onclick="eliminar(<?php echo $idcli ?>)" />
	  <?php if ($usaportal == 'S' && $tipocliente == 2) {?>
	 <a href="gest_administrar_acceso_portal.php?idc=<?php echo $idcli?>"><img src="img/inet.png" width="22" height="22" title="Administrar Portal"/></a>
	
		<?php }?>
        <a href="venta_credito_habilita.php?bus_rz=<?php echo  (trim($rs->fields['razon_social']))?>" title="Linea de Credito"><img src="img/pagos.png" width="22" height="22" alt=""/></a>
	  </td>
  </tr>
   <?php
   $rs->MoveNext();
  }
 	    ?>
</table>
<?php } ?>
</div>
<div align="center">
<?php if ($errorbusqueda > 0) {?>
	<span class="resaltarojo">La b&uacute;squeda efectuada no produjo ning&uacute;n resultado.</span>
<?php }?>

</div>
   

 
 
  
  
  
  
  
  
  
    
    
  </div> <!-- contenedor -->
  


   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>