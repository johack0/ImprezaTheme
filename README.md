# ImprezaTheme

Repository del tema **Impreza** con child theme, plugin correlati e MU plugin personalizzati.

## Versioni

> Aggiornare questa tabella ad ogni cambio di versione di tema, core o plugin.

### Tema e core

| Componente | Versione |
|---|---|
| Impreza (tema) | **9.0.1** |
| Impreza Child | **1.0** |
| UpSolution Core (`us-core`) | **9.0.1** |

### Plugin correlati

| Plugin | Versione |
|---|---|
| WPBakery Page Builder (`js_composer`) | **8.7.3** |
| Advanced Custom Fields PRO (`advanced-custom-fields-pro`) | **6.8.2** |

### MU plugin personalizzati

| MU plugin | Versione |
|---|---|
| Impreza - MU Plugin Manager (`000-impreza-mu-plugin-manager.php`) | **1.0.0** |
| Impreza - Librerie JS (`impreza-js-libraries.php`) | **1.2.1** |
| Impreza - Admin Menu Width (`managed/Impreza__admin-menu-width.php`) | **1.2.1** |
| Impreza - Display Logic Device Conditions (`managed/Impreza__display-logic-device-conditions.php`) | **1.6.1** |
| Altri MU plugin gestiti in `mu-plugins/managed/` | 31 file (vedi pannello *Impostazioni → MU Plugin Impreza*) |

## Struttura

- `Impreza/` — tema base (UpSolution).
- `Impreza-child/` — child theme con personalizzazioni (CSS, JS, animazioni, font).
- `us-core/` — plugin companion del tema (UpSolution Core).
- `js_composer/` — WPBakery Page Builder.
- `advanced-custom-fields-pro/` — ACF PRO.
- `mu-plugins/` — Must-Use plugin personalizzati:
  - `000-impreza-mu-plugin-manager.php` — manager che attiva/disattiva i MU plugin gestiti.
  - `impreza-js-libraries.php` — carica GSAP/Lenis/MouseFollower per il child theme; pannello *Impostazioni → Librerie JS Impreza* (separato dal manager).
  - `managed/` — estensioni del page builder Impreza, abilitabili dal pannello admin.
