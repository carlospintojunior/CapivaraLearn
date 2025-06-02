document.addEventListener('DOMContentLoaded', function() {
    const universitySelect = document.getElementById('university_id');
    const courseSelect = document.getElementById('course_id');
    const enrollmentForm = document.getElementById('enrollmentForm');
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');

    // Load courses when university is selected
    if (universitySelect) {
        universitySelect.addEventListener('change', function() {
            const universityId = this.value;
            if (!universityId) {
                courseSelect.innerHTML = '<option value="">Selecione um curso</option>';
                courseSelect.disabled = true;
                return;
            }

            fetch(`ajax/get_university_courses.php?university_id=${universityId}`)
                .then(response => response.json())
                .then(courses => {
                    courseSelect.innerHTML = '<option value="">Selecione um curso</option>';
                    courses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.nome;
                        courseSelect.appendChild(option);
                    });
                    courseSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Erro ao carregar cursos:', error);
                    courseSelect.innerHTML = '<option value="">Erro ao carregar cursos</option>';
                });
        });
    }

    // Form validation
    if (enrollmentForm) {
        enrollmentForm.addEventListener('submit', function(e) {
            let isValid = true;
            const today = new Date();
            const startDate = new Date(dataInicio.value);
            const endDate = dataFim.value ? new Date(dataFim.value) : null;

            // Reset error messages
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Validate university selection
            if (!universitySelect.value) {
                showError(universitySelect, 'Selecione uma universidade');
                isValid = false;
            }

            // Validate course selection
            if (!courseSelect.value) {
                showError(courseSelect, 'Selecione um curso');
                isValid = false;
            }

            // Validate start date
            if (!dataInicio.value) {
                showError(dataInicio, 'Data de início é obrigatória');
                isValid = false;
            } else if (startDate < today) {
                showError(dataInicio, 'Data de início não pode ser anterior à data atual');
                isValid = false;
            }

            // Validate end date if provided
            if (dataFim.value && endDate <= startDate) {
                showError(dataFim, 'Data de término deve ser posterior à data de início');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    function showError(element, message) {
        element.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        element.parentNode.appendChild(feedback);
    }
});
