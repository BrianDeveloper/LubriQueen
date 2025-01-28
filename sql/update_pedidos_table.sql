-- Agregar las columnas necesarias a la tabla pedidos si no existen
ALTER TABLE pedidos
ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS direccion_envio TEXT NULL,
ADD COLUMN IF NOT EXISTS referencia VARCHAR(50) NULL;
