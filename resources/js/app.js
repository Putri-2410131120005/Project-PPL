import './bootstrap';
import { Modal } from 'bootstrap';

// Menyimpan state global yang diperlukan oleh PWA dan alur login Google.
let serviceWorkerRegistration;

const isInstalledPwa = () => window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: fullscreen)').matches
    || window.navigator.standalone === true;

const oauthHistoryKey = 'smart-abk:oauth-history';
const oauthHistoryMaxAge = 10 * 60 * 1000;

const readOauthHistory = () => {
    try {
        const value = JSON.parse(window.sessionStorage.getItem(oauthHistoryKey));
        if (! value || Date.now() - value.startedAt > oauthHistoryMaxAge) {
            window.sessionStorage.removeItem(oauthHistoryKey);
            return null;
        }
        return value;
    } catch {
        window.sessionStorage.removeItem(oauthHistoryKey);
        return null;
    }
};

// OAuth dapat menambahkan beberapa entry Google ke history PWA. Catat posisi
// sebelum OAuth agar callback dapat kembali ke entry aplikasi tanpa melintasi
// halaman Google satu per satu.
const initialiseOAuthHistoryIsolation = () => {
    const googleLogin = document.querySelector('.abk-google-login-button:not([aria-disabled="true"])');
    if (! isInstalledPwa() || ! (googleLogin instanceof HTMLAnchorElement)) return;

    googleLogin.addEventListener('click', () => {
        window.sessionStorage.setItem(oauthHistoryKey, JSON.stringify({
            phase: 'started',
            baselineLength: window.history.length,
            startedAt: Date.now(),
        }));
    });
};

const registerServiceWorker = () => {
    const serviceWorkerUrl = document.querySelector('meta[name="pwa-service-worker"]')?.getAttribute('content');

    if (serviceWorkerUrl && 'serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => navigator.serviceWorker.register(serviceWorkerUrl)
            .then((registration) => { serviceWorkerRegistration = registration; })
            .catch(() => {}), { once: true });
    }
};

const urlBase64ToUint8Array = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
};

const initialisePwaControls = () => {
    const installButton = document.querySelector('[data-pwa-install]');
    const notificationButton = document.querySelector('[data-push-enable]');
    const installHint = document.querySelector('[data-pwa-install-hint]');
    let installPrompt;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        installButton?.removeAttribute('hidden');
        installHint?.setAttribute('hidden', '');
    });
    installButton?.addEventListener('click', async () => {
        if (! installPrompt) return;
        await installPrompt.prompt();
        installPrompt = null;
        installButton.setAttribute('hidden', '');
    });

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (! isStandalone && isIos && installHint) {
        installHint.textContent = 'Untuk instal: tekan Bagikan, lalu Tambahkan ke Layar Utama.';
        installHint.removeAttribute('hidden');
    } else if (! isStandalone && installHint) {
        window.setTimeout(() => {
            if (! installPrompt) {
                installHint.textContent = 'Buka menu browser lalu pilih Instal aplikasi.';
                installHint.removeAttribute('hidden');
            }
        }, 1200);
    }

    if (! notificationButton || ! ('Notification' in window) || ! ('PushManager' in window)) return;
    notificationButton.removeAttribute('hidden');

    const storeUrl = document.querySelector('meta[name="push-subscription-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const storeSubscription = async (subscription) => {
        const response = await fetch(storeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: JSON.stringify(subscription),
        });
        if (! response.ok) throw new Error('Langganan notifikasi tidak dapat disimpan.');
    };
    const markNotificationsActive = () => {
        notificationButton.textContent = 'Notifikasi aktif';
        notificationButton.disabled = true;
    };

    if (Notification.permission === 'granted') {
        navigator.serviceWorker.ready
            .then((registration) => registration.pushManager.getSubscription())
            .then(async (subscription) => {
                if (! subscription) return;
                await storeSubscription(subscription);
                markNotificationsActive();
            })
            .catch(() => {});
    }

    notificationButton.addEventListener('click', async () => {
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') throw new Error('Izin notifikasi belum diberikan.');
            const registration = serviceWorkerRegistration || await navigator.serviceWorker.ready;
            const keyUrl = document.querySelector('meta[name="push-vapid-key-url"]')?.content;
            const keyResponse = await fetch(keyUrl, { credentials: 'same-origin' });
            if (! keyResponse.ok) throw new Error('Push notification belum siap dikonfigurasi.');
            const { publicKey } = await keyResponse.json();
            const subscription = await registration.pushManager.getSubscription()
                || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(publicKey) });
            await storeSubscription(subscription);
            markNotificationsActive();
        } catch (error) {
            notificationButton.textContent = error.message || 'Notifikasi tidak tersedia';
        }
    });
};

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

const initialisePasswordToggles = () => {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        if (! (toggle instanceof HTMLButtonElement)) {
            return;
        }

        const inputId = toggle.getAttribute('aria-controls');
        const input = inputId ? document.getElementById(inputId) : null;
        const label = toggle.querySelector('[data-password-toggle-label]');

        if (! (input instanceof HTMLInputElement) || ! label) {
            return;
        }

        toggle.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(showPassword));
            label.textContent = showPassword ? 'Sembunyikan' : 'Lihat';
        });
    });
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

// Menyatukan Back aplikasi, browser, dan Android/PWA berdasarkan parent struktural
// yang dihasilkan server. Logout hanya boleh dijalankan dari tombol Keluar Akun.
const initialiseHierarchyBackNavigation = () => {
    const body = document.body;
    const reportModal = document.querySelector('[data-report-summary-modal]');
    const rawParentUrl = reportModal?.getAttribute('data-close-url')
        || body.dataset.navigationParent;
    const isRoot = body.dataset.navigationRoot === 'true';

    const oauthHistory = readOauthHistory();
    if (isInstalledPwa() && oauthHistory?.phase === 'normalizing') {
        const destination = new URL(oauthHistory.destination, window.location.href);
        if (destination.origin === window.location.origin && destination.href !== window.location.href) {
            window.sessionStorage.removeItem(oauthHistoryKey);
            window.location.replace(destination.href);
            return;
        }
        window.sessionStorage.removeItem(oauthHistoryKey);
    }

    if (isInstalledPwa() && isRoot && oauthHistory?.phase === 'started') {
        const steps = Math.max(0, window.history.length - Number(oauthHistory.baselineLength || 0));
        if (steps > 0) {
            window.sessionStorage.setItem(oauthHistoryKey, JSON.stringify({
                ...oauthHistory,
                phase: 'normalizing',
                destination: window.location.href,
            }));
            window.history.go(-steps);
            return;
        }
        window.sessionStorage.removeItem(oauthHistoryKey);
    }

    if (! rawParentUrl && ! isRoot) {
        return;
    }

    const currentUrl = window.location.href;
    const current = new URL(currentUrl);
    const defaultParent = rawParentUrl ? new URL(rawParentUrl, currentUrl) : null;
    const storageKey = `smart-abk:navigation-parent:${current.pathname}`;
    const maxContextAge = 2 * 60 * 60 * 1000;
    let parentUrl = defaultParent;

    const readContext = (key, consume = false) => {
        try {
            const raw = window.sessionStorage.getItem(key);
            if (consume) window.sessionStorage.removeItem(key);
            if (! raw) return null;
            const context = JSON.parse(raw);
            if (Date.now() - context.savedAt > maxContextAge || context.targetPath !== current.pathname) return null;
            const candidate = new URL(context.parentUrl, currentUrl);
            if (! defaultParent || candidate.origin !== current.origin || candidate.pathname !== defaultParent.pathname) return null;
            return candidate;
        } catch {
            return null;
        }
    };

    const navigationType = window.performance.getEntriesByType('navigation')[0]?.type;
    const pendingContext = readContext('smart-abk:pending-navigation', true);
    const savedContext = navigationType === 'reload' ? readContext(storageKey) : null;
    if (! pendingContext && navigationType !== 'reload') {
        window.sessionStorage.removeItem(storageKey);
    }
    parentUrl = pendingContext || savedContext || defaultParent;
    if (pendingContext) {
        window.sessionStorage.setItem(storageKey, JSON.stringify({
            targetPath: current.pathname,
            parentUrl: pendingContext.href,
            savedAt: Date.now(),
        }));
    }

    if (parentUrl?.href === currentUrl) {
        parentUrl = defaultParent?.href === currentUrl ? null : defaultParent;
    }

    document.querySelectorAll('.ks-back, .abk-pdf-preview-back, [data-navigation-back]').forEach((link) => {
        if (parentUrl && link instanceof HTMLAnchorElement) link.href = parentUrl.href;
    });

    const trackedForms = [...document.forms].filter((form) => {
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        return method !== 'GET' && ! form.matches('[data-navigation-logout], [data-navigation-ignore-dirty]');
    });
    const serializeForm = (form) => [...form.elements].flatMap((field) => {
        if (! field.name || field.disabled || ['submit', 'button', 'reset'].includes(field.type)) return [];
        if (['checkbox', 'radio'].includes(field.type) && ! field.checked) return [`${field.name}:unchecked`];
        if (field instanceof HTMLInputElement && field.type === 'file') {
            return [...field.files].map((file) => `${field.name}:${file.name}:${file.size}:${file.lastModified}`);
        }
        return [`${field.name}:${field.value}`];
    }).join('|');
    const formBaselines = new Map(trackedForms.map((form) => [form, serializeForm(form)]));
    const hasUnsavedChanges = () => trackedForms.some((form) => serializeForm(form) !== formBaselines.get(form));
    let navigationCommitted = false;

    trackedForms.forEach((form) => form.addEventListener('submit', (event) => {
        window.queueMicrotask(() => {
            if (! event.defaultPrevented) navigationCommitted = true;
        });
    }));

    const confirmLeavingDirtyForm = () => ! hasUnsavedChanges()
        || window.confirm('Perubahan pada formulir belum disimpan. Tinggalkan halaman ini?');

    window.addEventListener('beforeunload', (event) => {
        if (! navigationCommitted && hasUnsavedChanges()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (! (link instanceof HTMLAnchorElement) || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
            || link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-download')) return;

        const target = new URL(link.href, currentUrl);
        if (target.origin !== current.origin || target.href === currentUrl || target.hash && target.pathname === current.pathname && target.search === current.search) return;

        if (! confirmLeavingDirtyForm()) {
            event.preventDefault();
            return;
        }

        navigationCommitted = true;
        window.sessionStorage.setItem('smart-abk:pending-navigation', JSON.stringify({
            targetPath: target.pathname,
            parentUrl: currentUrl,
            savedAt: Date.now(),
        }));
        event.preventDefault();
        window.location.replace(target.href);
    });

    // Dashboard adalah root di browser maupun PWA. Jangan membuat trap atau
    // mengirim form logout: Back native boleh meninggalkan/minimalkan aplikasi
    // dan sesi autentikasi tetap aktif.
    if (isRoot) {
        window.history.replaceState({ smartAbkNavigationRoot: current.pathname }, '', currentUrl);
        return;
    }

    // Satu entry halaman dan satu trap adalah jumlah tetap per dokumen. Semua
    // link internal memakai location.replace(), sehingga trap halaman lama tidak
    // menumpuk saat pengguna berpindah modul atau membuka detail.
    const pageState = { smartAbkNavigationPage: current.pathname };
    const trapState = { smartAbkNavigationTrap: current.pathname };
    const state = window.history.state || {};
    if (state.smartAbkNavigationTrap !== current.pathname) {
        if (state.smartAbkNavigationPage !== current.pathname) {
            window.history.replaceState(pageState, '', currentUrl);
        }
        window.history.pushState(trapState, '', currentUrl);
    }

    window.addEventListener('popstate', () => {
        if (! confirmLeavingDirtyForm()) {
            window.history.pushState(trapState, '', currentUrl);
            return;
        }

        if (parentUrl) {
            navigationCommitted = true;
            window.location.replace(parentUrl.href);
        }
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
        initialisePwaControls();
        initialiseOAuthHistoryIsolation();
        initialisePasswordToggles();
        initialiseReportSummaryModal();
        initialiseMobileSidebar();
        initialiseHierarchyBackNavigation();
        initialiseMasterAccordions();
        initialiseResponsiveTableDisclosures();
    }, { once: true });
} else {
    initialiseConfirmationModal();
    initialisePwaControls();
    initialiseOAuthHistoryIsolation();
    initialisePasswordToggles();
    initialiseReportSummaryModal();
    initialiseMobileSidebar();
    initialiseHierarchyBackNavigation();
    initialiseMasterAccordions();
    initialiseResponsiveTableDisclosures();
}

registerServiceWorker();

// Halaman terlindungi yang dipulihkan dari back-forward cache harus meminta
// dokumen baru agar middleware kembali memeriksa sesi (terutama setelah logout).
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});
