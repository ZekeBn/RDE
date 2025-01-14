 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");

header("location: ajustar_stock.php");
exit;

// funciones para stock
require_once("includes/funciones_stock.php");

$consulta = "
    select * 
    from gest_depositos_ajustes_stock
    where 
    estado = 'A'
    and idempresa = $idempresa
    and registrado_por = $idusu
    ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idajusteact = intval($rs->fields['idajuste']);
$iddeposito = intval($rs->fields['iddeposito']);
// deposito
$consulta = "
    select * 
    from gest_depositos
    where
    iddeposito = $iddeposito
    and idempresa = $idempresa
    and estado <> 6
    ";
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // recibe parametros
    $idajuste = antisqlinyeccion($_POST['idajuste'], "int");
    $iddeposito = antisqlinyeccion($_POST['iddeposito'], "int");
    $fechaajuste = antisqlinyeccion(date("Y-m-d"), "text");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = antisqlinyeccion($idusu, "int");
    $estado = antisqlinyeccion('A', "text");
    $idmotivo = antisqlinyeccion($_POST['idmotivo'], "int");

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (intval($_POST['iddeposito']) == 0) {
        $valido = "N";
        $errores .= " - El campo iddeposito no puede ser cero o nulo.<br />";
    }
    if ($idajusteact > 0) {
        $valido = "N";
        $errores .= " - Ya existe una tanda abierta, finalice primero antes de iniciar otra.<br />";
    }
    if (intval($_POST['idmotivo']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar el motivo del ajuste.<br />";
    }


    /*
    $consulta="
    select *, (select usuario from usuarios where usuarios.idusu = gest_depositos_ajustes_stock.registrado_por) as usuario
    from gest_depositos_ajustes_stock
    where
    estado = 'A'
    and idempresa = $idempresa
    and iddeposito = $iddeposito
    ";
    $rs2=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $idajusteacti=intval($rs2->fields['idajuste']);
    $usutanda=trim($rs2->fields['usuario']);
    if($idajusteacti > 0){
        $valido="N";
        $errores.=" - Ya existe una tanda abierta para este deposito por $usutanda, finalice primero antes de iniciar otra.<br />";
    }*/
    /*if(trim($_POST['fechaajuste']) == ''){
        $valido="N";
        $errores.=" - El campo fechaajuste no puede estar vacio.<br />";
    }*/


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
        insert into gest_depositos_ajustes_stock
        (idajuste, idempresa, iddeposito, fechaajuste, registrado_el, registrado_por, estado, idmotivo)
        values
        ($idajuste, $idempresa, $iddeposito, $fechaajuste, $registrado_el, $registrado_por, $estado, $idmotivo)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_stock_ajuste.php");
        exit;

    }

}
//Post Final
if (isset($_POST['ter']) && ($_POST['ter']) != '') {
    $tfin = intval($_POST['ter']);
    $valido = "S";
    // recorre los archivos a transferir en la tabla temporal
    $buscar = "
    select *, 
    (select insumos_lista.descripcion from insumos_lista where idinsumo = tmp_ajuste.idinsumo and idempresa = $idempresa) as insumo,
    (select insumos_lista.costo from insumos_lista where idinsumo = tmp_ajuste.idinsumo and idempresa = $idempresa) as ultcosto
    from tmp_ajuste
    inner join gest_depositos_ajustes_stock on gest_depositos_ajustes_stock.idajuste = tmp_ajuste.idajuste
     where 
     gest_depositos_ajustes_stock.idajuste=$tfin 
     and gest_depositos_ajustes_stock.idempresa = $idempresa
     and gest_depositos_ajustes_stock.estado = 'A'
     order by (select insumos_lista.descripcion from insumos_lista where idinsumo = tmp_ajuste.idinsumo and idempresa = $idempresa) asc
     ";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if (intval($rsf->fields['idinsumo']) == 0) {
        echo "Error! no existe la tanda de ajuste o no cargo ningun item.";
        exit;
    }

    /// si se valida el stock disponible
    /*if($valida_stock == 'S'){

        while (!$rsf->EOF){

            $idinsumo_traslado=$rsf->fields['idproducto'];
            $cantidad_traslado=$rsf->fields['cantidad'];
            $iddeposito_origen=$rsf->fields['origen'];
            $nombreinsu=$rsf->fields['insumo'];

            // busca el disponible en stock general
            $buscar="Select sum(disponible)  as total_stock
            from gest_depositos_stock_gral
            where
            idproducto=$idinsumo_traslado
            and idempresa=$idempresa
            and estado=1
            and iddeposito = $iddeposito_origen";
            $rsst=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
            $total_stock=floatval($rsst->fields['total_stock']);
            if($cantidad_traslado > $total_stock){
                $errores.="-El disponible es menor a la cantidad que quiere transferir de $nombreinsu, quedan $total_stock y quiere transferir $cantidad_traslado.<br />";
                $valido="N";
            }
            if($total_stock <= 0){
                $errores.="-No queda disponible de $nombreinsu en stock.<br />";
                $valido="N";
            }

        $rsf->MoveNext(); }
        $rsf->MoveFirst(); // reinicia el recordset
    }*/

    // si todo es valido
    if ($valido == "S") {

        // trasladamos insumos
        while (!$rsf->EOF) {

            $idinsumo_ajuste = $rsf->fields['idinsumo'];
            $cantidad_ajuste = $rsf->fields['cantidad'];
            $iddeposito = $rsf->fields['iddeposito'];
            //$iddeposito_destino=$rsf->fields['destino'];
            $nombreinsu = str_replace("'", "", $rsf->fields['insumo']);
            $tipoajuste = $rsf->fields['tipoajuste'];
            $idajuste = $rsf->fields['idajuste'];
            $fechaajuste = $rsf->fields['fechaajuste'];
            $ultcosto = floatval($rsf->fields['ultcosto']);

            // inserta en tabla de ajustes
            $insertar = "
            INSERT INTO gest_depositos_ajustes_stock_det
            (idajuste, idempresa, idinsumo, fechaajust, registrado_por, registrado_el, cantidad_ajuste, tipoajuste, iddeposito) 
            VALUES 
            ($idajuste, $idempresa, $idinsumo_ajuste, '$fechaajuste', $idusu, '$ahora', $cantidad_ajuste, '$tipoajuste', $iddeposito)
            ";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            // busca si existe en stock general cada insumo a trasladar
            $buscar = "Select * 
            from gest_depositos_stock_gral 
            where 
            idproducto=$idinsumo_ajuste 
            and idempresa=$idempresa 
            and estado=1 
            and iddeposito = $iddeposito";
            $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            // si no existe inserta
            if (intval($rsst->fields['idproducto']) == 0) {
                $insertar = "INSERT INTO gest_depositos_stock_gral
                (iddeposito, idproducto, disponible, tipodeposito, last_transfer, estado, descripcion, idempresa) 
                VALUES 
                ($iddeposito,$idinsumo_ajuste,0,1,'$ahora',1,'$nombreinsu',$idempresa
                )";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                movimientos_stock($idinsumo_ajuste, 0, $iddeposito, 9, '+', $idajuste, $fechaajuste);
            }

            // descontar insumo de stock general
            if ($tipoajuste == '-') {
                $consulta = "
                UPDATE gest_depositos_stock_gral 
                SET 
                disponible=(disponible-$cantidad_ajuste)
                WHERE 
                idempresa=$idempresa 
                and iddeposito=$iddeposito
                and idproducto=$idinsumo_ajuste
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                movimientos_stock($idinsumo_ajuste, $cantidad_ajuste, $iddeposito, 9, '-', $idajuste, $fechaajuste);
            } else {
                // aumentar insumo de stock general destino
                $consulta = "
                UPDATE gest_depositos_stock_gral 
                SET 
                disponible=(disponible+$cantidad_ajuste)
                WHERE 
                idempresa=$idempresa 
                and iddeposito=$iddeposito
                and idproducto=$idinsumo_ajuste
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                movimientos_stock($idinsumo_ajuste, $cantidad_ajuste, $iddeposito, 9, '+', $idajuste, $fechaajuste);
            }



            // descontamos con costos y depositos especificos
            if ($tipoajuste == '-') {
                descontar_stock($idinsumo_ajuste, $cantidad_ajuste, $iddeposito);
            } else {
                aumentar_stock($idinsumo_ajuste, $cantidad_ajuste, $ultcosto, $iddeposito);
            }

            $rsf->MoveNext();
        }

        //actualizamos el cierre de transferencia
        $update = "
        Update gest_depositos_ajustes_stock 
        set 
        estado='C' 
        where 
        idajuste=$tfin 
        and idempresa=$idempresa
        and estado='A'
        and iddeposito=$iddeposito 
        ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        header("Location: gest_stock_ajuste.php?t=$idajuste");
        exit;


    } //if($valido=="S"){

}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<script>
function busca_insumo(valor){
    var n = valor.length;
    //alert(valor);
    if(n > 2){
        $("#codigoprod").val('');
       var parametros = {
              "codigop" : valor
       };
       $.ajax({
                data:  parametros,
                url:   'gest_ajustar_stock_cuadro.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
                        $("#insumo_box").html(response);
                }
        });    
    }
}
function busca_insumo_cod_enter(e){
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
        busca_insumo_cod();
    }
}
function busca_insumo_cod(){
       var valor = $("#codigoprod").val();
      $("#codigop").val(''); 
       var parametros = {
              "codigoprod" : valor
       };
       $.ajax({
                data:  parametros,
                url:   'gest_ajustar_stock_cuadro.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
                        $("#insumo_box").html(response);
                }
        });    
}
function busca_insumo_cbar(e){
    var codbar = $("#codigobarra").val();
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
        var valor = $("#codigobarra").val();
        $("#codigop").val(''); 
        var parametros = {
          "codigobarra" : valor,
        };
        $.ajax({
            data:  parametros,
            url:   'gest_ajustar_stock_cuadro.php',
            type:  'post',
            beforeSend: function () {
                    $("#insumo_box").html("Cargando...");
            },
            success:  function (response) {
                    $("#insumo_box").html(response);
                    $("#codigobarra").val('');
                    $(".insu_focus_1").focus();
            }
        });    
    }
}
function busca_insumo_grup(valor){
      $("#codigoprod").val('');
      $("#codigop").val(''); 
       var parametros = {
              "grupo" : valor
       };
       $.ajax({
                data:  parametros,
                url:   'gest_ajustar_stock_cuadro.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
                        $("#insumo_box").html(response);
                }
        });    
}
    function addtmp_todo(){
        //alert($("#tras_insumo").serialize());
       $.ajax({
                data:  $("#tras_insumo").serialize(),
                url:   'add_tmp_ajuste.php',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        $("#insumo_box").html("");
                        $("#tmprodusmov").html('Cargando...');
                },
                success:  function (response) {
                        $("#tmprodusmov").html(response);
                        
                }
        });
        
    }
    function terminar(tandafin){
        $("#cerrartrans").hide();
        var tfp=document.getElementById('ter').value;
        if (tfp !=''){
            document.getElementById('fin').submit();
        }
        
    }
    function chau(cual,tanda){
        if (cual !=''){    
            enlace='add_tmp_ajuste.php';
            var parametros="cual="+cual+'&tp=3&idta='+tanda;
            OpenPage(enlace,parametros,'POST','tmprodusmov','pred');
        }
    }
</script>
</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">

           <div align="center">
            <table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="index.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Ajustar Stock</span>
                </div><br /><br />
<?php if (intval($rs->fields['idajuste']) == 0) { ?>
<?php if (trim($errores) != "") { ?>
    <div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
              <form id="form1" name="form1" method="post" action="">
             
              <table width="400" border="1">
            <tbody>
              <tr>
                <td align="right">Deposito:</td>
                <td>
                <?php
$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where 
usuarios.idempresa=$idempresa 
and gest_depositos.idempresa=$idempresa 
and gest_depositos.estado <> 6
order by descripcion ASC ";
    $rsdep = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<select name="iddeposito" id="iddeposito" >
                    <option value="">Seleccionar...</option>
                      <?php while (!$rsdep->EOF) {?>
                        <option value="<?php echo $rsdep->fields['iddeposito']?>" <?php if ($rsdep->fields['iddeposito'] == $iddeposito) { ?> selected="selected" <?php } ?>><?php echo $rsdep->fields['descripcion']?></option>
                      <?php $rsdep->MoveNext();
                      } ?>
                  </select></td>
              </tr>
              <tr>
                <td align="right">Fecha:</td>
                <td><input name="fecha" type="date" disabled="disabled" id="fecha" readonly="readonly" value="<?php echo date("Y-m-d");?>" /></td>
              </tr>
              <tr>
                <td align="right"><a href="motivos_ajuste.php">[+]</a> Motivo Ajuste:</td>
                <td><?php
        // consulta
        $consulta = "select * from motivos_ajuste  where estado = 1 and idempresa = $idempresa";

    // valor seleccionado
    if (isset($_POST['idmotivo'])) {
        $value_selected = htmlentities($_POST['idmotivo']);
    } else {
        $value_selected = htmlentities($rs->fields['idmotivo']);
    }

    // parametros
    $parametros_array = [
    'nombre_campo' => 'idmotivo',
    'id_campo' => 'idmotivo',

    'nombre_campo_bd' => 'motivo',
    'id_campo_bd' => 'idmotivo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => ''
    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?></td>
              </tr>
              <tr>
                <td colspan="2" align="center"><input type="submit" name="submit" id="submit" value="Iniciar Tanda" />
                <input type="hidden" name="MM_insert" id="MM_insert" value="form1" /></td>
              </tr>
             <!-- <tr>
                <td align="right">Fecha:</td>
                <td>
                <input type="text" name="textfield" id="textfield" /></td>
              </tr>-->
            </tbody>
            </table> </form>
<?php  } ?>
              <p>&nbsp;</p>
<?php if (intval($rs->fields['idajuste']) > 0) { ?>
<table width="400" border="1">
          <tr>
            <td colspan="2" align="center" id="cerrartrans">Tanda: <?php echo $rs->fields['idajuste']; ?>&nbsp;<a href="gest_stock_ajuste_borra.php?id=<?php echo $rs->fields['idajuste']; ?>">[Borrar]</a></td>         
         </tr>
          <tr>
            <td colspan="2" align="center" id="cerrartrans">Deposito:   <?php echo $rsdep->fields['descripcion']; ?></tr>
          <tr>
            <td colspan="2" align="center" id="cerrartrans">
            Finalizar la tanda de ajuste activa<br />
        <a href="javascript:void(0);" onclick="terminar(<?php echo intval($rs->fields['idajuste']); ?>)" title="Cerrar Tanda de Ajuste"><img src="img/1495723082_Close.png" width="32" height="32" alt=""/></a>    <form id="fin" name="fin" action="gest_stock_ajuste.php" method="post">
        <input type="hidden" name="ter" id="ter" value="<?php echo intval($rs->fields['idajuste']); ?>"  />
    
    </form>
          </tr>
        </table><br />
<div id="productos" align="center">
           <span class="resaltaditomenor"><?php echo $orichar ?> Buscar Insumo <?php echo $deschar?></span>
        <br />
        <form id="sc2" name="sc2" action="gest_adm_depositos_mover_tanda.php" method="post" >
        <table width="400">
             <tr>
               <td><input type="text" name="codigoprod" id="codigoprod" style="width:99%; height:40px;" value="<?php echo htmlentities($_POST['codigoprod']);?>" placeholder="codigo" onkeyup="busca_insumo_cod_enter(event)" /></td>
                <td height="28" colspan="2"><input type="text" name="codigop" id="codigop" style="width:99%; height:40px;" value="<?php if ($_POST['codigoprod'] == '') {
                    echo htmlentities($_POST['codigop']);
                } ?>" placeholder="Ingrese producto a buscar" onkeyup="busca_insumo(this.value);"   />
                </td>
                <td width="26"><select name="grupo" required="required" id="grupo" style="width:90%; height:40px;" onchange="busca_insumo_grup(this.value);">
                <option value="0" selected="selected">Seleccionar</option>
                <?php
                $buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
    $gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    while (!$gr->EOF) {?>
                <option value="<?php echo $gr->fields['idgrupoinsu']?>" <?php if ($gr->fields['idgrupoinsu'] == $_GET['gr']) { ?>selected="selected"<?php } ?>><?php echo trim($gr->fields['nombre']) ?></option>
                <?php $gr->MoveNext();
    }?>
              </select></td>    
                </td>
                <td width="26"><a href="javascript:void(0);" onclick="busca_insumo_cod();"><img src="img/buscar.png" width="32" height="32" alt=""/></a>
                    
                </td>
        </tr>
         <tr>
                        <td colspan="5"><input type="text" name="codigobarra" id="codigobarra" style="width:99%; height:40px;" value="<?php echo htmlentities($_POST['codigobar']);?>" placeholder="codigo barras" onkeyup="busca_insumo_cbar(event);"   /></td>
                </td>
         </tr>
        
        
        </table>
        </form>
    </div>
     <br />

     <div align="center"><?php echo $errorcantidaad;?></div>
        <div align="center" id="insumo_box"></div>

    <div id="tmprodusmov" align="center">
    <?php require_once('add_tmp_ajuste.php');?>
    
    </div>
    
    <p>
  <?php  } ?>
<?php if (intval($rs->fields['idajuste']) == 0) { ?>
  <?php if (intval($_GET['t']) > 0) {
      $idajuste = intval($_GET['t']);
      $consulta = "
      select *, (select descripcion from insumos_lista where insumos_lista.idinsumo = gest_depositos_ajustes_stock_det.idinsumo) as insumo,
      (select motivo from gest_depositos_ajustes_stock where gest_depositos_ajustes_stock.idajuste = gest_depositos_ajustes_stock_det.idajuste) as motivo_desc,
      (
      select motivos_ajuste.motivo 
      from gest_depositos_ajustes_stock
      inner join motivos_ajuste on motivos_ajuste.idmotivo = gest_depositos_ajustes_stock.idmotivo
       where 
       gest_depositos_ajustes_stock.idajuste = gest_depositos_ajustes_stock_det.idajuste
       ) as motivo,
                  (
                Select sum(disponible) as disponible 
                from gest_depositos_stock_gral
                 where 
                iddeposito=gest_depositos_ajustes_stock_det.iddeposito
                and gest_depositos_stock_gral.idproducto = gest_depositos_ajustes_stock_det.idinsumo
                and idempresa=$idempresa
                ) as disponible
      from gest_depositos_ajustes_stock_det
      where
      idempresa = $idempresa
      and idajuste = $idajuste
      ";
      $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

      if ($rs->fields['idinsumo'] > 0) {
          ?>
      </p>
    <table width="500" border="1">
      <tbody>
        <tr>
          <td colspan="3" align="center" bgcolor="#F8FFCC">Ajuste Realizado</td>
        </tr>
        <tr>
          <td colspan="3" align="center">Motivo: <?php echo $rs->fields['motivo']; ?></td>
        </tr>
        <tr>
          <td align="center" bgcolor="#CCCCCC">Insumo</td>
          <td width="100" align="center" bgcolor="#CCCCCC">Cantidad Ajustada</td>
          <td width="100" align="center" bgcolor="#CCCCCC">Stock Actual</td>
        </tr>
<?php while (!$rs->EOF) {    ?>
        <tr>
          <td align="center"><?php echo $rs->fields['insumo']; ?></td>
          <td align="center"><?php echo $rs->fields['tipoajuste'].formatomoneda($rs->fields['cantidad_ajuste'], 4, 'N'); ?></td>
          <td align="center"><?php echo formatomoneda($rs->fields['disponible'], 4, 'N'); ?></td>
        </tr>
<?php $rs->MoveNext();
}?>
      </tbody>
    </table>
<?php  } else { ?>
 <p align="center">Error! hubo problema para registrar este ajuste.</p>
<?php  } ?>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>
<?php  } ?>
<?php  } ?>      
      
    </p>
    <p>&nbsp;</p>
          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
