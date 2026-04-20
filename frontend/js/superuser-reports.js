(function () {
  'use strict';

  function bindTabSwitching() {
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
      });
    });
  }

  function renderAssignmentChart() {
    const assignmentChart = document.getElementById('assignmentChart');
    if (!assignmentChart || typeof Chart === 'undefined') {
      return;
    }

    const bodyEl = document.body;
    const assignedStudents = Number(bodyEl.getAttribute('data-assigned-students') || 0);
    const unassignedStudents = Number(bodyEl.getAttribute('data-unassigned-students') || 0);
    const assignedLabel = bodyEl.getAttribute('data-label-assigned') || 'Assigned Students';
    const unassignedLabel = bodyEl.getAttribute('data-label-unassigned') || 'Unassigned Students';

    new Chart(assignmentChart, {
      type: 'pie',
      data: {
        labels: [assignedLabel, unassignedLabel],
        datasets: [{
          data: [assignedStudents, unassignedStudents]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindTabSwitching();
    renderAssignmentChart();
  });
})();
