(() => {
  'use strict';
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const button = form.querySelector('[type="submit"]');
    if (!button || form.dataset.allowRepeat === 'true') return;
    if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
    form.dataset.submitting = 'true';
    button.disabled = true;
    button.dataset.originalText = button.textContent || '';
    button.textContent = 'Please wait…';
  });
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!window.confirm(element.getAttribute('data-confirm') || 'Continue with this action?')) event.preventDefault();
    });
  });
})();
