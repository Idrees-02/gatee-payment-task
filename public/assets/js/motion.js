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

  introduceHero();
  driftHeroPhoto();
  assembleHeadphones();
  revealSections();
  slideFooterName();

  /** Signature move: each line of the name rises from behind its own baseline. */
  function introduceHero() {
    gsap.timeline({ defaults: { ease: 'power4.out' } })
      .from('.hero__line > span', { yPercent: 105, duration: 0.9, stagger: 0.11 })
      .from('.hero__lede > *', { y: 24, opacity: 0, duration: 0.7, stagger: 0.08 }, '-=0.35');
  }

  function driftHeroPhoto() {
    gsap.fromTo('.hero__photo', { scale: 1.08 }, { scale: 1, duration: 1.6, ease: 'power2.out' });
    gsap.to('.hero__photo', {
      yPercent: 12,
      ease: 'none',
      scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true },
    });
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

    var timeline = gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: {
        trigger: stage,
        start: window.matchMedia('(min-width: 861px)').matches ? 'top top' : 'top 70%',
        end: '+=110%',
        pin: window.matchMedia('(min-width: 861px)').matches,
        scrub: 0.6,
        onUpdate: function (self) { highlightStep(steps, self.progress); },
      },
    });

    timeline
      .to(stage.querySelector('.hp-band'), { strokeDashoffset: 0, duration: 1 })
      .to(stage.querySelector('.hp-band-inner'), { strokeDashoffset: 0, duration: 0.6 }, '-=0.4')
      .from(stage.querySelector('[data-part="cup-left"]'), { x: -160, opacity: 0, duration: 0.8 }, '>-0.1')
      .from(stage.querySelector('[data-part="cup-right"]'), { x: 160, opacity: 0, duration: 0.8 }, '<')
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
