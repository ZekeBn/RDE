<?php

require("../../clases/phpqrcode/qrlib.php");
// include('../lib/full/qrlib.php');





$dataText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi congue congue accumsan. Suspendisse lacinia non augue nec iaculis. Aliquam pulvinar enim non pulvinar condimentum. Sed tempor id risus sit amet placerat. Nullam viverra sit amet arcu ut pellentesque. Sed scelerisque augue iaculis semper molestie. Vivamus odio nisl, ullamcorper et suscipit eu, molestie in massa. Duis sed aliquet libero. Cras volutpat ante neque, vitae blandit erat scelerisque nec. Nam ut urna sem. Suspendisse metus nulla, molestie eget finibus malesuada, luctus sed neque. In sagittis ligula felis, vel placerat lacus eleifend ut. Nunc placerat a ligula eget vestibulum. Fusce rutrum sem vitae accumsan scelerisque. Maecenas laoreet ex et nisi hendrerit vulputate. Donec rhoncus facilisis eros fermentum pharetra.


";

$saveToFile = false; // nombre del archivo o false para no guardar



/*
// SAVE TO FILE
// TEXT | FILENAME | CORRECT LEVEL | PIXEL PER POINT | OUTER FRAME SIZE | PRINT + SAVE | BACKGROUND COLOR | FOREGROUND COLOR
QRcode::png("https://code-boxx.com", false, 'h', 6, 2, false, 0x000000, 0xFFFFFF);
1. The text you want to encode.
2. The filename to save to, enter false to directly output the image to the browser.
3. Error correction level – L, M, Q, H. (Low, medium, quartile, high)
4. Pixel per point (affects the size of QR code)
5. Outer frame size (size of the border surrounding the QR code)
6. Print and save (true or false). When set to true, it will both save to a file and output as an image.
7. The background color, in RGB hex code.
8. The foreground color, in RGB hex code.
formatos de imagen:
QRcode::svg($dataText, $saveToFile, $saveToFile, QR_ECLEVEL_L, $imageWidth);
QRcode::png($dataText, $saveToFile, QR_ECLEVEL_L, 1200);
*/

$descarga = strtoupper(substr($_GET['desc'], 0, 1));
if ($descarga != 'S') {
    // outputs image directly into browser, as PNG stream
    QRcode::png($dataText, $saveToFile, QR_ECLEVEL_H, 1200);
} else {
    $datetime = date("YmdHis");
    $fichero = 'qr_menu_'.$datetime.'.png';
    QRcode::png($dataText, $fichero, QR_ECLEVEL_H, 1200);


    if (file_exists($fichero)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($fichero).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fichero));
        readfile($fichero);

        // borra el archivo
        unlink($fichero);
        exit;
    } else {
        echo "Hubo un problema y la imagen no se pudo crear, verifique permisos del fichero.";
        exit;
    }


}
