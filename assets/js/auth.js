/**
 * MITS Alumni Portal - Google Identity Services (GIS) Client Handler
 */

/**
 * Global Callback for Google Identity Services Button & One-Tap
 * @param {Object} response - GIS credential response object
 */
async function handleGoogleCredentialResponse(response) {
    if (!response || !response.credential) {
        showToast('danger', 'Google authentication did not return a valid credential.');
        return;
    }

    const loginLoadingEl = document.getElementById('login-loading');
    const loginActionsEl = document.getElementById('login-actions');
    if (loginLoadingEl) loginLoadingEl.classList.remove('d-none');
    if (loginActionsEl) loginActionsEl.classList.add('opacity-50', 'pointer-events-none');

    try {
        const res = await apiFetch(getBaseUrl() + '/auth/google-callback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                credential: response.credential,
                csrf_token: getCsrfToken()
            })
        });

        const data = await res.json();

        if (data.success) {
            showToast('success', data.message || 'Signed in successfully! Redirecting...');
            setTimeout(() => {
                window.location.href = data.redirect || (getBaseUrl() + '/index.php');
            }, 500);
        } else {
            showToast('danger', data.message || 'Authentication failed. Please try again.');
            if (loginLoadingEl) loginLoadingEl.classList.add('d-none');
            if (loginActionsEl) loginActionsEl.classList.remove('opacity-50', 'pointer-events-none');
        }
    } catch (err) {
        console.error('Auth error:', err);
        showToast('danger', 'Network error communicating with authentication service.');
        if (loginLoadingEl) loginLoadingEl.classList.add('d-none');
        if (loginActionsEl) loginActionsEl.classList.remove('opacity-50', 'pointer-events-none');
    }
}

/**
 * Handle Local Development One-Click Sign-In
 * @param {string} email - Demo user email address
 */
async function signInAsDemo(email) {
    const loginLoadingEl = document.getElementById('login-loading');
    if (loginLoadingEl) loginLoadingEl.classList.remove('d-none');

    try {
        const res = await apiFetch(getBaseUrl() + '/auth/google-callback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                demo_email: email,
                csrf_token: getCsrfToken()
            })
        });

        const data = await res.json();
        if (data.success) {
            showToast('success', 'Demo signed in as ' + email);
            setTimeout(() => {
                window.location.href = data.redirect || (getBaseUrl() + '/index.php');
            }, 400);
        } else {
            showToast('danger', data.message || 'Failed to sign in demo account.');
            if (loginLoadingEl) loginLoadingEl.classList.add('d-none');
        }
    } catch (err) {
        console.error('Demo auth error:', err);
        showToast('danger', 'Error connecting to demo authentication.');
        if (loginLoadingEl) loginLoadingEl.classList.add('d-none');
    }
}
