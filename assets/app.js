// Plain JavaScript — navbar collapse toggle and dismissible alerts.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.navbar-toggler').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-bs-target'));
      if (target) target.classList.toggle('show');
    });
  });
  document.querySelectorAll('.alert .btn-close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var alert = btn.closest('.alert');
      if (alert) alert.remove();
    });
  });
});
