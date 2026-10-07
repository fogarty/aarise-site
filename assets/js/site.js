/**
 * AARISE — comportements du site : menu mobile, en-tête au défilement, apparition des sections.
 */
(function () {
	'use strict';

	var root = document.documentElement;

	/* En-tête : fond opaque dès que la page défile. */
	var header = document.querySelector('[data-aa-header]');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-scrolled', window.scrollY > 24);
		};
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	/* Menu mobile */
	var toggle = document.querySelector('[data-aa-nav-toggle]');
	if (toggle) {
		var setOpen = function (open) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			document.body.classList.toggle('aa-nav-open', open);
		};
		toggle.addEventListener('click', function () {
			setOpen(toggle.getAttribute('aria-expanded') !== 'true');
		});
		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && document.body.classList.contains('aa-nav-open')) {
				setOpen(false);
				toggle.focus();
			}
		});
		document.getElementById('aa-nav').addEventListener('click', function (event) {
			if (event.target.closest('a')) {
				setOpen(false);
			}
		});
	}

	/* Apparition douce des éléments des sections au défilement. */
	if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	var selectors = [
		'.aa-section > .wp-block-group__inner-container > *',
		'.aa-section:not(:has(> .wp-block-group__inner-container)) > *',
		'.aa-hero .wp-block-cover__inner-container > *',
		'.aa-project-hero__text > *',
		'.aa-projects .wp-block-post',
		'.aa-pillars .wp-block-column',
		'.aa-contacts .wp-block-column'
	];
	var items = [];
	selectors.forEach(function (selector) {
		try {
			document.querySelectorAll(selector).forEach(function (el) {
				if (items.indexOf(el) === -1) {
					items.push(el);
				}
			});
		} catch (e) {
			// :has() non pris en charge : ce sélecteur est ignoré.
		}
	});

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			}
		});
	}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

	items.forEach(function (el) {
		// Décalage en cascade entre éléments voisins.
		var index = Array.prototype.indexOf.call(el.parentNode.children, el);
		el.style.setProperty('--aa-delay', Math.min(index, 4) * 0.09 + 's');
		el.classList.add('aa-reveal');
		observer.observe(el);
	});

	root.classList.add('aa-js');
})();
