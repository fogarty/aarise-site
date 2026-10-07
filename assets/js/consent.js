/**
 * AARISE — interface du bandeau cookies.
 * La logique de consentement (cookie, Consent Mode, GTM) est dans le script en ligne
 * du <head> (inc/consent.php), exposée via window.aaConsent.
 */
(function () {
	'use strict';

	var root = document.getElementById('aa-consent');
	if (!root || !window.aaConsent) {
		return;
	}

	var panel = document.getElementById('aa-consent-panel');
	var boxes = {
		analytics: root.querySelector('input[name="analytics"]'),
		marketing: root.querySelector('input[name="marketing"]')
	};

	// withPanel : ouverture volontaire (« Gérer les cookies ») → panneau des catégories + focus dessus.
	// Au premier affichage automatique, aucun bouton ne reçoit le focus.
	function show(withPanel) {
		var current = window.aaConsent.get();
		boxes.analytics.checked = !!(current && current.analytics);
		boxes.marketing.checked = !!(current && current.marketing);
		root.hidden = false;
		panel.hidden = !withPanel;
		if (withPanel) {
			boxes.analytics.focus({ preventScroll: true });
		}
	}

	function choose(choice) {
		window.aaConsent.save(choice);
		root.hidden = true;
		document.documentElement.classList.remove('aa-needs-consent');
	}

	root.addEventListener('click', function (event) {
		var button = event.target.closest('[data-aa-consent]');
		if (!button) {
			return;
		}
		switch (button.getAttribute('data-aa-consent')) {
			case 'accept':
				choose({ analytics: true, marketing: true });
				break;
			case 'reject':
				choose({ analytics: false, marketing: false });
				break;
			case 'save':
				choose({ analytics: boxes.analytics.checked, marketing: boxes.marketing.checked });
				break;
		}
	});

	// « Gérer les cookies » : bouton du pied de page ou lien vers #manage-cookies.
	document.addEventListener('click', function (event) {
		var opener = event.target.closest('[data-aa-consent-open], a[href$="#manage-cookies"]');
		if (!opener) {
			return;
		}
		event.preventDefault();
		show(true);
	});

	if (!window.aaConsent.get()) {
		show(false);
	}
})();
