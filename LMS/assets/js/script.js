/* =========================================================
   LIBRARY MANAGEMENT SYSTEM
   MAIN JAVASCRIPT
========================================================= */


/* =========================================================
   DOCUMENT READY
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    initializeSidebar();

    initializeAlerts();

    initializePasswordToggle();

    initializeDeleteConfirmation();

    initializeModal();

    initializeTableSearch();

});


/* =========================================================
   SIDEBAR
========================================================= */

function initializeSidebar() {

    const menuButton =
        document.querySelector(".menu-toggle");

    const sidebar =
        document.querySelector(".sidebar");

    const overlay =
        document.querySelector(".sidebar-overlay");


    if (!menuButton || !sidebar) {
        return;
    }


    menuButton.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle("active");

            if (overlay) {

                overlay.classList.toggle(
                    "active"
                );

            }

        }
    );


    if (overlay) {

        overlay.addEventListener(
            "click",
            function () {

                sidebar.classList.remove(
                    "active"
                );

                overlay.classList.remove(
                    "active"
                );

            }
        );

    }

}


/* =========================================================
   CLOSE SIDEBAR WHEN LINK IS CLICKED
========================================================= */

document.addEventListener(
    "click",
    function (event) {

        const sidebarLink =
            event.target.closest(
                ".sidebar a"
            );

        const sidebar =
            document.querySelector(
                ".sidebar"
            );

        const overlay =
            document.querySelector(
                ".sidebar-overlay"
            );


        if (
            sidebarLink &&
            window.innerWidth <= 768
        ) {

            if (sidebar) {

                sidebar.classList.remove(
                    "active"
                );

            }

            if (overlay) {

                overlay.classList.remove(
                    "active"
                );

            }

        }

    }
);


/* =========================================================
   ALERTS
========================================================= */

function initializeAlerts() {

    const alerts =
        document.querySelectorAll(
            ".alert"
        );


    alerts.forEach(
        function (alert) {

            const closeButton =
                alert.querySelector(
                    ".close-message"
                );


            if (closeButton) {

                closeButton.addEventListener(
                    "click",
                    function () {

                        removeElement(
                            alert
                        );

                    }
                );

            }


            /*
             * Automatically hide alerts
             * after 5 seconds.
             */

            setTimeout(
                function () {

                    if (
                        document.body.contains(
                            alert
                        )
                    ) {

                        removeElement(
                            alert
                        );

                    }

                },
                5000
            );

        }
    );

}


/* =========================================================
   REMOVE ELEMENT
========================================================= */

function removeElement(element) {

    if (!element) {
        return;
    }


    element.style.transition =
        "opacity 0.3s ease";


    element.style.opacity =
        "0";


    setTimeout(
        function () {

            if (
                element.parentNode
            ) {

                element.remove();

            }

        },
        300
    );

}


/* =========================================================
   PASSWORD SHOW / HIDE
========================================================= */

function initializePasswordToggle() {

    const toggleButtons =
        document.querySelectorAll(
            ".password-toggle"
        );


    toggleButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    const inputId =
                        button.getAttribute(
                            "data-target"
                        );


                    const input =
                        document.getElementById(
                            inputId
                        );


                    if (!input) {
                        return;
                    }


                    if (
                        input.type ===
                        "password"
                    ) {

                        input.type =
                            "text";

                        button.textContent =
                            "Hide";

                    } else {

                        input.type =
                            "password";

                        button.textContent =
                            "Show";

                    }

                }
            );

        }
    );

}


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

function initializeDeleteConfirmation() {

    const deleteButtons =
        document.querySelectorAll(
            "[data-delete]"
        );


    deleteButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    const itemName =
                        button.getAttribute(
                            "data-name"
                        ) ||
                        "this item";


                    const confirmed =
                        confirm(
                            "Are you sure you want to delete " +
                            "\"" +
                            itemName +
                            "\"?\n\n" +
                            "This action cannot be undone."
                        );


                    if (!confirmed) {

                        event.preventDefault();

                    }

                }
            );

        }
    );

}


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
            "This action cannot be undone."
        );


    if (!confirmed) {

        return;

    }


    const bookIdInput =
        document.getElementById(
            "deleteBookId"
        );


    const deleteForm =
        document.getElementById(
            "deleteForm"
        );


    if (
        bookIdInput &&
        deleteForm
    ) {

        bookIdInput.value =
            bookId;

        deleteForm.submit();

    }

}


/* =========================================================
   MODAL
========================================================= */

function initializeModal() {

    const modalOpenButtons =
        document.querySelectorAll(
            "[data-modal]"
        );


    modalOpenButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    const modalId =
                        button.getAttribute(
                            "data-modal"
                        );


                    openModal(
                        modalId
                    );

                }
            );

        }
    );


    const modalCloseButtons =
        document.querySelectorAll(
            ".modal-close"
        );


    modalCloseButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    const modal =
                        button.closest(
                            ".modal-overlay"
                        );


                    if (modal) {

                        closeModal(
                            modal.id
                        );

                    }

                }
            );

        }
    );


    /*
     * Close modal when clicking
     * outside the modal.
     */

    document.addEventListener(
        "click",
        function (event) {

            if (
                event.target.classList.contains(
                    "modal-overlay"
                )
            ) {

                closeModal(
                    event.target.id
                );

            }

        }
    );

}


/* =========================================================
   OPEN MODAL
========================================================= */

function openModal(modalId) {

    const modal =
        document.getElementById(
            modalId
        );


    if (!modal) {
        return;
    }


    modal.classList.add(
        "active"
    );


    document.body.style.overflow =
        "hidden";

}


/* =========================================================
   CLOSE MODAL
========================================================= */

function closeModal(modalId) {

    const modal =
        document.getElementById(
            modalId
        );


    if (!modal) {
        return;
    }


    modal.classList.remove(
        "active"
    );


    document.body.style.overflow =
        "";

}


/* =========================================================
   TABLE SEARCH
========================================================= */

function initializeTableSearch() {

    const searchInputs =
        document.querySelectorAll(
            "[data-table-search]"
        );


    searchInputs.forEach(
        function (input) {

            const tableId =
                input.getAttribute(
                    "data-table-search"
                );


            const table =
                document.getElementById(
                    tableId
                );


            if (!table) {
                return;
            }


            input.addEventListener(
                "input",
                function () {

                    const searchValue =
                        input.value
                            .toLowerCase()
                            .trim();


                    const rows =
                        table.querySelectorAll(
                            "tbody tr"
                        );


                    rows.forEach(
                        function (row) {

                            const rowText =
                                row.textContent
                                    .toLowerCase();


                            if (
                                rowText.includes(
                                    searchValue
                                )
                            ) {

                                row.style.display =
                                    "";

                            } else {

                                row.style.display =
                                    "none";

                            }

                        }
                    );

                }
            );

        }
    );

}


/* =========================================================
   CONFIRM FORM SUBMISSION
========================================================= */

function confirmAction(
    message
) {

    return confirm(
        message ||
        "Are you sure you want to continue?"
    );

}


/* =========================================================
   FORM VALIDATION
========================================================= */

function initializeFormValidation() {

    const forms =
        document.querySelectorAll(
            "form[data-validate]"
        );


    forms.forEach(
        function (form) {

            form.addEventListener(
                "submit",
                function (event) {

                    let valid = true;


                    const requiredFields =
                        form.querySelectorAll(
                            "[required]"
                        );


                    requiredFields.forEach(
                        function (field) {

                            field.classList.remove(
                                "input-error"
                            );


                            if (
                                field.value.trim() ===
                                ""
                            ) {

                                field.classList.add(
                                    "input-error"
                                );

                                valid = false;

                            }

                        }
                    );


                    if (!valid) {

                        event.preventDefault();

                        alert(
                            "Please complete all required fields."
                        );

                    }

                }
            );

        }
    );

}


/* =========================================================
   PASSWORD CONFIRMATION
========================================================= */

function checkPasswordMatch(
    passwordId,
    confirmPasswordId
) {

    const password =
        document.getElementById(
            passwordId
        );


    const confirmPassword =
        document.getElementById(
            confirmPasswordId
        );


    if (
        !password ||
        !confirmPassword
    ) {

        return false;

    }


    return (
        password.value ===
        confirmPassword.value
    );

}


/* =========================================================
   NUMBER INPUT
========================================================= */

function preventNegativeNumbers() {

    const numberInputs =
        document.querySelectorAll(
            'input[type="number"]'
        );


    numberInputs.forEach(
        function (input) {

            input.addEventListener(
                "input",
                function () {

                    if (
                        Number(input.value) <
                        0
                    ) {

                        input.value = 0;

                    }

                }
            );

        }
    );

}


/* =========================================================
   DATE VALIDATION
========================================================= */

function validateDates(
    startDateId,
    endDateId
) {

    const startDate =
        document.getElementById(
            startDateId
        );


    const endDate =
        document.getElementById(
            endDateId
        );


    if (
        !startDate ||
        !endDate
    ) {

        return true;

    }


    if (
        startDate.value &&
        endDate.value
    ) {

        if (
            new Date(
                endDate.value
            ) <
            new Date(
                startDate.value
            )
        ) {

            alert(
                "The return date cannot be earlier than the borrow date."
            );

            return false;

        }

    }


    return true;

}


/* =========================================================
   SIDEBAR DROPDOWN
========================================================= */

function toggleSubmenu(
    submenuId
) {

    const submenu =
        document.getElementById(
            submenuId
        );


    if (!submenu) {
        return;
    }


    submenu.classList.toggle(
        "open"
    );

}


/* =========================================================
   PRINT PAGE
========================================================= */

function printPage() {

    window.print();

}


/* =========================================================
   GO BACK
========================================================= */

function goBack() {

    window.history.back();

}


/* =========================================================
   LOGOUT CONFIRMATION
========================================================= */

function confirmLogout() {

    return confirm(
        "Are you sure you want to log out?"
    );

}


/* =========================================================
   INITIALIZE NUMBER INPUTS
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        preventNegativeNumbers();

        initializeFormValidation();

    }
);


/* =========================================================
   ESC KEY
   CLOSE MODALS / SIDEBAR
========================================================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key !==
            "Escape"
        ) {

            return;

        }


        /*
         * Close modals
         */

        const activeModal =
            document.querySelector(
                ".modal-overlay.active"
            );


        if (activeModal) {

            closeModal(
                activeModal.id
            );

        }


        /*
         * Close sidebar
         */

        const sidebar =
            document.querySelector(
                ".sidebar"
            );


        const overlay =
            document.querySelector(
                ".sidebar-overlay"
            );


        if (sidebar) {

            sidebar.classList.remove(
                "active"
            );

        }


        if (overlay) {

            overlay.classList.remove(
                "active"
            );

        }

    }
);