@component('mail::message')

<table width="100%" style="border-spacing: 0;">
<tr>
<td class="first_table_td">
    <div>
        <h2>Caso xxxxxxx - xxxxxxx - <br>Petición/Queja</h2>
    </div>
</td>
<td class="first_table_td2">
    <td>
        <div style="">
            <img src="https://i.ibb.co/mTPD6DW/f-HJa-Zl-S42-U.png" style="width: 180px;" alt="">
        </div>
    </td>
</td>
</tr>
</table>


{{-- segunda tabla --}}
<table width="100%" style="border-spacing: 0;">
<tr class="second_table_tr">
<td class="second_table_td">
    <div>
        <h2 style="font-size: 20px; color: #ffffff;">Respetado(a) Señor(a) xxxxxxxxxxxx </h2>
    </div>
</td>
</tr>
</table>

{{-- stilos del cuerpo --}}
<style>
    .first_table_td{
        padding: 20px 30px;
        width: 60%;
    }

    .second_table_td{
        padding-left: 30px;
    }

    .second_table_tr{
        background-position-x: -20%; 
        background-size: 100% 100%; 
        height: 100px;
        background-repeat: no-repeat; 
        background-image: url('https://i.ibb.co/LZzjCFN/banner2.png');
    }

</style>

{{-- componente para llamar al text info --}}
@component('mail::text-info', ['url' => '', 'width' => '100%', 'color' => '#7A7A7A', 'second' => 'Atentamente, Soporte' ])
Reciba un cordial saludo en nombre de <strong>kamgus</strong>, se ha radicado su caso bajo el numero xxxxxxx,
le estaremos enviando respuesta lo más pronto posible.
@endcomponent


@endcomponent
