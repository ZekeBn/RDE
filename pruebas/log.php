<?php

$myfile = fopen("log.txt", "a") or die("Unable to open file!");

fwrite($myfile, " escribiendo ");
fwrite($myfile, date("Y-m-d H:i:s"));
fwrite($myfile, "\n");
fclose($myfile);
