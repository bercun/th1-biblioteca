# th1-biblioteca — Venta de libros online + lector RFID

Proyecto 2do semestre (UTU Uruguay, Equipo Vortex): página web de venta de
libros online (**Brooklyn Public Library**) en PHP vanilla + MySQL, con
panel administrador, y lector de tarjetas **RFID-RC522** con Arduino Uno
integrado a una página PHP que muestra el UID en vivo.

## Estructura

```
biblioteca/        # App web (pseudo-MVC sin framework)
  front/           # SPA pública: catálogo, buscador, compra, perfil
    index.php
    rfid_simple.php  # Página lector RFID (COM5) — ver card.md
    rfid_read.ps1    # Helper serie .NET con timeouts reales
    js/ assets/ ...
  admin/           # Panel admin (CRUD libros, usuarios, compras)
  controllers/     # API JSON (users, books, admin, config)
  models/          # PDO: Database, User, Book, Review, UserBook...
  bd/              # biblioteca.sql + migraciones
main.cpp           # Firmware Arduino Uno + RC522 (con latido READY/WAIT)
card.md            # Doc del lector RFID: instalación, uso, fallos
INFORME-biblioteca.md  # Informe técnico de la app web
```

## Requisitos

- PHP 8.2 + MySQL (XAMPP/Laragon) o `php -S` para desarrollo.
- App web: navegador moderno, sin dependencias (JS vanilla).
- Lector RFID (solo Windows): Arduino Uno en **COM5** + RC522,
  PlatformIO o Arduino IDE para subir `main.cpp`, PowerShell disponible.

## Puesta en marcha

### 1. Base de datos

Importar en orden en MySQL (`root` sin password, BD `biblioteca`):

1. `biblioteca/bd/biblioteca.sql`
2. Migraciones de `biblioteca/bd/` (`add_*.sql`, `migrate_books_category.sql`)

> Ojo: `biblioteca.sql` necesita las categorías insertadas
> (`migrate_books_category.sql`) por la FK `books.category_id`.
> Detalle completo en `INFORME-biblioteca.md` §6.1.

### 2. Servidor web

- XAMPP: clonar dentro de `htdocs` como `biblioteca/` →
  `http://localhost/biblioteca/front/index.php` (admin: `.../admin/index.php`,
  seed `admin@test.com / 12345678`).
- Dev rápido: `php -S localhost:8000` desde la raíz del repo →
  `http://localhost:8000/biblioteca/front/index.php`.

### 3. Lector RFID

1. Subir `main.cpp` al Uno (SDA→D10, RST→D8, MOSI→D11, MISO→D12,
   SCK→D13, VCC→**3.3V**, GND→GND).
2. Abrir el Monitor Serie a 9600: debe salir `READY` y `WAIT` cada 1.5 s;
   con tarjeta, el bloque `UID:` + `Lectura de Bloque 4:`.
3. **Cerrar el monitor** (COM5 solo lo usa un programa a la vez).
4. Abrir `.../front/rfid_simple.php`: verás `[vía dotnet]` y
   *“Lector conectado — acerca la tarjeta”*. Apoya la tarjeta 2-3 s
   durante *“Leyendo…”* → aparece UID + contenido del bloque 4.
5. Si algo falla, pulsa **Diagnosticar puerto** (`?diag=1`): dice si el
   puerto está libre, ocupado (monitor abierto) o si el Uno cambió de COM.

Todo el detalle (arquitectura, por qué no `fopen('COM5')` directo,
significado del `[404]` del `php -S`, l al JSON (`?read=1` / `?diag=1`).

## Funcionalidad (app web)

Registro/login/logout con sesión, catálogo por temporada, buscador por
título/autor/ISBN, compra directa con bonus, reseñas 1-5 (solo si compró),
mis libros, panel admin con CRUD + roles. Sin carrito ni pago real
(compra simulada de a un libro). Auditoría completa en
`INFORME-biblioteca.md`.

## Límites conocidos

- RFID: una sola pestaña a la vez, ~6 s por lectura (el Uno se resetea al
  abrir COM5); versión simple sin BD (fase 2: vincular UID a usuario).
- Web: paths absolutos `/biblioteca/...`, `media query.css` con espacio,
  credenciales DB hardcodeadas (`models/Database.php`). Ver informe §6-8.
