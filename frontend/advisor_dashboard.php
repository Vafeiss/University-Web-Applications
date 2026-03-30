<?php
/* Name: Advisor Dashbaord
   Description: Main landing page for advisors after login. Shows assigned students and allows communication. (For now)
   Paraskevas Vafeiadis
   27-Mar-2026 v0.1
   files in use: init.php, AdvisorClass.php, AdvisorController.php
  */

require_once('init.php');
require_once("../backend/modules/UsersClass.php");
require_once("../backend/modules/AdvisorClass.php");
$user = new Users();
$user->Check_Session("Advisor");
$activeSection = $_GET['section'] ?? 'communications';

//load advisor's assigned students from backend
$advisorId = $_SESSION['UserID'] ?? null;
$myStudents = [];

//get assigned students for this advisor
if ($advisorId) {
    try {
    $advisorModule = new AdvisorClass();
    $myStudents = $advisorModule->getAssignedStudents((int)$advisorId);
  } catch (Throwable $e) {
        error_log("Error loading students: " . $e->getMessage());
        $myStudents = [];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administrator Portal</title>
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
    grid-template-columns: 300px 1fr;
    gap: 0;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
    min-height: 560px;
  }

  /* Left: student list */
  .comm-sidebar {
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
  }
  .comm-sidebar-header {
    padding: 18px 16px 14px;
    border-bottom: 1px solid #e5e7eb;
  }
  .comm-sidebar-header h6 {
    font-weight: 700;
    font-size: .9rem;
    color: #111827;
    margin: 0;
  }
  .comm-student-list {
    overflow-y: auto;
    flex: 1;
  }
  .comm-student-list::-webkit-scrollbar { width: 4px; }
  .comm-student-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

  .comm-student-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background .12s;
  }
  .comm-student-item:hover { background: #f9fafb; }
  .comm-student-item.active { background: #ede9fe; border-left: 3px solid #4f46e5; }
  .comm-student-item.active .comm-stu-name { color: #4f46e5; }
  .comm-stu-name  { font-weight: 600; font-size: .9rem; color: #111827; }
  .comm-stu-id    { font-size: .78rem; color: #6b7280; margin-top: 2px; }
  .comm-unread {
    background: #ef4444;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    flex-shrink: 0;
  }

  /* Right: conversation pane */
  .comm-pane {
    display: flex;
    flex-direction: column;
  }
  .comm-pane-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #e5e7eb;
  }
  .comm-pane-header h6 { font-weight: 700; font-size: .95rem; color: #111827; margin: 0 0 2px; }
  .comm-pane-header small { color: #6b7280; font-size: .8rem; }

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
    max-height: 380px;
  }
  .comm-messages::-webkit-scrollbar { width: 4px; }
  .comm-messages::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

  .msg-bubble-wrap { display: flex; flex-direction: column; gap: 4px; }
  .msg-bubble-wrap.from-student { align-items: flex-start; }
  .msg-bubble-wrap.from-advisor { align-items: flex-end; }

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
  .from-student .msg-bubble { background: #f3f4f6; color: #111827; border-top-left-radius: 4px; }
  .from-advisor .msg-bubble { background: #ede9fe; color: #1e1b4b; border-top-right-radius: 4px; }

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

  @media (max-width: 680px) {
    .comm-layout { grid-template-columns: 1fr; }
    .comm-sidebar { border-right: none; border-bottom: 1px solid #e5e7eb; max-height: 220px; }
  }
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
    <div class="user-avatar">A</div>
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

<!-- section panel  -->
<div class="section-panel <?= $activeSection === 'communications' ? 'active' : '' ?>" id="section-communications">

  <div class="section-card">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h5 class="mb-0 fw-semibold">Student Communications</h5>
      <p class="text-muted mb-0" style="font-size:.85rem;">Select a student to view and respond to their messages.</p>
    </div>
  </div>

  <div class="comm-layout">

    <!-- left student list -->
    <div class="comm-sidebar">
      <div class="comm-sidebar-header">
        <h6>Your Students</h6>
      </div>
      <div class="comm-student-list" id="commStudentList">
        <!-- get students into the side -->
        <?php
          $hasStudents = false;
          $studentRows = is_array($myStudents) ? $myStudents : [];

          foreach ($studentRows as $s):
            $hasStudents = true;
            $unread = (int)($s['unread_count'] ?? 0);
        ?>
        <div class="comm-student-item"
             data-student-id="<?= htmlspecialchars($s['User_ID']) ?>"
             data-external-id="<?= htmlspecialchars($s['StuExternal_ID']) ?>"
             data-name="<?= htmlspecialchars($s['First_name'] . ' ' . $s['Last_Name']) ?>"
             onclick="commSelectStudent(this)">
          <div>
            <div class="comm-stu-name"><?= htmlspecialchars($s['First_name'] . ' ' . $s['Last_Name']) ?></div>
            <div class="comm-stu-id"><?= htmlspecialchars($s['StuExternal_ID']) ?></div>
          </div>
          <!-- show this badge if there are unread messages-->
          <?php if ($unread > 0): ?>
          <span class="comm-unread" id="badge-<?= htmlspecialchars($s['User_ID']) ?>"><?= $unread ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
          <!-- if not students found show this -->
        <?php if (!$hasStudents): ?>
          <div class="comm-placeholder" style="min-height:120px">
            <i class="bi bi-people"></i>
            <p>No students assigned yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- conversation panel -->
    <div class="comm-pane" id="commPane">

      <!-- show this when no student selected -->
      <div class="comm-placeholder" id="commPlaceholder">
        <i class="bi bi-chat-dots"></i>
        <p>Select a student from the list to view your conversation.</p>
      </div>

      <!-- show this when student is selected -->
      <div id="commConversation" style="display:none;flex-direction:column;flex:1;">

        <div class="comm-pane-header">
          <h6 id="commConvTitle">Conversation</h6>
          <small id="commConvId" class="text-muted"></small>
        </div>

        <div class="comm-messages" id="commMessages">
          <div class="comm-loading">Loading messages…</div>
        </div>
          <!-- Count word for advisor to keep track -->
        <div class="comm-compose">
          <label for="commTextarea">Send a message <span class="text-muted">(200 words max)</span></label>
          <textarea id="commTextarea" placeholder="Type your message here…" maxlength="2000" oninput="commWordCount(this)"></textarea>
          <div class="comm-compose-footer">
            <span class="comm-word-count" id="commWordCount">0 / 200 words</span>
            <button type="button" class="btn-send" id="commSendBtn" onclick="commSend()" disabled>
              <i class="bi bi-send-fill"></i> Send Message
            </button>
          </div>
        </div>

      </div>

    </div>

  </div>

  </div>

</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

let commCurrentStudentId   = null;
let commCurrentStudentName = '';
let commCurrentExternalId  = '';
const COMM_MAX_WORDS       = 200;

//get the student info and load the conversation of the selected student. then mark it as read
function commSelectStudent(el) {
  //active status
  document.querySelectorAll('.comm-student-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');

  commCurrentStudentId   = el.dataset.studentId;
  commCurrentStudentName = el.dataset.name;
  commCurrentExternalId  = el.dataset.externalId;

  //show the conversation pane
  document.getElementById('commPlaceholder').style.display    = 'none';
  const conv = document.getElementById('commConversation');
  conv.style.display = 'flex';

  document.getElementById('commConvTitle').textContent = 'Conversation with ' + commCurrentStudentName;
  document.getElementById('commConvId').textContent    = commCurrentExternalId;

  //clear compose
  document.getElementById('commTextarea').value = '';
  commWordCount(document.getElementById('commTextarea'));

  //mark as read
  commMarkRead(commCurrentStudentId, el);

  //load thread
  commLoadThread(commCurrentStudentId);
}

//load the conversation thread for the selected student 
function commLoadThread(studentId) {
  const box = document.getElementById('commMessages');
  box.innerHTML = '<div class="comm-loading"><i class="bi bi-arrow-repeat"></i> Loading…</div>';

  const fd = new FormData();
  fd.append('action', '/message/thread');
  fd.append('student_id', studentId);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(messages => {
      if (!messages || messages.length === 0) {
        box.innerHTML = '<div class="comm-placeholder"><i class="bi bi-chat"></i><p>No messages yet. Send the first one!</p></div>';
        return;
      }
      if (messages.error) {
        box.innerHTML = '<div class="comm-loading text-danger">Failed to load messages.</div>';
        return;
      }
      box.innerHTML = messages.map(m => commBubbleHTML(m)).join('');
      box.scrollTop = box.scrollHeight;
    })
    .catch(err => {
      console.error(err);
      box.innerHTML = '<div class="comm-loading text-danger">Failed to load messages.</div>';
    });
}

//generate the html for each message
function commBubbleHTML(m) {
  const isAdvisor = m.sender === 'advisor';
  const side      = isAdvisor ? 'from-advisor' : 'from-student';
  const senderLabel = isAdvisor ? 'You' : (m.sender_name || 'Student');
  const time = m.sent_at ? new Date(m.sent_at).toLocaleString() : '';

  return `
    <div class="msg-bubble-wrap ${side}">
      <div class="msg-meta">
        <span class="msg-sender">${commEsc(senderLabel)}</span>
        <span>${commEsc(time)}</span>
      </div>
      <div class="msg-bubble">${commEsc(m.body)}</div>
    </div>`;
}

//get a word count of the message being written and disable send button if empty or over 200 words
function commWordCount(textarea) {
  const words = textarea.value.trim() === '' ? 0
    : textarea.value.trim().split(/\s+/).length;
  const el   = document.getElementById('commWordCount');
  const btn  = document.getElementById('commSendBtn');

  el.textContent = `${words} / ${COMM_MAX_WORDS} words`;
  el.classList.toggle('over', words > COMM_MAX_WORDS);
  btn.disabled = (words === 0 || words > COMM_MAX_WORDS);
}

//send the message to the backend and reload the thread on success
function commSend() {
  const textarea = document.getElementById('commTextarea');
  const body     = textarea.value.trim();
  if (!body || !commCurrentStudentId) return;

  const btn = document.getElementById('commSendBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending…';

  const fd = new FormData();
  fd.append('action', '/message/send');
  fd.append('student_id', commCurrentStudentId);
  fd.append('message_body', body);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        textarea.value = '';
        commWordCount(textarea);
        commLoadThread(commCurrentStudentId); //reload full thread
      } else {
        alert(data.error || 'Failed to send message.');
      }
    })
    .catch(() => alert('Network error. Please try again.'))
    .finally(() => {
      btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Message';
    });
}

//mark messages in the backend as read for the selected student and remove the unread badge in the UI
function commMarkRead(studentId, listItem) {
  const badge = document.getElementById('badge-' + studentId);
  if (badge) badge.remove();

  const fd = new FormData();
  fd.append('action', '/message/read');
  fd.append('student_id', studentId);
  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd }).catch(() => {});
}

//prevent XSS 
function commEsc(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

</body>
</html>
