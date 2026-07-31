<style>
.support-container {
    max-width: 680px;
    margin: 0 auto;
    padding: 2rem 1.25rem;
}
.support-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.support-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, var(--accent), #c49a3c);
}
.profile-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2.25rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.profile-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #c49a3c);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(123, 45, 38, 0.2);
    border: 3px solid var(--bg-card);
}
.profile-name {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}
.profile-title {
    font-size: 0.88rem;
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.profile-subtitle {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.2rem;
}
.channel-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.channel-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    border-radius: 16px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.channel-row:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
    border-color: rgba(var(--accent-rgb), 0.3);
}
.channel-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
}
.channel-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.25rem;
}
.channel-icon.phone {
    background: rgba(123, 45, 38, 0.1);
    color: var(--accent);
}
.channel-icon.whatsapp {
    background: rgba(37, 211, 102, 0.1);
    color: #25D366;
}
.channel-icon.email {
    background: rgba(234, 67, 53, 0.1);
    color: #EA4335;
}
.channel-details {
    min-width: 0;
}
.channel-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.15rem;
}
.channel-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-all;
}
.channel-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
}
.channel-action-btn:hover {
    transform: scale(1.05);
}
.channel-action-btn.phone:hover {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}
.channel-action-btn.whatsapp:hover {
    background: #25D366;
    color: #fff;
    border-color: #25D366;
}
.channel-action-btn.email:hover {
    background: #EA4335;
    color: #fff;
    border-color: #EA4335;
}

@media (max-width: 600px) {
    .support-container {
        padding: 1.25rem 0.75rem;
    }
    .support-card {
        padding: 1.5rem 1rem;
    }
    .profile-section {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1.25rem;
    }
    .profile-avatar {
        width: 64px;
        height: 64px;
        font-size: 1.5rem;
    }
    .channel-row {
        padding: 1rem;
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
        text-align: center;
    }
    .channel-meta {
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }
    .channel-action-btn {
        width: 100%;
        height: 42px;
        border-radius: 8px;
    }
}
</style>

<div class="support-container">
    <div class="support-card">
        <!-- Profile header with admin name -->
        <div class="profile-section">
            <div class="profile-avatar">MP</div>
            <div>
                <div class="profile-name">Mahesh Patel</div>
                <div class="profile-title">System Administrator</div>
                <div class="profile-subtitle">CardVault Platform Support & Operations</div>
            </div>
        </div>

        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 2rem; text-align: center;">
            Need help, found a bug, or want to suggest a new feature? You can get in touch with the system administrator directly through the channels below.
        </p>

        <!-- Communication Channels -->
        <div class="channel-list">
            <!-- Mobile Call option -->
            <div class="channel-row">
                <div class="channel-meta">
                    <div class="channel-icon phone">📞</div>
                    <div class="channel-details">
                        <div class="channel-label">Direct Line</div>
                        <div class="channel-value">+91 93720 08131</div>
                    </div>
                </div>
                <a href="tel:+919372008131" class="channel-action-btn phone" title="Call Administrator">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </a>
            </div>

            <!-- WhatsApp Option -->
            <div class="channel-row">
                <div class="channel-meta">
                    <div class="channel-icon whatsapp">💬</div>
                    <div class="channel-details">
                        <div class="channel-label">WhatsApp Support</div>
                        <div class="channel-value">Chat Live Now</div>
                    </div>
                </div>
                <a href="https://wa.me/919372008131?text=Hello%20Mahesh%2C%20I%20need%20help%20with%20the%20CardVault%20application." target="_blank" class="channel-action-btn whatsapp" title="Message via WhatsApp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.458 5.704 1.458h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
            </div>

            <!-- Email Address Row -->
            <div class="channel-row">
                <div class="channel-meta">
                    <div class="channel-icon email">✉️</div>
                    <div class="channel-details">
                        <div class="channel-label">Email Support</div>
                        <div class="channel-value">patelbhiyaram757@gmail.com</div>
                    </div>
                </div>
                <a href="mailto:patelbhiyaram757@gmail.com?subject=CardVault%20Bug%20/%20Support%20Request" class="channel-action-btn email" title="Send Email">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
