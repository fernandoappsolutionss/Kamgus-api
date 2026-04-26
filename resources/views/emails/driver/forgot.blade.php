<html>
    <body style="background-color:#eee">
        <center>
                <div style="background-color: #fff; width: 800px; font-family: arial; padding: 5px;">
                <div style="background-image: url(https://kamgus.com/landing-page/imgs/bg-1.jpg); background-repeat:no-repeat; background-position:center; background-size:cover; padding: 10px 0px;">
                        <img src="https://kamgus.com/landing-page/imgs/logo-kangus.png">
                        <h1 style="color:#fff">Hola, {{$row->nombres}}</h1>
                        <h3 style="color:#fff">Reestabecer contraseña</h3>
                    </div>
                <div>
                    <p>
                        Para reestablecer contraseña da clic
                        <a href="https://www.kamgus.com/dashboard/forgot.php?token={{$resetToken}}&email={{$data['email']}}&reset=newPass">	aqui</a>
                        <!--<a href="{{url('')}}"> aqui</a>-->
                    </p>
                </div>
            </div>
        </center>                                                                                    
    </body>
</html>