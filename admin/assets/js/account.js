(function(){
  // --- Header profile dropdown toggle ---
  const profileBtn = document.getElementById('profile-dropdown-btn');
  const profileDropdown = document.getElementById('profile-dropdown');
  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', function(e){
      e.stopPropagation();
      profileDropdown.classList.toggle('active');
    });
    document.addEventListener('click', function(){
      profileDropdown.classList.remove('active');
    });
  }

  // --- Password change form logic ---
  const form = document.getElementById('changePasswordForm');
  if(!form) return;

  const btn = document.getElementById('changePasswordBtn');
  const msg = document.getElementById('changePasswordMessage');
  const showMsg = (text, ok) => {
    if(!msg) return;
    msg.style.display = 'block';
    msg.textContent = text;
    msg.className = 'form-message ' + (ok ? 'success' : 'error');
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Clear old errors
    ['current_password','new_password','confirm_password'].forEach(id => {
      const span = document.getElementById(id + '-error');
      if (span) span.textContent = '';
    });
    if (msg) { msg.style.display = 'none'; msg.textContent=''; msg.className='form-message'; }

    const fd = new FormData(form);

    // Basic client-side validation
    const newPwd = fd.get('new_password')?.toString() || '';
    const confirm = fd.get('confirm_password')?.toString() || '';
    if (newPwd.length < 8) {
      const span = document.getElementById('new_password-error');
      if (span) span.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
      return;
    }
    if (newPwd !== confirm) {
      const span = document.getElementById('confirm_password-error');
      if (span) span.textContent = 'La confirmación no coincide.';
      return;
    }

    // Toggle loading state
    if (btn) {
      btn.disabled = true;
      const text = btn.querySelector('.btn-text');
      const loading = btn.querySelector('.btn-loading');
      if (text) text.style.display = 'none';
      if (loading) loading.style.display = 'inline-block';
    }

    try {
      const res = await fetch('api/admins/change_password.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      });
      const data = await res.json().catch(() => ({ success:false, message:'Respuesta inválida del servidor' }));
      if (data.success) {
        showMsg(data.message || 'Contraseña actualizada correctamente.', true);
        form.reset();
      } else {
        // If server returns specific message, show at top; optionally attach inline
        showMsg(data.message || 'No se pudo actualizar la contraseña.', false);
      }
    } catch (err) {
      showMsg('Error de red. Inténtalo de nuevo.', false);
    } finally {
      if (btn) {
        btn.disabled = false;
        const text = btn.querySelector('.btn-text');
        const loading = btn.querySelector('.btn-loading');
        if (text) text.style.display = '';
        if (loading) loading.style.display = 'none';
      }
    }
  });
})();
