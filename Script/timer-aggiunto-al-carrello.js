document.addEventListener('DOMContentLoaded', function () {
  const toast = document.getElementById('cart-toast');
  if (!toast) return;

  try {
    const url = new URL(window.location.href);
    if (url.searchParams.get('added') === '1') {
      url.searchParams.delete('added');
      window.history.replaceState({}, '', url.toString());
    }
  } catch (e) {}

  toast.classList.add('is-visible');

  setTimeout(() => {
    toast.classList.remove('is-visible');
    setTimeout(() => toast.remove(), 350);
  }, 5000);
});