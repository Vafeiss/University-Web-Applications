(function () {
  function activateTab(section) {
    var buttons = document.querySelectorAll('.tab-btn');
    var panels = document.querySelectorAll('.section-panel');
    var targetPanel = document.getElementById('section-' + section);

    buttons.forEach(function (button) {
      button.classList.remove('active');
      if (button.dataset.section === section) {
        button.classList.add('active');
      }
    });

    panels.forEach(function (panel) {
      panel.classList.remove('active');
    });

    if (targetPanel) {
      targetPanel.classList.add('active');
    }
  }

  function bindTabSwitching() {
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab');

    if (tab) {
      activateTab(tab);
    }

    document.querySelectorAll('.tab-btn').forEach(function (button) {
      button.addEventListener('click', function () {
        var section = button.dataset.section;
        activateTab(section);

        var url = new URL(window.location);
        url.searchParams.set('tab', section);
        window.history.replaceState({}, '', url);
      });
    });
  }

  function bindSearchInputs() {
    var advisorSearch = document.getElementById('advisorSearch');
    if (advisorSearch) {
      advisorSearch.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.advisor-row').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    var studentSearch = document.getElementById('studentSearch');
    if (studentSearch) {
      studentSearch.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.student-row').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    var superuserSearch = document.getElementById('superuserSearch');
    if (superuserSearch) {
      superuserSearch.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.superuser-row').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    document.querySelectorAll('.assign-search').forEach(function (input) {
      input.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        var body = this.closest('.accordion-body');
        if (!body) return;
        body.querySelectorAll('.assign-student-row').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    });
  }

  function bindEditButtons() {
    var editAdvisorBtn = document.getElementById('editAdvisorBtn');
    if (editAdvisorBtn) {
      editAdvisorBtn.addEventListener('click', function () {
        var checked = document.querySelectorAll('input[name="advisor_id[]"]:checked');

        if (checked.length === 0) {
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', 'Please select one advisor to edit.');
          }
          return;
        }

        if (checked.length > 1) {
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', 'Please select only one advisor to edit.');
          }
          return;
        }

        var advisor = checked[0];
        document.getElementById('editAdvisorFirstName').value = advisor.dataset.firstName || '';
        document.getElementById('editAdvisorLastName').value = advisor.dataset.lastName || '';
        document.getElementById('editAdvisorEmail').value = advisor.dataset.email || '';
        document.getElementById('editAdvisorPhone').value = advisor.dataset.phone || '';
        document.getElementById('editAdvisorExternalId').value = advisor.value || '';

        var departmentSelect = document.getElementById('editAdvisorDepartment');
        var departmentId = advisor.dataset.departmentId || '1';
        departmentSelect.value = departmentId;

        if (departmentSelect.value !== departmentId) {
          var option = document.createElement('option');
          option.value = departmentId;
          option.textContent = 'Department ' + departmentId;
          departmentSelect.appendChild(option);
          departmentSelect.value = departmentId;
        }

        var editAdvisorModal = new bootstrap.Modal(document.getElementById('editAdvisorModal'));
        editAdvisorModal.show();
      });
    }

    var editStudentBtn = document.getElementById('editStudentBtn');
    if (editStudentBtn) {
      editStudentBtn.addEventListener('click', function () {
        var checked = document.querySelectorAll('input[name="student_ID[]"]:checked');

        if (checked.length === 0) {
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', 'Please select one student to edit.');
          }
          return;
        }

        if (checked.length > 1) {
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', 'Please select only one student to edit.');
          }
          return;
        }

        var student = checked[0];
        document.getElementById('editStudentExternalId').value = student.dataset.externalId || '';
        document.getElementById('editStudentFirstName').value = student.dataset.firstName || '';
        document.getElementById('editStudentLastName').value = student.dataset.lastName || '';
        document.getElementById('editStudentEmail').value = student.dataset.email || '';
        document.getElementById('editStudentYear').value = student.dataset.year || '';
        document.getElementById('editStudentAdvisor').value = student.dataset.advisorId || '';

        var degreeSelect = document.getElementById('editStudentDegree');
        var degreeId = student.dataset.degreeId || '1';
        degreeSelect.value = degreeId;

        if (degreeSelect.value !== degreeId) {
          var option = document.createElement('option');
          option.value = degreeId;
          option.textContent = 'Degree ' + degreeId;
          degreeSelect.appendChild(option);
          degreeSelect.value = degreeId;
        }

        var editStudentModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
        editStudentModal.show();
      });
    }
  }

  function bindDeleteConfirmation() {
    ['advisorForm', 'studentForm', 'superuserForm'].forEach(function (id) {
      var form = document.getElementById(id);
      if (!form) return;

      form.addEventListener('submit', function (e) {
        var checked = form.querySelectorAll('input[type=checkbox]:checked');
        if (checked.length === 0) {
          e.preventDefault();
          if (window.showSystemNotification) {
            window.showSystemNotification('danger', 'Please select at least one item to delete.');
          }
          return;
        }

        e.preventDefault();
        if (window.confirmAction) {
          window.confirmAction('Delete ' + checked.length + ' selected item(s)? This cannot be undone.')
            .then(function (ok) {
              if (ok) {
                form.submit();
              }
            });
        } else {
          form.submit();
        }
      });
    });
  }

  function bindCollapseState() {
    var filter = document.getElementById('filterSection');
    if (filter) {
      if (localStorage.getItem('filtersOpen') === 'true') {
        filter.classList.add('show');
      }
      filter.addEventListener('shown.bs.collapse', function () {
        localStorage.setItem('filtersOpen', 'true');
      });
      filter.addEventListener('hidden.bs.collapse', function () {
        localStorage.setItem('filtersOpen', 'false');
      });
    }

    var assignFilter = document.getElementById('assignFilterSection');
    if (assignFilter) {
      if (localStorage.getItem('assignFiltersOpen') === 'true') {
        assignFilter.classList.add('show');
      }
      assignFilter.addEventListener('shown.bs.collapse', function () {
        localStorage.setItem('assignFiltersOpen', 'true');
      });
      assignFilter.addEventListener('hidden.bs.collapse', function () {
        localStorage.setItem('assignFiltersOpen', 'false');
      });
    }
  }

  function degToggleEdit(id) {
    var item = document.getElementById('degItem-' + id);
    var form = document.getElementById('degForm-' + id);
    if (!item || !form) return;

    var isOpen = item.classList.contains('editing');
    document.querySelectorAll('.deg-list-item.editing').forEach(function (el) {
      el.classList.remove('editing');
      var inlineForm = el.querySelector('.deg-inline-form');
      if (inlineForm) {
        inlineForm.style.display = 'none';
      }
    });

    if (!isOpen) {
      item.classList.add('editing');
      form.style.display = 'flex';
      item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function deptToggleEdit(id) {
    var item = document.getElementById('deptItem-' + id);
    var form = document.getElementById('deptForm-' + id);
    if (!item || !form) return;

    var isOpen = item.classList.contains('editing');
    document.querySelectorAll('.deg-list-item.editing').forEach(function (el) {
      el.classList.remove('editing');
      var inlineForm = el.querySelector('.deg-inline-form');
      if (inlineForm) {
        inlineForm.style.display = 'none';
      }
    });

    if (!isOpen) {
      item.classList.add('editing');
      form.style.display = 'flex';
      item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function bindInlineEditSearch() {
    var degreeSearchInput = document.getElementById('degreeSearch');
    if (degreeSearchInput) {
      degreeSearchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.deg-list-item').forEach(function (item) {
          item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }

    var departmentSearchInput = document.getElementById('departmentSearch');
    if (departmentSearchInput) {
      departmentSearchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#departmentEditList .deg-list-item').forEach(function (item) {
          item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }
  }

  function renderAdvisorChart() {
    var canvas = document.getElementById('advisorPieChart');
    if (!canvas || typeof Chart === 'undefined') return;

    var chartData = [];
    try {
      chartData = JSON.parse(canvas.dataset.advisorChart || '[]');
    } catch (error) {
      chartData = [];
    }

    if (!Array.isArray(chartData) || chartData.length === 0) return;

    var COLORS = [
      '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444',
      '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1'
    ];

    var legend = document.getElementById('advisorLegend');
    var center = document.getElementById('chartCenterCount');
    var buttons = document.querySelectorAll('.year-filter-btn');
    var chartInstance = null;

    function getCounts(year) {
      return chartData.map(function (advisor) {
        return year === 0 ? advisor.total : ((advisor.byYear && advisor.byYear[year]) || 0);
      });
    }

    function buildLegend(counts, total) {
      if (!legend) return;
      legend.innerHTML = '';

      counts.forEach(function (count, i) {
        var advisor = chartData[i];
        var pct = total > 0 ? Math.round((count / total) * 100) : 0;

        var row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;';

        var left = document.createElement('div');
        left.style.cssText = 'display:flex;align-items:center;gap:10px;flex:1;min-width:0;';

        var swatch = document.createElement('span');
        swatch.style.cssText = 'width:12px;height:12px;border-radius:3px;background:' + COLORS[i % COLORS.length] + ';flex-shrink:0;display:inline-block;';

        var name = document.createElement('span');
        name.style.cssText = 'font-size:.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        name.textContent = String(advisor && advisor.name ? advisor.name : '');

        var right = document.createElement('span');
        right.style.cssText = 'font-size:.82rem;color:#6b7280;white-space:nowrap;margin-left:12px;';
        right.textContent = count + ' student' + (count !== 1 ? 's' : '') + ' (' + pct + '%)';

        left.appendChild(swatch);
        left.appendChild(name);
        row.appendChild(left);
        row.appendChild(right);
        legend.appendChild(row);
      });
    }

    function renderChart(year) {
      var counts = getCounts(year);
      var total = counts.reduce(function (sum, value) { return sum + value; }, 0);
      var labels = chartData.map(function (advisor) { return advisor.name; });
      var colors = COLORS.slice(0, counts.length);

      if (center) center.textContent = total;
      buildLegend(counts, total);

      var displayCounts = total === 0 ? [1] : counts;
      var displayColors = total === 0 ? ['#e5e7eb'] : colors;
      var displayLabels = total === 0 ? ['No data'] : labels;

      if (chartInstance) {
        chartInstance.data.labels = displayLabels;
        chartInstance.data.datasets[0].data = displayCounts;
        chartInstance.data.datasets[0].backgroundColor = displayColors;
        chartInstance.update();
        return;
      }

      chartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
          labels: displayLabels,
          datasets: [{
            data: displayCounts,
            backgroundColor: displayColors,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6
          }]
        },
        options: {
          cutout: '68%',
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  if (total === 0) return ' No students assigned';
                  var val = counts[ctx.dataIndex];
                  var pct = total > 0 ? Math.round((val / total) * 100) : 0;
                  return ' ' + val + ' student' + (val !== 1 ? 's' : '') + ' (' + pct + '%)';
                }
              }
            }
          },
          animation: { animateRotate: true, duration: 500 }
        }
      });
    }

    renderChart(0);

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        buttons.forEach(function (b) {
          b.classList.remove('btn-primary');
          b.classList.add('btn-outline-primary');
        });
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
        var currentYear = parseInt(button.dataset.year || '0', 10);
        renderChart(currentYear);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var flashToast = document.getElementById('flashToast');
    if (flashToast) {
      setTimeout(function () {
        flashToast.remove();
      }, 3500);
    }

    bindTabSwitching();
    bindSearchInputs();
    bindEditButtons();
    bindDeleteConfirmation();
    bindCollapseState();
    bindInlineEditSearch();
    renderAdvisorChart();

    window.degToggleEdit = degToggleEdit;
    window.deptToggleEdit = deptToggleEdit;
  });
})();
