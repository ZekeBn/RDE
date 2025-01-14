 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";

require_once("includes/rsusuario.php");


$consulta = "
select permite_reimpresion_centralped from preferencias_caja  limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$permite_reimpresion_centralped = $rs->fields['permite_reimpresion_centralped'];
if ($permite_reimpresion_centralped != 'S') {
    echo "<hr /><br /><strong style='color:red;'>Reimpresion bloqueada por la administracion.</strong><br /><br />";
    exit;
}







$idped = intval($_GET['idped']);
//echo $idmesa;
if ($idped == 0) {
    echo "Pedido inexistente!";
    exit;
}

// trae la primera impresora
$consulta = "
SELECT * FROM 
impresoratk 
where 
idsucursal = $idsucursal 
and borrado = 'N' 
and tipo_impresora='CAJ' 
order by idimpresoratk  asc
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$idimpresoratk = $rsimp->fields['idimpresoratk'];
if (intval($idimpresoratk) == 0) {
    echo "No existe impresora del tipo caja creada para tu sucursal en gestion > impresoras y pantallas";
    exit;
}
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}

if ($rsco->fields['ticket_fox'] == 'S') {


    $ticket_json = preticket_json($idped);


} else {


    // tipo de impresor
    $impresor_tip = "REI";
    $redir_impr = "impresor_ticket_reimp.php";

    // parametros
    $consolida = 'S';
    $leyenda_credito = $rsimp->fields['leyenda_credito'];
    $datos_fiscal = $rsimp->fields['datos_fiscal'];
    $muestra_nombre = $rsimp->fields['muestra_nombre'];
    $usa_chapa = $rsimp->fields['usa_chapa'];
    $usa_obs = $rsimp->fields['usa_obs'];
    $usa_precio = $rsimp->fields['usa_precio'];
    $usa_total = $rsimp->fields['usa_total'];
    $usa_nombreemp = $rsimp->fields['usa_nombreemp'];
    $usa_totaldiscreto = $rsimp->fields['usa_totaldiscreto'];
    $txt_codvta = $rsimp->fields['txt_codvta'];
    $cabecera_pagina = $rsimp->fields['cabecera_pagina'];
    $pie_pagina = $rsimp->fields['pie_pagina'];
    $usa_enfasis = $rsimp->fields['usa_enfasis'];

    require_once("impresor_motor.php");

}

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Imprimir</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">

function imprime_cliente(){
        //alert('a');
        var texto = document.getElementById("texto").value;
        //alert(texto);
        var parametros = {
                "tk" : texto,
                "tk_json" : '<?php echo $ticket_json; ?>',
                "duplic_control" : 'N'
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora ?>',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
                crossDomain: true,
                success:  function (response) {
                        //$("#impresion_box").html(response);    
                        //si impresion es correcta marcar
                        var str = response;
                        var res = str.substr(0, 18);
                        //alert(res);
                        if(res == 'Impresion Correcta'){
                            //marca_impreso('<?php echo $id; ?>');
                            document.body.innerHTML = "Impresion Enviada!";
                            $('#reimprimebox',window.parent.document).html('');
                        }else{
                            $("#impresion_box").html(response);    
                        }
                        
                        // si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen
                        
                }
        });
    
}

</script>
</head>
<body bgcolor="#CCCCCC" onLoad="imprime_cliente()">
<div style="width:290px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onClick="imprime_cliente();"></p>
</div><br />
<div style="width:290px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea style="display:; width:310px; height:500px;" id="texto"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto;?>
</pre>
</div>
</body>
</html>


