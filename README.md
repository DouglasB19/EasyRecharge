# Clone the repository
git clone <REPOSITORY_URL>
cd EasyRecharge/Backend/EasyRecharge.API

# Set up the database
# - Create `easy_recharge_db` and import the .sql file

# Install Composer dependencies
composer init
composer require firebase/php-jwt
composer dump-autoload

# Set up the frontend
cd EasyRecharge/Frontend
npm install
npm start

# Restart Apache if necessary
sudo service apache2 restart   # On Linux
# Or restart via XAMPP on Windows

3. Configure the Database
3.1. Start MySQL
Open the XAMPP control panel and start MySQL.
Access phpMyAdmin at http://localhost/phpmyadmin.
3.2. Import the Database
Create a new database called easy_recharge_db.
DataBase: easy_recharge_db

-- users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'blocked') DEFAULT 'user',  -- Campo role atualizado
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password reset attempts
CREATE TABLE password_reset_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,  -- Relacionamento com a tabela users
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Horário da tentativa
    success BOOLEAN DEFAULT FALSE,  -- Se a tentativa foi bem-sucedida ou não
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support requests table
CREATE TABLE support_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(100),
    message TEXT,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Banks table
CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(10) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Recharges table for mobile and DTH
CREATE TABLE recharges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(15),
    account_number VARCHAR(50),
    operator VARCHAR(50),
    recharge_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('card', 'balance') NOT NULL,
    recharge_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User balances table
CREATE TABLE user_balances (
    user_id INT PRIMARY KEY,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Operators table
CREATE TABLE operators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country_code CHAR(2) NOT NULL,  -- Código do país no formato ISO 3166-1 alpha-2
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transfers table
CREATE TABLE transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT NOT NULL,
    recipient_user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_date DATETIME NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (sender_user_id) REFERENCES users(id),
    FOREIGN KEY (recipient_user_id) REFERENCES users(id)
);


-- Transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('recharge', 'withdraw', 'deposit', 'transfer') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    bank_id INT DEFAULT NULL,  -- Permitindo que o banco seja opcional
    operator_id INT,  -- Relacionamento com a tabela operators
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(id) ON DELETE CASCADE  -- Relacionamento com operador
);


-- Withdrawals table
CREATE TABLE withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    agent_id INT, -- ID do agente ou ponto de atendimento
    authentication_method ENUM('biometric', 'pin', 'otp') NOT NULL,
    withdrawal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bank_id INT NOT NULL,  -- Campo para banco associado
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE CASCADE
);

-- Deposits table
CREATE TABLE deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    agent_id INT,  -- ID do agente que processou o depósito
    authentication_method ENUM('biometric', 'pin', 'otp') NOT NULL,  -- Método de autenticação
    deposit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Data e hora do depósito
    bank_id INT NOT NULL,  -- Campo para banco associado
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE CASCADE
);
