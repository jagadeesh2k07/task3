'use strict';

/* ── PARTICLE CANVAS ─────────────────────────────── */
const canvas = document.getElementById('particleCanvas');
if (canvas) {
  const ctx = canvas.getContext('2d');
  let particles = [];

  function resizeCanvas() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  function createParticle() {
    return {
      x:     Math.random() * canvas.width,
      y:     Math.random() * canvas.height,
      r:     Math.random() * 1.2 + 0.3,
      dx:    (Math.random() - 0.5) * 0.3,
      dy:    (Math.random() - 0.5) * 0.3,
      alpha: Math.random() * 0.5 + 0.1
    };
  }

  for (let i = 0; i < 110; i++) particles.push(createParticle());

  function drawParticles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(251, 146, 60, ${p.alpha})`;
      ctx.fill();
      p.x += p.dx;
      p.y += p.dy;
      if (p.x < 0 || p.x > canvas.width)  p.dx *= -1;
      if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
    });

    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dist = Math.hypot(particles[i].x - particles[j].x, particles[i].y - particles[j].y);
        if (dist < 100) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(251, 146, 60, ${0.06 * (1 - dist / 100)})`;
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(drawParticles);
  }
  drawParticles();
}

/* ── HELPERS ─────────────────────────────────────── */
const $ = id => document.getElementById(id);

function setErr(grp, errId, msg) {
  const g = $(grp), e = $(errId);
  if (!g || !e) return;
  g.classList.add('has-error');
  g.classList.remove('has-ok');
  e.textContent = msg;
}

function setOk(grp, errId) {
  const g = $(grp), e = $(errId);
  if (!g || !e) return;
  g.classList.remove('has-error');
  g.classList.add('has-ok');
  e.textContent = '';
}

function clrField(grp, errId) {
  const g = $(grp), e = $(errId);
  if (!g || !e) return;
  g.classList.remove('has-error', 'has-ok');
  e.textContent = '';
}

function showToast(id, msg, type = 'ok') {
  const t = $(id);
  if (!t) return;
  t.textContent = msg;
  t.className = `toast ${type}`;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 4000);
}

function setLoad(btnId, txtId, loadId, on) {
  const b = $(btnId), t = $(txtId), l = $(loadId);
  if (!b || !t || !l) return;
  b.disabled = on;
  t.classList.toggle('hidden', on);
  l.classList.toggle('hidden', !on);
}

const validEmail = v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
const validPhone = v => /^[+]?[\d\s\-()]{7,15}$/.test(v.trim());

/* ── TOGGLE PASSWORD VISIBILITY ──────────────────── */
function toggleEye(inputId, iconId) {
  const inp  = $(inputId);
  const icon = $(iconId);
  if (!inp || !icon) return;
  const show = inp.type === 'password';
  inp.type       = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}

/* ── SOCIAL BUTTONS ──────────────────────────────── */
(function initSocialBtns() {
  const g = $('googleBtn');
  const h = $('githubBtn');
  if (g) g.addEventListener('click', () => window.open('https://accounts.google.com', '_blank'));
  if (h) h.addEventListener('click', () => window.open('https://github.com/login',    '_blank'));
})();

/* ── PASSWORD STRENGTH ───────────────────────────── */
function getRules(pw) {
  return {
    len:   pw.length >= 8,
    upper: /[A-Z]/.test(pw),
    num:   /[0-9]/.test(pw),
    sym:   /[^A-Za-z0-9]/.test(pw),
  };
}

function isStrongEnough(pw) {
  const r = getRules(pw);
  return r.len && r.upper && r.num && r.sym;
}

/**
 * Updates the strength bar and password-rule list items.
 *
 * register.html rule IDs : rule-len, rule-upper, rule-num, rule-symbol
 * register.html bar IDs  : str-fill, str-txt
 *
 * forgot-pw modal bar IDs: fp-str-fill, fp-str-txt  (no rule list)
 */
function updateBar(pw, fillId, txtId, rulePrefix) {
  const fill = $(fillId || 'str-fill');
  const txt  = $(txtId  || 'str-txt');

  const rules = getRules(pw);
  const score = [rules.len, rules.upper, rules.num, rules.sym].filter(Boolean).length;

  const map = [
    { w: '0%',   c: 'transparent',          t: '' },
    { w: '25%',  c: '#ef4444',              t: 'Weak' },
    { w: '50%',  c: '#f97316',              t: 'Fair' },
    { w: '75%',  c: '#eab308',              t: 'Good' },
    { w: '100%', c: 'var(--accent-color)',  t: 'Strong ✓' },
  ];

  const s = pw.length === 0 ? 0 : score;
  if (fill) {
    fill.style.width      = map[s].w;
    fill.style.background = map[s].c;
  }
  if (txt) {
    txt.textContent  = pw ? map[s].t : '';
    txt.style.color  = map[s].c;
  }

  // Update rule list items (only present on register page)
  // HTML IDs: rule-len, rule-upper, rule-num, rule-symbol
  const ruleMap = {
    'rule-len':    rules.len,
    'rule-upper':  rules.upper,
    'rule-num':    rules.num,
    'rule-symbol': rules.sym,
  };
  Object.entries(ruleMap).forEach(([id, passed]) => {
    const el = $(id);
    if (!el) return;
    el.classList.toggle('met', passed);
    const icon = el.querySelector('i');
    if (icon) icon.className = passed ? 'fas fa-check-circle' : 'fas fa-circle';
  });
}

/* ── CONFIRM-PASSWORD MATCH ──────────────────────── */
/**
 * @param {string} pw       - original password value
 * @param {string} cpw      - confirm-password value
 * @param {string} grpId    - wrapper element id for confirm field
 * @param {string} errId    - error span id
 * @param {string} matchId  - match-ok span id
 */
function matchCheck(pw, cpw, grpId, errId, matchId) {
  const ok = $(matchId);
  if (!cpw) {
    clrField(grpId, errId);
    if (ok) ok.classList.add('hidden');
    return;
  }
  if (pw === cpw) {
    setOk(grpId, errId);
    if (ok) ok.classList.remove('hidden');
  } else {
    setErr(grpId, errId, 'Passwords do not match.');
    if (ok) ok.classList.add('hidden');
  }
}

/* ══════════════════════════════════════════════════
   LOGIN PAGE
══════════════════════════════════════════════════ */
(function initLogin() {
  const form = $('loginForm');
  if (!form) return;

  /* ── field live validation ── */
  $('email').addEventListener('blur', function () {
    const v = this.value.trim();
    if (!v)             return setErr('grp-email', 'err-email', 'Email is required.');
    if (!validEmail(v)) return setErr('grp-email', 'err-email', 'Enter a valid email address.');
    setOk('grp-email', 'err-email');
  });
  $('email').addEventListener('input', function () {
    if (this.value.trim()) clrField('grp-email', 'err-email');
  });

  $('password').addEventListener('blur', function () {
    const v = this.value;
    if (!v)           return setErr('grp-pass', 'err-pass', 'Password is required.');
    if (v.length < 6) return setErr('grp-pass', 'err-pass', 'At least 6 characters required.');
    setOk('grp-pass', 'err-pass');
  });
  $('password').addEventListener('input', function () {
    if (this.value) clrField('grp-pass', 'err-pass');
  });

  /* ── submit ── */
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    let ok = true;

    const email = $('email').value.trim();
    const pw    = $('password').value;

    if (!email)               { setErr('grp-email', 'err-email', 'Email is required.');       ok = false; }
    else if (!validEmail(email)) { setErr('grp-email', 'err-email', 'Enter a valid email.');  ok = false; }
    else                         setOk('grp-email', 'err-email');

    if (!pw)              { setErr('grp-pass', 'err-pass', 'Password is required.');   ok = false; }
    else if (pw.length < 6) { setErr('grp-pass', 'err-pass', 'At least 6 characters.'); ok = false; }
    else                    setOk('grp-pass', 'err-pass');

    if (!ok) return;

    setLoad('loginBtn', 'loginTxt', 'loginLoad', true);

    const formData = new FormData();
    formData.append('email',    email);
    formData.append('password', pw);

    fetch('login.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        setLoad('loginBtn', 'loginTxt', 'loginLoad', false);
        if (data.status === 'success') {
          showToast('toast', '✓ Signed in! Redirecting…', 'ok');
          setTimeout(() => { window.location.href = 'dashboard.php'; }, 1200);
        } else {
          showToast('toast', '✗ ' + data.message, 'bad');
        }
      })
      .catch(() => {
        setLoad('loginBtn', 'loginTxt', 'loginLoad', false);
        showToast('toast', '✗ Something went wrong. Try again.', 'bad');
      });
  });
})();

/* ══════════════════════════════════════════════════
   FORGOT PASSWORD MODAL  (index.html only)
══════════════════════════════════════════════════ */
(function initForgotPassword() {
  const overlay = $('fpOverlay');
  if (!overlay) return;          // not on login page → bail

  const forgotLink = $('forgotLink');
  const closeBtn   = $('fpClose');

  /* ── open / close ── */
  function openModal() {
    overlay.classList.remove('hidden');
    // reset to step 1 every time it opens
    showStep(1);
    clrField('grp-fpemail', 'err-fpemail');
    const fpEmail = $('fpEmail');
    if (fpEmail) fpEmail.value = '';
    const fpToast = $('fp-toast');
    if (fpToast) fpToast.classList.add('hidden');
  }

  function closeModal() {
    overlay.classList.add('hidden');
  }

  if (forgotLink) forgotLink.addEventListener('click', function (e) {
    e.preventDefault();
    openModal();
  });

  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  // Close on backdrop click
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeModal();
  });

  /* ── step visibility ── */
  function showStep(n) {
    [1, 2, 3].forEach(i => {
      const el = $(`fpStep${i}`);
      if (el) el.classList.toggle('hidden', i !== n);
    });
  }

  /* ─────────────────────────────────────
     STEP 1 — verify email
  ───────────────────────────────────── */
  window.fpNext1 = function () {
    const email = $('fpEmail').value.trim();

    clrField('grp-fpemail', 'err-fpemail');

    if (!email)              { setErr('grp-fpemail', 'err-fpemail', 'Email is required.');         return; }
    if (!validEmail(email))  { setErr('grp-fpemail', 'err-fpemail', 'Enter a valid email address.'); return; }

    setLoad('fpStep1Btn', 'fpStep1Txt', 'fpStep1Load', true);

    // Check if email exists in the system
    const fd = new FormData();
    fd.append('email', email);

    fetch('check_email.php', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        setLoad('fpStep1Btn', 'fpStep1Txt', 'fpStep1Load', false);
        // check_email.php returns { taken: true } when the email IS registered
        if (data.taken) {
          setOk('grp-fpemail', 'err-fpemail');
          showStep(2);
        } else {
          setErr('grp-fpemail', 'err-fpemail', 'No account found with this email.');
        }
      })
      .catch(() => {
        setLoad('fpStep1Btn', 'fpStep1Txt', 'fpStep1Load', false);
        showToast('fp-toast', '✗ Network error. Please try again.', 'bad');
      });
  };

  /* ─────────────────────────────────────
     STEP 2 — verify current password
  ───────────────────────────────────── */
  window.fpNext2 = function () {
    const email   = $('fpEmail').value.trim();
    const currPw  = $('fpCurr').value;

    clrField('grp-fpcurr', 'err-fpcurr');

    if (!currPw) { setErr('grp-fpcurr', 'err-fpcurr', 'Current password is required.'); return; }

    setLoad('fpStep2Btn', 'fpStep2Txt', 'fpStep2Load', true);

    const fd = new FormData();
    fd.append('email',    email);
    fd.append('password', currPw);

    fetch('verify_password.php', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        setLoad('fpStep2Btn', 'fpStep2Txt', 'fpStep2Load', false);
        if (data.status === 'success') {
          setOk('grp-fpcurr', 'err-fpcurr');
          showStep(3);
          // wire up new-password strength bar now that step 3 is visible
          const fpNew = $('fpNew');
          if (fpNew) {
            fpNew.addEventListener('input', function () {
              updateBar(this.value, 'fp-str-fill', 'fp-str-txt');
              clrField('grp-fpnew', 'err-fpnew');
              const conf = $('fpConf').value;
              if (conf) matchCheck(this.value, conf, 'grp-fpconf', 'err-fpconf', 'fp-match-ok');
            });
          }
          const fpConf = $('fpConf');
          if (fpConf) {
            fpConf.addEventListener('input', function () {
              matchCheck($('fpNew').value, this.value, 'grp-fpconf', 'err-fpconf', 'fp-match-ok');
            });
          }
        } else {
          setErr('grp-fpcurr', 'err-fpcurr', data.message || 'Incorrect password.');
        }
      })
      .catch(() => {
        setLoad('fpStep2Btn', 'fpStep2Txt', 'fpStep2Load', false);
        showToast('fp-toast', '✗ Network error. Please try again.', 'bad');
      });
  };

  /* ─────────────────────────────────────
     STEP 3 — set new password
  ───────────────────────────────────── */
  window.fpSubmit = function () {
    const email  = $('fpEmail').value.trim();
    const newPw  = $('fpNew').value;
    const confPw = $('fpConf').value;

    clrField('grp-fpnew',  'err-fpnew');
    clrField('grp-fpconf', 'err-fpconf');
    $('fp-match-ok') && $('fp-match-ok').classList.add('hidden');

    let ok = true;

    if (!newPw)               { setErr('grp-fpnew', 'err-fpnew', 'New password is required.');              ok = false; }
    else if (newPw.length < 8){ setErr('grp-fpnew', 'err-fpnew', 'Use at least 8 characters.');             ok = false; }
    else if (!isStrongEnough(newPw)) { setErr('grp-fpnew', 'err-fpnew', 'Must include uppercase, number & symbol.'); ok = false; }

    if (!confPw)          { setErr('grp-fpconf', 'err-fpconf', 'Please confirm your new password.'); ok = false; }
    else if (newPw !== confPw) { setErr('grp-fpconf', 'err-fpconf', 'Passwords do not match.');      ok = false; }

    if (!ok) return;

    setLoad('fpStep3Btn', 'fpStep3Txt', 'fpStep3Load', true);

    const fd = new FormData();
    fd.append('email',       email);
    fd.append('newPassword', newPw);

    fetch('reset_password.php', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        setLoad('fpStep3Btn', 'fpStep3Txt', 'fpStep3Load', false);
        if (data.status === 'success') {
          showToast('fp-toast', '✓ Password updated! You can now sign in.', 'ok');
          setTimeout(() => closeModal(), 2500);
        } else {
          showToast('fp-toast', '✗ ' + (data.message || 'Update failed. Try again.'), 'bad');
        }
      })
      .catch(() => {
        setLoad('fpStep3Btn', 'fpStep3Txt', 'fpStep3Load', false);
        showToast('fp-toast', '✗ Network error. Please try again.', 'bad');
      });
  };
})();

/* ══════════════════════════════════════════════════
   REGISTER PAGE
══════════════════════════════════════════════════ */
(function initRegister() {
  const form = $('registerForm');
  if (!form) return;

  /* ── password strength & rules ── */
  $('rpw').addEventListener('input', function () {
    updateBar(this.value, 'str-fill', 'str-txt');
    clrField('grp-rpw', 'err-rpw');
    const cv = $('cpw').value;
    if (cv) matchCheck(this.value, cv, 'grp-cpw', 'err-cpw', 'match-ok');
  });

  /* ── confirm password ── */
  $('cpw').addEventListener('input', function () {
    matchCheck($('rpw').value, this.value, 'grp-cpw', 'err-cpw', 'match-ok');
  });

  /* ── email availability check (debounced) ── */
  let emailTimer;
  $('remail').addEventListener('input', function () {
    clrField('grp-remail', 'err-remail');
    const h = $('hint-email');
    if (h) h.textContent = '';
    clearTimeout(emailTimer);
    const v = this.value.trim();
    if (validEmail(v)) {
      emailTimer = setTimeout(() => checkEmailAjax(v, 'hint-email'), 600);
    }
  });

  /* ── blur validators ── */
  $('fname').addEventListener('blur', function () {
    const v = this.value.trim();
    if (!v || v.length < 2) setErr('grp-fname', 'err-fname', 'Enter your first name.');
    else setOk('grp-fname', 'err-fname');
  });

  $('lname').addEventListener('blur', function () {
    const v = this.value.trim();
    if (!v) setErr('grp-lname', 'err-lname', 'Enter your last name.');
    else setOk('grp-lname', 'err-lname');
  });

  $('remail').addEventListener('blur', function () {
    const v = this.value.trim();
    if (!v)              setErr('grp-remail', 'err-remail', 'Email is required.');
    else if (!validEmail(v)) setErr('grp-remail', 'err-remail', 'Enter a valid email.');
  });

  $('phone').addEventListener('blur', function () {
    const v = this.value.trim();
    if (v && !validPhone(v)) setErr('grp-phone', 'err-phone', 'Enter a valid phone number.');
    else clrField('grp-phone', 'err-phone');
  });

  $('rpw').addEventListener('blur', function () {
    const v = this.value;
    if (!v)                   return setErr('grp-rpw', 'err-rpw', 'Password is required.');
    if (v.length < 8)         return setErr('grp-rpw', 'err-rpw', 'Use at least 8 characters.');
    if (!isStrongEnough(v))   return setErr('grp-rpw', 'err-rpw', 'Must include uppercase, number & symbol.');
    setOk('grp-rpw', 'err-rpw');
  });

  $('cpw').addEventListener('blur', function () {
    matchCheck($('rpw').value, this.value, 'grp-cpw', 'err-cpw', 'match-ok');
  });

  /* ── submit ── */
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    let ok = true;

    const fn    = $('fname').value.trim();
    const ln    = $('lname').value.trim();
    const email = $('remail').value.trim();
    const phone = $('phone').value.trim();
    const pw    = $('rpw').value;
    const cpw   = $('cpw').value;
    const terms = $('terms').checked;

    if (!fn || fn.length < 2) { setErr('grp-fname',  'err-fname',  'First name required.');   ok = false; } else setOk('grp-fname',  'err-fname');
    if (!ln)                  { setErr('grp-lname',  'err-lname',  'Last name required.');     ok = false; } else setOk('grp-lname',  'err-lname');

    if (!email)               { setErr('grp-remail', 'err-remail', 'Email is required.');      ok = false; }
    else if (!validEmail(email)) { setErr('grp-remail', 'err-remail', 'Enter a valid email.'); ok = false; }
    else setOk('grp-remail', 'err-remail');

    if (phone && !validPhone(phone)) { setErr('grp-phone', 'err-phone', 'Enter a valid phone.'); ok = false; }

    if (!pw)                  { setErr('grp-rpw', 'err-rpw', 'Password required.');                             ok = false; }
    else if (pw.length < 8)   { setErr('grp-rpw', 'err-rpw', 'At least 8 characters.');                        ok = false; }
    else if (!isStrongEnough(pw)) { setErr('grp-rpw', 'err-rpw', 'Must include uppercase, number & symbol.');   ok = false; }
    else setOk('grp-rpw', 'err-rpw');

    if (!cpw)          { setErr('grp-cpw', 'err-cpw', 'Confirm your password.'); ok = false; }
    else if (pw !== cpw) { setErr('grp-cpw', 'err-cpw', 'Passwords do not match.'); ok = false; }
    else setOk('grp-cpw', 'err-cpw');

    if (!terms) { setErr('grp-terms', 'err-terms', 'You must agree to continue.'); ok = false; }
    else clrField('grp-terms', 'err-terms');

    if (!ok) return;

    setLoad('regBtn', 'regTxt', 'regLoad', true);

    const formData = new FormData();
    formData.append('firstName', fn);
    formData.append('lastName',  ln);
    formData.append('email',     email);
    formData.append('phone',     phone);
    formData.append('password',  pw);

    fetch('register.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        setLoad('regBtn', 'regTxt', 'regLoad', false);
        if (data.status === 'success') {
          showToast('reg-toast', `✓ Account created! Welcome, ${fn}. Redirecting to sign in…`, 'ok');
          setTimeout(() => { window.location.href = 'index.html'; }, 2000);
        } else {
          showToast('reg-toast', '✗ ' + data.message, 'bad');
        }
      })
      .catch(() => {
        setLoad('regBtn', 'regTxt', 'regLoad', false);
        showToast('reg-toast', '✗ Something went wrong. Try again.', 'bad');
      });
  });
})();

/* ══════════════════════════════════════════════════
   EMAIL AVAILABILITY CHECK (register page)
══════════════════════════════════════════════════ */
function checkEmailAjax(val, hintId) {
  const hint = $(hintId);
  if (!hint) return;
  hint.style.color = 'var(--text-muted)';
  hint.textContent = 'Checking…';

  const formData = new FormData();
  formData.append('email', val);

  fetch('check_email.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.taken) {
        hint.style.color = '#e05555';
        hint.textContent = '✗ Email already registered.';
        setErr('grp-remail', 'err-remail', '');
      } else {
        hint.style.color = '#27a060';
        hint.textContent = '✓ Email is available.';
        setOk('grp-remail', 'err-remail');
      }
    })
    .catch(() => {
      hint.textContent = '';
    });
}