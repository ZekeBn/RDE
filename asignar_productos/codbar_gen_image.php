<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
require_once("../../clases/php-barcode/src/BarcodeBar.php");
require_once("../../clases/php-barcode/src/Barcode.php");
require_once("../../clases/php-barcode/src/Types/TypeInterface.php");
require_once("../../clases/php-barcode/src/Types/TypeCode128.php");
require_once("../../clases/php-barcode/src/BarcodeGenerator.php");
require_once("../../clases/php-barcode/src/BarcodeGeneratorHTML.php");
require_once("../../clases/php-barcode/src/BarcodeGeneratorPNG.php");


$redColor = [0, 0, 0];
$codbar = $_POST['codbar'];
$generator = new Picqer\Barcode\BarcodeGeneratorPNG();
?>
<script>
     function descargar_imagen() {
        var img = document.getElementById('codigo_de_barras_png');
        var link = document.createElement('a');
        link.href = img.src;
        link.download = '<?php echo $codbar != "" ? $codbar : "Sin_codbar"?>.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
<div style="color:#fff;">.</div>
<?php if ($codbar != "" && $codbar != null) { ?>
    <div class="div_codbar">
        <h2>Codigo de Barras Generado</h2>
        <img class="img-fluid img-thumbnail " id="codigo_de_barras_png" src="data:image/png;base64,<?php echo base64_encode($generator->getBarcode("$codbar", $generator::TYPE_CODE_128)) ?>" >
        <div class="row" style="display:flex;justify-content:center;">
            <?php echo $codbar; ?>
        </div>
        <div class="row" style="display:flex;justify-content:center;">
            <a href="javascript:void(0)" onclick="descargar_imagen()" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Descargar PNG</a>
        </div>
    </div>
<?php } ?>
