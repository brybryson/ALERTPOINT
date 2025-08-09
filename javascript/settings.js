const dropdown = document.getElementById('settingsDropdown');
  const cogIcon = document.querySelector('.fa-cog');

  function toggleSettingsDropdown() {
    dropdown.classList.toggle('pointer-events-none');
    dropdown.classList.toggle('opacity-0');
    dropdown.classList.toggle('scale-95');
    dropdown.classList.toggle('opacity-100');
    dropdown.classList.toggle('scale-100');
  }

  // Close dropdown if click is outside
  document.addEventListener('click', function (event) {
    const isClickInside = dropdown.contains(event.target) || cogIcon.contains(event.target);

    if (!isClickInside && !dropdown.classList.contains('pointer-events-none')) {
      // Close dropdown with animation
      dropdown.classList.add('pointer-events-none');
      dropdown.classList.remove('opacity-100', 'scale-100');
      dropdown.classList.add('opacity-0', 'scale-95');
    }
  });


  function toggleDarkMode() {
    document.documentElement.classList.toggle("dark");
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  }

  // On page load, apply dark mode if saved
  window.onload = function () {
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  }


