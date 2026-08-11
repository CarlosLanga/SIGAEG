function showNotification(message, isSuccess = false) {
    const box = document.getElementById('form-message');
    if (!box) return;

    box.className = 'form-message ' + (isSuccess ? 'success' : 'error');
    box.innerHTML = `
        <span class="notif-icon">${isSuccess ? '✓' : '✕'}</span>
        <span class="notif-text">${message}</span>
    `;
    box.style.display = 'flex';

    // Scroll para a mensagem com um smooth behaviour
    window.scrollTo({ top: 0, behavior: 'smooth' });

    setTimeout(() => {
        box.style.display = 'none';
    }, 5000);
}

