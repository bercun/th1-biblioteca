# Migración del lector RFID a Linux (Ubuntu Server + nginx + MySQL)

> Estado: **plan aprobado, sin implementar**. `rfid_simple.php` sigue siendo
> solo-Windows. Este documento describe qué hay que cambiar, por qué y en
> qué orden, para que el lector RC522 + Arduino Uno funcione en Ubuntu
> Server con nginx + PHP-FPM + MySQL, con el Uno enchufado por USB a la
> misma máquina. Chip USB de la placa: **desconocido** → el diseño incluye
> autodetección (`ttyACM*` vs `ttyUSB*`).

## 1. Punto de partida (lo que ya funciona y NO se toca)

- **`main.cpp` (raíz del repo): firmware 100% portable.** Baud 9600,
  `READY` al arrancar, `WAIT` cada 1.5 s sin tarjeta, bloque
  `UID: XX XX XX XX` + `Lectura de Bloque 4:` con tarjeta. El protocolo
  por USB-serie es idéntico en Windows y Linux.
- **Frontend y parseo:** todo el JS/HTML de `rfid_simple.php`, las regex
  de `interpretarBuffer()`, los endpoints `?read=1` / `?diag=1`, el candado
  `flock` y el polling encadenado (~8 s) quedan igual. `flock` funciona en
  Linux sin cambios.
- **Contrato del helper:** exit `0`=OK (imprime líneas), `2`=ocupado,
  `3`=otro fallo. Lo implementa hoy `rfid_read.ps1`; en Linux lo
  implementará `rfid_read.py` (mismo contrato).

## 2. Por qué hace falta cambiar algo (las 3 piezas Windows-dependientes)

Ubicación exacta en `biblioteca/front/rfid_simple.php` (inventario verificado):

| # | Hoy (Windows) | Dónde está | Por qué no sirve en Linux |
|---|---|---|---|
| 1 | `const RFID_COM_PORT = 'COM5'` | línea 6 | En Linux el Uno aparece como `/dev/ttyACM0` (original ATmega16U2) o `/dev/ttyUSB0` (clon CH340) |
| 2 | `exec('mode COM5 BAUD=...')` | líneas 45, 198 | `mode` no existe en Linux; se configura con `stty` |
| 3 | `fopen('//./COM5')` + lector `.NET` vía `powershell` | líneas 48, 59, 73-112 | `//./COM5` y `powershell` son Windows. En Linux el serial **sí** es un archivo normal: `fopen('/dev/ttyACM0')` directo |

Paradoja a favor: en Linux `stream_set_blocking(false)` sobre el puerto
serie **sí funciona** (en Windows era lo que se colgaba y obligó al helper
.NET). Por eso en Linux la vía principal vuelve a ser PHP puro y el helper
pasa a ser solo respaldo.

## 3. Diseño propuesto (qué se va a implementar)

### 3.1 Selección de puerto

```php
// Pseudocódigo del cambio (línea 6 y usos):
$port = getenv('RFID_PORT');
if (!$port) {
    if (PHP_OS_FAMILY === 'Windows') { $port = 'COM5'; }
    else { $port = autodetectar(); } // primer accesible entre glob(/dev/ttyACM*) + glob(/dev/ttyUSB*)
}
```

- Variable de entorno `RFID_PORT` (en nginx: `fastcgi_param RFID_PORT /dev/ttyUSB0;`
  o `env[RFID_PORT]` en el pool FPM) tiene prioridad: fija el puerto sin tocar código.
- Si no hay env, autodetección: probar `ttyACM*` primero (original) y luego
  `ttyUSB*` (clon), quedarse con el primero que abra. Así no importa no saber
  el chip de la placa.
- Validación estricta del formato (`/^COM\d+$/` o `/^\/dev\/tty(ACM|USB)\d+$/`)
  antes de pasarlo a `exec`/`stty` (misma higiene que hoy).

### 3.2 Configuración del puerto

- Windows (sin cambios): `mode COMx BAUD=9600 PARITY=n DATA=8 STOP=1 xon=off to=on`.
- Linux: `stty -F <puerto> 9600 cs8 -cstopb -parenb -ixon raw -echo`
  (9600 8N1, sin control de flujo, sin eco). Se mantiene la espera de 2 s
  tras abrir: el Uno **también se resetea al abrir en Linux**.

### 3.3 Orden de vías de lectura

- **Linux:** `fread` no-bloqueante (drenaje 0.8 s + escucha 3 s, código actual
  tal cual, solo cambia el path del `fopen`) → fallback `python3 rfid_read.py`
  (requiere `pyserial`) → fallback `pwsh rfid_read.ps1` (solo si existe `pwsh`).
- **Windows:** sin cambios (`dotnet` → `fread`).
- El campo `via` del JSON (`dotnet`/`fread`/`pyserial`) dirá qué camino se usó;
  la página ya muestra `[vía ...]` junto a la hora.

### 3.4 Nuevo `biblioteca/front/rfid_read.py` (respaldo, ~30 líneas)

Mismo contrato que el `.ps1`: abre el puerto (argumento), 9600 8N1,
espera el reset, descarta banner, lee líneas hasta `ListenMs` con
`timeout=0.5`, imprime a stdout; exit 0/2/3. Dependencia única: `pyserial`
(`pip install pyserial` o `apt install python3-serial`).

### 3.5 `?diag=1` en Linux

Además de lo actual: `command -v pwsh python3`, `ls -l /dev/ttyACM* /dev/ttyUSB*`,
salida de `stty -F`, y `groups www-data` (ver §4.3). El `veredicto` distinguirá:
libre / ocupado (`Permission denied` con puerto tomado por otro proceso) /
sin permiso (`Permission denied` + usuario fuera de `dialout`, ver §4.3) /
ausente (`No such file` = desenchufado o nombre mal).

## 4. Trampas de Ubuntu Server (leer ANTES de probar)

### 4.1 ModemManager secuestra el puerto — LA trampa n.º 1

Ubuntu Server trae ModemManager, que al detectar el Uno por USB lo sondea
como si fuera un módem: **abre el puerto, lo resetea en bucle y lo deja
inutilizable para PHP**. Síntomas: el puerto "aparece y desaparece",
lecturas vacías intermitentes, resets sin explicación.

```bash
systemctl is-active ModemManager   # si dice active, hay que actuar
sudo systemctl disable --now ModemManager
```

Alternativa fina (si no se quiere desactivar del todo): regla udev que ignore
el Arduino. Pero para este proyecto, desactivarlo es lo simple y suficiente.

### 4.2 `brltty` reclama los clones CH340/FTDI — trampa n.º 2

Ubuntu incluye `brltty` (soporte braille) que captura los USB-serie CH340/FTDI
al enchufarlos: el `/dev/ttyUSB0` "desaparece" o da ocupado permanente.
Si tu placa resulta ser clon y el puerto no se deja abrir ni con todo cerrado:

```bash
sudo apt remove brltty brltty-udev  # o excluir el VID:PID por udev
```

(Solo aplica a clones; un Uno original `ttyACM0` no lo sufre.)

### 4.3 Permisos: PHP-FPM es `www-data`, no tu usuario — trampa n.º 3

Que TU usuario pueda abrir el puerto no significa que PHP pueda. El pool FPM
corre como `www-data`:

```bash
groups www-data            # debe listar dialout
sudo usermod -aG dialout www-data
sudo systemctl restart php8.2-fpm   # el grupo aplica al reiniciar el servicio
ls -l /dev/ttyACM0         # típico: crw-rw---- root dialout
```

Sin esto, `?diag` dirá `Permission denied` aunque el puerto esté libre.
(Equivalente Windows que ya vivimos: Apache-servicio sin acceso a COM5.)

### 4.4 Nombre inestable del dispositivo

Al reconectar, `ttyACM0` puede pasar a `ttyACM1`; si hay otro USB-serie,
el orden cambia. Para producción, regla udev por VID:PID+número de serie
que cree un symlink fijo (p. ej. `/dev/arduino_uno`) y `RFID_PORT=/dev/arduino_uno`.
Para la demo alcanza con la autodetección (§3.1).

### 4.5 nginx + FPM: concurrencia y timeouts

A diferencia del `php -S` monohilo, FPM atiende en paralelo: el `flock` +
polling encadenado se vuelven **más** importantes (dos lecturas solapadas
pelearían el puerto). Cada `?read` tarda ~6 s (2 reset + 0.8 drenaje + 3
escucha); verificar `max_execution_time` y `request_terminate_timeout` del
pool ≥ 30 s (6 s cabe holgado, pero el default debe confirmarse).

## 5. Cómo saber qué chip tiene tu placa (cuando la enchufes al Ubuntu)

```bash
dmesg | grep -i -E 'tty|ch340|acm|cp210|ftdi' | tail -5
ls /dev/ttyACM* /dev/ttyUSB* 2>/dev/null
lsusb | grep -i -E 'arduino|ch340|1a86|2341'
```

- `... now attached to ttyACM0` / VID `2341` → original.
- `ch341-uart ... attached to ttyUSB0` / VID `1a86` → clon CH340
  (y presta atención al §4.2).

## 6. Orden de implementación (cuando se autorice tocar código)

1. `rfid_simple.php`: selector de puerto (env + OS + autodetección),
   `stty` vs `mode`, orden de vías por OS, `?diag` Linux. Sin tocar parseo ni UI
   (solo sumar `[vía pyserial]` al indicador existente).
2. `biblioteca/front/rfid_read.py` nuevo (contrato exit 0/2/3).
3. Verificación en este orden: `php -l` → helper py manual contra el puerto
   (debe imprimir `WAIT`s y terminar) → `?diag` en verde con `veredicto`
   correcto → `?read` con `via` esperada y `lector:true` en reposo →
   UID con tarjeta 2-3 s.
4. Docs: agregar sección Linux a `card.md` y fila en `README.md`.

## 7. Datos que faltan del lado del servidor (pedir antes de implementar)

```bash
ls /dev/ttyACM* /dev/ttyUSB* 2>/dev/null
groups www-data
systemctl is-active ModemManager
python3 -c "import serial; print(serial.__version__)"
php -v; dpkg -l | grep -E 'nginx|php8.[0-9]-fpm' | head
```

Con esas 5 salidas se fijan los defaults exactos (`RFID_PORT`, si hace falta
`pyserial`, versión del pool FPM a reiniciar) y se implementa sin adivinar.

## 8. Lo que expresamente NO cambia

- `main.cpp`: ni una línea (protocolo serie idéntico).
- `Book/User/biblioteca` web, `bd/*.sql`, credenciales: fuera de alcance.
- Windows: sigue funcionando como hoy (`dotnet` → `fread`).
