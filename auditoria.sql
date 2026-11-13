CREATE TABLE aud_auditorias_facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_declarado DECIMAL(12,2) NOT NULL,
    transparente TINYINT(1) NOT NULL,
    fecha_auditoria DATETIME NOT NULL
);

CREATE TABLE aud_items_auditados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario_con_iva DECIMAL(12,2) NOT NULL,
    base_total DECIMAL(12,2) NOT NULL,
    iva_total DECIMAL(12,2) NOT NULL,
    total_linea DECIMAL(12,2) NOT NULL,
    tasa_iva DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES aud_auditorias_facturas(id) ON DELETE CASCADE
);
