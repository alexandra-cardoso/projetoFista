--
-- Base de dados: `ERASMUS`
--

CREATE DATABASE IF NOT EXISTS `ERASMUS`;
USE `ERASMUS`;
-- --------------------------------------------------------

--
-- Estrutura da tabela `Curso`
--

CREATE TABLE `Curso` (
  `CursoID` int(11) NOT NULL,
  `Nome` varchar(50) NOT NULL,
  `CodFaculdade` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Extraindo dados da tabela `Curso`
--

INSERT INTO `Curso` (`CursoID`, `Nome`, `CodFaculdade`) VALUES
(1, 'Engenharia Informática', 'P LISBOA07'),
(2, 'Informática e Gestão de Empresas', 'P LISBOA07'),
(3, 'Engenharia Informática', 'P LISBOA109'),
(4, 'Engenharia de Telecomunicações e Informática', 'P LISBOA07'),
(5, 'Applied Informatics', 'CZ ZILIN01');

-- --------------------------------------------------------

--
-- Estrutura da tabela `Disciplina`
--

CREATE TABLE `Disciplina` (
  `DisciplinaID` varchar(15) NOT NULL,
  `Nome` varchar(70) NOT NULL,
  `Ano` int(11) DEFAULT NULL,
  `Semestre` int(11) NOT NULL,
  `ECTS` int(11) NOT NULL,
  `CursoID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Extraindo dados da tabela `Disciplina`
--

INSERT INTO `Disciplina` (`DisciplinaID`, `Nome`, `Ano`, `Semestre`, `ECTS`, `CursoID`) VALUES
('03707', 'Fundamento de Redes de Computadores', 2, 2, 6, 1),
('03708', 'Arquitetura de Redes', 3, 1, 6, 1),
('03712', 'Engenharia de Software', 3, 1, 6, 1),
('03722', 'Probabilidades e Processos Estocásticos', 2, 1, 6, 1),
('03723', 'Tópicos de Matemática para Computação', 2, 1, 6, 1),
('03724', 'Bases de Dados', 2, 1, 6, 1),
('03725', 'Desenho e Análise de Algoritmos', 2, 2, 6, 1),
('03726', 'Projeto de Programação Multiparadigma', 2, 2, 6, 1),
('03727', 'Agentes Autónomos', 3, 1, 6, 1),
('1', 'Softcomputing and Datamining', NULL, 1, 5, 5),
('AE1PM', 'Programming Methods', NULL, 1, 4, 5),
('AE30S', 'Operating Systems', NULL, 1, 4, 5),
('AE7PS', 'Computer Network Operation', NULL, 1, 5, 5),
('AE9SI', 'Experimental Methods in Software Engineering', NULL, 1, 4, 5),
('L0731', 'Inteligência Artificial', 2, 2, 6, 1),
('L0786', 'Concepção e Desenvolvimento de Sistemas de Informação', 2, 2, 6, 1),
('L5096', 'Programação Concorrente e Distribuída', 3, 1, 6, 1),
('L5103', 'Teoria da Computação', 2, 1, 6, 1),
('L5315', 'Programação Orientada a Objetos', 2, 1, 6, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `Equivalencia`
--

CREATE TABLE `Equivalencia` (
  `EquivalenciaID` int(11) NOT NULL,
  `Disciplina_Origem` varchar(15) NOT NULL,
  `Disciplina_Destino` varchar(15) NOT NULL,
  `Ano_Aprovacao` year(4) NOT NULL,
  `Faculdade_Origem` varchar(15) NOT NULL,
  `Faculdade_Destino` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Extraindo dados da tabela `Equivalencia`
--

INSERT INTO `Equivalencia` (`EquivalenciaID`, `Disciplina_Origem`, `Disciplina_Destino`, `Ano_Aprovacao`, `Faculdade_Origem`, `Faculdade_Destino`) VALUES
(1, '03727', '1', '2026', 'A WELS01', 'P LISBOA07'),
(2, '03708', 'AE7PS', '2026', 'A WELS01', 'P LISBOA07'),
(3, '03712', 'AE1PM', '2026', 'A WELS01', 'P LISBOA07'),
(5, 'L5096', 'AE30S', '2026', 'A WELS01', 'P LISBOA07'),
(6, '03712', 'AE9SI', '2026', 'A WELS01', 'P LISBOA07');

-- --------------------------------------------------------

--
-- Estrutura da tabela `Faculdade`
--

CREATE TABLE `Faculdade` (
  `CodFaculdade` varchar(15) NOT NULL,
  `Nome` varchar(50) NOT NULL,
  `Pais` varchar(3) NOT NULL,
  `URL` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Extraindo dados da tabela `Faculdade`
--

INSERT INTO `Faculdade` (`CodFaculdade`, `Nome`, `Pais`, `URL`) VALUES
('A WELS01', 'University of Applied Sciences Upper Austria', 'AUT', NULL),
('B GENT25', 'Hogeschool Gent', 'BE', NULL),
('CZ BRNO01', 'Brno University of Technology', 'CZ', NULL),
('CZ OSTRAVA01', 'VSB - Technical University of Ostrava', 'CZ', NULL),
('CZ ZILIN01', 'Tomas Bata University in Zlín', 'CZ', NULL),
('P LISBOA07', 'ISCTE- Instituto Universitário de Lisboa', 'PT', NULL),
('P LISBOA109', 'Instituto Superior Técnico- Universidade de Lisboa', 'PT', NULL),
('SF TAMPERE06', 'Tampere University of Applied Sciences', 'FI', NULL),
('SF VANTAA06', 'Laurea University of Applied Sciences', 'FI', NULL);

--
-- Índices para tabela `Curso`
--
ALTER TABLE `Curso`
  ADD PRIMARY KEY (`CursoID`),
  ADD KEY `fk_faculdade` (`CodFaculdade`);

--
-- Índices para tabela `Disciplina`
--
ALTER TABLE `Disciplina`
  ADD PRIMARY KEY (`DisciplinaID`),
  ADD KEY `fk_curso` (`CursoID`);

--
-- Índices para tabela `Equivalencia`
--
ALTER TABLE `Equivalencia`
  ADD PRIMARY KEY (`EquivalenciaID`),
  ADD KEY `fk_disc_origem` (`Disciplina_Origem`),
  ADD KEY `fk_disc_destino` (`Disciplina_Destino`),
  ADD KEY `fk_facul_origem` (`Faculdade_Origem`),
  ADD KEY `fk_facul_destino` (`Faculdade_Destino`);

--
-- Índices para tabela `Faculdade`
--
ALTER TABLE `Faculdade`
  ADD PRIMARY KEY (`CodFaculdade`);

--
-- AUTO_INCREMENT de tabela `Curso`
--
ALTER TABLE `Curso`
  MODIFY `CursoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `Equivalencia`
--
ALTER TABLE `Equivalencia`
  MODIFY `EquivalenciaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;


--
-- Limitadores para a tabela `Curso`
--
ALTER TABLE `Curso`
  ADD CONSTRAINT `fk_faculdade` FOREIGN KEY (`CodFaculdade`) REFERENCES `Faculdade` (`CodFaculdade`);

--
-- Limitadores para a tabela `Disciplina`
--
ALTER TABLE `Disciplina`
  ADD CONSTRAINT `fk_curso` FOREIGN KEY (`CursoID`) REFERENCES `Curso` (`CursoID`);

--
-- Limitadores para a tabela `Equivalencia`
--
ALTER TABLE `Equivalencia`
  ADD CONSTRAINT `fk_disc_destino` FOREIGN KEY (`Disciplina_Destino`) REFERENCES `Disciplina` (`DisciplinaID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disc_origem` FOREIGN KEY (`Disciplina_Origem`) REFERENCES `Disciplina` (`DisciplinaID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facul_destino` FOREIGN KEY (`Faculdade_Destino`) REFERENCES `Faculdade` (`CodFaculdade`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facul_origem` FOREIGN KEY (`Faculdade_Origem`) REFERENCES `Faculdade` (`CodFaculdade`) ON UPDATE CASCADE;

