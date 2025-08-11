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
            searchResultsList.html('<p>Searching...</p>');

            $.ajax({
                url: ajaxurl, // WordPress AJAX URL
                type: 'POST',
                data: {
                    action: 'mobooking_dashboard_live_search',
                    nonce: mobooking_dashboard_params.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        if (response.data.results.length) {
                            response.data.results.forEach(function(item) {
                                html += `<a href="${item.url}" class="search-result-item">
                                            <span class="result-type">${item.type}</span>
                                            <span class="result-title">${item.title}</span>
                                         </a>`;
                            });
                        } else {
                            html = '<p class="no-results">No results found.</p>';
                        }
                        searchResultsList.html(html);
                    } else {
                        searchResultsList.html('<p class="no-results">Search failed.</p>');
                    }
                }
            });
        }, 300);
    });
});
