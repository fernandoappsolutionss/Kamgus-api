<?php

namespace App\Constants;

class Constant
{
    const
    //CREAR SERVICIOS
    CREATE_SERVICE = 'Servicio solicitado con éxito',
    LOAD_SERVICES  = 'Servicios cargados con éxito',
    UPDATE_SERVICE = 'Servicio actualizado con éxito',
    CANCEL_SERVICE = 'Servicio cancelado con éxito',


    //PAGOS
    SUCCESSFUL_PAYMENT = 'Pago realizado con éxito',

    //ARTICULOS
    CREATE_ARTICLE = 'Articulo creado con éxito',
    LOAD_ARTICLES = 'Articulos cargados con éxito',

    //FCM TOKENS
    CREATE_FCM_TOKEN = 'Token generado con éxito',
    UPDATE_FCM_TOKEN = 'Token actualizado con éxito',
    ERROR_UPDATE_FCM_TOKEN = 'Error desconocido, no se pudo actualizar el FCM TOKEN',

    //TRANSPORTES
    LOAD_TYPES_TRANSPORTS= 'Tipos de transporte cargados con éxito',
    LOAD_MODELS= 'Modelos de vehículo cargados con éxito',

    //PAISES
    LOAD_COUNTRIES = 'Paises cargados con éxito',

    //CLIENTES
    UPDATE_CUSTOMER = 'Cliente actualizado con éxito',
    CHANGE_PASSWORD = '¡Tu contraseña ha sido cambiada!',
    FORGOT_PASSWORD = '¡Le hemos enviado por correo electrónico su enlace de restablecimiento de contraseña!',

    //CONDUCTORES
    CREATE_DRIVER = 'Conductor creado con éxito',
    LOAD_DRIVERS = 'Conductores cargados con éxito',
    UPDATE_DRIVER = 'Conductor actualizado con éxito',

    //EMPRESAS
    UPDATE_COMPANY = 'Empresa actualizada con éxito',

    //ACTUALIZAR IMAGEN DEL PERFIL DE CUALQUIER USUARIO
    UPDATE_PROFILE_IMAGE = "Imagen de perfil actualizada con éxito",

    UPDATE_ADMIN = 'Administrador actualizado con éxito',

    //ROLES
    LOAD_ROLES = 'Roles cargados con éxito',
    
    //SETTINGS
    LOAD_SETTINGS = 'Configuraciones cargados con éxito',
    
    //PAGOS
    ADD_CARD = 'Tarjeta agregada correctamente';
    

}