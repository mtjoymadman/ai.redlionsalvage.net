-- Database: salvageyard_ai
CREATE DATABASE IF NOT EXISTS salvageyard_ai;
USE salvageyard_ai;

-- Employees Table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    suspended BOOLEAN DEFAULT FALSE,
    last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email VARCHAR(100)
);

-- Roles Table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

-- Employee Roles Junction Table
CREATE TABLE employee_roles (
    employee_id INT,
    role_id INT,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    PRIMARY KEY (employee_id, role_id)
);

-- Time Logs Table
CREATE TABLE time_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    clock_in TIMESTAMP NOT NULL,
    clock_out TIMESTAMP,
    extra_time INT DEFAULT 0, -- in minutes
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX(clock_in)
);

-- Breaks Table
CREATE TABLE breaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_log_id INT,
    start_time TIMESTAMP NOT NULL,
    duration INT NOT NULL, -- in minutes
    FOREIGN KEY (time_log_id) REFERENCES time_logs(id)
);

-- Pending Vehicles Table
CREATE TABLE pending_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_number VARCHAR(20) NOT NULL,
    vin VARCHAR(17) NOT NULL,
    make VARCHAR(50),
    model VARCHAR(50),
    year INT,
    condition VARCHAR(50),
    weight DECIMAL(10,2),
    photo VARCHAR(255),
    submitted_by INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES employees(id),
    INDEX(submitted_at)
);

-- Vehicles Table
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_number VARCHAR(20) NOT NULL,
    vin VARCHAR(17) UNIQUE NOT NULL,
    make VARCHAR(50),
    model VARCHAR(50),
    year INT,
    date_acquired TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(date_acquired)
);

-- Vehicle Stripping Table
CREATE TABLE vehicle_stripping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_number VARCHAR(20) NOT NULL,
    vin VARCHAR(17) NOT NULL,
    first_scan_at TIMESTAMP,
    strip_confirmed_at TIMESTAMP,
    cat_present BOOLEAN DEFAULT FALSE,
    status ENUM('Pending', 'Stripped') DEFAULT 'Pending',
    yardman_id INT,
    vehicle_id INT,
    FOREIGN KEY (yardman_id) REFERENCES employees(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    INDEX(vin, first_scan_at)
);

-- Fleet Vehicles Table
CREATE TABLE fleet_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20),
    vin VARCHAR(17) UNIQUE NOT NULL,
    has_scales BOOLEAN DEFAULT FALSE,
    current_weight DECIMAL(10,2) DEFAULT 0,
    last_weighed_at TIMESTAMP,
    mileage INT DEFAULT 0,
    maintenance_due DATE DEFAULT NULL,
    INDEX(has_scales, last_weighed_at)
);

-- Fleet Vehicle Assignments Table
CREATE TABLE fleet_vehicle_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT,
    driver_id INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unassigned_at TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES fleet_vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES employees(id),
    INDEX(vehicle_id, assigned_at)
);

-- Parts Table
CREATE TABLE parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    interchange_info TEXT
);

-- Vehicle Parts Junction Table
CREATE TABLE vehicle_parts (
    vehicle_id INT,
    part_id INT,
    quantity INT DEFAULT 1,
    lot_number VARCHAR(50),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (part_id) REFERENCES parts(id),
    PRIMARY KEY (vehicle_id, part_id)
);

-- Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Sales Table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    employee_id INT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX(date)
);

-- Sale Items Table
CREATE TABLE sale_items (
    sale_id INT,
    part_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    exchange_returned BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (part_id) REFERENCES parts(id),
    PRIMARY KEY (sale_id, part_id)
);

-- Scrap Customers Table
CREATE TABLE scrap_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_name VARCHAR(100),
    drivers_license_number VARCHAR(20) UNIQUE,
    drivers_license_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Scrap Metal Types Table
CREATE TABLE scrap_metal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metal_type VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Scrap Transactions Table
CREATE TABLE scrap_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    truck_id INT,
    metal_type VARCHAR(50),
    weight DECIMAL(10,2),
    price DECIMAL(10,2),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    FOREIGN KEY (customer_id) REFERENCES scrap_customers(id),
    FOREIGN KEY (truck_id) REFERENCES fleet_vehicles(id),
    FOREIGN KEY (processed_by) REFERENCES employees(id),
    INDEX(transaction_date)
);

-- Fleet Maintenance Table
CREATE TABLE fleet_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    truck_id INT,
    maintenance_date DATE,
    maintenance_type VARCHAR(50),
    notes TEXT,
    mileage_interval INT,
    scheduled_by INT,
    completed_at TIMESTAMP,
    FOREIGN KEY (truck_id) REFERENCES fleet_vehicles(id),
    FOREIGN KEY (scheduled_by) REFERENCES employees(id),
    INDEX(maintenance_date)
);

-- Fleet Maintenance Receipts Table
CREATE TABLE fleet_maintenance_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    truck_id INT,
    receipt_path VARCHAR(255),
    service_type VARCHAR(50),
    service_date DATE,
    amount DECIMAL(10,2),
    needs_reimbursement BOOLEAN DEFAULT FALSE,
    notes TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (truck_id) REFERENCES fleet_vehicles(id),
    FOREIGN KEY (uploaded_by) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id),
    INDEX(uploaded_at)
);

-- Fleet Vehicle Documents Table
CREATE TABLE fleet_vehicle_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    truck_id INT,
    document_path VARCHAR(255),
    document_type VARCHAR(50),
    expiration_date DATE NOT NULL,
    notes TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    expiration_notified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (truck_id) REFERENCES fleet_vehicles(id),
    FOREIGN KEY (uploaded_by) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id),
    INDEX(uploaded_at, status)
);

-- Insert Initial Roles
INSERT INTO roles (role_name) VALUES ('admin'), ('baby admin'), ('office'), ('driver'), ('yardman'), ('fleet'), ('employee');

-- Insert Default Admin
INSERT INTO employees (username, full_name, password, email) VALUES ('admin', 'Administrator', '$2y$10$YOUR_HASHED_PASSWORD', 'mtjoymadman@gmail.com');
INSERT INTO employee_roles (employee_id, role_id) VALUES (1, 1);