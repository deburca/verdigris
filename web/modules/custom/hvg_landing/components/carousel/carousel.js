/**
 * @file
 * Adds previous/next buttons and dots to the carousel component.
 *
 * Without JavaScript the track is still a native, swipeable scroll-snap row.
 */
((Drupal, once) => {
  Drupal.behaviors.hvgCarousel = {
    attach(context) {
      once('hvg-carousel', '.hvg-carousel', context).forEach((root) => {
        const track = root.querySelector('.hvg-carousel__track');
        const slides = Array.from(track.children);
        if (slides.length < 2) {
          return;
        }

        const button = (cls, label, text) => {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = cls;
          b.setAttribute('aria-label', label);
          if (text) {
            b.textContent = text;
          }
          return b;
        };

        slides.forEach((slide, i) => {
          slide.setAttribute('role', 'group');
          slide.setAttribute('aria-roledescription', 'slide');
          slide.setAttribute('aria-label', `${i + 1} of ${slides.length}`);
        });

        const controls = document.createElement('div');
        controls.className = 'hvg-carousel__controls';
        const prev = button('hvg-carousel__button', 'Previous slide', '‹');
        const next = button('hvg-carousel__button', 'Next slide', '›');
        const dots = document.createElement('div');
        dots.className = 'hvg-carousel__dots';
        const dotButtons = slides.map((_, i) => {
          const d = button('hvg-carousel__dot', `Go to slide ${i + 1}`);
          d.addEventListener('click', () => go(i));
          dots.append(d);
          return d;
        });
        controls.append(prev, dots, next);
        root.append(controls);

        // Distance from the track's left edge to a slide's centre, in track
        // scroll coordinates.
        const centre = (slide) => slide.getBoundingClientRect().left
          - track.getBoundingClientRect().left
          + track.scrollLeft
          + slide.offsetWidth / 2;

        const current = () => {
          const mid = track.scrollLeft + track.clientWidth / 2;
          let best = 0;
          slides.forEach((s, i) => {
            if (Math.abs(centre(s) - mid) < Math.abs(centre(slides[best]) - mid)) {
              best = i;
            }
          });
          return best;
        };

        const go = (i) => {
          const s = slides[Math.max(0, Math.min(slides.length - 1, i))];
          track.scrollTo({ left: centre(s) - track.clientWidth / 2 });
        };

        const update = () => {
          const i = current();
          dotButtons.forEach((d, n) => {
            d.setAttribute('aria-current', n === i ? 'true' : 'false');
          });
          prev.disabled = i === 0;
          next.disabled = i === slides.length - 1;
        };

        prev.addEventListener('click', () => go(current() - 1));
        next.addEventListener('click', () => go(current() + 1));
        track.addEventListener('keydown', (e) => {
          if (e.key === 'ArrowLeft') {
            e.preventDefault();
            go(current() - 1);
          }
          else if (e.key === 'ArrowRight') {
            e.preventDefault();
            go(current() + 1);
          }
        });
        let frame = 0;
        track.addEventListener('scroll', () => {
          cancelAnimationFrame(frame);
          frame = requestAnimationFrame(update);
        }, { passive: true });
        window.addEventListener('resize', update);
        update();
      });
    },
  };
})(Drupal, once);
