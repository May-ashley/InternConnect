<?php
session_start();
include("db.php");

$conn = connect();

$sql = "
    SELECT 
        internships.id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        internships.description,
        internships.skills,
        companies.company_name,
        companies.logo
    FROM internships
    INNER JOIN companies
        ON internships.company_id = companies.id
    WHERE internships.status = 'active'
    ORDER BY internships.created_at DESC
    LIMIT 3
";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title -->
    <title>Home | InternConnect</title>
    <!-- External CSS-->
    <link rel="stylesheet" href="style.css">
   
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
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="internships.php">Internships</a></li>
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

  <!--  ================= Hero ================= -->

    <section class="hero">

        <!-- Left Content -->

        <div class="hero-left">

            <div class="badge">
                <i class="ri-community-line"></i>
                Student &amp; Company Internship Platform
            </div>

            <h1>
                CONNECTING
                <span>TALENT</span>
                WITH OPPORTUNITY.
            </h1>

            <p>
                InternConnect connects talented students with leading companies.
                Students discover internship opportunities, while companies find,
                manage, and hire the right talent through one powerful platform.
            </p>

            <div class="hero-buttons">

                <a href="internships.php" class="primary-btn">
                    <i class="ri-search-line"></i>
                    Find Internships
                </a>

                <a href="register.php" class="secondary-btn">
                    <i class="ri-building-line"></i>
                    Hire Interns
                </a>

            </div>


            <div class="hero-stats">

                <div class="mini-stat">
                    <h3>1200+</h3>
                    <span>Internship Opportunities</span>
                </div>

                <div class="mini-stat">
                    <h3>550+</h3>
                    <span>Partner Companies</span>
                </div>

                <div class="mini-stat">
                    <h3>10K+</h3>
                    <span>Student Profiles</span>
                </div>

            </div>

        </div>

        <!-- Right Content -->

        <div class="hero-right">

            <div class="hero-image">

                <img
                    src="images/hero.png"
                    alt="InternConnect Platform Preview">

                <!-- Student Card -->

                <div class="floating-card card-one">

                    <i class="ri-graduation-cap-line"></i>

                    <div>
                        <h4>Student Profiles</h4>
                        <span>10,000+ Talents</span>
                    </div>

                </div>

                <!-- Company Card -->

                <div class="floating-card card-two">

                    <i class="ri-building-4-line"></i>

                    <div>
                        <h4>Partner Companies</h4>
                        <span>550+ Employers</span>
                    </div>

                </div>

                <!-- Internship Card -->

                <div class="floating-card card-three">

                    <i class="ri-briefcase-line"></i>

                    <div>
                        <h4>Internship Jobs</h4>
                        <span>1200+ Opportunities</span>
                    </div>

                </div>

                <!-- Match Rate -->

                <div class="floating-card card-four">

                    <i class="ri-link-m"></i>

                    <div>
                        <h2>95%</h2>
                        <p>Successful Matches</p>
                    </div>

                </div>

                <!-- Platform Access -->

                <div class="floating-card card-five">

                    <i class="ri-global-line"></i>

                    <div>
                        <h2>24/7</h2>
                        <p>Platform Access</p>
                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- ================= Trusted Network ================= -->

    <section class="companies">

    <div class="section-header">

        <span>Trusted Network</span>

        <h2>Connecting Students with Industry Leaders</h2>

        <p>
            InternConnect partners with leading organizations to provide
            students with valuable internship opportunities while helping
            companies discover skilled and motivated future professionals.
        </p>

    </div>

    <div class="company-slider">

        <div class="company-track">

            <!-- Original Logos -->
            <div class="company-card">
                <img src="images/google.png" alt="Google">
            </div>

            <div class="company-card">
                <img src="images/microsoft.png" alt="Microsoft">
            </div>

            <div class="company-card">
                <img src="images/AWS.png" alt="AWS">
            </div>

            <div class="company-card">
                <img src="images/meta.png" alt="Meta">
            </div>

            <div class="company-card">
                <img src="images/apple.png" alt="Apple">
            </div>

            <div class="company-card">
                <img src="images/Adobe.png" alt="Adobe">
            </div>

             <div class="company-card">
                <img src="images/NVIDIA.png" alt="NVIDIA">
            </div>

            <!-- Duplicate Logos -->
            <div class="company-card">
                <img src="images/google.png" alt="Google">
            </div>

            <div class="company-card">
                <img src="images/microsoft.png" alt="Microsoft">
            </div>

            <div class="company-card">
                <img src="images/AWS.png" alt="AWS">
            </div>

            <div class="company-card">
                <img src="images/meta.png" alt="Meta">
            </div>

            <div class="company-card">
                <img src="images/apple.png" alt="Apple">
            </div>

            <div class="company-card">
                <img src="images/Adobe.png" alt="Adobe">
            </div>

             <div class="company-card">
                <img src="images/NVIDIA.png" alt="NVIDIA">
            </div>

        </div>

    </div>

</section>

<!-- ================= Featured Internship ================= -->

<section class="featured">

    <div class="section-header">
        <span>Featured Opportunities</span>
        <h2>Latest Internships</h2>
    </div>

    <div class="internship-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>

        <article class="internship-card">

            <div class="top">

                <div class="company-logo">

                    <?php if (!empty($row['logo'])): ?>

                        <img 
                            src="<?php echo htmlspecialchars($row['logo']); ?>" 
                            alt="<?php echo htmlspecialchars($row['company_name']); ?> Logo"
                        >

                    <?php else: ?>

                        <i class="ri-building-4-line"></i>

                    <?php endif; ?>

                </div>

                <span class="type">
                    <?php echo htmlspecialchars($row['type']); ?>
                </span>

            </div>

            <h3>
                <?php echo htmlspecialchars($row['title']); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars($row['company_name']); ?>
            </p>

            <div class="tags">

                <?php
                $skills = explode(',', $row['skills']);

                foreach ($skills as $skill):
                    $skill = trim($skill);

                    if (!empty($skill)):
                ?>

                    <span>
                        <?php echo htmlspecialchars($skill); ?>
                    </span>

                <?php
                    endif;
                endforeach;
                ?>

            </div>

            <div class="bottom">

                <p>
                    <?php echo htmlspecialchars($row['location']); ?>
                </p>

                <a href="internship-details.php?id=<?php echo $row['id']; ?>">
                    View Details →
                </a>

            </div>

        </article>

    <?php endwhile; ?>

<?php else: ?>

    <p class="no-internships">
        No internship opportunities available at the moment.
    </p>

<?php endif; ?>

    </div>

    <!-- View All Button -->
    <div class="view-all-wrapper">
        <a href="internships.php" class="view-all-btn">
            View All Internships
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>
   
</section>
<!-- ================= Browse Categories ================= -->

<section class="categories">

    <div class="section-header">

        <span>Explore Opportunities</span>
        <h2>Browse Internship Categories</h2>

        <p>
            Discover internship opportunities across different industries and
            find the perfect role that matches your skills.
        </p>

    </div>

    <div class="category-grid">

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-code-box-line"></i>
        </div>
        <h3>Software Development</h3>
        <span>245 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-palette-line"></i>
        </div>
        <h3>UI / UX Design</h3>
        <span>118 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-megaphone-line"></i>
        </div>
        <h3>Marketing</h3>
        <span>170 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-bank-line"></i>
        </div>
        <h3>Finance</h3>
        <span>80 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-briefcase-4-line"></i>
        </div>
        <h3>Business</h3>
        <span>96 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-settings-3-line"></i>
        </div>
        <h3>Engineering</h3>
        <span>143 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-user-heart-line"></i>
        </div>
        <h3>Human Resources</h3>
        <span>54 Internships</span>
    </div>

    <div class="category-card">
        <div class="category-icon">
            <i class="ri-line-chart-line"></i>
        </div>
        <h3>Data Analytics</h3>
        <span>102 Internships</span>
    </div>

</div>

</section>

<!-- ================= Why Choose ================= -->

<section class="why-us">

    <div class="section-header">

        <span>Why InternConnect?</span>

        <h2>
            Everything You Need In One Platform
        </h2>

        <p>
            We simplify internship recruitment by connecting
            students and companies through one secure platform.
        </p>

    </div>


    <div class="why-grid">


        <!-- Why Card 1 -->
        <div class="why-card">

            <div class="icon">
                <i class="ri-shield-check-line"></i>
            </div>

            <h3>
                Verified Companies
            </h3>

            <p>
                Every company is reviewed before posting internship opportunities.
            </p>

        </div>


        <!-- Why Card 2 -->
        <div class="why-card">

            <div class="icon">
                <i class="ri-file-list-3-line"></i>
            </div>

            <h3>
                Easy Applications
            </h3>

            <p>
                Apply for internships quickly using your professional profile and CV.
            </p>

        </div>


        <!-- Why Card 3 -->
        <div class="why-card">

            <div class="icon">
                <i class="ri-route-line"></i>
            </div>

            <h3>
                Track Applications
            </h3>

            <p>
                Monitor the status of every application in one place.
            </p>

        </div>


        <!-- Why Card 4 -->
        <div class="why-card">

            <div class="icon">
                <i class="ri-smartphone-line"></i>
            </div>

            <h3>
                Responsive Platform
            </h3>

            <p>
                Use InternConnect on desktop, tablet, or mobile devices.
            </p>

        </div>


    </div>

</section>

<!-- ================= How It Works ================= -->

<section class="steps">

    <div class="section-header">

        <span>How It Works</span>

        <h2>Connecting Students & Companies</h2>

        <p>
            InternConnect makes the internship process simple for both students
            looking for opportunities and companies searching for talented interns.
        </p>

    </div>

    <div class="journey-grid">

        <!-- Student Journey -->

        <div class="journey-card">

            <div class="journey-title">
                <i class="ri-graduation-cap-line"></i>
                <h3>For Students</h3>
            </div>

            <div class="journey-step">
                <span>1</span>
                <p>Create Your Profile</p>
            </div>

            <div class="journey-step">
                <span>2</span>
                <p>Upload Your Resume</p>
            </div>

            <div class="journey-step">
                <span>3</span>
                <p>Apply for Internships</p>
            </div>

            <div class="journey-step">
                <span>4</span>
                <p>Track Your Application</p>
            </div>

        </div>

        <!-- Company Journey -->

        <div class="journey-card">

            <div class="journey-title">
                <i class="ri-building-4-line"></i>
                <h3>For Companies</h3>
            </div>

            <div class="journey-step">
                <span>1</span>
                <p>Create Company Account</p>
            </div>

            <div class="journey-step">
                <span>2</span>
                <p>Post Internship</p>
            </div>

            <div class="journey-step">
                <span>3</span>
                <p>Review Applicants</p>
            </div>

            <div class="journey-step">
                <span>4</span>
                <p>Hire Top Talent</p>
            </div>

        </div>

    </div>

</section>



<!-- ================= Statistics ================= -->

<section class="statistics">

    <div class="section-header">

        <span>
            Platform Overview
        </span>

        <h2>
            Helping Students Build Their Careers
        </h2>

    </div>


    <div class="stats-grid">


        <!-- Statistics Card 1 -->
        <div class="stats-card">

            <h2>
                10,000+
            </h2>

            <p>
                Registered Students
            </p>

        </div>


        <!-- Statistics Card 2 -->
        <div class="stats-card">

            <h2>
                550+
            </h2>

            <p>
                Partner Companies
            </p>

        </div>


        <!-- Statistics Card 3 -->
        <div class="stats-card">

            <h2>
                1,200+
            </h2>

            <p>
                Internship Opportunities
            </p>

        </div>


        <!-- Statistics Card 4 -->
        <div class="stats-card">

            <h2>
                95%
            </h2>

            <p>
                Placement Success
            </p>

        </div>


    </div>

</section>

<!-- ==========================================
                CALL TO ACTION
========================================== -->

<section class="cta">

    <div class="cta-container">

        <span class="section-badge">
            Join InternConnect Today
        </span>

        <h2>
            Ready to Start Your Internship Journey?
        </h2>

        <p>
            Whether you're a student searching for valuable internship
            opportunities or a company looking for talented future
            professionals, InternConnect helps you connect, collaborate,
            and grow together.
        </p>

        <div class="cta-buttons">

            <a href="internships.php" class="primary-btn">
                <i class="ri-search-line"></i>
                Find Internships
            </a>

            <a href="register.php" class="secondary-btn">
                <i class="ri-building-4-line"></i>
                Register Company
            </a>

        </div>

        <div class="cta-stats">

            <div class="cta-stat">
                <h3>10K+</h3>
                <span>Students</span>
            </div>

            <div class="cta-stat">
                <h3>550+</h3>
                <span>Companies</span>
            </div>

            <div class="cta-stat">
                <h3>1,200+</h3>
                <span>Internships</span>
            </div>

        </div>

    </div>

</section>

<!-- ==========================================
                FOOTER
========================================== -->
<?php include("footer.php"); ?>

</body>
</html>