<?php /** Card Upload / Scan Page */ ?>

<div style="max-width:900px; margin:0 auto;">
    <div style="text-align:center; margin-bottom:2rem;">
        <h2 style="font-size:1.5rem; margin-bottom:0.5rem;">Scan a Visiting Card</h2>
        <p style="color:var(--text-muted);">Upload or photograph a visiting card. AI will extract all the details automatically.</p>
    </div>

    <!-- Upload Section -->
    <div id="uploadSection">
        <form id="scanForm" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            
            <!-- Webcam UI (Hidden initially) -->

            <div id="webcamUI" style="display:none; text-align:center; margin-bottom:1.5rem; background:var(--bg-card); padding:1rem; border-radius:var(--radius-lg); border:1px solid var(--border-color);">
                <video id="webcamVideo" autoplay playsinline style="width:100%; max-width:500px; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-bottom:1rem;"></video>
                <canvas id="webcamCanvas" style="display:none;"></canvas>
                <div style="display:flex; justify-content:center; gap:1rem;">
                    <button type="button" class="btn btn-primary" id="captureBtn">Snap Photo</button>
                    <button type="button" class="btn btn-secondary" id="stopCameraBtn">Cancel</button>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                <div>
                    <label class="form-label">Front Side *</label>
                    <div class="upload-zone" id="frontZone">
                        <div class="upload-zone-placeholder" id="frontPlaceholder">
                            <div class="upload-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>
                            <h3>Card Front</h3>
                            <p>Click or drag image here</p>
                        </div>
                        <div class="upload-preview-wrap" id="frontPreviewWrap" style="display:none;">
                            <button type="button" class="preview-remove-btn" onclick="removePreview('front')" title="Remove image">&#x2715;</button>
                            <img id="frontPreview" class="upload-preview">
                        </div>
                        <input type="file" name="card_front" id="cardFront" accept="image/*" capture="environment" required>
                    </div>
                    <div style="text-align:center; margin-top:0.75rem;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openWebcam('front')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            Scan Live
                        </button>
                    </div>
                </div>
                <div>
                    <label class="form-label">Back Side (Optional)</label>
                    <div class="upload-zone" id="backZone">
                        <div class="upload-zone-placeholder" id="backPlaceholder">
                            <div class="upload-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>
                            <h3>Card Back</h3>
                            <p>Click or drag image here</p>
                        </div>
                        <div class="upload-preview-wrap" id="backPreviewWrap" style="display:none;">
                            <button type="button" class="preview-remove-btn" onclick="removePreview('back')" title="Remove image">&#x2715;</button>
                            <img id="backPreview" class="upload-preview">
                        </div>
                        <input type="file" name="card_back" id="cardBack" accept="image/*" capture="environment">
                    </div>
                    <div style="text-align:center; margin-top:0.75rem;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openWebcam('back')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            Scan Live
                        </button>
                    </div>
                </div>
            </div>
            <div style="text-align:center;">
                <button type="submit" class="btn btn-primary btn-lg" id="scanBtn">
                    Scan with AI
                </button>
            </div>
        </form>
    </div>

    <!-- Review Section (shown after AI scan) -->
    <div id="reviewSection" style="display:none;">
        <div class="alert alert-success" id="scanSuccess">
            AI extracted the card data! Review and correct if needed, then save.
        </div>

        <form method="POST" action="<?= APP_URL ?>/cards/save" id="saveForm">
            <?= CSRF::field() ?>
            <input type="hidden" name="card_front_image" id="saveFrontImage">
            <input type="hidden" name="card_back_image" id="saveBackImage">
            <input type="hidden" name="confidence_score" id="saveConfidence">

            <!-- Person Details -->
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem;">
                <h3 style="font-size:0.9rem; color:var(--accent); margin-bottom:1rem;">Person Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="person_name" id="fPersonName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" id="fDesignation" class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="fDepartment" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="text" name="linkedin_url" id="fLinkedin" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Contact Details -->
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem;">
                <h3 style="font-size:0.9rem; color:var(--accent); margin-bottom:1rem;">Contact Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone (Primary)</label>
                        <input type="tel" name="phone_primary" id="fPhone1" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone (Secondary)</label>
                        <input type="tel" name="phone_secondary" id="fPhone2" class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email (Primary)</label>
                        <input type="email" name="email_primary" id="fEmail1" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email (Secondary)</label>
                        <input type="email" name="email_secondary" id="fEmail2" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Company Details -->
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem;">
                <h3 style="font-size:0.9rem; color:var(--accent); margin-bottom:1rem;">Company Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" id="fCompany" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="text" name="company_website" id="fWebsite" class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Industry</label>
                        <input type="text" name="company_industry" id="fIndustry" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">GST Number</label>
                        <input type="text" name="gst_number" id="fGST" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" id="fAddress" class="form-input">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="fCity" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" id="fState" class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" id="fPincode" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" id="fCountry" class="form-input" value="India">
                    </div>
                </div>
            </div>

            <!-- Products & Tags -->
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem;">
                <h3 style="font-size:0.9rem; color:var(--accent); margin-bottom:1rem;">Products, Services & Tags</h3>
                <div class="form-group">
                    <label class="form-label">Products / Services</label>
                    <input type="text" name="products_services" id="fProducts" class="form-input" placeholder="Comma separated: TV, CCTV, Networking">
                    <div class="form-hint">Comma-separated list. These are searchable keywords.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tags</label>
                    <input type="text" name="tags" id="fTags" class="form-input" placeholder="Comma separated: electronics, security">
                    <div class="form-hint">Additional tags for categorization.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="fNotes" class="form-textarea" placeholder="Any additional information..."></textarea>
                </div>
            </div>

            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="resetScan()">← Scan Another</button>
                <button type="submit" class="btn btn-primary btn-lg">Save Card</button>
            </div>
        </form>
    </div>
</div>
