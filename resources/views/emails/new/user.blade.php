@component('mail::message')

<div style="padding-top: 10px;">
    <h1 style="text-align: center; font-size: 30px; color: #2B24DF;">¡Bienvenido!</h1> 
</div>


<table class="table-padre">
<tr >
<td class="two-columns" 
style="background-position-x: -10%; background-size: 90% 95%; background-repeat: no-repeat; background-image: url('https://i.ibb.co/jHRVxs0/i-Phone-12-Pro-Max-6.png');">

{{-- columna 1 --}}
<table class="column">
<tr>
<td class="padding: 0 20px;">
<div style="display: flex">
<div>
    <hr>
</div>
<div style="padding: 10px;">
<p style="color: #fff; font-size: 1.2rem">Gracias por inscribirte al servicio de</p>
</div>
</div>
<img src="https://i.ibb.co/1M8kbTc/113ed9f5-277e-4376-aba3-0157bfb31869.png" alt="KAMGUS" style="width: 150px;">
</td>
</tr>
</table>


{{-- columna 2 --}}
<table class="column2">
<tr>
<td style="vertical-align:bottom; text-align:center;">
<div style="position: relative;">
<img src="https://i.ibb.co/VBkR9mK/usuario.png" style="position: absolute; bottom: 0; right: 30px;" alt="KAMGUS">
</div>
</td>
</tr>
</table>

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
}

.two-columns .column{
    width: 100%;
    max-width: 300px;
    text-align: left;
    border-spacing: 0;
    padding: 20px;
}

.two-columns .column2{
    width: 100%;
    max-width: 250px;
    border-spacing: 0;
}

hr{
 height:10vh;
 width:.2vw;
 border-width:0;
 color:#FFFFFF;
 background-color: #FFFFFF;
}

</style>

{{-- componente para llamar al text info --}}
@component('mail::text-info', ['url' => '', 'width' => '80%', 'color' => '#7A7A7A', 'second' => 'Ingresa a tu dashboard aqui!'])
Elige el Servicio que se Ajuste Mejor a Ti
@endcomponent

@component('mail::button', ['url' => 'https://myapp.kamgus.com/#/dashboard', 'font' => '20px', 'buttom_tam' => '60px'])
Ingresar
@endcomponent

@endcomponent
