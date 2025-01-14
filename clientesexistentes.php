<?php
/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");



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


    $parametros_array = [
        'idclientetipo' => 1,
        'ruc' => $_POST['ruc'],
        'razon_social' => $razon,
        'documento' => $_POST['dc'],
        'fantasia' => $_POST['fantasia'],
        'nombre' => $_POST['nom'],
        'apellido' => $_POST['ape'],


        'idvendedor' => '',
        'sexo' => '',

        'nombre_corto' => $_POST['nombre_corto'],
        'idtipdoc' => $_POST['idtipdoc'],

        'telefono' => $_POST['telfo'],
        'celular' => $_POST['celular'],
        'email' => $_POST['email'],
        'direccion' => $_POST['dire'],
        'comentario' => $_POST['comentario'],
        'fechanac' => $_POST['fechanac'],

        'ruc_especial' => $_POST['ruc_especial'],
        'idsucursal' => $idsucursal,
        'idusu' => $idusu,

    ];


    $res = validar_cliente($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = $res['valido'];
        $errores = nl2br($res['errores']);
    }
    //print_r($res);exit;
    // si todo es correcto inserta
    if ($valido == "S") {

        $res = registrar_cliente($parametros_array);
        $idcliente = $res['idcliente'];
        echo $idcliente;

        //header("location: cliente_edita_cred_linea.php?id=".$mayor);
        //exit;

    }
    /*

                $buscar="Select * from cliente where nombre=$nombres and apellido=$apellidos and idempresa = $idempresa and estado = 1";
                $rsf=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
                if ($rsf->fields['nombre']==''){
                               //esta ok
                               $buscar="select max(idcliente) as mayor from cliente";
                               $rsmay=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
                               $mayor=intval($rsmay->fields['mayor'])+1;

                               $insertar="Insert into cliente (idcliente,nombre,apellido,ruc,documento,direccion,celular,razon_social,idempresa)
                               values
                               ($mayor,$nombres,$apellidos,$ruc,$dc,$direclie,$telfo,'$razon',$idempresa)";
                               $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));
                               $buscar="Select * from cliente where idcliente=$mayor and idempresa = $idempresa order by razon_social asc";
                               $rscli=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
                               $tcli=$rscli->RecordCount();
                } else {
                               //Posible duplicidad

                }
                */
} else {
    $v = trim($_POST['bus']);




    if ($v != '') {
        $ra = antisqlinyeccion($_POST['bus'], 'text');
        $ra = str_replace("'", "", $ra);
        $add = " and razon_social like '%$ra%'";
    } else {
        $add = '';

    }
    $buscar = "Select * from cliente where idempresa = $idempresa and estado = 1 $add order by razon_social asc limit 20";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tcli = $rscli->RecordCount();
}





?>
<input type="text" name="blci" id="blci" onKeyPress="filtrar();" placeholder="Razon Social"><input type="button" value="filtrar" onClick="filtrar();">
<div id="clientereca">
    <select size="5" style="width:100%" name="cliente" id="cliente" onChange="selecciona_cliente(this.value);">
                <?php while (!$rscli->EOF) {?>    
    <option value="<?php echo $rscli->fields['idcliente']?>"><?php echo $rscli->fields['ruc'].' --> '.$rscli->fields['razon_social']?></option>
    <?php $rscli->MoveNext();
                }?>
    </select>
</div>
<br />
