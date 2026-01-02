    </div> <!-- Cierre admin-container -->

    <!-- Footer -->
    <footer class="admin-footer">
        <p>&copy; <?php echo date('Y'); ?> Cinerama Perú - Panel de Administración v<?php echo APP_VERSION; ?></p>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo ASSETS_URL; ?>js/main.js"></script>

    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>

    <!-- Mostrar alertas si existen -->
    <?php if (isset($_SESSION['alert'])): ?>
        <script>
            Swal.fire({
                icon: '<?php echo $_SESSION['alert']['type']; ?>',
                title: '<?php echo $_SESSION['alert']['title']; ?>',
                text: '<?php echo $_SESSION['alert']['message']; ?>',
                confirmButtonColor: '#e62429'
            });
        </script>
    <?php
        unset($_SESSION['alert']);
    endif;
    ?>
    </body>

    </html>