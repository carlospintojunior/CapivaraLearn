    </div> <!-- End container-fluid -->

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Confirmação de exclusão
        function confirmDelete(name) {
            return confirm(`Tem certeza que deseja excluir "${name}"?\n\nEsta ação não pode ser desfeita.`);
        }
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
