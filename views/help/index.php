<style>
.support-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
}
.support-header-title {
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.support-card {
    border: 1px solid var(--border-color);
    border-radius: 16px;
    background: var(--bg-card);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}
.support-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.05), transparent);
}
.support-card-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.support-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
    gap: 0.75rem;
}
.support-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}
.support-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(var(--accent-rgb), 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
    flex-shrink: 0;
}
.support-label {
    font-size: 0.7rem;
    color: var(--text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
}
.support-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.2;
}
.support-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}
.support-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    text-decoration: none;
    transition: transform 0.2s ease, filter 0.2s ease;
}
.support-btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.05);
}
.support-btn-accent {
    background: rgba(var(--accent-rgb), 0.1);
    color: var(--accent);
    border: 1px solid rgba(var(--accent-rgb), 0.2);
}
.support-btn-whatsapp {
    background: #25D366;
    color: white;
    border: none;
}
.support-btn-email {
    background: #EA4335;
    color: white;
    border: none;
}

@media (max-width: 600px) {
    .support-card-header {
        padding: 1.25rem 1rem;
    }
    .support-card-body {
        padding: 1rem;
        gap: 0.75rem;
    }
    .support-row {
        padding: 0.75rem 1rem;
        gap: 0.5rem;
    }
    .support-info {
        gap: 0.5rem;
    }
    .support-icon {
        width: 32px;
        height: 32px;
    }
    .support-icon svg {
        width: 16px;
        height: 16px;
    }
    .support-label {
        font-size: 0.6rem;
    }
    .support-value {
        font-size: 0.8rem;
    }
    .support-btn {
        width: 34px;
        height: 34px;
        border-radius: 6px;
    }
    .support-btn svg {
        width: 16px;
        height: 16px;
    }
}
</style>

<div class="container-fluid support-container">
    <!-- Header Page Title -->
    <div class="mb-4">
        <h2 class="support-header-title">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            System Support & Help
        </h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 0;">
            If you encounter any bugs, display errors, or have questions about using CardVault, you can directly reach out to our System Administrator.
        </p>
    </div>

    <!-- Contact Info Cards -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <!-- Mobile Number Row -->
        <div class="support-row">
            <div class="support-info">
                <div>
                    <div class="support-label">Mobile Number</div>
                    <div class="support-value">+91 93720 08131</div>
                </div>
            </div>
            
            <div class="support-actions">
                <!-- Call Link -->
                <a href="tel:+919372008131" title="Call Administrator" class="support-btn support-btn-accent">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </a>
                
                <!-- WhatsApp Link -->
                <a href="https://wa.me/919372008131?text=Hello%20Mahesh%2C%20I%20need%20help%20with%20the%20CardVault%20application." target="_blank" title="WhatsApp Message" class="support-btn support-btn-whatsapp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.458 5.704 1.458h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- Email Row -->
        <div class="support-row">
            <div class="support-info">
                <div style="min-width: 0;">
                    <div class="support-label">Email Address</div>
                    <div class="support-value">patelbhiyaram757@gmail.com</div>
                </div>
            </div>
            
            <div class="support-actions">
                <!-- Email Direct Button -->
                <a href="mailto:patelbhiyaram757@gmail.com?subject=CardVault%20Bug%20/%20Support%20Request" title="Send Email" class="support-btn support-btn-email">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
