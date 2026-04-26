@component('mail::message')

<table class="table-padre">
<tr >
<td class="two-columns">

{{-- columna 1 --}}
<table class="column">
<tr>
<td class="padding: 0 20px;">
<div style="display: flex">
<div>
    <hr>
</div>
<div style="padding: 10px;">
<p style="color: #2B24DF; font-size: 1.5rem">Resumen de estado</p>
</div>
</div>
</td>
</tr>
</table>


{{-- columna 2 --}}
<table class="column2">
<tr>
<td align="right">
<div>
<img src="https://i.ibb.co/wRJY5Lb/es-K0-Ku1-Zih.png" style="" alt="KAMGUS">
</div>
</td>
</tr>
</table>
</td>
</tr>

{{-- otro tr fila--}}
<tr>
<td class="second_internal_td">

{{-- column 1 --}}
<table class="second_internal_table_1">
<tr>
<td>
<div class="second_internal_table_1_td_div">
    <h1>Estado del servicio</h1>
</div>
<div class="second_internal_table_1_td_div_padre">
<div class="second_internal_table_1_td_div_padre_first_div">
    @if ( $status == "Activo")
        <img src="https://i.ibb.co/fkj3MPg/marque-dentro-del-circulo.png" class="second_internal_table_1_td_div_padre-image" alt="">
    @elseif($status == "Programado")
        <img src="https://i.ibb.co/LzzBZNg/calendario.png" class="second_internal_table_1_td_div_padre-image" alt="">
    @elseif($status == "Cancelado")
        <img src="https://i.ibb.co/MB3rhkw/signo-de-exclamacion1.png" class="second_internal_table_1_td_div_padre-image" alt="">
    @endif
</div> 
<div>
    @if ( $status == "Activo")
        <h3 style="color: #2B24DF">Activo</h3>
    @elseif($status == "Programado")
        <h3 style="color: #2B24DF">Programado</h3>
    @elseif($status == "Cancelado")
        <h3 style="color: #B70F0A">Cancelado</h3>
    @else
        <h3 style="color: #2B24DF">{{$status}}</h3>
    @endif
</div>
</div>
</td>
</tr>
</table>

{{-- column 2 --}}
<table class="second_internal_table_2">
<tr>
<td style="width: 100%;">
<a href="http://api.whatsapp.com/send?phone=+5073970770" style="padding: 15px 30px; border-radius: 50px; width: 100%; border: 2px solid #2B24DF; text-decoration: none;" target="_blank" rel="noopener"> <strong style="color:#2B24DF; font-size: 15px;"> Seguir servicio </strong></a>
</td>
</tr>
</table>


</td>
</tr>

{{-- tercera fila --}}
<tr>
<td class="third_internal_td">
<table width="100%">
<tr>
<td>
@if ($status == "Activo")
<img src="https://i.ibb.co/G76X30x/guiagris2.png" alt="">
@elseif($status == "Programado")
<img src="https://i.ibb.co/z4T5HRS/guiagris1.png" alt="">  
@elseif($status == "Cancelado")
<img src="https://i.ibb.co/b2h19zR/guiagris.png" alt="">
@else

@endif
</td>
</tr>
</table>
</td>
</tr>

</table>


<style>
    .table-padre{
        width: 100%;
        height: 50%;
        border-spacing: 0;
    }
    .two-columns{
        text-align: center;
        display: flex;
    }

    .two-columns .column{
        width: 100%;
        max-width: 200px;
        text-align: left;
        border-spacing: 0;
        padding: 20px;
        display: inline-block;
        vertical-align: top;
    }

    .two-columns .column2{
        width: 100%;
        max-width: 400px;
        min-width: 100px;
        border-spacing: 0;
    }

    hr{
        height:12vh;
        width:.2vw;
        border-width:0;
        color:#2B24DF;
        background-color: #2B24DF;
    }

    .second_internal_td{
        text-align: center;
        display: flex;
    }

    .second_internal_table_1{
        width: 80%; 
        max-width: 300px; 
        background-position-x: -10%; 
        background-size: 110% 95%; 
        background-repeat: no-repeat; 
        background-image: url('https://i.ibb.co/cJgFz0Q/banner-gris.png');
        border-spacing: 0;

    }

    .second_internal_table_2{
        width: 100%; 
        max-width: 250px;
        border-spacing: 0;
    }

    .second_internal_table_1_td_div h1{
        color: #7A7A7A;
        padding-left: 15px;
    }

    .second_internal_table_1_td_div_padre_first_div{
        width: 30%;
        padding-bottom: 5px;
    }

    .second_internal_table_1_td_div_padre-image{
        width: 50%;
    }

    .second_internal_table_1_td_div_padre{
        display: flex;
        left: 0;
    }

</style>

@endcomponent

