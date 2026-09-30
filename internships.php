<?php

session_start();

require_once "db.php";

$conn = connect();

$sql = "SELECT 
            internships.*,
            companies.company_name,
            companies.logo
        FROM internships
        INNER JOIN companies
            ON internships.company_id = companies.id
        WHERE internships.status = 'active'
        ORDER BY internships.created_at DESC
        LIMIT 6";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title -->
    <title>Internships | InternConnect</title>
    <!-- External CSS-->
    <link rel="stylesheet" type="text/css" href="style.css">
     <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
         <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"
        rel="stylesheet">

  
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


    <!-- ==================================================
         INTERNSHIP LIST
    ================================================== -->

    <section class="internship-list-section">

        <div class="section-header">

            <span>
                Latest Opportunities
            </span>

            <h2>
                Available Internships
            </h2>

            <p>
                Discover opportunities that match your
                skills, interests and career goals.
            </p>

            

        </div>


      <div class="internship-list-grid" id="internshipGrid">

    <p id="noSearchResults"style="display: none;">
    No internships found. Try changing your search or filters.
    </p>

<?php

    if (mysqli_num_rows($result) > 0) {

        while ($internship = mysqli_fetch_assoc($result)) {

    ?>

        <div
        class="internship-list-card"

    
    data-category="<?php echo htmlspecialchars(
        strtolower($internship['category'])
    ); ?>"

    data-location="<?php echo htmlspecialchars(
        strtolower($internship['location'])
    ); ?>"

    data-type="<?php echo htmlspecialchars(
        strtolower($internship['type'])
    ); ?>"

    data-search="<?php echo htmlspecialchars(
        strtolower(
            $internship['title'] . ' ' .
            $internship['company_name'] . ' ' .
            $internship['category'] . ' ' .
            $internship['location'] . ' ' .
            $internship['type'] . ' ' .
            $internship['skills'] . ' ' .
            $internship['description']
        )
    ); ?>"
 >

        <div class="internship-card-top">

            <div class="internship-company-logo">

                <?php if (!empty($internship['logo'])) { ?>

                    <img
                        src="<?php echo htmlspecialchars($internship['logo']); ?>"
                        alt="Company Logo"
                    >

                <?php } else { ?>

                    <i class="fa-solid fa-building"></i>

                <?php } ?>

            </div>

            <span class="internship-type">

                <?php echo htmlspecialchars($internship['type']); ?>

            </span>

        </div>


        <h3>
            <?php echo htmlspecialchars($internship['title']); ?>
        </h3>


        <p class="internship-company-name">

            <?php echo htmlspecialchars($internship['company_name']); ?>

        </p>


        <div class="internship-info">

            <span>
                <i class="fa-solid fa-location-dot"></i>

                <?php echo htmlspecialchars($internship['location']); ?>

            </span>


            <span>
                <i class="fa-solid fa-briefcase"></i>

                <?php echo htmlspecialchars($internship['category']); ?>

            </span>

        </div>


        <div class="internship-tags">

            <?php

            $skills = explode(',', $internship['skills']);

            foreach ($skills as $skill) {

            ?>

                <span>
                    <?php echo htmlspecialchars(trim($skill)); ?>
                </span>

            <?php } ?>

        </div>


        <div class="internship-card-bottom">

            <span class="posted-date">

                <?php
                echo date(
                    "M d, Y",
                    strtotime($internship['created_at'])
                );
                ?>

            </span>


            <a
                href="internship-details.php?id=<?php echo $internship['id']; ?>"
                class="view-internship-btn"
            >
                View Internship

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </div>

<?php

    }

} else {

?>

    <p>No internships available at the moment.</p>

<?php } ?>

</div>

    </section>

    <div class="view-all-section">
    <a href="login.php" class="view-all-btn">
       View All Internships
            <i class="ri-arrow-right-line"></i>
    </a>

    <p class="view-all-note">
        Sign in or register to view all available internships.
    </p>
</div>
    
    <!-- ==================================================
        Footer
    ================================================== -->

<?php include("footer.php"); ?>


</body>

</html> 