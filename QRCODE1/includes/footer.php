    </div>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <!-- Custom JS -->
    <?php
    // Determine if we're in the pages directory or root for JS path
    $isInPagesDir = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
    $rootPath = $isInPagesDir ? '../' : '';
    ?>
    <script src="<?php echo $rootPath; ?>assets/js/script.js"></script>
</body>
</html>
