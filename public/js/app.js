/**
 * CardVault — Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initUploadPreviews();
    initScanForm();
    initSearchSuggestions();
    initAlertDismiss();
    initWebcam();
    initCardActionDropdowns();
});

// === File Upload Previews ===
function initUploadPreviews() {
    const frontInput = document.getElementById('cardFront');
    const backInput = document.getElementById('cardBack');
    if (frontInput) {
        frontInput.addEventListener('change', (e) => previewImage(e, 'frontPreview', 'frontZone', 'frontPreviewWrap'));
    }
    if (backInput) {
        backInput.addEventListener('change', (e) => previewImage(e, 'backPreview', 'backZone', 'backPreviewWrap'));
    }

    // Drag & drop
    document.querySelectorAll('.upload-zone').forEach(zone => {
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const input = zone.querySelector('input[type="file"]');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

function previewImage(event, previewId, zoneId, wrapId) {
    const file = event.target.files[0];
    const preview     = document.getElementById(previewId);
    const zone        = document.getElementById(zoneId);
    const wrap        = document.getElementById(wrapId);
    const placeholder = zone ? zone.querySelector('.upload-zone-placeholder') : null;
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            if (wrap)        wrap.style.display = 'block';
            if (placeholder) placeholder.style.visibility = 'hidden';
            if (zone)        zone.style.borderColor = 'var(--success)';
        };
        reader.readAsDataURL(file);
    }
}

function removePreview(side) {
    const inputId   = side === 'front' ? 'cardFront'         : 'cardBack';
    const wrapId    = side === 'front' ? 'frontPreviewWrap'  : 'backPreviewWrap';
    const zoneId    = side === 'front' ? 'frontZone'         : 'backZone';
    const previewId = side === 'front' ? 'frontPreview'      : 'backPreview';

    const input       = document.getElementById(inputId);
    const wrap        = document.getElementById(wrapId);
    const zone        = document.getElementById(zoneId);
    const preview     = document.getElementById(previewId);
    const placeholder = zone ? zone.querySelector('.upload-zone-placeholder') : null;

    input.value = '';
    if (preview)     preview.src = '';
    if (wrap)        wrap.style.display = 'none';
    if (placeholder) placeholder.style.visibility = 'visible';
    if (zone)        zone.style.borderColor = '';
}

// === AI Card Scan ===
function initScanForm() {
    const form = document.getElementById('scanForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('scanBtn');
        const overlay = document.getElementById('loadingOverlay');
        
        btn.disabled = true;
        btn.textContent = 'Scanning...';
        overlay.classList.add('active');

        const formData = new FormData(form);

        try {
            const response = await fetch(APP_URL + '/cards/scan', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success && result.data) {
                populateReviewForm(result.data);
                document.getElementById('uploadSection').style.display = 'none';
                document.getElementById('reviewSection').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(result.error || 'Failed to scan card. Please try again.');
            }
        } catch (err) {
            console.error('Scan error:', err);
            alert('Network error. Please check your connection and try again.');
        } finally {
            overlay.classList.remove('active');
            // 6-second cooldown to respect API rate limits
            let remaining = 6;
            btn.disabled = true;
            btn.textContent = `Please wait... (${remaining}s)`;
            const cooldown = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(cooldown);
                    btn.disabled = false;
                    btn.textContent = 'Scan with AI';
                } else {
                    btn.textContent = `Please wait... (${remaining}s)`;
                }
            }, 1000);
        }
    });
}

function populateReviewForm(data) {
    const map = {
        'fPersonName': 'person_name',
        'fDesignation': 'designation',
        'fDepartment': 'department',
        'fPhone1': 'phone_primary',
        'fPhone2': 'phone_secondary',
        'fEmail1': 'email_primary',
        'fEmail2': 'email_secondary',
        'fLinkedin': 'linkedin_url',
        'fCompany': 'company_name',
        'fWebsite': 'company_website',
        'fIndustry': 'company_industry',
        'fGST': 'gst_number',
        'fAddress': 'address',
        'fCity': 'city',
        'fState': 'state',
        'fPincode': 'pincode',
        'fCountry': 'country',
        'fNotes': 'notes',
    };

    for (const [elemId, dataKey] of Object.entries(map)) {
        const el = document.getElementById(elemId);
        if (el && data[dataKey]) {
            el.value = data[dataKey];
        }
    }

    // Handle arrays
    if (data.products_services && Array.isArray(data.products_services)) {
        document.getElementById('fProducts').value = data.products_services.join(', ');
    }
    if (data.tags && Array.isArray(data.tags)) {
        document.getElementById('fTags').value = data.tags.join(', ');
    }

    // Hidden fields
    document.getElementById('saveFrontImage').value = data.card_front_image || '';
    document.getElementById('saveBackImage').value = data.card_back_image || '';
    document.getElementById('saveConfidence').value = data.confidence_score || '';
}

async function resetScan() {
    const frontImg = document.getElementById('saveFrontImage').value;
    const backImg = document.getElementById('saveBackImage').value;

    if (frontImg || backImg) {
        const formData = new FormData();
        if (frontImg) formData.append('front_image', frontImg);
        if (backImg) formData.append('back_image', backImg);
        
        try {
            await fetch(APP_URL + '/cards/cleanup-temp', {
                method: 'POST',
                body: formData
            });
        } catch (err) {
            console.error('Failed to clean up temporary images:', err);
        }
    }

    document.getElementById('uploadSection').style.display = 'block';
    document.getElementById('reviewSection').style.display = 'none';
    document.getElementById('scanForm').reset();
    document.getElementById('frontPreviewWrap').style.display = 'none';
    document.getElementById('backPreviewWrap').style.display  = 'none';
    document.querySelectorAll('.upload-zone').forEach(z => z.style.borderColor = '');
}

// === Search Clear Button ===
function initSearchSuggestions() {
    const input       = document.getElementById('globalSearch');
    const suggestions = document.getElementById('searchSuggestions');
    const clearBtn    = document.getElementById('searchClearBtn');
    if (!input || !suggestions) return;

    // Toggle clear button visibility
    const toggleClear = () => {
        if (clearBtn) clearBtn.classList.toggle('visible', input.value.length > 0);
    };
    input.addEventListener('input', toggleClear);
    toggleClear();

    // Clear on click
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            suggestions.classList.remove('active');
            clearBtn.classList.remove('visible');
            input.focus();
        });
    }

    let debounceTimer;
    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 2) {
            suggestions.classList.remove('active');
            return;
        }
        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(APP_URL + '/search/suggestions?q=' + encodeURIComponent(q));
                const items = await res.json();
                if (items.length === 0) {
                    suggestions.classList.remove('active');
                    return;
                }
                suggestions.innerHTML = items.map(item =>
                    `<div class="suggestion-item" onclick="window.triggerSearch('${escapeHtml(item.label).replace(/'/g, "\\'")}')">
                        <span>${escapeHtml(item.label)}</span>
                        <span class="suggestion-type">${item.type}</span>
                    </div>`
                ).join('');
                suggestions.classList.add('active');
            } catch (e) {
                suggestions.classList.remove('active');
            }
        }, 300);
    });

    // Close suggestions on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-global')) {
            suggestions.classList.remove('active');
        }
    });

    // Form submit loader
    const form = input.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            const overlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            if (overlay && loadingText) {
                loadingText.textContent = 'Searching with AI...';
                overlay.classList.add('active');
            }
        });
    }

    // Suggestions click loader
    window.triggerSearch = (query) => {
        const overlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        if (overlay && loadingText) {
            loadingText.textContent = 'Searching with AI...';
            overlay.classList.add('active');
        }
        window.location.href = `${APP_URL}/search?q=${encodeURIComponent(query)}`;
    };
}

// === Auto-dismiss alerts ===
function initAlertDismiss() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

// === Utility ===
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// APP_URL constant for JS
const APP_URL = document.querySelector('link[rel="stylesheet"]')?.href.split('/css/style.css')[0] || '';

// === Webcam Logic ===
let currentWebcamTarget = null;
let videoStream = null;

function initWebcam() {
    const stopBtn = document.getElementById('stopCameraBtn');
    const captureBtn = document.getElementById('captureBtn');
    const ui = document.getElementById('webcamUI');
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');

    if (!stopBtn) return;

    window.openWebcam = async (target) => {
        currentWebcamTarget = target;
        try {
            // Try with ideal environment facing mode first (great for mobile back cameras)
            videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
        } catch (err) {
            try {
                // Fallback to any available video stream (like front webcam on laptops)
                videoStream = await navigator.mediaDevices.getUserMedia({ video: true });
            } catch (fallbackErr) {
                alert("Camera access denied or not available on this device.");
                return;
            }
        }
        
        try {
            video.srcObject = videoStream;
            ui.style.display = 'block';
            captureBtn.textContent = 'Snap ' + (target === 'front' ? 'Front' : 'Back') + ' Side';
            ui.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (err) {
            alert("Error initializing camera stream.");
        }
    };

    stopBtn.addEventListener('click', () => {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }
        ui.style.display = 'none';
    });

    captureBtn.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        canvas.toBlob(blob => {
            const file = new File([blob], `webcam_${currentWebcamTarget}.jpg`, { type: "image/jpeg" });
            const dt = new DataTransfer();
            dt.items.add(file);
            
            const inputId = currentWebcamTarget === 'front' ? 'cardFront' : 'cardBack';
            const input = document.getElementById(inputId);
            input.files = dt.files;
            input.dispatchEvent(new Event('change')); // Trigger preview
            
            stopBtn.click(); // Close webcam
        }, 'image/jpeg', 0.9);
    });
}

// === Card Actions Dropdown Toggle ===
function initCardActionDropdowns() {
    // Force-hide all dropdowns on page load
    document.querySelectorAll('.card-actions-dropdown').forEach(d => {
        d.style.setProperty('display', 'none', 'important');
    });
}

function toggleCardActions(e, btn) {
    e.stopPropagation();
    const dropdown = btn.nextElementSibling;
    const computedDisplay = getComputedStyle(dropdown).display;
    const isOpen = computedDisplay !== 'none';

    // Close ALL open dropdowns first
    document.querySelectorAll('.card-actions-dropdown').forEach(d => {
        d.style.setProperty('display', 'none', 'important');
    });
    document.querySelectorAll('.card-actions-toggle.active').forEach(b => b.classList.remove('active'));

    if (!isOpen) {
        dropdown.style.setProperty('display', 'flex', 'important');
        dropdown.style.flexDirection = 'column';
        btn.classList.add('active');
    }
}

// Close dropdowns when clicking anywhere outside
document.addEventListener('click', () => {
    document.querySelectorAll('.card-actions-dropdown').forEach(d => {
        d.style.setProperty('display', 'none', 'important');
    });
    document.querySelectorAll('.card-actions-toggle.active').forEach(b => b.classList.remove('active'));
});

// Remove loader active state on page show (e.g. back/forward navigation or bfcache restore)
window.addEventListener('pageshow', (event) => {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
});
