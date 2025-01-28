-- Agregar las columnas necesarias a la tabla detalles_pedido
ALTER TABLE detalles_pedido
ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS direccion_envio TEXT NULL;
