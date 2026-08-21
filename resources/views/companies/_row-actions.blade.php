<script>
document.addEventListener('click', function(e) {
    if (e.target.closest('.reg-row-dots')) {
        e.stopPropagation();
        var btn  = e.target.closest('.reg-row-dots');
        var wrap = btn.closest('.reg-row-actions');
        var menu = wrap.querySelector('.reg-row-menu');
        var open = menu.classList.contains('open');

        document.querySelectorAll('.reg-row-menu.open').forEach(function(m) {
            m.classList.remove('open');
            m.style.top = ''; m.style.left = '';
        });

        if (!open) {
            var rect = btn.getBoundingClientRect();
            menu.classList.add('open');
            menu.style.left = 'auto';
            menu.style.right = 'auto';

            var mh = menu.offsetHeight;
            var mw = menu.offsetWidth;

            var top  = rect.bottom + 2;
            var left = rect.right - mw;

            if (top + mh > window.innerHeight) {
                top = rect.top - mh - 2;
            }
            if (left < 0) left = rect.left;

            menu.style.top  = top + 'px';
            menu.style.left = left + 'px';
        }
        return;
    }
    document.querySelectorAll('.reg-row-menu.open').forEach(function(m) {
        m.classList.remove('open');
        m.style.top = ''; m.style.left = '';
    });
});

window.addEventListener('scroll', function() {
    document.querySelectorAll('.reg-row-menu.open').forEach(function(m) {
        m.classList.remove('open');
        m.style.top = ''; m.style.left = '';
    });
}, true);
</script>
