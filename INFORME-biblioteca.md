# Informe Técnico — Proyecto Biblioteca / Venta de Libros Online
**UTU Uruguay — Proyecto 2do semestre**
**Fecha:** 2026-10-06
**Carpeta analizada:** `biblioteca/`
**Branding en código:** `Brooklyn Public Library` / Equipo Vortex

---

## 1. Resumen ejecutivo

El proyecto es una **página web de venta de libros online** funcional, con arquitectura **pseudo-MVC sin framework**:

- Frontend público (`front/`) en HTML + CSS + JS vanilla con `fetch`.
- Panel administrador (`admin/`) separado.
- Backend API JSON en PHP (`controllers/`) que delega a modelos PDO (`models/`).
- Base de datos MySQL (`bd/`).
- Autenticación por sesión PHP `$_SESSION['user_id']`.

**Estado general:** Núcleo completo para un proyecto UTU: auth, catálogo por temporada, buscador, compra directa, reseñas solo si compró, CRUD admin con roles. **No hay** carrito de compras, ni pasarela de pago real, ni descarga de libros, ni favoritos por usuario (lo llamado "favorites" es catálogo por estación).

---

## 2. Estructura de carpetas y arquitectura

```
biblioteca/
  front/
    index.php          # SPA pública: About + Favorites + Search + Help + modales
    style.css
    media query.css    # ¡nombre con espacio! propenso a 404
    js/
      const.js         # API_BASE, ADMIN_API, endpoints
      utils.js         # createBookCardElement(), setContainerMessage(), formatDateTime()
      register.js      # auth + sesión + compra (buyBook, handleLogin) ~817 líneas, núcleo
      favorites.js     # catálogo por temporada
      search.js        # búsqueda
      reviews.js       # rating promedio + estrellas en perfil
      logout.js
      index.js         # burger, modales, slider About
    assets/images/     # book1..16.png, bookDefault.png, image1..5.png, map.png, bg.jpg
    assets/icons/      # *.svg, vortex.png
    assets/favicon/
  admin/
    index.php          # Dashboard con tabs Books/Users, form CRUD libro
    admin.js           # CRUD + lista usuarios + compras ~543 líneas
    admin.css
  controllers/
    users/login.php, register.php, current.php, logout.php
    books/favorites.php, search.php, buy.php, review.php
    admin/books_list.php, books_create.php, books_update.php, books_delete.php
    admin/users_list.php, users_purchases.php
    config/require_admin.php, book_image.php
  models/
    Database.php, Model.php, User.php, Book.php, BookCategory.php, Review.php, UserBook.php
  bd/
    biblioteca.sql                 # schema completo + seed 16 libros + admin
    add_admin_role.sql
    add_reviews.sql
    add_book_price.sql
    add_books_isbn.sql
    add_user_books_purchased_at.sql
    migrate_books_category.sql
```

**Patrón:** sin router, sin composer, sin ORM. `front/admin/*.php` son solo HTML + JS. Toda la lógica es API JSON en `controllers/*.php` → `models/*.php` con PDO MySQL.

**Flujo de datos:**

```
index.js (UI) → const.js (URLs /biblioteca/controllers/*)
register.js: fetch POST JSON register/login/buy/review + GET currentUser
favorites.js/search.js: fetch GET favorites.php?season / search.php?q=
utils.js: createBookCardElement() DOM puro con textContent
  → controllers/*.php: header JSON, session_start(), json_decode php://input
  → models/*.php: PDO prepare/execute, mapRow() → JsonSerializable
  → MySQL biblioteca
  → JSON → JS actualiza DOM, sin reload (excepto logout → reload)
admin.js: FormData (multipart por upload imagen) → admin/*.php → require_admin.php + book_image.php
```

**Librerías:**
- Backend: solo PHP PDO + `password_hash`, `finfo`, `random_bytes`. Sin framework.
- Frontend: vanilla JS, `fetch`, `FormData`, Google Fonts `Forum+Inter`, `FontAwesome kit e7c741f98a.js` para X/Instagram/Facebook. Sin jQuery/React.

---

## 3. Funcionalidades por módulo

### 3.1 Auth / Usuarios — Implementado completo

- Modales en `front/index.php`: `#modal-login`, `#modal-register`, `#modal-profile`.
- `controllers/users/register.php`: valida campos, `filter_var(email)`, `strlen>=8`, `emailExists()` → `409`, `password_hash(PASSWORD_DEFAULT)`, `$_SESSION[user_id]=id`.
- `controllers/users/login.php`: `findByEmail()` + `password_verify()` → `401` si falla, `incrementLoginCount()`.
- `controllers/users/current.php`: si no hay sesión retorna `null`; si hay, retorna `id, first_name, last_name, email, login_count, bonus, role, books_count, books[]` con `UserBook::listOwnedByUser()`.
- `controllers/users/logout.php`: vacía `$_SESSION`, borra cookie, `session_destroy()`.
- `front/js/register.js`: `normalizeUser()`, `loadCurrentUser()` al `DOMContentLoaded` restaura sesión, `handleLogin()` oculta `#button-user`, muestra `#dropmenu`, iniciales en avatar, `loginCount` en `#visit-counter-profile`, `bonus` en `.bonuses`, muestra link Admin si `role==admin`, `updateOwnedBooksInterface()`.

### 3.2 Catálogo / Favorites — Implementado pero mal nombrado

- **No es "favoritos de usuario".** Es catálogo por `season`: `winter/spring/summer/autumn` = `books_category.id 1-4`.
- `controllers/books/favorites.php` valida `$_GET[season]` contra allowlist, llama `Book::listBySeason(season|null)`.
- `front/js/favorites.js`: `loadFavoriteBooks()` hace `fetch(API.booksFavorites)` **sin parámetro** → trae todo, `renderFavoriteBooks()` crea cards con clase `favorites-items {season} hidden`, luego `showSeasonBooks()` solo desoculta la temporada del radio. Funciona pero ineficiente.

### 3.3 Búsqueda — Implementada

- `controllers/books/search.php`: `$_GET[q]`, vacío → `[]`. `Book::search()` con `LIKE %q%` en `title/author/isbn` + manejo especial ISBN sin guiones/espacios, `AVG(rating)`, `COUNT`.
- `front/js/search.js`: `Enter` o click, `renderSearchResults()` con `createBookCardElement()`, marca `Own` si ya comprado.

### 3.4 Compra — Implementada como "compra directa", SIN carrito

- Card generada en `utils.js:createBookCardElement()`: título, autor, ISBN, descripción, `+N bonus`, `$price`, rating, botón `Buy[data-book-id][data-book-price]` + botón `Own[disabled][hidden]`.
- Click `.buy-before-login`: si `!currentUser` → `showLoginModal()`; si ya owned → `markBookAsOwned()` + alert; sino `showBuyBookModal(price)`.
- Modal `#modal-buy-book` pide `bank, code-part1/2, CVC, name, postal, city` — **todo solo frontend `required`, nunca se envía al backend**.
- `buyBook(bookId)` → `POST controllers/books/buy.php {bookId}`. Backend: exige sesión `401`, valida `bookId>0`, `findForPurchase()`, `userOwnsBook()` → `409`, `purchase()` + `addBonus(bonus)`, retorna `book, booksCount, bonus`. Frontend actualiza contadores, `prepend` en `#my-books`.
- No hay pasarela real, no se valida precio/stock/tarjeta, no hay carrito ni checkout múltiple.

### 3.5 Reseñas — Implementado solo si compró

- `models/Review.php:save()` con `INSERT ... ON DUPLICATE KEY UPDATE rating`.
- `controllers/books/review.php`: exige login `401`, `rating 1-5`, `userOwnsBook()` sino `403`.
- `front/js/reviews.js`: `createBookRatingElement()` → `★ 4.5 (3 reviews)` o `★ No rating`; `createProfileStarRating()` 5 botones ★ en `#my-books`; click → `saveBookReview()` → `paintProfileStars()` + `updateBookCardsAverage()`; hover con clase `.hover`.

### 3.6 Panel Admin — CRUD completo, roles

- `admin/index.php`: header con `#admin-user-label`, link Library, tabs Books/Users, form libro `title/author/isbn/category/price/bonus/image-file+hidden/description`, tablas `#books-table`, `#users-table`, `#purchases-table`.
- `admin/admin.js`: `initAdmin()` → `GET currentUser`, si no admin bloquea; `loadBooks()` / `saveBook(FormData)` / `deleteBook(confirm)` / `loadUsers()` / `loadPurchases(?user_id=)`.
- `controllers/config/require_admin.php`: `session_start()`, `401` si no login, `403` si `role!=admin`. Usado en los 6 endpoints admin.
- `controllers/admin/books_create|update.php`: usan `getAdminBookRequestData()` soporta `multipart/form-data` + JSON, validan título/autor/descripción/categoría, `bonus/price>=0`, `BookCategory::exists()`, `resolveBookImagePath()`, capturan `PDOException` por ISBN duplicado → `409`.
- `controllers/admin/users_list.php`: `User::listForAdmin()` con `COUNT(user_books)`.
- `controllers/admin/users_purchases.php`: `?user_id=` → `UserBook::listPurchasesForAdmin()`.
- Roles: `users.role ENUM(user,admin)`. Seed `admin@test.com / 12345678`.

---

## 4. Esquema de base de datos

Fuente: `bd/biblioteca.sql`

- `users(id PK, first_name(100), last_name(100), email(150) UNIQUE, password(255) hash, login_count, bonus, role ENUM user/admin)`
- `books_category(id PK, season ENUM winter/spring/summer/autumn UNIQUE)`
- `books(id PK, title(255), author(255), isbn(20) NULL UNIQUE, description TEXT, category_id FK→books_category RESTRICT/CASCADE, image(255), bonus INT DEFAULT 1, price DECIMAL(8,2) DEFAULT 1.00)`
- `user_books(user_id FK→users CASCADE, book_id FK→books CASCADE, purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PK(user_id,book_id))` — tabla de compras/propiedad, no hay favoritos ni carrito.
- `reviews(id PK, user_id FK CASCADE, book_id FK CASCADE, rating TINYINT CHECK 1-5, created_at, updated_at, UNIQUE(user_id,book_id))`

Relaciones: 1 categoría → N libros; N:M usuarios-libros vía `user_books`; 1 usuario puede dejar 1 rating por libro vía `reviews`, promedio calculado con `AVG`.

Seed: 16 libros, 4 por temporada, precios $1.00-$2.50, algunas ISBN NULL.

---

## 5. Puntos fuertes

- Uso consistente de `prepare()` + placeholders en `User/Book/Review/UserBook` — no hay concatenación SQL.
- Passwords con `password_hash/verify`, nunca se expone hash en `toArray()` / `current.php` / `listForAdmin()`.
- Guard admin centralizado, validación de `season`, `rating 1-5`, `bookId>0`, ISBN-duplicado → `409`.
- Upload con `finfo MIME`, allowlist `jpg/png/webp/gif`, límite 2MB, `is_uploaded_file` + `move_uploaded_file`, nombre `book_Ymd_His_randomhex.ext`, evita colisiones.
- Frontend XSS-safe: todo con `createElement/textContent/replaceChildren`, fallback `onerror→bookDefault.png`, sin `innerHTML`.
- `CHECK(rating)`, `UNIQUE(user_id,book_id)`, `FK CASCADE/RESTRICT`, `ON DUPLICATE KEY UPDATE` para re-votar bien pensado.

---

## 6. Problemas encontrados

### 6.1 Críticos — BD / instalación (te impiden instalar en limpio)

- `bd/biblioteca.sql:79-268` el `INSERT INTO books ... VALUES (...),(...);` termina en `)` sin `;` antes del comentario admin → error de sintaxis al importar.
- `bd/biblioteca.sql` nunca hace `INSERT INTO books_category (1,winter)...` pero `books.category_id=1..4` con FK → falla FK en instalación limpia. Solo `migrate_books_category.sql` lo inserta. Hay que ejecutar migraciones en orden.
- Credenciales `models/Database.php:17-23` hardcode `localhost/biblioteca/root/''`, sin variables de entorno.

### 6.2 Seguridad

- Sin `session_regenerate_id()` tras login/register → fijación de sesión. Sin `session_set_cookie_params(httponly,secure,samesite)`, sin timeout, sin rate-limit → fuerza bruta login/register posible. Sin CSRF tokens (aunque Same-Origin + JSON mitiga algo, `books_delete` y `logout` POST sin token).
- Hash admin `admin@test.com / 12345678` publicado en claro en 2 `.sql` + contraseña débil de 8 dígitos.
- `resolveBookImagePath()` si no hay upload acepta cualquier string `image` sin validar URL/path → admin malicioso podría guardar `javascript:` / `../../etc` o URL externa; se renderiza directo en `<img src>`. Falta allowlist `../front/assets/images/` o `http(s)`.
- Imágenes huérfanas: update/delete nunca borra archivo anterior de `front/assets/images/`.
- `User::findByEmail() SELECT *` carga hash en memoria; correcto aquí pero riesgoso si se serializa con `includePassword=true`.
- Datos tarjeta se piden (`type=number minlength/maxlength` — `maxlength` ignorado en `number`, pierde ceros iniciales, debería ser `text inputmode=numeric pattern`) pero jamás se validan/cargan — falsa sensación de pago, incumple PCI si algún día se guarda.
- Enumeración de usuarios por mensajes distintos: `Invalid credentials` genérico bien, pero `409 User already exists` en register permite enumerar emails.

### 6.3 Lógica / incompleto

- Sin carrito real, sin historial de pagos, sin descarga de libro tras compra (texto dice "available for download" pero no hay link/archivo).
- `favorites.js` trae todos los libros y filtra con `.hidden` en cliente — desperdicia ancho de banda, `favorites.php?season=winter` nunca se usa con param.
- `buy.php:85` `purchase()` sin `try/catch` ni transacción: si doble-click concurrente, segundo `INSERT` PK duplicada → `500` en vez de `409`; además `purchase + addBonus` no son atómicos (si falla bonus queda compra sin bonus).
- `Book::search()` sin `LIMIT` ni escape `%_`, `ORDER BY title` sin paginación.
- `front/index.php:235` `type=text` para email-login (debería `email`), validación solo en register.
- `register.js:256,299` referencia `#username` y `#visit-counter-card` que no existen en HTML (solo `username-profile` / `visit-counter-profile`) → código muerto.
- `front/index.php:67` link admin usa `href=/biblioteca/admin/index.php` absoluto — rompe si app no está en raíz. `const.js` igual con `/biblioteca/...`.
- `admin/index.php` sin guard PHP, solo JS `initAdmin()` — ve HTML vacío + `Checking access...` antes de bloquear; API sí protegida pero UX/flash mejorable con redirect server-side.
- `DEFAULT_IMAGE='../front/assets/images/bookDefault.png'` relativo frágil desde `admin/` vs `front/`; funciona por casualidad de estructura.

### 6.4 Diseño / i18n

- `lang=en`, todo en inglés, pero proyecto UTU Uruguay español — inconsistencia. Footer mezcla `286 Cadman Plaza, NY` + teléfono US + `ESI, Montevideo`.
- `style.css + media query.css` con nombre con espacio (`media query.css`) — requiere quoting `%20`, propenso a 404 en servidores estrictos.
- Slider `index.js:193-201` con `showCount 3` en `>=1400px` falla en cola final (muestra <3), flechas se deshabilitan por `style.display==block` frágil.
- Múltiples `DOMContentLoaded` y `body click` listeners duplicados para burger/user-menu, uso de `event._isClickWithInMenu` poco estándar; `overlay` usa `toggle` vs `add/remove` inconsistente puede dejar modal abierto.

No se encontraron `TODO/FIXME`, no hay `eval/innerHTML`.

---

## 7. Comparación con idea típica de proyecto UTU

| Lo que suele pedirse | Estado en tu código |
|---|---|
| Registro / Login / Logout | ✅ Completo |
| Catálogo de libros | ✅ Por temporada, 16 libros seed |
| Buscador | ✅ Por título/autor/ISBN |
| Detalle libro | ✅ Parcial en card (sin página detalle) |
| Carrito | ❌ No existe, es compra directa 1x1 |
| Checkout / pago real | ❌ Modal visual, no valida ni cobra |
| Favoritos por usuario | ❌ Mal nombrado, son categorías |
| Mis libros comprados | ✅ En perfil `#my-books` |
| Reseñas / rating | ✅ Solo si compró, 1-5 |
| Bonus / puntos | ✅ Suma bonus por compra |
| Panel Admin CRUD libros | ✅ Completo con imagen |
| Gestión usuarios / compras | ✅ Solo lectura |
| Roles user/admin | ✅ `ENUM` + `require_admin.php` |
| Responsive | ⚠️ Existe `media query.css` pero con fallas slider/burger |

---

## 8. Recomendaciones priorizadas para entregar

1. **Arreglar instalación:** agregar `;` faltante en `biblioteca.sql`, agregar `INSERT INTO books_category`, documentar orden de ejecución en un `README`.
2. **Renombrar "Favorites" → "Catálogo" o implementar favoritos reales** (tabla `favorites(user_id,book_id)`), para no confundir al tribunal.
3. **Decidir carrito:** o implementas `cart` en `localStorage` + checkout múltiple, o declaras en documentación "alcance: compra directa sin carrito".
4. **Seguridad mínima:** `session_regenerate_id(true)` tras login, cambiar password admin seed, usar paths relativos en `const.js`, validación `email` en login, quitar campos tarjeta o aclarar "simulación".
5. **i18n:** pasar `lang=es`, traducir UI o justificar inglés, corregir footer a Montevideo UTU.
6. **Renombrar `media query.css` → `media-query.css`** y actualizar `<link>`.
7. **Transacción en compra:** `BEGIN; INSERT user_books; UPDATE users bonus; COMMIT;` + catch duplicado → `409`.
8. **Agregar paginación `LIMIT 20` en search + `favorites.php?season=`** para eficiencia.

---

## 9. Archivos clave para mostrar al profesor

- `front/index.php` — UI pública
- `front/js/register.js` — núcleo auth + compra
- `front/js/utils.js` — `createBookCardElement()`
- `controllers/books/buy.php` + `models/UserBook.php` — compra
- `controllers/books/review.php` + `models/Review.php` — reseñas
- `admin/index.php` + `admin/admin.js` — CRUD
- `models/Database.php` — conexión
- `bd/biblioteca.sql` — esquema

*Informe generado por revisión estática read-only, sin ejecución.*
