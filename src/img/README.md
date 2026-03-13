
# Module image files

This directory holds the image assets for the MokoCRM module.

## Current images

- **`favicon_256.png`** — Editor/publisher logo (256×256 px). Referenced by `$editor_squarred_logo = 'favicon_256.png@mokocrm'` in `modMokoCRM.class.php`; displayed on the module card in the Dolibarr admin panel.
- **`favicon.ico`** / **`favicon.gif`** / **`favicon.svg`** — Favicon assets for the MokoCRM brand.
- **`logo.png`** / **`logo.svg`** — MokoCRM wordmark/logo for use in documentation and UI.
- **`access_restricted_banner.png`** — Login page background image. Set as `MAIN_LOGIN_BACKGROUND` on module activation and removed on deactivation.
- **`object_mokostandards.png`** — Module icon (16×16 or 32×32 px). Referenced by the `$picto` property in `modMokoCRM.class.php` as `mokostandards@mokocrm`.

## Adding new images

- Object icons follow the naming convention `object_<name>.png` (16×16 or 32×32 px) and are referenced in the corresponding class as `$picto = '<name>@mokocrm'`.
- The editor logo (`$editor_squarred_logo`) should be a square PNG referenced as `'<filename>@mokocrm'`.
