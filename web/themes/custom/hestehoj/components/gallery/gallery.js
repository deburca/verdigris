import { ComponentType, ComponentInstance } from "../../lib/component.js";

/**
 * Lightbox behavior for the gallery component.
 *
 * Upgrades the tile links (plain anchors to the original files, the
 * no-JS fallback) to open the component's <dialog> instead, with
 * prev/next navigation, wrap-around, and arrow-key support. Escape and
 * focus containment come free with dialog.showModal().
 */
class Gallery extends ComponentInstance {
  init() {
    this.dialog = this.el.querySelector(".gallery--dialog");
    this.image = this.el.querySelector(".gallery--dialog-image");
    this.caption = this.el.querySelector(".gallery--dialog-caption");
    this.closeButton = this.el.querySelector(".gallery--close");
    this.prevButton = this.el.querySelector(".gallery--prev");
    this.nextButton = this.el.querySelector(".gallery--next");
    this.items = Array.from(this.el.querySelectorAll(".gallery--item"));
    this.index = 0;

    if (!this.dialog || typeof this.dialog.showModal !== "function" || !this.items.length) {
      return;
    }

    this.items.forEach((item, index) => {
      const link = item.querySelector("a");
      if (!link) {
        return;
      }
      link.addEventListener("click", (event) => {
        event.preventDefault();
        this.open(index);
      });
    });

    // A single image needs no navigation controls.
    if (this.items.length < 2) {
      this.prevButton.hidden = true;
      this.nextButton.hidden = true;
    }

    this.closeButton.addEventListener("click", () => this.dialog.close());
    this.prevButton.addEventListener("click", () => this.show(this.index - 1));
    this.nextButton.addEventListener("click", () => this.show(this.index + 1));

    this.dialog.addEventListener("keydown", (event) => {
      if (event.key === "ArrowLeft") {
        this.show(this.index - 1);
      } else if (event.key === "ArrowRight") {
        this.show(this.index + 1);
      }
    });

    // Clicking the backdrop (outside the dialog's content) closes it.
    this.dialog.addEventListener("click", (event) => {
      if (event.target === this.dialog) {
        this.dialog.close();
      }
    });

    // Marker class: styling hooks and a JS-ran signal for verification.
    this.el.classList.add("gallery--ready");
  }

  /**
   * Shows item `index` (wrapping around) and opens the dialog.
   */
  open(index) {
    this.show(index);
    this.dialog.showModal();
    this.closeButton.focus();
  }

  /**
   * Points the dialog's image at item `index`, wrapping around.
   */
  show(index) {
    const count = this.items.length;
    this.index = (index + count) % count;
    const item = this.items[this.index];
    this.image.src = item.dataset.fullSrc;
    this.image.alt = item.dataset.alt || "";
    this.caption.textContent = item.dataset.alt || "";
  }
}

new ComponentType(Gallery, "hestehojGallery", ".gallery");
