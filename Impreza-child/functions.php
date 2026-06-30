<?php
/* Custom functions code goes here. */

/**
 * Enqueue scripts and styles.
 */
function theme_scripts_and_styles()
{
  // === JAVASCRIPT ===

  // Carica la libreria principale GSAP
  wp_enqueue_script(
    "gsap-js", // Handle
    get_stylesheet_directory_uri() . "/minified/gsap.min.js",
    [], // Nessuna dipendenza
    false,
    true // Carica nel footer
  );

  // Carica i plugin di GSAP, dichiarando 'gsap-js' come dipendenza
  $gsap_plugins = [
    "scroll" => "ScrollTrigger.min.js",
    "smooth" => "ScrollSmoother.min.js",
    "observer" => "Observer.min.js",
    "text" => "TextPlugin.min.js",
    "split" => "SplitText.min.js",
    "draw" => "DrawSVGPlugin.min.js",
    "motion" => "MotionPathPlugin.min.js",
  ];

  foreach ($gsap_plugins as $key => $file) {
    wp_enqueue_script(
      "gsap-js-{$key}",
      get_stylesheet_directory_uri() . "/minified/{$file}",
      ["gsap-js"],
      false,
      true
    );
  }

  // Il tuo file JavaScript personalizzato che usa GSAP
  wp_enqueue_script(
    "gsap-js-custom",
    get_stylesheet_directory_uri() . "/main.js",
    ["gsap-js"],
    false,
    true
  );

  // === CSS ===

  // Un array per gestire i CSS più facilmente.
  // NB: i CSS sotto /css/ sono caricati ricorsivamente dal MU plugin
  // "Impreza - Auto Load CSS". Qui resta solo ciò che è fuori da /css/.
  $css_files = [
    "animation" => "/animation-utils/animation.css",
  ];

  foreach ($css_files as $handle => $path) {
    // Verifica che il file esista prima di accodarlo per evitare errori
    if (file_exists(get_stylesheet_directory() . $path)) {
      wp_enqueue_style(
        "{$handle}-css",
        get_stylesheet_directory_uri() . $path,
        [],
        filemtime(get_stylesheet_directory() . $path),
        "all"
      );
    }
  }
}
add_action("wp_enqueue_scripts", "theme_scripts_and_styles");

/**
 * Aggiunge type="module" ai tag <script> specifici.
 */
function set_scripts_type_attribute($tag, $handle, $src)
{
  // Unisci le condizioni con un OR (||) per un codice più pulito
  if ("gsap-js-custom" === $handle || "custom-js-constants" === $handle) {
    $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
  }
  return $tag;
}
add_filter("script_loader_tag", "set_scripts_type_attribute", 10, 3);

/**
 * Disabilita il lazy loading nativo di WordPress per le immagini.
 */
add_filter("wp_lazy_loading_enabled", "__return_false");

/**
 * Inietta stili CSS personalizzati nell'editor del backend.
 */
function custom_backend_styles()
{
  if (is_admin()) {
    echo "<style>.us-bld-editor-row {max-width: 90% !important;}.us-bld-editor-wrapper {min-width: 80%;}</style>";
  }
}
add_action("admin_head", "custom_backend_styles");

/**
 * Inietta CSS e JS inline nell'head per prevenire il FOUC.
 */
function inline_critical_css()
{
  echo '<style id="critical-fouc-fix">body { opacity: 0; transition: opacity 0.3s ease; } body.fade-in { opacity: 1; }</style>';
  echo "<script id='critical-fouc-fix-js'>document.addEventListener('DOMContentLoaded', function() { document.body.classList.add('fade-in'); });</script>";
}
add_action("wp_head", "inline_critical_css", 1);
