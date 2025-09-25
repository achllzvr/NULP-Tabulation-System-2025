/**
 * API Helper Functions
 * Provides a consistent interface for making API calls to api/api.php
 * Replaces React's fetch operations with vanilla JavaScript
 */

// Get API base URL from global config
window.APP_API_BASE = window.APP_API_BASE || 'api/api.php';

/**
 * Main API function for making requests
 * @param {string} action - The action parameter for api.php
 * @param {object} payload - Data to send with the request
 * @param {string} method - HTTP method (default: POST)
 * @returns {Promise<object>} - API response as JSON
 */
async function api(action, payload = {}, method = 'POST') {
    try {
        const url = `${window.APP_API_BASE}?action=${encodeURIComponent(action)}`;
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (method !== 'GET' && Object.keys(payload).length > 0) {
            options.body = JSON.stringify(payload);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
        
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * Convenience functions for common API operations
 */

// Score submission
async function submitScore(roundId, criterionId, participantId, judgeUserId, score) {
    return api('submit_score', {
        round_id: roundId,
        criterion_id: criterionId,
        participant_id: participantId,
        judge_user_id: judgeUserId,
        score: score
    });
}

// Get leaderboard
async function getLeaderboard(roundId) {
    return api('get_leaderboard', { round_id: roundId }, 'GET');
}

// Set advancement results
async function setAdvancement(fromRoundId, toRoundId, participantIds) {
    return api('set_advancement', {
        from_round_id: fromRoundId,
        to_round_id: toRoundId,
        participant_ids: participantIds
    });
}

// Control rounds (open/close)
async function controlRound(roundId, action, adminUserId) {
    return api('control_round', {
        round_id: roundId,
        action: action, // 'open' or 'close' 
        admin_user_id: adminUserId
    });
}

// Set award results
async function setAwardResult(awardId, participantIds, adminUserId) {
    return api('set_award_result_manual', {
        award_id: awardId,
        participant_ids: participantIds,
        admin_user_id: adminUserId
    });
}

// Resolve tie groups
async function resolveTieGroup(tieGroupId, orderedParticipantIds, adminUserId) {
    return api('resolve_tie_group', {
        tie_group_id: tieGroupId,
        ordered_participant_ids: orderedParticipantIds,
        admin_user_id: adminUserId
    });
}

// Update visibility settings
async function setVisibilityFlags(pageantId, flags) {
    return api('set_visibility_flags', {
        pageant_id: pageantId,
        flags: flags
    });
}

// Get public leaderboard
async function getPublicLeaderboard(pageantCode) {
    return api('public_leaderboard', { code: pageantCode }, 'GET');
}

/**
 * Form submission helper
 * Converts form data to API call
 */
function submitFormAsAPI(formElement, action) {
    const formData = new FormData(formElement);
    const payload = {};
    
    for (let [key, value] of formData.entries()) {
        payload[key] = value;
    }
    
    return api(action, payload);
}

/**
 * Show loading state
 */
function showLoading(element, text = 'Loading...') {
    if (element) {
        element.disabled = true;
        element.dataset.originalText = element.textContent;
        element.textContent = text;
    }
}

function hideLoading(element) {
    if (element && element.dataset.originalText) {
        element.disabled = false;
        element.textContent = element.dataset.originalText;
        delete element.dataset.originalText;
    }
}

/**
 * Simple toast notifications
 */
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${getToastClasses(type)}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

function getToastClasses(type) {
    switch (type) {
        case 'success':
            return 'bg-green-100 text-green-800 border border-green-200';
        case 'error':
            return 'bg-red-100 text-red-800 border border-red-200';
        case 'warning':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        default:
            return 'bg-blue-100 text-blue-800 border border-blue-200';
    }
}