CREATE TABLE matricula_historico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricula_id INT NOT NULL,
    user_id INT NOT NULL,
    curso_id INT NOT NULL,
    universidade_id INT NOT NULL,
    situacao VARCHAR(20) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    motivo VARCHAR(255),
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (curso_id) REFERENCES cursos(id),
    FOREIGN KEY (universidade_id) REFERENCES universidades(id)
);
