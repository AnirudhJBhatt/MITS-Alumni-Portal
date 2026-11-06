/**
 * MITS Alumni Portal - Networking & Connections Hub JS
 */

/**
 * Send Connection Request
 */
async function sendConnect(targetId, btn) {
    if (!btn) return;
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';

    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', target_user_id: targetId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('success', data.message);
            btn.outerHTML = '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 small"><i class="bi bi-clock-history me-1"></i>Request Sent</span>';
        } else {
            showToast('danger', data.message || 'Could not send request.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        console.error(err);
        showToast('danger', 'Network error sending connection request.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * Accept an Incoming Connection Request
 */
async function acceptConnection(connectionId, cardId) {
    const card = document.getElementById(cardId);
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'accept', connection_id: connectionId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('success', data.message);
            if (card) {
                card.classList.add('bg-success-subtle', 'border-success');
                card.innerHTML = `
                    <div class="p-3 text-center text-success fw-semibold">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>Connected!
                    </div>
                `;
                setTimeout(() => {
                    card.remove();
                    updateBadgeCounters();
                }, 1500);
            }
        } else {
            showToast('danger', data.message || 'Failed to accept connection.');
        }
    } catch (err) {
        showToast('danger', 'Error processing request.');
    }
}

/**
 * Decline / Reject an Incoming Connection Request
 */
async function rejectConnection(connectionId, cardId) {
    const card = document.getElementById(cardId);
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reject', connection_id: connectionId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('info', 'Connection request declined.');
            if (card) {
                card.remove();
                updateBadgeCounters();
            }
        } else {
            showToast('danger', data.message || 'Failed to decline request.');
        }
    } catch (err) {
        showToast('danger', 'Error declining request.');
    }
}

/**
 * Cancel / Withdraw a Sent Connection Request
 */
async function withdrawConnection(connectionId, cardId) {
    const card = document.getElementById(cardId);
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'withdraw', connection_id: connectionId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('info', 'Request cancelled.');
            if (card) {
                card.remove();
                updateBadgeCounters();
            }
        } else {
            showToast('danger', data.message || 'Failed to cancel request.');
        }
    } catch (err) {
        showToast('danger', 'Error cancelling request.');
    }
}

/**
 * Remove / Disconnect from a Connection
 */
async function removeConnection(connectionId, userName, cardId) {
    if (!confirm('Are you sure you want to remove ' + userName + ' from your connections?')) {
        return;
    }

    const card = document.getElementById(cardId);
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', connection_id: connectionId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('info', 'Disconnected from ' + userName);
            if (card) {
                card.remove();
                updateBadgeCounters();
            }
        } else {
            showToast('danger', data.message || 'Could not remove connection.');
        }
    } catch (err) {
        showToast('danger', 'Error removing connection.');
    }
}

/**
 * Helper to update tab counters dynamically
 */
function updateBadgeCounters() {
    const receivedList = document.querySelectorAll('#invitations-container .connection-item');
    const badgeReceived = document.getElementById('badge-received-count');
    if (badgeReceived) {
        badgeReceived.textContent = receivedList.length;
        if (receivedList.length === 0) {
            badgeReceived.classList.add('d-none');
            const emptyEl = document.getElementById('empty-received-msg');
            if (emptyEl) emptyEl.classList.remove('d-none');
        }
    }

    const sentList = document.querySelectorAll('#sent-container .connection-item');
    const badgeSent = document.getElementById('badge-sent-count');
    if (badgeSent) {
        badgeSent.textContent = sentList.length;
        if (sentList.length === 0) {
            badgeSent.classList.add('d-none');
            const emptyEl = document.getElementById('empty-sent-msg');
            if (emptyEl) emptyEl.classList.remove('d-none');
        }
    }

    const connList = document.querySelectorAll('#connections-container .connection-item');
    const badgeConn = document.getElementById('badge-connections-count');
    if (badgeConn) {
        badgeConn.textContent = connList.length;
        if (connList.length === 0) {
            const emptyEl = document.getElementById('empty-connections-msg');
            if (emptyEl) emptyEl.classList.remove('d-none');
        }
    }
}
