// --- Importazioni ---
// GSAP, Lenis e MouseFollower sono gestiti dal MU plugin "Impreza - Librerie JS".
// Lo stato degli interruttori è esposto in window.ImprezaJSLibs.
// - GSAP e Lenis: accodati come script classici (global window.gsap / window.Lenis).
// - MouseFollower: importato dinamicamente qui sotto solo se il flag è attivo.
import mainAnimation from "./animation-utils/main-animation/index.js";
// import animationPreload from "./path/to/animationPreload.js";

const libs = window.ImprezaJSLibs || {
  gsap: true,
  lenis: true,
  mousefollower: true,
};

document.addEventListener("DOMContentLoaded", async function (event) {
  console.log("DOM loaded");

  // --- Inizializzazione Lenis (Smooth Scroll) ---
  if (libs.lenis && typeof window.Lenis !== "undefined") {
    const lenis = new Lenis({
      duration: 2,
      velocity: 0.5,
    });

    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);
  }

  // --- Impostazioni e Plugin GSAP ---
  if (libs.gsap && typeof window.gsap !== "undefined") {
    gsap.defaults({ ease: "power3.inOut" });

    // Registra i plugin GSAP effettivamente caricati (in base agli interruttori
    // del MU plugin "Impreza - Librerie JS"). Quelli non attivi non esistono
    // come global e vengono semplicemente saltati.
    const gsapPlugins = [
      window.ScrollTrigger,
      window.ScrollSmoother,
      window.Observer,
      window.TextPlugin,
      window.SplitText,
      window.DrawSVGPlugin,
      window.MotionPathPlugin,
    ].filter(Boolean);

    if (gsapPlugins.length) {
      gsap.registerPlugin(...gsapPlugins);
    }
  }

  // --- INIZIALIZZAZIONE CURSORE (MouseFollower) ---
  // Caricato dinamicamente solo se l'interruttore è attivo (MU plugin "Impreza - Librerie JS").
  // MouseFollower richiede GSAP: lo inizializziamo solo se window.gsap è disponibile,
  // per evitare l'errore "this.gsap is undefined".
  if (libs.mousefollower && typeof window.gsap !== "undefined") {
    const { default: MouseFollower } = await import(
      "./minified/MouseFollower.min.js"
    );

    // Collega esplicitamente GSAP a MouseFollower (più robusto del fallback a window.gsap).
    MouseFollower.registerGSAP(window.gsap);

    // Metodo 2: cursore personalizzato definito in mouse.js
    // (l'import istanzia anche il cursore configurato lì, come in precedenza).
    await import("./animation-utils/mouse/mouse.js");
    // cursor.customCursorInit(); // da scommentare se si vuole usare quel metodo

    // Metodo 1: cursore base MouseFollower
    const follower = new MouseFollower();
  }

  // --- Animazioni Principali ---
  mainAnimation.mainAnimation();

  // --- Event Listener per il Caricamento Completo della Pagina ---
  window.addEventListener("load", function (e) {
    console.log("window loaded");
    // animationPreload.animationPreload();
  });
});
