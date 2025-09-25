/**
 * Scoring Interface Helpers
 * Provides functions for enhanced scoring UI interactions
 */

/**
 * Auto-save scores with debouncing
 */
let saveScoreTimeout;
const SAVE_DELAY = 1000; // 1 second delay

function autoSaveScore(roundId, criterionId, participantId, judgeUserId, score) {
    // Clear existing timeout
    if (saveScoreTimeout) {
        clearTimeout(saveScoreTimeout);
    }
    
    // Set new timeout
    saveScoreTimeout = setTimeout(async () => {
        try {
            showToast('Saving score...', 'info');
            await submitScore(roundId, criterionId, participantId, judgeUserId, score);
            showToast('Score saved', 'success');
        } catch (error) {
            showToast('Failed to save score', 'error');
            console.error('Score save error:', error);
        }
    }, SAVE_DELAY);
}

/**
 * Update score display with color coding
 */
function updateScoreDisplay(criterionId, score) {
    const display = document.getElementById(`score-display-${criterionId}`);
    if (display) {
        display.textContent = parseFloat(score).toFixed(1);
        
        // Update color based on score
        display.className = 'text-2xl font-bold ';
        if (score >= 8) {
            display.className += 'text-green-600';
        } else if (score >= 6) {
            display.className += 'text-yellow-600';
        } else {
            display.className += 'text-red-600';
        }
    }
}

/**
 * Calculate and display total weighted score
 */
function updateTotalScore(criteria, scores) {
    let totalWeightedScore = 0;
    let totalWeight = 0;
    
    criteria.forEach(criterion => {
        const score = scores[criterion.id] || 0;
        totalWeightedScore += score * (criterion.weight / 100);
        totalWeight += criterion.weight;
    });
    
    const finalScore = totalWeight > 0 ? totalWeightedScore : 0;
    
    const display = document.getElementById('total-score-display');
    if (display) {
        display.textContent = finalScore.toFixed(2);
        
        // Update color
        display.className = 'text-3xl font-bold ';
        if (finalScore >= 8) {
            display.className += 'text-green-600';
        } else if (finalScore >= 6) {
            display.className += 'text-yellow-600';
        } else {
            display.className += 'text-red-600';
        }
    }
}

/**
 * Validate score range
 */
function validateScore(score, min = 1, max = 10) {
    const numScore = parseFloat(score);
    return !isNaN(numScore) && numScore >= min && numScore <= max;
}

/**
 * Progress tracking for scoring
 */
function updateScoringProgress(completedCount, totalCount) {
    const progressBar = document.getElementById('scoring-progress-bar');
    const progressText = document.getElementById('scoring-progress-text');
    
    if (progressBar) {
        const percentage = totalCount > 0 ? (completedCount / totalCount) * 100 : 0;
        progressBar.style.width = `${percentage}%`;
    }
    
    if (progressText) {
        progressText.textContent = `${completedCount}/${totalCount} participants scored`;
    }
}

/**
 * Keyboard shortcuts for scoring
 */
function setupScoringKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save scores
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const saveButton = document.getElementById('save-scores-btn');
            if (saveButton && !saveButton.disabled) {
                saveButton.click();
            }
        }
        
        // Number keys for quick scoring (if focused on a slider)
        if (e.target.type === 'range' && e.key >= '1' && e.key <= '9') {
            e.preventDefault();
            const score = parseInt(e.key);
            e.target.value = score;
            e.target.dispatchEvent(new Event('input'));
        }
    });
}

/**
 * Real-time score validation
 */
function setupScoreValidation() {
    const scoreInputs = document.querySelectorAll('input[type="range"], input[type="number"]');
    
    scoreInputs.forEach(input => {
        input.addEventListener('input', function() {
            const score = parseFloat(this.value);
            const isValid = validateScore(score);
            
            // Visual feedback
            if (isValid) {
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            } else {
                this.classList.remove('border-green-500');
                this.classList.add('border-red-500');
            }
        });
    });
}

/**
 * Initialize scoring interface
 */
document.addEventListener('DOMContentLoaded', function() {
    setupScoringKeyboardShortcuts();
    setupScoreValidation();
    
    // Setup score sliders if they exist
    const sliders = document.querySelectorAll('.score-slider');
    sliders.forEach(slider => {
        slider.addEventListener('input', function() {
            const criterionId = this.dataset.criterionId;
            if (criterionId) {
                updateScoreDisplay(criterionId, this.value);
            }
        });
    });
});

/**
 * Bulk score operations
 */
async function saveAllScores(formData) {
    const saveButton = document.getElementById('save-scores-btn');
    showLoading(saveButton, 'Saving...');
    
    try {
        const response = await submitFormAsAPI(formData, 'save_bulk_scores');
        showToast('All scores saved successfully', 'success');
        return response;
    } catch (error) {
        showToast('Failed to save scores', 'error');
        throw error;
    } finally {
        hideLoading(saveButton);
    }
}

/**
 * Score comparison and conflict detection
 */
function detectScoreConflicts(judgeScores, threshold = 2.0) {
    const conflicts = [];
    
    // This would compare scores between judges for the same participant/criterion
    // Implementation depends on data structure
    
    return conflicts;
}