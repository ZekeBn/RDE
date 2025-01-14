<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
   
    <script src="./public/tailwinds.js"></script>
    <link rel="stylesheet" href="./public/tailwinds.min.css">
    
   <script>
        tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {}
        }
        }
   
</script>
<style>
 
 @import url('https://fonts.googleapis.com/css?family=Abril+Fatface|Lato');
 body {
        background: #d3deea;
    }
 .top {
	 margin-top: 30px;
    }
 .container {
	 margin: 0 auto;
	 position: relative;
	 width: 250px;
	 height: 250px;
	 margin-top: -40px;
}
 .ghost {
	 width: 50%;
	 height: 53%;
	 left: 25%;
	 top: 10%;
	 position: absolute;
	 border-radius: 50% 50% 0 0;
	 background: #ededed;
	 border: 1px solid #bfc0c0;
	 border-bottom: none;
	 animation: float 2s ease-out infinite;
}
 .ghost-copy {
	 width: 50%;
	 height: 53%;
	 left: 25%;
	 top: 10%;
	 position: absolute;
	 border-radius: 50% 50% 0 0;
	 background: #ededed;
	 border: 1px solid #bfc0c0;
	 border-bottom: none;
	 animation: float 2s ease-out infinite;
	 z-index: 0;
}
 .face {
	 position: absolute;
	 width: 100%;
	 height: 60%;
	 top: 20%;
}
 .eye, .eye-right {
	 position: absolute;
	 background: #585959;
	 width: 13px;
	 height: 13px;
	 border-radius: 50%;
	 top: 40%;
}
 .eye {
	 left: 25%;
}
 .eye-right {
	 right: 25%;
}
 .mouth {
	 position: absolute;
	 top: 50%;
	 left: 45%;
	 width: 10px;
	 height: 10px;
	 border: 3px solid;
	 border-radius: 50%;
	 border-color: transparent #585959 #585959 transparent;
	 transform: rotate(45deg);
}
 .one, .two, .three, .four {
	 position: absolute;
	 background: #ededed;
	 top: 85%;
	 width: 25%;
	 height: 23%;
	 border: 1px solid #bfc0c0;
	 z-index: 0;
}
 .one {
	 border-radius: 0 0 100% 30%;
	 left: -1px;
}
 .two {
	 left: 23%;
	 border-radius: 0 0 50% 50%;
}
 .three {
	 left: 50%;
	 border-radius: 0 0 50% 50%;
}
 .four {
	 left: 74.5%;
	 border-radius: 0 0 30% 100%;
}
 .shadow {
	 position: absolute;
	 width: 30%;
	 height: 7%;
	 background: #bfc0c0;
	 left: 35%;
	 top: 80%;
	 border-radius: 50%;
	 animation: scale 2s infinite;
}
 @keyframes scale {
	 0% {
		 transform: scale(1);
	}
	 50% {
		 transform: scale(1.1);
	}
	 100% {
		 transform: scale(1);
	}
}
 @keyframes float {
	 50% {
		 transform: translateY(15px);
	}
}
 .bottom {
	 margin-top: 10px;
}
/*text styling*/
 h1 {
	 font-family: 'Abril Fatface', serif;
	 color: #ededed;
	 text-align: center;
	 font-size: 9em;
	 margin: 0;
	 text-shadow: -1px 0 #bfc0c0, 0 1px #bfc0c0, 1px 0 #bfc0c0, 0 -1px #bfc0c0;
}
 h3 {
	 font-family: 'Lato', sans-serif;
	 font-size: 2em;
	 text-transform: uppercase;
	 text-align: center;
	 color: #bfc0c0;
	 margin-top: -20px;
	 font-weight: 900;
}
 p {
	 text-align: center;
	 font-family: 'Lato', sans-serif;
	 color: #585959;
	 font-size: 0.6em;
	 margin-top: -20px;
	 text-transform: uppercase;
}
 .search {
	 text-align: center;
}
 .buttons {
	 display: flex;
	 align-items: center;
	 justify-content: center;
	 margin-top: 10px;
}
/*search style*/
 .search-bar {
	 border: 1px solid #bfc0c0;
	 padding: 5px;
	 height: 20px;
	 margin-left: -30px;
	 width: 200px;
	 outline: none;
}
 .search-bar:focus {
	 border: 1px solid #d3deea;
}
 .search-btn {
	 position: absolute;
	 width: 30px;
	 height: 32px;
	 border: 1px solid #bfc0c0;
	 background: #bfc0c0;
	 text-align: center;
	 color: #ededed;
	 cursor: pointer;
	 font-size: 1em;
	 outline: none;
}
 .search-btn:hover {
	 background: #ededed;
	 border: 1px solid #ededed;
	 color: #bfc0c0;
	 transition: all 0.2s ease;
}
 .btn {
	 background: #ededed;
	 padding: 15px 20px;
	 margin: 5px;
	 color: #585959;
	 font-family: 'Lato', sans-serif;
	 text-transform: uppercase;
	 font-size: 0.6em;
	 letter-spacing: 1px;
	 border: 0;
}
 .btn:hover {
	 background: #bfc0c0;
	 transition: all 0.4s ease-out;
}
 footer {
	 position: absolute;
	 bottom: 0;
	 right: 0;
	 text-align: center;
	 font-size: 0.8em;
	 text-transform: uppercase;
	 padding: 10px;
	 color: #ea7996;
	 letter-spacing: 3px;
	 font-family: 'Lato', sans-serif;
}
 footer a {
	 color: #fff;
	 text-decoration: none;
}
 footer a:hover {
	 color: #7d7d7d;
}

@media (max-width: 600px) {
    h1 {
        font-family: 'Abril Fatface', serif;
        color: #ededed;
        text-align: center;
        font-size: 4em;
        margin: 0;
        text-shadow: -1px 0 #bfc0c0, 0 1px #bfc0c0, 1px 0 #bfc0c0, 0 -1px #bfc0c0;
    }
    
}
 
</style>
</head>
<body >
<div id="background"></div>
<div class="top">
  <h1>Error</h1>
  <h3></h3>
</div>
<div class="container">
  <div class="ghost-copy">
    <div class="one"></div>
    <div class="two"></div>
    <div class="three"></div>
    <div class="four"></div>
  </div>
  <div class="ghost">
    <div class="face">
      <div class="eye"></div>
      <div class="eye-right"></div>
      <div class="mouth"></div>
    </div>
  </div>
  <div class="shadow"></div>
</div>
<div class="bottom">
  <p>Buuh, parece que un error inesperado ha aparecido de la nada!</p>
  
  
</div>





    <!-- Main navigation container -->




    
<?php require_once("../includes/footer_gen.php"); ?>
    
</body>
</html>