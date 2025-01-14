 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "278";
require_once("includes/rsusuario.php");

require_once("includes/funciones_cobros.php");

$script_impresora = "http://localhost/impresorweb/ladocliente.php";
$idcuentaclientepagcab = intval($_GET['id']);


$consulta = "
select * 
from cuentas_clientes_pagos_cab 
where 
idcuentaclientepagcab = $idcuentaclientepagcab
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcuentaclientepagcab = $rs->fields['idcuentaclientepagcab'];
$idpago_afavor = intval($rs->fields['idpago_afavor']);

$anticipo_redir = substr(strtoupper(trim($_GET['anticipo_redir'])), 0, 1);
if ($anticipo_redir == 'S') {
    $url1 = "pagos_afavor_adh_det.php?id=".$idpago_afavor;
} else {
    $url1 = "cobro_cuentas.php";
}

$texto = recibo_pago($idcuentaclientepagcab);


?><html>
<head>
<script src="js/jquery-1.10.2.min.js"></script>
<meta charset="utf-8">
<title>Impresiones de Recibos</title>
<script>
function imprime_cliente(){
        //alert('a');
        var texto = document.getElementById("texto").value;
        var redirbtn = "<a href='cobro_cuentas.php'>[Cancelar Impresion]</a><br /><br />";
        //alert(texto);
        var parametros = {
                "tk"      : texto,
                'tk_json' : '<?php echo $texto_json; ?>'
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora ?>',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion Ticket...<br /><br />"+redirbtn);
                },
                crossDomain: true,
                success:  function (response) {    

                        $("#impresion_box").html(response);    
                        document.location.href='<?php echo $url1; ?>';
                        
                }
        });
    
}    
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
// ejecutar al cargar la pagina
$( document ).ready(function() {
    imprime_cliente();
    setTimeout(
        function(){
            //document.location.href='<?php echo $url1; ?>';
        },
        1000
    );

});
</script>
</head>
<body>
<textarea name="texto" id="texto" style="display: none"><?php echo $texto; ?></textarea>

Imprimiendo...<br />

<div id="impresion_box"></div>

</body>
</html>
