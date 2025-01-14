 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");


$idsalon = intval($_GET['salon']);

$consulta = "
select * from mesas
inner join salon on mesas.idsalon = salon.idsalon
where
mesas.idsalon = $idsalon
and salon.idsucursal = $idsucursal
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rs->fields['idmesa']) == 0) {
    echo "No existen mesas en este salon, agregue mesas para ver el mapa.";
    exit;
}
//print_r($_POST);

$img = "gfx/salones/sal_".$idsalon.".jpg";
if (!file_exists($img)) {
    $img = "gfx/salones/sal.jpg";
}

// recorrer mesas y guardar nuevas posiciones
if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {
    while (!$rs->EOF) {
        $idmesa = $rs->fields['idmesa'];
        if ($idmesa >= 0) {

            // asigna valores
            $position_top = intval($_POST['mesat_'.$idmesa]);
            $position_left = intval($_POST['mesal_'.$idmesa]);

            //echo $_POST['mesat_$idmesa'];

            //validar
            $valido = "S";
            if (trim($_POST['mesal_'.$idmesa]) == '' or $_POST['mesal_'.$idmesa] < 0) {
                $valido = "N";
            }
            if (trim($_POST['mesat_'.$idmesa]) == '' or $_POST['mesat_'.$idmesa] < 0) {
                $valido = "N";
            }


            if ($valido == 'S') {
                $consulta = "
                update mesas 
                set 
                position_top = $position_top,
                position_left = $position_left
                where
                idmesa = $idmesa
                ";
                //echo $consulta;
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                header("location: mesas.php?salon=$idsalon&guardado=s");
            }
        }
        $rs->MoveNext();
    }
}

//print_r($_POST);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<?php require("includes/head.php"); ?>
<script src="js/jquery.ui.touch-punch.min.js"></script>
<style>
.mapa_mesas{
    background-color:#ADADAD;
    width:980px;
    height:600px;
    background-image:url(<?php echo $img; ?>);    
    float:left;
}
.menu_mesas{
    background-color:#FFFFFF;
    width:980px;
    height:100px;
    float:left;
    border:0px solid #000;
    text-align:left;
}
.mesa{
    width:120px;
    height:120px;
    line-height:120px;
    background-image:url(img/mesa.fw.png);    
    /*background-color:#FF0000;*/
    background-repeat:no-repeat;
    background-size:contain;
    cursor:pointer;
    text-align:center;
    font-weight:bold;
}
.mesamovdiv{
    float:right; 
    width:100px; 
    height:100px; 
    /*border:1px solid #000; */
    line-height:100px; 
    text-align:center;    
    background-image:url(img/guardar.jpg);    
    background-repeat:no-repeat;
    background-size:contain;
}
.mesamovdivedit{
    float:right; 
    width:100px; 
    height:100px; 
    /*border:1px solid #000; */
    line-height:100px; 
    text-align:center;    
    background-image:url(img/editar.png);    
    background-repeat:no-repeat;
    background-size:contain;
}
.volverdiv{
    float:right; 
    width:100px; 
    height:100px; 
    /*border:1px solid #000; */
    line-height:100px; 
    text-align:center;    
    background-image:url(img/volveratras.png);    
    background-repeat:no-repeat;
    background-size:contain;
}
.basurero{
    background-color:#FFF;
    width:100px;
    height:100px;    
    /*border:1px solid #000;*/
    /*margin:0px auto;*/
    background-image:url(img/papelera.png);    
    background-repeat:no-repeat;
    background-size:contain;
    float:left;
}
</style>
<script>
<?php if (!($_GET['guardado'] == 's')) { ?>
$(function() {
<?php
$rs->MoveFirst();
    while (!$rs->EOF) { ?>
    $("#mesa_<?php echo $rs->fields['idmesa']; ?>").draggable();
<?php $rs->MoveNext();
    } ?>
});    
<?php
    $rs->MoveFirst();
    while (!$rs->EOF) { ?>
$(function() {
    $("#mesa_<?php echo $rs->fields['idmesa']; ?>").mousedown(function() {
        $("#mesamov").val(<?php echo $rs->fields['idmesa']; ?>);
    })
    $("#mesa_<?php echo $rs->fields['idmesa']; ?>").mouseup(function() {
        var mesa<?php echo $rs->fields['idmesa']; ?> = $( "#mesa_<?php echo $rs->fields['idmesa']; ?>" );
        var position = mesa<?php echo $rs->fields['idmesa']; ?>.position();
        //$("#posicion").text( "left: " + position.left + ", top: " + position.top );
        $("#mesal_<?php echo $rs->fields['idmesa']; ?>").val(position.left);
        $("#mesat_<?php echo $rs->fields['idmesa']; ?>").val(position.top);
        
    })
});    
<?php $rs->MoveNext();
    } ?>
$(function() {
    $( "#droppable" ).droppable({
                drop: function( event, ui ) {
                  $( this )
                    var valor = $("#mesamov").val();
                    $("#mesa_"+valor).remove();
                }
    });
});
// obtener posicion
$(function() {
<?php
    $rs->MoveFirst();
    while (!$rs->EOF) { ?>
    var mesa<?php echo $rs->fields['idmesa']; ?> = $( "#mesa_<?php echo $rs->fields['idmesa']; ?>" );
    var position = mesa<?php echo $rs->fields['idmesa']; ?>.position();
    $("#mesal_<?php echo $rs->fields['idmesa']; ?>").val(position.left);
    $("#mesat_<?php echo $rs->fields['idmesa']; ?>").val(position.top);
<?php $rs->MoveNext();
    } ?>
});    
<?php } ?>
// establecer posiciones si hay datos guardados
$(function() {
<?php
    $rs->MoveFirst();
while (!$rs->EOF) {
    // busca si tiene valores guardados, si no tiene pone el valor por defecto
    $position_top = $rs->fields['position_top'];
    $position_left = $rs->fields['position_left'];
    if (trim($position_top) == '') {
        $position_top = 215;
    }
    if (trim($position_left) == '') {
        $position_left = 409;
    }
    ?>
    var mesa<?php echo $rs->fields['idmesa']; ?> = $( "#mesa_<?php echo $rs->fields['idmesa']; ?>" );
    var offset = mesa<?php echo $rs->fields['idmesa']; ?>.offset();
    $("#mesa_<?php echo $rs->fields['idmesa']; ?>").offset({ top: <?php echo $position_top; ?>, left: <?php echo $position_left; ?> });
    // guardar posiciones
    var mesa<?php echo $rs->fields['idmesa']; ?> = $( "#mesa_<?php echo $rs->fields['idmesa']; ?>" );
    var position = mesa<?php echo $rs->fields['idmesa']; ?>.position();
    $("#mesal_<?php echo $rs->fields['idmesa']; ?>").val(position.left);
    $("#mesat_<?php echo $rs->fields['idmesa']; ?>").val(position.top);
<?php $rs->MoveNext();
} ?>
});    
</script>
</head>
<body bgcolor="#FFFFFF" style="background-image:none;<?php if ($_GET['guardado'] == 's') { ?> background-color:#505050;<?php } ?>">
<div class="mapa_mesas">
<?php
$rs->MoveFirst();
while (!$rs->EOF) { ?>
    <div class="mesa" id="mesa_<?php echo $rs->fields['idmesa']; ?>">Mesa <?php echo $rs->fields['numero_mesa']; ?></div>
<?php $rs->MoveNext();
} ?>
</div>
<div class="clear"></div>
<div class="menu_mesas">
    <?php if (!($_GET['guardado'] == 's')) { ?>
    <div id="mesamovdiv" class="mesamovdiv" onmouseup="document.getElementById('guardador').submit();"></div>
    <?php } else { ?>
    <div id="mesamovdiv" class="mesamovdivedit" onmouseup="document.location.href='mesas.php?salon=<?php echo $idsalon;  ?>';"></div>
    <?php } ?>
    <div class="volverdiv" onmouseup="document.location.href='salones.php';"></div>
    <?php if ($_GET['guardado'] == 's') { ?><div class="guardadodiv"><br /><br /><br /><h1>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CAMBIOS GUARDADOS!!!</h1></div><?php } ?>
    <!--<div class="basurero" id="droppable"></div>-->
    <input type="hidden" id="mesamov" value="" size="5" maxlength="5" width="10"/>
</div>
<div id="posicion"></div>
<div id="evento"></div>
<form id="guardador" action="mesas.php?salon=<?php echo $idsalon;  ?>" method="post">
<?php
$rs->MoveFirst();
while (!$rs->EOF) { ?>
<input type="hidden" name="mesal_<?php echo $rs->fields['idmesa']; ?>" id="mesal_<?php echo $rs->fields['idmesa']; ?>" value="" />
<input type="hidden" name="mesat_<?php echo $rs->fields['idmesa']; ?>" id="mesat_<?php echo $rs->fields['idmesa']; ?>" value="" />
<?php $rs->MoveNext();
} ?><input type="hidden" name="MM_update" value="form1" />
</form>
</body>
</html>
