<?php
// footer.php
?>
<footer class="footer mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <h5>MarketPK</h5>
                <p style="color: #bbb;">Your one-stop marketplace for quality products from trusted vendors.</p>
                <div class="social-icons">
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-3">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                    <li><a href="#" class="text-white-50 text-decoration-none">Contact Us</a></li>
                    <li><a href="#" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                    <li><a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>For Vendors</h5>
                <ul class="list-unstyled">
                    <li><a href="register_vendor.php" class="text-white-50 text-decoration-none">Sell on MarketPK</a></li>
                    <li><a href="vendor_login.php" class="text-white-50 text-decoration-none">Vendor Login</a></li>
                    <li><a href="#" class="text-white-50 text-decoration-none">Vendor FAQ</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Contact Info</h5>
                <ul class="list-unstyled">
                    <li class="text-white-50"><i class="fas fa-phone"></i> +92 300 1234567</li>
                    <li class="text-white-50"><i class="fas fa-envelope"></i> info@marketpk.com</li>
                    <li class="text-white-50"><i class="fas fa-map-marker-alt"></i> Karachi, Pakistan</li>
                </ul>
            </div>
        </div>
        <div class="text-center mt-4 pt-3 border-top border-secondary">
            <p class="text-white-50">&copy; <?php echo date('Y'); ?> MarketPK. All Rights Reserved.</p>
        </div>
    </div>
    <style>
        .footer {
            background: #2c2b2b;
            color: #fff;
            padding: 40px 0 20px;
        }
        .footer h5 {
            color: #FF636B;
        }
        .social-icons a {
            transition: color 0.3s ease;
        }
        .social-icons a:hover {
            color: #FF636B !important;
        }
        .footer ul li a:hover {
            color: #FF636B !important;
        }
    </style>
</footer>
