-- Tietokanta tietokonekauppaa varten
CREATE DATABASE IF NOT EXISTS tietokonekauppa;
USE tietokonekauppa;

-- Käyttäjätaulu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tuotetaulu
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lisätään testituotteita
INSERT INTO products (name, description, price, stock, category) VALUES
('Lenovo ThinkPad X1', 'Tehokas yrityskannettava SSD-levyllä', 1299.99, 10, 'Kannettavat'),
('Dell XPS 15', 'Huippuluokan kannettava tietokone', 1599.99, 5, 'Kannettavat'),
('AMD Ryzen 7 5800X', '8-ytiminen prosessori pelaamiseen', 349.99, 20, 'Komponentit'),
('NVIDIA GeForce RTX 3080', 'Huippuluokan näytönohjain', 799.99, 3, 'Komponentit'),
('Samsung 1TB SSD', 'Nopea SSD-levy', 129.99, 15, 'Komponentit');

CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);