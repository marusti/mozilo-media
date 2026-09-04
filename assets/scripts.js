function showRename(id) {
    const element = document.getElementById('rename-' + id);

    if (!element) {
        return;
    }

    element.style.display = 'block';

    const input = element.querySelector('input[type="text"]');

    if (input) {
        input.focus();
        input.select();
    }
}


function cancelRename(id) {
    const element = document.getElementById('rename-' + id);

    if (!element) {
        return;
    }

    element.style.display = 'none';
}
// Zentraler Bestätigungsdialog

document.addEventListener('DOMContentLoaded', function () {

    const dialog = document.getElementById('confirmDialog');

    if (!dialog) {
        return;
    }


    let currentForm = null;


    const message =
        document.getElementById('confirmMessage');

    const confirmButton =
        document.getElementById('confirmAction');

    const cancelButton =
        document.getElementById('cancelConfirm');


    document.querySelectorAll('.confirm-form, .confirm-action')
        .forEach(function (element) {


        const form =
            element.tagName === 'FORM'
                ? element
                : element.closest('form');


        if (!form) {
            return;
        }


        element.addEventListener('click', function (event) {

            event.preventDefault();

            currentForm = form;

            message.textContent =
                element.dataset.confirm || '';

            dialog.showModal();

        });

    });


    cancelButton.addEventListener('click', function () {

        dialog.close();

        currentForm = null;

    });


    confirmButton.addEventListener('click', function () {

        if (currentForm) {

            HTMLFormElement.prototype.submit.call(currentForm);

        }

    });

});

// Toast

document.addEventListener('DOMContentLoaded', function () {

    const notice = document.querySelector('.notice');

    if (notice) {
        setTimeout(function () {
            notice.remove();
        }, 4200);
    }


    // Login-Fehler fokussieren

    const loginError = document.getElementById('login-error');

    if (loginError) {
        loginError.focus();
    }


    // Suche ohne Page Refresh

    const searchForm = document.querySelector('.search form');

    if (!searchForm) {
        return;
    }

    const results = document.getElementById('search-results');

    if (!results) {
        return;
    }


    function performSearch() {

        const formData = new FormData(searchForm);
        const params = new URLSearchParams(formData);

        const url =
            window.location.pathname +
            '?' +
            params.toString();

        fetch(url)
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {

                const parser = new DOMParser();

                const newDocument =
                    parser.parseFromString(
                        html,
                        'text/html'
                    );

                const newResults =
                    newDocument.getElementById(
                        'search-results'
                    );

                if (!newResults) {
                    return;
                }

                results.innerHTML =
                    newResults.innerHTML;

                window.history.replaceState(
                    {},
                    '',
                    url
                );
            });
    }


    searchForm.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();

            performSearch();
        }
    );


    const filters =
    searchForm.querySelectorAll(
        'select[name="cms_version"], select[name="subcategory"]'
    );

filters.forEach(function (filter) {

    filter.addEventListener(
        'change',
        function () {

            performSearch();

        }
    );

});
    
        const searchInput =
        searchForm.querySelector(
            'input[name="q"]'
        );

    if (searchInput) {

        let searchTimer;

        searchInput.addEventListener(
            'input',
            function () {

                clearTimeout(searchTimer);

                searchTimer = setTimeout(
                    function () {
                        performSearch();
                    },
                    500
                );

            }
        );
    }

});