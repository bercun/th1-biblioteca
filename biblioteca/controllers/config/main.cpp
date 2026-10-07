#include <Arduino.h>
#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN 8   // Reset en Pin 8
#define SS_PIN  10  // SDA en Pin 10

MFRC522 rfid(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;

// Bloque 4 = Primer bloque de datos del Sector 1
const byte bloqueDatos = 4;

// Mensaje a grabar (Debe ser de EXACTAMENTE 16 bytes)
byte datosAEscribir[16] = {
  'A', 'R', 'D', 'U', 'I', 'N', 'O', ' ', 
  'N', 'F', 'C', ' ', '2', '0', '2', '6'
};

void setup() {
  Serial.begin(9600);
  while (!Serial); // Esperar Monitor Serie

  SPI.begin();
  rfid.PCD_Init();

  // Clave por defecto de fábrica en tarjetas Mifare (FF FF FF FF FF FF)
  for (byte i = 0; i < 6; i++) {
    key.keyByte[i] = 0xFF;
  }

  Serial.println("==========================================");
  Serial.println("RC522 Conectado. Acerca una tarjeta...");
  Serial.println("==========================================");
  Serial.println("READY"); // Latido para la página PHP: no quitar
}

// Latido para la página PHP (no quitar): el PHP lee COM5 con fread()
// bloqueante y se cuelga si el puerto está mudo. WAIT cada 1.5 s
// mantiene viva la lectura y prueba que el lector está conectado.
unsigned long lastWait = 0;

void loop() {
  // 1. Detectar tarjeta (con latido si no hay ninguna cerca)
  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
    if (millis() - lastWait > 1500) {
      lastWait = millis();
      Serial.println("WAIT");
    }
    return;
  }

  Serial.println("\n--- TARJETA DETECTADA ---");
  Serial.print("UID:");
  for (byte i = 0; i < rfid.uid.size; i++) {
    Serial.print(rfid.uid.uidByte[i] < 0x10 ? " 0" : " ");
    Serial.print(rfid.uid.uidByte[i], HEX);
  }
  Serial.println();

  MFRC522::StatusCode status;

  // 2. Autenticación en Bloque 4 usando Clave A
  status = rfid.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A, bloqueDatos, &key, &(rfid.uid));
  if (status != MFRC522::STATUS_OK) {
    Serial.print("Error Autenticacion: ");
    Serial.println(rfid.GetStatusCodeName(status));
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return;
  }

  // 3. Escribir Datos
  Serial.print("Escribiendo en Bloque ");
  Serial.print(bloqueDatos);
  Serial.println("...");
  
  status = rfid.MIFARE_Write(bloqueDatos, datosAEscribir, 16);
  if (status == MFRC522::STATUS_OK) {
    Serial.println(">> ¡ESCRITURA EXITOSA! <<");
  } else {
    Serial.print("Error al escribir: ");
    Serial.println(rfid.GetStatusCodeName(status));
  }

  // 4. Leer Datos para verificar
  byte bufferLectura[18];
  byte tamanoBuffer = sizeof(bufferLectura);

  status = rfid.MIFARE_Read(bloqueDatos, bufferLectura, &tamanoBuffer);
  if (status == MFRC522::STATUS_OK) {
    Serial.print("Lectura de Bloque ");
    Serial.print(bloqueDatos);
    Serial.print(": ");

    // Imprimir los 16 bytes como caracteres ASCII
    for (byte i = 0; i < 16; i++) {
      Serial.write(bufferLectura[i]);
    }
    Serial.println();
  } else {
    Serial.print("Error al leer: ");
    Serial.println(rfid.GetStatusCodeName(status));
  }

  // 5. Finalizar sesión con el tag
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  lastWait = millis(); // evita un WAIT inmediato tras leer una tarjeta
  delay(2000); // Pausa para evitar lecturas continuas
}