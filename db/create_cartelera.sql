-- Tabla para gestionar la programación de cartelera
-- Relaciona: Película + Local + Sala + Horarios + Fechas

CREATE TABLE IF NOT EXISTS `tbl_cartelera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pelicula` int(5) NOT NULL COMMENT 'ID de la película',
  `local` int(5) NOT NULL COMMENT 'ID del cine/local',
  `sala` int(5) NULL DEFAULT NULL COMMENT 'ID de la sala (opcional)',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio de programación',
  `fecha_fin` date NOT NULL COMMENT 'Fecha de fin de programación',
  `horarios` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'IDs de horarios separados por coma',
  `formato` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '2D' COMMENT '2D, 3D, IMAX, etc',
  `idioma` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'ESP' COMMENT 'ESP, SUB, ING',
  `estado` char(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_cartelera_pelicula`(`pelicula`) USING BTREE,
  INDEX `fk_cartelera_local`(`local`) USING BTREE,
  INDEX `fk_cartelera_sala`(`sala`) USING BTREE,
  CONSTRAINT `fk_cartelera_pelicula` FOREIGN KEY (`pelicula`) REFERENCES `tbl_pelicula` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cartelera_local` FOREIGN KEY (`local`) REFERENCES `tbl_locales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cartelera_sala` FOREIGN KEY (`sala`) REFERENCES `tbl_sala` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_spanish_ci ROW_FORMAT = Compact;
