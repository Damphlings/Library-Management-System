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

$pageTitle   = 'Borrowers';
$currentPage = 'borrowers';


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$userName = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'User';

$userRole = $_SESSION['role']
    ?? 'librarian';


/*
|--------------------------------------------------------------------------
| DELETE BORROWER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['delete_borrower'])
) {

    $deleteId = (int) ($_POST['borrower_id'] ?? 0);


    if ($deleteId > 0) {

        try {

            /*
            |--------------------------------------------------------------
            | CHECK FOR ACTIVE BORROWINGS
            |--------------------------------------------------------------
            */

            $checkStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM borrowings
                WHERE borrower_id = ?
                AND status IN ('borrowed', 'overdue')
            ");

            $checkStmt->execute([$deleteId]);

            $activeBorrowings = (int) $checkStmt->fetchColumn();


            if ($activeBorrowings > 0) {

                header(
                    "Location: index.php?delete_error=borrowed"
                );

                exit();

            }


            /*
            |--------------------------------------------------------------
            | DELETE BORROWER
            |--------------------------------------------------------------
            */

            $deleteStmt = $pdo->prepare("
                DELETE FROM borrowers
                WHERE id = ?
            ");

            $deleteStmt->execute([$deleteId]);


            /*
            |--------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------
            */

            $description =
                'Deleted borrower ID: ' . $deleteId . '.';


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
                    'DELETE',
                    ?
                )
            ");

            $logStmt->execute([
                $_SESSION['user_id'],
                $description
            ]);


            header("Location: index.php?deleted=1");
            exit();


        } catch (PDOException $e) {

            header("Location: index.php?delete_error=1");
            exit();

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET BORROWERS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The borrowers table contains:
|
| id
| student_id
| full_name
| email
| contact
| course
| year_level
| status
| created_at
|
*/

$stmt = $pdo->query("
    SELECT
        id,
        student_id,
        full_name,
        email,
        contact,
        course,
        year_level,
        status,
        created_at
    FROM borrowers
    ORDER BY id DESC
");


$borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <?= htmlspecialchars($pageTitle) ?> | LMS
    </title>


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- BORROWERS CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/borrowers.css"
    >

</head>


<body>


<div class="lms-container">


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <?php

    require_once __DIR__ . '/../includes/navbar.php';

    ?>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main class="main-content">


        <section class="content borrowers-page">


            <!-- =================================================
                 PAGE HEADING
            ================================================== -->

            <div class="page-heading">


                <div>

                    <h1>
                        Borrowers
                    </h1>

                    <p>
                        Manage registered library borrowers.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="add-book-button"
                >

                    <span>+</span>

                    Add Borrower

                </a>


            </div>



            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if (
                isset($_GET['deleted'])
                &&
                $_GET['deleted'] === '1'
            ): ?>


                <div class="alert alert-success">


                    <div class="message-icon">
                        ✓
                    </div>


                    <div>

                        <strong>
                            Borrower Deleted
                        </strong>

                        <p>
                            The borrower was removed successfully.
                        </p>

                    </div>


                    <button
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>


                </div>


            <?php endif; ?>



            <!-- =================================================
                 ACTIVE BORROWING ERROR
            ================================================== -->

            <?php if (
                isset($_GET['delete_error'])
                &&
                $_GET['delete_error'] === 'borrowed'
            ): ?>


                <div class="alert alert-warning">


                    <div class="message-icon">
                        !
                    </div>


                    <div>

                        <strong>
                            Cannot Delete Borrower
                        </strong>

                        <p>
                            This borrower currently has active borrowings.
                        </p>

                    </div>


                    <button
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>


                </div>


            <?php endif; ?>



            <!-- =================================================
                 GENERAL DELETE ERROR
            ================================================== -->

            <?php if (
                isset($_GET['delete_error'])
                &&
                $_GET['delete_error'] === '1'
            ): ?>


                <div class="alert alert-error">


                    <div class="message-icon">
                        !
                    </div>


                    <div>

                        <strong>
                            Delete Failed
                        </strong>

                        <p>
                            The borrower could not be deleted.
                        </p>

                    </div>


                    <button
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>


                </div>


            <?php endif; ?>



            <!-- =================================================
                 BORROWERS TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-card-header">


                    <div>

                        <h3>
                            Registered Borrowers
                        </h3>

                        <p>
                            <?= number_format(count($borrowers)) ?>
                            borrower(s) registered
                        </p>

                    </div>


                </div>



                <div class="table-wrapper">


                    <table class="data-table">


                        <thead>

                            <tr>

                                <th>
                                    Student ID
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Course
                                </th>

                                <th>
                                    Year
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($borrowers)): ?>


                                <tr>

                                    <td
                                        colspan="8"
                                        class="empty-table"
                                    >

                                        <div class="empty-icon">
                                            👥
                                        </div>

                                        <strong>
                                            No Borrowers Found
                                        </strong>

                                        <p>
                                            There are currently no registered borrowers.
                                        </p>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $borrowers
                                    as $borrower
                                ): ?>


                                    <tr>


                                        <!-- STUDENT ID -->

                                        <td>

                                            <strong class="student-id">

                                                <?= htmlspecialchars(
                                                    $borrower['student_id']
                                                    ?? ''
                                                ) ?>

                                            </strong>

                                        </td>



                                        <!-- NAME -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $borrower['full_name']
                                                ) ?>

                                            </strong>

                                        </td>



                                        <!-- EMAIL -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $borrower['email']
                                                ?? ''
                                            ) ?>

                                        </td>



                                        <!-- CONTACT -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $borrower['contact']
                                                ?? ''
                                            ) ?>

                                        </td>



                                        <!-- COURSE -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $borrower['course']
                                                ?? ''
                                            ) ?>

                                        </td>



                                        <!-- YEAR -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $borrower['year_level']
                                                ?? ''
                                            ) ?>

                                        </td>



                                        <!-- STATUS -->

                                        <td>


                                            <?php

                                            $status =
                                                strtolower(
                                                    $borrower['status']
                                                    ?? ''
                                                );

                                            ?>


                                            <span
                                                class="borrower-status
                                                <?= $status === 'active'
                                                    ? 'status-active'
                                                    : 'status-inactive' ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst($status)
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- ACTIONS -->

                                        <td>

                                            <div class="borrower-actions">


                                                <a
                                                    href="edit.php?id=<?= (int) $borrower['id'] ?>"
                                                    class="btn btn-secondary"
                                                >
                                                    Edit
                                                </a>


                                                <form
                                                    method="POST"
                                                    action=""
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="borrower_id"
                                                        value="<?= (int) $borrower['id'] ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        name="delete_borrower"
                                                        class="btn btn-danger"
                                                        onclick="
                                                            return confirm(
                                                                'Are you sure you want to delete this borrower?'
                                                            );
                                                        "
                                                    >

                                                        Delete

                                                    </button>


                                                </form>


                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>