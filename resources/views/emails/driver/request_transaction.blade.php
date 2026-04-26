@component('mail::message')

    
    
    @component('mail::text-info', ['url' => 'https://www.kamgus.com/dashboard/', 'width' => '80%', 'color' => '#7A7A7A', 'second' => '$'.number_format($value, 2)])
    <?php echo $description;?>
    <br>
    El conductor <?php echo $driverName?> ha solicitado una transacción de 
    @endcomponent
    @component('mail::button', ['url' => 'https://www.kamgus.com/dashboard/', 'font' => '20px', 'buttom_tam' => '60px'])
        Ir al dashboard
    @endcomponent

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



@endcomponent
