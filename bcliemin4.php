 <?php
/*------------------------------------------PARA USAR CON PAG MOD 3 SUPERMERCADOS-------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
/*---------------------HECHAUKA--------------------------*/
$genericoruc = '44444401';
$genericodv = '7';
$generico = $genericoruc.'-'.$genericodv;

/*------------------FIN -HECHAUKA-GENERICO---------------------*/

$b = intval($_POST['mini']);
$id = intval($_POST['id']);
$ruc_sin_registrar = "N";
// no hubo post
if ($b == 0) {
    $b = 1;
    // buscar si se cargo ruc en tablet y poner si existe como cliente
    if ($id > 0) {
        // buscar en tablet
        $buscar = "Select ruc, razon_social,telefono,direccion from tmp_ventares_cab where idtmpventares_cab = $id and idempresa = $idempresa and idsucursal = $idsucursal";
        $rstb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $ruc = trim($rstb->fields['ruc']);
        $razon_social = trim($rstb->fields['razon_social']);
        $telefono = trim($rstb->fields['telefono']);
        $direccion = trim($rstb->fields['direccion']);
        // si encuentra buscar en clientes
        if ($ruc != '') {
            $buscar = "Select idcliente from cliente where ruc='$ruc' and idempresa = $idempresa";
            $rstbcli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            if (intval($rstbcli->fields['idcliente']) > 0) {
                $b = intval($rstbcli->fields['idcliente']);
            } else {
                $ruc_sin_registrar = "S";
            }
        }
        // si no existe
    } else {
        //No hubo post, usar por deecto
        $b = 1;
    }

}
// registro automatico
if ($ruc_sin_registrar == 'S') {
    /*$ruc=antisqlinyeccion($ruc,'text');
    $razon_social=antisqlinyeccion($razon_social,'text');
    $telefono=antisqlinyeccion($telefono,'text');
    $direccion=antisqlinyeccion($direccion,'text');*/

    $parametros_array = [
        'idclientetipo' => $_POST['tc'],
        'ruc' => $ruc,
        'razon_social' => $razon_social,
        'documento' => $_POST['dc'],
        'fantasia' => $_POST['fantasia'],
        'nombre' => $_POST['nom'],
        'apellido' => $_POST['ape'],
        'idvendedor' => '',
        'sexo' => '',
        'nombre_corto' => $_POST['nombre_corto'],
        'idtipdoc' => $_POST['idtipdoc'],


        'telefono' => $telefono,
        'celular' => $_POST['celular'],
        'email' => $_POST['email'],
        'direccion' => $direccion,
        'comentario' => $_POST['comentario'],
        'fechanac' => $_POST['fechanac'],

        'ruc_especial' => $_POST['ruc_especial'],
        'idsucursal' => $idsucursal,
        'idusu' => $idusu,


    ];
    $valido = "S";
    $errores = "";
    $res = validar_cliente($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = $res['valido'];
        $errores = nl2br($res['errores']);
    }
    if ($valido == 'S') {
        $res = registrar_cliente($parametros_array);
        $idcliente = $res['idcliente'];

        /*$inserta="
        insert into cliente
        (idempresa,ruc,razon_social,telefono,direccion)
        values
        ($idempresa,$ruc,$razon_social,$telefono,$direccion)";
        $conexion->Execute($inserta) or die(errorpg($conexion,$inserta));
        // buscar el insertado
        $buscar="Select idcliente from cliente where ruc=$ruc and idempresa = $idempresa";
        $rstbcli=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));*/
        //$b=intval($rstbcli->fields['idcliente']);
        $b = $idcliente;
        if ($b == 0) {
            $b = 1;
        }
    }
}
//echo $b;
$buscar = "Select * from cliente where idcliente=$b and idempresa = $idempresa order by razon_social asc";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
// si no encuentra poner el predeterminado
if (intval($rsl->fields['idcliente']) == 0) {
    $buscar = "Select * from cliente where idempresa = $idempresa and borrable = 'N' limit 1";
    $rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $b = $rsl->fields['idcliente'];
}


//echo $buscar;
$raz = trim($rsl->fields['razon_social']);
$direccion = trim($rsl->fields['direccion']);
$ruc = trim($rsl->fields['ruc']);
if ($ruc == '') {
    $ruc = $generico;

}
$telfo = trim($rsl->fields['celular']);

?>
<table width="100%" border="1" style="border-collapse:collapse;">
<tr>
  <td width="70" rowspan="2" align="left"><strong><a href="#pop1" onMouseUp="asignarv(1);" title="Registrar Nuevo"><img src="img/1485884191_user_add.png" width="32" height="32" alt=""/></a><a href="#pop1" onMouseUp="asignarv(2);" title="Existentes"><img src="img/02p64.png" width="32" height="32" alt=""/></a></strong></td>
      <td height="35" align="center" bgcolor="#DBF2F4"><strong>Raz&oacute;n Social</strong></td>
      <td height="35" align="left" bgcolor="#DBF2F4"><strong>Ruc</strong><?php if ($rsl->fields['borrable'] == 'S') { ?>&nbsp;<input type="button" name="button" id="button" value="Editar Cliente" onClick="document.location.href='cliente_editar.php?id=<?php echo $b; ?>'"><?php } ?></td>
    </tr>
    <tr>
      <td height="29" align="center"><strong><?php   echo $raz;  ?></strong></td>
      <td height="29" align="left"><input name="ruch" type="text" id="ruch" value="<?php  echo $ruc?>" readonly style="background-color:#EEEEEE;" /></td>
    </tr>
     <!--<tr>
       <td bgcolor="#DBF2F4" ><strong>Direcci&oacute;n</strong></td>
       <td height="39" bgcolor="#DBF2F4" >&nbsp;</td>
       <td bgcolor="#DBF2F4"><strong>Tel&eacute;fono</strong></td>
    </tr>
     <tr>
       <td height="39" colspan="2" ><?php echo $direccion?></td>
       <td><?php echo $telfo?></td>
    </tr>
  --> 
</table><input type="hidden" name="clientesel" id="clientesel" value="<?php echo $b;?>" />
<input type="hidden" name="clientesel2" id="clientesel2" value="<?php echo $b;?>" />
