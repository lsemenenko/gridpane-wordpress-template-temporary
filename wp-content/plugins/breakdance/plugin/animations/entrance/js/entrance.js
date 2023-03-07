/* global gsap, ScrollTrigger, imagesLoaded */
(function () {
  gsap.registerPlugin(ScrollTrigger);

  const {
    mergeObjects,
    onResize,
    matchMedia,
    prefersReducedMotion
  } = BreakdanceFrontend.utils;

  class BreakdanceEntrance {
    enabledClass = 'breakdance-animation-enabled';
    beforeClass = 'is-before';
    animatingClass = 'is-animating';
    completedClass = 'is-animated';

    distance = 100;

    animations = {
      fade: [
        { autoAlpha: 0 },
        { autoAlpha: 1 }
      ],

      slideUp: [
        { y: this.distance },
        { y: 0 }
      ],
      slideDown: [
        { y: -this.distance },
        { y: 0 }
      ],
      slideLeft: [
        { x: -this.distance },
        { x: 0 }
      ],
      slideRight: [
        { x: this.distance },
        { x: 0 }
      ],

      flipUp: [
        { perspective: 2500, rotateX: -this.distance },
        { rotateX: 0 }
      ],

      flipDown: [
        { perspective: 2500, rotateX: this.distance },
        { rotateX: 0 }
      ],
      flipLeft: [
        { perspective: 2500, rotateY: -this.distance },
        { rotateY: 0 }
      ],
      flipRight: [
        { perspective: 2500, rotateY: this.distance },
        { rotateY: 0 }
      ],

      zoomIn: [
        { scale: 0.6 },
        { scale: 1 }
      ],
      zoomOut: [
        { scale: 1.2 },
        { scale: 1 }
      ],
    };

    defaultOptions = {
      animation_type: null,
      duration: { number: 500, unit: 'ms', style: '500ms' },
      delay: { number: 0, unit: 'ms', style: '0ms' },
      advanced: {
        offset: { number: 0, unit: 'px', style: '0px' },
        ease: 'power1.out',
        anchorPlacement: 'top bottom',
        once: false,
        disable_at: null
      }
    };

    initialized = false;

    constructor(selector, options) {
      this.selector = selector;
      this.options = mergeObjects(this.defaultOptions, options);
      this.rootEl = document.documentElement;

      this.init();
    }

    canAnimate() {
      const breakpoint = this.options.advanced.disable_at;
      if (!breakpoint) return true;
      return !matchMedia(breakpoint);
    }

    getDuration(value) {
      if (!value) return value;
      if (value.unit === 's') return value.number;
      return value.number / 1000; // Convert MS to S
    }

    bindImageListeners() {
      this.imagesLoaded = imagesLoaded(document.body);
      this.imagesLoaded.on('progress', () => ScrollTrigger.refresh());
    }

    unbindImageListeners() {
      if (!this.imagesLoaded) return;
      this.imagesLoaded.off('progress');
      this.imagesLoaded = null;
    }

    getOffset(offset, anchor) {
      const defaultOffset = 120;

      // Top-bottom placement looks terrible if the element is animated with offset zero.
      if (anchor === 'top bottom' && offset.number === 0) {
        return defaultOffset;
      }

      return offset.number;
    }

    createTween() {
      const type = this.options.animation_type;

      if (!this.animations[type]) {
        console.log(`[ENTRANCE] The selected ${type} animation is invalid.`);
        return;
      }

      const [from, to] = this.animations[type];

      const { ease, once, anchorPlacement } = this.options.advanced;
      const duration = this.getDuration(this.options.duration);
      const delay = this.getDuration(this.options.delay);
      const offset = this.getOffset(this.options.advanced.offset, anchorPlacement);
      const [elementStart, viewportStart] = anchorPlacement.split(' ');

      const anim = gsap.timeline({
        delay,
        paused: true
      });

      this.element.classList.add(this.beforeClass);

      anim.fromTo(this.element, {
        ...from,
        autoAlpha: 0,
      },
      {
        ...to,
        duration,
        autoAlpha: 1,
        delay,
        clearProps: 'all',
        ease,
        onStart: () => {
          this.element.classList.add(this.animatingClass);
          this.element.classList.remove(this.beforeClass);
        },
        onComplete: () => {
          this.element.classList.remove(this.animatingClass);
          this.element.classList.add(this.completedClass);
        },
        onReverseComplete: () => {
          this.element.classList.add(this.beforeClass);
          this.element.classList.remove(this.animatingClass);
        },
      });

      this.startTrigger = ScrollTrigger.create({
        trigger: this.element,
        start: `${elementStart}+=${offset} ${viewportStart}`,
        toggleActions: "play none none none",
        once,
        onEnter: () => anim.play()
      });

      if (!once) {
        this.resetAnimation(this.element, anim);
      }

      return anim;
    }

    resetAnimation(element, animation) {
      // Reset animation to the beginning once its goes offscreen at the bottom.
      this.endTrigger = ScrollTrigger.create({
        trigger: element,
        start: `top-=10 bottom`,
        onLeaveBack: () => {
          animation.pause(0);
          element.classList.add(this.beforeClass);
          element.classList.remove(this.completedClass);
          element.classList.remove(this.animatingClass);
        }
      });
    }

    update(options) {
      this.options = mergeObjects(this.defaultOptions, options);
      this.destroy();
      this.init();
    }

    destroy() {
      this.initialized = false;

      if (!this.element) return;

      this.element.classList.add(this.completedClass);

      if (!this.tween) return;

      this.tween.kill();
      this.tween = null;
      this.startTrigger?.kill();
      this.endTrigger?.kill();

      // Remove all inline styles
      gsap.set(this.element, { clearProps: 'all' });

      this.unbindImageListeners();
    }

    refresh() {
      ScrollTrigger.refresh();
    }

    initTween() {
      if (this.initialized) return;
      this.initialized = true;

      this.element.classList.remove(this.completedClass);
      this.tween = this.createTween();
    }

    initOrDestroy() {
      if (this.canAnimate()) {
        this.initTween();
      } else {
        this.destroy();
      }
    }

    init() {
      if (!this.options.animation_type) return;

      this.element = document.querySelector(this.selector);

      if (prefersReducedMotion()) {
        this.element.classList.add(this.completedClass);
        return;
      }

      onResize(() => this.initOrDestroy());
      this.bindImageListeners();

      this.rootEl.classList.add(this.enabledClass);
    }
  }

  window.BreakdanceEntrance = BreakdanceEntrance;
}());
