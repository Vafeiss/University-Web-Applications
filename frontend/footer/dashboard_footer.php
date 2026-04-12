<style>
  html,
  body {
    height: 100%;
  }

  body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  main {
    flex: 1 0 auto;
  }

  .dashboard-fixed-footer {
    background: #ffffff;
    margin-top: auto;
  }

  .dashboard-fixed-footer .footer-inner {
    max-width: 1100px;
  }
</style>

<footer class="dashboard-fixed-footer border-top mt-4 py-3">
  <div class="container-fluid px-4 text-center text-muted small footer-inner">
    <span>&copy; <?= date('Y') ?> Cyprus University of Technology, All rights reserved.</span>
    <span class="mx-2">|</span>
    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#aboutProjectModal">
      About the Project
    </button>
  </div>
</footer>

<div class="modal fade" id="aboutProjectModal" tabindex="-1" aria-labelledby="aboutProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold" id="aboutProjectModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About AdviCut
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-0">AdviCut is a university advising platform that helps students, advisors manage appointments, communication, and reporting in one place.</p>
        </p>
        <p class="mb-0">Created by Paraskevas Vafeiadis, Panteleimoni Alexandrou , Pelagia Koniotaki , Antriani Theofanous , Panayioths Panayiotou as part of the Software Engineering course at Cyprus University of Technology, 2024-2026.</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
