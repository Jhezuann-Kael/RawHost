async function loadAbuseReports() {
    const tbody   = document.getElementById('abuse-body');
    const spinner = document.getElementById('abuse-loading');
    if (!tbody) return;

    if (spinner) spinner.style.display = 'inline';

    try {
        const res  = await fetch(`/api/servers/abuse_reports?id=${serverId}`);
        const data = await res.json();

        if (!data.success) throw new Error(data.message);

        let reports = data.data;
        if (!Array.isArray(reports)) {
            reports = reports && typeof reports === 'object' ? Object.values(reports) : [];
        }
        renderAbuseReports(reports);
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:20px;color:#ef4444;">
            <i class="fas fa-exclamation-circle"></i> ${e.message}
        </td></tr>`;
    } finally {
        if (spinner) spinner.style.display = 'none';
    }
}

function renderAbuseReports(reports) {
    const tbody = document.getElementById('abuse-body');
    if (!tbody) return;

    if (!reports || reports.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">
            <i class="fas fa-check-circle" style="color:#22c55e;"></i> No abuse reports found.
        </td></tr>`;
        return;
    }

    tbody.innerHTML = reports.map((r, idx) => {
        const date    = r.date || r.created_at || r.timestamp || '—';
        const type    = r.abuse_type || r.type || r.category || '—';
        const subject = r.subject || '—';
        const content = r.content || r.description || r.message || '—';

        // Extract IP from subject if possible (e.g. "[...] | 31.57.184.23 | ...")
        let ip = r.ip || '—';
        if (ip === '—') {
            const ipMatch = subject.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
            if (ipMatch) ip = ipMatch[1];
        }

        const isLong = content.length > 180;
        const shortContent = isLong ? content.substring(0, 180) + '...' : content;

        return `<tr>
            <td><code style="color:var(--text-light); font-size:0.8rem;">${escHtml(ip)}</code></td>
            <td><span class="status-badge status-inactive" style="text-transform:capitalize;">${escHtml(type)}</span></td>
            <td style="color:var(--text-muted); font-size:0.75rem; white-space:nowrap;">${escHtml(date)}</td>
            <td style="color:var(--text-muted); font-size:0.8rem; max-width:450px; word-break:break-word;">
                <div style="font-weight:600; color:var(--text-light); margin-bottom:4px;">${escHtml(subject)}</div>
                <div id="abuse-short-${idx}">${escHtml(shortContent)}
                    ${isLong ? `<button onclick="toggleAbuse(${idx})" class="btn-link" style="color:var(--primary); border:none; background:none; padding:0; font-size:0.75rem; cursor:pointer; margin-left:5px; text-decoration:underline;">Show more</button>` : ''}
                </div>
                <div id="abuse-full-${idx}" style="display:none;">
                    <div style="background:rgba(0,0,0,0.2); padding:10px; border-radius:6px; margin-top:5px; white-space:pre-wrap; font-family:monospace; font-size:0.75rem; border:1px solid rgba(255,255,255,0.05);">${escHtml(content)}</div>
                    <button onclick="toggleAbuse(${idx})" class="btn-link" style="color:var(--primary); border:none; background:none; padding:0; font-size:0.75rem; cursor:pointer; margin-top:5px; text-decoration:underline;">Show less</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function toggleAbuse(idx) {
    const short = document.getElementById(`abuse-short-${idx}`);
    const full  = document.getElementById(`abuse-full-${idx}`);
    if (short && full) {
        const isHidden = full.style.display === 'none';
        full.style.display  = isHidden ? 'block' : 'none';
        short.style.display = isHidden ? 'none'  : 'block';
    }
}

function escHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

window.addEventListener('DOMContentLoaded', () => {
    if (typeof serverId !== 'undefined' && serverId) loadAbuseReports();
});
