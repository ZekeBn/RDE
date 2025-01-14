<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
$errores = "";

if (isset($_GET['id_mesa'])) {
    $id_mesa = $_GET['id_mesa'];
} else {
    $id_mesa = 0;
}
$id_mesa = intval($id_mesa);
$id_mesa = antisqlinyeccion($id_mesa, 'int');

if ($id_mesa != 0) {
    $parametros_array = [
        "id_mesa" => $id_mesa
    ];
    $rs_mesas_atc = buscar_mesa_atc($parametros_array);

    $idatc = intval($rs_mesas_atc['idatc']);
    $pin_atc = $rs_mesas_atc['pin'];

    if ($idatc != 0) {

        if ($preferencia_usa_pin == "S") {

            //verifica sesion  si no tiene nada entonces realiza el proceso

            if (($_SESSION['pin_atc_cliente']) != "" && isset($_SESSION['pin_atc_cliente'])) {
                // La sesión está activa y la variable 'pin_atc_cliente' está establecida
                $pin_atc_cliente = $_SESSION['pin_atc_cliente'];
                if ($pin_atc == $pin_atc_cliente) {
                    // redirigir a la botonera
                    header("Location: ./dashboard_bootstrap.php");

                } else {
                    // error la contraseña fue cambiada preguntar al mozo
                    $errores .= "Tu contraseña ha sido modificada. Por favor, consulta al personal.<br />";
                }
            } else {
                if ($preferencia_cliente_gen_pin == "S") {
                    if ($pin_atc == "") {
                        $uuid_cliente = generarUUID20();
                        $parametros_array = [
                            "idatc" => $idatc,
                            "id_mesa" => $id_mesa,
                            "pin" => $uuid_cliente
                        ];
                        update_mesa_atc_pin($parametros_array);
                        if (!isset($_SESSION)) {
                            session_start();
                        }
                        session_regenerate_id(true);
                        $_SESSION['pin_atc_cliente'] = $uuid_cliente;
                        $_SESSION['id_mesa'] = $id_mesa;


                        //redirigir a la botonera
                        header("Location: ./dashboard_bootstrap.php");

                    } else {
                        // echo "el pin ya fue creado";
                        // echo $pin_atc;

                        //esperar al form de abajo  porque ya se verifico la sesion antes
                    }
                } elseif ($preferencia_cliente_gen_pin == "N") {
                    if ($pin_atc == "") {
                        // esperaa al form de abajo pero el mozo no creo el pin preguntar al encargado
                    } else {
                        // esperaa al form de abajo pero el mozo ya creo el pin preguntar al encargado

                    }
                }
            }
        } else {
            $_SESSION['id_mesa'] = $id_mesa;
            header("Location: ./dashboard_bootstrap.php");
        }
    } else {
        $errores .= "Disculpe, la mesa no está activa en este momento. Por favor, espere mientras el personal la habilita para usted.<br />";

    }
} else {
    $errores .= "Lo sentimos, ha ocurrido un error. Por favor, escanee nuevamente el código QR.<br />";

}
if (isset($_POST['MM_mesas_qr']) && $_POST['MM_mesas_qr'] == 'form1') {


    // validaciones basicas
    $valido = "S";
    $errores = "";
    // echo json_encode($_POST);exit;
    // control de formularios, seguridad para evitar doble envio y ataques via bots
    // if($_SESSION['form_control'] != $_POST['form_control']){
    // 	$errores.="- Se detecto un intento de envio doble, recargue la pagina.<br />";
    // 	$valido="N";
    // }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots
    $pin_form = antisqlinyeccion($_POST['pin'], "text");
    $pin_form = str_replace("'", "", $pin_form);


    $parametros_array = [
        "id_mesa" => $id_mesa
    ];
    $rs_mesas_atc = buscar_mesa_atc($parametros_array);

    $idatc = intval($rs_mesas_atc['idatc']);
    $pin_atc = $rs_mesas_atc['pin'];


    if ($pin_form == $pin_atc) {


        // redirigir a la botonera
        $_SESSION['pin_atc_cliente'] = $pin_form;
        $_SESSION['id_mesa'] = $id_mesa;
        header("location: ./dashboard_bootstrap.php");
        exit;
    }
}

$image = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTo7yWfYGoEDh89FuW50wkkudLFd-eDWpkWXg&usqp=CAU";
$logo = "data:image/png;base64,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";

$_SESSION['form_control'] = md5(rand());

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


<script>
        tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {}
        }
        }
</script>
<style>
    .resized-image {
    width: 100%; /* Nuevo ancho deseado */
  
}

</style>
<script>
     function cerrar_alert(){
        var elemento = document.getElementById('alerta_error');
        elemento.classList.toggle('hidden');
    }
</script>
</head>
<body>
    <!-- /////////////////////////////////////////////////////////////////////// -->



    <!-- source:https://codepen.io/owaiswiz/pen/jOPvEPB -->
<div class="min-h-screen bg-gray-100 text-gray-900 flex justify-center">
    <div class="w-screen h-screen flex justify-center flex-1">
        <div class="lg:w-1/2 xl:w-5/12 p-6 sm:p-12">
        <div class="relative px-4 sm:px-6 lg:px-8 max-w-lg mx-auto">
                <img class="rounded-t shadow-lg resized-image" src="<?php echo $image;?>"  alt="Pay background" />
            </div>
            <div class="relative px-4 sm:px-6 lg:px-8 pb-8 max-w-lg mx-auto" >
                <div class="bg-white px-8 pb-6 rounded-b shadow-lg">

                    <!-- Card header -->
                    <div class="text-center mb-6">
                        <div class="mb-2">
                            <img class="-mt-8 inline-flex rounded-full" src="<?php echo $logo;?>" width="64" height="64" alt="User" />
                        </div>
                        <h1 class="text-xl leading-snug text-gray-800 font-semibold mb-2">¡Bienvenido!</h1>
                        <div class="text-sm">
                         Para acceder al menú, simplemente haz clic sobre la imagen circular.
                        </div>
                    </div>

                   

                    <!-- Card form -->
                    <form id="MM_mesas_qr" name="MM_mesas_qr" method="post" action="">
                            <div class="space-y-4">
                                <!-- Card Number -->
                                <div>
                                    <label class="block text-sm font-medium mb-1" for="pin">Contraseña <span class="text-red-500">*</span></label>
                                    <input  class="text-sm text-gray-800 bg-white border rounded leading-5 py-2 px-3 border-gray-200 hover:border-gray-300 focus:border-indigo-300 shadow-sm placeholder-gray-400 focus:ring-0 w-full" id="pin" name="pin" type="password" placeholder="*******" />
                                </div>
                        
                            </div>
                            <!-- Form footer -->
                            <div class="mt-6">
                                <div class="mb-4">
                                    <button class="font-medium text-sm inline-flex items-center justify-center px-3 py-2 border border-transparent rounded leading-5 shadow-sm transition duration-150 ease-in-out w-full bg-indigo-500 hover:bg-indigo-600 text-white focus:outline-none focus-visible:ring-2" type="submit">Ingresar</button>
                                </div>
                                <input type="hidden" name="MM_mesas_qr" value="form1" />
                                <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
                            </div>
                    </form>

                   

                </div>
            </div>
        </div>
        
    </div>
</div>



<!-- More components -->
<?php if ($errores != "") { ?>
<div id="alerta_error" x-show="open" class="fixed bottom-0 right-0 w-full md:bottom-8 md:right-12 md:w-auto z-60" x-data="{ open: false }">
    <div class="bg-gray-800 text-gray-50 text-sm p-3 md:rounded shadow-lg flex justify-between">
        <div><?php echo $errores; ?></div>
        <button class="text-gray-500 hover:text-gray-400 ml-5" onclick="cerrar_alert()" >
            <span class="sr-only">Close</span>
            <svg class="w-4 h-4 flex-shrink-0 fill-current" viewBox="0 0 16 16">
                <path d="M12.72 3.293a1 1 0 00-1.415 0L8.012 6.586 4.72 3.293a1 1 0 00-1.414 1.414L6.598 8l-3.293 3.293a1 1 0 101.414 1.414l3.293-3.293 3.293 3.293a1 1 0 001.414-1.414L9.426 8l3.293-3.293a1 1 0 000-1.414z" />
            </svg>
        </button>
    </div>
</div>
<?php } ?>
    
    
</body>
</html>