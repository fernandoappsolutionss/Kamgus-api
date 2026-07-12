# Contrato Orquestador - Kamgus API v2

## POST /api/v2/quote

Cotiza el `precio_sugerido` para el orquestador sin crear servicios ni tocar dinero.

### Request canonico

```json
{
  "id_tipo_camion": 1,
  "duration_seconds": 1800,
  "distance_meters": 12000,
  "precio_cliente": 55,
  "origin": { "lat": 9.0, "lng": -79.5 },
  "destination": { "lat": 9.1, "lng": -79.6 }
}
```

`duration_seconds` es preferido. Si falta, el endpoint usa Directions solo cuando `GOOGLE_MAPS_SERVER_KEY` esta configurado y existen `origin`/`destination` (tambien acepta `origin_lat`, `origin_lng`, `destination_lat`, `destination_lng`). Directions tiene timeout de 5s y cache de 10 minutos. Si no puede obtener duracion, responde 422.

### Response canonico

```json
{
  "error": false,
  "msg": "Cotizacion calculada",
  "data": {
    "id_tipo_camion": 1,
    "nombre_camion": "Panel",
    "duration_seconds": 1800,
    "duration_minutes": 30,
    "distance_meters": 12000,
    "distance_km": 12,
    "precio_minuto": 0.47,
    "precio_ayudante": 15,
    "tiempo": 1,
    "pk": 0.5,
    "precio_sugerido": 63.3,
    "precio_listado_spa": 71.2,
    "precio_cliente": 55,
    "precio_cliente_banda": "yellow",
    "bandas_precio": {
      "red": "<= 0.7 * precio_sugerido",
      "yellow": "> 0.7 * precio_sugerido && < 0.86 * precio_sugerido",
      "green": ">= 0.86 * precio_sugerido"
    },
    "source": "request"
  }
}
```

Errores: 404 si `types_transports.id` no existe; 422 si no hay duracion util; 429 por throttle 30/min.

## Formula de precio

La fuente canonica es el build compilado `repos/kamgus-invitados-bridge/build/main.js`.

Formula preliminar del listado de vehiculos:

```js
calculatePriceByVehicule(e,t,n,o){const i=Math.round(parseInt(e)/60*2);return Math.round(i*t)+parseInt(n)+60*o*t}
getVehicles(){... e.precioTotal=this._sh.calculatePriceByVehicule(this.service.time.duration.value,e.precio_minuto,e.precio_ayudante,e.tiempo)}
```

Formula realmente enviada como `precio_sugerido`:

```js
cotizaVehicle(){... const e=parseInt(this.service.time.duration.value)/60; ... const t=parseFloat(this.selVehicle.precio_minuto); ... const n=parseFloat(this.selVehicle.precio_ayudante); const o=this.selVehicle.pk; ... const i=parseFloat(this.service.time.distance.value)/1e3; const r=parseFloat(this.selVehicle.tiempo); ... this.totalPrice=(e+60*r)*t+i*o+n}
sendArticulos(){... precio_sugerido:this.totalPrice ...}
```

Por eso `/api/v2/quote` usa:

```text
precio_sugerido = (parseInt(duration_seconds) / 60 + 60 * parseFloat(tiempo))
                  * parseFloat(precio_minuto)
                  + (parseFloat(distance_meters) / 1000) * parseFloat(pk)
                  + parseFloat(precio_ayudante)
```

`pk` no vive en `types_transports`; el endpoint invitado de vehiculos lo inyecta como `0.5`.

## Tarifario y mapeo id_tipo_camion

`/api/v2/quote` lee `types_transports.id` directamente. El dump/seed actual contiene:

| id | nombre | precio_minuto | precio_ayudante | tiempo |
|---:|---|---:|---:|---:|
| 1 | Panel | 0.47 | 15.00 | 1 |
| 2 | Pick up | 0.45 | 15.00 | 1 |
| 3 | Camion Pequeno | 0.75 | 30.00 | 2 |
| 4 | Camion Grande | 0.85 | 40.00 | 2 |
| 5 | Moto | 0.05 | 3.50 | 1 |
| 6 | Sedan | 0.08 | 5.00 | 1 |

El store invitado actual (`App\Http\Controllers\V2\Inviteds\ServiceController::$transportType`) conserva mapeo legado:

```php
"1" => "PANEL",
"2" => "PICK UP",
"3" => "CAMIÓN PEQUEÑO",
"4" => "CAMIÓN GRANDE",
"7" => "MOTO",
"8" => "SEDAN",
"6" => "SEDAN",
```

Nota de integracion: `Moto` es `5` en `types_transports`, pero el store invitado legado espera `7` para traducir a `MOTO`. El orquestador debe tratar `types_transports.id` como canonico para quote y no asumir que el store legado acepta todos los ids sin normalizacion.

## DTO para crear servicio invitado

Cuando T3 cree o simule un servicio contra Laravel, el DTO canonico debe conservar los nombres exactos del SPA:

```json
{
  "tiempo": 1800,
  "kms": 12,
  "fecha_reserva": "2026-07-12T10:30:00.000Z",
  "tipo_servicio": {},
  "id_tipo_camion": 1,
  "tipo_translado": "vehiculo",
  "estado": "ACTIVO",
  "valor": 55,
  "precio_sugerido": 63.3,
  "pago": "PENDIENTE",
  "descripcion": "Carga liviana",
  "tipo_pago": "efectivo",
  "tokenTarjeta": "-",
  "punto_inicial": "Origen",
  "coordenas": "{\"coord_punto_inicio\":\"9,-79.5\",\"coord_punto_final\":\"9.1,-79.6\"}",
  "latitud_inicial": 9,
  "longitud_inicial": -79.5,
  "punto_final": "Destino",
  "latitud_final": 9.1,
  "longitud_final": -79.6,
  "nombres": "Ana",
  "apellidos": "Perez",
  "telefono": "60000000",
  "correo": "ana@example.com",
  "password": "",
  "articulos": "[]",
  "customer_id": null,
  "payment_method": null,
  "description": "Carga liviana",
  "assistant": "0"
}
```

`coordenas` esta mal escrito por compatibilidad. La serializacion exacta del SPA es:

```js
JSON.stringify({coord_punto_inicio:`${origin.lat},${origin.lng}`,coord_punto_final:`${destination.lat},${destination.lng}`})
```

Campos de identidad requeridos por el flujo invitado: `nombres`, `apellidos`, `telefono`, `correo`; `password` puede ir vacio porque el backend invitado reemplaza con `K12345678`.

## Bandas del precio del usuario

El SPA muestra:

```js
precio > .7 * totalPrice && precio < .86 * totalPrice  // yellow
precio >= .86 * totalPrice                             // green
precio <= .7 * totalPrice                              // red
```

El agente/orquestador debe usar esas mismas bandas al aceptar o explicar el precio del cliente.
