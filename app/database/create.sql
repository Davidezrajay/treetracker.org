CREATE DATABASE treetracker;

USE treetracker;

CREATE TABLE users (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	first_name VARCHAR(30) NOT NULL,
	last_name VARCHAR(30) NOT NULL,
	email VARCHAR(30) NOT NULL,
	password TEXT NOT NULL,
	organization VARCHAR(30) NOT NULL,
	phone TEXT, 
	pwd_reset_required BOOLEAN DEFAULT false,
	UNIQUE email (email)
);


CREATE TABLE tokens (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	token TEXT,
	expires DATETIME NOT NULL,
	user_id INT,
	FOREIGN KEY (user_id) 
        REFERENCES users(id)
);

CREATE TABLE locations (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	lat VARCHAR(10) NOT NULL,
	lon VARCHAR(10) NOT NULL,
	gps_accuracy INT,
	user_id INT,
	FOREIGN KEY (user_id) 
        REFERENCES users(id)
);


CREATE TABLE photos (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	outdated BOOLEAN DEFAULT false,
	time_taken DATETIME NOT NULL,
	location_id INT,
	user_id INT,
	base64_image BLOB,
	FOREIGN KEY (user_id) 
        REFERENCES users(id),
	FOREIGN KEY (location_id) 
        REFERENCES locations(id)
);

CREATE TABLE notes (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	content TEXT,
	time_created DATETIME NOT NULL,
	user_id INT,
	FOREIGN KEY (user_id) 
        REFERENCES users(id)
);

ALTER TABLE notes ADD UNIQUE (time_created, user_id);

CREATE TABLE settings (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	next_update INT DEFAULT 30,
	min_gps_accuracy INT DEFAULT 30
);


CREATE TABLE trees (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	time_created DATETIME NOT NULL,
	time_updated DATETIME NOT NULL,
	missing BOOLEAN DEFAULT false,
	priority BOOLEAN DEFAULT false,
	cause_of_death_id INT,
	user_id INT,
	primary_location_id INT,
	settings_id INT,
	override_settings_id INT,
	FOREIGN KEY (user_id) 
        REFERENCES users(id),
	FOREIGN KEY (primary_location_id) 
        REFERENCES locations(id),
	FOREIGN KEY (settings_id) 
        REFERENCES settings(id),
	FOREIGN KEY (override_settings_id) 
        REFERENCES settings(id),
	FOREIGN KEY (cause_of_death_id) 
        REFERENCES notes(id)
);

CREATE TABLE photo_trees (
	tree_id INT,
	photo_id INT,
	FOREIGN KEY (tree_id) 
        REFERENCES trees(id),
	FOREIGN KEY (photo_id) 
        REFERENCES photos(id)
);

CREATE TABLE note_trees (
	tree_id INT,
	note_id INT,
	FOREIGN KEY (tree_id) 
        REFERENCES trees(id),
	FOREIGN KEY (note_id) 
        REFERENCES notes(id)
);

CREATE TABLE pending_update (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	user_id INT,
	settings_id INT,
	tree_id INT,
	location_id INT,
	FOREIGN KEY (user_id) 
        REFERENCES users(id),
	FOREIGN KEY (settings_id) 
        REFERENCES settings(id),
	FOREIGN KEY (tree_id) 
        REFERENCES trees(id),
	FOREIGN KEY (location_id) 
        REFERENCES locations(id)
);

INSERT INTO settings VALUES(1, 30, 10);


CREATE TABLE password_reminders (
	email VARCHAR(255) NOT NULL,
	token VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT NOW()
);