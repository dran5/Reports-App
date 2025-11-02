<?php
// dashboard.php
require 'config.php';
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];

// prepare avatar / initials fallback
$avatar = !empty($user['picture']) ? $user['picture'] : null;
$name = !empty($user['name']) ? $user['name'] : ($user['email'] ?? 'User');
$initials = '';
if (!$avatar) {
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) === 1) {
        $initials = strtoupper(substr($parts[0], 0, 1));
    } else {
        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard - Reports App</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="shortcut icon" href="images/Spider-Man.png" type="image/x-icon">
  <style>
    :root{
      --bg:#0b1220;
      --card:#0f1724;
      --muted:#93a0b8;
      --accent:#1d72f3;
      --up:#2bd27b;
      --down:#ff6b6b;
      --glass: rgba(255,255,255,0.03);
      --card-radius:14px;
    }
    *{box-sizing:border-box}
    body {
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      margin: 0; padding: 28px;
      background: linear-gradient(180deg, #071026 0%, #081126 60%, #071020 100%);
      color: #e6eef8;
      min-height: 100vh;
    }

    /* Top bar */
    .top {
      display:flex; justify-content:space-between; align-items:center; gap:12px;
      margin-bottom:18px;
    }
    .top h2 { margin:0; font-size:20px; letter-spacing:0.2px; color:#fff; }
    .controls {
      display:flex; gap:12px; align-items:center;
      position: relative;
    }

    /* Avatar and dropdown trigger */
    .avatar-btn {
      display:inline-flex; align-items:center; justify-content:center;
      width:48px; height:48px; border-radius:50%;
      overflow:hidden; font-weight:600; color:#071026;
      background: linear-gradient(135deg,#00c6ff,#1d72f3);
      box-shadow: 0 6px 18px rgba(29,114,243,0.14);
      border: 2px solid rgba(255,255,255,0.06);
      cursor: pointer;
      transition: transform .12s ease, box-shadow .12s ease;
    }
    .avatar-btn:active { transform: scale(.98); }
    .avatar-btn img { width:100%; height:100%; object-fit:cover; display:block; border-radius:50%; }

    .top-right-name { margin-right:8px; font-weight:600; color:#e6eef8; text-align:right; }

    /* Profile avatar in menu */
    .profile-avatar {
      width:44px; height:44px; border-radius:8px; object-fit:cover; display:block;
    }

    /* Modal (bigger avatar) */
    .profile-modal-avatar {
      width:72px; height:72px; border-radius:10px; object-fit:cover; display:block;
    }

    /* Dropdown menu */
    .profile-menu {
      position: absolute;
      right: 0;
      top: 70px;
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      border: 1px solid rgba(255,255,255,0.04);
      color: #eaf4ff;
      width: 220px;
      border-radius: 12px;
      box-shadow: 0 18px 40px rgba(2,6,23,0.6);
      padding: 10px;
      z-index: 40;
      transform-origin: top right;
      transition: transform .14s ease, opacity .14s ease;
      opacity: 0;
      transform: scale(.96);
      pointer-events: none;
    }
    .profile-menu.open { opacity: 1; transform: scale(1); pointer-events: auto; }

    .profile-menu .meta { display:flex; gap:10px; align-items:center; padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.02); margin-bottom:8px;}
    .profile-menu .meta .meta-text { font-size:13px; }
    .profile-menu .meta .meta-text .name { font-weight:700; color:#fff; }
    .profile-menu .meta .meta-text .email { font-size:12px; color:var(--muted); }

    .profile-menu a.item {
      display:flex; align-items:center; gap:10px; padding:8px; border-radius:8px;
      color: #e6eef8; text-decoration:none; font-weight:600; font-size:13px;
    }
    .profile-menu a.item:hover { background: rgba(255,255,255,0.02); }

    .profile-menu .signout { margin-top:8px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.02); display:flex; gap:8px; }

    /* Card */
    .card { background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
            padding:16px; border-radius:var(--card-radius); box-shadow: 0 6px 28px rgba(2,6,23,0.6);
            border: 1px solid rgba(255,255,255,0.03); }

    /* Summary grid */
    .grid { display:grid; grid-template-columns: repeat(3,1fr); gap:14px; margin-top:12px; }
    .summary-card {
      padding:18px; border-radius:12px; display:flex; flex-direction:column; gap:8px;
      transition:transform .18s ease, box-shadow .18s ease;
      background: linear-gradient(135deg, rgba(255,255,255,0.015), rgba(255,255,255,0.01));
      border: 1px solid rgba(255,255,255,0.03);
    }
    .summary-card:hover { transform:translateY(-6px); box-shadow: 0 18px 40px rgba(2,6,23,0.6); }
    .summary-card h4 { margin:0; font-size:13px; color:var(--muted); font-weight:600; letter-spacing:0.6px;}
    .summary-value { font-size:20px; font-weight:700; color:#fff; }

    /* sparkline */
    .sparkline { margin-top:8px; width:100%; height:40px; display:block; }
    .sparkline path { fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .delta { font-weight:700; margin-right:6px; }
    .delta.up { color: var(--up); }
    .delta.down { color: var(--down); }

    /* Table area */
    .table { margin-top:18px; padding:14px; border-radius:12px; }
    table { width:100%; border-collapse:collapse; background:transparent; }
    thead th {
      text-align:left; font-size:12px; color:var(--muted); padding:10px 12px;
      border-bottom:1px solid rgba(255,255,255,0.03);
      font-weight:600;
    }
    tbody td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,0.02); color:#dbe9ff; font-size:14px; }
    tbody tr:hover td { background: linear-gradient(90deg, rgba(29,114,243,0.02), rgba(255,255,255,0.01)); }

    /* Controls row for import/export */
    .actions { display:flex; gap:10px; align-items:center; }
    input[type=file]{ display:none; }
    .btn { padding:8px 12px; border-radius:8px; border:none; cursor:pointer; font-weight:600; font-size:13px; }
    .btn-primary { background: linear-gradient(90deg,var(--accent), #00c6ff); color:#071026; box-shadow: 0 6px 18px rgba(29,114,243,0.12); }
    .btn-ghost { background:transparent; border:1px solid rgba(255,255,255,0.06); color:var(--muted); }

    /* delete button */
    .btn-danger {
      background: linear-gradient(90deg,#ff6b6b,#ff9472);
      color: #fff;
      padding:6px 10px;
      font-weight:700;
      border-radius:6px;
      border:none;
      cursor:pointer;
    }
    .btn-danger[disabled] {
      opacity: .6;
      cursor: not-allowed;
    }

    /* responsive */
    @media (max-width:900px){
      .grid { grid-template-columns: repeat(1,1fr); }
      body { padding:16px; }
      .profile-menu { right: 8px; left: 8px; top: 86px; width: auto; }
    }

    .small-muted { color:var(--muted); font-size:13px; }

    /* Modal styles */
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(2,6,23,0.6); display: none; align-items: center; justify-content: center; z-index: 60;
    }
    .modal-backdrop.open { display:flex; }
    .modal {
      width: 540px; max-width: calc(100% - 40px); background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      border-radius: 12px; padding: 18px; box-shadow: 0 18px 40px rgba(2,6,23,0.6); border: 1px solid rgba(255,255,255,0.03);
      transform: translateY(8px); transition: transform .15s ease, opacity .15s ease;
    }
    .modal.open { transform: translateY(0); }
    .modal h3 { margin: 0 0 8px 0; font-size:18px; color:#fff; }
    .modal .muted { color:var(--muted); font-size:13px; margin-bottom:10px; }
    .modal .row { display:flex; gap:12px; align-items:center; margin-bottom:10px; }
    .modal label { display:block; font-size:13px; margin-bottom:6px; color:var(--muted); }
    .modal input[type="text"], .modal input[type="email"], .modal textarea, .modal select {
      width:100%; padding:10px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.04); background: rgba(255,255,255,0.02); color:#eaf4ff;
    }
    .modal .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:12px; }
    .close-btn { background:transparent; border:none; color:var(--muted); font-weight:700; font-size:18px; cursor:pointer; }

    /* Confirm modal + Toast styles */
    #confirmBackdrop { background: rgba(2,6,23,0.6); display:none; }
    #confirmBackdrop.open { display:flex; }
    #confirmBackdrop .modal { max-width:420px; }
    #toastWrap { position:fixed; right:18px; bottom:18px; z-index:9999; display:flex; flex-direction:column; gap:8px; pointer-events:none; }
    .toast {
      min-width:220px;
      max-width:360px;
      padding:10px 14px;
      border-radius:10px;
      background: rgba(0,0,0,0.7);
      color: #fff;
      box-shadow: 0 8px 24px rgba(2,6,23,0.6);
      font-weight:600;
      pointer-events:auto;
      opacity:1;
      transform:translateY(0);
    }
    .toast.success { background: linear-gradient(90deg,#2bd27b,#18b76a); color:#071026; }
    .toast.error { background: linear-gradient(90deg,#ff6b6b,#ff9472); color:#fff; }
    .toast.info { background: rgba(255,255,255,0.06); color:#e6eef8; }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h2>Reports Dashboard</h2>
      <div class="small-muted">Overview of your Sales · Marketing · Visitors</div>
    </div>

    <div class="controls" id="profile-controls">
      <div style="text-align:right; margin-right:6px;">
        <div class="top-right-name"><?php echo htmlspecialchars($name); ?></div>
        <div class="small-muted" style="font-size:12px;"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
      </div>

      <!-- Avatar button (opens menu) -->
      <div style="position:relative;">
        <button id="avatarBtn" class="avatar-btn" aria-haspopup="true" aria-expanded="false" title="Open profile menu">
          <?php if ($avatar): ?>
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar"
                 onerror="replaceWithInitials(this, '<?php echo htmlspecialchars($initials); ?>', 'circle')" />
          <?php else: ?>
            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-weight:700;"><?php echo htmlspecialchars($initials); ?></div>
          <?php endif; ?>
        </button>

        <!-- Profile dropdown -->
        <div id="profileMenu" class="profile-menu" role="menu" aria-hidden="true">
          <div class="meta">
            <div style="width:44px;height:44px;border-radius:8px; overflow:hidden;">
              <?php if ($avatar): ?>
                <img class="profile-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar"
                     onerror="replaceWithInitials(this, '<?php echo htmlspecialchars($initials); ?>', 'rounded')" />
              <?php else: ?>
                <div style="width:44px; height:44px; display:flex; align-items:center; justify-content:center; font-weight:700; background:linear-gradient(135deg,#00c6ff,#1d72f3); color:#071026; border-radius:8px;"><?php echo htmlspecialchars($initials); ?></div>
              <?php endif; ?>
            </div>
            <div class="meta-text">
              <div class="name"><?php echo htmlspecialchars($name); ?></div>
              <div class="email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
            </div>
          </div>

          <a class="item" href="#" id="profileView" role="menuitem">Profile</a>
          <a class="item" href="#" id="settingsView" role="menuitem">Settings</a>

          <div class="signout">
            <a class="item" href="api/logout.php" role="menuitem" style="width:100%; justify-content:center; background:linear-gradient(90deg,#ff5f6d,#ff9472); color:#fff; border-radius:8px;">Sign out</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- summary -->
  <div class="grid" id="summary">
    <div class="summary-card card" id="card-sales">
      <h4>Sales</h4>
      <div class="summary-value" id="sales-val">Loading...</div>
      <div class="small-muted"><span id="sales-delta" class="delta">+0%</span> vs last week</div>
      <svg id="sales-spark" class="sparkline" viewBox="0 0 100 40" preserveAspectRatio="none"></svg>
    </div>

    <div class="summary-card card" id="card-marketing">
      <h4>Marketing</h4>
      <div class="summary-value" id="marketing-val">Loading...</div>
      <div class="small-muted"><span id="marketing-delta" class="delta">+0%</span> vs last week</div>
      <svg id="marketing-spark" class="sparkline" viewBox="0 0 100 40" preserveAspectRatio="none"></svg>
    </div>

    <div class="summary-card card" id="card-visitors">
      <h4>Visitors</h4>
      <div class="summary-value" id="visitors-val">Loading...</div>
      <div class="small-muted"><span id="visitors-delta" class="delta">+0%</span> vs last week</div>
      <svg id="visitors-spark" class="sparkline" viewBox="0 0 100 40" preserveAspectRatio="none"></svg>
    </div>
  </div>

  <div class="card table" style="margin-top:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
      <strong>Report Rows</strong>
      <div class="actions">
        <label class="btn btn-primary" for="import-file" style="cursor:pointer;">Import Excel</label>
        <input id="import-file" type="file" accept=".xls,.xlsx" />
        <button id="export-xlsx" class="btn btn-ghost">Export Excel</button>
        <button id="generate-pdf" class="btn btn-ghost">Generate PDF</button>
      </div>
    </div>

    <table id="reports-table" style="margin-top:12px;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Category</th>
          <th>Source</th>
          <th style="text-align:right;">Amount</th>
          <th style="text-align:right;">Visitors</th>
          <th style="text-align:center; width:110px;">Actions</th>
        </tr>
      </thead>
      <tbody id="reports-body"><tr><td colspan="6" style="text-align:center; padding:18px;">Loading...</td></tr></tbody>
    </table>
  </div>

  <!-- Profile Modal -->
  <div id="profileModalBackdrop" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3 id="profileModalTitle">Your Profile</h3>
        <button class="close-btn" id="closeProfileModal" aria-label="Close profile modal">&times;</button>
      </div>
      <div class="muted">View and edit your profile information.</div>

      <div style="display:flex; gap:12px; align-items:center; margin:12px 0;">
        <div style="width:72px; height:72px; border-radius:10px; overflow:hidden;">
          <?php if ($avatar): ?>
            <img class="profile-modal-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar"
                 onerror="replaceWithInitials(this, '<?php echo htmlspecialchars($initials); ?>', 'modal')" />
          <?php else: ?>
            <div style="width:72px; height:72px; display:flex; align-items:center; justify-content:center; font-weight:700; background:linear-gradient(135deg,#00c6ff,#1d72f3); color:#071026; border-radius:10px;"><?php echo htmlspecialchars($initials); ?></div>
          <?php endif; ?>
        </div>
        <div style="flex:1;">
          <div style="font-weight:700; font-size:16px;"><?php echo htmlspecialchars($name); ?></div>
          <div class="small-muted"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
        </div>
      </div>

      <form id="profileForm">
        <label for="profileName">Name</label>
        <input id="profileName" name="name" type="text" value="<?php echo htmlspecialchars($name); ?>" />

        <label for="profileEmail" style="margin-top:10px;">Email</label>
        <input id="profileEmail" name="email" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled />

        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" id="cancelProfile">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Settings Modal -->
  <div id="settingsModalBackdrop" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3 id="settingsModalTitle">Settings</h3>
        <button class="close-btn" id="closeSettingsModal" aria-label="Close settings modal">&times;</button>
      </div>
      <div class="muted">Application preferences</div>

      <form id="settingsForm" style="margin-top:12px;">
        <label for="prefCurrency">Default currency</label>
        <select id="prefCurrency" name="currency">
          <option value="PHP">PHP - Philippine Peso</option>
          <option value="USD">USD - US Dollar</option>
          <option value="EUR">EUR - Euro</option>
        </select>

        <label for="prefRows" style="margin-top:10px;">Rows per page</label>
        <select id="prefRows" name="rows">
          <option value="10">10</option>
          <option value="25" selected>25</option>
          <option value="50">50</option>
        </select>

        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" id="cancelSettings">Cancel</button>
          <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirm modal (used for delete) -->
  <div id="confirmBackdrop" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
      <h3 id="confirmTitle">Confirm action</h3>
      <div class="muted" id="confirmText" style="margin:8px 0 12px 0;">Are you sure?</div>
      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button id="confirmCancel" class="btn btn-ghost">Cancel</button>
        <button id="confirmOk" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>

  <!-- toast container -->
  <div id="toastWrap" aria-live="polite" aria-atomic="true"></div>

<script>
// ---------------- Avatar fallback helper ----------------
function replaceWithInitials(imgEl, initials, type) {
  try {
    const wrapper = document.createElement('div');
    // compute size from parent container to preserve layout
    const parent = imgEl.parentElement || imgEl;

    // set visual style based on type
    wrapper.style.display = 'inline-flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.justifyContent = 'center';
    wrapper.style.fontWeight = '700';
    wrapper.style.background = 'linear-gradient(135deg,#00c6ff,#1d72f3)';
    wrapper.style.color = '#071026';
    wrapper.style.overflow = 'hidden';

    if (type === 'modal') {
      wrapper.style.width = '72px';
      wrapper.style.height = '72px';
      wrapper.style.borderRadius = '10px';
      wrapper.style.fontSize = '22px';
    } else if (type === 'rounded') {
      wrapper.style.width = '44px';
      wrapper.style.height = '44px';
      wrapper.style.borderRadius = '8px';
      wrapper.style.fontSize = '14px';
    } else { // default circle
      wrapper.style.width = '48px';
      wrapper.style.height = '48px';
      wrapper.style.borderRadius = '50%';
      wrapper.style.fontSize = '16px';
    }

    wrapper.textContent = initials || '';
    // replace image element with the wrapper
    imgEl.parentNode.replaceChild(wrapper, imgEl);
  } catch (err) {
    imgEl.style.display = 'none';
  }
}

// ---------------- profile menu + modal wiring ----------------
const avatarBtn = document.getElementById('avatarBtn');
const profileMenu = document.getElementById('profileMenu');

function openProfileMenu() {
  profileMenu.classList.add('open');
  profileMenu.setAttribute('aria-hidden', 'false');
  avatarBtn.setAttribute('aria-expanded', 'true');
}
function closeProfileMenu() {
  profileMenu.classList.remove('open');
  profileMenu.setAttribute('aria-hidden', 'true');
  avatarBtn.setAttribute('aria-expanded', 'false');
}

avatarBtn.addEventListener('click', function(e){
  e.stopPropagation();
  if (profileMenu.classList.contains('open')) closeProfileMenu();
  else openProfileMenu();
});

// close when clicking outside
document.addEventListener('click', function(e){
  if (!profileMenu.contains(e.target) && !avatarBtn.contains(e.target)) {
    closeProfileMenu();
  }
});

// close on Escape
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') {
    closeProfileMenu();
    closeProfileModal();
    closeSettingsModal();
    // also hide confirm if open
    closeConfirm();
  }
});

/* Modal wiring */
const profileViewBtn = document.getElementById('profileView');
const settingsViewBtn = document.getElementById('settingsView');

const profileModalBackdrop = document.getElementById('profileModalBackdrop');
const settingsModalBackdrop = document.getElementById('settingsModalBackdrop');

const closeProfileModalBtn = document.getElementById('closeProfileModal');
const closeSettingsModalBtn = document.getElementById('closeSettingsModal');

function openProfileModal() {
  closeProfileMenu();
  profileModalBackdrop.classList.add('open');
  profileModalBackdrop.setAttribute('aria-hidden', 'false');
  // focus the first field
  setTimeout(()=> document.getElementById('profileName').focus(), 80);
}
function closeProfileModal() {
  profileModalBackdrop.classList.remove('open');
  profileModalBackdrop.setAttribute('aria-hidden', 'true');
}

// ---------- open settings modal and populate current settings ----------
async function openSettingsModal() {
  closeProfileMenu();
  // fetch saved settings
  try {
    const res = await fetch('api/get_settings.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (data.success && data.data) {
      const s = data.data;
      document.getElementById('prefCurrency').value = s.currency || 'PHP';
      document.getElementById('prefRows').value = s.rows_per_page || 25;
    }
  } catch(err) {
    console.warn('Could not load settings:', err);
  }

  settingsModalBackdrop.classList.add('open');
  settingsModalBackdrop.setAttribute('aria-hidden', 'false');
  setTimeout(()=> document.getElementById('prefCurrency').focus(), 80);
}

// ---------- settings form submit (save to server) ----------
document.getElementById('settingsForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const currency = document.getElementById('prefCurrency').value;
  const rows = document.getElementById('prefRows').value;

  try {
    const res = await fetch('api/update_settings.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({currency, rows})
    });

    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch(err){ console.error('update_settings: invalid JSON', text); showToast('Server returned invalid response.', 'error'); return; }

    if (res.ok && data && data.success) {
      showToast('Settings saved.', 'success');
      closeSettingsModal();
    } else {
      const msg = data && data.message ? data.message : ('HTTP ' + res.status);
      showToast('Error saving settings: ' + msg, 'error');
    }
  } catch(err) {
    console.error(err);
    showToast('Network error while saving settings.', 'error');
  }
});

function closeSettingsModal() {
  settingsModalBackdrop.classList.remove('open');
  settingsModalBackdrop.setAttribute('aria-hidden', 'true');
}

// ---------- profile form submit (save name) ----------
document.getElementById('profileForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const name = document.getElementById('profileName').value.trim();
  if (!name) { showToast('Please enter a name.', 'error'); return; }

  try {
    const res = await fetch('api/update_profile.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({name})
    });

    // handle non-JSON responses gracefully
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch(err){ console.error('update_profile: invalid JSON', text); showToast('Server returned invalid response.', 'error'); return; }

    if (data && data.success) {
      // update UI (top-right name)
      const topName = document.querySelector('.top-right-name');
      if (topName) topName.textContent = data.name;
      showToast('Profile updated.', 'success');
      closeProfileModal();
    } else {
      showToast('Error updating profile: ' + (data && data.message ? data.message : 'unknown'), 'error');
    }
  } catch(err) {
    console.error(err);
    showToast('Network error while updating profile.', 'error');
  }
});

// open modal when menu items clicked
profileViewBtn.addEventListener('click', function(e){
  e.preventDefault();
  openProfileModal();
});
settingsViewBtn.addEventListener('click', function(e){
  e.preventDefault();
  openSettingsModal();
});

// close modal when clicking backdrop (outside modal)
profileModalBackdrop.addEventListener('click', function(e){
  if (e.target === profileModalBackdrop) closeProfileModal();
});
settingsModalBackdrop.addEventListener('click', function(e){
  if (e.target === settingsModalBackdrop) closeSettingsModal();
});

// close buttons
closeProfileModalBtn.addEventListener('click', closeProfileModal);
closeSettingsModalBtn.addEventListener('click', closeSettingsModal);

// Cancel buttons inside forms
document.getElementById('cancelProfile').addEventListener('click', function(e){
  e.preventDefault();
  closeProfileModal();
});
document.getElementById('cancelSettings').addEventListener('click', function(e){
  e.preventDefault();
  closeSettingsModal();
});

// ---------------- Confirm + Toast helpers ----------------
const confirmBackdrop = document.getElementById('confirmBackdrop');
const confirmOkBtn = document.getElementById('confirmOk');
const confirmCancelBtn = document.getElementById('confirmCancel');
const confirmTextEl = document.getElementById('confirmText');

function openConfirm(text = 'Are you sure?') {
  confirmTextEl.textContent = text;
  confirmBackdrop.classList.add('open');
  confirmBackdrop.setAttribute('aria-hidden', 'false');
}

function closeConfirm() {
  confirmBackdrop.classList.remove('open');
  confirmBackdrop.setAttribute('aria-hidden', 'true');
}

function showConfirm(text = 'Are you sure?') {
  return new Promise((resolve) => {
    openConfirm(text);

    function cleanup(result) {
      closeConfirm();
      confirmOkBtn.removeEventListener('click', onOk);
      confirmCancelBtn.removeEventListener('click', onCancel);
      document.removeEventListener('keydown', onKey);
      resolve(result);
    }
    function onOk(e){ e.preventDefault(); cleanup(true); }
    function onCancel(e){ e.preventDefault(); cleanup(false); }
    function onKey(e){ if (e.key === 'Escape') cleanup(false); }

    confirmOkBtn.addEventListener('click', onOk);
    confirmCancelBtn.addEventListener('click', onCancel);
    document.addEventListener('keydown', onKey);
  });
}

function showToast(message, type = 'info', ms = 3800) {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast ' + (type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info'));
  t.textContent = message;
  wrap.appendChild(t);

  // auto dismiss
  const timeout = setTimeout(()=> {
    t.style.transition = 'opacity .28s ease, transform .28s ease';
    t.style.opacity = '0';
    t.style.transform = 'translateY(8px)';
    setTimeout(()=> { try{ t.remove(); }catch(e){} }, 300);
  }, ms);

  // return an object allowing manual removal
  return {
    remove() {
      clearTimeout(timeout);
      try { t.remove(); } catch(e){}
    }
  };
}

// ---------------- Sparklines & deltas ----------------
function setDelta(elId, value) {
  const el = document.getElementById(elId);
  if (!el) return;
  const v = Number(value) || 0;
  const positive = v >= 0;
  el.textContent = (positive ? '+' : '') + v + '%';
  el.classList.remove('up','down');
  el.classList.add(positive ? 'up' : 'down');
}

function drawSparkline(svgId, values, color) {
  const svg = document.getElementById(svgId);
  if (!svg || !Array.isArray(values) || values.length === 0) return;

  const w = 100, h = 40;
  const max = Math.max(...values);
  const min = Math.min(...values);
  const range = max - min || 1;
  const step = (w) / Math.max(values.length - 1, 1);

  let d = '';
  values.forEach((v, i) => {
    const x = (i * step);
    const y = h - ((v - min) / range) * h;
    d += (i === 0 ? `M ${x.toFixed(2)} ${y.toFixed(2)}` : ` L ${x.toFixed(2)} ${y.toFixed(2)}`);
  });

  const fillPath = `${d} L ${w} ${h} L 0 ${h} Z`;

  svg.innerHTML = `
    <defs>
      <linearGradient id="${svgId}-grad" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0%" stop-color="${color}" stop-opacity="0.16"></stop>
        <stop offset="100%" stop-color="${color}" stop-opacity="0.02"></stop>
      </linearGradient>
    </defs>
    <path d="${fillPath}" fill="url(#${svgId}-grad)"></path>
    <path d="${d}" stroke="${color}" fill="none"></path>
  `;
}

/* existing dashboard functions (import/export/pdf + fetchSummary) */
async function fetchSummary(){
    const res = await fetch('api/get_summary.php', { credentials: 'same-origin' });
    if (!res.ok) { console.error('Failed to fetch summary', res.status); showToast('Failed to load summary.', 'error'); return; }
    const data = await res.json();

    // totals
    const sales = Number(data.sales_total ?? 0);
    const marketing = Number(data.marketing_total ?? 0);
    const visitors = Number(data.total_visitors ?? 0);

    document.getElementById('sales-val').innerText = sales.toLocaleString();
    document.getElementById('marketing-val').innerText = marketing.toLocaleString();
    document.getElementById('visitors-val').innerText = visitors.toLocaleString();

    // deltas (from API if present) - fallbacks are 0
    setDelta('sales-delta', Number(data.sales_delta ?? 0));
    setDelta('marketing-delta', Number(data.marketing_delta ?? 0));
    setDelta('visitors-delta', Number(data.visitors_delta ?? 0));

    // choose colors
    const accent = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#1d72f3';
    const accent2 = '#00c6ff';

    // sparkline arrays (from API if present) - fallbacks to reasonable mock arrays
    drawSparkline('sales-spark', Array.isArray(data.sales_trend) ? data.sales_trend : [4,6,5,8,7,9,11], accent);
    drawSparkline('marketing-spark', Array.isArray(data.marketing_trend) ? data.marketing_trend : [3,4,6,5,4,7,6], accent);
    drawSparkline('visitors-spark', Array.isArray(data.visitors_trend) ? data.visitors_trend : [100,120,150,130,160,200,210], accent2);

    // table rows
    const body = document.getElementById('reports-body');
    body.innerHTML = '';
    if (Array.isArray(data.reports) && data.reports.length){
        data.reports.forEach(r=>{
            // NOTE: r.id is required for deletion. Ensure your API returns id for each row.
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${r.date}</td>
              <td>${r.category}</td>
              <td>${r.source || ''}</td>
              <td style="text-align:right;">${Number(r.amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
              <td style="text-align:right;">${r.visitors || ''}</td>
              <td style="text-align:center;">
                <button class="btn-danger" data-id="${r.id}" title="Delete row" aria-label="Delete report row">Delete</button>
              </td>
            `;
            body.appendChild(tr);
        });
    } else {
        body.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:18px;">No data</td></tr>';
    }
}

// ------------- delete logic -------------
async function deleteReport(id, btnEl = null) {
  const ok = await showConfirm('Delete this report row? This action cannot be undone.');
  if (!ok) return false;

  // disable the button if passed
  if (btnEl) {
    btnEl.setAttribute('disabled', 'true');
    btnEl.dataset.origText = btnEl.innerHTML;
    btnEl.innerHTML = 'Deleting...';
  }

  try {
    const res = await fetch('api/delete_report.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({ id })
    });

    // read raw text to avoid parse errors when PHP emits warnings
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (err) {
      console.error('deleteReport: invalid JSON response:', text);
      showToast('Server returned an invalid response. See console.', 'error');
      return false;
    }

    if (res.ok && data && data.success) {
      showToast('Row deleted', 'success');
      await fetchSummary();
      return true;
    } else {
      const msg = data && data.message ? data.message : ('HTTP ' + res.status);
      showToast('Failed to delete: ' + msg, 'error');
      return false;
    }
  } catch (err) {
    console.error('Network error while deleting:', err);
    showToast('Network error while deleting.', 'error');
    return false;
  } finally {
    if (btnEl) {
      btnEl.removeAttribute('disabled');
      if (btnEl.dataset.origText) btnEl.innerHTML = btnEl.dataset.origText;
    }
  }
}

// event delegation for delete buttons
document.getElementById('reports-table').addEventListener('click', function(e){
  const btn = e.target.closest('.btn-danger');
  if (!btn) return;
  const id = btn.getAttribute('data-id');
  if (!id) return;
  deleteReport(id, btn);
});

// -------- Import / Export / PDF handlers (toasts) --------
document.getElementById('import-file').addEventListener('change', async function(e){
    const f = this.files[0];
    if (!f) return;
    const fd = new FormData();
    fd.append('excel', f);

    const uploadingToast = showToast('Uploading file...', 'info', 60000);

    try {
      const res = await fetch('api/import_excel.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const text = await res.text();
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch(err){ console.error('import_excel: invalid JSON', text); }

      if (res.ok && data && data.success) {
        showToast(data.message || 'Imported', 'success');
        fetchSummary();
      } else {
        const msg = data && data.message ? data.message : ('HTTP ' + res.status);
        showToast('Import failed: ' + msg, 'error');
      }
    } catch (err) {
      console.error('Import error', err);
      showToast('Network error during import.', 'error');
    } finally {
      try { if (uploadingToast && uploadingToast.remove) uploadingToast.remove(); } catch(e){}
      // reset file input so same file can be repicked if needed
      this.value = '';
    }
});

document.getElementById('export-xlsx').addEventListener('click', function(){
    window.location = 'api/export_excel.php';
});

document.getElementById('generate-pdf').addEventListener('click', function(){
    window.open('api/generate_pdf.php', '_blank');
});

fetchSummary();
</script>

</body>
</html>
