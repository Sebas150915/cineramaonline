-- Tabla para mapear asientos de cada sala
-- Permite crear el layout de asientos por sala

CREATE TABLE IF NOT EXISTS `tbl_sala_asiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sala` int(5) NOT NULL COMMENT 'ID de la sala',
  `fila` varchar(2) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Letra de la fila (A, B, C...)',
  `numero` int(3) NOT NULL COMMENT 'Número del asiento en la fila',
  `tipo` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'NORMAL' COMMENT 'NORMAL, VIP, DISCAPACITADO, PASILLO',
  `estado` char(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '1' COMMENT '1=Disponible, 0=Bloqueado',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_asiento`(`sala`, `fila`, `numero`) USING BTREE,
  INDEX `fk_asiento_sala`(`sala`) USING BTREE,
  CONSTRAINT `fk_asiento_sala` FOREIGN KEY (`sala`) REFERENCES `tbl_sala` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_spanish_ci ROW_FORMAT = Compact;
