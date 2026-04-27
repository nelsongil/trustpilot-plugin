# Changelog

Todos los cambios notables del plugin **Custom Trustpilot Reviews** se documentan aquí.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es/1.1.0/) y el versionado semántico ([SemVer](https://semver.org/lang/es/)).

`CHANGELOG.md` es la **fuente de verdad**. El `readme.txt` (que WordPress.org consume) se mantiene sincronizado a mano antes de cada release.

---

## [2.1] — 2026-04-26

### Fixed
- **Crítico**: la extracción de reseñas estaba rota tras el cambio de Trustpilot a Next.js con clases CSS hasheadas. Sustituido por parser del JSON embebido en `__NEXT_DATA__`, mucho más estable. Si Trustpilot vuelve a mover el JSON, hay un walker recursivo de respaldo que detecta listas con forma de review.
- Updater: `check_for_updates()` ya no peta cuando WordPress pasa `false` como transient inicial.
- Cron huérfano al desactivar: `wp_clear_scheduled_hook` apuntaba a `ctr_clear_cache` (no usado) en vez de `ctr_check_for_updates`.
- Firma de `Ctr_Trustpilot_Module::render()` ahora compatible con PHP 8+ (parámetro con default).
- Estado de `libxml_use_internal_errors` se restaura tras el parsing.

### Removed
- `ctr_extract_reviews_alternative()`: heurística peligrosa que agarraba cualquier `<p>` >50 caracteres y lo mostraba como reseña con autor "Cliente Anónimo" y 5★ inventadas.
- Carpeta nested duplicada `Trustpilot Reviews/Trustpilot Reviews/` con código v1.7 desfasado.
- Opción `ctr_update_channel` (definida pero nunca usada).
- 6 ZIPs antiguos sueltos en la raíz del repo (los releases viven en GitHub Releases).

### Changed
- Módulo Divi delega 100% del render en `ctr_render_reviews_carousel()`. Una sola implementación HTML para shortcode + Divi.
- Reseñas clickeables ahora son `<a target="_blank">` reales en vez de `onclick="window.open(...)"`. Mejor accesibilidad (foco con teclado), mejor SEO.
- JS del carrusel extraído a `assets/js/ctr-carousel.js`, registrado con `wp_register_script` y enqueued solo cuando hay carrusel en la página. Sin dependencia de jQuery.
- Petición a Trustpilot con `Accept-Encoding: identity` y User-Agent del propio site (`CustomTrustpilotReviews/2.1; +URL`) en lugar de simular Chrome 91.
- Mensaje de error con `role="alert"` y movido encima del botón "¡Valora en Trustpilot!".
- Defaults de activación migran a `false === get_option(..., false)` (estricto, evita pisar valores `0` válidos).

### Added
- `assets/js/ctr-carousel.js` — vanilla JS, navegación con flechas y teclado.
- `.gitignore` — excluye `*.zip`, `.agent/`, OS files, IDE files, `node_modules/`.
- `.gitattributes` — normaliza finales de línea LF (evita el diff fantasma CRLF↔LF).
- `CHANGELOG.md` (este archivo).
- `README.md` para GitHub.
- `.github/ISSUE_TEMPLATE/bug.yml` para reportes estructurados.

---

## [2.0] — 2026-02-20

### Added
- Sistema de auto-actualización vía GitHub Releases (`includes/updater.php`).

### Fixed
- Extracción de nombres de autor (selectores actualizados).
- Flechas del carrusel.

---

## [1.7]

### Added
- Sistema de estrellas de valoración automático.
- Reseñas clickeables con enlaces a Trustpilot.
- Extracción y visualización de fechas de reseñas.
- Layout Masonry (tipo Pinterest).
- Layout Timeline (línea de tiempo).
- 5 estilos de tarjetas (modern, classic, minimal, elegant, bold).
- 6 esquemas de colores (default, blue, green, purple, orange, dark).
- Panel de configuración organizado en pestañas.

### Changed
- Diseño responsive mejorado.
- Mejoras de accesibilidad y configuración avanzada de visualización.

---

## [1.6]

### Added
- Sistema de caché configurable (transients de WP).
- Layouts Grid, Lista y Carrusel.
- Soporte para múltiples columnas en grid.
- Botones de navegación para carrusel.
- Soporte para modo oscuro.

### Changed
- Mejor manejo de errores y logging.
- Rate limiting (1 req/min) para no martillar Trustpilot.
- Parsing HTML más robusto.
- Interfaz de administración mejorada.

---

## [1.5]

### Added
- Versión inicial del plugin.
