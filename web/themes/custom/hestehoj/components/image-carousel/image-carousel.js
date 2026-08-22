import { ComponentType, ComponentInstance } from "../../lib/component.js";

/**
 * Prev/next/dot navigation for the image carousel component.
 *
 * image-carousel.css already shows only the first slide with no
 * JavaScript at all (the baseline experience: one static hero image);
 * this only adds the ability to switch which slide is active.
 */
class ImageCarousel extends ComponentInstance {
  init() {
    this.slides = Array.from(this.el.querySelectorAll(".image-carousel--slide"));
    this.dots = Array.from(this.el.querySelectorAll(".image-carousel--dot"));
    this.prevButton = this.el.querySelector(".image-carousel--prev");
    this.nextButton = this.el.querySelector(".image-carousel--next");
    this.index = 0;

    // A single image needs no navigation controls (image-carousel.twig
    // already omits them in this case, but guard here too).
    if (this.slides.length < 2 || !this.prevButton || !this.nextButton) {
      return;
    }

    this.prevButton.addEventListener("click", () => this.show(this.index - 1));
    this.nextButton.addEventListener("click", () => this.show(this.index + 1));
    this.dots.forEach((dot, index) => {
      dot.addEventListener("click", () => this.show(index));
    });

    this.el.classList.add("image-carousel--ready");
  }

  /**
   * Shows slide `index`, wrapping around at either end.
   */
  show(index) {
    const count = this.slides.length;
    this.index = (index + count) % count;
    this.slides.forEach((slide, i) => slide.classList.toggle("is-active", i === this.index));
    this.dots.forEach((dot, i) => dot.classList.toggle("is-active", i === this.index));
  }
}

new ComponentType(ImageCarousel, "hestehojImageCarousel", ".image-carousel");
