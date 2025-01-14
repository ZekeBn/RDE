 <?php
/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//echo "Modulo desactivado"; // le falta funcion de clientes
//exit;

$n = intval($_POST['n']);
if ($n == 1) {
    //registrar

    $nombres = antisqlinyeccion($_POST['nom'], 'text');
    $apellidos = antisqlinyeccion($_POST['ape'], 'text');

    $razon = str_replace("'", "", $nombres).' '.str_replace("'", "", $apellidos);
    $dc = intval($_POST['dc']);
    $ruc = antisqlinyeccion($_POST['ruc'], 'text');
    $direclie = antisqlinyeccion($_POST['dire'], 'text');
    $telfo = intval($_POST['telfo']);

    $buscar = "Select * from cliente where ruc = $ruc and idempresa = $idempresa ";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if (trim($rsf->fields['ruc']) == '' && $ruc_pred != trim($_POST['ruc'])) {
        //esta ok
        $buscar = "select max(idcliente) as mayor from cliente";
        $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mayor = intval($rsmay->fields['mayor']) + 1;

        $insertar = "Insert into cliente (idcliente,idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social)
        values
        ($mayor,$idempresa,$nombres,$apellidos,$ruc,$dc,$direclie,$telfo,'$razon')";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $buscar = "Select * from cliente where idcliente=$mayor order by razon_social asc";
        $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tcli = $rscli->RecordCount();
        echo $rscli->fields['idcliente'];
        exit;
    } else {
        //Posible duplicidad
        echo "duplicado";
        exit;
    }

} else {
    $v = trim($_POST['bus']);




    if ($v != '') {
        $ra = antisqlinyeccion($_POST['bus'], 'text');
        $ra = str_replace("'", "", $ra);
        $ra = strtoupper($ra);
        $add = " and UPPER(razon_social) like '%$ra%'";
    } else {
        $add = '';

    }
    $buscar = "Select * from cliente where idempresa = $idempresa  $add order by razon_social asc limit 20";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcli = $rscli->RecordCount();
}





?>


    <select size="5" style="width:100%" name="cliente" id="cliente" onChange="selecciona_cliente(this.value);">
    <?php while (!$rscli->EOF) {?>    
    <option value="<?php echo $rscli->fields['idcliente']?>"><?php echo $rscli->fields['ruc'].' --> '.$rscli->fields['razon_social']?></option>
    <?php $rscli->MoveNext();
    }?>
    </select>

<br />

