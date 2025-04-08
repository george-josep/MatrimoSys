<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Handle membership update when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['membership_type'])) {
    $userid = $_SESSION['username']; // Using userid since that's your column name
    
    // Create a new connection since connect.php closes its connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update payment_id based on membership type
    if ($_POST['membership_type'] === 'normal') {
        $sql = "UPDATE profiles SET payment_id = 2 WHERE userid = ?";
    } else if ($_POST['membership_type'] === 'premium') {
        $sql = "UPDATE profiles SET payment_id = 1 WHERE userid = ?";
    } else {
        // Handle other membership types if needed
        die("Invalid membership type.");
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid); // Using "s" since userid is VARCHAR
    
    if (!$stmt->execute()) {
        // Handle error case
        die("Error updating membership: " . $conn->error);
    }
    
    $stmt->close();
    $conn->close();

    // Redirect to normal_user.php instead of profile.php
    header("Location: normal_user.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans - MatrimoSys</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            color: #333;
            background-color: #fff;
        }

        .hero-section {
            height: 100vh;
            width: 100%;
            position: relative;
            overflow: hidden;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('4.png');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 20px;
            z-index: 2;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            font-weight: 300;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .header {
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #FFD700;
            letter-spacing: 1px;
        }

        .nav {
            display: flex;
            align-items: center;
        }

        .nav a {
            color: #333;
            text-decoration: none;
            margin-left: 30px;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #FFD700;
            transition: width 0.3s ease;
        }

        .nav a:hover {
            color: #FFD700;
        }

        .nav a:hover:after {
            width: 100%;
        }

        .plans-section {
            padding: 100px 5%;
            background-color: white;
        }

        .section-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            position: relative;
        }

        .section-title:after {
            content: '';
            position: absolute;
            width: 80px;
            height: 3px;
            background-color: #FFD700;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: #666;
            max-width: 700px;
            margin: 30px auto 50px;
            line-height: 1.6;
        }

        .plans-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }

        .plan-card {
            background-color: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            width: 350px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .plan-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }

        .plan-price {
            font-size: 2.2rem;
            color: #FFD700;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .plan-price span {
            font-size: 1rem;
            color: #666;
            font-weight: 400;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 30px;
            text-align: left;
        }

        .plan-features li {
            margin-bottom: 15px;
            font-size: 1rem;
            position: relative;
            padding-left: 30px;
            color: #555;
        }

        .plan-features li i {
            color: #FFD700;
            position: absolute;
            left: 0;
            top: 3px;
        }

        .plan-button {
            padding: 14px 0;
            font-size: 1rem;
            background-color: #FFD700;
            color: #333;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .plan-button:hover {
            background-color: #f0c800;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .premium-card {
            border: 2px solid #FFD700;
            transform: scale(1.05);
        }

        .premium-card:hover {
            transform: translateY(-10px) scale(1.05);
        }

        .premium-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #FFD700;
            color: #333;
            padding: 8px 15px;
            font-size: 0.8rem;
            font-weight: 600;
            border-bottom-left-radius: 15px;
        }

        .footer {
            background-color: #333;
            color: #fff;
            padding: 50px 5%;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            margin-bottom: 20px;
            color: #FFD700;
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: #ddd;
            margin: 0 15px;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #FFD700;
        }

        .footer-text {
            font-size: 14px;
            color: #aaa;
        }

        /* Dating app specific elements */
        .app-features {
            padding: 80px 5%;
            background-color: #f9f9f9;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 50px auto 0;
        }

        .feature-item {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 40px;
            color: #FFD700;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }

        .feature-desc {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .testimonials {
            padding: 80px 5%;
            background-color: white;
        }

        .testimonial-container {
            max-width: 1000px;
            margin: 50px auto 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .testimonial-card {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            width: 300px;
            position: relative;
            border-left: 3px solid #FFD700;
        }

        .testimonial-text {
            font-style: italic;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #FFD700;
        }

        .author-info h4 {
            font-size: 1rem;
            margin-bottom: 5px;
            color: #333;
        }

        .author-info p {
            font-size: 0.85rem;
            color: #777;
        }

        .cta-section {
            padding: 80px 5%;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.3));
            text-align: center;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #333;
            font-family: 'Playfair Display', serif;
        }

        .cta-text {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background-color: #FFD700;
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .cta-button:hover {
            background-color: #f0c800;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .plan-card {
                width: 100%;
                max-width: 350px;
            }
            
            .premium-card {
                transform: scale(1);
            }
            
            .premium-card:hover {
                transform: translateY(-10px);
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .testimonial-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="home.html">Home</a>
            <a href="profile.html">Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Perfect Match</h1>
            <p class="hero-subtitle">Begin your journey to a meaningful relationship with someone who shares your values and aspirations.</p>
        </div>
    </section>

    <section class="app-features">
        <h2 class="section-title">Why Choose MatrimoSys</h2>
        <p class="section-subtitle">Our platform is designed to help you find your perfect match with ease and confidence.</p>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <h3 class="feature-title">Verified Profiles</h3>
                <p class="feature-desc">All profiles are manually verified to ensure authenticity and safety.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="feature-title">Compatibility Matching</h3>
                <p class="feature-desc">Our advanced algorithm suggests matches based on your preferences and compatibility.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="feature-title">Privacy Control</h3>
                <p class="feature-desc">You have complete control over who can view your profile and contact information.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-desc">Our dedicated support team is always available to assist you with any queries.</p>
            </div>
        </div>
    </section>

    <section class="plans-section">
        <h2 class="section-title">Choose Your Plan</h2>
        <p class="section-subtitle">Select the plan that best suits your needs and start your journey to find your perfect match.</p>
        
        <div class="plans-container">
            <div class="plan-card">
                <h3 class="plan-name">Standard</h3>
                <div class="plan-price">Free</div>
                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> Browse 10 Profiles Daily</li>
                    <li><i class="fas fa-check-circle"></i> Simple Search Functionality</li>
                </ul>
                <form action="welcome.php" method="POST">
                    <input type="hidden" name="membership_type" value="normal">
                    <button type="submit" class="plan-button">Get Started</button>
                </form>
            </div>

            <div class="plan-card premium-card">
                <div class="premium-badge">PREMIUM</div>
                <h3 class="plan-name">Premium</h3>
                <div class="plan-price">₹200 <span>/year</span></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> Unlimited Profile Browsing</li>
                    <li><i class="fas fa-check-circle"></i> Advanced Search Functionality</li>
                </ul>
                <form action="payment.php" method="POST">
                    <input type="hidden" name="membership_type" value="premium">
                    <button type="submit" class="plan-button">Upgrade Now</button>
                </form>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Find Your Perfect Match?</h2>
            <p class="cta-text">Join thousands of happy couples who found their life partners on MatrimoSys. Your journey to a beautiful relationship starts here.</p>
            <a href="#" class="cta-button">Get Started Today</a>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">MatrimoSys</div>
            <div class="footer-links">
                <a href="#">About Us</a>
                <a href="#">Success Stories</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
            <p class="footer-text">© 2023 MatrimoSys. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>