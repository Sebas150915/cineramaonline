    <footer style="background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%); padding: 3rem 2rem 2rem; margin-top: 4rem; border-top: 3px solid #DC143C;">
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- About Section -->
            <div>
                <h3 style="color: #DC143C; font-family: 'Poppins', sans-serif; font-size: 1.5rem; margin-bottom: 1rem; font-weight: 700;">CINERAMA</h3>
                <p style="color: #b3b3b3; line-height: 1.6; font-size: 0.95rem;">La mejor experiencia cinematográfica en Perú. Disfruta de las últimas películas con la mejor tecnología y comodidad.</p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 style="color: #ffffff; font-family: 'Poppins', sans-serif; margin-bottom: 1rem; font-weight: 600;">Enlaces Rápidos</h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 0.5rem;"><a href="<?php echo BASE_URL; ?>index.php" style="color: #b3b3b3; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#DC143C'" onmouseout="this.style.color='#b3b3b3'">Nuestros Cines</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="<?php echo BASE_URL; ?>cartelera.php" style="color: #b3b3b3; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#DC143C'" onmouseout="this.style.color='#b3b3b3'">Cartelera</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="<?php echo BASE_URL; ?>estrenos.php" style="color: #b3b3b3; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#DC143C'" onmouseout="this.style.color='#b3b3b3'">Próximos Estrenos</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="<?php echo BASE_URL; ?>contacto.php" style="color: #b3b3b3; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#DC143C'" onmouseout="this.style.color='#b3b3b3'">Contacto</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 style="color: #ffffff; font-family: 'Poppins', sans-serif; margin-bottom: 1rem; font-weight: 600;">Contacto</h4>
                <p style="color: #b3b3b3; margin-bottom: 0.5rem; font-size: 0.95rem;"><i class="fas fa-envelope" style="color: #DC143C; margin-right: 0.5rem;"></i> info@cinerama.com.pe</p>
                <p style="color: #b3b3b3; margin-bottom: 0.5rem; font-size: 0.95rem;"><i class="fas fa-phone" style="color: #DC143C; margin-right: 0.5rem;"></i> (01) 555-5555</p>
            </div>

            <!-- Social Media -->
            <div>
                <h4 style="color: #ffffff; font-family: 'Poppins', sans-serif; margin-bottom: 1rem; font-weight: 600;">Síguenos</h4>
                <div style="display: flex; gap: 1rem;">
                    <a href="#" style="color: #b3b3b3; font-size: 1.5rem; transition: all 0.3s;" onmouseover="this.style.color='#DC143C'; this.style.transform='translateY(-3px)'" onmouseout="this.style.color='#b3b3b3'; this.style.transform='translateY(0)'"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color: #b3b3b3; font-size: 1.5rem; transition: all 0.3s;" onmouseover="this.style.color='#DC143C'; this.style.transform='translateY(-3px)'" onmouseout="this.style.color='#b3b3b3'; this.style.transform='translateY(0)'"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: #b3b3b3; font-size: 1.5rem; transition: all 0.3s;" onmouseover="this.style.color='#DC143C'; this.style.transform='translateY(-3px)'" onmouseout="this.style.color='#b3b3b3'; this.style.transform='translateY(0)'"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: #b3b3b3; font-size: 1.5rem; transition: all 0.3s;" onmouseover="this.style.color='#DC143C'; this.style.transform='translateY(-3px)'" onmouseout="this.style.color='#b3b3b3'; this.style.transform='translateY(0)'"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; text-align: center;">
            <p style="color: #666; margin: 0; font-size: 0.9rem;">&copy; <?php echo date('Y'); ?> Cinerama. Todos los derechos reservados.</p>
        </div>
    </footer>
    </body>

    </html>