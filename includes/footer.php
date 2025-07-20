</div>

<!-- Footer com informações de versão -->
<footer class="mt-5 py-3 bg-light border-top">
    <div class="container text-center">
        <small class="text-muted">
            <?php 
            if (class_exists('AppVersion')) {
                echo AppVersion::getFooterText(); 
            } else {
                echo 'CapivaraLearn v1.0.0';
            }
            ?>
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
