@component('mail::message')

<table class="table-padre">
<tr>
<td class="first-column">
<table class="column">
<tr>
<td style="background-image: url('https://i.ibb.co/Z6Th9gG/unnamed.png'); background-position-x: 80%; background-position-y: 45%; background-size: 160% 100%; background-repeat: no-repeat;">
<div style="">
<h1 style="color: #ffffff; padding-left: 20px; font-size: 1.8em; padding-top: 10px;">Servicio!</h1>
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
<h2 style="color: #2B24DF; font-size: 1.8rem">Al cliente</h2>
</div>
</div>
</td>
</tr>
</table>

</td>
</tr>
</table>
    
    
{{-- columna 2 --}}
<table class="column2" width="100%">
<tr>
<td align="center">
<div>
<img src="https://i.ibb.co/LZL86PQ/Aq-P6a6-Vsi-S.png" style="width: 300px;" alt="KAMGUS">
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
@component('mail::text-info', ['url' => '', 'width' => '80%', 'color' => '#2B24DF', 'second' => ''])
Haz click en el siguiente link para recuperar la contraseña. Por motivos de seguridad, este enlace caducará en 15 minutos después de la hora de envío.
@endcomponent


@component('mail::button', ['url' => $url,  'font' => '15px', 'buttom_tam' => '60px'])
Recuperar Contraseña
@endcomponent

@endcomponent