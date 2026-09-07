<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_subfolder = ($current_dir === 'admin' || $current_dir === 'student');
$base_path = $is_subfolder ? '../' : './';
?>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin' && $is_subfolder): ?>
            </main>
        </div>
    </div>
    <?php else: ?>
        </div>
    </main>
    <?php endif; ?>

    <footer class="footer py-3 bg-light text-center mt-5 border-top">
        <div class="container text-muted small">
            &copy; <?php echo date('Y'); ?> <strong><?php echo escape(get_setting('site_name', 'TechEval Pro')); ?></strong>. All rights reserved.
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo $base_path; ?>assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
