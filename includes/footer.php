</div> </div> </main>
    
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">V 1.0</div>
        <strong><?= $settings['footer_text'] ?></strong>
    </footer>
</div> <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/adminlte.min.js"></script>
<script>
    // Theme Toggle Logic with Persistence
    const toggleButton = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const htmlElement = document.documentElement;

    // 1. Function to update Icon
    function updateIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-fill');
        } else {
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill');
        }
    }

    // 2. Initialize Icon on Load
    const currentTheme = localStorage.getItem('theme') || 'light';
    updateIcon(currentTheme);

    // 3. Handle Click
    toggleButton.addEventListener('click', () => {
        const current = htmlElement.getAttribute('data-bs-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        
        // Apply
        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme); // SAVE IT
        updateIcon(newTheme);
    });

    // 4. Notification Handler
    const markReadBtn = document.getElementById('mark-notifications-read');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData();
            formData.append('action', 'mark_read');

            fetch('<?= BASE_URL ?>dashboards/student/ajax_notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const badge = document.querySelector('.navbar-badge');
                    if(badge) badge.remove();
                    const list = document.getElementById('notification-list');
                    if(list) list.innerHTML = '<div class="dropdown-item text-center py-3 text-muted small">No new notifications</div>';
                    const header = document.querySelector('.dropdown-header');
                    if(header) header.textContent = '0 Notifications';
                }
            });
        });
    }
</script>
</body>
</html>