import './bootstrap';
import { Modal } from 'bootstrap';

// Menyiapkan modal konfirmasi untuk formulir yang mengubah data.
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
    let pendingSubmitter = null;

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

        if (! (form instanceof HTMLFormElement)) {
            return;
        }

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        const confirmOnly = form.dataset.confirmOnly;
        const requiresConfirmation = form.matches('[data-confirm-form]')
            || (confirmOnly && submitter?.getAttribute('value') === confirmOnly);

        if (! requiresConfirmation) {
            return;
        }

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = submitter;

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
        const submitter = pendingSubmitter;
        pendingForm = null;
        pendingSubmitter = null;
        form.dataset.confirmed = 'true';
        modal.hide();
        form.requestSubmit(submitter);
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        pendingForm = null;
        pendingSubmitter = null;
    });
};

const initialiseDevelopmentRoleSelector = () => {
    const role = document.querySelector('[data-development-role]');
    const account = document.querySelector('[data-tim-inklusi-account]');

    if (! (role instanceof HTMLSelectElement) || ! (account instanceof HTMLElement)) {
        return;
    }

    const updateAccountVisibility = () => {
        const isTimInklusi = role.value === 'tim-inklusi';
        account.hidden = ! isTimInklusi;
        account.querySelectorAll('select, input').forEach((field) => {
            field.disabled = ! isTimInklusi;
        });
    };

    role.addEventListener('change', updateAccountVisibility);
    updateAccountVisibility();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initialiseConfirmationModal();
        initialiseDevelopmentRoleSelector();
    }, { once: true });
} else {
    initialiseConfirmationModal();
    initialiseDevelopmentRoleSelector();
}
