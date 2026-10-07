# rfid_read.ps1 — Lector serie para rfid_simple.php (NO usar a mano salvo debug).
# Abre COM5 a 9600 8N1, espera el reset del Uno, lee lineas durante N ms
# con ReadTimeout real (NO se cuelga aunque no haya tarjeta) e imprime
# el texto tal cual a stdout. Codigos de salida:
#   0 = OK (puede haber 0 lineas si no hubo tarjeta ni latido)
#   2 = puerto OCUPADO por otro programa (Monitor Serie / PlatformIO)
#   3 = otro fallo (puerto inexistente, parametro mal, etc.)
# Uso manual: powershell -NoProfile -ExecutionPolicy Bypass -File rfid_read.ps1 -Port COM5
param(
  [string]$Port = 'COM5',
  [int]$Baud = 9600,
  [int]$WaitResetMs = 2000,
  [int]$ListenMs = 3000
)
$ErrorActionPreference = 'Stop'
if ($Port -notmatch '^(?i)COM[0-9]{1,3}$') { Write-Error "Puerto invalido: $Port"; exit 3 }
try {
  $sp = New-Object System.IO.Ports.SerialPort($Port, $Baud, 'None', 8, 'One')
  $sp.ReadTimeout = 500
  $sp.WriteTimeout = 500
  $sp.DtrEnable = $true
  $sp.RtsEnable = $true
  $sp.Open()
} catch [System.UnauthorizedAccessException] {
  Write-Error 'BUSY'
  exit 2
} catch {
  Write-Error $_.Exception.Message
  exit 3
}
try {
  Start-Sleep -Milliseconds $WaitResetMs   # el Uno se resetea al abrir
  $sp.DiscardInBuffer()                    # tirar banner de arranque
  $sw = [Diagnostics.Stopwatch]::StartNew()
  while ($sw.ElapsedMilliseconds -lt $ListenMs) {
    try {
      $line = $sp.ReadLine()               # TimeoutException cada 500ms sin datos: normal
      Write-Output $line
    } catch [System.TimeoutException] {
      # sin datos en esta ventana, seguir escuchando hasta ListenMs
    }
  }
} finally {
  $sp.Close()
}
exit 0
