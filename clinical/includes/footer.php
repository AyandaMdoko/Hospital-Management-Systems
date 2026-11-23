    </div><!-- /.clinical-frame -->

    <footer class="clinical-footer">
        &copy; <?= date('Y') ?> Hospital Management System. Authorized clinical access only.
    </footer>
</div><!-- /.clinical-shell -->

<script>
(function () {
    'use strict';
    const trigger = document.getElementById('clinical-menu-button');
    const sidebar = document.getElementById('clinical-sidebar');
    const backdrop = document.getElementById('clinical-sidebar-backdrop');
    if (!trigger || !sidebar || !backdrop) return;

    function setMenu(open) {
        document.body.classList.toggle('clinical-nav-open', open);
        trigger.setAttribute('aria-expanded', String(open));
        trigger.setAttribute('aria-label', open ? 'Close clinical navigation' : 'Open clinical navigation');
        if (!open) trigger.focus({ preventScroll: true });
    }
    trigger.addEventListener('click', function () {
        setMenu(!document.body.classList.contains('clinical-nav-open'));
    });
    backdrop.addEventListener('click', function () { setMenu(false); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('clinical-nav-open')) {
            setMenu(false);
        }
    });
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            document.body.classList.remove('clinical-nav-open');
            trigger.setAttribute('aria-expanded', 'false');
        });
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            document.body.classList.remove('clinical-nav-open');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
</body>
</html>
