(function () {
  'use strict';

  var modalInstance = null;
  var pendingResolve = null;

  function ensureModal() {
    var existing = document.getElementById('confirmActionModal');
    if (existing) {
      if (!modalInstance) {
        modalInstance = bootstrap.Modal.getOrCreateInstance(existing);
      }
      return existing;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = [
      '<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">',
      '  <div class="modal-dialog modal-dialog-centered">',
      '    <div class="modal-content border-0 shadow">',
      '      <div class="modal-header border-0 pb-0">',
      '        <h5 class="modal-title fw-semibold" id="confirmActionTitle">Please confirm</h5>',
      '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
      '      </div>',
      '      <div class="modal-body pt-2" id="confirmActionMessage"></div>',
      '      <div class="modal-footer border-0 pt-0">',
      '        <button type="button" class="btn btn-light" data-confirm-cancel>Cancel</button>',
      '        <button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button>',
      '      </div>',
      '    </div>',
      '  </div>',
      '</div>'
    ].join('');

    document.body.appendChild(wrapper.firstElementChild);
    existing = document.getElementById('confirmActionModal');
    modalInstance = bootstrap.Modal.getOrCreateInstance(existing);

    existing.addEventListener('click', function (event) {
      if (event.target && event.target.matches('[data-confirm-cancel]')) {
        if (pendingResolve) pendingResolve(false);
        pendingResolve = null;
        modalInstance.hide();
      }

      if (event.target && event.target.matches('[data-confirm-ok]')) {
        if (pendingResolve) pendingResolve(true);
        pendingResolve = null;
        modalInstance.hide();
      }
    });

    existing.addEventListener('hidden.bs.modal', function () {
      if (pendingResolve) {
        pendingResolve(false);
        pendingResolve = null;
      }
    });

    return existing;
  }

  function confirmAction(message, title) {
    var modal = ensureModal();
    var messageEl = modal.querySelector('#confirmActionMessage');
    var titleEl = modal.querySelector('#confirmActionTitle');

    if (messageEl) {
      messageEl.textContent = message || 'Are you sure you want to continue?';
    }

    if (titleEl) {
      titleEl.textContent = title || 'Please confirm';
    }

    modalInstance.show();

    return new Promise(function (resolve) {
      pendingResolve = resolve;
    });
  }

  function handleConfirmTrigger(trigger) {
    var message = trigger.getAttribute('data-confirm');
    if (!message) return;

    return confirmAction(message, trigger.getAttribute('data-confirm-title') || 'Please confirm')
      .then(function (ok) {
        if (!ok) return;

        if (trigger.tagName === 'A' && trigger.href) {
          window.location.href = trigger.href;
          return;
        }

        if (trigger.tagName === 'FORM') {
          trigger.submit();
          return;
        }

        if (trigger.type === 'submit' && trigger.form) {
          trigger.form.submit();
        }
      });
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-confirm]');
    if (!trigger || trigger.tagName === 'FORM') return;

    event.preventDefault();
    handleConfirmTrigger(trigger);
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('form[data-confirm]');
    if (!form) return;

    event.preventDefault();
    handleConfirmTrigger(form);
  });

  window.confirmAction = confirmAction;
})();
