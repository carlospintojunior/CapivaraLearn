-- CapivaraLearn - Dados iniciais: Testes Especiais de Ortopedia
-- Categoria + Regiões + Testes

-- 1. Categoria principal
INSERT INTO categorias_clinicas (nome, descricao, icone, curso_alvo, ordem) VALUES
('Testes Especiais de Ortopedia', 'Testes ortopédicos utilizados na avaliação fisioterapêutica para diagnóstico clínico de lesões musculoesqueléticas.', 'fa-bone', 'Fisioterapia', 1);

SET @cat_id = LAST_INSERT_ID();

-- 2. Regiões corporais
INSERT INTO regioes_corporais (categoria_id, nome, descricao, icone, ordem) VALUES
(@cat_id, 'Ombro', 'Testes que avaliam síndrome do impacto, manguito rotador e instabilidade.', 'fa-hand-paper', 1),
(@cat_id, 'Cotovelo', 'Focados em epicondilite (lateral/medial) e estabilidade ligamentar.', 'fa-hand-point-right', 2),
(@cat_id, 'Punho e Mão', 'Utilizados para tenossinovites e síndrome do túnel do carpo.', 'fa-hand-sparkles', 3),
(@cat_id, 'Quadril', 'Avaliam contraturas, impacto femoroacetabular e estabilidade.', 'fa-person-walking', 4);

-- Capturar IDs das regiões
SET @ombro_id = (SELECT id FROM regioes_corporais WHERE categoria_id = @cat_id AND nome = 'Ombro');
SET @cotovelo_id = (SELECT id FROM regioes_corporais WHERE categoria_id = @cat_id AND nome = 'Cotovelo');
SET @punho_id = (SELECT id FROM regioes_corporais WHERE categoria_id = @cat_id AND nome = 'Punho e Mão');
SET @quadril_id = (SELECT id FROM regioes_corporais WHERE categoria_id = @cat_id AND nome = 'Quadril');

-- 3. Testes - OMBRO
INSERT INTO testes_especiais (regiao_id, nome, nome_alternativo, descricao, tecnica, indicacao, positivo_quando, ordem) VALUES
(@ombro_id, 'Teste de Hawkins-Kennedy', NULL,
 'Avalia impacto subacromial.',
 'O examinador realiza flexão de 90° do ombro e cotovelo, seguido de rotação interna passiva forçada.',
 'Impacto subacromial (bursite/tendinopatia do manguito rotador)',
 'Dor na região anterolateral do ombro durante a rotação interna forçada.', 1),

(@ombro_id, 'Teste de Neer', NULL,
 'Avalia impacto subacromial (bursite/tendinopatia).',
 'O examinador eleva passivamente o braço do paciente em rotação interna.',
 'Impacto subacromial',
 'Dor na região anterior do ombro durante a elevação passiva.', 2),

(@ombro_id, 'Teste de Jobe', 'Lata Vazia (Empty Can)',
 'Avalia lesão do músculo supraespinhal.',
 'Braços em abdução de 90°, flexão horizontal de 30° e rotação interna máxima (polegares para baixo), com resistência à abdução.',
 'Lesão do supraespinhal',
 'Dor ou fraqueza ao resistir a abdução nessa posição.', 3),

(@ombro_id, 'Teste de Gerber', 'Lift Off Test',
 'Avalia o músculo subescapular.',
 'Paciente coloca a mão nas costas (região lombar) e tenta afastá-la contra resistência.',
 'Lesão do subescapular',
 'Incapacidade de afastar a mão das costas contra resistência.', 4),

(@ombro_id, 'Teste de Patte', NULL,
 'Avalia o infraespinhal e redondo menor.',
 'Avalia rotação externa contra resistência com o braço em abdução de 90°.',
 'Lesão do infraespinhal e/ou redondo menor',
 'Dor ou fraqueza na rotação externa resistida.', 5),

(@ombro_id, 'Drop Arm Test', 'Teste da Queda do Braço',
 'Avalia roturas do manguito rotador (supraespinhal).',
 'O paciente eleva ativamente o braço e o desce lentamente.',
 'Rotura do manguito rotador (supraespinhal)',
 'Incapacidade de controlar a descida do braço (o braço cai subitamente).', 6);

-- 4. Testes - COTOVELO
INSERT INTO testes_especiais (regiao_id, nome, nome_alternativo, descricao, tecnica, indicacao, positivo_quando, ordem) VALUES
(@cotovelo_id, 'Teste de Cozen', NULL,
 'Para epicondilite lateral ("tennis elbow").',
 'Cotovelo a 90°, antebraço pronado. O paciente faz extensão do punho e desvio radial contra resistência.',
 'Epicondilite lateral (cotovelo de tenista)',
 'Dor no epicôndilo lateral durante a extensão resistida do punho.', 1),

(@cotovelo_id, 'Teste de Maudsley', NULL,
 'Para epicondilite lateral.',
 'Resistência à extensão do terceiro dedo com o cotovelo estendido.',
 'Epicondilite lateral',
 'Dor no epicôndilo lateral ao resistir a extensão do dedo médio.', 2),

(@cotovelo_id, 'Teste do Cotovelo de Golfista', 'Epicondilite Medial',
 'Avalia epicondilite medial.',
 'O examinador estende passivamente o cotovelo e punho com o braço supinado, palpando o epicôndilo medial.',
 'Epicondilite medial (cotovelo de golfista)',
 'Dor no epicôndilo medial durante a extensão passiva do punho e cotovelo.', 3),

(@cotovelo_id, 'Teste de Estresse em Varo/Valgo', NULL,
 'Avalia instabilidade ligamentar colateral.',
 'Forças laterais (valgo) ou mediais (varo) aplicadas com o cotovelo em semiflexão (20-30°).',
 'Instabilidade ligamentar colateral do cotovelo',
 'Abertura articular excessiva ou dor durante a aplicação de estresse.', 4);

-- 5. Testes - PUNHO E MÃO
INSERT INTO testes_especiais (regiao_id, nome, nome_alternativo, descricao, tecnica, indicacao, positivo_quando, ordem) VALUES
(@punho_id, 'Teste de Finkelstein', NULL,
 'Para Tenossinovite de De Quervain.',
 'Paciente fecha a mão com o polegar dentro e o examinador realiza desvio ulnar.',
 'Tenossinovite de De Quervain',
 'Dor intensa na região do processo estilóide do rádio durante o desvio ulnar.', 1),

(@punho_id, 'Teste de Phalen', NULL,
 'Para Síndrome do Túnel do Carpo.',
 'Flexão máxima dos dois punhos (costas das mãos juntas) mantida por 60 segundos.',
 'Síndrome do Túnel do Carpo',
 'Parestesia (formigamento) no território do nervo mediano em até 60 segundos.', 2),

(@punho_id, 'Sinal de Tinel', 'Tinel no Punho',
 'Avalia compressão do nervo mediano no túnel do carpo.',
 'Percussão sobre o nervo mediano no túnel do carpo (face volar do punho).',
 'Síndrome do Túnel do Carpo',
 'Formigamento ou choque irradiado para os dedos (território do nervo mediano).', 3),

(@punho_id, 'Teste de Watson', 'Shift Test',
 'Avalia instabilidade escafolunar.',
 'O examinador pressiona o tubérculo do escafóide enquanto move o punho de desvio ulnar para desvio radial.',
 'Instabilidade escafolunar',
 'Estalido doloroso ou subluxação palpável do escafóide.', 4);

-- 6. Testes - QUADRIL
INSERT INTO testes_especiais (regiao_id, nome, nome_alternativo, descricao, tecnica, indicacao, positivo_quando, ordem) VALUES
(@quadril_id, 'Teste de Patrick', 'FABER (Flexão, Abdução, Rotação Externa)',
 'Avalia articulação sacroilíaca ou quadril.',
 'Paciente em decúbito dorsal, calcanhar sobre o joelho oposto (forma um "4"). O examinador pressiona o joelho para baixo.',
 'Patologia da articulação sacroilíaca ou do quadril',
 'Dor na região sacroilíaca ou na virilha durante a manobra.', 1),

(@quadril_id, 'Teste de Ober', NULL,
 'Avalia contratura do tensor da fáscia lata/trato iliotibial.',
 'Paciente em decúbito lateral, o examinador realiza abdução e extensão do quadril, soltando a perna.',
 'Contratura do tensor da fáscia lata / trato iliotibial',
 'Se a perna não aduzir (não descer) quando solta, o teste é positivo.', 2),

(@quadril_id, 'Teste de Trendelenburg', NULL,
 'Avalia fraqueza do glúteo médio.',
 'Paciente fica em apoio unipodal (sobre uma perna).',
 'Fraqueza do glúteo médio',
 'A pelve do lado elevado (lado oposto ao apoio) cai, indicando insuficiência do glúteo médio.', 3),

(@quadril_id, 'Teste de Thomas', NULL,
 'Avalia contratura dos flexores do quadril.',
 'Paciente supino abraça um joelho contra o peito. Observar a outra perna.',
 'Contratura em flexão do quadril (iliopsoas)',
 'A outra perna levanta da maca, indicando contratura dos flexores do quadril.', 4),

(@quadril_id, 'Teste de Quadrant Scouring', 'Impingement Test',
 'Avalia lesão labral ou artrose do quadril.',
 'Flexão passiva do quadril com rotação interna/externa combinada com carga axial pelo fêmur.',
 'Lesão labral ou artrose da articulação do quadril',
 'Dor, crepitação ou ressalto durante a combinação de movimentos com carga axial.', 5),

(@quadril_id, 'Teste de Ortolani', NULL,
 'Utilizado para instabilidade/luxação congênita do quadril em crianças.',
 'Abdução das coxas com pressão suave no trocânter maior (bebê em decúbito dorsal, quadris e joelhos fletidos a 90°).',
 'Displasia / luxação congênita do quadril (neonatos)',
 'Estalido palpável ("clunk") durante a abdução, indicando redução de um quadril luxado.', 6);
