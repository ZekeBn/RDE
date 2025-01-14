 <?php
require_once("includes/conexion.php");
//require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";

require_once("includes/rsusuario.php");

$insertado = 'N';

//Limpiar y almacenar
if (isset($_POST['tipoclie']) && intval($_POST['tipoclie']) > 0) {
    $tipoclie = intval($_POST['tipoclie']);
    $nombres = antisqlinyeccion($_POST['nombres'], 'text');
    $apellidos = antisqlinyeccion($_POST['apellidos'], 'text');
    $razon = antisqlinyeccion($_POST['razon'], 'text');
    $documento = antisqlinyeccion($_POST['documento'], 'int');
    $ruc = antisqlinyeccion($_POST['ruc'], 'text');
    $direccion = antisqlinyeccion($_POST['direccion'], 'text');
    $movil = ($_POST['celular']);
    //$movil=substr($movil,1,11);
    $lbaja = antisqlinyeccion($_POST['lbaja'], 'text');
    $email = antisqlinyeccion($_POST['email'], 'text');
    $comentario = antisqlinyeccion($_POST['comentario'], 'text');
    $fechanac = antisqlinyeccion($_POST['fechanac'], 'text');

    $valido = 'S';
    $errores = '';

    if ($tipoclie == 0) {
        $errores = $errores."* Debe Seleccionar tipo de cliente.<br />";
        $valido = 'N';

    }
    if ($tipoclie == 1) {

        if (($nombres == 'NULL') or ($apellidos == 'NULL')) {
            $errores = $errores."* Debe ingresar el (los) nombre(s) y apellido(s).<br />";
            $valido = 'N';
        }

        $razon = antisqlinyeccion($_POST['nombres'].' '.$_POST['apellidos'], 'text');
        if (($documento == 'NULL') or ($documento == 0)) {
            if ($ruc == 'NULL') {
                $errores = $errores."* Debe ingresar al menos un tipo de documento.<br />";
                $valido = 'N';
            } else {
                $tipodocu = 2;
                $agregado = " ruc=$ruc ";
                $muestra = "$ruc";
            }
        } else {
            $tipodocu = 1;
            $agregado = " documento=$documento ";
            $muestra = "$documento";
        }
        if ($direccion == 'NULL') {
            $errores = $errores."* Debe ingresar direcci&oacute;n del cliente.<br />";
            $valido = 'N';
        }
        if (($movil == 'NULL') or ($movil == 0)) {
            if (($lbaja == 'NULL') or ($lbaja == 0)) {
                $errores = $errores."* Debe ingresar al menos un n&uacute;mero telef&oacute;nico.<br />";
                $valido = 'N';
            }
        }

    } else {
        if ($tipoclie == 2) {
            if (($razon == 'NULL') or ($razon == '')) {
                $errores = $errores."* Debe ingresar nombre de la empresa.<br />";
                $valido = 'N';
            }
            if ($direccion == 'NULL') {
                $errores = $errores."* Debe ingresar direcci&oacute;n de la empresa.<br />";
                $valido = 'N';
            }
            if (($movil == 'NULL') or ($movil == 0)) {
                if (($lbaja == 'NULL') or ($lbaja == 0)) {
                    $errores = $errores."* Debe ingresar al menos un n&uacute;mero telef&oacute;nico.<br />";
                    $valido = 'N';
                }
            }

            if ($ruc == 'NULL') {
                $errores = $errores."* Debe ingresar n&uacute;mero de ruc.<br />";
                $valido = 'N';
            } else {
                $tipodocu = 2;
                $agregado = " ruc=$ruc ";
                $muestra = "$ruc";
            }




        }
    }


    if ($valido == 'S') {
        //Controlamos que el documento/otros de identidad no exista ya en la BD para la empresa.

        $buscar = "Select nombre,apellido,idcliente from cliente where $agregado and idempresa=$idempresa";
        $rsok = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $cliente = trim($rsok->fields['nombre']).' '.trim($rsok->fields['apellido']);
        $docfound = intval($rsok->fields['idcliente']);
    }
    if ($docfound > 0) {
        $errores = $errores."El Documento $muestra ingresado,ya se encuentra registrado y pertenece a: ".capitalizar($cliente)."<br />Favor ingrese un documento distinto para registrar al presente cliente.";
        $valido = 'N';
    }

    if ($valido == 'S') {
        $buscar = "Select max(idcliente) as mayor from cliente where idempresa=$idempresa";
        $may = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idcliente = intval($may->fields['mayor']) + 1;

        $inserta = "
            insert into cliente
            (idcliente,idempresa,nombre,apellido,documento
            ,ruc,telefono,celular,email,direccion,comentario,fechanac,
            tipocliente,razon_social)
            
            values 
            
            ($idcliente,$idempresa,$nombres,$apellidos,$documento,
            $ruc,$lbaja,$movil,$email,$direccion,$comentario,$fechanac,$tipoclie,$razon)
            ";

        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));
        $insertado = 'S';
    }

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
function mostrar(valor){
        if (valor==1){
            //particular
            document.getElementById('ocu').hidden="";
            
            document.getElementById('razontr').hidden="hidden";
            document.getElementById('nombrestr').hidden="";
            document.getElementById('apellidostr').hidden="";
            document.getElementById('documentotr').hidden="";
            document.getElementById('ructr').hidden="hidden";
            document.getElementById('nacimientotr').hidden="";
            document.getElementById('tipoclie').value=valor;
        } else {
            if (valor==2){
                document.getElementById('ocu').hidden="";
                
                document.getElementById('nombrestr').hidden="hidden";
                document.getElementById('apellidostr').hidden="hidden";
                document.getElementById('documentotr').hidden="hidden";
                document.getElementById('razontr').hidden="";
                document.getElementById('ructr').hidden="";
                document.getElementById('nacimientotr').hidden="hidden";
                document.getElementById('tipoclie').value=valor;
            } else {
                //seleccionar
                
            }
             
        }
        
        
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
        <div class="clear"></div>
        <div class="colcompleto" id="contenedor">
            <br />
             <div align="center">
                <a href="clientes.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar" /></a> 
            </div>
            <br />
              
<!-- SECCION DONDE COMIENZA TODO -->
    




<div align="center" >
    <form id="productos" action="registrar_clientes.php" method="post">
        <table width="278" border="0">
            <tr>
                <td width="272" height="19" colspan="3" align="center"><br /></td>
            </tr>

            <tr>
                <td colspan="3"> </td>
            </tr>
            <tr>
                <td colspan="3"> </td>
            </tr>
            <tr>
                <td height="29" colspan="3" align="Center" style="border:double" >
                    <span class="resaltarojomini"><img src="img/contactos01.png" width="64" height="64" /><br />Seleccione Tipo de Cliente</span><br /><br />
                        <select name="tipoclie2" id="tipoclie2" onchange="mostrar(this.value)">
                        <option value="0" selected="selected">Seleccionar</option>
                        <option value="1">Particular</option>
                        <option value="2">Empresa</option>
                    </select>
                    <input name="tipoclie"  id="tipoclie" type="hidden" value="0" /> 
                </td>
            </tr>
        </table>
        <div id="ocu" hidden="hidden">
            <table width="250" border="0">
                <tr id="nombrestr">
                    <td width="116" align="right" height="29" ><strong>Nombres</strong></td>
                    <td width="2">&nbsp;</td>
                    <td width="118"><input type="text" name="nombres" id="nombres" size="20" 
                    value="<?php echo trim($_POST['nombres']); ?>" />
                    </td>
                </tr>
                <tr id="apellidostr"> 
                    <td align="right"  height="29"><strong>Apellidos</strong></td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="apellidos" id="apellidos" size="20"  value="<?php echo trim($_POST['apellidos']); ?>" /></td>
                </tr>
                <tr id="documentotr">
                    <td align="right"  height="29"><strong>Documento</strong></td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="documento" id="documento" size="20"  value="<?php echo($_POST['documento']); ?>"  /></td>
                </tr>
                <tr id="razontr" hidden="hidden">
                    <td align="right"  height="29"><strong>Razon Social</strong></td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="razon" id="razon" size="20"  value="<?php echo trim($_POST['razon']); ?>" /></td>
                </tr>
                <tr id="ructr" hidden="hidden"> 
                    <td align="right"  height="29"><strong>R.U.C  / Otros </strong></td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="ruc" id="ruc" size="20" value="<?php echo($_POST['ruc']); ?>"  />
                    </td>
                </tr>
                <tr >
                    <td align="right"  height="29" ><strong>Direcci&oacute;n</strong></td>
                    <td ></td>
                    <td ><input type="text" name="direccion" id="direccion" size="20" value="<?php echo $_POST['direccion'] ?>" />
                    </td>
                </tr>
                <tr>
                    <td  align="right"  height="29" ><strong>M&oacute;vil</strong></td>
                    <td> </td>
                    <td ><input type="text" name="celular" id="celular" size="20" value="<?php echo $_POST['celular'] ?>" title="Ingrese solo numeros.Ej:595971594301" /></td>
                </tr>
                <tr >
                    <td align="right"  height="29" ><strong>L&iacute;nea Baja</strong></td>
                    <td ></td>
                    <td ><input type="text" name="lbaja" id="lbaja" size="20" value="<?php echo ($_POST['lbaja']) ?>"  title="Ingrese solo numeros.Ej:59521221243" /></td>
                </tr>
                <tr>
                    <td align="right" style="color:#006;font-weight:bold"  height="29" ><strong>Email</strong></td>
                    <td ></td>
                    <td >
                    <input type="text" name="email" id="email" size="20" value="<?php echo ($_POST['email']) ?>" /></td>
                </tr>
                <tr>
                    <td align="right"  height="29"><strong>Comentario</strong></td>
                    <td>&nbsp;</td>
                    <td><textarea name="comentario" id="comentario" cols="17" rows="3"><?php echo trim($_POST['comentario']); ?></textarea></td>
                </tr>
                <tr id="nacimientotr">
                    <td  align="right"  height="29"><strong>Fecha Nacimiento</strong></td>
                    <td> </td>
                    <td valign="middle"><input type="date" name="fechanac" id="fechanac" size="10" value="<?php echo($_POST['fechanac']); ?>" /></td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="3" align="center"><?php if ($insertado == 'N') {?><img src="img/ok01.png" width="32" height="32" title="registrar" onclick="submit()" style="cursor:pointer" /> <?php }  ?>
                    </td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </div>
    </form>
</div>
<br />
<?php if ($insertado == 'S') {?>
    <div align="center">
        <a href="registrar_clientes.php"><img src="img/ok2smile.png" width="32" height="32" title="Click aqui para agregar Nuevo Cliente" /></a><br /><strong>Registro Correcto</strong>
    </div>
<?php } ?>
    <div align="center" id="errores" class="errorpost">
        <?php echo "<br />".$errores ?>
    </div> 


     
        </div> <!-- contenedor -->
        <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
 
</body>
</html>
