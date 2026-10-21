// Administration : confirmation avant suppression
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirmer');
    if (msg && !window.confirm(msg)) e.preventDefault();
});
