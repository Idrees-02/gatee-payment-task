/**
 * Motion layer for the product page (GSAP + ScrollTrigger).
 *
 * Every animation starts from the finished, visible layout and animates *from* an offset,
 * so if this file or GSAP fails to load, or the visitor prefers reduced motion,
 * the page simply shows its final state.
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion || !window.gsap || !window.ScrollTrigger) {
    return;
  }

  gsap.registerPlugin(ScrollTrigger);

  smoothScrolling();
  introduceHero();
  driftHeroPhoto();
  assembleHeadphones();
  revealSections();
  slideFooterName();

  /**
   * Lenis turns wheel and trackpad input into one steady, eased scroll, and GSAP reads that
   * same position every frame. Even a fast flick reaches the pinned scene at a steady speed,
   * so the animation never jumps ahead and snaps back.
   */
  function smoothScrolling() {
    if (!window.Lenis) return;

    var lenis = new Lenis({ duration: 1.15, easing: function (t) { return 1 - Math.pow(1 - t, 3); }, smoothWheel: true });
    window.__lenis = lenis;

    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
    gsap.ticker.lagSmoothing(0);

    // In-page links (Buy, See what's inside) glide with the same easing.
    document.addEventListener('click', function (event) {
      var link = event.target.closest('a[href^="#"], a[href^="/#"]');
      if (!link) return;
      var target = document.querySelector(link.getAttribute('href').replace(/^\//, ''));
      if (!target) return;
      event.preventDefault();
      lenis.scrollTo(target, { offset: 0, duration: 1.4 });
    });
  }

  /**
   * Signature move: the name rises from behind its baseline,
   * then the halo is drawn dot by dot over the towers, left to right.
   */
  function introduceHero() {
    gsap.timeline({ defaults: { ease: 'power4.out' } })
      .from('.hero__line > span', { yPercent: 105, duration: 0.9 })
      .from('.hero__lede, .hero__action', { y: 20, opacity: 0, duration: 0.7, stagger: 0.08 }, '-=0.45')
      .from('.hero__halo circle', { scale: 0, opacity: 0, duration: 0.35, ease: 'back.out(3)', stagger: 0.018 }, 0.3);
  }

  /** The drawing sinks a little slower than the page scrolls; the halo lifts away from it. */
  function driftHeroPhoto() {
    var scroll = { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true };
    gsap.to('.hero__art', { yPercent: 8, ease: 'none', scrollTrigger: scroll });
    gsap.to('.hero__halo', { yPercent: -6, ease: 'none', scrollTrigger: scroll });
  }

  /**
   * Scroll scene: while the stage is pinned, the band draws itself into a ring,
   * the cups slide in, and the noise line underneath flattens to silence.
   */
  function assembleHeadphones() {
    var stage = document.querySelector('.assembly__stage');
    if (!stage) return;

    var steps = stage.querySelectorAll('.assembly__steps li');
    var noisePath = stage.querySelector('[data-noise]');
    var noise = { amplitude: 1 };

    gsap.set(stage.querySelectorAll('.hp-band, .hp-band-inner'), { strokeDasharray: 1, strokeDashoffset: 1 });

    var isDesktop = window.matchMedia('(min-width: 861px)').matches;

    // Scrub is tied 1:1 to the (already smoothed) scroll position: the drawing is always exactly
    // where the scroll says it should be, at any speed, in both directions.
    var timeline = gsap.timeline({
      defaults: { ease: 'power1.inOut' },
      scrollTrigger: {
        trigger: stage,
        start: isDesktop ? 'top top' : 'top 75%',
        end: isDesktop ? '+=120%' : 'bottom 40%',
        pin: isDesktop,
        anticipatePin: 1,
        scrub: true,
        invalidateOnRefresh: true,
        onUpdate: function (self) { highlightStep(steps, self.progress); },
      },
    });

    timeline
      .to(stage.querySelector('.hp-band'), { strokeDashoffset: 0, duration: 1 })
      .to(stage.querySelector('.hp-band-inner'), { strokeDashoffset: 0, duration: 0.6 }, '-=0.5')
      .fromTo(stage.querySelector('[data-part="cup-left"]'), { x: -90 }, { x: 0, duration: 0.8 }, '-=0.3')
      .fromTo(stage.querySelector('[data-part="cup-right"]'), { x: 90 }, { x: 0, duration: 0.8 }, '<')
      .to(noise, {
        amplitude: 0,
        duration: 1,
        onUpdate: function () { noisePath.setAttribute('d', wavePath(noise.amplitude)); },
      });

    noisePath.setAttribute('d', wavePath(1));
  }

  function highlightStep(steps, progress) {
    var index = Math.min(steps.length - 1, Math.floor(progress * steps.length));
    steps.forEach(function (step, i) { step.classList.toggle('is-active', i === index); });
  }

  /** A jagged "street noise" line whose height scales with amplitude (0 = silence). */
  function wavePath(amplitude) {
    var points = [];
    for (var x = 0; x <= 400; x += 6) {
      var y = 60 + amplitude * 48 * (Math.sin(x * 0.11) * 0.55 + Math.sin(x * 0.37 + 1.3) * 0.3 + Math.sin(x * 0.83) * 0.15);
      points.push((x === 0 ? 'M' : 'L') + x + ' ' + y.toFixed(1));
    }
    return points.join(' ');
  }

  /** Each section's parts enter in order: heading, then text, then pictures. */
  function revealSections() {
    var groups = [
      ['.finishes', '.finishes__plate, .finishes__tabs, .finishes__copy > *, .finishes__actions'],
      ['.specs', '.specs h2, .specs__group, .specs__detail'],
      ['.places', '.places h2, .places__lede, .places figure'],
      ['.checkout', '.checkout__head > *, .order, .pay'],
      ['.closer', '.closer h2 span, .closer__foot > *'],
    ];

    groups.forEach(function (group) {
      var section = document.querySelector(group[0]);
      if (!section) return;
      gsap.from(section.querySelectorAll(group[1]), {
        y: 28,
        opacity: 0,
        duration: 0.8,
        ease: 'power3.out',
        stagger: 0.075,
        scrollTrigger: { trigger: section, start: 'top 78%', once: true },
      });
    });

    gsap.utils.toArray('.places img, .finishes__plate img').forEach(function (img) {
      gsap.fromTo(img, { scale: 1.12 }, {
        scale: 1,
        ease: 'none',
        scrollTrigger: { trigger: img, start: 'top bottom', end: 'bottom top', scrub: true },
      });
    });
  }

  function slideFooterName() {
    gsap.from('.site-foot__name', {
      xPercent: -12,
      ease: 'none',
      scrollTrigger: { trigger: '.site-foot', start: 'top bottom', end: 'bottom bottom', scrub: true },
    });
  }
})();
