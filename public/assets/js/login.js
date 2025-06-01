// Funcionalidades do sistema de login

// Alternar entre abas de login e registro
function switchTab(tabName) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to selected tab and content
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Clear demo data when switching to register
    if (tabName === 'register') {
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
    } else {
        // Restore demo data for login
        document.getElementById('email').value = 'teste@capivaralearn.com';
        document.getElementById('password').value = '123456';
    }
}

// Validação de formulário
function validateForm(formType) {
    if (formType === 'register') {
        const password = document.getElementById('reg_password').value;
        const confirmPassword = document.getElementById('reg_confirm_password').value;
        
        if (password !== confirmPassword) {
            alert('As senhas não coincidem!');
            return false;
        }
        
        if (password.length < 6) {
            alert('A senha deve ter pelo menos 6 caracteres!');
            return false;
        }
    }
    
    return true;
}

// Configurações quando o documento carrega
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus no primeiro input
    document.getElementById('email').focus();
    
    // Adicionar eventos de validação
    const registerForm = document.querySelector('#register-tab form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!validateForm('register')) {
                e.preventDefault();
            }
        });
    }
    
    // Animação do logo ao passar o mouse
    const logoImage = document.querySelector('.logo-image');
    if (logoImage) {
        logoImage.addEventListener('mouseenter', function() {
            this.style.animation = 'bounce 0.6s ease-in-out';
        });
        
        logoImage.addEventListener('animationend', function() {
            this.style.animation = 'bounce 2s ease-in-out infinite';
        });
    }
    
    // Adicionar feedback visual aos campos
    const inputs = document.querySelectorAll('.form-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            if (this.value.trim() !== '') {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });
    });
});

// Função para mostrar loading nos botões
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '⏳ Processando...';
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 3000);
}

// Adicionar event listeners aos botões de submit
document.addEventListener('DOMContentLoaded', function() {
    const submitButtons = document.querySelectorAll('.btn[type="submit"]');
    submitButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const form = this.closest('form');
            if (form.checkValidity()) {
                showLoading(this);
            }
        });
    });
});