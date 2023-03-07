// Support for swiper v8
(function() {
  function BreakdanceSwiper() {
    const { mergeObjects } = BreakdanceFrontend.utils;

    // force-next-tick.ts
    // an element may have been hidden. Wait for it to be in the DOM before trying to access it
    function forceNextTick(callback) {
      requestAnimationFrame(() => {
        requestAnimationFrame(callback);
      });
    }

    function isElementInDom(selector) {
      // An element can be "hidden" (aka not in the DOM) with Hide on breakpoint -> in builder preview
      // and Swiper needs the element to be part of the DOM to work on it
      const isElementInDom = document.querySelector(selector);

      return !!isElementInDom;
    }

    // This prevents memory leak from event listeners
    function destroy(id) {
      if (
        window.swiperInstances &&
        window.swiperInstances[id] &&
        // if the element is not in the DOM, "el" will be the selector instead of the element reference
        typeof window.swiperInstances[id].el === "object"
      ) {
        forceNextTick(() => {
          window.swiperInstances[id].destroy(true, true);
          delete window.swiperInstances[id];
        });
      }
    }

    function update({ id, selector, settings, paginationSettings, extras }) {
      if (!isElementInDom(selector)) return;

      destroy(id);

      const defaultOptions = {
        settings: {
          effect: "slide",
          coverflow: {
            rotate: {
              number: 50
            },
            depth: {
              number: 100
            },
            stretch: {
              number: 0
            }
          },
          speed: { number: 1000 },
          autoplay_settings: {
            speed: {
              number: 3000
            }
          },
          advanced: {
            between_slides: { number: 0 }
          }
        },
        pagination: {
          type: "bullets"
        }
      };

      settings = mergeObjects(defaultOptions.settings, settings);
      paginationSettings = mergeObjects(
        defaultOptions.pagination,
        paginationSettings
      );

      const advancedSettings = settings.advanced;
      const isBuilder = !!window.parent.Breakdance;

      const isCoverflowEffect = settings.effect === "coverflow";
      const coverFlowEffect = isCoverflowEffect
        ? {
            coverflowEffect: {
              rotate: settings.coverflow.rotate.number,
              slideShadows: !!settings.coverflow.shadow,
              depth: settings.coverflow.depth.number,
              stretch: settings.coverflow.stretch.number
            }
          }
        : {};

      const fadeEffect =
        settings.effect === "fade"
          ? {
              fadeEffect: {
                crossFade: true
              }
            }
          : {};

      const builderOnlySettings = isBuilder
        ? {
            // this prevents bugs caused by Swiper swallowing events
            preventClicksPropagation: false,
            preventClicks: false,
            simulateTouch: false,
            // doesn't play nice with our drag events
            allowTouchMove: false
          }
        : {};

      const isFadeOrFlipEffect =
        settings.effect === "fade" || settings.effect === "flip";

      // default to 0 so that all changes apply if not set
      let forceSlidesPerViewToOneInBreakpoint = 0;

      if (advancedSettings.one_per_view_at) {
        const findBreakpointThatMatchesSelection = breakpoint =>
          breakpoint.id === advancedSettings.one_per_view_at;

        const maybeStoreBreakpoints =
          window.parent.Breakdance &&
          window.parent.Breakdance.stores.configStore.breakpoints;
        const maybeBreakdanceFrontentBreakpoints =
          window.BreakdanceFrontend &&
          window.BreakdanceFrontend.data.breakpoints;

        if (maybeStoreBreakpoints) {
          const matchingBreakpoint = maybeStoreBreakpoints.find(
            findBreakpointThatMatchesSelection
          );

          if (matchingBreakpoint) {
            forceSlidesPerViewToOneInBreakpoint = matchingBreakpoint.maxWidth;
          }
        }
        // The if order is important here, as BreakdanceFrontend also exists in the builder.
        else if (maybeBreakdanceFrontentBreakpoints) {
          const matchingBreakpoint = maybeBreakdanceFrontentBreakpoints.find(
            findBreakpointThatMatchesSelection
          );

          if (matchingBreakpoint) {
            forceSlidesPerViewToOneInBreakpoint = matchingBreakpoint.maxWidth;
          }
        }
      }

      const forceAutoplay = extras && extras.autoplay === true;

      forceNextTick(() => {
        const swiperInstance = new Swiper(`${selector} .swiper`, {
          ...extras,
          speed: settings.speed.number,
          loop: settings.infinite === "enabled" && !isBuilder,
          autoplay:
            settings.autoplay === "enabled" && (!isBuilder || forceAutoplay)
              ? {
                  delay: settings.autoplay_settings.speed.number,
                  pauseOnMouseEnter: !!settings.autoplay_settings
                    .pause_on_hover,
                  disableOnInteraction: !!settings.autoplay_settings
                    .stop_on_interaction,
                  stopOnLastSlide: settings.infinite !== "enabled"
                }
              : false,

          effect: settings.effect,
          pagination: {
            el: `${selector} .swiper-pagination`,
            type: paginationSettings.type,
            clickable: true
          },
          navigation: {
            nextEl: `${selector} .swiper-button-next`,
            prevEl: `${selector} .swiper-button-prev`
          },
          keyboard: true,
          ...coverFlowEffect,
          ...fadeEffect,

          ...builderOnlySettings,

          // Advanced options
          mousewheel: advancedSettings.swipe_on_scroll
            ? {
                releaseOnEdges: true
              }
            : false,

          autoHeight: !!advancedSettings.auto_height,
          loopPreventsSlide: false,
          spaceBetween: advancedSettings.between_slides.number,
          centeredSlides: isCoverflowEffect
            ? // We decided to make it always true because otherwise it looks ugly
              true
            : settings.center_slides,
          // Swiper docs advise to do this
          watchSlidesProgress: advancedSettings.slides_per_view !== 1,
          // doesn't do anything on its own, but enables elements to create cool effects with HTML
          parallax: true,
          // Swiper's Breakpoints work mobile first, but Breakdance's work desktop first.
          // So the default is 1, and we set the user's slides_per_view above the selected breakpoint
          slidesPerView: 1,
          breakpoints: isFadeOrFlipEffect
            ? false
            : advancedSettings.slides_per_view && {
                [forceSlidesPerViewToOneInBreakpoint]: {
                  slidesPerView: advancedSettings.slides_per_view
                }
              }
        });

        window.swiperInstances = {
          ...window.swiperInstances,
          [id]: swiperInstance
        };
      });
    }

    function updateSliderFromChild(id) {
      const sliderId = document
        // select itself
        .querySelector(`[data-node-id="${id}"]`)
        // get parent (slider) node id
        .parentElement.closest("[data-node-id]").dataset.nodeId;

      const sliderIdNumber = sliderId && parseInt(sliderId);

      if (window.swiperInstances && window.swiperInstances[sliderIdNumber]) {
        window.swiperInstances[sliderIdNumber].update();
      }
    }

    function selectSlide(id) {
      const slideElement = document
        .querySelector(`[data-node-id="${id}"]`)
        .closest(".swiper-slide");

      if (slideElement) {
        const slideIndex = Array.from(
          slideElement.parentElement.children
        ).indexOf(slideElement);

        const sliderElement =
          slideElement.parentElement &&
          slideElement.parentElement.closest("[data-node-id]");

        const sliderId = sliderElement ? sliderElement.dataset.nodeId : null;

        if (
          sliderId &&
          slideIndex !== null &&
          window.swiperInstances &&
          window.swiperInstances[sliderId]
        ) {
          window.swiperInstances[sliderId].slideTo(slideIndex, 0);
        }
      }
    }

    return {
      update,
      destroy,
      updateSliderFromChild,
      selectSlide
    };
  }

  window.BreakdanceSwiper = BreakdanceSwiper;
})();
