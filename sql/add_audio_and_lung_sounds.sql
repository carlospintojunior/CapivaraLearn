-- CapivaraLearn - Suporte a áudio + Sons Adventícios (Ausculta Pulmonar)
-- Idempotente: pode ser executado mais de uma vez.

-- 1. Coluna de áudio nos itens clínicos
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testes_especiais' AND COLUMN_NAME = 'audio_filename');
SET @sql := IF(@col = 0, 'ALTER TABLE testes_especiais ADD COLUMN audio_filename VARCHAR(255) NULL AFTER video_filename', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Categoria
INSERT INTO categorias_clinicas (nome, descricao, icone, curso_alvo, ordem)
SELECT 'Ausculta Pulmonar', 'Sons respiratórios e sons adventícios identificados na ausculta torácica.', 'fa-lungs', 'Fisioterapia', 2
WHERE NOT EXISTS (SELECT 1 FROM categorias_clinicas WHERE nome = 'Ausculta Pulmonar');

SET @cat_id = (SELECT id FROM categorias_clinicas WHERE nome = 'Ausculta Pulmonar' LIMIT 1);

-- 3. Região
INSERT INTO regioes_corporais (categoria_id, nome, descricao, icone, ordem)
SELECT @cat_id, 'Sons Adventícios', 'Ruídos anormais sobrepostos aos sons respiratórios normais.', 'fa-lungs', 1
WHERE NOT EXISTS (SELECT 1 FROM regioes_corporais WHERE categoria_id = @cat_id AND nome = 'Sons Adventícios');

SET @reg_id = (SELECT id FROM regioes_corporais WHERE categoria_id = @cat_id AND nome = 'Sons Adventícios' LIMIT 1);

-- 4. Sons (o áudio é enviado depois pela administração)
INSERT INTO testes_especiais (regiao_id, nome, nome_alternativo, descricao, indicacao, ordem)
SELECT @reg_id, v.nome, v.alt, v.descricao, v.indicacao, v.ordem FROM (
  SELECT 'Roncos' AS nome, 'Sons contínuos graves' AS alt,
         'Sons contínuos, de baixa frequência (graves), semelhantes a um ronco ou ronronar. Originam-se da vibração de secreção espessa ou do estreitamento das vias aéreas de maior calibre. Podem mudar ou desaparecer após a tosse ou a aspiração.' AS descricao,
         'Bronquite, DPOC, fibrose cística, bronquiectasias e outras condições com acúmulo de secreção nas vias aéreas calibrosas.' AS indicacao, 1 AS ordem
  UNION ALL SELECT 'Sibilo', 'Chiado',
         'Som contínuo, de alta frequência (agudo), semelhante a um assobio ou chiado, mais audível na expiração. Resulta da passagem de ar por vias aéreas estreitadas por broncoespasmo, edema ou secreção.',
         'Asma, DPOC, bronquiolite, insuficiência cardíaca (asma cardíaca) e corpo estranho em via aérea.', 2
  UNION ALL SELECT 'Estridor', NULL,
         'Som contínuo, agudo e de alta intensidade, mais audível na inspiração e frequentemente audível sem estetoscópio. Indica obstrução de via aérea superior (laringe ou traqueia) e é sinal de alerta que pode exigir intervenção imediata.',
         'Laringite (crupe), epiglotite, aspiração de corpo estranho, edema de glote, paralisia de pregas vocais e estenose traqueal.', 3
  UNION ALL SELECT 'Estertores finos', 'Crepitações finas',
         'Sons descontínuos, curtos e de alta frequência, semelhantes ao atrito de fios de cabelo próximo ao ouvido ou ao velcro sendo aberto. Ocorrem na inspiração, pela abertura súbita de pequenas vias aéreas e alvéolos colapsados.',
         'Fibrose pulmonar, pneumonia, edema pulmonar, insuficiência cardíaca congestiva e atelectasia.', 4
  UNION ALL SELECT 'Estertores grossos', 'Crepitações grossas',
         'Sons descontínuos, mais longos e de baixa frequência, semelhantes a bolhas estourando. Ocorrem na inspiração e na expiração, pela passagem de ar através de secreção em vias aéreas de maior calibre, e podem se modificar com a tosse.',
         'Bronquite, bronquiectasias, pneumonia, edema pulmonar e DPOC com secreção.', 5
  UNION ALL SELECT 'Atrito pleural', NULL,
         'Som áspero, semelhante ao ranger de couro ou ao atrito de duas superfícies ásperas, audível na inspiração e na expiração. Surge do atrito entre as pleuras visceral e parietal inflamadas. Costuma vir acompanhado de dor ventilatório-dependente e não se altera com a tosse.',
         'Pleurite, pneumonia com envolvimento pleural, embolia pulmonar, tumores pleurais e doenças autoimunes.', 6
) v
WHERE NOT EXISTS (SELECT 1 FROM testes_especiais t WHERE t.regiao_id = @reg_id AND t.nome = v.nome);
