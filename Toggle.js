function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('themeIcon');
    
    body.classList.toggle('dark-theme');
    
    // Salva a escolha no navegador do usuário
    if (body.classList.contains('dark-theme')) {
        localStorage.setItem('theme', 'dark');
        icon.classList.replace('fa-moon', 'fa-sun');
    } else {
        localStorage.setItem('theme', 'light');
        icon.classList.replace('fa-sun', 'fa-moon');
    }
}

// Carregar o tema assim que a página abrir
(function() {
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-theme');
        document.getElementById('themeIcon')?.classList.replace('fa-moon', 'fa-sun');
    }
})();

