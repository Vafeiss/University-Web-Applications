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

  function bindDepartmentDegreeFilters() {
    [
      ['statsDepartmentSelect', 'statsDegreeSelect'],
      ['studentDepartmentSelect', 'studentDegreeSelect']
    ].forEach(function (pair) {
      const departmentSelect = document.getElementById(pair[0]);
      const degreeSelect = document.getElementById(pair[1]);
      if (!departmentSelect || !degreeSelect) {
        return;
      }

      const allDegreeOptions = Array.from(degreeSelect.options).map(function (option) {
        return option.cloneNode(true);
      });

      function renderDegreeOptions(resetDegree) {
        const selectedDepartment = departmentSelect.value || '0';
        const selectedDegree = resetDegree ? '0' : degreeSelect.value;

        degreeSelect.innerHTML = '';
        allDegreeOptions.forEach(function (option) {
          const optionDepartment = option.getAttribute('data-department-id') || '';
          if (option.value === '0' || selectedDepartment === '0' || selectedDepartment === '' || optionDepartment === selectedDepartment) {
            degreeSelect.appendChild(option.cloneNode(true));
          }
        });

        degreeSelect.value = selectedDegree;
        if (degreeSelect.value !== selectedDegree) {
          degreeSelect.value = '0';
        }
      }

      renderDegreeOptions(false);
      departmentSelect.addEventListener('change', function () {
        renderDegreeOptions(true);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindTabSwitching();
    bindDepartmentDegreeFilters();
    renderAssignmentChart();
  });
})();
