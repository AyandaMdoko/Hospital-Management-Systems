    <footer class="clinical-footer">
        <p>&copy; <?= date('Y') ?> Hospital Management System. Authorized clinical access only.</p>
    </footer>

</div>

<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const clinicalSidebar = document.getElementById('clinicalSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && clinicalSidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', () => {
            clinicalSidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', () => {
            clinicalSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });
    }
</script>

</body>
</html>