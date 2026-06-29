//import constants from "../constants.js";
import MouseFollower from "../../minified/MouseFollower.min.js";

const cursor = new MouseFollower({
  el: null,
  speed: 0.55,
  className: "mf-cursor",
  innerClassName: "mf-cursor-inner",
  textClassName: "mf-cursor-text",
  mediaClassName: "mf-cursor-media",
  mediaBoxClassName: "mf-cursor-media-box",
  iconSvgClassName: "mf-svgsprite",
  iconSvgNamePrefix: "-",
  iconSvgSrc: "/wp-content/uploads/2023/10/freccia-drog.png",
  dataAttr: "cursor",
  stateDetection: {
    "-us-btn-style_1": "a.us-btn-style_1",
    "-pointer": "a, .hamburger",
    "-cta": "#play-video-btn",
    "-opaque": ".my-image",
    // "-add-class": ".split",

    // "-hidden": ".carousel-pensato",
    // "-icon": ".owl-next, .owl-prev",
  },
});

const customCursorInit = () => {
  const el = document.querySelector(".kr_carousel_home");
  const ell = document.querySelector(".kr_opinioni");
  const elll = document.querySelectorAll("video");
  if (el) {
    el.addEventListener("mouseenter", () => {
      cursor.setImg("/wp-content/uploads/2023/10/freccia-drog.png");
    });

    el.addEventListener("mouseleave", () => {
      cursor.removeImg();
    });
  }
  if (ell) {
    ell.addEventListener("mouseenter", () => {
      cursor.setImg("/wp-content/uploads/2023/10/freccia-drog.png");
    });

    ell.addEventListener("mouseleave", () => {
      cursor.removeImg();
    });
  }
  if (elll) {
    elll.forEach((video) => {
      video.addEventListener("mouseenter", () => {
        cursor.setText("Play!");
      });

      video.addEventListener("mouseleave", () => {
        cursor.removeText();
      });
    });
  }
};

// Link

export default {
  customCursorInit,
};
