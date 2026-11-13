DROP DATABASE `aud_auditoria`;

CREATE DATABASE `aud_auditoria`;

USE `aud_auditoria`;

CREATE TABLE `aud_auditorias_facturas` (
  `id` int(11) NOT NULL,
  `proveedor_nombre` varchar(255) NOT NULL,
  `proveedor_nit` varchar(50) NOT NULL,
  `proveedor_telefono` varchar(50) NOT NULL,
  `numero_factura` varchar(100) NOT NULL,
  `total_declarado` decimal(12,2) NOT NULL,
  `transparente` tinyint(1) NOT NULL,
  `fecha_auditoria` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `aud_insumos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `costo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tasa_iva` decimal(5,2) NOT NULL DEFAULT 19.00,
  `ultima_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `aud_items_auditados` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario_con_iva` decimal(12,2) NOT NULL,
  `base_total` decimal(12,2) NOT NULL,
  `iva_total` decimal(12,2) NOT NULL,
  `total_linea` decimal(12,2) NOT NULL,
  `tasa_iva` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `aud_auditorias_facturas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `aud_insumos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `aud_items_auditados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`);

ALTER TABLE `aud_auditorias_facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `aud_insumos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `aud_items_auditados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `aud_items_auditados`
  ADD CONSTRAINT `aud_items_auditados_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `aud_auditorias_facturas` (`id`) ON DELETE CASCADE;
