// Lazy Load Images
(function lazyLoadImages() {
  "use strict";

  const loadImage = (img) => {
    const src = img.dataset.src;
    const picture = img.closest("picture");

    if (src) {
      img.src = src;
      img.removeAttribute("data-src");
    }

    if (picture) {
      picture.querySelectorAll("source").forEach((source) => {
        const srcset = source.dataset.srcset;
        if (srcset) {
          source.srcset = srcset;
          source.removeAttribute("data-srcset");
        }
      });
    }

    img.classList.remove("lazy-image");
  };

  // Without IntersectionObserver there is no way to wait for the viewport: load every image right away
  if (!("IntersectionObserver" in window)) {
    document.querySelectorAll(".lazy-image").forEach(loadImage);
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        loadImage(entry.target);
        observer.unobserve(entry.target);
      });
    },
    { rootMargin: "100px" },
  );

  const observeImages = (images) => {
    images.forEach((img) => {
      if (img.classList.contains("lazy-image")) observer.observe(img);
    });
  };

  observeImages(document.querySelectorAll(".lazy-image"));

  // Images added later (e.g. Splide clones) are lazy loaded too, for as long as the page is open
  new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        if (node.classList.contains("lazy-image")) observeImages([node]);
        observeImages(node.querySelectorAll(".lazy-image"));
      });
    });
  }).observe(document.body, { childList: true, subtree: true });
})();
