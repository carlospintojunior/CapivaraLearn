</div>

<!-- Footer com informações de versão -->
<footer class="mt-5 py-3 bg-light border-top">
    <div class="container">
        <div class="row align-items-center">
            <div class="col text-center">
                <small class="text-muted d-block">(C) Carlos Pinto Jr, 2025</small>
                <small class="text-muted d-block">
                    Suporte: <a href="mailto:capivara@capivaralearn.com.br" class="text-muted text-decoration-none">capivara@capivaralearn.com.br</a>
                </small>
                <small class="text-muted">
                    <?php 
                    if (class_exists('AppVersion')) {
                        echo AppVersion::getFooterText(); 
                    } else {
                        echo 'CapivaraLearn v1.1.0';
                    }
                    ?>
                </small>
            </div>
            <div class="col-auto">
                <a href="<?php echo defined('APP_GITHUB_URL') ? APP_GITHUB_URL : 'https://github.com/carlospintojunior/CapivaraLearn'; ?>" 
                   target="_blank" class="text-muted text-decoration-none" title="GitHub Repository">
                    <i class="fab fa-github fa-lg"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
