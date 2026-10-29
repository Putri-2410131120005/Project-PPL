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
    const guruMapelAccount = document.querySelector('[data-guru-mapel-account]');
    const account = document.querySelector('[data-tim-inklusi-account]');
    const gpkAccount = document.querySelector('[data-gpk-account]');

    if (! (role instanceof HTMLSelectElement) || ! (guruMapelAccount instanceof HTMLElement) || ! (account instanceof HTMLElement) || ! (gpkAccount instanceof HTMLElement)) {
        return;
    }

    const updateAccountVisibility = () => {
        const isGuruMapel = role.value === 'guru-mapel';
        guruMapelAccount.hidden = ! isGuruMapel;
        guruMapelAccount.querySelectorAll('select, input').forEach((field) => {
            field.disabled = ! isGuruMapel;
        });
        const isTimInklusi = role.value === 'tim-inklusi';
        account.hidden = ! isTimInklusi;
        account.querySelectorAll('select, input').forEach((field) => {
            field.disabled = ! isTimInklusi;
        });
        const isGpk = role.value === 'gpk';
        gpkAccount.hidden = ! isGpk;
        gpkAccount.querySelectorAll('select, input').forEach((field) => {
            field.disabled = ! isGpk;
        });
    };

    role.addEventListener('change', updateAccountVisibility);
    updateAccountVisibility();
};

const initialiseReportSummaryModal = () => {
    const backdrop = document.querySelector('[data-report-summary-modal]');
    const dialog = backdrop?.querySelector('[role="dialog"]');

    if (! (backdrop instanceof HTMLElement) || ! (dialog instanceof HTMLElement)) {
        return;
    }

    const close = () => {
        const closeUrl = backdrop.dataset.closeUrl;
        if (closeUrl) {
            window.location.assign(closeUrl);
        }
    };

    dialog.focus();
    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) {
            close();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });
};

const initialiseMobileSidebar = () => {
    document.querySelectorAll('[data-mobile-sidebar]').forEach((sidebar) => {
        const toggle = sidebar.querySelector('.abk-menu-toggle');
        const content = sidebar.querySelector('.abk-sidebar-content');

        if (! (toggle instanceof HTMLButtonElement) || ! (content instanceof HTMLElement)) {
            return;
        }

        const setOpen = (isOpen) => {
            sidebar.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
            const label = toggle.querySelector('.visually-hidden');
            if (label) {
                label.textContent = isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi';
            }
        };

        toggle.addEventListener('click', () => {
            setOpen(! sidebar.classList.contains('is-open'));
        });

        content.addEventListener('click', (event) => {
            if (window.innerWidth < 768 && event.target instanceof HTMLAnchorElement) {
                setOpen(false);
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                setOpen(false);
            }
        });
    });
};

const initialiseMasterAccordions = () => {
    document.querySelectorAll('[data-master-accordion]').forEach((accordion) => {
        const disclosures = [...accordion.querySelectorAll('.abk-master-disclosure')];

        disclosures.forEach((disclosure) => {
            disclosure.addEventListener('toggle', () => {
                if (! disclosure.open) {
                    return;
                }

                disclosures.forEach((otherDisclosure) => {
                    if (otherDisclosure !== disclosure) {
                        otherDisclosure.open = false;
                    }
                });
            });
        });
    });
};

const initialiseResponsiveTableDisclosures = () => {
    document.querySelectorAll('.abk-responsive-card-table, .ks-students-table').forEach((table) => {
        const rows = [...table.querySelectorAll('tbody tr')].filter((row) => ! row.querySelector('td[colspan]'));

        rows.forEach((row) => {
            const firstCell = row.querySelector('td:first-child');
            if (! (firstCell instanceof HTMLTableCellElement)) {
                return;
            }

            const studentName = firstCell.textContent.trim();
            const actionLinks = [...row.querySelectorAll('td:last-child a')];
            if (actionLinks.length) {
                const quickActions = document.createElement('div');
                quickActions.className = 'abk-row-quick-actions';
                actionLinks.forEach((link) => quickActions.append(link.cloneNode(true)));
                firstCell.append(quickActions);
                row.classList.add('has-quick-actions');
            }

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'abk-row-disclosure-toggle';
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', `Buka detail ${studentName}`);
            toggle.innerHTML = '<span aria-hidden="true"></span>';
            firstCell.append(toggle);

            const setOpen = (isOpen) => {
                row.classList.toggle('is-open', isOpen);
                toggle.setAttribute('aria-expanded', String(isOpen));
                toggle.setAttribute('aria-label', `${isOpen ? 'Tutup' : 'Buka'} detail ${studentName}`);
            };

            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = ! row.classList.contains('is-open');
                rows.forEach((otherRow) => {
                    if (otherRow !== row) {
                        otherRow.classList.remove('is-open');
                        const otherToggle = otherRow.querySelector('.abk-row-disclosure-toggle');
                        if (otherToggle instanceof HTMLButtonElement) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                            const otherName = otherRow.querySelector('td:first-child')?.textContent.trim() || 'siswa';
                            otherToggle.setAttribute('aria-label', `Buka detail ${otherName}`);
                        }
                    }
                });
                setOpen(willOpen);
            });

            firstCell.addEventListener('click', (event) => {
                const target = event.target;
                if (window.innerWidth < 768 && target instanceof Element && ! target.closest('a, button, input, select')) {
                    toggle.click();
                }
            });
        });

        table.classList.add('is-disclosure-ready');
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initialiseConfirmationModal();
        initialiseDevelopmentRoleSelector();
        initialiseReportSummaryModal();
        initialiseMobileSidebar();
        initialiseMasterAccordions();
        initialiseResponsiveTableDisclosures();
    }, { once: true });
} else {
    initialiseConfirmationModal();
    initialiseDevelopmentRoleSelector();
    initialiseReportSummaryModal();
    initialiseMobileSidebar();
    initialiseMasterAccordions();
    initialiseResponsiveTableDisclosures();
}
