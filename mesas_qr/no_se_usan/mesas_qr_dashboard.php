<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");

// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";

if (!isset($_SESSION)) {
    session_start();
}

// Ejemplo de uso
$id_mesa = $_SESSION['id_mesa'];

if ($id_mesa == 0) {
    header("Location: ./mesas_qr.php");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
    function boton1(){
        console.log("boton 1");
    }
    function boton2(){
        console.log("boton 2");
    }
    function boton3(){
        console.log("boton 3");
    }
    function boton4(){
        console.log("boton 4");
    }
</script>
</head>
<body>


<div style="display: flex;
  justify-content: center;">
    
    <div class="btn-group"  role="group" aria-label="Basic example">
      <button type="button" onclick="boton1()" class="btn btn-primary">Left</button>
      <button type="button" onclick="boton2()" class="btn btn-primary">Middle</button>
      <button type="button" onclick="boton3()" class="btn btn-primary">Middle</button>
      <button type="button" onclick="boton4()" class="btn btn-primary">Right</button>
    </div>
</div>
    
</body>
</html>