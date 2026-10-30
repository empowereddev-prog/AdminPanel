(() => {
  'use strict';
  document.querySelectorAll('#individual-subscriptions form.waitlist').forEach(form => {
    const section = form.closest('section');
    const input = form.querySelector('input[type="email"]');
    const button = form.querySelector('button[type="submit"]');
    const status = section.querySelector('.form-status');
    let submitting = false;

    if (form.dataset.registrationOpen === 'false') return;

    input.addEventListener('input', () => {
      input.removeAttribute('aria-invalid');
      input.setCustomValidity('');
    });

    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (submitting) return;
      input.value = input.value.trim();
      if (!form.reportValidity()) return;
      submitting = true;
      button.disabled = true;
      form.setAttribute('aria-busy', 'true');
      status.textContent = 'Registering your interest…';
      status.classList.remove('is-error', 'is-success');
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), 15000);
      try {
        const response = await fetch(form.dataset.endpoint, {
          method: 'POST',
          credentials: 'omit',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: input.value, website: form.elements.website.value }),
          signal: controller.signal,
        });
        const data = await response.json().catch(() => ({}));
        if (response.status === 429) throw new Error('Too many attempts. Please wait and try again later.');
        if (response.status === 422) {
          input.setAttribute('aria-invalid', 'true');
          input.focus();
          throw new Error(data.errors?.email?.[0] || 'Please check your email address and try again.');
        }
        if (!response.ok || data.success !== true) throw new Error('We couldn’t save your registration. Please try again later.');
        status.textContent = data.message;
        status.classList.add('is-success');
        form.reset();
      } catch (error) {
        status.textContent = error.name === 'AbortError'
          ? 'The request timed out. Please try again. If it was saved, your email will only be counted once.'
          : error instanceof TypeError
            ? 'We couldn’t connect. Please check your connection and try again.'
            : error.message;
        status.classList.add('is-error');
      } finally {
        clearTimeout(timer);
        submitting = false;
        button.disabled = false;
        form.removeAttribute('aria-busy');
      }
    });
  });
})();
