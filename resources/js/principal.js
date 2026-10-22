// Scoped to A19; downloads announce completion only after a real response blob.
if (document.body.classList.contains('ks')) {
    document.querySelectorAll('[data-auto-submit]').forEach(input => input.addEventListener('change', () => input.form.requestSubmit()));
    const toast = document.querySelector('.ks-toast');
    document.querySelector('[data-toast-close]')?.addEventListener('click', () => { toast.hidden = true; });
    document.querySelectorAll('[data-download]').forEach(link => link.addEventListener('click', async event => {
        event.preventDefault();
        if (link.getAttribute('aria-busy') === 'true') return;
        link.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(link.href, { headers: { Accept: 'application/octet-stream' }, credentials: 'same-origin' });
            if (!response.ok || response.redirected || !/application\/(pdf|zip|vnd\.openxmlformats-officedocument\.spreadsheetml\.sheet)/.test(response.headers.get('content-type') || '')) throw new Error();
            const blob = await response.blob();
            if (!blob.size) throw new Error();
            const url = URL.createObjectURL(blob);
            const download = document.createElement('a');
            download.href = url;
            download.download = response.headers.get('content-disposition')?.match(/filename="([^"]+)"/)?.[1] || 'smart-abk-laporan';
            download.click();
            setTimeout(() => URL.revokeObjectURL(url), 30000);
            toast.querySelector('[data-download-title]').textContent = 'Unduhan siap';
            toast.querySelector('[data-download-message]').textContent = 'File berhasil disiapkan untuk diunduh.';
            toast.classList.remove('ks-toast-error');
            toast.querySelector('.ks-success-icon').textContent = '✓';
        } catch {
            toast.querySelector('[data-download-title]').textContent = 'Unduhan gagal';
            toast.querySelector('[data-download-message]').textContent = 'Laporan belum tersedia atau unduhan gagal. Periksa pilihan dan coba kembali.';
            toast.classList.add('ks-toast-error');
            toast.querySelector('.ks-success-icon').textContent = '!';
        } finally {
            toast.hidden = false;
            link.removeAttribute('aria-busy');
        }
    }));
    const dialog = document.querySelector('.ks-note-dialog');
    if (dialog) {
        dialog.focus();
        dialog.addEventListener('keydown', event => {
            if (event.key === 'Escape') dialog.querySelector('a').click();
            if (event.key === 'Tab') { event.preventDefault(); dialog.querySelector('a').focus(); }
        });
    }
}
