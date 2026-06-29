const animationPreload = () => {
  const preloader = document.querySelector(".ht--preloader");
  if (preloader) {
    setTimeout(function () {
      const htPreloader = document.querySelector(".ht--preloader");
      if (htPreloader) {
        htPreloader.classList.add("ht--done");
      }
    }, 500);

    setTimeout(function () {
      const htPreloader = document.querySelector(".ht--preloader");
      if (htPreloader) {
        htPreloader.classList.add("ht--hidden");
      }
    }, 2000);
  }
};

export default {
  animationPreload,
};
