<?php
/**
 * Global Footer Component
 * Smart Tax Planning Assistance
 */
?>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-top py-3 px-4 text-center text-muted small mt-auto no-print">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-2 mb-md-0">
                        &copy; <?= date('Y') ?> <strong>Smart Tax Planning Assistance</strong>. Designed for Indian Taxpayers.
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-secondary">Educational & Planning tool &bull; Sourced from Finance Act &amp; CBDT Provisions</span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12 text-muted" style="font-size: 0.72rem;">
                        <strong>Disclaimer:</strong> This application calculates estimated tax liabilities for personal financial planning and educational simulations. It does not replace a licensed Chartered Accountant, nor does it directly file income tax returns on the official TRP portal.
                    </div>
                </div>
            </div>
        </footer>

<?php if (isLoggedIn()): ?>
    </div> <!-- .app-main -->
</div> <!-- .app-wrapper -->
<?php else: ?>
    </main>
<?php endif; ?>

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Global Scripts -->
<script src="<?= baseUrl('assets/js/app.js') ?>"></script>

</body>
</html>
