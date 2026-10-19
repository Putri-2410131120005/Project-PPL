import './bootstrap';
import { Modal } from 'bootstrap';

/**
 * Confirmation is deliberately opt-in: only forms marked with
 * `data-confirm-form` are intercepted. This keeps ordinary navigation and
 * non-mutating forms immediate while making state-changing actions explicit.
 */
const initialiseConfirmationModal = () => {
    const modalElement = document.getElementById('abkConfirmationModal');

    if (! modalElement) {
        return;
    }

    const modal = Modal.getOrCreateInstance(modalElement);
    const title = document.getElementById('abkConfirmationModalTitle');
    const message = document.getElementById('abkConfirmationModalMessage');
    const subject = document.getElementById('abkConfirmationModalSubject');
    const confirmButton = document.getElementById('abkConfirmationModalConfirm');
    let pendingForm = null;

    if (! title || ! message || ! subject || ! confirmButton) {
        return;
    }

    const setVariant = (variant) => {
        const allowedVariants = ['primary', 'danger', 'destructive'];
        const resolvedVariant = allowedVariants.includes(variant) ? variant : 'primary';

        confirmButton.classList.remove(
            'abk-confirm-action-primary',
            'abk-confirm-action-danger',
            'abk-confirm-action-destructive',
        );
        confirmButton.classList.add(`abk-confirm-action-${resolvedVariant}`);
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement) || ! form.matches('[data-confirm-form]')) {
            return;
        }

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        pendingForm = form;

        title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
        message.textContent = form.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';

        const subjectText = form.dataset.confirmSubject;
        subject.textContent = subjectText || '';
        subject.classList.toggle('d-none', ! subjectText);

        confirmButton.textContent = form.dataset.confirmButton || 'Ya, lanjutkan';
        setVariant(form.dataset.confirmVariant);
        modal.show();
    });

    confirmButton.addEventListener('click', () => {
        if (! pendingForm) {
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = 'true';
        modal.hide();
        form.requestSubmit();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        pendingForm = null;
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseConfirmationModal, { once: true });
} else {
    initialiseConfirmationModal();
}
