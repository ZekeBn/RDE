<?php

// eliminando las variables de sesion creadas
$_SESSION['usuoper'] = null;
$_SESSION['claveoper'] = null;
$_SESSION['sucursal'] = null;
unset($_SESSION['usuoper']);
unset($_SESSION['claveoper']);
unset($_SESSION['sucursal']);

/*
// Destruye todas las variables de la sesion
if(!isset($_SESSION)){
session_unset();
}

// Finalmente, destruye la sesion
if(!isset($_SESSION)){
session_destroy();
}*/
