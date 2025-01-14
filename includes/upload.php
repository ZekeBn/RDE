<?php

class Upload
{
    public $maxsize = 5000; //KB
    public $minsize = 100; //KB
    public $message = "";
    public $newfile = "";
    public $newpath = "";
    public $maxwidth = 0; // 0 para no hacer el resize

    public $filesize = 0;
    public $filetype = "";
    public $filename = "";
    public $filetemp;
    public $fileexte;

    public $allowed;
    public $blocked;
    public $isimage;

    public $isupload;


    public function Upload()
    {
        $this->allowed = ["image/bmp","image/gif","image/jpeg","image/pjpeg","image/png","image/x-png","application/pdf","application/x-pdf"];
        $this->blocked = ["php","phtml","php3","php4","js","shtml","pl","py"];
        $this->message = "";
        $this->isupload = false;
    }
    public function setFile($field, $newname)
    {
        $this->filesize = $_FILES[$field]['size'];
        $this->filename = $_FILES[$field]['name'];
        $this->filetemp = $_FILES[$field]['tmp_name'];
        $this->filetype = mime_content_type($this->filetemp);
        $this->fileexte = strtolower(substr($this->filename, strrpos($this->filename, '.') + 1));

        $this->newfile = $newname.".".$this->fileexte;
        //$this->newfile = substr(md5(uniqid(rand())),0,8).".".$this->fileexte;
    }
    public function setPath($value)
    {
        $this->newpath = $value;
    }
    public function setMaxSize($value)
    {
        $this->maxsize = $value;
    }
    public function setMinSize($value)
    {
        $this->minsize = $value;
    }
    public function isImage($value)
    {
        $this->isimage = $value;
    }
    public function isPdf($value)
    {
        $this->ispdf = $value;
    }
    public function setMaxWidth($value)
    {
        $this->maxwidth = $value;
    }
    public function save()
    {
        if (is_uploaded_file($this->filetemp)) {
            // check if file valid
            if ($this->filename == "") {
                $this->message = "No cargaste ningun archivo"; // No file upload
                $this->isupload = false;
                return false;
            }
            // check max size
            if ($this->maxsize != 0) {
                if ($this->filesize > $this->maxsize * 1024) {
                    $this->message = "La imagen es demasiado grande."; //Large File Size
                    $this->isupload = false;
                    return false;
                }
            }
            // check min size
            if ($this->minsize != 0) {
                if ($this->filesize < $this->minsize * 1024) {
                    $this->message = "La imagen es demasiado chica."; //Litle File Size
                    $this->isupload = false;
                    return false;
                }
            }
            // check if image
            if ($this->isimage) {
                // check dimensions
                if (!getimagesize($this->filetemp)) {
                    $this->message = "Archivo de imagen no valido."; // Invalid Image File
                    $this->isupload = false;
                    return false;
                }
                // check content type
                if (!in_array($this->filetype, $this->allowed)) {
                    $this->message = "Tipo de archivo no valido"; // Invalid Content Type
                    $this->isupload = false;
                    return false;
                }
            }
            // check if pdf
            if ($this->ispdf) {
                // check content type
                if (!in_array($this->filetype, $this->allowed)) {
                    $this->message = "Tipo de archivo no valido"; // Invalid Content Type
                    $this->isupload = false;
                    return false;
                }
            }
            // check if file is allowed
            if (in_array($this->fileexte, $this->blocked)) {
                $this->message = "Tipo de archivo no permitido"; //File Not Allowed - .$this->fileexte
                $this->isupload = false;
                return false;
            }
            if (move_uploaded_file($this->filetemp, strtolower($this->newpath."/".$this->newfile))) {
                // if is an image
                if ($this->isimage) {
                    // resize image
                    if ($this->maxwidth > 0) {
                        $this->rutaorig = strtolower($this->newpath."/".$this->newfile);
                        if ($this->fileexte == 'jpg') {
                            $this->img_origen = imagecreatefromjpeg($this->rutaorig);
                        } elseif ($this->fileexte == 'gif') {
                            $this->img_origen = imagecreatefromgif($this->rutaorig);
                        } elseif ($this->fileexte == 'png') {
                            $this->img_origen = imagecreatefrompng($this->rutaorig);
                        }
                        $this->ancho_origen = imagesx($this->img_origen);//se ontiene el ancho de la imagen
                        $this->alto_origen = imagesy($this->img_origen);//se obtiene el alto de la imagen
                        $this->ancho_limite = $this->maxwidth;
                        if ($this->ancho_origen >= $this->alto_origen) { // para fotos horizontales
                            $this->ancho_final = $this->ancho_limite;
                            $this->alto_final = $this->ancho_limite * $this->alto_origen / $this->ancho_origen;
                        } else { //para fotos verticales
                            $this->alto_final = $this->ancho_limite;
                            $this->ancho_final = $this->ancho_limite * $this->ancho_origen / $this->alto_origen;
                        }
                        $this->ancho_final = intval(round($this->ancho_final, 0)); // redondear
                        $this->img_destino = imagecreatetruecolor($this->ancho_final, $this->alto_final); // se crea la imagen segun las dimensiones dadas
                        // copy/resize as usual
                        imagecopyresized($this->img_destino, $this->img_origen, 0, 0, 0, 0, $this->ancho_final, $this->alto_final, $this->ancho_origen, $this->alto_origen);
                        imagejpeg($this->img_destino, $this->rutaorig); //se guarda la nueva foto
                    }
                }
                $this->message = "Archivo Cargado Exitosamente"; // File succesfully uploaded!
                $this->isupload = true;
                return true;
            } else {
                $this->message = "El archivo no se cargo, favor intente nuevamente."; // File was not uploaded please try again
                $this->isupload = false;
                return false;
            }
        } else {
            $this->message = "El archivo no se cargo, favor intente nuevamente."; //File was not uploaded please try again
            $this->isupload = false;
            return false;
        }
    }
}
