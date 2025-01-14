<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");

// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";


$id_mesa = $_GET['id_mesa'];
if ($id_mesa == 0) {
    header("Location: ./mesas_qr_error.php");
}

$idmozo = $_GET['idmozo'];
if ($idmozo == 0) {
    header("Location: ./mesas_qr_error.php");
}
$token = $_GET['token'];
if ($token == "") {
    header("Location: ./mesas_qr_error.php");
}

$parametros_array = [
  "id_mesa" => $id_mesa
];
$rs_mesas_atc = buscar_mesa_atc($parametros_array);
$idatc = intval($rs_mesas_atc['idatc']);
if ($idatc == 0) {
    header("Location: ./mesas_qr_error.php");
}

$logo = "./logo.png";

$logo = "data:image/png;base64,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";

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
    
    function reload_buttons(){
      var direccion="mesas_qr_dashboard_mozo_grilla_btn.php";
      var parametros = {
            "id_mesa" : <?php echo $id_mesa;?>
      };
      // parametros = JSON.stringify(parametros);
      var a;
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
                  console.log(response);
                  $("#box_grilla_btn").html(response);
                  a=response;
                }
        });	
    }

    function finalizar_pedido_mesero(){
      var direccion="mesas_qr_finalizar_pedido_mozo.php";
      var parametros = {
            "id_mesa"   : <?php echo $id_mesa ;?>,
            "tipo_pedido" : 1,
            "idmozo" : <?php echo $idmozo;?>
      };
      // parametros = JSON.stringify(parametros);
      var a;
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
                  console.log(response);
                  reload_buttons();
                }
        });	
    }
  
    
    
    function finalizar_pedir_cuenta(){
      var direccion="mesas_qr_finalizar_pedido_mozo.php";
      var parametros = {
            "id_mesa"   : <?php echo $id_mesa ;?>,
            "tipo_pedido" : 2,
            "idmozo" : <?php echo $idmozo;?>

        };
      // parametros = JSON.stringify(parametros);
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
                  console.log(response);
                  reload_buttons();

                }
        });	
    }
    function agregarSombra(tipo) {
      // tipo 1 solicitar mesero 2 visualizar peiddo 3 pedir cuenta 
      // mesero pedidos pedir_cuenta
      switch (tipo) {
        case 1:
          break;
        case 2:
          break;
        case 3:
          break;
        case 4:
          finalizar_pedido_mesero();
          break;
        case 5:
          break;
        case 6:
          finalizar_pedir_cuenta();
          break;
        default:
          console.log("Opción no válida");
        }
        
    }
    function salir(){
      var direccion="mesas_qr_logout.php";
      var parametros = {
            "reload" : 1
      };
      // parametros = JSON.stringify(parametros);
      var a;
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
					        document.location.href='mesas_qr_error.php';
                 
                }
        });	
    }
</script>
<style>
 
  .activo{
    background: #70AE6E !important;
  }
  .border_shadow {
      box-shadow: 0px 0px 10px 3px rgba(0,0,0,0.5) !important;
  }
  body{
      background:#c2c2c2;
  }
  .white_text i,
    .white_text h3,
    .white_text p {
        color: white;
    }

    .black_text i{
      color: #4B5563;

    }
    .black_text h3{
        color: #4B5563;

    }
    .black_text p {
        color: #4B5563;
    }
</style>
</head>
<body class="bg-gray-100">


<div class="bg-gray-100">

    <div class="h-screen flex overflow-hidden bg-gray-200">
        <!-- Sidebar -->
        <div class="absolute bg-gray-800 text-white w-56 min-h-screen overflow-y-auto transition-transform transform -translate-x-full ease-in-out duration-300"
            id="sidebar">
            <!-- Your Sidebar Content -->
            <div class="p-4">
                <h1 class="text-2xl font-semibold">Acciones</h1>
                <ul class="mt-4">
                    <li class="mb-2"><a href="#" class="block hover:text-indigo-400" onclick="salir()">Salir</a></li>
                </ul>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <div class="bg-white shadow">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center py-4 px-2">
                        <img class="rounded-t shadow-lg resized-image h-8 w-8" src="<?php echo $logo;?>"  alt="Pay background" />

                        <h1 class="text-xl font-semibold">Bar Leo</h1>

                        <button class="text-gray-500 hover:text-gray-600" id="open-sidebar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    </div>
                </div>
            </div>
            <!-- Content Body -->
            
<div id="box_grilla_btn">
  <?php require_once("mesas_qr_dashboard_mozo_grilla_btn.php");?>
</div>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
    const openSidebarButton = document.getElementById('open-sidebar');
    
    openSidebarButton.addEventListener('click', (e) => {
        e.stopPropagation();
        sidebar.classList.toggle('-translate-x-full');
    });

    // Close the sidebar when clicking outside of it
    document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !openSidebarButton.contains(e.target)) {
            sidebar.classList.add('-translate-x-full');
        }
    });
    </script>

</div>




    <!-- Main navigation container -->




    
<?php require_once("../includes/footer_gen.php"); ?>
    
</body>
</html>