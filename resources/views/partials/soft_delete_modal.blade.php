@php
    $initN1 = rand(4, 16);
    $initN2 = rand(3, 12);
    $initOp = rand(0, 1) === 1 ? '+' : '*';
    $initAns = $initOp === '+' ? ($initN1 + $initN2) : ($initN1 * $initN2);
    $initQuestion = "{$initN1} " . ($initOp === '+' ? '+' : '×') . " {$initN2}";
    $initToken = \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['ans' => $initAns, 'time' => time()]));
@endphp

<!-- Soft Delete Transaction Modal (Admin Only) -->
<div id="softDeleteModal" class="soft-delete-modal-overlay" style="display: none;">
    <div class="soft-delete-modal-card">
        <!-- Modal Header -->
        <div class="soft-delete-modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="soft-delete-icon-badge">
                    ⚠️
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #f87171;">
                        Soft Delete Transaction
                    </h3>
                    <p style="margin: 0.15rem 0 0 0; font-size: 0.8rem; color: var(--text-muted);">
                        Admin Audit Protection Workflow
                    </p>
                </div>
            </div>
            <button type="button" class="soft-delete-close-btn" onclick="closeSoftDeleteModal()">&times;</button>
        </div>

        <!-- Audit Preservation Alert Notice -->
        <div class="soft-delete-alert-banner">
            <div style="font-size: 1rem; line-height: 1;">🛡️</div>
            <div>
                <strong>Database Record Preservation:</strong>
                <span>
                    This transaction will <strong>NOT</strong> be deleted from the database. It will be marked as soft-deleted / voided, excluded from active store revenue totals, and items will be returned to inventory. The transaction remains permanently archived for audit compliance.
                </span>
            </div>
        </div>

        <!-- Transaction Summary Box -->
        <div class="soft-delete-summary-box">
            <div class="summary-col">
                <span class="summary-label">Receipt / Sale ID</span>
                <span class="summary-value" id="sdSaleRef">#0</span>
            </div>
            <div class="summary-col">
                <span class="summary-label">Total Amount</span>
                <span class="summary-value" id="sdSaleAmount" style="color: var(--primary-hover);">{{ isset($baseCurrency) ? $baseCurrency->symbol : '$' }}0.00</span>
            </div>
            <div class="summary-col">
                <span class="summary-label">Customer</span>
                <span class="summary-value" id="sdSaleCustomer">Walk-in</span>
            </div>
            <div class="summary-col">
                <span class="summary-label">Transacted Date</span>
                <span class="summary-value" id="sdSaleDate">-</span>
            </div>
        </div>

        <!-- Submission Form -->
        <form id="softDeleteForm" method="POST" action="" onsubmit="return handleSoftDeleteSubmit(event)">
            @csrf
            <input type="hidden" name="math_challenge_token" id="sdMathToken" value="{{ $initToken }}">

            <!-- Step 1: Why is this transaction being deleted? -->
            <div class="soft-delete-field-group">
                <label for="sdReasonInput" class="soft-delete-field-label">
                    <span>1. Why is this transaction being deleted? <span style="color: #ef4444;">*</span></span>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">(Required for audit trail)</span>
                </label>
                <textarea 
                    name="deletion_reason" 
                    id="sdReasonInput" 
                    rows="3" 
                    class="soft-delete-textarea" 
                    placeholder="Enter the reason (e.g. Wrong items rung up, cashier billing mistake, customer cancelled order, duplicate receipt)..."
                    required
                    oninput="validateSoftDeleteFields()"
                ></textarea>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">
                    This explanation will be permanently recorded in the system audit logs.
                </div>
            </div>

            <!-- Step 2: Math Authenticator -->
            <div class="soft-delete-field-group" style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 8px; padding: 0.9rem;">
                <label class="soft-delete-field-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <span style="color: #a78bfa; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <span>🔐</span> 2. Security Math Authenticator
                    </span>
                    <button type="button" class="math-refresh-btn" onclick="refreshMathChallenge(event)" title="Generate new math challenge">
                        🔄 New Challenge
                    </button>
                </label>
                <p style="margin: 0 0 0.6rem 0; font-size: 0.78rem; color: var(--text-muted);">
                    Solve the equation to verify and authenticate this administrative deletion:
                </p>

                <div class="math-challenge-row">
                    <div class="math-equation-display" id="sdMathEquation">
                        {{ $initQuestion }} = ?
                    </div>
                    <div style="flex: 1;">
                        <input 
                            type="number" 
                            name="math_answer" 
                            id="sdMathAnswerInput" 
                            class="soft-delete-math-input" 
                            placeholder="Enter answer"
                            required
                            autocomplete="off"
                            oninput="validateSoftDeleteFields()"
                        >
                    </div>
                </div>

                <div id="sdMathStatusMessage" class="math-status-msg">
                    <span style="color: var(--text-muted);">Solve the calculation above to unlock approval.</span>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="soft-delete-actions">
                <button type="button" class="btn-cancel-modal" onclick="closeSoftDeleteModal()">
                    Cancel
                </button>
                <button type="submit" id="sdSubmitBtn" class="btn-approve-soft-delete" disabled>
                    <span>🗑️ Approve & Soft Delete</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.soft-delete-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(10, 15, 30, 0.8);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeInModal 0.2s ease-out;
}

@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
}

.soft-delete-modal-card {
    background: var(--card-bg, #1e293b);
    border: 1px solid rgba(239, 68, 68, 0.35);
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.5), 0 0 25px rgba(239, 68, 68, 0.15);
    border-radius: 14px;
    width: 100%;
    max-width: 540px;
    padding: 1.5rem;
    color: var(--text-main, #f8fafc);
    position: relative;
    box-sizing: border-box;
}

.soft-delete-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--card-border, rgba(255, 255, 255, 0.1));
    margin-bottom: 1rem;
}

.soft-delete-icon-badge {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.soft-delete-close-btn {
    background: transparent;
    border: none;
    color: var(--text-muted, #94a3b8);
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.soft-delete-close-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
}

.soft-delete-alert-banner {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 8px;
    padding: 0.75rem 0.9rem;
    margin-bottom: 1.1rem;
    display: flex;
    gap: 0.6rem;
    font-size: 0.8rem;
    line-height: 1.45;
    color: #fde68a;
}

.soft-delete-summary-box {
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.6rem 1rem;
    margin-bottom: 1.2rem;
}

.summary-col {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: var(--text-muted, #94a3b8);
    letter-spacing: 0.5px;
    font-weight: 600;
}

.summary-value {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-main, #f8fafc);
    margin-top: 0.15rem;
}

.soft-delete-field-group {
    margin-bottom: 1.1rem;
}

.soft-delete-field-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.4rem;
    color: var(--text-main, #f8fafc);
}

.soft-delete-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 0.65rem 0.8rem;
    background: var(--input-bg, rgba(15, 23, 42, 0.6));
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.15));
    border-radius: 8px;
    color: var(--text-main, #f8fafc);
    font-size: 0.85rem;
    resize: vertical;
    outline: none;
    transition: border-color 0.2s ease;
    font-family: inherit;
}

.soft-delete-textarea:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
}

.math-challenge-row {
    display: flex;
    gap: 0.6rem;
    align-items: center;
}

.math-equation-display {
    background: rgba(139, 92, 246, 0.15);
    border: 1px solid rgba(139, 92, 246, 0.35);
    color: #c4b5fd;
    font-size: 1.15rem;
    font-weight: 800;
    padding: 0.55rem 0.9rem;
    border-radius: 8px;
    letter-spacing: 1px;
    white-space: nowrap;
    min-width: 110px;
    text-align: center;
    user-select: none;
}

.soft-delete-math-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.65rem 0.8rem;
    background: var(--input-bg, rgba(15, 23, 42, 0.6));
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.15));
    border-radius: 8px;
    color: var(--text-main, #f8fafc);
    font-size: 1rem;
    font-weight: 700;
    outline: none;
    transition: all 0.2s ease;
}

.soft-delete-math-input:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
}

.math-refresh-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-muted, #94a3b8);
    font-size: 0.72rem;
    padding: 0.2rem 0.5rem;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.math-refresh-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.math-status-msg {
    margin-top: 0.4rem;
    font-size: 0.78rem;
    min-height: 18px;
}

.soft-delete-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--card-border, rgba(255, 255, 255, 0.1));
}

.btn-cancel-modal {
    background: transparent;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.15));
    color: var(--text-muted, #94a3b8);
    padding: 0.6rem 1.1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel-modal:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}

.btn-approve-soft-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    color: #fff;
    padding: 0.6rem 1.3rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-approve-soft-delete:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    box-shadow: none;
    filter: grayscale(0.5);
}

.btn-approve-soft-delete:not(:disabled):hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
}
</style>

<script>
let currentExpectedAnswer = {{ $initAns }};

function openSoftDeleteModal(saleId, amountFormatted, customerName, dateFormatted) {
    const modal = document.getElementById('softDeleteModal');
    if (!modal) return;

    document.getElementById('sdSaleRef').textContent = '#' + saleId;
    document.getElementById('sdSaleAmount').textContent = amountFormatted;
    document.getElementById('sdSaleCustomer').textContent = customerName || 'Walk-in Customer';
    document.getElementById('sdSaleDate').textContent = dateFormatted || 'N/A';

    // Set form action
    const form = document.getElementById('softDeleteForm');
    form.action = "{{ url('/sales') }}/" + saleId + "/soft-delete";

    // Reset input fields
    document.getElementById('sdReasonInput').value = '';
    document.getElementById('sdMathAnswerInput').value = '';

    // Fetch fresh challenge
    refreshMathChallenge();

    modal.style.display = 'flex';
    setTimeout(() => {
        document.getElementById('sdReasonInput').focus();
    }, 100);
}

function closeSoftDeleteModal() {
    const modal = document.getElementById('softDeleteModal');
    if (modal) modal.style.display = 'none';
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSoftDeleteModal();
    }
});

// Close when clicking modal backdrop
document.addEventListener('click', function(e) {
    const modal = document.getElementById('softDeleteModal');
    if (modal && e.target === modal) {
        closeSoftDeleteModal();
    }
});

function refreshMathChallenge(e) {
    if (e) e.preventDefault();

    const eqDisplay = document.getElementById('sdMathEquation');
    const tokenInput = document.getElementById('sdMathToken');
    const answerInput = document.getElementById('sdMathAnswerInput');
    const statusMsg = document.getElementById('sdMathStatusMessage');

    eqDisplay.textContent = '...';

    fetch("{{ route('sales.math-challenge') }}", {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            eqDisplay.textContent = data.question + ' = ?';
            tokenInput.value = data.token;
            currentExpectedAnswer = parseInt(data.expected, 10);
            answerInput.value = '';
            validateSoftDeleteFields();
        }
    })
    .catch(err => {
        // Fallback local challenge if offline
        const n1 = Math.floor(Math.random() * 12) + 3;
        const n2 = Math.floor(Math.random() * 10) + 2;
        currentExpectedAnswer = n1 + n2;
        eqDisplay.textContent = `${n1} + ${n2} = ?`;
        validateSoftDeleteFields();
    });
}

function validateSoftDeleteFields() {
    const reason = document.getElementById('sdReasonInput').value.trim();
    const answerVal = document.getElementById('sdMathAnswerInput').value.trim();
    const statusMsg = document.getElementById('sdMathStatusMessage');
    const submitBtn = document.getElementById('sdSubmitBtn');

    let mathValid = false;

    if (answerVal === '') {
        statusMsg.innerHTML = '<span style="color: var(--text-muted);">Enter calculation result to authenticate.</span>';
    } else if (parseInt(answerVal, 10) === currentExpectedAnswer) {
        mathValid = true;
        statusMsg.innerHTML = '<span style="color: #34d399; font-weight: 600;">✅ Math Authenticated! Calculation correct.</span>';
    } else {
        mathValid = false;
        statusMsg.innerHTML = '<span style="color: #f87171; font-weight: 600;">❌ Incorrect calculation. Check your math.</span>';
    }

    const reasonValid = reason.length >= 3;

    if (mathValid && reasonValid) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

function handleSoftDeleteSubmit(e) {
    const reason = document.getElementById('sdReasonInput').value.trim();
    const answerVal = document.getElementById('sdMathAnswerInput').value.trim();

    if (reason.length < 3) {
        alert('Please provide an audit explanation of why this transaction is being deleted (minimum 3 characters).');
        e.preventDefault();
        return false;
    }

    if (parseInt(answerVal, 10) !== currentExpectedAnswer) {
        alert('Math Authenticator verification failed. Please enter the correct calculation answer.');
        e.preventDefault();
        return false;
    }

    // Confirmation safety
    return confirm('Confirm soft deletion of this transaction? The record will be preserved in database logs with reason.');
}
</script>
