# verificador-facturas-php

Herramienta web ligera en PHP y MySQL para validar el cuadre financiero de facturas de proveedores, calcular costos unitarios reales (evaluando precios con IVA incluido o base pura), sincronizar existencias de insumos, gestionar variables de entorno de forma segura y exportar informes en PDF.

## Funcionalidades
- **Auditoría de totales:** Verifica automáticamente si los ítems ingresados cuadran con el total declarado en la factura.
- **Desglose de impuestos:** Calcula base neta, IVA y costo unitario real por producto.
- **Sincronización de inventario:** Permite crear nuevos insumos, sumar stock o sobrescribir costos en la base de datos.
- **Reportes PDF:** Genera comprobantes individuales por auditoría y consolidados históricos por rango de fechas mediante FPDF.
- **Configuración segura:** Gestión de credenciales y parámetros de conexión mediante variables de entorno (`vlucas/phpdotenv`).

## Requisitos
- PHP >= 8.0
- Composer
- MySQL / MariaDB

## Instalación y Configuración

1. **Clonar o descargar** el repositorio en tu entorno local.
2. **Instalar dependencias** ejecutando el siguiente comando en la raíz del proyecto:
   ```bash
   composer install
   