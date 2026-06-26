<link rel="stylesheet" href="dark.css">
<script>
  // Apply before render to prevent flash
  if (localStorage.getItem('cuts-dark') === '1') document.documentElement.classList.add('dark');

  function toggleDark() {
    var on = document.documentElement.classList.toggle('dark');
    localStorage.setItem('cuts-dark', on ? '1' : '0');
    document.querySelectorAll('.cuts-dark-btn').forEach(function(b) {
      b.textContent = on ? 'Light' : 'Dark';
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    var on = document.documentElement.classList.contains('dark');
    document.querySelectorAll('.cuts-dark-btn').forEach(function(b) {
      b.textContent = on ? 'Light' : 'Dark';
    });
  });
</script>
