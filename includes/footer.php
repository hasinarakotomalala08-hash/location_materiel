<!-- Google Fonts (premium look) -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500&family=Poppins:wght@300;400&display=swap" rel="stylesheet">

<!-- Script Navbar Scroll Effect -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.getElementById('mainNavbar');

        if (navbar) {
            function handleScroll() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }

            window.addEventListener('scroll', handleScroll);
            handleScroll();
        }
    });
</script>

<footer class="footer-3cols mt-5 fade-in">
    <div class="container">

        <!-- Separator gold -->
        <div class="footer-separator mb-4"></div>

        <div class="row gy-4">

            <!-- Colonne 1 -->
            <div class="col-md-4 text-md-start text-center">
                <p class="footer-brand">&copy; 2025 PLUTINA EVENT</p>
                <p class="footer-desc">Location Matériel Événementiel Madagascar</p>
            </div>

            <!-- Colonne 2 -->
            <div class="col-md-4 text-md-center text-center">
                <p class="footer-title">CONTACT</p>
                <p class="footer-contact"><i class="fas fa-phone"></i> 034 34 661 49</p>
                <p class="footer-contact"><i class="fas fa-phone"></i> 032 52 500 60</p>
                <p class="footer-contact"><i class="fas fa-envelope"></i> hello@plutina-events.com</p>
            </div>

            <!-- Colonne 3 -->
            <div class="col-md-4 text-md-end text-center">
                <p class="footer-title">RÉSEAUX SOCIAUX</p>
                <p class="footer-contact"><i class="fab fa-facebook"></i> Plutina Events</p>
                <p class="footer-contact"><i class="fab fa-tiktok"></i> plutina_events</p>
            </div>
        </div>

        <!-- Separator gold bas -->
        <div class="footer-separator mt-4"></div>

    </div>
</footer>

<style>
    /* --- Footer Base --- */
    .footer-3cols {
        background-color: #0a0a0a;
        /* noir profond */
        color: #eaeaea;
        padding: 25px 0;
        font-family: "Poppins", sans-serif;
        letter-spacing: 0.5px;
        position: relative;
    }

    /* --- Gold Separator --- */
    .footer-separator {
        height: 1px;
        background: linear-gradient(to right, transparent, #d4af37, transparent);
        width: 100%;
    }

    /* --- Titles & Brand --- */
    .footer-brand {
        font-family: "Playfair Display", serif;
        font-size: 1.4rem;
        font-weight: 400;
        color: #ffffff;
        margin-bottom: 6px;
    }

    .footer-desc {
        font-size: 0.95rem;
        opacity: 0.75;
    }

    .footer-title {
        font-family: "Playfair Display", serif;
        font-size: 1.15rem;
        margin-bottom: 10px;
        color: #d4af37;
        /* Gold */
    }

    /* --- Contact line --- */
    .footer-contact {
        font-size: 0.95rem;
        margin: 3px 0;
    }

    .footer-contact i {
        margin-right: 7px;
        color: #d4af37;
        /* Gold icons */
        opacity: 0.95;
    }

    /* --- Fade-in Animation --- */
    .fade-in {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInMove 1.2s ease forwards;
    }

    @keyframes fadeInMove {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>



<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>