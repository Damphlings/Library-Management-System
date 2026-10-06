/* =========================================================
   DELETE BOOK
========================================================= */

function deleteBook(
    bookId,
    bookTitle
) {

    const confirmed =
        confirm(
            'Are you sure you want to delete "' +
            bookTitle +
            '"?\n\n' +
            'This action cannot be undone.'
        );


    if (!confirmed) {

        return;

    }


    document.getElementById(
        'deleteBookId'
    ).value = bookId;


    document.getElementById(
        'deleteForm'
    ).submit();

}


/* =========================================================
   AUTO HIDE ALERT
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        setTimeout(
            function () {

                const alerts =
                    document.querySelectorAll(
                        '.alert'
                    );


                alerts.forEach(
                    function (alert) {

                        alert.style.transition =
                            'opacity 0.3s ease';

                        alert.style.opacity =
                            '0';


                        setTimeout(
                            function () {

                                alert.remove();

                            },
                            300
                        );

                    }
                );

            },
            5000
        );

    }
);

/* =========================================================
   ADD BOOK FORM
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const form =
            document.getElementById('bookForm');

        const saveButton =
            document.getElementById('saveButton');


        if (!form) {

            return;

        }


        form.addEventListener(
            'submit',
            function (event) {

                const title =
                    document.getElementById('title')
                    .value
                    .trim();


                const author =
                    document.getElementById('author')
                    .value
                    .trim();


                const category =
                    document.getElementById('category')
                    .value;


                const quantity =
                    parseInt(
                        document.getElementById('quantity')
                        .value,
                        10
                    );


                /*
                |--------------------------------------------------------------------------
                | CLIENT-SIDE VALIDATION
                |--------------------------------------------------------------------------
                */

                if (
                    title === ''
                    ||
                    author === ''
                    ||
                    category === ''
                ) {

                    event.preventDefault();

                    alert(
                        'Please complete all required fields.'
                    );

                    return;

                }


                if (
                    isNaN(quantity)
                    ||
                    quantity < 1
                ) {

                    event.preventDefault();

                    alert(
                        'Quantity must be at least 1.'
                    );

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | CONFIRMATION
                |--------------------------------------------------------------------------
                */

                const confirmed =
                    confirm(
                        'Are you sure you want to add this book to the library inventory?'
                    );


                if (!confirmed) {

                    event.preventDefault();

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | PREVENT DOUBLE SUBMISSION
                |--------------------------------------------------------------------------
                */

                saveButton.disabled = true;

                saveButton.textContent =
                    'Adding Book...';

            }
        );

    }
);