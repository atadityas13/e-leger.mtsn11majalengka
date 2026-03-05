document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input, select, textarea').forEach((el) => {
    if (el.type === 'hidden') return;

    if (el.type === 'checkbox' || el.type === 'radio') {
      el.classList.add('form-check-input');
      return;
    }

    el.classList.add('form-control');
  });

  document.querySelectorAll('button').forEach((button) => {
    const hasBootstrapVariant = Array.from(button.classList).some((className) => className.startsWith('btn-'));
    button.classList.add('btn');

    if (hasBootstrapVariant) {
      return;
    }

    if (button.classList.contains('danger')) {
      button.classList.add('btn-danger');
      return;
    }

    if (button.classList.contains('secondary')) {
      button.classList.add('btn-outline-secondary');
      return;
    }

    button.classList.add('btn-success');
  });

  document.querySelectorAll('.table-wrap').forEach((wrap) => {
    wrap.classList.add('table-responsive');
  });

  document.querySelectorAll('table').forEach((table) => {
    table.classList.add('table', 'table-hover', 'align-middle', 'mb-0');
  });

  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (event) => {
      const msg = el.getAttribute('data-confirm') || 'Yakin?';
      if (!window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });
});
