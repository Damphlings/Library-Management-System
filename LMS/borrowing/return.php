<?php

session_start();

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle   = 'Return Book';
$currentPage = 'returns';

$error = '';


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$borrowingId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $borrowingId = (int) (
        $_POST['borrowing_id'] ?? 0
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($borrowingId <= 0) {

        $error =
            'Invalid borrowing record.';

    }


    /*
    |--------------------------------------------------------------------------
    | PROCESS RETURN
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {


            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | GET BORROWING RECORD
            |--------------------------------------------------------------------------
            |
            | borrowers.id is used internally.
            | borrowers.student_id is displayed to the user.
            |
            */

            $stmt = $pdo->prepare("

                SELECT

                    borrowings.id,
                    borrowings.borrower_id,
                    borrowings.book_id,
                    borrowings.borrow_date,
                    borrowings.due_date,
                    borrowings.status,

                    books.title,
                    books.author,
                    books.book_code,
                    books.quantity,
                    books.available_quantity,

                    borrowers.student_id,
                    borrowers.full_name

                FROM borrowings

                INNER JOIN books
                    ON books.id = borrowings.book_id

                INNER JOIN borrowers
                    ON borrowers.id = borrowings.borrower_id

                WHERE borrowings.id = ?

                LIMIT 1

                FOR UPDATE

            ");


            $stmt->execute([

                $borrowingId

            ]);


            $borrowing =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /*
            |--------------------------------------------------------------------------
            | CHECK BORROWING RECORD
            |--------------------------------------------------------------------------
            */

            if (!$borrowing) {

                throw new Exception(
                    'The borrowing record does not exist.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | CHECK CURRENT STATUS
            |--------------------------------------------------------------------------
            */

            if (
                strtolower(
                    trim(
                        $borrowing['status']
                    )
                ) !== 'borrowed'
            ) {

                throw new Exception(
                    'This book has already been returned.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE BORROWING
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                UPDATE borrowings

                SET

                    return_date = CURDATE(),
                    status = 'Returned'

                WHERE id = ?

            ");


            $stmt->execute([

                $borrowingId

            ]);


            /*
            |--------------------------------------------------------------------------
            | INCREASE AVAILABLE QUANTITY
            |--------------------------------------------------------------------------
            |
            | Do not allow available_quantity to exceed quantity.
            |
            */

            $stmt = $pdo->prepare("

                UPDATE books

                SET

                    available_quantity =
                        available_quantity + 1

                WHERE id = ?

                AND available_quantity < quantity

            ");


            $stmt->execute([

                $borrowing['book_id']

            ]);


            /*
            |--------------------------------------------------------------------------
            | VERIFY BOOK QUANTITY UPDATE
            |--------------------------------------------------------------------------
            */

            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    'Unable to update the book availability.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE BOOK STATUS
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                UPDATE books

                SET

                    status =
                        CASE

                            WHEN available_quantity > 0
                            THEN 'Available'

                            ELSE 'Unavailable'

                        END

                WHERE id = ?

            ");


            $stmt->execute([

                $borrowing['book_id']

            ]);


            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $descriptionLog =

                'Returned book: '

                . $borrowing['title']

                . ' by '

                . $borrowing['full_name']

                . ' ('

                . $borrowing['student_id']

                . ').';


            $logStmt = $pdo->prepare("

                INSERT INTO activity_logs

                (
                    user_id,
                    action,
                    description
                )

                VALUES

                (
                    ?,
                    'RETURN',
                    ?
                )

            ");


            $logStmt->execute([

                $_SESSION['user_id'],

                $descriptionLog

            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: index.php?returned=1"
            );

            exit();


        } catch (Exception $e) {


            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();

            }


            $error =
                $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET CURRENT BORROWINGS
|--------------------------------------------------------------------------
|
| Only currently borrowed books are shown.
|
*/

$stmt = $pdo->query("

    SELECT

        borrowings.id,
        borrowings.borrow_date,
        borrowings.due_date,
        borrowings.status,

        books.title,
        books.author,
        books.book_code,

        borrowers.student_id,
        borrowers.full_name

    FROM borrowings

    INNER JOIN books
        ON books.id = borrowings.book_id

    INNER JOIN borrowers
        ON borrowers.id = borrowings.borrower_id

    WHERE borrowings.status = 'Borrowed'

    ORDER BY

        borrowings.due_date ASC,
        borrowings.borrow_date ASC

");


$borrowings =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?>
    </title>


    <!-- =====================================================
         GLOBAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- =====================================================
         RETURN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/return.css"
    >

</head>


<body>


<div class="lms-container">


    <!-- =====================================================
         SHARED NAVBAR
    ====================================================== -->

    <?php

    require_once __DIR__ . '/../includes/navbar.php';

    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <div class="borrowing-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-heading">


                <div>

                    <h2>
                        Return Book
                    </h2>

                    <p>
                        Process and record returned library books.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="back-button"
                >

                    ← Back to Borrowing

                </a>


            </div>



            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-error">


                    <span class="alert-icon">
                        !
                    </span>


                    <div>

                        <strong>
                            Unable to process return
                        </strong>

                        <p>
                            <?= htmlspecialchars($error) ?>
                        </p>

                    </div>


                </div>

            <?php endif; ?>



            <!-- =================================================
                 RETURN CARD
            ================================================== -->

            <div class="form-card">


                <!-- =================================================
                     FORM HEADER
                ================================================== -->

                <div class="form-card-header">


                    <div class="header-icon">
                        ↩
                    </div>


                    <div>

                        <h3>
                            Return Information
                        </h3>

                        <p>
                            Select a currently borrowed book to process its return.
                        </p>

                    </div>


                </div>



                <!-- =================================================
                     FORM CONTENT
                ================================================== -->

                <div class="return-content">


                    <?php if (empty($borrowings)): ?>


                        <!-- =================================================
                             EMPTY STATE
                        ================================================== -->

                        <div class="empty-state">


                            <div class="empty-icon">
                                ✓
                            </div>


                            <h3>
                                No Borrowed Books
                            </h3>


                            <p>
                                There are currently no books waiting
                                to be returned.
                            </p>


                            <a
                                href="index.php"
                                class="btn btn-primary"
                            >

                                Back to Borrowing

                            </a>


                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             BORROWER / BOOK SELECTION
                        ================================================== -->

                        <form
                            method="POST"
                            action=""
                            id="returnForm"
                        >


                            <div class="form-section">


                                <h4>
                                    Borrowing Information
                                </h4>


                                <div class="form-grid">


                                    <!-- BORROWING RECORD -->

                                    <div class="form-group">


                                        <label for="borrowing_id">

                                            Borrowed Book

                                            <span class="required">
                                                *
                                            </span>

                                        </label>


                                        <select
                                            id="borrowing_id"
                                            name="borrowing_id"
                                            required
                                        >


                                            <option value="">

                                                Select Borrowed Book

                                            </option>


                                            <?php foreach (
                                                $borrowings
                                                as $borrowing
                                            ): ?>


                                                <option
                                                    value="<?= (int) $borrowing['id'] ?>"
                                                    <?= (
                                                        $borrowingId ===
                                                        (int) $borrowing['id']
                                                    )
                                                        ? 'selected'
                                                        : '' ?>
                                                >

                                                    <?= htmlspecialchars(
                                                        $borrowing['full_name']
                                                    ) ?>

                                                    —

                                                    <?= htmlspecialchars(
                                                        $borrowing['title']
                                                    ) ?>

                                                    —

                                                    Due:

                                                    <?= htmlspecialchars(
                                                        date(
                                                            'M d, Y',
                                                            strtotime(
                                                                $borrowing['due_date']
                                                            )
                                                        )
                                                    ) ?>

                                                </option>


                                            <?php endforeach; ?>


                                        </select>


                                        <small>
                                            Select the book being returned.
                                        </small>


                                    </div>


                                </div>


                            </div>



                            <!-- =================================================
                                 SELECTED BORROWING DETAILS
                            ================================================== -->

                            <div
                                class="form-section"
                                id="returnDetails"
                            >


                                <h4>
                                    Borrowing Details
                                </h4>


                                <div class="form-grid">


                                    <!-- BORROWER -->

                                    <div class="form-group">


                                        <label>
                                            Borrower
                                        </label>


                                        <input
                                            type="text"
                                            id="borrowerName"
                                            readonly
                                            placeholder="Select a borrowed book"
                                        >


                                    </div>



                                    <!-- STUDENT ID -->

                                    <div class="form-group">


                                        <label>
                                            Student ID
                                        </label>


                                        <input
                                            type="text"
                                            id="studentId"
                                            readonly
                                            placeholder="—"
                                        >


                                    </div>



                                    <!-- BOOK -->

                                    <div class="form-group">


                                        <label>
                                            Book
                                        </label>


                                        <input
                                            type="text"
                                            id="bookTitle"
                                            readonly
                                            placeholder="Select a borrowed book"
                                        >


                                    </div>



                                    <!-- AUTHOR -->

                                    <div class="form-group">


                                        <label>
                                            Author
                                        </label>


                                        <input
                                            type="text"
                                            id="bookAuthor"
                                            readonly
                                            placeholder="—"
                                        >


                                    </div>



                                    <!-- BORROW DATE -->

                                    <div class="form-group">


                                        <label>
                                            Borrow Date
                                        </label>


                                        <input
                                            type="text"
                                            id="borrowDate"
                                            readonly
                                            placeholder="—"
                                        >


                                    </div>



                                    <!-- DUE DATE -->

                                    <div class="form-group">


                                        <label>
                                            Due Date
                                        </label>


                                        <input
                                            type="text"
                                            id="dueDate"
                                            readonly
                                            placeholder="—"
                                        >


                                    </div>


                                </div>


                            </div>



                            <!-- =================================================
                                 RETURN INFORMATION
                            ================================================== -->

                            <div class="info-box">


                                <div class="info-icon">
                                    i
                                </div>


                                <div>

                                    <strong>
                                        Return Information
                                    </strong>

                                    <p>
                                        Processing this return will mark the
                                        borrowing transaction as returned,
                                        record today's date as the return date,
                                        and increase the book's available quantity
                                        by one.
                                    </p>

                                </div>


                            </div>



                            <!-- =================================================
                                 REQUIRED NOTE
                            ================================================== -->

                            <div class="required-note">

                                <span class="required">
                                    *
                                </span>

                                Required fields

                            </div>



                            <!-- =================================================
                                 FORM ACTIONS
                            ================================================== -->

                            <div class="form-actions">


                                <a
                                    href="index.php"
                                    class="btn btn-secondary"
                                >

                                    Cancel

                                </a>


                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    id="returnButton"
                                    disabled
                                >

                                    ↩ Return Book

                                </button>


                            </div>


                        </form>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const borrowingSelect =
            document.getElementById(
                'borrowing_id'
            );


        const returnButton =
            document.getElementById(
                'returnButton'
            );


        const borrowerName =
            document.getElementById(
                'borrowerName'
            );


        const studentId =
            document.getElementById(
                'studentId'
            );


        const bookTitle =
            document.getElementById(
                'bookTitle'
            );


        const bookAuthor =
            document.getElementById(
                'bookAuthor'
            );


        const borrowDate =
            document.getElementById(
                'borrowDate'
            );


        const dueDate =
            document.getElementById(
                'dueDate'
            );


        if (!borrowingSelect) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | BORROWING DATA
        |--------------------------------------------------------------------------
        */

        const borrowingData = {

            <?php foreach (
                $borrowings
                as $borrowing
            ): ?>

                <?= (int) $borrowing['id'] ?>: {

                    borrower:
                        <?= json_encode(
                            $borrowing['full_name']
                        ) ?>,

                    studentId:
                        <?= json_encode(
                            $borrowing['student_id']
                        ) ?>,

                    book:
                        <?= json_encode(
                            $borrowing['title']
                        ) ?>,

                    author:
                        <?= json_encode(
                            $borrowing['author']
                        ) ?>,

                    borrowDate:
                        <?= json_encode(
                            date(
                                'M d, Y',
                                strtotime(
                                    $borrowing['borrow_date']
                                )
                            )
                        ) ?>,

                    dueDate:
                        <?= json_encode(
                            date(
                                'M d, Y',
                                strtotime(
                                    $borrowing['due_date']
                                )
                            )
                        ) ?>

                },

            <?php endforeach; ?>

        };


        /*
        |--------------------------------------------------------------------------
        | UPDATE DETAILS
        |--------------------------------------------------------------------------
        */

        function updateDetails() {


            const selectedId =
                borrowingSelect.value;


            if (
                !selectedId
                ||
                !borrowingData[selectedId]
            ) {


                borrowerName.value = '';

                studentId.value = '';

                bookTitle.value = '';

                bookAuthor.value = '';

                borrowDate.value = '';

                dueDate.value = '';

                returnButton.disabled = true;

                return;

            }


            const data =
                borrowingData[selectedId];


            borrowerName.value =
                data.borrower;


            studentId.value =
                data.studentId;


            bookTitle.value =
                data.book;


            bookAuthor.value =
                data.author;


            borrowDate.value =
                data.borrowDate;


            dueDate.value =
                data.dueDate;


            returnButton.disabled = false;

        }


        /*
        |--------------------------------------------------------------------------
        | SELECT CHANGE
        |--------------------------------------------------------------------------
        */

        borrowingSelect.addEventListener(
            'change',
            updateDetails
        );


        /*
        |--------------------------------------------------------------------------
        | INITIAL LOAD
        |--------------------------------------------------------------------------
        */

        updateDetails();


        /*
        |--------------------------------------------------------------------------
        | CONFIRM RETURN
        |--------------------------------------------------------------------------
        */

        const returnForm =
            document.getElementById(
                'returnForm'
            );


        if (returnForm) {


            returnForm.addEventListener(
                'submit',
                function (event) {


                    const selectedId =
                        borrowingSelect.value;


                    if (
                        !selectedId
                        ||
                        !borrowingData[selectedId]
                    ) {

                        event.preventDefault();

                        alert(
                            'Please select a borrowed book.'
                        );

                        return;

                    }


                    const data =
                        borrowingData[selectedId];


                    const confirmed =
                        confirm(

                            'Confirm book return?'

                            + '\n\n'

                            + 'Borrower: '
                            + data.borrower

                            + '\n'

                            + 'Book: '
                            + data.book

                        );


                    if (!confirmed) {

                        event.preventDefault();

                        return;

                    }


                    returnButton.disabled = true;

                    returnButton.textContent =
                        'Processing...';

                }
            );

        }

    }
);

</script>


</body>

</html>