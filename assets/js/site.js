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

	var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Scènes (héros de l'accueil, hauts de page) : parallaxe des plans, poussière dorée. */
	var scenes = document.querySelectorAll('[data-aa-scene]');
	if (reducedMotion) {
		// Arrête aussi les animations SVG (orbites).
		scenes.forEach(function (scene) {
			scene.querySelectorAll('svg').forEach(function (svg) {
				if (svg.pauseAnimations) {
					svg.pauseAnimations();
				}
			});
		});
	} else if (scenes.length) {
		var layers = document.querySelectorAll('[data-aa-scene] [data-depth]');
		var pointer = { x: 0, y: 0 };
		var frame = 0;
		var render = function () {
			frame = 0;
			var scroll = Math.min(window.scrollY, window.innerHeight);
			layers.forEach(function (layer) {
				var depth = parseFloat(layer.getAttribute('data-depth'));
				layer.style.setProperty('--aa-px', (pointer.x * depth * -40).toFixed(1) + 'px');
				layer.style.setProperty('--aa-py', (pointer.y * depth * -24 + scroll * depth * 0.6).toFixed(1) + 'px');
			});
		};
		var queue = function () {
			if (!frame) {
				frame = window.requestAnimationFrame(render);
			}
		};
		if (window.matchMedia('(hover: hover)').matches) {
			window.addEventListener('pointermove', function (event) {
				pointer.x = event.clientX / window.innerWidth - 0.5;
				pointer.y = event.clientY / window.innerHeight - 0.5;
				queue();
			}, { passive: true });
		}
		window.addEventListener('scroll', queue, { passive: true });
		scenes.forEach(startDust);
	}

	/**
	 * Poussière dorée qui s'élève dans une scène ; en pause quand la scène n'est pas à l'écran.
	 */
	function startDust(scene) {
		var canvas = scene.querySelector('[data-aa-dust]');
		var ctx = canvas && canvas.getContext('2d');
		if (!ctx) {
			return;
		}
		var motes = [];
		var visible = true;
		var resize = function () {
			var ratio = Math.min(window.devicePixelRatio || 1, 2);
			canvas.width = canvas.offsetWidth * ratio;
			canvas.height = canvas.offsetHeight * ratio;
			ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
			var count = Math.round(canvas.offsetWidth / 22);
			motes = [];
			for (var i = 0; i < count; i++) {
				motes.push({
					x: Math.random() * canvas.offsetWidth,
					y: Math.random() * canvas.offsetHeight,
					r: Math.random() * 1.4 + 0.3,
					speed: Math.random() * 0.25 + 0.06,
					drift: Math.random() * Math.PI * 2,
					alpha: Math.random() * 0.5 + 0.15
				});
			}
		};
		var tick = function () {
			if (!visible) {
				return;
			}
			var w = canvas.offsetWidth;
			var h = canvas.offsetHeight;
			ctx.clearRect(0, 0, w, h);
			motes.forEach(function (m) {
				m.y -= m.speed;
				m.drift += 0.004;
				m.x += Math.sin(m.drift) * 0.15;
				if (m.y < -4) {
					m.y = h + 4;
					m.x = Math.random() * w;
				}
				// Plus lumineuses en montant, s'éteignent près du haut.
				var fade = Math.min(1, m.y / (h * 0.35));
				ctx.globalAlpha = m.alpha * fade;
				ctx.fillStyle = '#cdab5b';
				ctx.beginPath();
				ctx.arc(m.x, m.y, m.r, 0, Math.PI * 2);
				ctx.fill();
			});
			window.requestAnimationFrame(tick);
		};
		resize();
		window.addEventListener('resize', resize);
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				var wasVisible = visible;
				visible = entries[0].isIntersecting;
				if (visible && !wasVisible) {
					tick();
				}
			}).observe(scene);
		}
		tick();
	}

	/* Apparition douce des éléments des sections au défilement. */
	if (!('IntersectionObserver' in window) || reducedMotion) {
		return;
	}

	var selectors = [
		'.aa-section > .wp-block-group__inner-container > *',
		'.aa-section:not(:has(> .wp-block-group__inner-container)) > *',
		'.aa-hero .wp-block-cover__inner-container > *',
		'.aa-project-hero__text > *',
		'.aa-projects .wp-block-post',
		'.aa-pillars .wp-block-column',
		'.aa-contacts .wp-block-column',
		'.aa-member'
	];
	var items = [];
	selectors.forEach(function (selector) {
		try {
			document.querySelectorAll(selector).forEach(function (el) {
				if (items.indexOf(el) === -1 && !el.classList.contains('aa-scene')) {
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
