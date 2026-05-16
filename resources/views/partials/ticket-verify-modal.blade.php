@php
    $tvUsine = isset($verifyTicketUsineId) && $verifyTicketUsineId !== '' && $verifyTicketUsineId !== null
        ? (int) $verifyTicketUsineId
        : null;
@endphp
{{-- Modal : nombre de tickets puis champs avec vérification API (id usine optionnel) --}}
<div
    id="ticket-verify-modal"
    class="modal fade"
    tabindex="-1"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-verify-url="{{ route('api.tickets.verify') }}"
    data-store-url="{{ route('api.tickets.store') }}"
    data-csrf-token="{{ csrf_token() }}"
    data-verify-usine-id="{{ $tvUsine !== null ? $tvUsine : '' }}"
>
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticket-verify-modal-title">Vérifier des tickets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="tv-step-count">
                    <p class="text-muted small mb-3">Indiquez combien de tickets vous souhaitez vérifier.</p>
                    <label class="form-label" for="tv-ticket-count">Nombre de tickets</label>
                    <input
                        type="number"
                        class="form-control"
                        id="tv-ticket-count"
                        min="1"
                        max="50"
                        value="1"
                    />
                    <p id="tv-count-error" class="text-danger small mt-2 d-none" role="alert"></p>
                </div>
                <div id="tv-step-fields" class="d-none">
                    <p class="text-muted small mb-3">Saisissez les numéros ou identifiants des tickets.</p>
                    <div id="tv-fields-container" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <div class="ms-auto d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary d-none" id="tv-btn-back">Modifier le nombre</button>
                    <button type="button" class="btn btn-primary" id="tv-btn-next">Valider</button>
                    <button type="button" class="btn btn-primary d-none" id="tv-btn-submit">Enregistrer les informations</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal détails ticket (après vérification OK) --}}
<div
    id="ticket-detail-modal"
    class="modal fade"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white border-0 py-3" style="background-color: #3b4253">
                <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="ticket-detail-modal-title">
                    <i class="bx bx-ticket fs-4"></i>
                    <span id="td-title-text">Détails du ticket</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-4" id="ticket-detail-modal-body"></div>
            <div class="modal-footer border-top-0 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary ms-auto" id="td-btn-verify">Vérifier</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal retour utilisateur (succès / erreur) — remplace alert() --}}
<div id="tv-feedback-modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 420px">
        <div class="modal-content border-0 shadow-lg">
            <div
                class="modal-header text-white border-0 py-3 rounded-top tv-feedback-header bg-success"
                id="tv-feedback-header"
            >
                <div class="d-flex align-items-center gap-3 w-100">
                    <div
                        class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 tv-feedback-icon-wrap"
                        style="width: 3rem; height: 3rem; background: rgba(255, 255, 255, 0.2)"
                    >
                        <i class="bx tv-feedback-icon fs-2 text-white bx-check-circle" aria-hidden="true"></i>
                    </div>
                    <h5 class="modal-title mb-0 flex-grow-1 fw-semibold" id="tv-feedback-title">Succès</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <p class="mb-0 text-body lh-lg" id="tv-feedback-message"></p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-primary w-100 py-2 fw-medium" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
        <script>
            (function () {
                const MAX = 50;
                const modalEl = document.getElementById('ticket-verify-modal');
                if (!modalEl || typeof bootstrap === 'undefined') return;

                const verifyUrl = modalEl.getAttribute('data-verify-url');
                const storeUrl = modalEl.getAttribute('data-store-url');
                const csrfToken = modalEl.getAttribute('data-csrf-token');
                const usineIdRaw = modalEl.getAttribute('data-verify-usine-id');
                const usineId = usineIdRaw && String(usineIdRaw).trim() !== '' ? String(usineIdRaw).trim() : '';

                const modal = new bootstrap.Modal(modalEl);
                const stepCount = document.getElementById('tv-step-count');
                const stepFields = document.getElementById('tv-step-fields');
                const countInput = document.getElementById('tv-ticket-count');
                const countError = document.getElementById('tv-count-error');
                const fieldsBox = document.getElementById('tv-fields-container');
                const btnNext = document.getElementById('tv-btn-next');
                const btnBack = document.getElementById('tv-btn-back');
                const btnSubmit = document.getElementById('tv-btn-submit');

                const debouncers = new WeakMap();

                const detailModalEl = document.getElementById('ticket-detail-modal');
                let detailModal = null;
                if (detailModalEl) {
                    detailModal = new bootstrap.Modal(detailModalEl);
                }

                let pendingDetailTicket = null;
                let detailReturnRow = null;
                let reopenVerifyAfterDetail = false;

                const feedbackModalEl = document.getElementById('tv-feedback-modal');
                let feedbackModalBs = null;
                if (feedbackModalEl) {
                    feedbackModalBs = new bootstrap.Modal(feedbackModalEl);
                }

                /**
                 * @param {'success'|'danger'|'warning'} variant
                 * @param {string} title
                 * @param {string} message
                 * @param {function(): void} [onHidden]
                 */
                function showFeedbackModal(variant, title, message, onHidden) {
                    if (!feedbackModalEl || !feedbackModalBs) {
                        window.alert(message);
                        if (typeof onHidden === 'function') {
                            onHidden();
                        }
                        return;
                    }
                    var hdr = document.getElementById('tv-feedback-header');
                    var icon = feedbackModalEl.querySelector('.tv-feedback-icon');
                    var titleEl = document.getElementById('tv-feedback-title');
                    var msgEl = document.getElementById('tv-feedback-message');
                    var closeBtn = hdr ? hdr.querySelector('.btn-close') : null;

                    if (hdr) {
                        hdr.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white', 'text-dark');
                    }
                    if (titleEl) {
                        titleEl.classList.remove('text-dark');
                    }
                    if (icon) {
                        icon.classList.remove(
                            'bx-check-circle',
                            'bx-error-circle',
                            'bx-info-circle',
                            'text-white',
                            'text-dark'
                        );
                    }
                    if (closeBtn) {
                        closeBtn.classList.remove('btn-close-white');
                    }

                    if (variant === 'success') {
                        if (hdr) hdr.classList.add('bg-success', 'text-white');
                        if (icon) icon.classList.add('bx-check-circle', 'text-white');
                        if (closeBtn) closeBtn.classList.add('btn-close-white');
                    } else if (variant === 'warning') {
                        if (hdr) hdr.classList.add('bg-warning', 'text-dark');
                        if (titleEl) titleEl.classList.add('text-dark');
                        if (icon) icon.classList.add('bx-info-circle', 'text-dark');
                    } else {
                        if (hdr) hdr.classList.add('bg-danger', 'text-white');
                        if (icon) icon.classList.add('bx-error-circle', 'text-white');
                        if (closeBtn) closeBtn.classList.add('btn-close-white');
                    }

                    if (titleEl) titleEl.textContent = title;
                    if (msgEl) msgEl.textContent = message;

                    if (typeof onHidden === 'function') {
                        feedbackModalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
                    }

                    feedbackModalBs.show();
                }

                function esc(s) {
                    return String(s == null ? '' : s)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                function nz(v) {
                    if (v == null || String(v).trim() === '' || String(v) === 'null') return '—';
                    return String(v);
                }

                function fmtDate(v) {
                    if (!v) return '—';
                    var d = String(v).split(' ')[0].split('T')[0];
                    var p = d.split('-');
                    if (p.length !== 3) return esc(String(v));
                    return esc(p[2] + '/' + p[1] + '/' + p[0]);
                }

                function fmtDateTime(v) {
                    if (!v) return '—';
                    var s = String(v).replace('T', ' ');
                    var datePart = s.split(' ')[0];
                    var timePart = (s.split(' ')[1] || '').slice(0, 5);
                    var p = datePart.split('-');
                    if (p.length !== 3) return esc(s);
                    var out = p[2] + '/' + p[1] + '/' + p[0];
                    if (timePart) out += ' ' + timePart;
                    return esc(out);
                }

                function fmtNum(v) {
                    if (v == null || v === '' || String(v).trim() === 'null') return '—';
                    var n = parseFloat(String(v).replace(',', '.'));
                    if (isNaN(n)) return esc(String(v));
                    return esc(
                        n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    );
                }

                function fmtDashMoney(v) {
                    if (v == null || v === '' || String(v).trim() === 'null') return '–';
                    var n = parseFloat(String(v).replace(',', '.'));
                    if (isNaN(n) || n === 0) return '–';
                    return esc(
                        n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    );
                }

                function fmtPoids(v) {
                    if (v == null || v === '' || String(v) === 'null') return '—';
                    var n = parseFloat(String(v).replace(',', '.'));
                    if (isNaN(n)) return esc(String(v)) + ' kg';
                    return esc(String(n)) + ' kg';
                }

                function joinAgent(t) {
                    var a = [t.agent_nom, t.agent_prenom]
                        .filter(function (x) {
                            return x && String(x).trim();
                        })
                        .join(' ')
                        .trim();
                    return a ? esc(a) : '—';
                }

                function vehLabel(t) {
                    if (t.matricule_vehicule != null && String(t.matricule_vehicule).trim() !== '') {
                        return esc(String(t.matricule_vehicule));
                    }
                    if (t.type_vehicule != null && String(t.type_vehicule).trim() !== '') {
                        return esc(String(t.type_vehicule));
                    }
                    return '—';
                }

                function fmtDatePlain(v) {
                    if (!v) return '—';
                    var d = String(v).split(' ')[0].split('T')[0];
                    var p = d.split('-');
                    if (p.length !== 3) return String(v);
                    return p[2] + '/' + p[1] + '/' + p[0];
                }

                function nzPlain(v) {
                    if (v == null || String(v).trim() === '' || String(v) === 'null') return '—';
                    return String(v);
                }

                function joinAgentPlain(t) {
                    var a = [t.agent_nom, t.agent_prenom]
                        .filter(function (x) {
                            return x && String(x).trim();
                        })
                        .join(' ')
                        .trim();
                    return a || '—';
                }

                function vehPlain(t) {
                    if (t.matricule_vehicule != null && String(t.matricule_vehicule).trim() !== '') {
                        return String(t.matricule_vehicule);
                    }
                    if (t.type_vehicule != null && String(t.type_vehicule).trim() !== '') {
                        return String(t.type_vehicule);
                    }
                    return '—';
                }

                function fmtPoidsPlain(v) {
                    if (v == null || v === '' || String(v) === 'null') return '—';
                    var n = parseFloat(String(v).replace(',', '.'));
                    if (isNaN(n)) return String(v) + ' kg';
                    return String(n) + ' kg';
                }

                function fmtNumPlain(v) {
                    if (v == null || v === '' || String(v).trim() === 'null') return '—';
                    var n = parseFloat(String(v).replace(',', '.'));
                    if (isNaN(n)) return String(v);
                    return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                function clearRowTicket(row) {
                    row.tvTicketPayload = null;
                    var box = row.querySelector('.tv-ticket-summary');
                    if (box) box.classList.add('d-none');
                }

                function attachTicketToRow(row, ticket) {
                    row.tvTicketPayload = ticket;
                    var box = row.querySelector('.tv-ticket-summary');
                    if (!box) return;
                    var d = box.querySelector('.tv-sum-date');
                    if (d) d.textContent = fmtDatePlain(ticket.date_ticket);
                    var u = box.querySelector('.tv-sum-usine');
                    if (u) u.textContent = nzPlain(ticket.nom_usine);
                    var ag = box.querySelector('.tv-sum-agent');
                    if (ag) ag.textContent = joinAgentPlain(ticket);
                    var vh = box.querySelector('.tv-sum-veh');
                    if (vh) vh.textContent = vehPlain(ticket);
                    var po = box.querySelector('.tv-sum-poids');
                    if (po) po.textContent = fmtPoidsPlain(ticket.poids);
                    var pr = box.querySelector('.tv-sum-prix');
                    if (pr) pr.textContent = fmtNumPlain(ticket.prix_unitaire);
                    box.classList.remove('d-none');
                }

                function showTicketDetail(t) {
                    if (!detailModal || !detailModalEl) return;
                    var titleEl = document.getElementById('td-title-text');
                    if (titleEl) {
                        var n = t.numero_ticket != null && String(t.numero_ticket).trim() !== '' ? String(t.numero_ticket) : '';
                        titleEl.textContent = 'Détails du Ticket #' + n;
                    }

                    var veh = vehLabel(t);

                    var cree =
                        t.cree_par ||
                        t.createur_nom ||
                        t.utilisateur_nom ||
                        (t.id_utilisateur != null ? 'Utilisateur #' + t.id_utilisateur : null);
                    var creeTxt = cree ? esc(String(cree)) : '—';

                    var left =
                        '<div class="col-md-6">' +
                        '<div class="mb-3"><strong>Date du ticket :</strong><br><span>' +
                        fmtDate(t.date_ticket) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Usine :</strong><br><span>' +
                        esc(nz(t.nom_usine)) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Agent :</strong><br><span>' +
                        joinAgent(t) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Véhicule :</strong><br><span>' +
                        veh +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Poids ticket :</strong><br><span>' +
                        fmtPoids(t.poids) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Créé par :</strong><br><span>' +
                        creeTxt +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Date de création :</strong><br><span>' +
                        fmtDateTime(t.created_at) +
                        '</span></div>' +
                        '</div>';

                    var right =
                        '<div class="col-md-6">' +
                        '<div class="mb-3"><strong>Prix unitaire :</strong><br><span>' +
                        fmtNum(t.prix_unitaire) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Montant à payer :</strong><br><span class="text-primary fw-semibold">' +
                        fmtNum(t.montant_paie) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Montant payé :</strong><br><span>' +
                        fmtDashMoney(t.montant_payer) +
                        '</span></div>' +
                        '<div class="mb-3"><strong>Reste à payer :</strong><br><span>' +
                        fmtDashMoney(t.montant_reste) +
                        '</span></div>' +
                        '</div>';

                    var body = document.getElementById('ticket-detail-modal-body');
                    if (body) body.innerHTML = '<div class="row">' + left + right + '</div>';

                    detailModal.show();
                }

                function showErr(msg) {
                    countError.textContent = msg;
                    countError.classList.remove('d-none');
                }
                function hideErr() {
                    countError.classList.add('d-none');
                    countError.textContent = '';
                }

                function markRowIntrouvable(row, message) {
                    if (!row) return;
                    const icon = row.querySelector('.tv-ticket-icon');
                    const fb = row.querySelector('.tv-ticket-feedback');
                    const inp = row.querySelector('.tv-ticket-input');
                    row.classList.add('tv-introuvable-recorded');
                    row.classList.remove('tv-user-verified');
                    clearRowTicket(row);
                    if (icon) {
                        icon.classList.remove('d-none', 'bg-label-secondary', 'bg-label-success', 'bg-label-danger');
                        icon.classList.add('bg-label-warning');
                        icon.innerHTML = '<i class="bx bx-error-circle text-warning fs-4"></i>';
                    }
                    if (inp) {
                        inp.classList.remove('is-valid', 'is-invalid');
                    }
                    if (fb) {
                        fb.classList.remove('text-danger', 'text-success');
                        fb.classList.add('text-warning');
                        fb.textContent = message || 'Ticket introuvable — enregistré pour suivi.';
                    }
                }

                function resetRowUI(row) {
                    const icon = row.querySelector('.tv-ticket-icon');
                    const fb = row.querySelector('.tv-ticket-feedback');
                    const inp = row.querySelector('.tv-ticket-input');
                    row.classList.remove('tv-user-verified', 'tv-introuvable-recorded');
                    clearRowTicket(row);
                    if (icon) {
                        icon.classList.add('d-none');
                        icon.innerHTML = '';
                        icon.classList.remove('bg-label-success', 'bg-label-danger', 'bg-label-secondary');
                    }
                    if (fb) {
                        fb.textContent = '';
                        fb.classList.remove('text-success');
                        fb.classList.add('text-danger');
                    }
                    if (inp) {
                        inp.classList.remove('is-valid', 'is-invalid');
                    }
                }

                function resetSteps() {
                    hideErr();
                    stepCount.classList.remove('d-none');
                    stepFields.classList.add('d-none');
                    fieldsBox.innerHTML = '';
                    btnNext.classList.remove('d-none');
                    btnBack.classList.add('d-none');
                    btnSubmit.classList.add('d-none');
                }

                function setRowLoading(row, loading) {
                    const icon = row.querySelector('.tv-ticket-icon');
                    if (!icon) return;
                    if (loading) {
                        icon.classList.remove('d-none', 'bg-label-success', 'bg-label-danger');
                        icon.classList.add('bg-label-secondary');
                        icon.innerHTML = '<i class="bx bx-loader-alt bx-spin text-primary"></i>';
                    }
                }

                function setRowResult(row, ok, message) {
                    const icon = row.querySelector('.tv-ticket-icon');
                    const fb = row.querySelector('.tv-ticket-feedback');
                    const inp = row.querySelector('.tv-ticket-input');
                    if (!icon || !inp) return;
                    icon.classList.remove('d-none', 'bg-label-secondary', 'bg-label-success', 'bg-label-danger');
                    inp.classList.remove('is-valid', 'is-invalid');
                    if (ok) {
                        icon.classList.add('bg-label-success');
                        icon.innerHTML = '<i class="bx bx-check text-success fs-4"></i>';
                        if (fb) fb.textContent = '';
                        inp.classList.add('is-valid');
                    } else {
                        clearRowTicket(row);
                        icon.classList.add('bg-label-danger');
                        icon.innerHTML = '<i class="bx bx-x text-danger fs-4"></i>';
                        if (fb) fb.textContent = message || 'Ticket introuvable.';
                        inp.classList.add('is-invalid');
                    }
                }

                function markUserVerified(row) {
                    if (!row) return;
                    row.classList.add('tv-user-verified');
                    const fb = row.querySelector('.tv-ticket-feedback');
                    if (fb) {
                        fb.classList.remove('text-danger');
                        fb.classList.add('text-success');
                        fb.textContent = 'Vérification confirmée.';
                    }
                }

                function verifyInput(inputEl) {
                    const row = inputEl.closest('.tv-ticket-row');
                    if (!row || !verifyUrl) return;

                    const val = String(inputEl.value || '').trim();
                    if (val.length < 2) {
                        resetRowUI(row);
                        return;
                    }

                    setRowLoading(row, true);
                    const q = new URLSearchParams();
                    q.set('numero', val);
                    if (usineId) q.set('id_usine', usineId);

                    fetch(verifyUrl + '?' + q.toString(), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) {
                            return r.text().then(function (text) {
                                var j = {};
                                try {
                                    j = JSON.parse(text);
                                } catch (e) {}
                                return { ok: r.ok, body: j };
                            });
                        })
                        .then(function (res) {
                            var body = res.body || {};
                            if (res.ok && body.valid === true) {
                                setRowResult(row, true, '');
                                if (body.ticket) {
                                    attachTicketToRow(row, body.ticket);
                                }
                                if (body.ticket && detailModal) {
                                    pendingDetailTicket = body.ticket;
                                    detailReturnRow = row;
                                    reopenVerifyAfterDetail = true;
                                    modal.hide();
                                }
                            } else {
                                var msg =
                                    body.message ||
                                    body.error ||
                                    (body.errors && body.errors.numero && body.errors.numero[0]) ||
                                    'Ticket introuvable.';
                                if (body.reason === 'not_found' && body.recorded) {
                                    markRowIntrouvable(row, msg);
                                    showFeedbackModal(
                                        'warning',
                                        'Ticket introuvable',
                                        msg || 'Ticket introuvable — enregistré pour suivi.'
                                    );
                                } else {
                                    setRowResult(row, false, msg);
                                    if (body.reason === 'already_verified') {
                                        showFeedbackModal(
                                            'warning',
                                            'Ticket déjà vérifié',
                                            msg || 'Ce ticket a déjà été vérifié.'
                                        );
                                    } else if (body.reason === 'already_reported_introuvable') {
                                        markRowIntrouvable(row, msg);
                                        showFeedbackModal(
                                            'warning',
                                            'Déjà signalé introuvable',
                                            msg || 'Ce numéro a déjà été signalé comme introuvable.'
                                        );
                                    }
                                }
                            }
                        })
                        .catch(function () {
                            setRowResult(row, false, 'Erreur réseau.');
                        });
                }

                function buildFields(n) {
                    fieldsBox.innerHTML = '';
                    for (let i = 1; i <= n; i++) {
                        const row = document.createElement('div');
                        row.className = 'tv-ticket-row';
                        row.innerHTML =
                            '<label class="form-label mb-1" for="tv-ticket-' +
                            i +
                            '">Ticket ' +
                            i +
                            '</label>' +
                            '<div class="input-group">' +
                            '<input type="text" class="form-control tv-ticket-input" id="tv-ticket-' +
                            i +
                            '" name="tickets[]" autocomplete="off" placeholder="Numéro ou identifiant" />' +
                            '<span class="input-group-text tv-ticket-icon d-none px-3" style="min-width:3rem;justify-content:center;" aria-hidden="true"></span>' +
                            '</div>' +
                            '<div class="small tv-ticket-feedback text-danger mt-1"></div>' +
                            '<div class="tv-ticket-summary d-none mt-2 p-2 rounded border bg-label-secondary bg-opacity-10 small">' +
                            '<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-2">' +
                            '<div><strong>Date du ticket :</strong> <span class="tv-sum-date"></span></div>' +
                            '<div><strong>Usine :</strong> <span class="tv-sum-usine"></span></div>' +
                            '<div><strong>Agent :</strong> <span class="tv-sum-agent"></span></div>' +
                            '<div><strong>Véhicule :</strong> <span class="tv-sum-veh"></span></div>' +
                            '<div><strong>Poids ticket :</strong> <span class="tv-sum-poids"></span></div>' +
                            '<div><strong>Prix unitaire :</strong> <span class="tv-sum-prix"></span></div>' +
                            '</div></div>';
                        fieldsBox.appendChild(row);
                    }
                }

                fieldsBox.addEventListener('input', function (e) {
                    const el = e.target;
                    if (!el.classList.contains('tv-ticket-input')) return;
                    const prev = debouncers.get(el);
                    if (prev) clearTimeout(prev);
                    const row = el.closest('.tv-ticket-row');
                    if (row) resetRowUI(row);
                    const t = setTimeout(function () {
                        verifyInput(el);
                    }, 450);
                    debouncers.set(el, t);
                });

                document.querySelectorAll('.js-open-ticket-verify-modal').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        resetSteps();
                        modal.show();
                        setTimeout(function () {
                            countInput.focus();
                        }, 300);
                    });
                });

                modalEl.addEventListener('hidden.bs.modal', function () {
                    if (pendingDetailTicket && detailModal) {
                        var t = pendingDetailTicket;
                        pendingDetailTicket = null;
                        showTicketDetail(t);
                        return;
                    }
                    resetSteps();
                });

                if (detailModalEl) {
                    detailModalEl.addEventListener('hidden.bs.modal', function () {
                        if (reopenVerifyAfterDetail) {
                            reopenVerifyAfterDetail = false;
                            modal.show();
                        }
                        detailReturnRow = null;
                    });

                    var tdBtnVerify = document.getElementById('td-btn-verify');
                    if (tdBtnVerify) {
                        tdBtnVerify.addEventListener('click', function () {
                            markUserVerified(detailReturnRow);
                            detailReturnRow = null;
                            if (detailModal) detailModal.hide();
                        });
                    }
                }

                btnNext.addEventListener('click', function () {
                    hideErr();
                    const raw = parseInt(String(countInput.value).trim(), 10);
                    if (isNaN(raw) || raw < 1) {
                        showErr('Entrez un nombre entier ≥ 1.');
                        return;
                    }
                    if (raw > MAX) {
                        showErr('Maximum : ' + MAX + ' tickets.');
                        return;
                    }
                    buildFields(raw);
                    stepCount.classList.add('d-none');
                    stepFields.classList.remove('d-none');
                    btnNext.classList.add('d-none');
                    btnBack.classList.remove('d-none');
                    btnSubmit.classList.remove('d-none');
                    const first = fieldsBox.querySelector('input');
                    if (first) first.focus();
                });

                btnBack.addEventListener('click', function () {
                    stepFields.classList.add('d-none');
                    stepCount.classList.remove('d-none');
                    fieldsBox.innerHTML = '';
                    btnNext.classList.remove('d-none');
                    btnBack.classList.add('d-none');
                    btnSubmit.classList.add('d-none');
                    countInput.focus();
                });

                btnSubmit.addEventListener('click', function () {
                    const inputs = fieldsBox.querySelectorAll('input[name="tickets[]"]');
                    const vals = Array.from(inputs).map(function (i) {
                        return i.value.trim();
                    });
                    if (vals.some(function (v) {
                        return v === '';
                    })) {
                        showFeedbackModal('warning', 'Champs incomplets', 'Veuillez remplir tous les champs.');
                        return;
                    }
                    const invalid = Array.from(inputs).filter(function (i) {
                        const r = i.closest('.tv-ticket-row');
                        return i.classList.contains('is-invalid') && !(r && r.classList.contains('tv-introuvable-recorded'));
                    });
                    if (invalid.length) {
                        showFeedbackModal(
                            'warning',
                            'Tickets invalides',
                            'Corrigez les tickets invalides avant d’enregistrer.'
                        );
                        return;
                    }
                    const pending = Array.from(inputs).filter(function (i) {
                        const r = i.closest('.tv-ticket-row');
                        if (r && r.classList.contains('tv-introuvable-recorded')) return false;
                        const v = i.value.trim();
                        return v.length >= 2 && !i.classList.contains('is-valid');
                    });
                    if (pending.length) {
                        showFeedbackModal(
                            'warning',
                            'Vérification en cours',
                            'Veuillez attendre la fin de la vérification pour chaque numéro avant d’enregistrer.'
                        );
                        return;
                    }
                    const ticketRows = fieldsBox.querySelectorAll('.tv-ticket-row');
                    const payloads = [];
                    var missingData = false;
                    ticketRows.forEach(function (r) {
                        if (r.classList.contains('tv-introuvable-recorded')) return;
                        var inp = r.querySelector('.tv-ticket-input');
                        if (!inp || !inp.value.trim()) return;
                        if (!r.tvTicketPayload) {
                            missingData = true;
                            return;
                        }
                        payloads.push(r.tvTicketPayload);
                    });
                    if (missingData) {
                        showFeedbackModal(
                            'warning',
                            'Données incomplètes',
                            'Données ticket incomplètes. Vérifiez que chaque numéro a bien été validé par l’API.'
                        );
                        return;
                    }
                    var valsToSave = vals.filter(function (v, idx) {
                        var row = ticketRows[idx];
                        return v && row && !row.classList.contains('tv-introuvable-recorded');
                    });
                    if (payloads.length !== valsToSave.length) {
                        showFeedbackModal(
                            'warning',
                            'Enregistrement impossible',
                            'Chaque ligne doit avoir des données vérifiées avant l’enregistrement.'
                        );
                        return;
                    }
                    if (payloads.length === 0) {
                        showFeedbackModal(
                            'warning',
                            'Aucun ticket à enregistrer',
                            'Les tickets introuvables ont déjà été enregistrés pour suivi. Aucun ticket valide à enregistrer en local.'
                        );
                        return;
                    }
                    if (!storeUrl || !csrfToken) {
                        showFeedbackModal(
                            'danger',
                            'Configuration',
                            'Enregistrement indisponible (configuration serveur).'
                        );
                        return;
                    }
                    var prevLabel = btnSubmit.textContent;
                    btnSubmit.disabled = true;
                    btnSubmit.textContent = 'Enregistrement…';
                    fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ tickets: payloads }),
                    })
                        .then(function (r) {
                            return r.text().then(function (text) {
                                var j = {};
                                try {
                                    j = JSON.parse(text);
                                } catch (e) {}
                                return { ok: r.ok, body: j };
                            });
                        })
                        .then(function (res) {
                            btnSubmit.disabled = false;
                            btnSubmit.textContent = prevLabel;
                            if (res.ok && res.body && res.body.ok) {
                                showFeedbackModal(
                                    'success',
                                    'Enregistrement réussi',
                                    res.body.message || 'Les tickets ont été enregistrés en local.',
                                    function () {
                                        modal.hide();
                                    }
                                );
                                return;
                            }
                            var msg =
                                (res.body && res.body.message) ||
                                (res.body && res.body.errors && JSON.stringify(res.body.errors)) ||
                                'Erreur lors de l’enregistrement.';
                            var title =
                                res.body && res.body.reason === 'already_verified'
                                    ? 'Ticket déjà vérifié'
                                    : 'Erreur';
                            var variant =
                                res.body && res.body.reason === 'already_verified' ? 'warning' : 'danger';
                            showFeedbackModal(variant, title, msg);
                        })
                        .catch(function () {
                            btnSubmit.disabled = false;
                            btnSubmit.textContent = prevLabel;
                            showFeedbackModal('danger', 'Réseau', 'Erreur réseau. Réessayez dans un instant.');
                        });
                });
            })();
        </script>
@endpush
