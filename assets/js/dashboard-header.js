jQuery(document).ready(function($) {
    'use strict';

    // User Menu Toggle
    const userMenu = $('.user-menu');
    const userMenuToggle = $('.user-menu-toggle');
    const userDropdown = $('.user-dropdown-menu');

    userMenuToggle.on('click', function(e) {
        e.stopPropagation();
        userMenu.toggleClass('open');
    });

    $(document).on('click', function(e) {
        if (userMenu.hasClass('open') && !$(e.target).closest('.user-menu').length) {
            userMenu.removeClass('open');
        }
    });

    // Live Search
    const searchInput = $('#mobooking-live-search');
    const searchResults = $('#mobooking-search-results');
    const searchResultsList = $('#search-results-list');
    let searchTimeout;

    searchInput.on('focus', function() {
        searchResults.addClass('open');
    });

    searchInput.on('blur', function() {
        // Delay hiding to allow click on results
        setTimeout(function() {
            searchResults.removeClass('open');
        }, 200);
    });

    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            searchResultsList.html('');
            return;
        }

        searchTimeout = setTimeout(function() {
            // AJAX call will go here
            console.log('Searching for:', query);
            searchResultsList.html('<p>Searching...</p>');
        }, 300);
    });
});
