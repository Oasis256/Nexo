{{-- Vue Components for WhatsApp Module --}}
<script>
// Register any module-specific Vue components here if needed
(function() {
    // WhatsApp module is ready
    if (window.nsEvent) {
        nsEvent.emit('whatsapp-module-loaded');
    }
})();
</script>
