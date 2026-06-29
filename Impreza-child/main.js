// --- Importazioni ---
import cursor from "./animation-utils/mouse/mouse.js"; // Lo lasciamo importato ma non lo useremo se scegli MouseFollower
import MouseFollower from "./minified/MouseFollower.min.js";
import mainAnimation from "./animation-utils/main-animation/index.js";
// Assicurati di importare anche questo file se lo usi!
// import animationPreload from "./path/to/animationPreload.js";

import "./minified/lenis.min.js";

document.addEventListener("DOMContentLoaded", function (event) {
  console.log("DOM loaded");

  // --- Inizializzazione Lenis (Smooth Scroll) ---
  const isMobile = window.innerWidth < 1024;
  const lenis = new Lenis({
    duration: 2,
    velocity: 0.5,
  });

  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }
  requestAnimationFrame(raf);

  // --- Impostazioni e Plugin GSAP ---
  gsap.defaults({ ease: "power3.inOut" });

  // Registra tutti i plugin in una sola volta
  gsap.registerPlugin(
    ScrollTrigger,
    Observer,
    TextPlugin
    // ScrollSmoother // Scommenta se lo usi
  );

  // --- INIZIALIZZAZIONE CURSORE ---
  // SCEGLI UNO DEI DUE METODI, NON USARLI ENTRAMBI!

  // Metodo 1: MouseFollower.js (consigliato se vuoi usare quella libreria)
  const follower = new MouseFollower();

  // Metodo 2: Il tuo cursore personalizzato (da tenere commentato se usi MouseFollower)
  // cursor.customCursorInit();

  // --- Animazioni Principali ---
  mainAnimation.mainAnimation();

  // --- Event Listener per il Caricamento Completo della Pagina ---
  window.addEventListener("load", function (e) {
    console.log("window loaded");

    // Assicurati che 'animationPreload' sia definito e importato correttamente
    // altrimenti questa riga causerà un errore.
    // animationPreload.animationPreload();
  });
});
