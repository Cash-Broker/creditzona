{{-- Adds .cz-date-empty class to date inputs when empty so CSS can hide the "mm/dd/yyyy" placeholder. --}}
<script>
(function () {
    function syncDate(el) {
        if (!el || el.type !== 'date') return;
        el.classList.toggle('cz-date-empty', !el.value);
    }

    function initAll() {
        document.querySelectorAll('input.cz-input[type="date"]').forEach(syncDate);
    }

    function attachListeners() {
        document.addEventListener('input', (e) => {
            if (e.target && e.target.matches && e.target.matches('input.cz-input[type="date"]')) {
                syncDate(e.target);
            }
        }, true);
        document.addEventListener('change', (e) => {
            if (e.target && e.target.matches && e.target.matches('input.cz-input[type="date"]')) {
                syncDate(e.target);
            }
        }, true);
    }

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(() => {
        initAll();
        attachListeners();

        // Re-run after Livewire morph updates the DOM
        document.addEventListener('livewire:initialized', initAll);
        document.addEventListener('livewire:navigated', initAll);
        document.addEventListener('livewire:update', initAll);
        document.addEventListener('livewire:morph.updated', initAll);

        // Also observe DOM changes as a safety net
        const observer = new MutationObserver(() => initAll());
        observer.observe(document.body, { childList: true, subtree: true });
    });
})();
</script>
