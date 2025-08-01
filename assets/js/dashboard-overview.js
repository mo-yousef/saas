document.addEventListener('DOMContentLoaded', function () {
    // Since the data is now loaded directly into the PHP template,
    // we don't need to make AJAX calls to fetch it anymore.
    // We just need to make sure the feather icons are replaced.
    feather.replace();

    // The rest of the JS functionality that was fetching data via AJAX
    // has been removed as it is no longer needed.
});
