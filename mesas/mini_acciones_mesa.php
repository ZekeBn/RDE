 <?php
/*---------------------------------------
20/10/2023:
Se corrige mostrar nombre o datos cargados de comensales
01/11/2023 :
Se incorpora bandera de diplomatico
---------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

require_once("../includes/funciones_mesas.php");

$clase = intval($_POST['clase']);
$idmesa = intval($_POST['idmesa']);
$idatc = intval($_POST['idatc']);
//Array ( [clase] => 1 [idmesa] => 60 [idatc] => 152 )


//ALTER TABLE `mesas_preferencias` ADD `usa_mesa_smart` CHAR(1) NOT NULL DEFAULT 'S' AFTER `cliente_gen_pin`;
$consulta = "
select mozo_permite_diplomatico,permite_separacuenta, permite_agrupar, permite_mudarmesa, usa_mesa_smart
from mesas_preferencias
limit 1
";
$rsprefm = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$permite_separacuenta = trim($rsprefm->fields['permite_separacuenta']);
$permite_agrupar = trim($rsprefm->fields['permite_agrupar']);
$permite_mudarmesa = trim($rsprefm->fields['permite_mudarmesa']);
$mozo_diplomatico = trim($rsprefm->fields['mozo_permite_diplomatico']);
$usa_mesa_smart = trim($rsprefm->fields['usa_mesa_smart']);





// verifica si el mozo puede establecer los comenzales o no
$consulta = "
select mozo_pone_comenzales from mesas_preferencias limit 1
";
$rsmoz = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$mozo_pone_comenzales = $rsmoz->fields['mozo_pone_comenzales'];

$parametros_array['idusu'] = $idusu;
$res = mozo_o_cajero($parametros_array);
$mozo_o_cajero = $res['mozo_o_cajero'];


$permite_poner_comenzales = "S";
// si es mozo
if ($mozo_o_cajero == 'M') {
    $escajero = 'N';
    if ($mozo_pone_comenzales == 'S') {
        $permite_poner_comenzales = "S";
    } else {
        $permite_poner_comenzales = "N";
    }
} else {
    $escajero = 'S';
}


// generar pin
if (trim($_POST['gen_pin']) == 'S') {

    $valido = "S";
    $errores = "";
    //echo $idatc;exit;
    //$pin=substr($idatc,4).date("s");
    $parametros_array = [
        'idatc' => $idatc
    ];
    $pin_res = autogenera_pin_mesa($parametros_array);
    $pin = $pin_res['pin'];
    //echo "pin:".$pin;exit;
    if ($pin_res['valido'] == 'N') {
        $valido = "N";
        $errores .= "- No se envio el idatc.".$saltolinea;
    }
    // valida que no tenga pin ese mismo atc
    $consulta = "
    select pin 
    from mesas_atc
    where
    idatc = $idatc
    and estado = 1
    ";
    $rspin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $pin_actual = antixss(trim($rspin->fields['pin']));

    // si no tiene pin actualiza
    if ($pin_actual != '') {
        $valido = "N";
        $errores .= "- La mesa ya tiene un pin, editelo.".$saltolinea;
    }

    // si todo es valido actualiza el pin
    if ($valido == 'S') {
        $parametros_array = [
            "pin" => $pin,
            "idatc" => $idatc
        ];
        $respuesta = mesas_atc_edit_pin($parametros_array);
        $res = ['valido' => $respuesta['valido'], 'errores' => $respuesta['errores'], 'pin_actual' => $respuesta['pin_actual']];

    } else {
        $res = ['valido' => $valido, 'errores' => $errores, 'pin_actual' => $pin_actual];
    }

    echo json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    exit;

}

if ($clase == 1) {
    //verificamos si ya esta aplicado un Diplomatico
    $buscar = "Select * from mesas_atc where idatc=$idatc";
    $gt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $aplicado_dip = trim($gt->fields['diplomatico']);
    $aplicado_desc = trim($gt->fields['descuento_aplicado']);
    if ($aplicado_dip == 'S') {
        //hay que ver si un descuento no esta aplicado para permitir o bloquear
        $ena = " disabled='disabled' style='background-color:grey;' ";
        $ena2 = "";
        $mensaje = "<span style='color:red;'>Diplom&aacute;tico est&aacute; activado!</span>";

    } else {
        //No esta aplicado el diplomatico, si hay un descuento activo, bloquear
        if ($aplicado_desc == 'S') {
            $ena = " disabled='disabled' style='background-color:grey;' ";
            $mensadd = "<br />** Actualmente un descuento esta aplicado a la mesa,favor revertir el descuento a cero, luego regrese para aplicar diplomatico **<br />
            De existir descuentos, deberan ser aplicados luego de diplomatico! ";
        } else {
            $ena = "";
        }
        $ena2 = " disabled='disabled' style='background-color:grey;' ";
        $mensaje = "<span style='color:red;'>Diplomatico esta Desactivado!$mensadd</span>";
    }
    //Diplomatico
    ?>
<hr />
<div class="col-md-12 col-xs-12">
    <div class="alert alert-warning alert-dismissible fade in" role="alert" style="text-align:center;">
        
        <strong>Nota:</strong>Aplica el descuento del IVA para diplom&aacute;ticos. Debe solicitar el carnet (copia) para certificar que sea un diplom&aacute;tico.<br />
        Estado actual: &nbsp; <?php echo $mensaje ?>
    </div>
    
    <?php if ($mozo_diplomatico == 'S') { ?>
    <div class="col-md-12 col-sm-12 col-xs-12 form-group has-feedback">
        <div class="col-md-4">
            <button <?php echo $ena; ?> type="button" class="btn-btn-round btn-primary btn-sm" onclick="diplomatico(<?php echo $idatc ?>,'S');"><span class="fa fa-briefcase"></span>&nbsp;&nbsp;Aplicar Diplomatico</button>&nbsp;&nbsp;
        </div>
        <div class="col-md-4">
            <button <?php echo $ena2; ?> type="button" class="btn-btn-round btn-primary btn-sm" onclick="diplomatico(<?php echo $idatc ?>,'N');"><span class="fa fa-refresh"></span>&nbsp;&nbsp;Revertir Diplomatico</button>
        </div>
    </div>
    <?php } else {
        if ($escajero == 'S') {?>
        <div class="col-md-12 col-sm-12 col-xs-12 form-group has-feedback">
            <button <?php echo $ena; ?> type="button" class="btn-btn-round btn-primary btn-sm" onclick="diplomatico(<?php echo $idatc ?>,'S');"><span class="fa fa-add">
            </span>&nbsp;&nbsp;Aplicar Diplomatico</button>
            <button <?php echo $ena2; ?> type="button" class="btn-btn-round btn-primary btn-sm" onclick="diplomatico(<?php echo $idatc ?>,'N');"><span class="fa fa-add">
            </span>&nbsp;&nbsp;Revertir Diplomatico</button>
        </div>
        
        <?php  } else {
            echo "Sin permiso.";
        } ?>
    <?php } ?>
</div>
<?php } ?>
<?php if ($clase == 2) { ?>
<hr />
<div class="col-md-6 col-xs-6">
    <strong><span class="fa fa-info-circle"></span>&nbsp;Env&iacute;a productos  seleccionados a una mesa libre.</strong>
    <br /><br /> <br />
    <button type="button" class="btn-btn-round btn-primary btn-sm" onclick="document.location.href='separa_cuenta.php?idmesa=<?php echo $idmesa ?>';"><span class="fa fa-calculator">
    </span>&nbsp;&nbsp;Separar Consumo</button><br />
    
</div>
<div class="col-md-6 col-xs-6">
    <strong><span class="fa fa-info-circle"></span>&nbsp;Muda todos los productos a otra mesa libre.</strong>
    
         <?php
        // consulta
        $consulta = "select *, CONCAT('MESA: ', mesas.numero_mesa,' SALON: ',salon.nombre) as mesa
        from mesas 
        inner join salon on mesas.idsalon = salon.idsalon
        where
        salon.idsucursal = $idsucursal
        and mesas.idsucursal = $idsucursal
        and idmesa <> $idmesa
        and mesas.estado_mesa = 1
        and salon.estado_salon = 1
        and mesas.estadoex = 1
        and idmesa not in (
                            Select mesas.idmesa 
                            from tmp_ventares_cab 
                            inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa 
                            INNER JOIN salon on mesas.idsalon = salon.idsalon 
                            where 
                            finalizado='S' 
                            and registrado='N' 
                            and estado=1 
                            and tmp_ventares_cab.idsucursal = $idsucursal
                            and mesas.idmesa <> $idmesa
                            GROUP by mesas.idmesa 
                            order by mesas.numero_mesa asc
                            )
        order by salon.nombre asc, mesas.numero_mesa asc
          ";

    // valor seleccionado
    if (isset($_POST['idmesa'])) {
        $value_selected = htmlentities($_POST['idmesa_destino']);
    }

    // parametros
    $parametros_array = [
    'nombre_campo' => 'idmesa_destino',
    'id_campo' => 'idmesa_destino',

    'nombre_campo_bd' => 'mesa',
    'id_campo_bd' => 'idmesa',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar Destino',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
        <button type="button" class="btn-btn-round btn-primary btn-sm" onclick="mudar_mesa(<?php echo $idatc ?>);"><span class="fa fa-list">
        </span>&nbsp;&nbsp;Mudar Consumo
        </button>
</div>
<hr />
<div  class="col-md-12 col-sm-12 col-xs-12 form-group has-feedback" >
        <div class="col-md-12 col-xs-12">
            <h2>Agrupaci&oacute;n de mesas</h2>
            <small> Al procesar o confirmar, las consumisiones de la(s)mesa(s) seleccionada(s), ser&aacute;n incluidas en la cuenta.</small>
        </div>
        <?php
    if ($permite_agrupar == 'S') {

        $buscar = "select idmesa,nombre as salanom,numero_mesa 
            from mesas 
            inner join salon on salon.idsalon=mesas.idsalon  
            where 
            estado_mesa=2 
            and salon.estado_salon = 1
            and idmesa not in (select idmesa from tmp_mesitasele) 
            and idmesa not in (select idmesa from mesas_atc where idatc=$idatc and estado in (2,3,4,5)) 
            and mesas.idsucursal = $idsucursal
            and mesas.idmesa <> $idmesa
            order by numero_mesa asc ";

        $rsmelista = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //echo $buscar;
        $tmesas = intval($rsmelista->RecordCount());
        if ($tmesas > 0) {

            ?>
        <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
            <select name="idmesasel" id="idmesasel" class="form-control">
            <option value="0" selected="selected">Seleccione Mesa</option>
            <?php
                while (!$rsmelista->EOF) {
                    ?>
            <option value="<?php echo $rsmelista->fields['idmesa'] ?>"><?php echo $rsmelista->fields['numero_mesa'].' ** '.$rsmelista->fields['salanom'] ?></option>
            <?php $rsmelista->MoveNext();
                } ?>
            </select><br />
            <button type="button" class="btn-btn-round btn-warning btn-sm" onclick="guardar_info(<?php echo $idmesa?>,<?php echo $idatc ?>);"><span class="fa fa-save"></span>&nbsp;&nbsp;Agregar</button>
        </div>
    
    
    
        <?php }?>
        <div  class="col-md-6 col-sm-12 col-xs-12 form-group has-feedback">
            <?php
                //Mostramos un cuadro con la mesa ppal, para indicar que a ésta se le anexa el resto
                $buscar = "select idmesa,nombre as salanom,numero_mesa from mesas 
                inner join salon on salon.idsalon=mesas.idsalon 
                where idmesa=$idmesa and mesas.idsucursal = $idsucursal ";
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //Traemos las mesas que se encuentren agrupadas bajo este idatc, en el temporal
        $buscar = "Select numero_mesa,idmesa from mesas where idmesa in (select idmesa from tmp_mesitasele where idatc=$idatc)";
        $rsmes = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tanexas = $rsmes->RecordCount();
        ?>
            <div class="alert alert-warning alert-dismissible fade in" role="alert" style="height:38px;">    
            Mesas Agrupadas:
            </div>
            <div class="col-md-12 col-xs-12">
                <a class="btn btn-app">
                    <i class="fa fa-inbox"></i>Ppal: &nbsp; <?php echo $rsb->fields['numero_mesa']?>
                </a>
                <?php if ($tanexas > 0) {
                    while (!$rsmes->EOF) {?>
                        <a class="btn btn-app" style="background-color: aqua">
                            <i class="fa fa-inbox"></i> N&deg;: &nbsp; <?php echo $rsmes->fields['numero_mesa']?>
                        </a>
                
                    <?php $rsmes->MoveNext();
                    }?>
                <?php }?>
            </div>
                
            
            
            <?php } else { // if($permite_agrupar == 'S'){?>
            <div class="alert alert-danger alert-dismissible fade in" role="alert">
        
                <strong>La agrupaci&oacute;n de mesas fue desactivada por el administrador de tu local.</strong>
            </div>
            
            <?php }?>
        </div>

    </div>    

<div class="clearfix"></div>
<hr />
<div class="col-md-12 col-xs-12">
<h2>Pin Mesa (Para clientes)</h2>
<?php if ($usa_mesa_smart == 'S') { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error_box_pin" style="display:none;">
<strong>Errores:</strong><br /><span id="error_box_pin_msg"></span>
</div>
<?php
$consulta = "
select pin 
from mesas_atc
where
idatc = $idatc
and estado = 1
";
    $rspin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $pin_mesa = antixss(trim($rspin->fields['pin']));
    ?>

    <div class="col-md-12 col-sm-12 form-group">

        <div class="col-md-12 col-sm-12 col-xs-12">
            <input type="text" id="pin_mesa" name="pin_mesa"  value="<?php echo antixss($pin_mesa);?>" placeholder="" class="form-control" readonly disabled >    
        </div>
    </div>
    <div class="col-md-12 col-sm-12 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <?php if ($pin_mesa == '') { ?>
            <button id='btn_genpin' type="button" class="btn-btn-round btn-default btn-sm" onclick="genera_pin_mesa(<?php echo $idatc ?>)"><span class="fa fa-refresh">
            </span>&nbsp;&nbsp;Generar Pin</button>
            <?php } else { ?>
            <button type="button" class="btn-btn-round btn-default btn-sm" onclick="document.location.href='../mesas_qr/mesas_smart_pin_edit_vmesa.php?idatc=<?php echo $idatc ?>'"><span class="fa fa-edit">
            </span>&nbsp;&nbsp;Editar Pin</button>
            <?php } ?>
            <button type="button" class="btn-btn-round btn-default btn-sm" onclick="document.location.href='../mesas_qr_imprime.php?idatc=<?php echo $idatc ?>'"><span class="fa fa-print">
            </span>&nbsp;&nbsp;Imprimir Pin</button>
        </div>
    </div>

    
</div>    
<?php } else { // if($usa_mesa_smart=='S'){?>
El modulo Mesa Smart no esta activo para tu local. Consulte con el soporte.
<?php } // if($usa_mesa_smart=='S'){?>
<?php } // if($clase==2){?>
<?php if ($clase == 3) {
    $buscar = "Select * from mesas_atc where idatc=$idatc";
    $rsmesadatos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $nombre_mesa = trim($rsmesadatos->fields['nombre_mesa']);
    $adul = intval($rsmesadatos->fields['cant_adultos']);
    $nin = intval($rsmesadatos->fields['cant_ninos']);
    $otros = intval($rsmesadatos->fields['cant_nopaga']);

    ?>
<div class="col-md-12 col-xs-12">
<h2>Comensales</h2>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Mesa </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" id="nombre_mesa" name="nombre_mesa"  value="<?php echo antixss($nombre_mesa);?>" placeholder="" class="form-control"  >    
        </div>
    </div>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Adultos</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" id="comensalesad" name="comensalesad" value="<?php echo $adul;?>" placeholder="" class="form-control" <?php /*$permite_poner_comenzales='N';*/ if ($permite_poner_comenzales != 'S') {
                echo 'readonly';
            } ?> >    
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Ni&ntilde;os</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text"  id="comensalesni" name="comensalesni" value="<?php echo $nin;?>" placeholder="" class="form-control" <?php if ($permite_poner_comenzales != 'S') {
                echo 'readonly';
            } ?> >    
        </div>
    </div>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">No Pagan</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text"  id="nopagan" name="nopagan"  value="<?php echo $otros;?>" placeholder="" class="form-control" <?php if ($permite_poner_comenzales != 'S') {
                echo 'readonly';
            } ?> >    
        </div>
    </div>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <button type="button" class="btn-btn-round btn-info btn-sm" onclick="guardar_info(<?php echo $idmesa?>,<?php echo $idatc ?>)"><span class="fa fa-add">
        </span>&nbsp;&nbsp;Guardar</button>
        </div>
    </div>
    
</div>
<?php } ?>
