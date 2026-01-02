-- =====================================================
-- Migration: Update tbl_sala_asiento structure
-- Date: 2025-12-07
-- Description: Improve seat table with better types and constraints
-- =====================================================

-- Step 1: Backup existing table
CREATE TABLE IF NOT EXISTS tbl_sala_asiento_backup AS SELECT * FROM tbl_sala_asiento;

-- Step 2: Drop existing table (WARNING: This will delete all seat data)
-- Make sure you have a backup before running this!
DROP TABLE IF EXISTS tbl_sala_asiento;

-- Step 3: Create new table with improved structure
CREATE TABLE tbl_sala_asiento (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    idsala INT(11) NOT NULL,
    local INT(5) NOT NULL,
    fila CHAR(1) NOT NULL COMMENT 'Row letter: A, B, C, ... Z',
    columna INT(2) NOT NULL COMMENT 'Physical column position: 1-99',
    num_asiento VARCHAR(5) DEFAULT NULL COMMENT 'Visual seat number (can be different from columna)',
    tipo ENUM('NORMAL', 'VIP', 'DISCAPACITADO', 'PASILLO') DEFAULT 'NORMAL' COMMENT 'Seat type',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo' COMMENT 'Seat status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Constraints
    UNIQUE KEY unique_posicion (idsala, fila, columna),
    
    -- Indexes for performance
    INDEX idx_sala (idsala),
    INDEX idx_local (local),
    INDEX idx_tipo (tipo),
    
    -- Foreign keys (if tbl_sala and tbl_locales exist)
    FOREIGN KEY (idsala) REFERENCES tbl_sala(id) ON DELETE CASCADE,
    FOREIGN KEY (local) REFERENCES tbl_locales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 4: Migrate data from backup (if you want to preserve old data)
-- This assumes your old table had: sala, fila, numero, tipo, estado
-- Adjust field names based on your actual old structure

/*
INSERT INTO tbl_sala_asiento (idsala, local, fila, columna, num_asiento, tipo, estado)
SELECT 
    sala as idsala,
    (SELECT local FROM tbl_sala WHERE id = sala LIMIT 1) as local,
    fila,
    numero as columna,
    CAST(numero AS CHAR) as num_asiento,
    tipo,
    CASE 
        WHEN estado = '1' THEN 'activo'
        WHEN estado = '0' THEN 'inactivo'
        ELSE 'activo'
    END as estado
FROM tbl_sala_asiento_backup
WHERE tipo != 'PASILLO';  -- Skip aisles from old data if they were stored differently
*/

-- Step 5: Verify migration
SELECT 
    COUNT(*) as total_seats,
    tipo,
    estado
FROM tbl_sala_asiento
GROUP BY tipo, estado;

-- Step 6: Drop backup table (only after verifying migration was successful)
-- DROP TABLE IF EXISTS tbl_sala_asiento_backup;
