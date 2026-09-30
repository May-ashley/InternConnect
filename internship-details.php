<?php

require_once "db.php";

$conn = connect();


// ==================================================
// Get Internship ID
// ==================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: internships.php");
    exit;

}

$internship_id = (int) $_GET['id'];

// ==================================================
// Get Internship Details
// ==================================================

$sql = "SELECT
            internships.*,
            companies.company_name,
            companies.description AS company_description,
            companies.industry,
            companies.location AS company_location,
            companies.website,
            companies.logo

        FROM internships

        INNER JOIN companies
            ON internships.company_id = companies.id

        WHERE internships.id = ?
        AND internships.status = 'active'

        LIMIT 1";


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $internship_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$internship = mysqli_fetch_assoc($result);


// ==================================================
// Internship Not Found
// ==================================================

if (!$internship) {

    header("Location: internship.php");
    exit;

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title -->
    <title>
     <?php echo htmlspecialchars($internship['title']); ?>
    </title>
    <!-- External CSS-->
    <link rel="stylesheet" href="style.css">
     <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"
        rel="stylesheet">

    <!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>


<body>
     
   
<!-- ================= Background ================= -->

    <div class="bg-circle one"></div>
    <div class="bg-circle two"></div>

    <!-- ================= Navbar ================= -->



    <header class="menu-toggle">
        <nav class="navbar">

            <div class="logo">
                <h2>Intern<span>Connect</span></h2>
            </div>

            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="internships.php" class="active">Internships</a></li>
                <li><a href="companies.php">Companies</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>

            </ul>

            <div class="nav-buttons">
                <a href="login.php" class="login-btn">Login</a>
                <a href="register.php" class="register-btn">Sign Up</a>
            </div>

        </nav>

    </header>

<!-- ==================================================
     Internship Details
================================================== -->

<section class="internship-details-section">

  <div class="internship-details-container">


    <!-- Back -->

    <a
        href="internships.php"
        class="back-to-internships"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Internships

    </a>



    <!-- Main Header -->

    <div class="internship-details-header">


        <div class="internship-details-company-logo">

            <?php if (!empty($internship['logo'])) { ?>

                <img
                    src="<?php echo htmlspecialchars(
                        $internship['logo']
                    ); ?>"
                    alt="Company Logo"
                >

            <?php } else { ?>

                <i class="fa-solid fa-building"></i>

            <?php } ?>

        </div>



        <div class="internship-details-title">

            <span class="section-badge">

                <i class="fa-solid fa-briefcase"></i>

                Internship Opportunity

            </span>


            <h1>

                <?php echo htmlspecialchars(
                    $internship['title']
                ); ?>

            </h1>


            <p>

                <i class="fa-solid fa-building"></i>

                <?php echo htmlspecialchars(
                    $internship['company_name']
                ); ?>

            </p>

        </div>

    </div>



    <!-- Internship Information -->

    <div class="internship-details-layout">


        <!-- Main Content -->

        <div class="internship-details-main">


            <!-- Overview -->

            <div class="details-card">

                <h2>
                    Internship Overview
                </h2>


                <div class="details-info-grid">


                    <div class="details-info-item">

                        <i class="fa-solid fa-location-dot"></i>

                        <div>

                            <span>
                                Location
                            </span>

                            <strong>
                                <?php echo htmlspecialchars(
                                    $internship['location']
                                ); ?>
                            </strong>

                        </div>

                    </div>



                    <div class="details-info-item">

                        <i class="fa-solid fa-briefcase"></i>

                        <div>

                            <span>
                                Internship Type
                            </span>

                            <strong>
                                <?php echo htmlspecialchars(
                                    $internship['type']
                                ); ?>
                            </strong>

                        </div>

                    </div>



                    <div class="details-info-item">

                        <i class="fa-solid fa-layer-group"></i>

                        <div>

                            <span>
                                Category
                            </span>

                            <strong>
                                <?php echo htmlspecialchars(
                                    $internship['category']
                                ); ?>
                            </strong>

                        </div>

                    </div>



                    <div class="details-info-item">

                        <i class="fa-solid fa-calendar"></i>

                        <div>

                            <span>
                                Application Deadline
                            </span>

                            <strong>

                                <?php

                                if (
                                    !empty(
                                        $internship[
                                            'application_deadline'
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "M d, Y",
                                        strtotime(
                                            $internship[
                                                'application_deadline'
                                            ]
                                        )
                                    );

                                } else {

                                    echo "Not specified";

                                }

                                ?>

                            </strong>

                        </div>

                    </div>


                </div>

            </div>



            <!-- Description -->

            <div class="details-card">

                <h2>
                    About This Internship
                </h2>

                <p class="internship-description">

                    <?php echo nl2br(
                        htmlspecialchars(
                            $internship['description']
                        )
                    ); ?>

                </p>

            </div>



            <!-- Skills -->

            <div class="details-card">

                <h2>
                    Required Skills
                </h2>


                <div class="details-skills">

                    <?php

                    $skills = explode(
                        ',',
                        $internship['skills']
                    );

                    foreach ($skills as $skill) {

                        $skill = trim($skill);

                        if ($skill !== '') {

                    ?>

                        <span>
                            <?php echo htmlspecialchars(
                                $skill
                            ); ?>
                        </span>

                    <?php

                        }

                    }

                    ?>

                </div>

            </div>


        </div>



        <!-- Sidebar -->

        <aside class="internship-details-sidebar">


            <!-- Apply -->

            <div class="apply-card">

                <h2>
                    Interested?
                </h2>

                <p>
                    Take the next step toward
                    your career.
                </p>


                <a
                    href="login.php"
                    class="apply-now-btn"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Apply Now

                </a>


            </div>



            <!-- Company -->

            <div class="details-card company-details-card">

                <h2>
                    About the Company
                </h2>


                <div class="company-details-logo">

                    <?php if (!empty($internship['logo'])) { ?>

                        <img
                            src="<?php echo htmlspecialchars(
                                $internship['logo']
                            ); ?>"
                            alt="Company Logo"
                        >

                    <?php } else { ?>

                        <i class="fa-solid fa-building"></i>

                    <?php } ?>

                </div>


                <h3>

                    <?php echo htmlspecialchars(
                        $internship['company_name']
                    ); ?>

                </h3>


                <?php if (
                    !empty($internship['industry'])
                ) { ?>

                    <p>

                        <i class="fa-solid fa-industry"></i>

                        <?php echo htmlspecialchars(
                            $internship['industry']
                        ); ?>

                    </p>

                <?php } ?>


                <?php if (
                    !empty($internship['company_location'])
                ) { ?>

                    <p>

                        <i class="fa-solid fa-location-dot"></i>

                        <?php echo htmlspecialchars(
                            $internship['company_location']
                        ); ?>

                    </p>

                <?php } ?>


                <?php if (
                    !empty($internship['website'])
                ) { ?>

                    <a
                        href="<?php echo htmlspecialchars(
                            $internship['website']
                        ); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        <i class="fa-solid fa-globe"></i>

                        Company Website

                    </a>

                <?php } ?>

            </div>


        </aside>


    </div>

</div>

</section>

<?php include("footer.php"); ?>

</body>

</html>
