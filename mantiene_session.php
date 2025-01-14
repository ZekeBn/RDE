 <?php
// En PHP  gc_maxlifetime usa segundos
// 8*60*60 = 8 hours
// (1440 seconds = 24 minutes)
// 86400 = 24 horas

//En Javascript setinverval usa milisegundos
// 60000 = 1 minuto
// 600000 = 10 minutos
// 1200000 = 20 minutos
// 3600000 = 1 hora
ini_set('session.gc_maxlifetime', 28800); // 8 horas 28800 segundos
//ini_set('session.gc_maxlifetime',86400); // segundos

if (!isset($_SESSION)) {
    session_start();
}

if ($_POST['ses'] != '') {
    //echo substr(htmlentities($_POST['ses']),0,100);

}

?>
