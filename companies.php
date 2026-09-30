<?php

session_start();

include("db.php");

$conn = connect();

$sql = "
    SELECT
        c.id,
        c.company_name,
        c.description,
        c.industry,
        c.location,
        c.website,
        c.logo
    FROM companies c
    ORDER BY c.company_name ASC
";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Companies | InternConnect</title>


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link rel="stylesheet" href="style.css">


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">


    <!-- =====================================================
         REMIX ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"
        rel="stylesheet">

</head>


<body id="ic-companies-page">


<!-- =====================================================
     BACKGROUND
===================================================== -->

<div
    id="ic-companies-bg-one"
    class="bg-circle one"
></div>

<div
    id="ic-companies-bg-two"
    class="bg-circle two"
></div>



<!-- =====================================================
     NAVBAR
===================================================== -->

<header id="ic-companies-header"class="menu-toggle">

    <nav id="ic-companies-navbar" class="navbar">

        <!-- LOGO -->

        <div id="ic-companies-logo" class="logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

        </div>


        <!-- NAVIGATION -->

        <ul id="ic-companies-nav-menu" class="nav-menu">

            <li>
                <a href="index.php">
                    Home
                </a>
            </li>

            <li>
                <a href="internships.php">
                    Internships
                </a>
            </li>

            <li>
                <a href="companies.php" class="active">
                    Companies
                </a>
            </li>

            <li>
                <a href="about.php">
                    About
                </a>
            </li>

            <li>
                <a href="contact.php">
                    Contact
                </a>
            </li>

        </ul>


        <!-- NAV BUTTONS -->
    <div class="nav-buttons">

        <?php if (isset($_SESSION['user_id'])): ?>

            <?php if ($_SESSION['user_role'] === 'student'): ?>

                <a href="student-dashboard.php" class="login-btn">
                    Dashboard
                </a>

            <?php elseif ($_SESSION['user_role'] === 'company'): ?>

                <a href="company-dashboard.php" class="login-btn">
                    Dashboard
                </a>

            <?php elseif ($_SESSION['user_role'] === 'admin'): ?>

                <a href="admin-dashboard.php" class="login-btn">
                    Dashboard
                </a>

            <?php endif; ?>

            <a href="logout.php" class="register-btn">
                Logout
            </a>

        <?php else: ?>

            <a href="login.php" class="login-btn">
                Login
            </a>

            <a href="register.php" class="register-btn">
                Sign Up
            </a>

        <?php endif; ?>

    </div>

    </nav>

</header>



<!-- =====================================================
     COMPANIES HERO
===================================================== -->

<section id="ic-companies-hero" class="companies-hero">


    <div id="ic-companies-hero-content" class="companies-hero-content">


        <!-- BADGE -->

        <span id="ic-companies-badge" class="companies-badge">
            
            <i class="ri-building-line"></i>

            Partner Companies

        </span>


        <!-- TITLE -->

        <h1>

            Trusted
            <span>Companies</span>

        </h1>


        <!-- DESCRIPTION -->

        <p>

            Discover organizations offering internship
            opportunities through InternConnect.

        </p>




    </div>


</section>



<!-- =====================================================
     COMPANIES SECTION
===================================================== -->

<section
    id="ic-companies-section"
    class="companies-section"
>


    <div
        id="ic-companies-grid"
        class="companies-grid"
    >


        <?php if (mysqli_num_rows($result) > 0): ?>


            <?php while ($company = mysqli_fetch_assoc($result)): ?>


                <!-- =================================================
                     COMPANY CARD
                ================================================== -->

                <article
                    id="ic-company-<?php echo $company['id']; ?>"
                    class="company-card"
                    data-company="<?php echo strtolower(htmlspecialchars($company['company_name'])); ?>"
                >


                    <!-- =================================================
                         COMPANY TOP
                    ================================================== -->

                    <div
                        id="ic-company-top-<?php echo $company['id']; ?>"
                        class="company-top"
                    >


                        <!-- COMPANY LOGO -->

                        <div
                            id="ic-company-logo-<?php echo $company['id']; ?>"
                            class="company-logo"
                        >


                            <?php if (!empty($company['logo'])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($company['logo']); ?>"
                                    alt="<?php echo htmlspecialchars($company['company_name']); ?>"
                                >

                            <?php else: ?>

                                <i class="ri-building-4-line"></i>

                            <?php endif; ?>


                        </div>


                        <!-- COMPANY NAME -->

                        <div
                            id="ic-company-name-<?php echo $company['id']; ?>"
                            class="company-name"
                        >


                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $company['company_name']
                                );

                                ?>

                            </h3>


                            <?php if (!empty($company['industry'])): ?>

                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $company['industry']
                                    );

                                    ?>

                                </span>

                            <?php endif; ?>


                        </div>


                    </div>



                    <!-- =================================================
                         DESCRIPTION
                    ================================================== -->

                    <p
                        id="ic-company-description-<?php echo $company['id']; ?>"
                        class="company-description"
                    >


                        <?php if (!empty($company['description'])): ?>


                            <?php

                            echo htmlspecialchars(
                                $company['description']
                            );

                            ?>


                        <?php else: ?>


                            Explore internship opportunities
                            from this company on InternConnect.


                        <?php endif; ?>


                    </p>



                    <!-- =================================================
                         COMPANY INFORMATION
                    ================================================== -->

                    <div
                        id="ic-company-info-<?php echo $company['id']; ?>"
                        class="company-info"
                    >


                        <!-- LOCATION -->

                        <?php if (!empty($company['location'])): ?>

                            <span>

                                <i class="ri-map-pin-line"></i>

                                <?php

                                echo htmlspecialchars(
                                    $company['location']
                                );

                                ?>

                            </span>

                        <?php endif; ?>


                        <!-- WEBSITE -->

                        <?php if (!empty($company['website'])): ?>

                            <a
                                href="<?php echo htmlspecialchars($company['website']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >

                                <i class="ri-global-line"></i>

                                Website

                            </a>

                        <?php endif; ?>


                    </div>



                    <!-- =================================================
                         VIEW INTERNSHIPS
                    ================================================== -->

                    <a
                        id="ic-view-internships-<?php echo $company['id']; ?>"
                        href="internships.php"
                        class="view-internships"
                    >

                        View Internships

                        <i class="ri-arrow-right-line"></i>

                    </a>


                </article>


            <?php endwhile; ?>


        <?php else: ?>


            <!-- =================================================
                 NO COMPANIES
            ================================================== -->

            <div
                id="ic-no-companies"
                class="no-companies"
            >

                <i class="ri-building-line"></i>

                <h3>
                    No Companies Available
                </h3>

                <p>
                    There are currently no companies
                    registered on InternConnect.
                </p>

            </div>


        <?php endif; ?>


    </div>


</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<?php include("footer.php"); ?>



<!-- =====================================================
     COMPANY SEARCH JAVASCRIPT
===================================================== -->

<script>

const companySearchInput =
    document.getElementById("ic-company-search");

const companyCards =
    document.querySelectorAll(
        "#ic-companies-grid .company-card"
    );


companySearchInput.addEventListener(
    "input",
    function () {

        const search =
            this.value
                .toLowerCase()
                .trim();


        companyCards.forEach(
            function (card) {

                const company =
                    card.getAttribute(
                        "data-company"
                    );


                if (company.includes(search)) {

                    card.style.display = "flex";

                } else {

                    card.style.display = "none";

                }

            }
        );

    }
);

</script>


</body>

</html>