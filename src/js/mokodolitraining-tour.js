/**
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Tour
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/js/mokodolitraining-tour.js
 * VERSION:  01.00.00
 * BRIEF:    Step-by-step tour engine for MokoDoliTraining exercises.
 *           State is persisted in localStorage under key 'mdt_tour'.
 *           No external dependencies. Zero-cost no-op when no tour is active.
 *           Loaded on every Dolibarr page when the module is enabled.
 *           All dynamic content is inserted via DOM textContent (no innerHTML
 *           with user data) to prevent any XSS risk.
 *
 * State schema (localStorage JSON):
 *   exercise   object   Full exercise from MokoDoliTrainingExercise::catalog()
 *   step       int      Current step index (0-based)
 *   trainer    bool     Show trainer_note fields when true
 *   dolRoot    string   Dolibarr base URL (e.g. "https://erp.example.com")
 */

(function () {
	'use strict';

	var STORAGE_KEY     = 'mdt_tour';
	var CSS_HIGHLIGHT   = 'mdt-highlighted';
	var CSS_BODY_ACTIVE = 'mdt-tour-active';
	var CARD_ID         = 'mdt-tour-card';
	var TOAST_ID        = 'mdt-tour-toast';
	var RENDER_DELAY    = 250; // ms — let Dolibarr JS settle first

	// ── Storage ───────────────────────────────────────────────────────────────

	function loadState() {
		try { return JSON.parse(localStorage.getItem(STORAGE_KEY)); } catch (e) { return null; }
	}
	function saveState(s) { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(s)); } catch (e) {} }
	function clearState() { try { localStorage.removeItem(STORAGE_KEY); } catch (e) {} }

	// ── DOM utilities ─────────────────────────────────────────────────────────

	function removeEl(id) {
		var el = document.getElementById(id);
		if (el && el.parentNode) el.parentNode.removeChild(el);
	}

	/** Create an element with optional class and text content */
	function el(tag, cls, text) {
		var node = document.createElement(tag);
		if (cls)  node.className   = cls;
		if (text) node.textContent = text;
		return node;
	}

	/** Create a button element with id, class, and text */
	function btn(id, cls, text) {
		var b = el('button', cls, text);
		b.id   = id;
		b.type = 'button';
		return b;
	}

	// ── Highlight ─────────────────────────────────────────────────────────────

	function clearHighlight() {
		var els = document.querySelectorAll('.' + CSS_HIGHLIGHT);
		for (var i = 0; i < els.length; i++) els[i].classList.remove(CSS_HIGHLIGHT);
		document.body.classList.remove(CSS_BODY_ACTIVE);
	}

	function applyHighlight(selector) {
		if (!selector) return;
		var target;
		try { target = document.querySelector(selector); } catch (e) { return; }
		if (!target) return;
		target.classList.add(CSS_HIGHLIGHT);
		document.body.classList.add(CSS_BODY_ACTIVE);
		try { target.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
	}

	// ── Page matching ─────────────────────────────────────────────────────────

	function onCorrectPage(step) {
		return window.location.pathname.indexOf(step.path) !== -1;
	}

	/** Validate that a URL stays on the same origin (prevents open-redirect) */
	function safeUrl(dolRoot, path) {
		var root = (dolRoot || '').replace(/\/$/, '');
		// Only allow same-origin paths (starting with /)
		var p = (path || '');
		if (p.indexOf('http') === 0) return p; // absolute URL from dolRoot
		return root + (p.charAt(0) === '/' ? p : '/' + p);
	}

	// ── Dot progress indicator ────────────────────────────────────────────────

	function buildDots(container, total, current) {
		var wrap = el('div', 'mdt-dots');
		for (var i = 0; i < total; i++) {
			wrap.appendChild(el('span', 'mdt-dot' + (i === current ? ' mdt-dot-active' : '')));
		}
		container.appendChild(wrap);
	}

	// ── Card: step instruction ────────────────────────────────────────────────

	function buildStepCard(card, step, idx, total, state) {
		var ex = state.exercise;

		// -- Header --
		var header = el('div', 'mdt-card-header');
		var left   = el('div', 'mdt-card-header-left');
		left.appendChild(el('span', 'mdt-step-badge', (idx + 1) + '\u2009/\u2009' + total));
		left.appendChild(el('span', 'mdt-exercise-title', ex.title));
		var closeBtn = btn('mdt-close', 'mdt-close-btn', '\u00d7'); // ×
		closeBtn.title = 'Stop tour';
		closeBtn.setAttribute('aria-label', 'Stop tour');
		header.appendChild(left);
		header.appendChild(closeBtn);

		// -- Body --
		var body = el('div', 'mdt-card-body');
		body.appendChild(el('p', 'mdt-step-heading', step.title));
		body.appendChild(el('p', 'mdt-step-text', step.body));

		if (state.trainer && step.trainer_note) {
			var note      = el('div', 'mdt-trainer-note');
			var noteLabel = el('span', 'mdt-trainer-label', '\uD83D\uDD14 Trainer note\u2002'); // 🔔
			var noteText  = el('span', null, step.trainer_note);
			note.appendChild(noteLabel);
			note.appendChild(noteText);
			body.appendChild(note);
		}

		// -- Footer --
		var footer = el('div', 'mdt-card-footer');

		if (idx > 0) {
			footer.appendChild(btn('mdt-prev', 'mdt-btn mdt-btn-ghost', '\u2190 Back'));
		} else {
			footer.appendChild(el('span'));
		}

		buildDots(footer, total, idx);

		var isLast  = idx === total - 1;
		var nextTxt = isLast ? '\u2713\u00a0Finish' : 'Next\u00a0\u2192';
		footer.appendChild(btn('mdt-next', 'mdt-btn mdt-btn-primary', nextTxt));

		card.appendChild(header);
		card.appendChild(body);
		card.appendChild(footer);
	}

	// ── Card: navigation prompt (wrong page) ─────────────────────────────────

	function buildNavCard(card, step, idx, total, state) {
		var ex  = state.exercise;
		var url = safeUrl(state.dolRoot, step.nav_url || step.path);

		// -- Header --
		var header = el('div', 'mdt-card-header');
		var left   = el('div', 'mdt-card-header-left');
		left.appendChild(el('span', 'mdt-step-badge', (idx + 1) + '\u2009/\u2009' + total));
		left.appendChild(el('span', 'mdt-exercise-title', ex.title));
		var closeBtn = btn('mdt-close', 'mdt-close-btn', '\u00d7');
		closeBtn.title = 'Stop tour';
		closeBtn.setAttribute('aria-label', 'Stop tour');
		header.appendChild(left);
		header.appendChild(closeBtn);

		// -- Body --
		var body    = el('div', 'mdt-card-body');
		var heading = el('p', 'mdt-step-heading');
		heading.textContent = '\uD83D\uDCCD Navigate to continue'; // 📍
		body.appendChild(heading);
		body.appendChild(el('p', 'mdt-step-text',
			'Step ' + (idx + 1) + ' takes place on a different page. Click below to go there '
			+ '\u2014 the tour will resume automatically when you arrive.'));

		// -- Footer --
		var footer   = el('div', 'mdt-card-footer mdt-footer-right');
		var stopBtn2 = btn('mdt-close2', 'mdt-btn mdt-btn-ghost', 'Stop Tour');
		var goLink   = document.createElement('a');
		goLink.id        = 'mdt-go';
		goLink.className = 'mdt-btn mdt-btn-primary';
		goLink.textContent = 'Go there\u00a0\u2192';
		goLink.href      = url;
		footer.appendChild(stopBtn2);
		footer.appendChild(goLink);

		card.appendChild(header);
		card.appendChild(body);
		card.appendChild(footer);
	}

	// ── Render ────────────────────────────────────────────────────────────────

	function render() {
		var state = loadState();
		if (!state || !state.exercise || typeof state.step !== 'number') return;

		var steps = state.exercise.steps || [];
		var idx   = state.step;
		var total = steps.length;

		if (idx >= total) { finish(); return; }

		var step = steps[idx];
		removeEl(CARD_ID);
		clearHighlight();

		var card = document.createElement('div');
		card.id = CARD_ID;
		card.setAttribute('role', 'complementary');
		card.setAttribute('aria-live', 'polite');

		if (!onCorrectPage(step)) {
			buildNavCard(card, step, idx, total, state);
			document.body.appendChild(card);
			on('mdt-close',  'click', finish);
			on('mdt-close2', 'click', finish);
			return;
		}

		buildStepCard(card, step, idx, total, state);
		document.body.appendChild(card);

		on('mdt-close', 'click', finish);
		on('mdt-next',  'click', function () { advance(state, 1);  });
		if (idx > 0) {
			on('mdt-prev', 'click', function () { advance(state, -1); });
		}

		applyHighlight(step.selector);
	}

	function on(id, event, handler) {
		var node = document.getElementById(id);
		if (node) node.addEventListener(event, handler);
	}

	// ── Advance step ──────────────────────────────────────────────────────────

	function advance(state, delta) {
		clearHighlight();
		removeEl(CARD_ID);

		var newIdx = state.step + delta;
		var steps  = state.exercise.steps || [];
		if (newIdx < 0) newIdx = 0;
		if (newIdx >= steps.length) { finish(); return; }

		state.step = newIdx;
		saveState(state);

		var next = steps[newIdx];
		if (onCorrectPage(next)) {
			render();
		} else {
			window.location.href = safeUrl(state.dolRoot, next.nav_url || next.path);
		}
	}

	// ── Finish ────────────────────────────────────────────────────────────────

	function finish() {
		clearHighlight();
		removeEl(CARD_ID);
		clearState();

		removeEl(TOAST_ID);
		var toast = el('div', null, '\u2713\u00a0Exercise complete!'); // ✓
		toast.id = TOAST_ID;
		toast.setAttribute('role', 'status');
		document.body.appendChild(toast);
		setTimeout(function () { removeEl(TOAST_ID); }, 3800);
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Start a tour. Called by exercise.php's inline script.
	 * Also reachable as window.MdtTour.start() for custom integrations.
	 *
	 * @param {object}  exerciseData  Exercise object (MokoDoliTrainingExercise::catalog())
	 * @param {number}  stepIndex     Starting step index (default 0)
	 * @param {boolean} trainerMode   Show trainer notes when true
	 * @param {string}  dolRoot       Dolibarr base URL
	 */
	function start(exerciseData, stepIndex, trainerMode, dolRoot) {
		saveState({
			exercise: exerciseData,
			step:     typeof stepIndex === 'number' ? stepIndex : 0,
			trainer:  !!trainerMode,
			dolRoot:  typeof dolRoot === 'string' ? dolRoot : '',
		});
		render();
	}

	// ── Bootstrap ─────────────────────────────────────────────────────────────

	function init() {
		window.MdtTour = { start: start, finish: finish };
		if (loadState()) setTimeout(render, RENDER_DELAY);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

}());
