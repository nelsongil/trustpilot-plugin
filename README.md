# Custom Trustpilot Reviews

Plugin de WordPress que muestra las valoraciones de [Trustpilot](https://www.trustpilot.com) en tu web, con módulo nativo para **Divi 4** y shortcode universal.

> **Estado**: ✅ v2.1 — funciona con la versión actual de Trustpilot (Next.js).
> **Web en producción**: [segurizate.info](https://www.segurizate.info/)
> **Repo**: <https://github.com/nelsongil/trustpilot-plugin>

---

## Características

- 5 layouts: **grid**, **lista**, **carrusel**, **masonry**, **timeline**.
- 5 estilos de tarjeta y 6 esquemas de color.
- Estrellas, fechas, autor y enlace clickable a Trustpilot.
- Sistema de **caché** con TTL configurable (default 1h).
- **Rate limiting** propio (1 req/min) para no abusar de Trustpilot.
- **Auto-actualización** vía GitHub Releases (sin necesidad de subirlo a wordpress.org).
- Módulo nativo de **Divi** (drag & drop) + shortcode `[custom_trustpilot_reviews]`.
- Accesible: `<a>` reales para reseñas clickables, `aria-label` en estrellas, navegación por teclado en el carrusel.

## Instalación

1. Descarga el último ZIP desde [Releases](https://github.com/nelsongil/trustpilot-plugin/releases).
2. WP-Admin → Plugins → Añadir nuevo → Subir plugin → selecciona el ZIP.
3. Activa el plugin.
4. Ve a **Trustpilot Reviews** en el menú lateral y rellena la URL de tu perfil:
   `https://es.trustpilot.com/review/tu-dominio.com`
5. Inserta el shortcode en cualquier página/post o usa el módulo Divi en el Visual Builder.

## Uso del shortcode

```
[custom_trustpilot_reviews]
[custom_trustpilot_reviews layout="masonry" count="6" columns="3" show_stars="true"]
```

| Parámetro     | Valores                                            | Default  |
|---------------|----------------------------------------------------|----------|
| `layout`      | `grid`, `list`, `carousel`, `masonry`, `timeline`  | `grid`   |
| `count`       | 1-50                                               | 5        |
| `columns`     | 1-4 (solo grid/masonry)                            | 1        |
| `show_stars`  | `true`/`false`                                     | `true`   |
| `show_dates`  | `true`/`false`                                     | `true`   |
| `clickable`   | `true`/`false`                                     | `true`   |
| `style`       | `modern`, `classic`, `minimal`, `elegant`, `bold`  | `modern` |
| `colors`      | `default`, `blue`, `green`, `purple`, `orange`, `dark` | `default` |

## Cómo funciona internamente

El plugin lee la página pública de tu perfil Trustpilot y extrae el JSON
embebido en `<script id="__NEXT_DATA__">`. Trustpilot es una SPA Next.js
detrás de Cloudflare; las clases CSS llevan sufijos hash que cambian
cada deploy, así que parsear el DOM por clases es frágil. El JSON es la
fuente estable.

Si Trustpilot reorganiza el JSON, hay un walker recursivo que busca la
primera lista que "huela a review" (objetos con keys `rating`, `text`,
`title`, `consumer`...). Si tampoco eso funciona, devuelve un error
explícito en vez de inventar reseñas.

## Troubleshooting

- **"No se encontró `__NEXT_DATA__`"** → Trustpilot ha cambiado su HTML otra vez. Abre una [issue](https://github.com/nelsongil/trustpilot-plugin/issues) con la URL afectada.
- **"Error HTTP 403"** → Cloudflare está retando al bot. Suele resolverse solo en minutos. Si persiste, abre issue.
- **"Demasiadas solicitudes"** → es el rate limit interno (1 req/min). Espera 1 minuto.
- **Reviews no se actualizan** → la caché por defecto es 1h. Settings → Avanzado → Limpiar caché.

## Desarrollo

Estructura:

```
custom-trustpilot-reviews/
├── custom-trustpilot-reviews.php  # bootstrap, hooks de activación
├── includes/
│   ├── api.php             # fetch + parsing de Trustpilot
│   ├── shortcode.php       # render HTML (lo usa shortcode + Divi)
│   ├── divi-module.php     # módulo Divi (delega en shortcode)
│   ├── admin-options.php   # panel de ajustes
│   ├── updater.php         # auto-update vía GitHub Releases
│   └── uninstall.php
├── assets/
│   ├── css/styles.css
│   ├── js/ctr-carousel.js  # vanilla JS, sin jQuery
│   └── img/
├── readme.txt              # consumido por WordPress
├── CHANGELOG.md            # source of truth de versiones
└── README.md
```

## Release

Ver workflow detallado en `.agent/workflows/github-release.md`. Resumen:

1. Bumpear versión en `custom-trustpilot-reviews.php` (header + constante) y `readme.txt`.
2. Añadir entrada en `CHANGELOG.md`.
3. Commit + push a `main`.
4. `gh release create vX.Y --title "Versión X.Y" zip-empaquetado.zip`.
5. El updater de los WordPress que tienen el plugin instalado detectará la actualización en su siguiente check (12h, o forzando desde Settings → Avanzado).

## Licencia

GPLv2 o posterior.

## Autor

[Nelson Ariel Gil Olguín](https://github.com/nelsongil)
