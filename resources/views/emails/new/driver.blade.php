@component('mail::message')


<table class="table-padre">
<tr>
<td class="first-column">
<table class="column">
<tr>
<td style="background-image: url('https://i.ibb.co/Z6Th9gG/unnamed.png'); background-position-x: 80%; background-position-y: 45%; background-size: 160% 100%; background-repeat: no-repeat;">
<div style="">
   <h1 style="color: #ffffff; padding-left: 20px; font-size: 1.8em; padding-top: 10px;">Bienvenido!</h1>
</div>
</td>
</tr>
</table>

</td>
</tr>
</table>

<br>

<table class="table-padre">
<tr>
<td class="two-columns">
<table class="column" height="50px;">
<tr>
<td>
<div style="display: flex;">
<div>
    <hr>
</div>
<div style="padding: 10px;">
<p style="color: #2B24DF; font-size: 1.2rem">Ahora formas parte de la familia</p>
</div>
</div>
</td>
</tr>
</table>

</td>
</tr>
</table>


{{-- columna 2 --}}
<table class="column2" width="100%" style="margin-top: 10%;">
<tr>
<td align="right">
<div>
<img src="https://i.ibb.co/SQJYRZq/driver.png" style="width: 600px;" alt="KAMGUS">
</div>
</td>
</tr>
</table>


<style>
    .table-padre{
        width: 100%;
        border-spacing: 0;
    }
    .two-columns{
        text-align: center;
        display: flex;
        height: 60px;
    }
    .two-columns .column{
        width: 100%;
        max-width: 300px;
        text-align: left;
        border-spacing: 0;
        padding: 20px 20px;
    }
    hr{
    height:10vh;
    width:.2vw;
    border-width:0;
    color:#2B24DF;
    background-color: #2B24DF;
    }

    .first-column{
        text-align: center;
        display: flex;
        height: 40px;
    }

    .first-column .column{
        width: 100%;
        max-width: 300px;
        text-align: center;
        border-spacing: 0;
        padding: 20px 20px;
    }

</style>

{{-- componente para llamar al text info --}}
@component('mail::text-info', ['url' => 'https://myapp.kamgus.com/#/dashboard', 'width' => '80%', 'color' => '#7A7A7A', 'second' => 'Ingresa a tu app!'])
Administra tus servicios
@endcomponent


<table class="column-icon" width="100%" height="100px" style="top: 0;">
<tr>
<td align="right">
<div>
<a href="http://google.com" target="_blank"><img src="https://i.ibb.co/1q1wQYq/Epbk-U5a-Zc-S.png" style="width: 200px;" alt="KAMGUS"></a>
</div>
</td>
<td>
<div>
<a href="http://google.com" target="_blank"><img src="https://i.ibb.co/ryXCjts/SDGr-B2m11-J.png" style="width: 200px;" alt="KAMGUS"></a>
</div>
</td>
</tr>
</table>

@endcomponent
