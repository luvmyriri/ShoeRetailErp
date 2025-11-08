// Unified AJAX form submission and alert helpers for HR pages

function submitFormAjax(formId, endpoint, callback) {
  const form = document.getElementById(formId);
  if (!form) return;
  const data = new FormData(form);

  fetch(endpoint, { method: 'POST', body: data })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showAlert(res.message || 'Success', 'success');
        if (callback) callback(res);
        if (res.redirectUrl) window.location.href = res.redirectUrl;
      } else {
        showAlert(res.message || 'Error', 'error');
      }
    })
    .catch(e => showAlert('Network error: ' + e, 'error'));
}

function showAlert(msg, type) {
  const container = document.querySelector('.alert-container') || createAlertContainer();
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.innerHTML = `<i class="alert-icon fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${msg}
    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>`;
  container.appendChild(alert);
  setTimeout(() => alert.remove(), 6000);
}

function createAlertContainer() {
  const c = document.createElement('div');
  c.className = 'alert-container';
  document.body.prepend(c);
  return c;
}
