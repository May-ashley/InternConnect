<?php

session_start();

require_once "db.php";

/* =========================================================
   COMPANY ACCESS PROTECTION
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'company'
) {
    header("Location: login.php");
    exit();
}


/* =========================================================
   DATABASE
========================================================= */

$conn = connect();

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET INTERNSHIP ID
========================================================= */

$internship_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($internship_id <= 0) {
    header("Location: company-internships.php");
    exit();
}


/* =========================================================
   GET COMPANY
========================================================= */

$company_id = 0;
$company_name = $_SESSION['user_name'] ?? 'Company';

$sql = "
    SELECT
        id,
        company_name
    FROM companies
    WHERE user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $company_id = (int) $row['id'];

        if (!empty($row['company_name'])) {
            $company_name = $row['company_name'];
        }
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   COMPANY NOT FOUND
========================================================= */

if ($company_id <= 0) {

    header("Location: company-internships.php");
    exit();

}


/* =========================================================
   SUCCESS / ERROR MESSAGES
========================================================= */

$success_message = '';
$error_message = '';


/* =========================================================
   DELETE INTERNSHIP
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'delete'
) {

    /*
       IMPORTANT:
       Delete only if the internship belongs
       to the logged-in company.
    */

    $delete_sql = "
        DELETE FROM internships
        WHERE id = ?
        AND company_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $delete_sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $internship_id,
            $company_id
        );

        if (mysqli_stmt_execute($stmt)) {

            if (mysqli_stmt_affected_rows($stmt) > 0) {

                mysqli_stmt_close($stmt);

                header(
                    "Location: company-internships.php?deleted=1"
                );

                exit();

            } else {

                $error_message =
                    "The internship could not be found or you do not have permission to delete it.";
            }

        } else {

            $error_message =
                "Something went wrong while deleting the internship.";
        }

        mysqli_stmt_close($stmt);

    } else {

        $error_message =
            "Unable to prepare the delete request.";
    }
}


/* =========================================================
   GET CURRENT INTERNSHIP
========================================================= */

$internship = null;

$sql = "
    SELECT
        id,
        company_id,
        title,
        category,
        location,
        type,
        description,
        skills,
        application_deadline,
        status,
        created_at
    FROM internships
    WHERE id = ?
    AND company_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $internship_id,
        $company_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $internship = $row;
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   INTERNSHIP NOT FOUND
========================================================= */

if (!$internship) {

    header("Location: company-internships.php");
    exit();

}


/* =========================================================
   UPDATE INTERNSHIP
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'update'
) {

    /* ---------------------------------------------
       GET FORM VALUES
    --------------------------------------------- */

    $title = trim($_POST['title'] ?? '');

    $category = trim($_POST['category'] ?? '');

    $location = trim($_POST['location'] ?? '');

    $type = trim($_POST['type'] ?? '');

    $description = trim($_POST['description'] ?? '');

    $skills = trim($_POST['skills'] ?? '');

    $application_deadline =
        trim($_POST['application_deadline'] ?? '');


    /* ---------------------------------------------
       VALIDATION
    --------------------------------------------- */

    if ($title === '') {

        $error_message =
            "Please enter an internship title.";

    } elseif ($category === '') {

        $error_message =
            "Please select an internship category.";

    } elseif ($location === '') {

        $error_message =
            "Please enter the internship location.";

    } elseif ($type === '') {

        $error_message =
            "Please select the internship type.";

    } elseif ($description === '') {

        $error_message =
            "Please enter an internship description.";

    } elseif ($skills === '') {

        $error_message =
            "Please enter at least one required skill.";

    }


    /* ---------------------------------------------
       UPDATE DATABASE
    --------------------------------------------- */

    if ($error_message === '') {

        /*
           Keep the current status.

           This means a company editing an internship
           will NOT accidentally change pending/active
           status.
        */

        $update_sql = "
            UPDATE internships
            SET
                title = ?,
                category = ?,
                location = ?,
                type = ?,
                description = ?,
                skills = ?,
                application_deadline = NULLIF(?, '')
            WHERE id = ?
            AND company_id = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $update_sql
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssssssii",
                $title,
                $category,
                $location,
                $type,
                $description,
                $skills,
                $application_deadline,
                $internship_id,
                $company_id
            );


            if (mysqli_stmt_execute($stmt)) {

                /*
                   Refresh current internship values.
                */

                $internship['title'] =
                    $title;

                $internship['category'] =
                    $category;

                $internship['location'] =
                    $location;

                $internship['type'] =
                    $type;

                $internship['description'] =
                    $description;

                $internship['skills'] =
                    $skills;

                $internship['application_deadline'] =
                    $application_deadline !== ''
                    ? $application_deadline
                    : null;


                $success_message =
                    "Internship updated successfully.";

            } else {

                $error_message =
                    "Something went wrong while updating the internship.";
            }

            mysqli_stmt_close($stmt);

        } else {

            $error_message =
                "Unable to prepare the update request.";
        }
    }
}


/* =========================================================
   STATUS
========================================================= */

$current_status =
    strtolower(
        $internship['status'] ?? 'pending'
    );


/* =========================================================
   FORMAT DEADLINE FOR INPUT
========================================================= */

$deadline_value = '';

if (
    !empty(
        $internship['application_deadline']
    )
) {

    $deadline_value =
        date(
            'Y-m-d',
            strtotime(
                $internship['application_deadline']
            )
        );
}


/* =========================================================
   COMPANY INITIAL
========================================================= */

$company_initial =
    strtoupper(
        substr(
            $company_name,
            0,
            1
        )
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
        Edit Internship | InternConnect
    </title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- Company Dashboard -->

    <link
        rel="stylesheet"
        href="company-dashboard.css"
    >


    <!-- ONLY THIS PAGE -->

    <link
        rel="stylesheet"
        href="company-internship-edit.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

            <p>
                Company Portal
            </p>

        </div>


        <nav class="sidebar-nav">


            <a href="company-dashboard.php">

                <i class="fa-solid fa-chart-line"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="company-internships.php"
                class="active"
            >

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    My Internships
                </span>

            </a>


            <a href="company-applications.php">

                <i class="fa-regular fa-file-lines"></i>

                <span>
                    Applications
                </span>

            </a>


            <a href="company-accepted.php">

                <i class="fa-solid fa-user-check"></i>

                <span>
                    Accepted Interns
                </span>

            </a>


            <a href="company-profile.php">

                <i class="fa-regular fa-building"></i>

                <span>
                    Company Profile
                </span>

            </a>


        </nav>


        <div class="sidebar-bottom">


            <a
                href="index.php"
                class="back-to-site"
            >

                <i class="fa-solid fa-arrow-left"></i>

                <span>
                    Back to Site
                </span>

            </a>


            <a
                href="logout.php"
                class="logout"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>
                    Logout
                </span>

            </a>


        </div>


    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="dashboard-main edit-main">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <header class="edit-page-header">


            <div class="header-left">


                <a
                    href="company-internships.php"
                    class="back-link"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to My Internships

                </a>


                <p class="edit-label">
                    Recruitment
                </p>


                <h1>
                    Edit Internship
                </h1>


                <p class="edit-subtitle">
                    Update your internship opportunity details
                    and keep your listing accurate.
                </p>


            </div>


            <div class="header-status">


                <span class="status-title">
                    Current Status
                </span>


                <span
                    class="edit-status status-<?php
                    echo htmlspecialchars(
                        $current_status
                    );
                    ?>"
                >

                    <i
                        class="<?php
                        if ($current_status === 'active') {
                            echo 'fa-solid fa-circle-check';
                        } elseif ($current_status === 'pending') {
                            echo 'fa-regular fa-clock';
                        } else {
                            echo 'fa-solid fa-circle-xmark';
                        }
                        ?>"
                    ></i>

                    <?php
                    echo ucfirst(
                        htmlspecialchars(
                            $current_status
                        )
                    );
                    ?>

                </span>


            </div>


        </header>


        <!-- =================================================
             ALERTS
        ================================================== -->

        <?php if ($success_message !== ''): ?>

            <div class="edit-alert success-alert">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $success_message
                    );
                    ?>
                </span>

                <button
                    type="button"
                    class="alert-close"
                    onclick="this.parentElement.remove();"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

        <?php endif; ?>


        <?php if ($error_message !== ''): ?>

            <div class="edit-alert error-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $error_message
                    );
                    ?>
                </span>

                <button
                    type="button"
                    class="alert-close"
                    onclick="this.parentElement.remove();"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

        <?php endif; ?>


        <!-- =================================================
             EDIT FORM
        ================================================== -->

        <form
            method="POST"
            class="edit-form"
            id="editInternshipForm"
        >

            <input
                type="hidden"
                name="action"
                value="update"
            >


            <!-- =============================================
                 BASIC INFORMATION
            ============================================== -->

            <section class="edit-card">


                <div class="card-heading">

                    <div class="card-heading-icon">

                        <i class="fa-solid fa-briefcase"></i>

                    </div>


                    <div>

                        <h2>
                            Basic Information
                        </h2>

                        <p>
                            Update the main information about
                            this internship opportunity.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- TITLE -->

                    <div class="form-group full-width">

                        <label for="title">

                            Internship Title

                            <span>*</span>

                        </label>


                        <div class="input-wrapper">

                            <i class="fa-solid fa-heading"></i>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?php
                                echo htmlspecialchars(
                                    $internship['title']
                                );
                                ?>"
                                placeholder="e.g. Web Developer Intern"
                                maxlength="150"
                                required
                            >

                        </div>

                    </div>


                   <!-- CATEGORY -->

                <div class="form-group">

                    <label for="category">

                        Category

                        <span>*</span>

                    </label>

                    <div class="select-wrapper">

                        <i class="fa-solid fa-layer-group"></i>

                        <select
                            id="category"
                            name="category"
                            required
                        >

                            <option
                                value=""
                                disabled
                                <?= empty($internship['category']) ? 'selected' : '' ?>
                            >
                                Select category
                            </option>

                            <?php

                            $categories = [
                                'Software Development',
                                'Web Development',
                                'Design',
                                'Data Science',
                                'Artificial Intelligence',
                                'Cybersecurity',
                                'Cloud Computing',
                                'Mobile Development',
                                'Engineering',
                                'Marketing',
                                'Business',
                                'Other'
                            ];

                            foreach ($categories as $item):

                            ?>

                                <option
                                    value="<?= htmlspecialchars($item) ?>"
                                    <?= $internship['category'] === $item ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($item) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                    <!-- LOCATION -->

                    <div class="form-group">

                        <label for="location">

                            Location

                            <span>*</span>

                        </label>


                        <div class="input-wrapper">

                            <i class="fa-solid fa-location-dot"></i>

                            <input
                                type="text"
                                id="location"
                                name="location"
                                value="<?php
                                echo htmlspecialchars(
                                    $internship['location']
                                );
                                ?>"
                                placeholder="e.g. Yangon / Remote"
                                maxlength="150"
                                required
                            >

                        </div>

                    </div>


                    <!-- TYPE -->

                    <div class="form-group">

                        <label for="type">

                            Internship Type

                            <span>*</span>

                        </label>


                        <div class="select-wrapper">

                            <i class="fa-solid fa-clock"></i>

                            <select
                                id="type"
                                name="type"
                                required
                            >

                                <option value="">
                                    Select type
                                </option>

                                <option
                                    value="Full-time"
                                    <?php
                                    echo $internship['type'] === 'Full-time'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Full-time
                                </option>

                                <option
                                    value="Part-time"
                                    <?php
                                    echo $internship['type'] === 'Part-time'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Part-time
                                </option>

                                <option
                                    value="Remote"
                                    <?php
                                    echo $internship['type'] === 'Remote'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Remote
                                </option>

                                <option
                                    value="Hybrid"
                                    <?php
                                    echo $internship['type'] === 'Hybrid'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Hybrid
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- DEADLINE -->

                    <div class="form-group">

                        <label for="application_deadline">

                            Application Deadline

                        </label>


                        <div class="input-wrapper">

                            <i class="fa-regular fa-calendar"></i>

                            <input
                                type="date"
                                id="application_deadline"
                                name="application_deadline"
                                value="<?php
                                echo htmlspecialchars(
                                    $deadline_value
                                );
                                ?>"
                            >

                        </div>


                        <small>
                            Leave empty if there is no deadline.
                        </small>

                    </div>


                </div>


            </section>


            <!-- =============================================
                 DESCRIPTION
            ============================================== -->

            <section class="edit-card">


                <div class="card-heading">

                    <div class="card-heading-icon">

                        <i class="fa-solid fa-align-left"></i>

                    </div>


                    <div>

                        <h2>
                            Internship Description
                        </h2>

                        <p>
                            Explain the role, responsibilities,
                            and what students can expect.
                        </p>

                    </div>

                </div>


                <div class="form-group">

                    <label for="description">

                        Description

                        <span>*</span>

                    </label>


                    <textarea
                        id="description"
                        name="description"
                        rows="7"
                        maxlength="3000"
                        placeholder="Describe the internship role, responsibilities, learning opportunities, and expectations..."
                        required
                    ><?php
                    echo htmlspecialchars(
                        $internship['description']
                    );
                    ?></textarea>


                    <div class="character-count">

                        <span id="descriptionCount">
                            0
                        </span>

                        / 3000 characters

                    </div>

                </div>


            </section>


            <!-- =============================================
                 SKILLS
            ============================================== -->

            <section class="edit-card">


                <div class="card-heading">

                    <div class="card-heading-icon">

                        <i class="fa-solid fa-code"></i>

                    </div>


                    <div>

                        <h2>
                            Required Skills
                        </h2>

                        <p>
                            Add the skills students should have
                            for this internship.
                        </p>

                    </div>

                </div>


                <div class="form-group">

                    <label for="skills">

                        Skills

                        <span>*</span>

                    </label>


                    <div class="input-wrapper skills-input">

                        <i class="fa-solid fa-wand-magic-sparkles"></i>

                        <input
                            type="text"
                            id="skills"
                            name="skills"
                            value="<?php
                            echo htmlspecialchars(
                                $internship['skills']
                            );
                            ?>"
                            placeholder="PHP, MySQL, HTML, CSS, JavaScript"
                            maxlength="1000"
                            required
                        >

                    </div>


                    <small>
                        Separate multiple skills with commas.
                    </small>


                    <!-- SKILL PREVIEW -->

                    <div
                        class="skill-preview"
                        id="skillPreview"
                    ></div>


                </div>


            </section>


            <!-- =============================================
                 ACTIONS
            ============================================== -->

            <div class="form-actions">


                <a
                    href="company-internships.php"
                    class="cancel-button"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Cancel

                </a>


                <button
                    type="submit"
                    class="update-button"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Changes

                </button>


            </div>


        </form>


        <!-- =================================================
             DANGER ZONE
        ================================================== -->

        <section class="danger-zone">


            <div class="danger-content">


                <div class="danger-icon">

                    <i class="fa-solid fa-trash-can"></i>

                </div>


                <div>

                    <h2>
                        Delete Internship
                    </h2>

                    <p>
                        Permanently remove this internship
                        opportunity. This action cannot be undone.
                    </p>

                </div>


            </div>


            <form
                method="POST"
                id="deleteInternshipForm"
            >

                <input
                    type="hidden"
                    name="action"
                    value="delete"
                >


                <button
                    type="button"
                    class="delete-button"
                    id="deleteButton"
                >

                    <i class="fa-solid fa-trash-can"></i>

                    Delete Internship

                </button>

            </form>


        </section>


    </main>


</div>


<!-- =====================================================
     DELETE CONFIRMATION MODAL
====================================================== -->

<div
    class="delete-modal"
    id="deleteModal"
    aria-hidden="true"
>


    <div class="delete-modal-overlay"></div>


    <div class="delete-modal-box">


        <div class="delete-modal-icon">

            <i class="fa-solid fa-triangle-exclamation"></i>

        </div>


        <h2>
            Delete this internship?
        </h2>


        <p>

            Are you sure you want to permanently delete

            <strong>
                <?php
                echo htmlspecialchars(
                    $internship['title']
                );
                ?>
            </strong>

            ?

            <br>

            This action cannot be undone.

        </p>


        <div class="delete-modal-actions">


            <button
                type="button"
                class="modal-cancel"
                id="modalCancel"
            >

                Cancel

            </button>


            <button
                type="button"
                class="modal-delete"
                id="modalDelete"
            >

                <i class="fa-solid fa-trash-can"></i>

                Yes, Delete

            </button>


        </div>


    </div>


</div>


<script>

/* =========================================================
   DESCRIPTION CHARACTER COUNT
========================================================= */

const description =
    document.getElementById('description');

const descriptionCount =
    document.getElementById('descriptionCount');


function updateDescriptionCount() {

    if (!description || !descriptionCount) {
        return;
    }

    descriptionCount.textContent =
        description.value.length;
}


if (description) {

    description.addEventListener(
        'input',
        updateDescriptionCount
    );

    updateDescriptionCount();
}


/* =========================================================
   SKILL PREVIEW
========================================================= */

const skillsInput =
    document.getElementById('skills');

const skillPreview =
    document.getElementById('skillPreview');


function updateSkillPreview() {

    if (!skillsInput || !skillPreview) {
        return;
    }

    const value =
        skillsInput.value.trim();

    skillPreview.innerHTML = '';

    if (value === '') {
        return;
    }

    const skills =
        value.split(',');

    skills.forEach(function(skill) {

        skill = skill.trim();

        if (skill === '') {
            return;
        }

        const tag =
            document.createElement('span');

        tag.className =
            'skill-preview-tag';

        tag.textContent =
            skill;

        skillPreview.appendChild(tag);

    });
}


if (skillsInput) {

    skillsInput.addEventListener(
        'input',
        updateSkillPreview
    );

    updateSkillPreview();
}


/* =========================================================
   UPDATE FORM CONFIRMATION
========================================================= */

const editForm =
    document.getElementById(
        'editInternshipForm'
    );


if (editForm) {

    editForm.addEventListener(
        'submit',
        function(event) {

            const confirmed =
                confirm(
                    'Save these changes to this internship?'
                );

            if (!confirmed) {

                event.preventDefault();

            }

        }
    );

}


/* =========================================================
   DELETE MODAL
========================================================= */

const deleteButton =
    document.getElementById(
        'deleteButton'
    );

const deleteModal =
    document.getElementById(
        'deleteModal'
    );

const modalCancel =
    document.getElementById(
        'modalCancel'
    );

const modalDelete =
    document.getElementById(
        'modalDelete'
    );

const deleteForm =
    document.getElementById(
        'deleteInternshipForm'
    );


function openDeleteModal() {

    deleteModal.classList.add(
        'show'
    );

    deleteModal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );

}


function closeDeleteModal() {

    deleteModal.classList.remove(
        'show'
    );

    deleteModal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'modal-open'
    );

}


if (deleteButton) {

    deleteButton.addEventListener(
        'click',
        openDeleteModal
    );

}


if (modalCancel) {

    modalCancel.addEventListener(
        'click',
        closeDeleteModal
    );

}


if (modalDelete) {

    modalDelete.addEventListener(
        'click',
        function() {

            if (deleteForm) {

                deleteForm.submit();

            }

        }
    );

}


/* =========================================================
   CLOSE MODAL BY CLICKING OVERLAY
========================================================= */

const modalOverlay =
    document.querySelector(
        '.delete-modal-overlay'
    );


if (modalOverlay) {

    modalOverlay.addEventListener(
        'click',
        closeDeleteModal
    );

}


/* =========================================================
   ESC KEY
========================================================= */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key === 'Escape' &&
            deleteModal.classList.contains('show')
        ) {

            closeDeleteModal();

        }

    }
);

</script>


</body>
</html>