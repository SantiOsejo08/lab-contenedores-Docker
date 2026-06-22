CREATE DATABASE IF NOT EXISTS mi_base_de_datos;

USE mi_base_de_datos;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL
);

INSERT INTO usuarios (nombre) VALUES ('Santiago');
INSERT INTO usuarios (nombre) VALUES ('Esteban');
INSERT INTO usuarios (nombre) VALUES ('Usuario de prueba');
