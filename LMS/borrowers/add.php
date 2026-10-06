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

$pageTitle   = 'Add Borrower';
$currentPage = 'borrowers';

$error = '';


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$student_id = '';
$full_name  = '';
$email      = '';
$contact    = '';
$course     = '';
$year_level = '';
$status     = 'Active';


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
            ['Active', 'Inactive'],
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

            LIMIT 1

        ");

        $stmt->execute([
            $student_id
        ]);


        if ($stmt->fetch()) {

            $error =
                'A borrower with this Student ID already exists.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT BORROWER
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
            | INSERT BORROWER
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                INSERT INTO borrowers

                (
                    student_id,
                    full_name,
                    email,
                    contact,
                    course,
                    year_level,
                    status
                )

                VALUES

                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )

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

                $status

            ]);


            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $description =
                'Added new borrower: '
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
                    'CREATE',
                    ?
                )

            ");


            $logStmt->execute([

                $_SESSION['user_id'],

                $description

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
                "Location: index.php?added=1"
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


            /*
            |--------------------------------------------------------------------------
            | ERROR
            |--------------------------------------------------------------------------
            */

            $error =
                'Unable to add the borrower. Database error: '
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


    <!-- =====================================================
         GLOBAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- =====================================================
         BOOKS / SHARED PAGE CSS
         This gives Borrowers the same visual system as Books.
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/books.css"
    >


    <!-- =====================================================
         BORROWER FORM CSS
    ====================================================== -->

    <style>

        /* =====================================================
           BORROWER PAGE
        ===================================================== */

        .borrowers-page {

            width: 100%;

        }


        /* =====================================================
           PAGE HEADING
        ===================================================== */

        .borrowers-page .page-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;

        }


        .borrowers-page .page-heading h2 {

            color: #001F54;

            font-size: 24px;

            margin-bottom: 5px;

        }


        .borrowers-page .page-heading p {

            color: #6C757D;

            font-size: 13px;

        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .borrowers-page .back-button {

            display: inline-flex;

            align-items: center;

            padding: 10px 15px;

            background: #ffffff;

            border: 1px solid #dee2e6;

            border-radius: 6px;

            color: #001F54;

            font-size: 12px;

            font-weight: bold;

            text-decoration: none;

        }


        .borrowers-page .back-button:hover {

            background: #f4f6f8;

        }


        /* =====================================================
           ERROR
        ===================================================== */

        .borrowers-page .alert {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 13px 15px;

            margin-bottom: 20px;

            border-radius: 7px;

        }


        .borrowers-page .alert-error {

            background: #f8d7da;

            border-left: 4px solid #dc3545;

            color: #842029;

        }


        .borrowers-page .alert-icon {

            width: 28px;

            height: 28px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            background: #dc3545;

            color: #ffffff;

            border-radius: 50%;

            font-size: 12px;

            font-weight: bold;

        }


        .borrowers-page .alert strong {

            display: block;

            font-size: 12px;

            margin-bottom: 3px;

        }


        .borrowers-page .alert p {

            font-size: 11px;

        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .borrowers-page .form-card {

            background: #ffffff;

            border: 1px solid #e1e5e9;

            border-radius: 8px;

            overflow: hidden;

        }


        /* =====================================================
           FORM CARD HEADER
        ===================================================== */

        .borrowers-page .form-card-header {

            display: flex;

            align-items: center;

            gap: 15px;

            padding: 22px 25px;

            background: #001F54;

            color: #ffffff;

        }


        .borrowers-page .header-icon {

            width: 48px;

            height: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: rgba(255,255,255,0.12);

            border-radius: 8px;

            font-size: 22px;

            flex-shrink: 0;

        }


        .borrowers-page .form-card-header h3 {

            font-size: 17px;

            margin-bottom: 4px;

        }


        .borrowers-page .form-card-header p {

            font-size: 11px;

            opacity: 0.8;

        }


        /* =====================================================
           FORM SECTION
        ===================================================== */

        .borrowers-page .form-section {

            padding: 25px;

            border-bottom: 1px solid #e9ecef;

        }


        .borrowers-page .form-section h4 {

            color: #001F54;

            font-size: 14px;

            margin-bottom: 20px;

        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .borrowers-page .form-grid {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 20px;

        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

        .borrowers-page .form-group {

            min-width: 0;

        }


        .borrowers-page .form-group-full {

            grid-column: 1 / -1;

        }


        .borrowers-page .form-group label {

            display: block;

            color: #001F54;

            font-size: 12px;

            font-weight: bold;

            margin-bottom: 7px;

        }


        .borrowers-page .required {

            color: #dc3545;

        }


        .borrowers-page .form-group input,
        .borrowers-page .form-group select,
        .borrowers-page .form-group textarea {

            width: 100%;

            border: 1px solid #ced4da;

            border-radius: 6px;

            padding: 10px 12px;

            outline: none;

            font-family: inherit;

            font-size: 12px;

            background: #ffffff;

        }


        .borrowers-page .form-group input,
        .borrowers-page .form-group select {

            height: 43px;

        }


        .borrowers-page .form-group textarea {

            resize: vertical;

            min-height: 80px;

        }


        .borrowers-page .form-group input:focus,
        .borrowers-page .form-group select:focus,
        .borrowers-page .form-group textarea:focus {

            border-color: #001F54;

            box-shadow:
                0 0 0 2px
                rgba(0,31,84,0.08);

        }


        .borrowers-page .form-group small {

            display: block;

            color: #6C757D;

            font-size: 10px;

            margin-top: 5px;

        }


        /* =====================================================
           REQUIRED NOTE
        ===================================================== */

        .borrowers-page .required-note {

            padding: 18px 25px 0;

            color: #6C757D;

            font-size: 10px;

        }


        /* =====================================================
           FORM ACTIONS
        ===================================================== */

        .borrowers-page .form-actions {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 10px;

            padding: 20px 25px;

        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .borrowers-page .btn {

            min-height: 42px;

            padding: 0 20px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: none;

            border-radius: 6px;

            font-size: 12px;

            font-weight: bold;

            cursor: pointer;

            text-decoration: none;

        }


        .borrowers-page .btn-primary {

            background: #001F54;

            color: #ffffff;

        }


        .borrowers-page .btn-primary:hover {

            background: #00337F;

        }


        .borrowers-page .btn-secondary {

            background: #6C757D;

            color: #ffffff;

        }


        .borrowers-page .btn-secondary:hover {

            background: #545b62;

        }


        .borrowers-page .btn:disabled {

            opacity: 0.6;

            cursor: not-allowed;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 700px) {

            .borrowers-page .page-heading {

                flex-direction: column;

                align-items: flex-start;

            }


            .borrowers-page .form-grid {

                grid-template-columns: 1fr;

            }


            .borrowers-page .form-group-full {

                grid-column: auto;

            }


            .borrowers-page .form-section {

                padding: 20px;

            }


            .borrowers-page .form-actions {

                flex-direction: column-reverse;

                padding: 20px;

            }


            .borrowers-page .form-actions .btn {

                width: 100%;

            }

        }

    </style>

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


        <div class="borrowers-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-heading">


                <div>

                    <h2>
                        Add New Borrower
                    </h2>

                    <p>
                        Register a new borrower in the library system.
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
                            Unable to add borrower
                        </strong>


                        <p>

                            <?= htmlspecialchars($error) ?>

                        </p>

                    </div>


                </div>

            <?php endif; ?>



            <!-- =================================================
                 FORM CARD
            ================================================== -->

            <div class="form-card">


                <!-- =================================================
                     FORM HEADER
                ================================================== -->

                <div class="form-card-header">


                    <div class="header-icon">

                        +

                    </div>


                    <div>

                        <h3>
                            Borrower Information
                        </h3>

                        <p>
                            Enter the borrower's information below.
                        </p>

                    </div>


                </div>



                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    method="POST"
                    action=""
                    id="borrowerForm"
                >


                    <!-- =================================================
                         PERSONAL INFORMATION
                    ================================================== -->

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
                                    placeholder="Example: 2026-0001"
                                    required
                                >


                                <small>
                                    Unique student ID assigned to the borrower.
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
                                    maxlength="50"
                                    placeholder="09XXXXXXXXX"
                                >

                            </div>


                        </div>


                    </div>



                    <!-- =================================================
                         SCHOOL INFORMATION
                    ================================================== -->

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
                                    placeholder="Example: BS Information Systems"
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
                         ACTIONS
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
                            id="saveButton"
                        >

                            + Add Borrower

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

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const form =
            document.getElementById(
                'borrowerForm'
            );


        const saveButton =
            document.getElementById(
                'saveButton'
            );


        if (!form) {

            return;

        }


        form.addEventListener(
            'submit',
            function (event) {


                const studentId =
                    document.getElementById(
                        'student_id'
                    ).value.trim();


                const fullName =
                    document.getElementById(
                        'full_name'
                    ).value.trim();


                const course =
                    document.getElementById(
                        'course'
                    ).value.trim();


                const yearLevel =
                    document.getElementById(
                        'year_level'
                    ).value;


                if (
                    studentId === ''
                    ||
                    fullName === ''
                    ||
                    course === ''
                    ||
                    yearLevel === ''
                ) {

                    event.preventDefault();

                    alert(
                        'Please complete all required fields.'
                    );

                    return;

                }


                const confirmed =
                    confirm(
                        'Add this borrower to the library system?'
                        + '\n\n'
                        + 'Student ID: '
                        + studentId
                        + '\n'
                        + 'Name: '
                        + fullName
                    );


                if (!confirmed) {

                    event.preventDefault();

                    return;

                }


                saveButton.disabled = true;

                saveButton.textContent =
                    'Saving...';

            }
        );

    }
);

</script>


</body>

</html>