<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";

require_once("includes/rsusuario.php");

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
    $buscar = "Select * from cliente $add2 and  cliente.idempresa=$idempresa order by nombre asc";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $total = $rs->RecordCount();
    if ($total == 0) {
        $errorbusqueda = 1;
    }
} else {
    //Lista de clientes registrados
    $buscar = "Select * from cliente where idempresa=$idempresa order by razon_social asc";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $total = $rs->RecordCount();

}
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
    <span class="resaltaditomenor">
    	Ud se encuentra administrando Clientes
    </span></div><br />
    <div align="center">
    <strong>Seleccione Acci&oacute;n</strong><br />
   <a href="gest_registrar_clientes.php">
   <img src="img/pagrega.png" width="64" height="64" title="Agregar Nuevo" style="cursor:pointer" /></a>
   <a href="gest_lista_clientes.php" target="_self"><img src="img/Downloads.png" width="64" height="64" style="cursor:pointer" title="Listar  Clientes" border="0" /></a></a>
   <img src="img/buscar.png" width="64" height="64" title="Buscar Cliente" style="cursor:pointer" onclick="desocultar(1)" /></a>
   </div>

   <br />
   <div align="center" id="buscar" hidden="hidden">
   
   
   </div>
  <div align="center" id="errores" class="errorpost">
  <?php echo "<br />".$errores ?>
  </div>
  <br  />
  <div align="center" id="lclie">
 	<?php if ($total > 0) {?>
  <table width="600" border="1" style="padding:2px;margin:2px;">
  <tr>
    <td width="166" height="25" align="center" bgcolor="#ECECEC"><strong>Cliente</strong></td>
    <td width="97" align="center" bgcolor="#ECECEC"><strong>Ruc / CI</strong></td>
    <td width="137" align="center" bgcolor="#ECECEC"><strong>Direcci&oacute;n</strong></td>
    <td width="101" align="center" bgcolor="#ECECEC"><strong>Tel&eacute;fono</strong></td>
    <td width="65" align="center" bgcolor="#ECECEC"><strong>Acciones</strong></td>
  </tr>
  <?php while (!$rs->EOF) {

      $idcli = $rs->fields['idcliente'];

      ?>
  <tr>
    <td>
		<?php
         if ($rs->fields['tipocliente'] == 1) {
             echo(trim($rs->fields['nombre']).' '.trim($rs->fields['apellido']));
         } else {
             echo(trim($rs->fields['razon_social']));
         }
      ?></td>
    <td align="center"><?php if ($rs->fields['tipocliente'] == 1) { ?><?php echo formatomoneda($rs->fields['documento'])?><?php } else {?><?php echo($rs->fields['ruc']);
    } ?></td>
    <td><?php echo capitalizar(trim($rs->fields['direccion'])); ?></td>
    <td align="center" style="margin-left:2px"><?php echo trim($rs->fields['celular']); ?></td>
    <td align="center"><a href="gest_editar_clientesv2.php?idc=<?php echo $idcli?>" ><img src="img/1444616944_info.png" width="22" height="22" title="Editar" /></a><img src="img/dropdb.png" width="22" height="22" title="Eliminar" onclick="eliminar(<?php echo $idcli ?>)" /></td>
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