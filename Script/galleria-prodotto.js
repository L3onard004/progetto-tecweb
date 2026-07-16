document.addEventListener("DOMContentLoaded", () => {
  const mainImg = document.getElementById("mainProductImage");
  if (!mainImg) return;

  document.querySelectorAll(".product-thumb img").forEach((thumbImg) => {
    thumbImg.addEventListener("click", () => {
      const tmpSrc = mainImg.src;
      const tmpAlt = mainImg.alt;

      mainImg.src = thumbImg.src;
      mainImg.alt = thumbImg.alt;

      thumbImg.src = tmpSrc;
      thumbImg.alt = tmpAlt;
    });
  });
});
