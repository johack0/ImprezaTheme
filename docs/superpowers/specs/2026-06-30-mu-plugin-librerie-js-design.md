# MU plugin "Librerie JS Impreza" — Design

Data: 2026-06-30

## Obiettivo

Creare un MU plugin **separato** dal "MU Plugin Manager" che carichi GSAP, Lenis e
MouseFollower per il child theme e fornisca una pagina admin per attivarle/disattivarle
singolarmente.

## Decisioni (confermate con l'utente)

- **Architettura**: il MU plugin diventa l'unico responsabile del caricamento; il child
  theme (`functions.php` + `main.js`) viene adattato di conseguenza.
- **Posizione pagina admin**: sottopagina in `Impostazioni → Librerie JS Impreza`,
  distinta da "MU Plugin Impreza".
- **Granularità**: 3 interruttori, uno per libreria.
- **Default**: tutte attive (preserva il comportamento attuale).

## Vincolo tecnico rilevato

- **Lenis** → build UMD (`(t||self).Lenis=e()`): caricabile come script classico, espone
  `window.Lenis`. → enqueue dal MU plugin.
- **GSAP** → già script classico (`window.gsap`). → enqueue dal MU plugin.
- **MouseFollower** → **modulo ES puro** (`export default class`), nessun global. Non
  enqueue-abile come classico. → resta nel child via **dynamic import** condizionato al
  flag del MU plugin.
- `mouse/mouse.js` istanzia MouseFollower al top-level dell'import; `main.js` lo importa
  staticamente, quindi oggi MouseFollower viene istanziato due volte. Per disattivarlo va
  gated anche l'import di `mouse.js`.
- `main-animation/index.js` è uno stub no-op senza dipendenze: importabile sempre.

## Componenti

### 1. `mu-plugins/impreza-js-libraries.php` (nuovo, alla radice di mu-plugins)
File auto-caricato da WordPress, indipendente dal manager. Responsabilità:
- Opzione `impreza_js_libraries_enabled` (array tra `gsap`, `lenis`, `mousefollower`;
  `false` = mai salvato = tutte attive).
- `wp_enqueue_scripts` (priorità 5): se `gsap` on → enqueue `gsap-js` + i 7 plugin
  (`gsap-js-{key}`); se `lenis` on → enqueue `lenis-js`. MouseFollower: nessun enqueue.
- `wp_head` (priorità 1): stampa `window.ImprezaJSLibs = {gsap,lenis,mousefollower}` come
  script classico inline (eseguito prima del modulo `main.js`, che è `defer`).
- `admin_init`: handler di salvataggio con nonce + redirect (pattern del manager).
- `admin_menu`: `add_options_page` slug `impreza-js-libraries`.
- Pagina: 3 checkbox + salva.

### 2. `Impreza-child/functions.php`
- Rimuovere l'enqueue di GSAP core + loop plugin (ora nel MU plugin).
- `main.js` (`gsap-js-custom`): dipendenza `["gsap-js"]` → `[]` (l'ordine è garantito dal
  `type="module"` deferred; evita che WP forzi GSAP quando disattivato).
- Invariati: filtro `type="module"`, `animation.css`, lazy-loading, stili backend, FOUC.

### 3. `Impreza-child/main.js`
- Rimuovere gli import statici di `lenis`, `MouseFollower`, `mouse.js`.
- `const libs = window.ImprezaJSLibs || {gsap:true,lenis:true,mousefollower:true}`.
- Handler `DOMContentLoaded` reso `async`.
- Lenis: `if (libs.lenis && window.Lenis) { ... }`.
- GSAP: `if (libs.gsap && window.gsap) { gsap.defaults/registerPlugin }`.
- MouseFollower: `if (libs.mousefollower) { dynamic import MouseFollower + mouse.js; new MouseFollower() }`
  (riproduce le due istanze attuali solo quando il flag è on).
- `mainAnimation.mainAnimation()` invariato (no-op).

## Comportamento atteso

| Flag off | Effetto |
|---|---|
| gsap | gsap + plugin non caricati; `main.js` salta init GSAP; MouseFollower senza registerGSAP (come oggi) |
| lenis | `lenis.min.js` non caricato; nessuno smooth scroll |
| mousefollower | nessun fetch di MouseFollower/mouse.js; nessun cursore custom |

Con tutte on il comportamento è identico ad oggi.

## Aggiornamento v1.1.0 — interruttori per singolo plugin GSAP

- Nuova opzione `impreza_js_libraries_gsap_plugins` (sottoinsieme di
  `scroll, smooth, observer, text, split, draw, motion`; `false` = tutti attivi).
- L'enqueue di GSAP accoda solo i plugin attivi; i checkbox dei plugin sono annidati
  sotto GSAP core e disabilitati via JS quando il core è spento.
- `main.js`: la `gsap.registerPlugin(...)` è ora dinamica — registra i plugin
  effettivamente presenti come global (`window.ScrollTrigger`, ecc.), saltando i non
  caricati. Conseguenza: con tutti i plugin attivi vengono registrati tutti e 7 (prima
  il codice ne registrava solo 3 pur caricandoli tutti). Global verificati uno per uno.

## Note / fuori scope

- La doppia istanza di MouseFollower (mouse.js import + main.js:41) è preservata tal
  quale; eventuale pulizia è separata da questa feature.
- Aggiornare la tabella versioni del README con il nuovo MU plugin.
