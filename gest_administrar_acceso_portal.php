 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";

require_once("includes/rsusuario.php");

header("Location: gest_adm_acceso_portal_empresas.php");
exit;

$idcliente = intval($_REQUEST['idc']);
if ($idcliente == 0) {
    $idcliente = intval($_REQUEST['idcoc']);

}


if ($idcliente > 0 && !$_POST['idcoc']) {


    $buscar = "Select * from cliente where idcliente=$idcliente and cliente.idempresa=$idempresa order by nombre asc";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    //Vemos si el cliente ya existe, en la tabla de codigos para mosgtrar sus datos
    $buscar = "Select * from clientes_codigos where idcliente=$idcliente ";
    $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


}

//Post de Registro
if (isset($_POST['idcoc']) && $idcliente > 0) {
    //

    $usuarioweb = antisqlinyeccion($_POST['usuario_empresa'], 'text');
    $claveweb = antisqlinyeccion($_POST['clave_empresa'], 'clave');
    $claveweb = str_replace("'", "", $claveweb);
    $claveweb = md5("$claveweb");


    $buscar = "Select * from clientes_codigos where idcliente=$idcliente";
    $con = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($con->fields['idcliente'] == '') {
        //No existe y hacemos insert
        $insertar = "Insert into clientes_codigos (us_empresa,pass_empresa,bloqueado_web,ult_modif,idempresa,registrado_por,registrado_el,idcliente,estado_web) 
        values($usuarioweb,'$claveweb','N',current_timestamp,$idempresa,$idusu,current_timestamp,$idcliente,1)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $i = 1;
        $palabra = ' creada ';
    } else {
        //Update
        $update = "Update clientes_codigos set pass_empresa='$claveweb',us_empresa=$usuarioweb where idcliente=$idcliente and idempresa=$idempresa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $u = 1;
        $palabra = ' actualizada ';
    }

    $buscar = "Select * from cliente where idcliente=$idcliente and cliente.idempresa=$idempresa order by nombre asc";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    //Vemos si el cliente ya existe, en la tabla de codigos para mosgtrar sus datos
    $buscar = "Select * from clientes_codigos where idcliente=$idcliente ";
    $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



    $wemail = trim(strtolower($rs->fields['email']));

    if ($wemail != '') {

        // crea imagen
        $img = "gfx/empresas/emp_".$idempresa.".png";
        //echo $img;exit;
        if (!file_exists($img)) {
            $img = "gfx/empresas/emp_0.png";
        }

        $img_url = "http://martaelena.restaurante.com.py/$img";
        $url = "http://martaelena.sistema.com.py/empresas";

        $buscar = "Select * from cliente where idcliente=$idcliente";
        $rscl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $buscar = "Select * from clientes_codigos where idcliente=$idcliente";
        $rsclcod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $yuser = $rsclcod->fields['us_empresa'];
        $claveweb = '12345M*';
        $rz = trim($rscl->fields['razon_social']);
        //enviar email
        $texto = str_replace("[empresa]", $rz);
        $texto = "
        <div align=\"left\">
        <strong>Bienvenido.</strong> $rz<br /><br />
        
        Te contamos que a partir de la fecha, ya podes usar nuestro exclusivo portal de empresas, el cual te permite :<br /></ul><br />
        * Agregar Adherentes (colaboradores - funcionarios).<br />
        * Establecer L&iacute;neas de cr&eacute;dito.<br />
        * Visualizar el estado de cuenta.<br />
        * Visualizar el Menu de la semana ( o del d&iacute;a) y por supuesto, hacer tu pedido.<br /><br />
        &nbsp;&nbsp; C&oacute;mo acceder?<br />
        Es muy f&aacute;cil, solo debes ingresar a nuestra url: http://martaelena.restaurante.com.py/empresas<br /><br />
        indicar tu nombre de usuario ->$yuser <br />
        indicar tu clave: ->  $claveweb (pod&eacute;s cambiarla en el panel)
        <br />
        <br />
        <div style=\"width:200px; height:80px;\">
        <img src=\"$img_url\" style=\"width:120px; height:80px;\" />
        </div>
        <br />
        <strong>Gracias por confiar en nuestro servicio. $nombreempresa, innovando para vos!.
        </div>";
        //echo $texto;exit;
        $terminado = 1;
        //enviomail($texto,$wemail,$nombreempresa);
        //echo  $terminado;exit;
    }


}




$buscar = "Select * from preferencias";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usaportal = trim($rspref->fields['habilita_portal']);
$clavenueva = $rs1->fields['pass_empresa'];
if ($clavenueva == '') {
    $clavenueva = "12345M*";
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>

<?php require("includes/head.php"); ?>
<script>
<?php if ($terminado == 1) {?>
function enviarmail(){
    //alert('Enviando mail');
        var parametros= {
      
        "cuenta"    :'<?php echo $wemail?>',
        "url"        : '<?php echo $url?>',
        "empresa"    : '<?php echo  $nombreempresa?>',
        "ima"        : '<?php echo $img_url ?>',
        "razon"        : '<?php echo $rz ?>',
        "yuser"        : '<?php echo $yuser ?>',
        "clavey"    : '<?php echo $claveweb ?>'
        };    
        $.ajax({
                data:  parametros,
                url:   'http://localhost/localmailer/index.php',
                type:  'post',
                beforeSend: function () {
                    
                },
                success:  function (response) {
                    
                    $("#mailear").html(response);
                    if (response=='ok'){
                        alertar('Mail enviado correctamente.','','success','Aceptar');
                    } else {
                        //alertar(response,'','error','Aceptar');
                        //alert(response);
                    }

                }     

         });
        
        
}
<?php }?>
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
<body bgcolor="#FFFFFF" <?php if ($terminado == 1) {?> onload="enviarmail()" <?php } ?>>
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
      <p><a href="clientes.php" title="Regresar"><img src="img/homeblue.png" width="64" height="64" alt=""/></a></p>
      <p><span class="resaltaditomenor">Acceso a portales</span>
        <br />
          <div id="mailear"></div>
      </p>
    </div>
    
    <br />
    <form action="gest_administrar_acceso_portal.php" method="post">
        <div class="resumenmini">Administre el acceso al los portales para los clientes, estableciendo el usuario y clave iniciales para la empresa</div><br />
        <?php if ($i == 1 or $u == 1) {?>
        <div align="center" style="font-size:24px;">
         La cuenta ha sido <?php echo $palabra ?> correctamente
        </div>
        <?php
        }?>
        <br />
<br />

      <table width="498" height="127" border="1">
  <tbody>
    <tr>
      <td width="250" height="32" align="center" bgcolor="#F4F4F4"><strong>Razon Social</strong></td>
      <td width="232" align="center" bgcolor="#F4F4F4"><strong>RUC</strong></td>
     
     
    </tr>
    <tr>
      <td height="42" align="center"><?php echo $rs->fields['razon_social']?></td>
      <td align="center"><?php echo $rs->fields['ruc']?></td>
      
      
    </tr>
    <tr>
      <td height="32" colspan="2" align="center" bgcolor="#F4F4F4">Email: <?php echo $rs->fields['email']?> <br />
      <?php if ($rs->fields['email'] == '') {?> 
      <span class="resaltarojomini">ATENCION: el cliente no posee una cuenta de email. La cuenta de acceso, ser&aacute; creada (o actualizada) , pero ning&uacute;n correo ser&aacute; enviado.</span>
      <?php }?>
      </td>
      </tr>
    <tr>
      <td width="250" height="32" align="center" bgcolor="#F4F4F4"><strong>Usuario</strong></td>
      <td width="232" align="center" bgcolor="#F4F4F4"><strong>Clave Inicial</strong></td>
    
    </tr>
    <tr>
      <td height="42"><input type="text" name="usuario_empresa" id="usuario_empresa" required="required" style="height: 40px; width: 99%;" value="<?php echo $rs1->fields['us_empresa']?>" /></td>
      <td><input type="text" name="clave_empresa" id="clave_empresa"  required="required" style="height: 40px; width: 99%;" value="<?php echo $clavenueva?>" /> </td>
      
    </tr>
    <tr>
      <td colspan="3" align="center">
          <input type="hidden" name="idcoc" id="idcoc" value="<?php echo $idcliente?>" />
          <input type="submit" value="Crear Cuenta y Permitir Acceso" /></td>
      </tr>
  </tbody>
</table>
    </form>
  </div>

   <br />
   <div align="center" id="buscar" hidden="hidden">
   
   
   </div>
   <br  />
</div> <!-- contenedor -->


   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
