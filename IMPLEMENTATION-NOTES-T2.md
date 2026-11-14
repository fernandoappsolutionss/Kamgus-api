# IMPLEMENTATION NOTES - T2

## Alcance implementado

- `POST /api/v2/quote` con throttle especifico `30/min`.
- `App\Classes\PriceCalculator` puro, sin dependencias de Laravel.
- Lectura del tarifario desde `types_transports`.
- Directions server-side solo si `GOOGLE_MAPS_SERVER_KEY` existe, con timeout 5s y cache 10min.
- Respuestas 422 si no hay duracion/ruta usable y 404 si `id_tipo_camion` no existe.
- Contrato del orquestador en `docs/api/orquestador-contract.md`.
- Tests PHPUnit agregados para calculadora y endpoint.

## Formula verificada en build compilado

Archivo fuente leido: `/Users/teamsolutionsslatam/Hermes-Agente IA PC/repos/kamgus-invitados-bridge/build/main.js`.

El listado de vehiculos calcula una vista preliminar:

```js
calculatePriceByVehicule(e,t,n,o){const i=Math.round(parseInt(e)/60*2);return Math.round(i*t)+parseInt(n)+60*o*t}
```

Pero el `precio_sugerido` enviado en el FormData sale de `this.totalPrice`:

```js
this.totalPrice=(e+60*r)*t+i*o+n
...
precio_sugerido:this.totalPrice
```

Donde el mismo bloque define:

```js
const e=parseInt(this.service.time.duration.value)/60;
const t=parseFloat(this.selVehicle.precio_minuto);
const n=parseFloat(this.selVehicle.precio_ayudante);
const o=this.selVehicle.pk;
const i=parseFloat(this.service.time.distance.value)/1e3;
const r=parseFloat(this.selVehicle.tiempo);
```

Por eso el endpoint usa la formula enviada, y expone tambien `precio_listado_spa` para auditar la formula preliminar.

## Mapeo id_tipo_camion

`/api/v2/quote` usa `types_transports.id` como canonico:

- 1 Panel
- 2 Pick up
- 3 Camion Pequeno
- 4 Camion Grande
- 5 Moto
- 6 Sedan

El store invitado legado conserva `7 => MOTO`, `8 => SEDAN` y `6 => SEDAN`; queda documentado para T3 porque `Moto=5` en el dump/seed.

## Verificacion

Restriccion de la tarea: no Docker, no Composer; verificacion final con `php -l`.

Comandos ejecutados:

- `php -l app/Classes/PriceCalculator.php`
- `php -l app/Http/Controllers/V2/QuoteController.php`
- `php -l config/services.php`
- `php -l routes/api.php`
- `php -l tests/Unit/PriceCalculatorTest.php`
- `php -l tests/Feature/QuoteEndpointTest.php`

Resultado: todos reportaron `No syntax errors detected`.

PHPUnit no se ejecuto por esa restriccion, pero quedaron tests en:

- `tests/Unit/PriceCalculatorTest.php`
- `tests/Feature/QuoteEndpointTest.php`
