<?php
if ($dirsup == "S") {
    $dirini = '../';
} elseif ($dirsup_sec == "S") {
    $dirini = '../../';
} else {
    $dirini = '';
}
?>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (trim($title_personaliza) == '') { ?>
	<title><?php echo "INNOVASYS" ?> - <?php echo $nombreempresa; ?></title>
<?php } else { ?>
    <title><?php echo $title_personaliza; ?></title>
<?php } ?>
    <!-- Bootstrap -->
    <link href="<?php echo $dirini; ?>vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?php echo $dirini; ?>vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="<?php echo $dirini; ?>vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- PNotify -->
    <link href="<?php echo $dirini; ?>vendors/pnotify/dist/pnotify.css" rel="stylesheet">
    <link href="<?php echo $dirini; ?>vendors/pnotify/dist/pnotify.buttons.css" rel="stylesheet">
    <link href="<?php echo $dirini; ?>vendors/pnotify/dist/pnotify.nonblock.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <!-- Nuestros CSS -->
    
<!-- Favicon -->
<link rel="shortcut icon" href="<?php echo $dirini; ?>favicon.ico" >
<!-- Favicon -->