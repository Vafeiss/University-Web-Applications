(function () {
	'use strict';

	if (window.__advicutRequestBufferInstalled) {
		return;
	}

	window.__advicutRequestBufferInstalled = true;

	var READY_PROBE_URL = '../backend/modules/dispatcher.php?route_diag=1';
	var READY_POLL_INTERVAL_MS = 500;
	var BUFFER_UI_ID = 'advicut-request-buffer';
	var BUFFER_STYLE_ID = 'advicut-request-buffer-style';

	var originalFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;
	var originalFormSubmit = HTMLFormElement.prototype.submit;
	var originalRequestSubmit = typeof HTMLFormElement.prototype.requestSubmit === 'function'
		? HTMLFormElement.prototype.requestSubmit
		: null;

	var serverReady = false;
	var readinessPromise = null;
	var bufferedRequestCount = 0;
	var activePostCount = 0;
	var requestBufferSuppressionCount = 0;

	function isPostMethod(method) {
		return String(method || '').toUpperCase() === 'POST';
	}

	function getFormMethod(form) {
		return String(form.getAttribute('method') || form.method || 'GET').toUpperCase();
	}

	function shouldBufferForm(form) {
		return form instanceof HTMLFormElement && isPostMethod(getFormMethod(form));
	}

	function getFetchMethod(input, init) {
		if (init && init.method) {
			return init.method;
		}

		if (typeof Request !== 'undefined' && input instanceof Request) {
			return input.method;
		}

		return 'GET';
	}

	function getFetchAction(init) {
		if (!init || !(init.body instanceof FormData)) {
			return '';
		}

		return String(init.body.get('action') || '');
	}

	function isSilentCommunicationRequest(input, init) {
		var action = getFetchAction(init);
		return action === '/message/thread'
			|| action === '/message/read'
			|| action === '/message/send'
			|| action === '/student/message/thread'
			|| action === '/student/message/read'
			|| action === '/student/message/send';
	}

	function shouldBufferFetch(input, init) {
		if (requestBufferSuppressionCount > 0) {
			return false;
		}

		if (isSilentCommunicationRequest(input, init)) {
			return false;
		}

		return isPostMethod(getFetchMethod(input, init));
	}

	window.__advicutRequestBufferRunWithoutBuffer = function (callback) {
		requestBufferSuppressionCount += 1;

		try {
			return callback();
		} finally {
			requestBufferSuppressionCount -= 1;
		}
	}

	function ensureBufferStyle() {
		if (document.getElementById(BUFFER_STYLE_ID)) {
			return;
		}

		var style = document.createElement('style');
		style.id = BUFFER_STYLE_ID;
		style.textContent = [
			'#' + BUFFER_UI_ID + ' {',
			'\tposition: fixed !important;',
			'\tinset: 0 !important;',
			'\tz-index: 2147483647 !important;',
			'\tdisplay: flex !important;',
			'\talign-items: center !important;',
			'\tjustify-content: center !important;',
			'\tpadding: 1.5rem !important;',
			'\tbackground: rgba(17, 24, 39, 0.45) !important;',
			'\tbackdrop-filter: blur(2px) !important;',
			'\t-webkit-backdrop-filter: blur(2px) !important;',
			'\topacity: 0 !important;',
			'\tpointer-events: none !important;',
			'\ttransition: opacity 180ms ease;',
			'}',
			'#' + BUFFER_UI_ID + '.is-visible {',
			'\topacity: 1 !important;',
			'\tpointer-events: auto !important;',
			'}',
			'#' + BUFFER_UI_ID + ' .advicut-request-buffer-card {',
			'\tdisplay: flex;',
			'\talign-items: center;',
			'\tgap: 0.9rem;',
			'\tflex-direction: column;',
			'\tpadding: 1.25rem 1.5rem;',
			'\tborder-radius: 16px;',
			'\tbackground: rgba(17, 24, 39, 0.82);',
			'\tcolor: #fff;',
			'\tbox-shadow: 0 18px 40px rgba(15, 23, 42, 0.24);',
			'\tfont: 600 0.98rem/1.2 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;',
			'\tmin-width: min(92vw, 360px);',
			'\ttext-align: center;',
			'}',
			'#' + BUFFER_UI_ID + ' .advicut-request-buffer-spinner {',
			'\twidth: 3rem;',
			'\theight: 3rem;',
			'\tborder: 0.35rem solid rgba(255, 255, 255, 0.28);',
			'\tborder-top-color: #ffffff;',
			'\tborder-radius: 50%;',
			'\tanimation: advicut-request-buffer-spin 0.8s linear infinite;',
			'}',
			'#' + BUFFER_UI_ID + ' .advicut-request-buffer-title {',
			'\tfont-size: 1rem;',
			'\tfont-weight: 700;',
			'\tletter-spacing: 0.01em;',
			'}',
			'#' + BUFFER_UI_ID + ' .advicut-request-buffer-text {',
			'\tfont-size: 0.92rem;',
			'\tfont-weight: 500;',
			'\topacity: 0.95;',
			'}',
			'@keyframes advicut-request-buffer-spin {',
			'\tto { transform: rotate(360deg); }',
			'}'
		].join('\n');
		document.head.appendChild(style);
	}

	function ensureBufferUi() {
		ensureBufferStyle();

		var existing = document.getElementById(BUFFER_UI_ID);
		if (existing) {
			return existing;
		}

		var root = document.createElement('div');
		root.id = BUFFER_UI_ID;
		root.setAttribute('aria-live', 'polite');
		root.setAttribute('aria-atomic', 'true');
		root.style.setProperty('position', 'fixed', 'important');
		root.style.setProperty('inset', '0', 'important');
		root.style.setProperty('z-index', '2147483647', 'important');
		root.style.setProperty('display', 'flex', 'important');
		root.style.setProperty('align-items', 'center', 'important');
		root.style.setProperty('justify-content', 'center', 'important');
		root.style.setProperty('pointer-events', 'none', 'important');
		root.innerHTML = [
			'<div class="advicut-request-buffer-card">',
			'<div class="advicut-request-buffer-spinner" aria-hidden="true"></div>',
			'<div class="advicut-request-buffer-title">Saving changes...</div>',
			'<div class="advicut-request-buffer-text">Preparing the server, holding POST requests...</div>',
			'</div>'
		].join('');

		document.body.appendChild(root);
		return root;
	}

	function updateBufferUi() {
		var root = document.getElementById(BUFFER_UI_ID);
		if (!root) {
			return;
		}

		var title = root.querySelector('.advicut-request-buffer-title');
		var text = root.querySelector('.advicut-request-buffer-text');
		if (title) {
			title.textContent = 'Loading...';
		}

		if (text) {
			text.textContent = '';
		}

		root.classList.toggle('is-visible', activePostCount > 0 || (bufferedRequestCount > 0 && !serverReady));
	}

	function showBufferUi() {
		bufferedRequestCount += 1;
		ensureBufferUi();
		updateBufferUi();
	}

	function hideBufferUi() {
		if (bufferedRequestCount > 0) {
			bufferedRequestCount -= 1;
		}

		updateBufferUi();
	}

	function incrementActivePostCount() {
		activePostCount += 1;
		showBufferUi();
	}

	function decrementActivePostCount() {
		if (activePostCount > 0) {
			activePostCount -= 1;
		}

		if (!serverReady && activePostCount <= 0 && bufferedRequestCount <= 0) {
			hideBufferUi();
		}
	}

	function probeServer() {
		if (!originalFetch) {
			return Promise.resolve();
		}

		return originalFetch(READY_PROBE_URL, {
			method: 'GET',
			cache: 'no-store',
			credentials: 'same-origin'
		}).then(function (response) {
			if (response && response.ok) {
				return response;
			}

			throw new Error('Server is not ready yet.');
		});
	}

	function waitForServerReady() {
		if (serverReady) {
			return Promise.resolve();
		}

		if (readinessPromise) {
			return readinessPromise;
		}

		readinessPromise = new Promise(function (resolve) {
			function attempt() {
				probeServer()
					.then(function () {
						serverReady = true;
						updateBufferUi();
						resolve();
					})
					.catch(function () {
						window.setTimeout(attempt, READY_POLL_INTERVAL_MS);
					});
			}

			attempt();
		});

		return readinessPromise;
	}

	function submitFormAfterReady(form, submitter, useRequestSubmit) {
		return waitForServerReady().then(function () {
			if (useRequestSubmit && originalRequestSubmit) {
				if (submitter) {
					originalRequestSubmit.call(form, submitter);
				} else {
					originalRequestSubmit.call(form);
				}
				return;
			}

			originalFormSubmit.call(form);
		});
	}

	if (originalFetch) {
		window.fetch = function (input, init) {
			if (!shouldBufferFetch(input, init)) {
				return originalFetch(input, init);
			}

			incrementActivePostCount();

			return waitForServerReady().then(function () {
				return originalFetch(input, init);
			}).finally(function () {
				decrementActivePostCount();
			});
		};
	}

	HTMLFormElement.prototype.submit = function () {
		if (!shouldBufferForm(this) || serverReady) {
			if (shouldBufferForm(this)) {
				incrementActivePostCount();
			}

			return originalFormSubmit.call(this);
		}

		incrementActivePostCount();
		return submitFormAfterReady(this, null, false);
	};

	if (originalRequestSubmit) {
		HTMLFormElement.prototype.requestSubmit = function (submitter) {
			if (!shouldBufferForm(this) || serverReady) {
				if (shouldBufferForm(this)) {
					incrementActivePostCount();
				}

				if (submitter) {
					return originalRequestSubmit.call(this, submitter);
				}

				return originalRequestSubmit.call(this);
			}

			incrementActivePostCount();
			return submitFormAfterReady(this, submitter || null, true);
		};
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;

		if (!(form instanceof HTMLFormElement) || !shouldBufferForm(form) || serverReady || event.defaultPrevented) {
			return;
		}

		event.preventDefault();
		incrementActivePostCount();
		submitFormAfterReady(form, event.submitter || null, true);
	});
})();
