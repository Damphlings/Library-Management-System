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

$pageTitle   = 'Edit Borrower';
$currentPage = 'borrowers';

$error = '';


/*
|--------------------------------------------------------------------------
| GET BORROWER ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($id <= 0) {

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| GET BORROWER
|--------------------------------------------------------------------------
|
| borrowers table columns used:
|
| id
| student_id
| full_name
| email
| contact
| course
| year_level
| status
|
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("

    SELECT

        id,
        student_id,
        full_name,
        email,
        contact,
        course,
        year_level,
        status

    FROM borrowers

    WHERE id = ?

    LIMIT 1

");


$stmt->execute([

    $id

]);


$borrower = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| BORROWER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$borrower) {

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$student_id = $borrower['student_id'] ?? '';

$full_name = $borrower['full_name'] ?? '';

$email = $borrower['email'] ?? '';

$contact = $borrower['contact'] ?? '';

$course = $borrower['course'] ?? '';

$year_level = $borrower['year_level'] ?? '';

$status = $borrower['status'] ?? 'Active';


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

    $student_id = trim(
        $_POST['student_id'] ?? ''
    );


    $full_name = trim(
        $_POST['full_name'] ?? ''
    );


    $email = trim(
        $_POST['email'] ?? ''
    );


    $contact = trim(
        $_POST['contact'] ?? ''
    );


    $course = trim(
        $_POST['course'] ?? ''
    );


    $year_level = trim(
        $_POST['year_level'] ?? ''
    );


    $status = trim(
        $_POST['status'] ?? 'Active'
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($student_id === '') {

        $error =
            'Student ID is required.';

    } elseif ($full_name === '') {

        $error =
            'Full name is required.';

    } elseif (
        $email !== ''
        &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } elseif ($course === '') {

        $error =
            'Course or program is required.';

    } elseif ($year_level === '') {

        $error =
            'Year level is required.';

    } elseif (
        !in_array(
            $status,
            [
                'Active',
                'Inactive'
            ],
            true
        )
    ) {

        $error =
            'Invalid borrower status.';

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE STUDENT ID
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = $pdo->prepare("

            SELECT id

            FROM borrowers

            WHERE student_id = ?

            AND id != ?

            LIMIT 1

        ");


        $stmt->execute([

            $student_id,

            $id

        ]);


        if ($stmt->fetch()) {

            $error =
                'The Student ID already belongs to another borrower.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE BORROWER
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
            | UPDATE BORROWER
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                UPDATE borrowers

                SET

                    student_id = ?,
                    full_name = ?,
                    email = ?,
                    contact = ?,
                    course = ?,
                    year_level = ?,
                    status = ?

                WHERE id = ?

            ");


            $stmt->execute([

                $student_id,

                $full_name,

                $email !== ''
                    ? $email
                    : null,

                $contact !== ''
                    ? $contact
                    : null,

                $course,

                $year_level,

                $status,

                $id

            ]);


            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $descriptionLog =

                'Updated borrower: '

                . $full_name

                . ' (Student ID: '

                . $student_id

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
                    'UPDATE',
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
                "Location: index.php?updated=1"
            );

            exit();


        } catch (PDOException $e) {


            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =
                'Unable to update the borrower. Database error: '
                . $e->getMessage();

        }

    }

}

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


    <!-- =====================================================
         NAVBAR
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

        <div class="borrowers-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-heading">


                <div>

                    <h2>
                        Edit Borrower
                    </h2>

                    <p>
                        Update the borrower's information.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="back-button"
                >

                    ← Back to Borrowers

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
                            Unable to update borrower
                        </strong>


                        <p>

                            <?= htmlspecialchars(
                                $error
                            ) ?>

                        </p>

                    </div>


                </div>

            <?php endif; ?>



            <!-- =================================================
                 FORM CARD
            ================================================== -->

            <div class="form-card">


                <!-- FORM HEADER -->

                <div class="form-card-header">


                    <div class="header-icon">
                        ✎
                    </div>


                    <div>

                        <h3>
                            Borrower Information
                        </h3>

                        <p>
                            Update the information below.
                        </p>

                    </div>


                </div>



                <!-- FORM -->

                <form
                    method="POST"
                    action=""
                    id="borrowerForm"
                >


                    <!-- =========================================
                         PERSONAL INFORMATION
                    ========================================== -->

                    <div class="form-section">


                        <h4>
                            Personal Information
                        </h4>


                        <div class="form-grid">


                            <!-- STUDENT ID -->

                            <div class="form-group">

                                <label for="student_id">

                                    Student ID

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="student_id"
                                    name="student_id"
                                    value="<?= htmlspecialchars($student_id) ?>"
                                    maxlength="50"
                                    placeholder="Enter student ID"
                                    required
                                >


                                <small>
                                    Student ID must be unique.
                                </small>

                            </div>



                            <!-- FULL NAME -->

                            <div class="form-group">

                                <label for="full_name">

                                    Full Name

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    value="<?= htmlspecialchars($full_name) ?>"
                                    maxlength="150"
                                    placeholder="Enter full name"
                                    required
                                >

                            </div>



                            <!-- EMAIL -->

                            <div class="form-group">

                                <label for="email">

                                    Email Address

                                </label>


                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    maxlength="150"
                                    placeholder="example@email.com"
                                >

                            </div>



                            <!-- CONTACT -->

                            <div class="form-group">

                                <label for="contact">

                                    Contact Number

                                </label>


                                <input
                                    type="text"
                                    id="contact"
                                    name="contact"
                                    value="<?= htmlspecialchars($contact) ?>"
                                    maxlength="30"
                                    placeholder="09XXXXXXXXX"
                                >

                            </div>


                        </div>


                    </div>



                    <!-- =========================================
                         SCHOOL INFORMATION
                    ========================================== -->

                    <div class="form-section">


                        <h4>
                            School Information
                        </h4>


                        <div class="form-grid">


                            <!-- COURSE -->

                            <div class="form-group">

                                <label for="course">

                                    Course / Program

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="course"
                                    name="course"
                                    value="<?= htmlspecialchars($course) ?>"
                                    maxlength="150"
                                    placeholder="Enter course or program"
                                    required
                                >

                            </div>



                            <!-- YEAR LEVEL -->

                            <div class="form-group">

                                <label for="year_level">

                                    Year Level

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="year_level"
                                    name="year_level"
                                    required
                                >


                                    <option value="">
                                        Select Year Level
                                    </option>


                                    <option
                                        value="1st Year"
                                        <?= $year_level === '1st Year'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        1st Year

                                    </option>


                                    <option
                                        value="2nd Year"
                                        <?= $year_level === '2nd Year'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        2nd Year

                                    </option>


                                    <option
                                        value="3rd Year"
                                        <?= $year_level === '3rd Year'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        3rd Year

                                    </option>


                                    <option
                                        value="4th Year"
                                        <?= $year_level === '4th Year'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        4th Year

                                    </option>


                                    <option
                                        value="Graduate"
                                        <?= $year_level === 'Graduate'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Graduate

                                    </option>


                                </select>

                            </div>



                            <!-- STATUS -->

                            <div class="form-group">

                                <label for="status">

                                    Status

                                </label>


                                <select
                                    id="status"
                                    name="status"
                                >


                                    <option
                                        value="Active"
                                        <?= $status === 'Active'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Active

                                    </option>


                                    <option
                                        value="Inactive"
                                        <?= $status === 'Inactive'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Inactive

                                    </option>


                                </select>

                            </div>


                        </div>


                    </div>



                    <!-- =========================================
                         STATUS NOTICE
                    ========================================== -->

                    <?php

                    $borrowerStatus =
                        strtolower(
                            trim($status)
                        );

                    ?>


                    <?php if (
                        $borrowerStatus === 'inactive'
                    ): ?>


                        <div class="status-notice">


                            <span class="notice-icon">
                                !
                            </span>


                            <div>

                                <strong>
                                    Inactive Borrower
                                </strong>


                                <p>
                                    This borrower should not be allowed
                                    to borrow new books while inactive.
                                </p>

                            </div>


                        </div>


                    <?php endif; ?>



                    <!-- =========================================
                         REQUIRED NOTE
                    ========================================== -->

                    <div class="required-note">

                        <span class="required">
                            *
                        </span>

                        Required fields

                    </div>



                    <!-- =========================================
                         ACTIONS
                    ========================================== -->

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
                            id="saveButton"
                        >

                            ✓ Save Changes

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="../assets/js/borrowers.js"></script>


</body>

</html>