(function () {
  'use strict';

  const COMM_MAX_WORDS = 200;
  let commLoaded = false;
  let studentCalendarLoaded = false;
  let studentCalendarInstance = null;
  let historyDetailsModal = null;

  const bodyEl = document.body;
  const studentCalendarEvents = (function () {
    const dataStr = bodyEl.getAttribute('data-student-calendar-events');
    if (!dataStr) return [];

    try {
      return JSON.parse(dataStr);
    } catch (e) {
      return [];
    }
  })();

  const commStudentId = Number(bodyEl.getAttribute('data-comm-student-id') || 0);

  function openHistoryDetailsModal(reasonText) {
    const content = document.getElementById('historyDetailsText');
    if (!content || !historyDetailsModal) return;

    const cleanReason = String(reasonText ?? '').trim();
    content.textContent = cleanReason !== '' ? cleanReason : '-';
    historyDetailsModal.show();
  }

  function setCalendarReason(buttonId, wrapId, contentId, value) {
    const button = document.getElementById(buttonId);
    const wrap = document.getElementById(wrapId);
    const content = document.getElementById(contentId);

    if (!button || !wrap || !content) return;

    const text = String(value ?? '').trim();
    const hasValue = text !== '' && text !== '-';

    content.textContent = hasValue ? text : '';
    button.style.display = hasValue ? 'inline-flex' : 'none';

    if (!hasValue) {
      const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
      collapse.hide();
    }
  }

  function resetCalendarReasonState(wrapId) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;

    const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
    collapse.hide();
  }

  function renderStudentCalendar() {
    if (studentCalendarLoaded) return;

    const calendarEl = document.getElementById('studentCalendar');
    const modalEl = document.getElementById('studentCalendarModal');
    if (!calendarEl || !modalEl) return;

    const detailsModal = new bootstrap.Modal(modalEl);

    studentCalendarInstance = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      height: 'auto',
      events: studentCalendarEvents,
      eventClick: function (info) {
        const props = info.event.extendedProps || {};
        document.getElementById('calendarModalAdvisor').textContent = props.advisor || '-';
        document.getElementById('calendarModalDate').textContent = props.date || '-';
        document.getElementById('calendarModalTime').textContent = props.time || '-';
        document.getElementById('calendarModalStatus').textContent = props.status || '-';
        setCalendarReason('calendarModalStudentReasonBtn', 'calendarModalStudentReasonWrap', 'calendarModalStudentReason', props.student_reason);
        setCalendarReason('calendarModalAdvisorReasonBtn', 'calendarModalAdvisorReasonWrap', 'calendarModalAdvisorReason', props.advisor_reason);
        resetCalendarReasonState('calendarModalStudentReasonWrap');
        resetCalendarReasonState('calendarModalAdvisorReasonWrap');
        detailsModal.show();
      }
    });

    studentCalendarInstance.render();
    studentCalendarLoaded = true;
  }

  function commLoad() {
    if (!commStudentId) return;
    commLoaded = true;
    commFetchThread();
  }

  function commFetchThread() {
    const box = document.getElementById('commMessages');
    if (!box) return;

    box.innerHTML = '<div class="comm-loading">Loading messages...</div>';

    const fd = new FormData();
    fd.append('action', '/student/message/thread');
    fd.append('student_id', String(commStudentId));

    fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (payload) {
        const messages = Array.isArray(payload)
          ? payload
          : (payload && Array.isArray(payload.data) ? payload.data : []);

        if (payload && payload.success === false) {
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', payload.message || 'Failed to load messages.');
          }
          box.innerHTML = [
            '<div class="comm-placeholder" style="color:#ef4444">',
            '<i class="bi bi-exclamation-circle"></i>',
            '<p>Failed to load messages. Please refresh the page.</p>',
            '</div>'
          ].join('');
          return;
        }

        if (!Array.isArray(messages) || messages.length === 0) {
          box.innerHTML = [
            '<div class="comm-placeholder">',
            '<i class="bi bi-chat"></i>',
            '<p>No messages yet. Send your first message to your advisor!</p>',
            '</div>'
          ].join('');
          return;
        }

        box.innerHTML = messages.map(function (m) { return commBubble(m); }).join('');
        box.scrollTop = box.scrollHeight;

        const markReadFd = new FormData();
        markReadFd.append('action', '/student/message/read');
        markReadFd.append('student_id', String(commStudentId));
        fetch('../backend/modules/dispatcher.php', { method: 'POST', body: markReadFd }).catch(function () {});
      })
      .catch(function () {
        box.innerHTML = [
          '<div class="comm-placeholder" style="color:#ef4444">',
          '<i class="bi bi-exclamation-circle"></i>',
          '<p>Failed to load messages. Please refresh the page.</p>',
          '</div>'
        ].join('');
      });
  }

  function commBubble(m) {
    const isStudent = m.sender === 'student';
    const side = isStudent ? 'from-student' : 'from-advisor';
    const senderLabel = isStudent ? 'You' : (m.sender_name || 'Advisor');
    const time = m.sent_at ? new Date(m.sent_at).toLocaleString() : '';

    return [
      '<div class="msg-bubble-wrap ' + side + '">',
      '<div class="msg-meta">',
      '<span class="msg-sender">' + commEsc(senderLabel) + '</span>',
      '<span>' + commEsc(time) + '</span>',
      '</div>',
      '<div class="msg-bubble">' + commEsc(m.body) + '</div>',
      '</div>'
    ].join('');
  }

  function commWordCount(textarea) {
    const words = textarea.value.trim() === '' ? 0 : textarea.value.trim().split(/\s+/).length;
    const el = document.getElementById('commWordCount');
    const btn = document.getElementById('commSendBtn');
    if (!el || !btn) return;

    el.textContent = words + ' / ' + COMM_MAX_WORDS + ' words';
    el.classList.toggle('over', words > COMM_MAX_WORDS);
    btn.disabled = words === 0 || words > COMM_MAX_WORDS;
  }

  function commSend() {
    const textarea = document.getElementById('commTextarea');
    const btn = document.getElementById('commSendBtn');
    if (!textarea || !btn) return;

    const body = textarea.value.trim();
    if (!body || !commStudentId) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';

    const fd = new FormData();
    fd.append('action', '/student/message/send');
    fd.append('student_id', String(commStudentId));
    fd.append('message_body', body);

    fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success) {
          textarea.value = '';
          commWordCount(textarea);
          commFetchThread();
        } else {
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', (data && data.message) ? data.message : 'Failed to send message. Please try again.');
          }
          btn.disabled = false;
        }
      })
      .catch(function () {
        if (window.showSystemNotification) {
          window.showSystemNotification('danger', 'Network error. Please try again.');
        }
        btn.disabled = false;
      })
      .finally(function () {
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Message';
      });
  }

  function commEsc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  window.commWordCount = commWordCount;
  window.commSend = commSend;

  document.addEventListener('DOMContentLoaded', function () {
    const historyModalEl = document.getElementById('historyDetailsModal');
    if (historyModalEl) {
      historyDetailsModal = new bootstrap.Modal(historyModalEl);
    }

    document.querySelectorAll('.history-details-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openHistoryDetailsModal(btn.getAttribute('data-history-reason'));
      });
    });

    const params = new URLSearchParams(window.location.search);
    const section = params.get('section');

    if (section) {
      const sectionBtn = document.querySelector('.tab-btn[data-section="' + section + '"]');
      const panel = document.getElementById('section-' + section);

      if (sectionBtn && panel) {
        document.querySelectorAll('.tab-btn').forEach(function (b) {
          b.classList.remove('active');
        });

        document.querySelectorAll('.section-panel').forEach(function (p) {
          p.classList.remove('active');
        });

        sectionBtn.classList.add('active');
        panel.classList.add('active');
      }
    }

    document.querySelectorAll('.tab-btn').forEach(function (sectionBtn) {
      sectionBtn.addEventListener('click', function () {
        const sectionName = sectionBtn.getAttribute('data-section');

        document.querySelectorAll('.tab-btn').forEach(function (b) {
          b.classList.remove('active');
        });

        document.querySelectorAll('.section-panel').forEach(function (p) {
          p.classList.remove('active');
        });

        sectionBtn.classList.add('active');

        const targetPanel = document.getElementById('section-' + sectionName);
        if (targetPanel) {
          targetPanel.classList.add('active');
        }

        const url = new URL(window.location);
        url.searchParams.set('section', sectionName);
        window.history.replaceState({}, '', url);

        if (sectionName === 'communications' && !commLoaded) {
          commLoad();
        }

        if (sectionName === 'calendar') {
          renderStudentCalendar();
        }
      });
    });

    const communicationsSection = document.getElementById('section-communications');
    if (communicationsSection && communicationsSection.classList.contains('active')) {
      commLoad();
    }

    const calendarSection = document.getElementById('section-calendar');
    if (calendarSection && calendarSection.classList.contains('active')) {
      renderStudentCalendar();
    }

    const studentRequestSearch = document.getElementById('studentRequestSearch');
    if (studentRequestSearch) {
      studentRequestSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.student-request-row').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    document.querySelectorAll('.open-book-modal-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const slotId = btn.getAttribute('data-slot-id');
        const slotSelect = document.getElementById('bookSlotSelect');

        if (slotSelect && slotId) {
          slotSelect.value = slotId;
        }
      });
    });
  });
})();
