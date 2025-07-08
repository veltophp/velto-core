<?php

if (!function_exists('loadingIndicator')) {
    function loadingIndicator(): string
    {
        return <<<HTML
<style>
#velto-loading-spinner {
    position: fixed;
    bottom: 1rem;
    left: 1rem;
    z-index: 9999;
    width: 36px;
    height: 36px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #dc2626; /* merah */
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: none;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div id="velto-loading-spinner"></div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const spinner = document.getElementById('velto-loading-spinner');
        if (spinner) spinner.style.display = 'block';
    });

    window.addEventListener("load", () => {
        const spinner = document.getElementById('velto-loading-spinner');
        if (spinner) spinner.style.display = 'none';
    });
</script>
HTML;
    }
}
