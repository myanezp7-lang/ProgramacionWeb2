CREATE DATABASE IF NOT EXISTS TIENDA CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE TIENDA;

CREATE TABLE IF NOT EXISTS PRODUCTO (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL CHECK (stock >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CLIENTE (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    direccion VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS COMPRA (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    total DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_producto INT NOT NULL,
    id_cliente INT NOT NULL,
    CONSTRAINT fk_compra_producto
        FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_compra_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo
INSERT INTO PRODUCTO (nombre, descripcion, precio, stock) VALUES
('Laptop Lenovo IdeaPad', 'Portátil ligera para trabajo y estudio.', 799000.00, 5),
('Smartphone Samsung Galaxy', 'Teléfono con cámara de alta resolución.', 540000.00, 12),
('Sofá Moderno', 'Sofá cómodo para sala de estar.', 420000.00, 3),
('Set de Toallas', 'Paquete de toallas suaves y absorbentes.', 28000.00, 20),
('Camisa de algodón', 'Camisa cómoda para uso diario.', 25000.00, 15),
('Balón de fútbol', 'Balón oficial para entrenamiento y partidos.', 32000.00, 9)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    precio = VALUES(precio),
    stock = VALUES(stock);

INSERT INTO CLIENTE (nombre, email, direccion) VALUES
('Ana García', 'ana.garcia@email.com', 'Av. Principal 123, Santiago'),
('Luis Pérez', 'luis.perez@email.com', 'Calle Las Flores 45, Valparaíso')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    direccion = VALUES(direccion);

INSERT INTO COMPRA (cantidad, total, fecha, id_producto, id_cliente) VALUES
(1, 799000.00, NOW(), 1, 1),
(2, 1080000.00, NOW(), 6, 2)
ON DUPLICATE KEY UPDATE
    cantidad = VALUES(cantidad),
    total = VALUES(total),
    id_producto = VALUES(id_producto),
    id_cliente = VALUES(id_cliente);
