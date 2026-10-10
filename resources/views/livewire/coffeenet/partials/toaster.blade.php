{{-- توست مشترک کامپوننت‌های Livewire پنل کافی‌نت [Task 4]
     اکشن‌های Livewire رویداد lw-toast می‌فرستند؛ این شنونده آن را با
     App.toast نشان می‌دهد تا UX قبل/بعد از مهاجرت یکسان بماند. --}}
<script>
    (function () {
        function bind() {
            if (!window.Livewire) { return; }
            if (window.__lwToastNet4) { return; }
            window.__lwToastNet4 = true;

            Livewire.on('lw-toast', (payload) => {
                const data = Array.isArray(payload) ? payload[0] : payload;
                const message = data && (data.message || data.msg);
                const type = (data && data.type) || 'success';

                if (message && window.App && typeof App.toast === 'function') {
                    App.toast(message, type);
                }
            });
        }

        if (window.Livewire) { bind(); }
        document.addEventListener('livewire:init', bind, { once: true });
        document.addEventListener('livewire:navigated', bind, { once: true });
    })();
</script>
