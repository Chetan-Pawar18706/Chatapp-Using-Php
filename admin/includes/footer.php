            </div>
            <!-- End Page Content -->
        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin JS -->
    <script src="assets/js/admin.js?v=<?php echo time(); ?>"></script>
    
    <script>
        // CSRF token for AJAX
        const CSRF_TOKEN = '<?php echo admin_csrf_token(); ?>';
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(function(tooltip) {
                new bootstrap.Tooltip(tooltip);
            });
        });
    </script>
    
    <?php if (isset($extra_scripts)): ?>
        <?php echo $extra_scripts; ?>
    <?php endif; ?>
</body>
</html>
