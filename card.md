# Lector RFID-RC522 con Arduino Uno — `card.md`

Página simple que lee directo del puerto **COM5** y muestra el valor de la tarjeta.
Un solo archivo: `biblioteca/front/rfid_simple.php`. Sin Python, sin BD, sin librerías.

## 1. Hardware

| RC522 | Arduino Uno |
|---|---|
| SDA (SS) | Pin 10 (`SS_PIN`) |
| RST | Pin 8 (`RST_PIN`) |
| MOSI | Pin 11 |
| MISO | Pin 12 |
| SCK | Pin 13 |
| VCC | **3.3V (no 5V)** |
| GND | GND |

Arduino conectado por USB → Windows lo ve como `COM5`.

## 2. Qué hace `main.cpp` (firmware con latido, OBLIGATORIO)

> Tu `main.cpp` original solo hablaba al detectar tarjeta. Eso colgaba la página:
> `fread()` en Windows bloquea si no llegan bytes → el request `?read=1` no terminaba
> nunca → el servidor PHP (monohilo) se quedaba pillado y ni `?diag` respondía
> (“Leyendo…” eterno). El archivo `main.cpp` es tu mismo código más 6 líneas:
> `READY` al arrancar y `WAIT` cada 1.5 s sin tarjeta. **Súbelo al Uno.**

1. `Serial.begin(9600)`, inicia SPI + `PCD_Init()`, clave `FF FF FF FF FF FF`.
2. En `loop` espera tarjeta (`PICC_IsNewCardPresent + ReadCardSerial`).
3. Al detectar imprime por serial:
   ```
   --- TARJETA DETECTADA ---
   UID: A3 F1 2B 09
   Escribiendo en Bloque 4...
   >> ¡ESCRITURA EXITOSA! <<
   Lectura de Bloque 4: ARDUINO NFC 2026
   ```
4. Escribe `ARDUINO NFC 2026` (16 bytes) en el **bloque 4** y lo relee para verificar.
5. `delay(2000)` para no leer en bucle.

> El PHP no modifica la tarjeta, solo **lee el texto** que Arduino imprime.

## 3. Qué hacen `rfid_simple.php` + `rfid_read.ps1`

Dos archivos en `biblioteca/front/`:

- `rfid_simple.php` → página HTML (UID grande + bloque + estado + debug raw + indicador `[vía dotnet/fread]`).
- `rfid_read.ps1` → helper que lee COM5 con .NET `SerialPort` y **timeouts reales** (`ReadTimeout=500ms`): devuelve lo que llegue en ~5 s y **nunca se cuelga** aunque la placa esté muda. Códigos: `0`=OK, `2`=puerto ocupado, `3`=otro fallo.
- `GET front/rfid_simple.php?read=1` → lee COM5 (vía dotnet, fallback fread) y devuelve JSON:

```json
{
  "success": true,
  "found": true,
  "uid": "A3 F1 2B 09",
  "uid_compacto": "A3F12B09",
  "bloque": "ARDUINO NFC 2026",
  "escritura_ok": true,
  "error": null,
  "raw": "--- TARJETA DETECTADA ---\nUID: ...",
  "hora": "10:23:41"
}
```

Pasos internos (`leerTarjeta()`):

1. `exec('mode COM5 BAUD=9600 ...')` configura el puerto en Windows (sin comillas: `mode` no acepta `'COM5'`).
2. Candado con `flock` en `sys_get_temp_dir()/rfid_com5.lock` para que 2 pestañas/polls no abran el puerto a la vez.
3. **Vía preferida `dotnet`**: `exec(powershell -File rfid_read.ps1 -Port COM5 ...)` → abre con .NET, espera 2 s el reset del Uno, descarta banner y lee líneas 3 s con `ReadTimeout`. Imposible que se cuelgue. Verificado: captura `WAIT`/`UID` tal cual.
4. **Fallback `fread`**: solo si falta powershell/helper. `fopen('//./COM5','r+b')` (**ojo:** en PHP-Windows `fopen('COM5')` falla con *“No such file”*; hay que usar `//./COM5`). Drenaje con tope 0.8 s + escucha 3 s. Tiene riesgo de bloqueo si la placa está muda, por eso es solo respaldo.
5. Parseo único (`interpretarBuffer`, ambas vías). Regex: `/UID:\s*([0-9A-Fa-f ]+)/` y `/Lectura de Bloque\s*\d+\s*:\s*(.+)/`. Flag `lector=true` si llegó READY/WAIT/UID; `via` dice qué camino se usó.
6. El JS hace `fetch(window.location.pathname + '?read=1')` encadenado cada ~8 s (sin solapes; el `php -S` es monohilo) y actualiza con `textContent` (anti-XSS).
7. `?diag=1` prueba apertura corta + informa `helper_ps1`, `powershell` y `veredicto` (libre/ocupado/ausente).
8. Errores: se reporta el error real. `Permission denied`/exit 2 = puerto ocupado (Monitor Serie abierto); `No such file` = el Uno no está en COM5.

Prueba manual del helper (monitor **cerrado**):
`powershell -NoProfile -ExecutionPolicy Bypass -File biblioteca/front/rfid_read.ps1 -Port COM5`
Debe imprimir `WAIT` cada 1.5 s (o el bloque `UID:` con tarjeta cerca) y terminar en ~5-6 s.

## 4. Cómo usarlo

1. Sube **`main.cpp`** (raíz del repo, ya incluye el latido READY/WAIT) al Uno con PlatformIO/Arduino IDE.
2. Abre el Monitor Serie a 9600 y verifica que salga `READY` y luego `WAIT` cada 1.5 s. Acerca la tarjeta y verifica el bloque `UID:`.
3. **Cierra el Monitor Serie / PlatformIO** (el puerto solo lo usa un programa a la vez).
4. Sirve el proyecto (XAMPP: `http://localhost/biblioteca/front/rfid_simple.php`).
   Con `php -S`: una sola pestaña, y cada lectura tarda ~5 s (2 s reset + 0.8 drenaje + 3 escucha).
5. Abre la página: primero debe decir *“Lector conectado — acerca la tarjeta”* (llegó el WAIT).
   Luego **mantén la tarjeta apoyada 2-3 s** durante *“Leyendo…”* → aparece UID + bloque.
6. Prueba directa del JSON: `.../rfid_simple.php?read=1` y `.../rfid_simple.php?diag=1`.
7. Debug: *“Ver datos crudos”* muestra lo que mandó Arduino; *“Diagnóstico del puerto”* dice por qué falla COM5.

## 5. Problemas comunes

| Síntoma | Causa / solución |
|---|---|
| `Puerto COM5 OCUPADO (Permission denied)` | Tienes el **Monitor Serie de PlatformIO/Arduino IDE abierto** (o 2 pestañas). Flujo correcto: sube firmware → verifica READY/WAIT en el monitor → **cierra el monitor** → recién ahí abre la página PHP. Solo un programa usa COM5 a la vez. Comprobado: con el monitor abierto, `mode COM5` dice “no disponible” y PHP da `Permission denied`. |
| `[404] ... rfid_simple.php - No such file` en la consola del `php -S` | **Ruido, ignóralo.** Es como el `php -S` vuelca el warning del `fopen` del script; tus requests devuelven `[200]` igual. El problema real está en el `Detalle:` del mensaje de la página, no en ese 404. |
| `Lector conectado — acerca la tarjeta` (sin UID nunca) | Tarjeta lejos o la quitas muy rápido. Mantenla apoyada 2-3 s durante *“Leyendo…”*. El `delay(2000)` del `.ino` emite como máximo 1 lectura cada 2 s. |
| `Puerto abierto pero mudo (sin WAIT)` | Tienes el firmware viejo sin latido en el Uno. Sube `main.cpp`. |
| Página clavada en *“Leyendo…”* y diagnóstico en *“Consultando…”* | Request anterior colgado en `fread` + servidor monohilo encolando. Desde esta versión la vía `dotnet` no se cuelga nunca; si ves `[vía fread]` colgado, falta el helper o powershell (míralo en `?diag=1`). Reinicia el `php -S` y recarga con una sola pestaña. |
| Datos pero `no se encontró UID` | Mira el `raw`: si tu tarjeta tiene UID de 7 bytes el regex igual lo toma; si el formato cambió, ajusta el regex. |
| Lectura lenta (5-6 s por vez) | Normal: cada `fetch` reabre COM5 y el Uno se resetea (~2 s). Es el costo de la versión simple. |
| `Error Autenticacion` en raw | Tarjeta no Mifare Classic o clave distinta de `FF*6`. El PHP lo muestra en `error`. |

## 6. Límites de esta versión simple (a propósito)

- Un request = una apertura de puerto → lento, no apto para torniquete rápido.
- Solo 1 cliente a la vez; 2 pestañas compiten por COM5.
- Reescribe el bloque 4 en cada lectura (desgaste + siempre el mismo texto demo).
- Si Apache corre como servicio con otro usuario, puede no ver COM5 → ejecutar XAMPP como el mismo usuario o usar el puente Python (fase 2).

## 7. Siguiente paso (cuando quieras)

Vincular `uid_compacto` a `users.rfid_uid` y hacer login/préstamo con tarjeta. Eso ya requiere `bd/add_rfid.sql` + `controllers/rfid/*.php`, fuera del alcance de este archivo simple.
