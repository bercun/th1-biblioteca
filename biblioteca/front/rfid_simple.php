<?php
// rfid_simple.php — Lectura directa Arduino Uno (COM5) + RC522, un solo archivo.
// Uso: http://localhost/biblioteca/front/rfid_simple.php
// AJAX: http://localhost/biblioteca/front/rfid_simple.php?read=1  -> JSON

const RFID_COM_PORT = 'COM5';
const RFID_BAUD = 9600;
const RFID_TIMEOUT_SEC = 3; // tiempo de escucha por request (con latido WAIT alcanza de sobra)

// --- Endpoint diagnóstico: ?diag=1 (no abre el puerto por segundos, solo prueba) ---
if (isset($_GET['diag'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(diagnostico(), JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Endpoint JSON: ?read=1 ---
if (isset($_GET['read'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(leerTarjeta(), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Diagnóstico rápido sin bloquear: prueba las variantes de apertura
 * y devuelve por qué falla COM5 (puerto ocupado, nombre incorrecto, etc.)
 */
function diagnostico(): array
{
    $port = RFID_COM_PORT;
    $info = [
        'php' => PHP_VERSION,
        'sapi' => PHP_SAPI,
        'script' => $_SERVER['SCRIPT_FILENAME'] ?? '?',
        'docroot' => $_SERVER['DOCUMENT_ROOT'] ?? '?',
        'port' => $port,
        'mode' => null,
        
        'candidatos' => [],
        'helper_ps1' => is_file(__DIR__ . DIRECTORY_SEPARATOR . 'rfid_read.ps1') ? 'presente' : 'AUSENTE',
        'powershell' => null,
    ];
    @exec('mode ' . $port . ' 2>&1', $out, $code);
    $info['mode'] = ['exit' => $code, 'out' => implode("\n", $out)];
    // Probar apertura corta de cada variante (sin sleep largo).
    // El que manda es //./COM5: OK=libre, denied=ocupado, no such file=el Uno no está ahí.
    foreach (['//./' . $port, $port] as $cand) {
        $fp = @fopen($cand, 'r+b');
        if ($fp) {
            fclose($fp);
            $info['candidatos'][$cand] = 'OK';
        } else {
            $e = error_get_last();
            $info['candidatos'][$cand] = 'FALLO: ' . ($e['message'] ?? '?');
        }
    }
    @exec('where powershell 2>&1', $wOut, $wCode);
    $info['powershell'] = ($wCode === 0) ? trim(implode(' ', $wOut)) : 'NO encontrada (la lectura usará fread con riesgo de cuelgue)';
    $real = $info['candidatos']['//./' . $port];
    if ($real === 'OK') {
        $info['veredicto'] = 'Puerto LIBRE y accesible. Si ?read falla igual, acerca la tarjeta durante "Leyendo…".';
    } elseif (stripos($real, 'denied') !== false) {
        $info['veredicto'] = 'Puerto OCUPADO por otro programa (Monitor Serie de PlatformIO/Arduino IDE). Ciérralo y reintenta. Solo uno a la vez.';
    } else {
        $info['veredicto'] = 'El Uno NO está en ' . $port . ' ahora (desconectado o cambió a COM6). Revisa con "mode" en terminal.';
    }
    return $info;
}

/**
 * Lee COM5 con .NET SerialPort via PowerShell (rfid_read.ps1).
 * Tiene timeouts reales: NUNCA se cuelga aunque la placa esté muda.
 * Retorna ['status' => ok|busy|unavailable|error, 'buf' => string, 'detail' => string].
 */
function leerSerialDotNet(string $port): array
{
    $out = ['status' => 'unavailable', 'buf' => '', 'detail' => ''];
    if (!function_exists('exec')) {
        $out['detail'] = 'exec() deshabilitado';
        return $out;
    }
    if (!preg_match('/^COM[0-9]{1,3}$/i', $port)) {
        $out['detail'] = 'puerto invalido';
        return $out;
    }
    $ps1 = __DIR__ . DIRECTORY_SEPARATOR . 'rfid_read.ps1';
    if (!is_file($ps1)) {
        $out['detail'] = 'falta rfid_read.ps1 junto al php';
        return $out;
    }
    $listenMs = RFID_TIMEOUT_SEC * 1000;
    // Comillas DOBLES: PowerShell las entiende ('mode.exe' no, por eso aquello va sin comillas).
    $cmd = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' . $ps1 . '"'
         . ' -Port ' . strtoupper($port)
         . ' -Baud ' . RFID_BAUD . ' -WaitResetMs 2000 -ListenMs ' . $listenMs . ' 2>&1';
    $lines = [];
    @exec($cmd, $lines, $code);
    $text = implode("\n", $lines);
    if ($code === 0) {
        $out['status'] = 'ok';
        $out['buf'] = $text;
    } elseif ($code === 2 || stripos($text, 'BUSY') !== false || stripos($text, 'denied') !== false) {
        $out['status'] = 'busy';
        $out['detail'] = trim($text) !== '' ? $text : 'exit 2';
    } elseif ($code === 3) {
        $out['status'] = 'error';
        $out['detail'] = trim($text) !== '' ? $text : 'exit 3';
    } else {
        // powershell ausente o fallo inesperado -> el llamador usa fread()
        $out['detail'] = 'powershell no disponible (exit ' . $code . '): ' . substr($text, 0, 200);
    }
    return $out;
}

/**
 * Interpreta el texto crudo del serial y completa el JSON de respuesta.
 * Lógica única usada por ambas vías (dotnet y fread).
 */
function interpretarBuffer(string $buf, array $response): array
{
    // Normalizar saltos y caracteres raros del serial
    $buf = str_replace(["\r"], '', $buf);
    $response['raw'] = substr($buf, 0, 2000);

    // Detector "lector vivo": el firmware con latido siempre envía
    // READY (arranque) o WAIT (sin tarjeta) o el bloque de tarjeta.
    $response['lector'] = (
        strpos($buf, 'READY') !== false ||
        strpos($buf, 'WAIT') !== false ||
        strpos($buf, 'TARJETA DETECTADA') !== false ||
        strpos($buf, 'UID:') !== false
    );

    if (trim($buf) === '') {
        $response['success'] = true;
        $response['error'] = 'Puerto abierto pero mudo: no llega ni el latido WAIT. Sube main.cpp (raíz del repo, trae READY/WAIT) al Uno y reintenta.';
        return $response;
    }

    // Parsear UID: "UID: A3 F1 2B 09"
    if (preg_match('/UID:\s*([0-9A-Fa-f ]{8,})/', $buf, $m)) {
        $uid = strtoupper(trim(preg_replace('/\s+/', ' ', $m[1])));
        $response['uid'] = $uid;
        $response['uid_compacto'] = str_replace(' ', '', $uid);
        $response['found'] = true;
    }

    // Parsear bloque: "Lectura de Bloque 4: ARDUINO NFC 2026"
    if (preg_match('/Lectura de Bloque\s*\d+\s*:\s*([^\n]+)/u', $buf, $b)) {
        $response['bloque'] = trim($b[1]);
    }

    // Flags extra
    if (strpos($buf, 'ESCRITURA EXITOSA') !== false) {
        $response['escritura_ok'] = true;
    }
    if (preg_match('/Error [^\n]+/u', $buf, $e)) {
        $response['error'] = trim($e[0]);
    }

    $response['success'] = true;
    if (!$response['found'] && $response['error'] === null) {
        $response['error'] = 'Se recibieron datos pero no se encontró línea "UID:". Revisa el raw.';
    }
    return $response;
}

/**
 * Abre COM5, lee lo que envía main.cpp y extrae UID + Bloque 4.
 * main.cpp imprime:
 *   --- TARJETA DETECTADA ---
 *   UID: A3 F1 2B 09
 *   Escribiendo en Bloque 4...
 *   >> ¡ESCRITURA EXITOSA! <<
 *   Lectura de Bloque 4: ARDUINO NFC 2026
 */
function leerTarjeta(): array
{
    $port = RFID_COM_PORT;
    $response = [
        'success' => false,
        'found' => false,
        'uid' => null,          // "A3 F1 2B 09"
        'uid_compacto' => null, // "A3F12B09"
        'bloque' => null,       // "ARDUINO NFC 2026"
        'escritura_ok' => false,
        'lector' => false, // true si llegó READY/WAIT/UID (firmware con latido)
        'via' => null, // "dotnet" (timeouts reales) o "fread" (fallback)
        'error' => null,
        'raw' => '',
        'hora' => date('H:i:s'),
    ];

    // 1. Configurar puerto (solo Windows). Sin comillas: 'mode' no acepta 'COM5' entrecomillado.
    // $port viene de la constante, formato validado COM + número.
    @exec('mode ' . $port . ' BAUD=' . RFID_BAUD . ' PARITY=n DATA=8 STOP=1 xon=off to=on 2>&1', $modeOut, $modeCode);

    // 1b. Candado entre requests: evita que 2 pestañas / polls solapados abran el puerto a la vez.
    $lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rfid_' . strtolower($port) . '.lock';
    $lockFp = @fopen($lockPath, 'c');
    $hasLock = $lockFp && @flock($lockFp, LOCK_EX | LOCK_NB);
    if (!$hasLock) {
        if ($lockFp) { @fclose($lockFp); }
        $response['error'] = 'Hay otra lectura en curso (otra pestaña o el poll automático). Deja una sola pestaña abierta.';
        return $response;
    }

    // 1c. Vía preferida: lector .NET (rfid_read.ps1) con timeouts reales.
    // No se cuelga aunque la placa esté muda; si el puerto está ocupado
    // lo informa; si powershell/helper faltan, se sigue con fread().
    $dn = leerSerialDotNet($port);
    if ($dn['status'] === 'busy') {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        $response['error'] = 'Puerto ' . $port . ' OCUPADO por otro programa (Monitor Serie de PlatformIO/Arduino IDE). Ciérralo y pulsa "Leer ahora". Solo un programa usa COM5 a la vez. Detalle: ' . substr($dn['detail'], 0, 300);
        return $response;
    }
    if ($dn['status'] === 'ok') {
        $response['via'] = 'dotnet';
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        return interpretarBuffer($dn['buf'], $response);
    }
    // status unavailable/error -> fallback fread() de abajo
    $response['via'] = 'fread';

    // 2. Abrir puerto. En PHP-Windows fopen('COM5') falla con "No such file";
    // hay que usar la ruta de dispositivo //./COM5. Se prueba esa primero y
    // SU error es el que manda para el diagnóstico (el de 'COM5' solo confunde).
    $candidatos = ['//./' . $port, $port];
    $fp = false;
    $errores = [];
    foreach ($candidatos as $cand) {
        $fp = @fopen($cand, 'r+b');
        if ($fp) { break; }
        $e = error_get_last();
        $errores[$cand] = $e['message'] ?? '?';
    }
    if (!$fp) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        // El error real es el de //./COM5 (primer intento).
        $realErr = $errores['//./' . $port] ?? reset($errores);
        // ¿Puerto ocupado por otro programa? En Windows es "Permission denied".
        if (stripos($realErr, 'denied') !== false || stripos($realErr, 'ocupado') !== false || stripos($realErr, 'busy') !== false || stripos($realErr, 'denegado') !== false) {
            $response['error'] = 'Puerto ' . $port . ' OCUPADO por otro programa (seguro el Monitor Serie de PlatformIO/Arduino IDE que tienes abierto). Ciérralo y pulsa "Leer ahora". Solo un programa usa COM5 a la vez. Detalle: ' . $realErr;
        } else {
            $response['error'] = 'No se pudo abrir ' . $port . '. Verifica que el Uno siga en ' . $port . ' (a veces cambia a COM6 al reconectar), cierra el Monitor Serie y reintenta. Detalle: ' . $realErr;
        }
        return $response;
    }

    // 3. El Uno se resetea al abrir el puerto: esperar 2s y limpiar el banner
    // de arranque. Drenaje CON TOPE (0.8s): el antiguo while sin tope se
    // colgaba porque fread() sobre serie en Windows bloquea si no hay datos.
    // Requiere el firmware con latido (READY/WAIT, ver arduino-rfid/main.cpp):
    // con bytes llegando cada 1.5s, fread retorna y los topes funcionan.
    stream_set_blocking($fp, false);
    @stream_set_timeout($fp, 1);
    sleep(2);
    $tDrain = microtime(true);
    while ((microtime(true) - $tDrain) < 0.8) {
        $c = @fread($fp, 1024);
        if ($c === false || $c === '') {
            usleep(100000); // 0.1s
        }
    }

    // 4. Escuchar hasta RFID_TIMEOUT_SEC segundos
    $buf = '';
    $t0 = microtime(true);
    while ((microtime(true) - $t0) < RFID_TIMEOUT_SEC) {
        $chunk = @fread($fp, 256);
        if ($chunk !== false && $chunk !== '') {
            $buf .= $chunk;
        } else {
            usleep(100000);
        }
    }
    fclose($fp);
    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);

    // 5-7. Parseo compartido con la vía dotnet (ver interpretarBuffer()).
    return interpretarBuffer($buf, $response);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lector RFID RC522 — COM5</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; background: #f2f2f2; margin: 0; padding: 24px; color: #222; }
  .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
  h1 { margin: 0 0 4px; font-size: 22px; }
  .sub { color: #666; font-size: 14px; margin-bottom: 16px; }
  .estado { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: bold; background: #eee; }
  .estado.ok { background: #d4edda; color: #155724; }
  .estado.espera { background: #fff3cd; color: #856404; }
  .estado.error { background: #f8d7da; color: #721c24; }
  .dato { margin-top: 16px; }
  .dato small { color: #666; display: block; margin-bottom: 4px; }
  .valor { font-size: 28px; font-weight: bold; letter-spacing: 2px; background: #f7f7f7; border: 1px dashed #bbb; border-radius: 8px; padding: 12px; word-break: break-all; }
  .valor.bloque { font-size: 20px; letter-spacing: 1px; }
  .fila { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
  button, a.btn { padding: 10px 18px; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; text-decoration: none; }
  button { background: #1a73e8; color: #fff; }
  button:disabled { opacity: .6; cursor: wait; }
  a.btn { background: #e0e0e0; color: #222; }
  label { font-size: 14px; color: #444; display: flex; align-items: center; gap: 6px; margin-top: 14px; }
  details { margin-top: 16px; font-size: 13px; }
  pre { background: #111; color: #0f0; padding: 12px; border-radius: 8px; overflow: auto; max-height: 220px; white-space: pre-wrap; }
  .hint { margin-top: 14px; font-size: 13px; color: #555; background: #f9f9f9; border-left: 4px solid #1a73e8; padding: 10px 12px; border-radius: 0 8px 8px 0; }
</style>
</head>
<body>
<div class="card">
  <h1>Lector RFID-RC522 <span style="font-size:14px;color:#666;">(<?php echo htmlspecialchars(RFID_COM_PORT); ?>)</span></h1>
  <div class="sub">Arduino Uno + <code>main.cpp</code> a 9600 baud. Acerca una tarjeta al lector.</div>

  <span id="estado" class="estado espera">Esperando lectura…</span>

  <div class="dato">
    <small>UID de la tarjeta</small>
    <div id="uid" class="valor">—</div>
  </div>

  <div class="dato">
    <small>Contenido Bloque 4</small>
    <div id="bloque" class="valor bloque">—</div>
  </div>

  <div class="dato" style="font-size:13px;color:#555;">
    Última lectura: <span id="hora">—</span> <span id="via" style="color:#999;"></span>
    <span id="msg"></span>
  </div>

  <div class="fila">
    <button id="btnLeer" type="button">Leer ahora</button>
    <button id="btnDiag" type="button" style="background:#555;">Diagnosticar puerto</button>
    <a class="btn" href="index.php">← Volver a biblioteca</a>
  </div>

  <label><input type="checkbox" id="auto" checked> Lectura automática (cada ~8 segundos, una a la vez)</label>

  <div class="hint">
    Si dice “no se pudo abrir”: cierra el <b>Monitor Serie / PlatformIO / Arduino IDE</b>,
    verifica que el Uno siga en <b>COM5</b> (al reconectar a veces pasa a COM6),
    deja <b>una sola pestaña</b> abierta y pulsa <b>Diagnosticar puerto</b> para ver el motivo exacto.
  </div>

  <details>
    <summary>Ver datos crudos del puerto (debug)</summary>
    <pre id="raw">(sin datos)</pre>
  </details>
  <details>
    <summary>Diagnóstico del puerto</summary>
    <pre id="diag">(pulsa “Diagnosticar puerto”)</pre>
  </details>
</div>

<script>
(function () {
  var estado = document.getElementById('estado');
  var uidEl = document.getElementById('uid');
  var bloqueEl = document.getElementById('bloque');
  var horaEl = document.getElementById('hora');
  var viaEl = document.getElementById('via');
  var msgEl = document.getElementById('msg');
  var rawEl = document.getElementById('raw');
  var btn = document.getElementById('btnLeer');
  var btnDiag = document.getElementById('btnDiag');
  var auto = document.getElementById('auto');
  var diagEl = document.getElementById('diag');
  var leyendo = false;
  // URL robusta: funciona con php -S en cualquier docroot, XAMPP o subcarpeta
  var base = window.location.pathname;

  function setEstado(texto, clase) {
    estado.textContent = texto;
    estado.className = 'estado ' + clase;
  }

  function leer() {
    if (leyendo) return;
    leyendo = true;
    btn.disabled = true;
    setEstado('Leyendo ' + '<?php echo RFID_COM_PORT; ?>' + '… acerca la tarjeta', 'espera');

    fetch(base + '?read=1&t=' + Date.now(), { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        horaEl.textContent = d.hora || '—';
        rawEl.textContent = d.raw || '(vacío)';
        if (!d.success) {
          setEstado('Error de puerto', 'error');
          msgEl.textContent = ' — ' + (d.error || 'desconocido');
          return;
        }
        if (d.found) {
          uidEl.textContent = d.uid + (d.uid_compacto ? ' (' + d.uid_compacto + ')' : '');
          bloqueEl.textContent = d.bloque || '(bloque no leído)';
          setEstado('Tarjeta detectada', 'ok');
          msgEl.textContent = d.escritura_ok ? ' — escritura OK' : '';
        } else if (d.lector) {
          setEstado('Lector conectado — acerca la tarjeta', 'espera');
          msgEl.textContent = ' — mantén la tarjeta apoyada 2-3 segundos durante “Leyendo…”';
        } else {
          setEstado('Sin respuesta del lector', 'espera');
          msgEl.textContent = d.error ? ' — ' + d.error : '';
        }
      })
      .catch(function (err) {
        setEstado('Error HTTP', 'error');
        msgEl.textContent = ' — ' + err;
      })
      .finally(function () {
        leyendo = false;
        btn.disabled = false;
      });
  }

  function ciclo() {
    leer();
    // Cada request tarda ~5s (2s reset del Uno + 0.8s drenaje + 3s escucha).
    // Con setTimeout encadenado nunca se solapan dos aperturas de COM5.
    // (El servidor PHP de desarrollo es monohilo: solapar colapsa todo.)
    setTimeout(function () { if (auto.checked) ciclo(); else setTimeout(ciclo, 1000); }, 8000);
  }

  btn.addEventListener('click', leer);
  btnDiag.addEventListener('click', function () {
    diagEl.textContent = 'Consultando diagnóstico…';
    fetch(base + '?diag=1&t=' + Date.now(), { cache: 'no-store' })
      .then(function (r) { return r.text(); })
      .then(function (t) {
        try { diagEl.textContent = JSON.stringify(JSON.parse(t), null, 2); }
        catch (e) { diagEl.textContent = t; }
      })
      .catch(function (err) { diagEl.textContent = 'Error: ' + err; });
  });
  leer(); // primera lectura al abrir
  setTimeout(ciclo, 8000);
})();
</script>
</body>
</html>
