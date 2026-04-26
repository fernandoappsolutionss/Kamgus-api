<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Videograph Template">
    <meta name="keywords" content="Videograph, unica, creative, html">
    <meta name="viewport" content="width=device-width, user-scalable=no initial-scale=1.0,
    maximun-scale=1.0, minimun-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bienvenido</title>
    <!-- Css Styles -->

    <link href="HTTPS://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" 
    rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" 
    crossorigin="anonymous"> 

    <link rel="stylesheet" href="./index/index.css">
    <link rel="stylesheet" href="./index/Whatsapp.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glider-js@1.7.3/glider.min.css">

    <style>
        .contenedor{
            height: 100vh;
        }
        .imgPrincipal{
            width: 100%;
            height: 50%;
        }
    
        .boxGris{
            background:#ececec;
            width: 80%;
            height: 15%;
            margin: 0 auto;
            padding-top:3%; 
        }
        .boxGris h2{
            color:#7f8085;
            font-weight: bold;
            text-align: center;
        }
        .boxGris h4{
            color:#7f8085;
            text-align: center;
        }
    
        .boton{
            border: solid 5px #2c24e4;
            border-radius: 30px;
            width: 40%;
            margin-left:30%;
            margin-top:5%
        }
    
        
        .boton h1{
            color: #2c24e4;
            font-weight: bold;
        }
    
        .PiePagina{
            background:#2c24e4;
            width: 100%;
            position: absolute;
            bottom: 0;
            padding: 25px 0;
            justify-content: space-between;
        }
    
        .textW{
            color:white;
            font-weight: 400;
            justify-content: justify;
            width: 30%;
            
        }
    
        .Redes{
            width: 20%;
        }
    
        .redesSociales{
            margin-top:15px;
        }
    
        .logo {
            width: 20%;
            margin-left: 5%;
        }
    
        .blanco{
            width: 10px;
            height: 100%;
            background: white;
            margin-right: 15px;
        }
    
        @media (max-width:768px){
            .imgPrincipal{
            width: 100%;
            height: 30%;
        }
    
        .boton{
            width: 70%;
            margin-left:15%; 
            margin-top:10%   
        }
        
        .PiePagina{
            width: 100%;
            position: absolute;
            bottom: 0;
            
            padding: 0px 0;
            flex-direction: column;
        }
    
        .logo {
            width: 80%;    
            margin-left: 10%;
        }
        .textW{
            width: 80%;    
            margin-left: 10%;
            margin-top:10px;
        }
    
        .textW h5 {
            font-size: 16px;
            text-align: center;
        }
        .blanco{
            display: none;
        }
    
        .Redes{
            width: 80%;    
            margin-left: 10%;
            margin-top:10px; 
        }
        .redesSociales{
            width: 50%;    
            margin-left: 25%;
            margin-bottom: 15px;
        }
        }
    </style>
    
</head>

<body>
    <div class="contenedor">
    <div class="top_logo" style="background-color: #2c24e4; color: #ececec; height: 80px; width: 100%; text-align: center;">
        <img  style="width: 400px; max-width: 100%; height: auto; margin: 0 auto;" src="https://kamgus.com/landing-page/imgs/logo-white.png" alt="MDN">
    </div>
    <div class="boxGris">
        <h2>{{$title}}</h2>
        <h3 style="color:#fff;">El conductor <?php echo $driverName?> ha solicitado una transacción de $<?php echo number_format($value, 2)?></h3>
        <p>
            <?php echo $description;?>
        </p>
        <a href="https://www.kamgus.com/dashboard/"></a><button class="btn boton"><h1>Ir al dashboard</h1> </button>
    </div>
    <div class="PiePagina d-flex">
 
        <img class="logo" src="https://kamgus.com/landing-page/imgs/logo-white.png" alt="MDN">
    
      <div class="textW d-flex">
          <div class="blanco"></div>
        <h5>tenemos la mejor calidad de atencion para todas tus nececidades de traslados y acarreo</h5>
      </div>
      <div class="Redes">
        <img class="redesSociales" src="https://kamgus.com/landing-page/imgs/redesSociales.png" alt="MDN">
      </div>
    </div>
</div>
</body>

</html>