<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $fecha = $_POST['fecha'];

    // Procesa los datos según tus necesidades (guardar en la base de datos, enviar un correo, etc.)
    echo "Nombre: $nombre <br>";
    echo "Correo Electrónico: $email <br>";
    echo "Fecha: $fecha <br>";
}
?>
