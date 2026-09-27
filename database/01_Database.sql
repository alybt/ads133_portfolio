DROP DATABASE ads_portfolio;

CREATE DATABASE IF NOT EXISTS ads_portfolio;

USE ads_portfolio;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    middle_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(250),
    password VARCHAR(250), 
    phoneno VARCHAR(200),
    dob DATE,
    address VARCHAR(250),
    photo_path VARCHAR(250),

    CONSTRAINT user_name UNIQUE (first_name, middle_name, last_name),
    UNIQUE (email)
);

CREATE TABLE photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(250) NOT NULL,
    path VARCHAR(250) NOT NULL
);

CREATE TABLE links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    link VARCHAR(250) NOT NULL
);

CREATE TABLE descriptions(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    description LONGTEXT NOT NULL
);

CREATE TABLE educations (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    school_name VARCHAR(250),
    address VARCHAR(250),
    grades DECIMAL,
    started_date DATE,
    end_date DATE,
    UNIQUE (school_name)
);

CREATE TABLE certificates(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(250),
    company VARCHAR(100),
    received_date DATE,
    UNIQUE(title)
);

CREATE TABLE certificate_description(
    certificate_id INT, 
    description_id INT, 
    FOREIGN KEY (certificate_id) REFERENCES certificates(id),
    FOREIGN KEY (description_id) REFERENCES descriptions(id) 
) ;

CREATE TABLE projects(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    started_date DATE,
    end_date DATE
);

CREATE TABLE project_description(
    project_id INT, 
    description_id INT, 
    path_id INT, 
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (description_id) REFERENCES descriptions(id), 
    FOREIGN KEY (path_id) REFERENCES photos(id)
);

CREATE TABLE work_experience(
    id INT AUTO_INCREMENT PRIMARY KEY,
    company VARCHAR(100),
    started_date DATE,
    end_date DATE
);