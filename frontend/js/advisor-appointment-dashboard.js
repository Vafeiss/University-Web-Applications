(function () {
  'use strict';

  // Communication state
  const COMM_MAX_WORDS = 200;
  let commActiveStudentId = 0;
  let commLoadedForStudent = 0;

  // Calendar state
  let advisorCalendarLoaded = false;
  let advisorCalendarInstance = null;

  // Get calendar events from data attribute
  const bodyEl = document.body;
  const advisorCalendarEvents = (function () {
    const dataStr = bodyEl.getAttribute('data-advisor-calendar-events');
    if (!dataStr) return [];
    try {
      return JSON.parse(dataStr);
    } catch (e) {
      return [];
    }
  })();

  // Calendar helper functions
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

  function renderAdvisorCalendar() {
    if (advisorCalendarLoaded) return;

    const calendarEl = document.getElementById('advisorCalendar');
    const modalEl = document.getElementById('advisorCalendarModal');
    if (!calendarEl || !modalEl) return;

    const detailsModal = new bootstrap.Modal(modalEl);

    advisorCalendarInstance = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      height: 'auto',
      events: advisorCalendarEvents,
      eventClick: function (info) {
        const props = info.event.extendedProps || {};
        document.getElementById('advisorCalendarModalStudent').textContent = props.student || '-';
        document.getElementById('advisorCalendarModalDate').textContent = props.date || '-';
        document.getElementById('advisorCalendarModalTime').textContent = props.time || '-';
        document.getElementById('advisorCalendarModalStatus').textContent = props.status || '-';
        setCalendarReason('advisorCalendarModalStudentReasonBtn', 'advisorCalendarModalStudentReasonWrap', 'advisorCalendarModalStudentReason', props.student_reason);
        setCalendarReason('advisorCalendarModalAdvisorReasonBtn', 'advisorCalendarModalAdvisorReasonWrap', 'advisorCalendarModalAdvisorReason', props.advisor_reason);
        resetCalendarReasonState('advisorCalendarModalStudentReasonWrap');
        resetCalendarReasonState('advisorCalendarModalAdvisorReasonWrap');
        detailsModal.show();
      }
    });

    advisorCalendarInstance.render();
    advisorCalendarLoaded = true;
  }

  // Expose calendar function globally for onclick handlers
  window.renderAdvisorCalendar = renderAdvisorCalendar;

  // Communication helper functions
  function commEsc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function commSetActiveStudent(item) {
    document.querySelectorAll('.comm-student-item').forEach(function (el) {
      el.classList.remove('active');
    });
    item.classList.add('active');

    const name = item.getAttribute('data-student-name') || 'Student';
    const extId = item.getAttribute('data-student-ext-id') || '-';
    document.getElementById('commPaneStudentName').textContent = name;
    document.getElementById('commPaneStudentMeta').textContent = 'Student ID: ' + extId;

    const textarea = document.getElementById('commTextarea');
    textarea.disabled = false;
    textarea.placeholder = 'Type your message here...';
  }

  function commLoadThread(studentId) {
    const box = document.getElementById('commMessages');
    if (!box || studentId <= 0) return;

    box.innerHTML = '<div class="comm-loading">Loading messages...</div>';

    const fd = new FormData();
    fd.append('action', '/message/thread');
    fd.append('student_id', String(studentId));

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
          box.innerHTML = '<div class="comm-placeholder" style="color:#ef4444"><i class="bi bi-exclamation-circle"></i><p>Failed to load messages. Please try again.</p></div>';
          return;
        }

        if (!Array.isArray(messages) || messages.length === 0) {
          box.innerHTML = '<div class="comm-placeholder"><i class="bi bi-chat"></i><p>No messages yet. Send the first reply.</p></div>';
        } else {
          box.innerHTML = messages.map(function (m) {
            const side = m.sender === 'advisor' ? 'from-advisor' : 'from-student';
            const senderLabel = m.sender === 'advisor' ? 'You' : (m.sender_name || 'Student');
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
          }).join('');
        }

        box.scrollTop = box.scrollHeight;

        const readFd = new FormData();
        readFd.append('action', '/message/read');
        readFd.append('student_id', String(studentId));
        fetch('../backend/modules/dispatcher.php', { method: 'POST', body: readFd }).catch(function () {});

        const activeItem = document.querySelector('.comm-student-item.active .comm-unread');
        if (activeItem) {
          activeItem.remove();
        }
      })
      .catch(function () {
        box.innerHTML = '<div class="comm-placeholder" style="color:#ef4444"><i class="bi bi-exclamation-circle"></i><p>Failed to load messages. Please try again.</p></div>';
      });
  }

  function commWordCount(textarea) {
    const words = textarea.value.trim() === '' ? 0 : textarea.value.trim().split(/\s+/).length;
    const counter = document.getElementById('commWordCount');
    const sendBtn = document.getElementById('commSendBtn');

    counter.textContent = words + ' / ' + COMM_MAX_WORDS + ' words';
    counter.classList.toggle('over', words > COMM_MAX_WORDS);
    sendBtn.disabled = (commActiveStudentId <= 0 || words === 0 || words > COMM_MAX_WORDS);
  }

  function commSend() {
    const textarea = document.getElementById('commTextarea');
    const sendBtn = document.getElementById('commSendBtn');
    const messageBody = textarea.value.trim();

    if (commActiveStudentId <= 0 || messageBody === '') return;

    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';

    const fd = new FormData();
    fd.append('action', '/message/send');
    fd.append('student_id', String(commActiveStudentId));
    fd.append('message_body', messageBody);

    fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success) {
          textarea.value = '';
          commWordCount(textarea);
          commLoadThread(commActiveStudentId);
        } else if (window.showSystemNotification) {
          window.showSystemNotification('danger', (data && data.message) ? data.message : 'Failed to send message.');
        }
      })
      .catch(function () {
        if (window.showSystemNotification) {
          window.showSystemNotification('danger', 'Network error while sending message.');
        }
      })
      .finally(function () {
        sendBtn.innerHTML = '<i class="bi bi-send-fill"></i> Send Reply';
        commWordCount(textarea);
      });
  }

  // Tab switching and filtering
  function bindTabSwitching() {
    const params = new URLSearchParams(window.location.search);
    const section = params.get("section");

    if (section) {
      const btn = document.querySelector('.tab-btn[data-section="' + section + '"]');
      const panel = document.getElementById('section-' + section);

      if (btn && panel) {
        document.querySelectorAll('.tab-btn').forEach(function (b) {
          b.classList.remove('active');
        });

        document.querySelectorAll('.section-panel').forEach(function (p) {
          p.classList.remove('active');
        });

        btn.classList.add('active');
        panel.classList.add('active');
      }
    }

    document.querySelectorAll('.tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const sectionName = btn.getAttribute('data-section');

        document.querySelectorAll('.tab-btn').forEach(function (b) {
          b.classList.remove('active');
        });

        document.querySelectorAll('.section-panel').forEach(function (p) {
          p.classList.remove('active');
        });

        btn.classList.add('active');

        const targetPanel = document.getElementById('section-' + sectionName);
        if (targetPanel) {
          targetPanel.classList.add('active');
        }

        const url = new URL(window.location);
        url.searchParams.set('section', sectionName);
        window.history.replaceState({}, '', url);

        if (sectionName === 'communications') {
          const firstStudent = document.querySelector('.comm-student-item');
          if (firstStudent && commLoadedForStudent === 0) {
            firstStudent.click();
          }
        }

        if (sectionName === 'calendar') {
          renderAdvisorCalendar();
        }
      });
    });
  }

  function bindSearchFilters() {
    const requestSearch = document.getElementById('requestSearch');
    if (requestSearch) {
      requestSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.request-row').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    const myStudentsSearch = document.getElementById('myStudentsSearch');
    const myStudentsYearFilter = document.getElementById('myStudentsYearFilter');

    function filterMyStudents() {
      const q = (myStudentsSearch?.value || '').toLowerCase().trim();
      const year = (myStudentsYearFilter?.value || '').trim();

      document.querySelectorAll('.mystudent-row').forEach(function (row) {
        const textMatch = q === '' || row.textContent.toLowerCase().includes(q);
        const rowYear = row.getAttribute('data-year') || '';
        const yearMatch = year === '' || rowYear === year;
        row.style.display = textMatch && yearMatch ? '' : 'none';
      });
    }

    if (myStudentsSearch) {
      myStudentsSearch.addEventListener('input', filterMyStudents);
    }

    if (myStudentsYearFilter) {
      myStudentsYearFilter.addEventListener('change', filterMyStudents);
    }

    const myStudentsResetFilters = document.getElementById('myStudentsResetFilters');
    if (myStudentsResetFilters) {
      myStudentsResetFilters.addEventListener('click', function () {
        if (myStudentsSearch) {
          myStudentsSearch.value = '';
        }
        if (myStudentsYearFilter) {
          myStudentsYearFilter.value = '';
        }
        filterMyStudents();
      });
    }
  }

  function bindDeclineModalButtons() {
    document.querySelectorAll('.open-decline-modal-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const requestId = this.getAttribute('data-request-id');
        const input = document.getElementById('declineRequestId');
        if (input) {
          input.value = requestId;
        }
      });
    });
  }

  function bindCommunicationListeners() {
    document.querySelectorAll('.comm-student-item').forEach(function (item) {
      item.addEventListener('click', function () {
        const studentId = parseInt(item.getAttribute('data-student-id') || '0', 10);
        if (!studentId) return;

        commActiveStudentId = studentId;
        commLoadedForStudent = studentId;

        commSetActiveStudent(item);
        commLoadThread(studentId);
        commWordCount(document.getElementById('commTextarea'));
      });
    });

    const textarea = document.getElementById('commTextarea');
    if (textarea) {
      textarea.addEventListener('input', function () {
        commWordCount(textarea);
      });
    }

    const sendBtn = document.getElementById('commSendBtn');
    if (sendBtn) {
      sendBtn.addEventListener('click', commSend);
    }
  }

  // Initialize on DOM ready
  document.addEventListener("DOMContentLoaded", function () {
    bindTabSwitching();
    bindSearchFilters();
    bindDeclineModalButtons();
    bindCommunicationListeners();

    // Handle initial section load
    if (document.getElementById('section-communications')?.classList.contains('active')) {
      const firstStudent = document.querySelector('.comm-student-item');
      if (firstStudent) {
        firstStudent.click();
      }
    }

    if (document.getElementById('section-calendar')?.classList.contains('active')) {
      renderAdvisorCalendar();
    }
  });
})();
