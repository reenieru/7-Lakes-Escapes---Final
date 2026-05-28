// ── TAB SWITCHER ─────────────────────────────────────────────
const tabs = document.querySelectorAll('.tab-btn');
const panels = document.querySelectorAll('.form-panel');

tabs.forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.dataset.tab;

    tabs.forEach(t => {
      t.classList.remove('active');
      t.setAttribute('aria-selected', 'false');
    });
    panels.forEach(p => p.classList.remove('active'));

    btn.classList.add('active');
    btn.setAttribute('aria-selected', 'true');
    document.getElementById('panel-' + target).classList.add('active');
  });
});

// ── PASSWORD TOGGLE ───────────────────────────────────────────
document.querySelectorAll('.toggle-pass').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';

    // Swap icon
    btn.querySelector('.eye-icon').innerHTML = isPass
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  });
});

// ── LOADING STATE ON SUBMIT (Fixed for login-form) ───────────
const targetForm = document.getElementById('login-form');

if (targetForm) {
  targetForm.addEventListener('submit', (e) => {
    const btn = targetForm.querySelector('.btn-submit');
    if (btn) {
      btn.classList.add('loading');
    }
  });
}

// ── INPUT FOCUS HIGHLIGHT ─────────────────────────────────────
document.querySelectorAll('.form-input').forEach(input => {
  input.addEventListener('focus', () => {
    input.closest('.form-group').querySelector('label').style.color = 'var(--moss)';
  });
  input.addEventListener('blur', () => {
    input.closest('.form-group').querySelector('label').style.color = '';
  });
});
