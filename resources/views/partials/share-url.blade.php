<div class="alert alert-info mt-4" role="alert">
    <div class="d-flex">
        <div class="me-3">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div>
            <h6 class="alert-heading">{{ $title ?? 'Share this page!' }}</h6>
            <p class="mb-2">{{ $description ?? 'Copy this URL to share:' }}</p>
            <div class="input-group">
                <input
                    type="text"
                    class="form-control font-monospace"
                    value="{{ $url }}"
                    readonly
                    id="shareUrl"
                >
                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">
                    Copy
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const urlInput = document.getElementById('shareUrl');
    if (urlInput) {
        urlInput.select();
        urlInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(urlInput.value).then(function() {
            const button = document.querySelector('button[onclick="copyToClipboard()"]');
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');

            setTimeout(function() {
                button.textContent = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }
}
</script>