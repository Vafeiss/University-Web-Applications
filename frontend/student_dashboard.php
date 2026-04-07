<?php
/* Name: student_dashboard.php
   Description: Main dashboard page for students after login. Shows assigned advisor and allows communication. (Not merged)
   Paraskevas Vafeiadis
   29-Mar-2026 v0.1
   files in use: init.php, UsersClass.php, StudentClass.php
   */
require_once('init.php');
require_once('../backend/modules/UsersClass.php');
require_once('../backend/modules/StudentClass.php');

$user = new Users();
$user->Check_Session('Student');

$activeSection = $_GET['section'] ?? 'communications';

// Get current student info and assigned advisor for dashboard rendering.
$studentUserId = (int)($_SESSION['UserID'] ?? 0);
$currentStudent = [];
$myAdvisor = null;

//if user id correct get the student info and advisor info 
if ($studentUserId > 0) {
  try {
    $studentModule = new StudentClass();
    $currentStudent = $studentModule->getStudentInfo($studentUserId);
    $myAdvisor = $studentModule->getStudentAdvisor($studentUserId);
  } catch (Throwable $e) {
    error_log('Error loading student info: ' . $e->getMessage());
    $currentStudent = [];
    $myAdvisor = null;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }

  /* navbar css */
  .top-navbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 1.5rem; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
  .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #ede9fe; color: #6d28d9; font-weight: 600; display: flex; align-items: center; justify-content: center; font-size: .9rem; }
  .welcome-text { font-weight: 750; font-size: 28px; color: #555; }
  .logo { height: 70px; width: auto; object-fit: contain; }

  /* tab bar css */
  .tab-bar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 1.5rem; display: flex; gap: .25rem; justify-content: center; }
  .tab-btn { border: none; background: none; padding: 1rem .75rem; font-size: .95rem; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; display: flex; align-items: center; gap: .4rem; transition: color .15s; }
  .tab-btn:hover { color: #111827; }
  .tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; font-weight: 500; }

  /* section card css */
  .section-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 1.5rem; }
  .section-panel { display: none; }
  .section-panel.active { display: block; }

  /* ── Communications layout ── */
  .comm-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
    min-height: 560px;
  }

  /* Chat messages */
  .comm-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #fff;
    min-height: 280px;
    max-height: 420px;
  }
  .comm-messages::-webkit-scrollbar { width: 4px; }
  .comm-messages::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

  .msg-bubble-wrap { display: flex; flex-direction: column; gap: 4px; }
  .msg-bubble-wrap.from-student { align-items: flex-end; }
  .msg-bubble-wrap.from-advisor { align-items: flex-start; }

  .msg-meta { font-size: .75rem; color: #6b7280; display: flex; align-items: center; gap: 8px; }
  .msg-meta .msg-sender { font-weight: 600; color: #374151; }

  .msg-bubble {
    padding: 10px 14px;
    border-radius: 12px;
    font-size: .875rem;
    line-height: 1.5;
    max-width: 75%;
    word-break: break-word;
  }
  .from-student .msg-bubble { background: #ede9fe; color: #1e1b4b; border-top-right-radius: 4px; }
  .from-advisor .msg-bubble { background: #f3f4f6; color: #111827; border-top-left-radius: 4px; }

  /* empty / placeholder states */
  .comm-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    gap: 10px;
    padding: 40px;
    text-align: center;
  }
  .comm-placeholder i { font-size: 2.5rem; opacity: .4; }
  .comm-placeholder p { font-size: .875rem; margin: 0; }

  /* Compose area */
  .comm-compose {
    border-top: 1px solid #e5e7eb;
    padding: 16px 20px;
    background: #fff;
  }
  .comm-compose label { font-size: .82rem; color: #374151; font-weight: 500; margin-bottom: 8px; display: block; }
  .comm-compose textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: .875rem;
    font-family: inherit;
    resize: vertical;
    min-height: 90px;
    color: #111827;
    transition: border-color .15s;
    outline: none;
  }
  .comm-compose textarea:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
  .comm-compose-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 8px;
  }
  .comm-word-count { font-size: .78rem; color: #6b7280; }
  .comm-word-count.over { color: #ef4444; font-weight: 600; }
  .btn-send {
    background: #4f46e5;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-size: .875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: opacity .15s, transform .15s;
  }
  .btn-send:hover:not(:disabled) { opacity: .88; transform: translateY(-1px); }
  .btn-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }

  .comm-loading { text-align: center; color: #9ca3af; font-size: .85rem; padding: 24px; }
</style>
</head>
<body>

<!-- top nav bar with logo and txt in the middle -->
<header class="top-navbar">

  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text">Welcome To Advicut!👋</span>
  </div>
  <!-- help icon, user , logout button -->
  <div class="d-flex align-items-center gap-3">
    <i class="bi bi-question-circle text-secondary fs-5" title="Help"></i>
    <div class="user-avatar">S</div>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.href='changepassword.php'" title="Change Password">
      <i class="bi bi-shield-lock me-1"></i> Password
    </button>
    <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
      <input type="hidden" name="action" value="/logout">
      <button class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </button>
    </form>
  </div>

</header>

<!-- tab bar (active tabs)-->
<div class="tab-bar">
  <button type="button" class="tab-btn <?= $activeSection === 'communications' ? 'active' : '' ?>" data-section="communications">
    <i class="bi bi-chat-dots"></i> Communications
  </button>
</div>

<main class="container-fluid py-4 px-4" style="max-width: 1100px;">

<!-- SECTION PANEL  -->
<div class="section-panel <?= $activeSection === 'communications' ? 'active' : '' ?>" id="section-communications">

  <div class="section-card">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h5 class="mb-0 fw-semibold">Communications</h5>
      <p class="text-muted mb-0" style="font-size:.85rem;">Send and receive messages from your academic advisor.</p>
    </div>
  </div>

  <div class="comm-layout">

    <?php if (empty($myAdvisor)): ?>

      <!-- no advisor assigned yet -->
      <div class="comm-placeholder">
        <i class="bi bi-person-x"></i>
        <p>You don't have an advisor assigned yet.<br>Please contact the administration.</p>
      </div>

    <?php else:
      $advisorName = htmlspecialchars($myAdvisor['First_name'] . ' ' . $myAdvisor['Last_Name']);
      $studentMessageUserId = (int)($currentStudent['User_ID'] ?? $studentUserId);
    ?>

      <!-- messages -->
      <div class="comm-messages" id="commMessages">
        <div class="comm-loading">Loading messages…</div>
      </div>

      <!-- compose -->
      <div class="comm-compose">
        <label for="commTextarea">Send a message to <?= $advisorName ?> <span class="text-muted">(200 words max)</span></label>
        <textarea id="commTextarea"
                  placeholder="Type your question or message here…"
                  maxlength="2000"
                  oninput="commWordCount(this)"></textarea>
        <div class="comm-compose-footer">
          <span class="comm-word-count" id="commWordCount">0 / 200 words</span>
          <button type="button" class="btn-send" id="commSendBtn" onclick="commSend()" disabled>
            <i class="bi bi-send-fill"></i> Send Message
          </button>
        </div>
      </div>

      <!-- pass student ID to JS -->
      <script>window.commStudentId = <?= json_encode($studentMessageUserId) ?>;</script>

    <?php endif; ?>

  </div>

  </div>

</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const COMM_MAX_WORDS = 200;
let commLoaded = false;

// auto load when the Communications tab becomes active
document.addEventListener('DOMContentLoaded', function () {
  //when the communications tab is clicked load the messages 
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      if (this.dataset.section === 'communications' && !commLoaded) {
        commLoad();
      }
    });
  });

  // also load immediately if this tab is already active on page load
  if (document.getElementById('section-communications')?.classList.contains('active')) {
    commLoad();
  }
});

//load messages for the communications tab
function commLoad() {
  if (!window.commStudentId) return;
  commLoaded = true;
  commFetchThread();
}

//fetch the message thread between the student and the advisor
function commFetchThread() {
  const box = document.getElementById('commMessages');
  if (!box) return;

  box.innerHTML = `<div class="comm-loading">Loading messages…</div>`;

  const fd = new FormData();
  fd.append('action', '/student/message/thread');
  fd.append('student_id', window.commStudentId);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(messages => {
      if (!messages.length) {
        box.innerHTML = `
          <div class="comm-placeholder">
            <i class="bi bi-chat"></i>
            <p>No messages yet. Send your first message to your advisor!</p>
          </div>`;
        return;
      }
      box.innerHTML = messages.map(m => commBubble(m)).join('');
      box.scrollTop = box.scrollHeight;

      // Mark messages as read
      const fd = new FormData();
      fd.append('action', '/student/message/read');
      fd.append('student_id', window.commStudentId);
      fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd }).catch(() => {});
    })
    .catch(() => {
      box.innerHTML = `
        <div class="comm-placeholder" style="color:#ef4444">
          <i class="bi bi-exclamation-circle"></i>
          <p>Failed to load messages. Please refresh the page.</p>
        </div>`;
    });
}

//create the html for every message 
function commBubble(m) {
  const isStudent   = m.sender === 'student';
  const side        = isStudent ? 'from-student' : 'from-advisor';
  const senderLabel = isStudent ? 'You' : (m.sender_name || 'Advisor');
  const time        = m.sent_at ? new Date(m.sent_at).toLocaleString() : '';

  return `
    <div class="msg-bubble-wrap ${side}">
      <div class="msg-meta">
        <span class="msg-sender">${commEsc(senderLabel)}</span>
        <span>${commEsc(time)}</span>
      </div>
      <div class="msg-bubble">${commEsc(m.body)}</div>
    </div>`;
}

//have a count for the words in the text area 
function commWordCount(textarea) {
  const words = textarea.value.trim() === '' ? 0
    : textarea.value.trim().split(/\s+/).length;
  const el  = document.getElementById('commWordCount');
  const btn = document.getElementById('commSendBtn');
  el.textContent = `${words} / ${COMM_MAX_WORDS} words`;
  el.classList.toggle('over', words > COMM_MAX_WORDS);
  btn.disabled = (words === 0 || words > COMM_MAX_WORDS);
}

//send the message to the advisor and update the database 
function commSend() {
  const textarea = document.getElementById('commTextarea');
  const body     = textarea.value.trim();
  if (!body || !window.commStudentId) return;

  const btn = document.getElementById('commSendBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending…';

  const fd = new FormData();
  fd.append('action', '/student/message/send');
  fd.append('student_id', window.commStudentId);
  fd.append('message_body', body);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        textarea.value = '';
        commWordCount(textarea);
        commFetchThread();           // reload thread to show new message
      } else {
        alert(data.error || 'Failed to send message. Please try again.');
        btn.disabled = false;
      }
    })
    .catch(() => {
      alert('Network error. Please try again.');
      btn.disabled = false;
    })
    .finally(() => {
      btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Message';
    });
}

//xss attack
function commEsc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

 </body>
 </html>
   
