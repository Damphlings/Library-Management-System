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

$pageTitle = 'Book Details';
$currentPage = 'books';


/*
|--------------------------------------------------------------------------
| GET BOOK ID
|--------------------------------------------------------------------------
*/

$bookId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($bookId <= 0) {

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| GET BOOK INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("

    SELECT

        id,
        isbn,
        title,
        author,
        publisher,
        publication_year,
        category,
        description,
        quantity,
        available_quantity,
        location,
        status,
        created_at

    FROM books

    WHERE id = ?

    LIMIT 1

");

$stmt->execute([
    $bookId
]);

$book = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| BOOK NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$book) {

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| BOOK VARIABLES
|--------------------------------------------------------------------------
*/

$totalCopies =
    (int) $book['quantity'];

$availableCopies =
    (int) $book['available_quantity'];

$borrowedCopies =
    max(
        0,
        $totalCopies - $availableCopies
    );


/*
|--------------------------------------------------------------------------
| AVAILABILITY PERCENTAGE
|--------------------------------------------------------------------------
*/

$availabilityPercentage = 0;

if ($totalCopies > 0) {

    $availabilityPercentage =
        round(
            (
                $availableCopies
                / $totalCopies
            ) * 100
        );

}


/*
|--------------------------------------------------------------------------
| BORROWING HISTORY
|--------------------------------------------------------------------------
|
| This section assumes your table is named:
| borrowings
|
*/

$borrowings = [];

try {

    $borrowStmt = $pdo->prepare("

        SELECT

            b.id,
            b.borrower_id,
            b.borrow_date,
            b.due_date,
            b.return_date,
            b.status,

            br.first_name,
            br.last_name

        FROM borrowings b

        LEFT JOIN borrowers br
            ON br.id = b.borrower_id

        WHERE b.book_id = ?

        ORDER BY b.id DESC

        LIMIT 10

    ");

    $borrowStmt->execute([
        $bookId
    ]);

    $borrowings =
        $borrowStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    /*
     * If the borrowing table is not yet
     * available, the book page will still work.
     */

    $borrowings = [];

}


/*
|--------------------------------------------------------------------------
| LOAD HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>



<!-- =========================================================
     BOOK VIEW PAGE
========================================================= -->

<div class="book-view-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-heading">

        <div>

            <h2>
                Book Details
            </h2>

            <p>
                View complete information about this library book.
            </p>

        </div>


        <div class="heading-actions">

            <a
                href="index.php"
                class="back-button"
            >
                ← Back to Books
            </a>


            <a
                href="edit.php?id=<?= (int) $book['id'] ?>"
                class="edit-button"
            >
                ✎ Edit Book
            </a>

        </div>

    </div>



    <!-- =====================================================
         BOOK MAIN CARD
    ====================================================== -->

    <div class="book-main-card">


        <!-- =================================================
             BOOK COVER / ICON
        ================================================== -->

        <div class="book-cover-section">

            <div class="book-cover">

                <div class="book-cover-icon">
                    📖
                </div>

                <div class="book-cover-title">

                    <?= htmlspecialchars(
                        $book['title']
                    ) ?>

                </div>

            </div>


            <div class="book-status">

                <?php if (
                    $book['status'] === 'Available'
                    &&
                    $availableCopies > 0
                ): ?>

                    <span class="status available">

                        <span>●</span>

                        Available

                    </span>

                <?php else: ?>

                    <span class="status unavailable">

                        <span>●</span>

                        Unavailable

                    </span>

                <?php endif; ?>

            </div>

        </div>



        <!-- =================================================
             BOOK INFORMATION
        ================================================== -->

        <div class="book-information">


            <div class="book-title-area">

                <span class="book-label">
                    BOOK TITLE
                </span>

                <h1>

                    <?= htmlspecialchars(
                        $book['title']
                    ) ?>

                </h1>


                <p class="author">

                    by

                    <strong>

                        <?= htmlspecialchars(
                            $book['author']
                        ) ?>

                    </strong>

                </p>

            </div>



            <!-- BOOK DETAILS GRID -->

            <div class="details-grid">


                <!-- ISBN -->

                <div class="detail-item">

                    <span>
                        ISBN
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $book['isbn']
                            )
                        ): ?>

                            <?= htmlspecialchars(
                                $book['isbn']
                            ) ?>

                        <?php else: ?>

                            N/A

                        <?php endif; ?>

                    </strong>

                </div>



                <!-- CATEGORY -->

                <div class="detail-item">

                    <span>
                        Category
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $book['category']
                        ) ?>

                    </strong>

                </div>



                <!-- PUBLISHER -->

                <div class="detail-item">

                    <span>
                        Publisher
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $book['publisher']
                            )
                        ): ?>

                            <?= htmlspecialchars(
                                $book['publisher']
                            ) ?>

                        <?php else: ?>

                            N/A

                        <?php endif; ?>

                    </strong>

                </div>



                <!-- YEAR -->

                <div class="detail-item">

                    <span>
                        Publication Year
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $book[
                                    'publication_year'
                                ]
                            )
                        ): ?>

                            <?= htmlspecialchars(
                                $book[
                                    'publication_year'
                                ]
                            ) ?>

                        <?php else: ?>

                            N/A

                        <?php endif; ?>

                    </strong>

                </div>



                <!-- LOCATION -->

                <div class="detail-item">

                    <span>
                        Shelf / Location
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $book['location']
                            )
                        ): ?>

                            <?= htmlspecialchars(
                                $book['location']
                            ) ?>

                        <?php else: ?>

                            N/A

                        <?php endif; ?>

                    </strong>

                </div>



                <!-- DATE ADDED -->

                <div class="detail-item">

                    <span>
                        Date Added
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $book['created_at']
                            )
                        ): ?>

                            <?= date(
                                'M d, Y',
                                strtotime(
                                    $book[
                                        'created_at'
                                    ]
                                )
                            ) ?>

                        <?php else: ?>

                            N/A

                        <?php endif; ?>

                    </strong>

                </div>


            </div>

        </div>

    </div>



    <!-- =====================================================
         INVENTORY STATISTICS
    ====================================================== -->

    <div class="inventory-section">


        <div class="section-title">

            <div>

                <h3>
                    Inventory Status
                </h3>

                <p>
                    Current availability of this book.
                </p>

            </div>

        </div>



        <div class="inventory-grid">


            <!-- TOTAL -->

            <div class="inventory-card">

                <div class="inventory-icon">
                    📚
                </div>

                <span>
                    Total Copies
                </span>

                <strong>
                    <?= number_format(
                        $totalCopies
                    ) ?>
                </strong>

            </div>



            <!-- AVAILABLE -->

            <div class="inventory-card">

                <div class="inventory-icon">
                    ✓
                </div>

                <span>
                    Available Copies
                </span>

                <strong>
                    <?= number_format(
                        $availableCopies
                    ) ?>
                </strong>

            </div>



            <!-- BORROWED -->

            <div class="inventory-card">

                <div class="inventory-icon">
                    ↗
                </div>

                <span>
                    Borrowed Copies
                </span>

                <strong>
                    <?= number_format(
                        $borrowedCopies
                    ) ?>
                </strong>

            </div>


        </div>



        <!-- AVAILABILITY BAR -->

        <div class="availability-box">

            <div class="availability-header">

                <span>
                    Availability
                </span>

                <strong>
                    <?= $availabilityPercentage ?>%
                </strong>

            </div>


            <div class="progress">

                <div
                    class="progress-bar"
                    style="width: <?= $availabilityPercentage ?>%;"
                ></div>

            </div>

        </div>


    </div>



    <!-- =====================================================
         DESCRIPTION
    ====================================================== -->

    <div class="description-card">


        <div class="section-title">

            <div>

                <h3>
                    Description / Notes
                </h3>

            </div>

        </div>


        <div class="description-content">

            <?php if (
                !empty(
                    trim(
                        $book['description']
                        ?? ''
                    )
                )
            ): ?>

                <p>

                    <?= nl2br(
                        htmlspecialchars(
                            $book[
                                'description'
                            ]
                        )
                    ) ?>

                </p>

            <?php else: ?>

                <p class="no-description">

                    No description or notes have been added
                    for this book.

                </p>

            <?php endif; ?>

        </div>

    </div>



    <!-- =====================================================
         BORROWING HISTORY
    ====================================================== -->

    <div class="history-card">


        <div class="section-title">

            <div>

                <h3>
                    Borrowing History
                </h3>

                <p>
                    Recent borrowing records for this book.
                </p>

            </div>


            <a
                href="../barrowing/index.php"
                class="view-all-button"
            >

                View All

            </a>

        </div>



        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Borrower
                        </th>

                        <th>
                            Borrow Date
                        </th>

                        <th>
                            Due Date
                        </th>

                        <th>
                            Return Date
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        empty(
                            $borrowings
                        )
                    ): ?>

                        <tr>

                            <td
                                colspan="5"
                                class="empty-history"
                            >

                                <div>
                                    📋
                                </div>

                                <strong>
                                    No Borrowing Records
                                </strong>

                                <p>
                                    This book has not been borrowed yet.
                                </p>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $borrowings
                            as $record
                        ): ?>


                            <tr>


                                <!-- BORROWER -->

                                <td>

                                    <?php

                                    $borrowerName =
                                        trim(
                                            (
                                                $record[
                                                    'first_name'
                                                ]
                                                ?? ''
                                            )
                                            . ' '
                                            .
                                            (
                                                $record[
                                                    'last_name'
                                                ]
                                                ?? ''
                                            )
                                        );

                                    ?>


                                    <?php if (
                                        $borrowerName
                                        !== ''
                                    ): ?>

                                        <strong
                                            class="borrower-name"
                                        >

                                            <?= htmlspecialchars(
                                                $borrowerName
                                            ) ?>

                                        </strong>

                                    <?php else: ?>

                                        <span
                                            class="not-available"
                                        >
                                            Unknown Borrower
                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- BORROW DATE -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $record[
                                                'borrow_date'
                                            ]
                                        )
                                    ): ?>

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $record[
                                                    'borrow_date'
                                                ]
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>



                                <!-- DUE DATE -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $record[
                                                'due_date'
                                            ]
                                        )
                                    ): ?>

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $record[
                                                    'due_date'
                                                ]
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>



                                <!-- RETURN DATE -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $record[
                                                'return_date'
                                            ]
                                        )
                                    ): ?>

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $record[
                                                    'return_date'
                                                ]
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        <span
                                            class="not-returned"
                                        >

                                            Not Returned

                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $recordStatus =
                                        $record[
                                            'status'
                                        ]
                                        ?? '';

                                    ?>


                                    <?php if (
                                        $recordStatus
                                        ===
                                        'Returned'
                                    ): ?>

                                        <span
                                            class="history-status returned"
                                        >

                                            Returned

                                        </span>


                                    <?php elseif (
                                        $recordStatus
                                        ===
                                        'Overdue'
                                    ): ?>

                                        <span
                                            class="history-status overdue"
                                        >

                                            Overdue

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="history-status borrowed"
                                        >

                                            Borrowed

                                        </span>

                                    <?php endif; ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


    </div>



    <!-- =====================================================
         ACTIONS
    ====================================================== -->

    <div class="bottom-actions">


        <a
            href="index.php"
            class="btn-secondary"
        >

            ← Back to Inventory

        </a>


        <a
            href="edit.php?id=<?= (int) $book['id'] ?>"
            class="btn-primary"
        >

            ✎ Edit Book

        </a>


    </div>


</div>



<!-- =========================================================
     CSS
========================================================= -->

<style>

/* =========================================================
   PAGE
========================================================= */

.book-view-page {
    width: 100%;
}


/* =========================================================
   PAGE HEADING
========================================================= */

.page-heading {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 25px;
}

.page-heading h2 {
    color: #001F54;

    font-size: 24px;

    margin-bottom: 5px;
}

.page-heading p {
    color: #6C757D;

    font-size: 13px;
}

.heading-actions {
    display: flex;

    gap: 8px;
}

.back-button,
.edit-button {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 40px;

    padding: 0 15px;

    border-radius: 6px;

    font-size: 11px;

    font-weight: bold;

    text-decoration: none;
}

.back-button {
    background: #ffffff;

    color: #001F54;

    border: 1px solid #dee2e6;
}

.edit-button {
    background: #001F54;

    color: #ffffff;
}

.edit-button:hover {
    background: #00337F;
}


/* =========================================================
   MAIN BOOK CARD
========================================================= */

.book-main-card {
    display: grid;

    grid-template-columns: 230px 1fr;

    background: #ffffff;

    border: 1px solid #e1e5e9;

    border-radius: 10px;

    overflow: hidden;

    margin-bottom: 20px;
}


/* =========================================================
   BOOK COVER
========================================================= */

.book-cover-section {
    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    padding: 30px;

    background: #001F54;
}

.book-cover {
    width: 135px;

    min-height: 180px;

    padding: 20px 15px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    background: #ffffff;

    border-radius: 4px;

    box-shadow:
        0 8px 20px rgba(0,0,0,0.15);
}

.book-cover-icon {
    font-size: 45px;

    margin-bottom: 15px;
}

.book-cover-title {
    color: #001F54;

    font-size: 11px;

    font-weight: bold;

    line-height: 1.4;

    display: -webkit-box;

    -webkit-line-clamp: 5;

    -webkit-box-orient: vertical;

    overflow: hidden;
}


/* =========================================================
   BOOK STATUS
========================================================= */

.book-status {
    margin-top: 20px;
}

.status {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 12px;

    border-radius: 20px;

    background: #ffffff;

    font-size: 10px;

    font-weight: bold;
}

.status.available {
    color: #198754;
}

.status.unavailable {
    color: #dc3545;
}


/* =========================================================
   BOOK INFORMATION
========================================================= */

.book-information {
    padding: 30px;
}

.book-label {
    color: #6C757D;

    font-size: 9px;

    font-weight: bold;

    letter-spacing: 1px;
}

.book-title-area h1 {
    color: #001F54;

    font-size: 25px;

    line-height: 1.3;

    margin: 7px 0;
}

.author {
    color: #6C757D;

    font-size: 12px;
}

.author strong {
    color: #343a40;
}


/* =========================================================
   DETAILS GRID
========================================================= */

.details-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-top: 25px;

    padding-top: 20px;

    border-top: 1px solid #e9ecef;
}

.detail-item {
    min-width: 0;
}

.detail-item span {
    display: block;

    color: #6C757D;

    font-size: 9px;

    text-transform: uppercase;

    margin-bottom: 5px;
}

.detail-item strong {
    display: block;

    color: #001F54;

    font-size: 11px;

    word-break: break-word;
}


/* =========================================================
   INVENTORY SECTION
========================================================= */

.inventory-section,
.description-card,
.history-card {
    background: #ffffff;

    border: 1px solid #e1e5e9;

    border-radius: 10px;

    padding: 25px;

    margin-bottom: 20px;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 20px;
}

.section-title h3 {
    color: #001F54;

    font-size: 15px;

    margin-bottom: 4px;
}

.section-title p {
    color: #6C757D;

    font-size: 10px;
}


/* =========================================================
   INVENTORY GRID
========================================================= */

.inventory-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;
}

.inventory-card {
    padding: 18px;

    background: #f5f7f9;

    border-radius: 7px;

    text-align: center;
}

.inventory-icon {
    color: #001F54;

    font-size: 20px;

    margin-bottom: 7px;
}

.inventory-card span {
    display: block;

    color: #6C757D;

    font-size: 10px;

    margin-bottom: 4px;
}

.inventory-card strong {
    color: #001F54;

    font-size: 23px;
}


/* =========================================================
   AVAILABILITY
========================================================= */

.availability-box {
    margin-top: 20px;
}

.availability-header {
    display: flex;

    justify-content: space-between;

    margin-bottom: 7px;

    color: #6C757D;

    font-size: 10px;
}

.availability-header strong {
    color: #001F54;
}

.progress {
    width: 100%;

    height: 8px;

    background: #e9ecef;

    border-radius: 20px;

    overflow: hidden;
}

.progress-bar {
    height: 100%;

    background: #001F54;

    border-radius: 20px;

    transition: width 0.3s ease;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.description-content {
    padding: 15px;

    background: #f8f9fa;

    border-radius: 6px;
}

.description-content p {
    color: #495057;

    font-size: 11px;

    line-height: 1.7;
}

.no-description {
    color: #adb5bd !important;

    font-style: italic;
}


/* =========================================================
   HISTORY TABLE
========================================================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 650px;
}

thead {
    background: #f5f7f9;
}

th {
    padding: 11px 12px;

    color: #001F54;

    font-size: 9px;

    text-align: left;

    text-transform: uppercase;
}

td {
    padding: 13px 12px;

    color: #495057;

    font-size: 10px;

    border-top: 1px solid #edf0f2;
}

.borrower-name {
    color: #001F54;

    font-size: 10px;
}

.not-available {
    color: #adb5bd;
}

.not-returned {
    color: #dc3545;

    font-size: 9px;
}


/* =========================================================
   HISTORY STATUS
========================================================= */

.history-status {
    display: inline-block;

    padding: 5px 8px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: bold;
}

.history-status.returned {
    background: #e8f5e9;

    color: #198754;
}

.history-status.borrowed {
    background: #fff3cd;

    color: #856404;
}

.history-status.overdue {
    background: #f8d7da;

    color: #dc3545;
}


/* =========================================================
   EMPTY HISTORY
========================================================= */

.empty-history {
    text-align: center;

    padding: 40px 15px !important;
}

.empty-history div {
    font-size: 30px;

    opacity: 0.5;

    margin-bottom: 8px;
}

.empty-history strong {
    display: block;

    color: #001F54;

    font-size: 12px;

    margin-bottom: 4px;
}

.empty-history p {
    color: #6C757D;

    font-size: 10px;
}


/* =========================================================
   VIEW ALL
========================================================= */

.view-all-button {
    color: #001F54;

    font-size: 10px;

    font-weight: bold;

    text-decoration: none;
}


/* =========================================================
   BOTTOM ACTIONS
========================================================= */

.bottom-actions {
    display: flex;

    justify-content: flex-end;

    gap: 10px;

    margin-bottom: 25px;
}

.btn-primary,
.btn-secondary {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 40px;

    padding: 0 18px;

    border-radius: 6px;

    font-size: 11px;

    font-weight: bold;

    text-decoration: none;
}

.btn-primary {
    background: #001F54;

    color: #ffffff;
}

.btn-primary:hover {
    background: #00337F;
}

.btn-secondary {
    background: #ffffff;

    color: #001F54;

    border: 1px solid #dee2e6;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 800px) {

    .book-main-card {
        grid-template-columns: 1fr;
    }

    .book-cover-section {
        padding: 25px;
    }

    .details-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .inventory-grid {
        grid-template-columns:
            repeat(3, 1fr);
    }

}


@media (max-width: 600px) {

    .page-heading {
        flex-direction: column;

        align-items: flex-start;
    }

    .heading-actions {
        width: 100%;
    }

    .back-button,
    .edit-button {
        flex: 1;
    }

    .book-information {
        padding: 20px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .inventory-grid {
        grid-template-columns: 1fr;
    }

    .bottom-actions {
        flex-direction: column;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
    }

}

</style>



<?php

require_once __DIR__ . '/../includes/footer.php';

?>