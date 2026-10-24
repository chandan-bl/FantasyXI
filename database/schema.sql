-- FantasyXI — schema + seed data
-- Import: mysql -u root -P 3306 < schema.sql   (or via phpMyAdmin "Import")

DROP DATABASE IF EXISTS fantasyxi;
CREATE DATABASE fantasyxi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fantasyxi;

-- ---------------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------------

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('super_admin', 'manager') NOT NULL DEFAULT 'manager',
    status     ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE franchises (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    short_code VARCHAR(10) NOT NULL,
    status     ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE players (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120) NOT NULL,
    franchise_id     INT NOT NULL,
    role             ENUM('wicketkeeper', 'batter', 'allrounder', 'bowler') NOT NULL,
    credit_value     DECIMAL(3,1) NOT NULL,
    career_runs      INT NOT NULL DEFAULT 0,
    career_wickets   INT NOT NULL DEFAULT 0,
    career_matches   INT NOT NULL DEFAULT 0,
    career_trophies  INT NOT NULL DEFAULT 0,
    status           ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (franchise_id) REFERENCES franchises(id),
    INDEX idx_players_franchise (franchise_id),
    INDEX idx_players_role (role)
) ENGINE=InnoDB;

CREATE TABLE contests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    franchise_a_id  INT NOT NULL,
    franchise_b_id  INT NOT NULL,
    match_datetime  DATETIME NOT NULL,
    venue           VARCHAR(150) NULL,
    status          ENUM('scheduled', 'completed') NOT NULL DEFAULT 'scheduled',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (franchise_a_id) REFERENCES franchises(id),
    FOREIGN KEY (franchise_b_id) REFERENCES franchises(id),
    CHECK (franchise_a_id <> franchise_b_id),
    INDEX idx_contests_datetime (match_datetime)
) ENGINE=InnoDB;

CREATE TABLE contest_entries (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    contest_id            INT NOT NULL,
    manager_id            INT NOT NULL,
    captain_player_id     INT NOT NULL,
    vice_captain_player_id INT NOT NULL,
    total_credits_used    DECIMAL(4,1) NOT NULL,
    total_points          DECIMAL(6,1) NOT NULL DEFAULT 0,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (captain_player_id) REFERENCES players(id),
    FOREIGN KEY (vice_captain_player_id) REFERENCES players(id),
    UNIQUE KEY uniq_contest_manager (contest_id, manager_id)
) ENGINE=InnoDB;

CREATE TABLE contest_entry_players (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    entry_id         INT NOT NULL,
    player_id        INT NOT NULL,
    is_captain       TINYINT(1) NOT NULL DEFAULT 0,
    is_vice_captain  TINYINT(1) NOT NULL DEFAULT 0,
    points_awarded   DECIMAL(6,1) NOT NULL DEFAULT 0,
    FOREIGN KEY (entry_id) REFERENCES contest_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id),
    UNIQUE KEY uniq_entry_player (entry_id, player_id)
) ENGINE=InnoDB;

CREATE TABLE match_stats (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    contest_id           INT NOT NULL,
    player_id            INT NOT NULL,
    played               TINYINT(1) NOT NULL DEFAULT 0,
    runs                 INT NOT NULL DEFAULT 0,
    fours                INT NOT NULL DEFAULT 0,
    sixes                INT NOT NULL DEFAULT 0,
    is_out               TINYINT(1) NOT NULL DEFAULT 0,
    wickets              INT NOT NULL DEFAULT 0,
    wickets_bowled_lbw   INT NOT NULL DEFAULT 0,
    maidens              INT NOT NULL DEFAULT 0,
    catches              INT NOT NULL DEFAULT 0,
    stumpings            INT NOT NULL DEFAULT 0,
    run_outs_direct      INT NOT NULL DEFAULT 0,
    run_outs_assisted    INT NOT NULL DEFAULT 0,
    points               DECIMAL(6,1) NOT NULL DEFAULT 0,
    FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id),
    UNIQUE KEY uniq_contest_player (contest_id, player_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------

-- Demo users. Password for every account is "password123".
-- (bcrypt hash generated via PHP password_hash(), verified at import time)
INSERT INTO users (id, name, email, password, role, status) VALUES
(1, 'League Admin', 'admin@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'super_admin', 'active'),
(2, 'Aarav Mehta', 'aarav@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(3, 'Priya Nair', 'priya@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(4, 'Rohan Deshpande', 'rohan@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(5, 'Sneha Kulkarni', 'sneha@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(6, 'Vikram Rathore', 'vikram@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(7, 'Ananya Iyer', 'ananya@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(8, 'Karthik Subramaniam', 'karthik@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'active'),
(9, 'Meera Pillai', 'meera@fantasyxi.test', '$2y$10$0RIP1JtAlJreYJMuOoJ/F.9g5o.xheZgmpb6Kt8WSWHJAgZfUscjy', 'manager', 'inactive');

-- (franchises + players inserted below, generated from the reference IPL dataset)
-- Franchises (10 current IPL franchises)
INSERT INTO franchises (id, name, short_code, status) VALUES (1, 'Mumbai Indians', 'MI', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (2, 'Chennai Super Kings', 'CSK', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (3, 'Royal Challengers Bengaluru', 'RCB', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (4, 'Kolkata Knight Riders', 'KKR', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (5, 'Delhi Capitals', 'DC', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (6, 'Punjab Kings', 'PBKS', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (7, 'Rajasthan Royals', 'RR', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (8, 'Sunrisers Hyderabad', 'SRH', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (9, 'Gujarat Titans', 'GT', 'active');
INSERT INTO franchises (id, name, short_code, status) VALUES (10, 'Lucknow Super Giants', 'LSG', 'active');

-- Players (88 real IPL players, seeded from reference dataset; credit_value computed, see README)
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (1, 'A Mishra', 10, 'bowler', 8.5, 381, 174, 162, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (2, 'A Nehra', 8, 'bowler', 10.0, 41, 106, 88, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (3, 'AB de Villiers', 3, 'wicketkeeper', 10.0, 5181, 0, 170, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (4, 'AR Patel', 5, 'allrounder', 7.5, 2089, 139, 177, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (5, 'Ajinkya Rahane', 4, 'batter', 8.5, 5367, 1, 197, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (6, 'Ambati Rayudu', 2, 'batter', 7.5, 4348, 0, 185, 6, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (7, 'Andre Russell', 4, 'allrounder', 10.5, 2655, 123, 134, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (8, 'Arshdeep Singh', 6, 'bowler', 9.0, 32, 111, 95, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (9, 'Avesh Khan', 10, 'bowler', 9.0, 68, 93, 82, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (10, 'B Sai Sudharsan', 9, 'batter', 10.5, 2515, 0, 57, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (11, 'BB McCullum', 3, 'wicketkeeper', 8.5, 2882, 0, 109, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (12, 'Bhuvneshwar Kumar', 3, 'bowler', 8.5, 354, 226, 206, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (13, 'CH Morris', 7, 'bowler', 9.5, 618, 96, 81, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (14, 'CV Varun', 4, 'bowler', 9.5, 31, 111, 94, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (15, 'Chris Gayle', 6, 'batter', 10.0, 4997, 18, 141, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (16, 'DW Steyn', 3, 'bowler', 7.5, 167, 97, 95, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (17, 'David Miller', 5, 'batter', 7.0, 3288, 0, 143, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (18, 'David Warner', 5, 'batter', 10.0, 6567, 0, 184, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (19, 'Deepak Chahar', 1, 'bowler', 7.0, 142, 96, 103, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (20, 'Dwayne Bravo', 2, 'allrounder', 9.0, 1566, 183, 161, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (21, 'Faf du Plessis', 5, 'batter', 9.5, 4773, 0, 147, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (22, 'Gautam Gambhir', 5, 'batter', 9.0, 4217, 0, 151, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (23, 'Glenn Maxwell', 6, 'batter', 7.0, 2820, 41, 140, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (24, 'Harbhajan Singh', 4, 'bowler', 7.0, 833, 150, 163, 4, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (25, 'Hardik Pandya', 1, 'allrounder', 7.5, 2964, 82, 159, 5, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (26, 'Harshal Patel', 8, 'bowler', 10.0, 280, 151, 122, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (27, 'IK Pathan', 5, 'allrounder', 7.0, 1150, 80, 102, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (28, 'Ishan Kishan', 8, 'wicketkeeper', 9.0, 3600, 0, 127, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (29, 'Ishant Sharma', 9, 'bowler', 6.5, 57, 96, 117, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (30, 'JA Morkel', 2, 'allrounder', 8.5, 975, 85, 90, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (31, 'JH Kallis', 4, 'allrounder', 10.0, 2427, 65, 98, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (32, 'Jasprit Bumrah', 1, 'bowler', 10.0, 75, 190, 158, 5, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (33, 'Jaydev Unadkat', 8, 'bowler', 7.0, 201, 114, 115, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (34, 'Jos Buttler', 9, 'batter', 10.0, 4647, 0, 136, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (35, 'KD Karthik', 3, 'wicketkeeper', 7.0, 4843, 0, 233, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (36, 'KL Rahul', 5, 'wicketkeeper', 10.5, 5828, 0, 149, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (37, 'Kagiso Rabada', 9, 'bowler', 10.5, 262, 151, 101, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (38, 'Kieron Pollard', 1, 'batter', 6.5, 3437, 69, 179, 5, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (39, 'Krunal Pandya', 3, 'allrounder', 6.5, 1982, 107, 158, 5, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (40, 'Kuldeep Yadav', 5, 'bowler', 7.5, 210, 112, 110, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (41, 'M Vijay', 2, 'batter', 7.5, 2619, 0, 105, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (42, 'MM Sharma', 5, 'bowler', 9.0, 125, 134, 120, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (43, 'MS Dhoni', 2, 'wicketkeeper', 7.5, 5439, 0, 241, 4, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (44, 'Manish Pandey', 4, 'batter', 7.5, 4021, 0, 163, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (45, 'Mayank Agarwal', 3, 'batter', 7.0, 2764, 0, 125, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (46, 'Mitchell Marsh', 10, 'allrounder', 9.5, 1855, 37, 68, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (47, 'Moeen Ali', 4, 'allrounder', 8.0, 1167, 41, 69, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (48, 'Mohammed Shami', 10, 'bowler', 8.5, 115, 145, 132, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (49, 'Mohammed Siraj', 9, 'bowler', 8.0, 117, 128, 125, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (50, 'Nicholas Pooran', 10, 'batter', 8.0, 2527, 0, 100, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (51, 'Nitish Rana', 5, 'batter', 7.5, 3078, 10, 123, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (52, 'PA Patel', 3, 'wicketkeeper', 7.0, 2848, 0, 136, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (53, 'PP Chawla', 1, 'bowler', 7.5, 624, 192, 192, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (54, 'Pat Cummins', 8, 'allrounder', 9.0, 642, 87, 80, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (55, 'Quinton de Kock', 1, 'wicketkeeper', 10.0, 3444, 0, 118, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (56, 'Ramesh Kumar', 4, 'bowler', 7.5, 310, 105, 104, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (57, 'Rashid Khan', 9, 'bowler', 9.5, 635, 179, 153, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (58, 'Ravichandran Ashwin', 2, 'bowler', 6.5, 833, 187, 217, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (59, 'Ravindra Jadeja', 7, 'allrounder', 7.0, 3526, 180, 259, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (60, 'Rishabh Pant', 10, 'wicketkeeper', 9.5, 3879, 0, 136, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (61, 'Robin Uthappa', 2, 'wicketkeeper', 8.0, 4954, 0, 197, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (62, 'Rohit Sharma', 1, 'batter', 8.0, 7331, 15, 275, 6, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (63, 'Ruturaj Gaikwad', 2, 'batter', 9.5, 2839, 0, 84, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (64, 'SA Yadav', 1, 'batter', 8.5, 4581, 0, 165, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (65, 'SE Marsh', 6, 'batter', 10.5, 2489, 0, 69, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (66, 'SK Raina', 2, 'batter', 8.5, 5536, 25, 200, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (67, 'SL Malinga', 1, 'bowler', 10.5, 88, 170, 122, 4, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (68, 'SM Curran', 2, 'allrounder', 9.5, 997, 59, 64, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (69, 'SPD Smith', 5, 'batter', 8.5, 2495, 0, 93, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (70, 'SR Watson', 2, 'allrounder', 10.0, 3880, 92, 145, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (71, 'Sandeep Sharma', 3, 'bowler', 8.0, 65, 182, 178, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (72, 'Sanju Samson', 2, 'wicketkeeper', 8.5, 5181, 0, 185, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (73, 'Shakib Al Hasan', 4, 'allrounder', 8.0, 795, 63, 71, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (74, 'Shardul Thakur', 1, 'bowler', 8.5, 349, 119, 111, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (75, 'Shikhar Dhawan', 6, 'batter', 9.0, 6769, 4, 221, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (76, 'Shreyas Iyer', 6, 'batter', 9.0, 4233, 0, 144, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (77, 'Shubman Gill', 9, 'batter', 10.0, 4598, 0, 130, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (78, 'Sunil Narine', 4, 'allrounder', 8.5, 1820, 209, 201, 3, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (79, 'Trent Boult', 1, 'bowler', 9.5, 93, 145, 124, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (80, 'Umesh Yadav', 9, 'bowler', 7.0, 208, 144, 147, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (81, 'Virat Kohli', 3, 'batter', 9.5, 9346, 4, 276, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (82, 'Virender Sehwag', 6, 'batter', 8.0, 2728, 6, 104, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (83, 'Wriddhiman Saha', 9, 'wicketkeeper', 6.5, 2934, 0, 143, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (84, 'YK Pathan', 8, 'batter', 6.5, 3222, 42, 161, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (85, 'Yashasvi Jaiswal', 7, 'batter', 9.5, 2593, 0, 82, 0, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (86, 'Yuvraj Singh', 1, 'batter', 7.0, 2754, 36, 129, 2, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (87, 'Yuzvendra Chahal', 6, 'bowler', 10.0, 37, 233, 184, 1, 'active');
INSERT INTO players (id, name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status) VALUES (88, 'Z Khan', 5, 'bowler', 8.0, 117, 102, 99, 0, 'active');


-- Demo contests -- one per real IPL-style fixture. #1-2 are pre-scored via
-- the real app pipeline (scripts/seed_demo_entries.php: builds valid teams
-- through TeamValidator, enters plausible match_stats, runs ScoringRun) and
-- this data was then dumped from the live DB, so it's exactly what was
-- verified end-to-end, not hand-typed. #3 is locked (deadline passed) but
-- stats not yet entered, to demo the admin's pending-scoring queue. #4-8
-- are still open for managers to join.
-- NOTE: Rajasthan Royals (franchise 7) is deliberately never paired into a
-- contest here -- the reference dataset only tags 3 players as RR, and with
-- the 7-per-franchise cap no partner franchise can supply the other 8
-- needed for an 11-player squad. This was found while seeding demo data and
-- fixed by extending TeamValidator::fixtureIsFeasible() to reject such a
-- pairing server-side at contest-creation time (see README "Bug found").
-- NOTE 2: contest #3 (DC vs PBKS) started as locked-but-unscored to demo the
-- admin's pending-scoring queue, then had match stats entered and scoring
-- run through the real live app during end-to-end verification (see
-- README "Verified end-to-end") -- so it now shows completed too, and
-- manager Priya Nair additionally joined the still-open LSG vs KKR contest
-- (#6) live during testing. This dump reflects that fully-exercised state.
INSERT INTO `contests` (`id`, `franchise_a_id`, `franchise_b_id`, `match_datetime`, `venue`, `status`, `created_at`) VALUES (1,1,4,'2026-08-20 19:30:00','Wankhede Stadium, Mumbai','completed','2026-09-08 05:39:46'),(2,2,3,'2026-08-25 19:30:00','M. A. Chidambaram Stadium, Chennai','completed','2026-09-08 05:39:46'),(3,5,6,'2026-09-05 19:30:00','Arun Jaitley Stadium, Delhi','completed','2026-09-08 05:39:46'),(4,8,9,'2026-09-12 19:30:00','Rajiv Gandhi International Stadium, Hyderabad','scheduled','2026-09-08 05:39:46'),(5,5,9,'2026-09-15 19:30:00','Arun Jaitley Stadium, Delhi','scheduled','2026-09-08 05:39:46'),(6,10,4,'2026-09-18 19:30:00','BRSABV Ekana Stadium, Lucknow','scheduled','2026-09-08 05:39:46'),(7,2,8,'2026-09-22 19:30:00','M. A. Chidambaram Stadium, Chennai','scheduled','2026-09-08 05:39:46'),(8,3,6,'2026-09-25 19:30:00','M. Chinnaswamy Stadium, Bengaluru','scheduled','2026-09-08 05:39:46');
INSERT INTO `contest_entries` (`id`, `contest_id`, `manager_id`, `captain_player_id`, `vice_captain_player_id`, `total_credits_used`, `total_points`, `created_at`) VALUES (1,1,2,64,5,94.0,679.0,'2026-09-08 05:39:46'),(2,1,3,62,47,98.5,672.0,'2026-09-08 05:39:46'),(3,1,4,56,44,95.0,556.5,'2026-09-08 05:39:46'),(4,1,5,62,24,92.5,777.0,'2026-09-08 05:39:46'),(5,2,3,81,45,90.0,603.5,'2026-09-08 05:39:46'),(6,2,4,6,81,88.5,733.0,'2026-09-08 05:39:46'),(7,2,6,71,6,89.0,877.0,'2026-09-08 05:39:46'),(8,2,7,61,16,89.5,838.0,'2026-09-08 05:39:46'),(9,3,2,4,76,93.5,694.5,'2026-09-08 05:39:47'),(10,3,5,22,69,97.5,784.5,'2026-09-08 05:39:47'),(11,4,4,28,84,92.5,0.0,'2026-09-08 05:39:47'),(12,4,8,26,57,100.0,0.0,'2026-09-08 05:39:47'),(13,5,2,18,88,96.5,0.0,'2026-09-08 05:39:47'),(14,6,3,60,5,93.0,0.0,'2026-09-08 11:27:38');
INSERT INTO `contest_entry_players` (`id`, `entry_id`, `player_id`, `is_captain`, `is_vice_captain`, `points_awarded`) VALUES (1,1,55,0,0,43.0),(2,1,5,0,1,68.0),(3,1,64,1,0,44.0),(4,1,86,0,0,93.0),(5,1,78,0,0,84.0),(6,1,14,0,0,85.0),(7,1,19,0,0,22.0),(8,1,24,0,0,66.0),(9,1,32,0,0,30.0),(10,1,74,0,0,17.0),(11,1,79,0,0,49.0),(12,2,55,0,0,43.0),(13,2,5,0,0,68.0),(14,2,44,0,0,47.0),(15,2,62,1,0,112.0),(16,2,64,0,0,44.0),(17,2,7,0,0,68.0),(18,2,25,0,0,20.0),(19,2,47,0,1,0.0),(20,2,32,0,0,30.0),(21,2,67,0,0,79.0),(22,2,79,0,0,49.0),(23,3,55,0,0,43.0),(24,3,5,0,0,68.0),(25,3,44,0,1,47.0),(26,3,64,0,0,44.0),(27,3,7,0,0,68.0),(28,3,25,0,0,20.0),(29,3,47,0,0,0.0),(30,3,32,0,0,30.0),(31,3,53,0,0,22.0),(32,3,56,1,0,71.0),(33,3,79,0,0,49.0),(34,4,55,0,0,43.0),(35,4,38,0,0,72.0),(36,4,62,1,0,112.0),(37,4,64,0,0,44.0),(38,4,7,0,0,68.0),(39,4,47,0,0,0.0),(40,4,73,0,0,42.0),(41,4,78,0,0,84.0),(42,4,24,0,1,66.0),(43,4,32,0,0,30.0),(44,4,56,0,0,71.0),(45,5,61,0,0,51.0),(46,5,41,0,0,93.0),(47,5,45,0,1,31.0),(48,5,63,0,0,105.0),(49,5,66,0,0,62.0),(50,5,81,1,0,2.0),(51,5,30,0,0,96.0),(52,5,68,0,0,5.0),(53,5,16,0,0,16.0),(54,5,58,0,0,109.0),(55,5,71,0,0,16.0),(56,6,52,0,0,57.0),(57,6,6,1,0,110.0),(58,6,41,0,0,93.0),(59,6,45,0,0,31.0),(60,6,81,0,1,2.0),(61,6,68,0,0,5.0),(62,6,70,0,0,116.0),(63,6,12,0,0,67.0),(64,6,16,0,0,16.0),(65,6,58,0,0,109.0),(66,6,71,0,0,16.0),(67,7,72,0,0,37.0),(68,7,6,0,1,110.0),(69,7,41,0,0,93.0),(70,7,81,0,0,2.0),(71,7,20,0,0,105.0),(72,7,39,0,0,135.0),(73,7,70,0,0,116.0),(74,7,12,0,0,67.0),(75,7,16,0,0,16.0),(76,7,58,0,0,109.0),(77,7,71,1,0,16.0),(78,8,61,1,0,51.0),(79,8,6,0,0,110.0),(80,8,45,0,0,31.0),(81,8,63,0,0,105.0),(82,8,66,0,0,62.0),(83,8,30,0,0,96.0),(84,8,70,0,0,116.0),(85,8,12,0,0,67.0),(86,8,16,0,1,16.0),(87,8,58,0,0,109.0),(88,8,71,0,0,16.0),(89,9,36,0,0,18.0),(90,9,17,0,0,38.0),(91,9,51,0,0,35.0),(92,9,65,0,0,88.0),(93,9,76,0,1,101.0),(94,9,82,0,0,72.0),(95,9,4,1,0,7.0),(96,9,27,0,0,130.0),(97,9,40,0,0,43.0),(98,9,42,0,0,92.0),(99,9,87,0,0,13.0),(100,10,36,0,0,18.0),(101,10,22,1,0,59.0),(102,10,51,0,0,35.0),(103,10,65,0,0,88.0),(104,10,75,0,0,70.0),(105,10,69,0,1,105.0),(106,10,4,0,0,7.0),(107,10,27,0,0,130.0),(108,10,8,0,0,56.0),(109,10,42,0,0,92.0),(110,10,87,0,0,13.0),(111,11,28,1,0,0.0),(112,11,34,0,0,0.0),(113,11,77,0,0,0.0),(114,11,84,0,1,0.0),(115,11,54,0,0,0.0),(116,11,2,0,0,0.0),(117,11,29,0,0,0.0),(118,11,33,0,0,0.0),(119,11,49,0,0,0.0),(120,11,57,0,0,0.0),(121,11,80,0,0,0.0),(122,12,28,0,0,0.0),(123,12,10,0,0,0.0),(124,12,34,0,0,0.0),(125,12,77,0,0,0.0),(126,12,54,0,0,0.0),(127,12,26,1,0,0.0),(128,12,29,0,0,0.0),(129,12,33,0,0,0.0),(130,12,37,0,0,0.0),(131,12,49,0,0,0.0),(132,12,57,0,1,0.0),(133,13,36,0,0,0.0),(134,13,18,1,0,0.0),(135,13,22,0,0,0.0),(136,13,34,0,0,0.0),(137,13,4,0,0,0.0),(138,13,27,0,0,0.0),(139,13,37,0,0,0.0),(140,13,42,0,0,0.0),(141,13,49,0,0,0.0),(142,13,80,0,0,0.0),(143,13,88,0,1,0.0),(144,14,60,1,0,0.0),(145,14,5,0,1,0.0),(146,14,44,0,0,0.0),(147,14,50,0,0,0.0),(148,14,46,0,0,0.0),(149,14,24,0,0,0.0),(150,14,14,0,0,0.0),(151,14,56,0,0,0.0),(152,14,48,0,0,0.0),(153,14,9,0,0,0.0),(154,14,1,0,0,0.0);
INSERT INTO `match_stats` (`id`, `contest_id`, `player_id`, `played`, `runs`, `fours`, `sixes`, `is_out`, `wickets`, `wickets_bowled_lbw`, `maidens`, `catches`, `stumpings`, `run_outs_direct`, `run_outs_assisted`, `points`) VALUES (1,1,55,1,30,3,1,1,0,0,0,0,0,0,0,43.0),(2,1,5,1,51,1,2,0,0,0,0,0,0,0,0,68.0),(3,1,38,1,34,0,1,0,0,0,0,2,0,1,0,72.0),(4,1,44,1,35,4,0,1,0,0,0,0,0,0,0,47.0),(5,1,62,1,86,6,4,1,0,0,0,0,0,0,0,112.0),(6,1,64,1,29,1,1,0,0,0,0,1,0,0,0,44.0),(7,1,86,1,61,6,3,0,0,0,0,1,0,0,0,93.0),(8,1,7,1,13,1,0,1,2,0,0,0,0,0,0,68.0),(9,1,25,1,15,1,0,1,0,0,0,0,0,0,0,20.0),(10,1,31,1,74,0,3,1,0,0,0,1,0,0,0,100.0),(11,1,47,0,0,0,0,0,0,0,0,0,0,0,0,0.0),(12,1,73,1,29,3,1,0,0,0,1,0,0,0,0,42.0),(13,1,78,1,60,6,3,1,0,0,0,0,0,0,0,84.0),(14,1,14,1,15,0,0,1,2,2,0,0,0,0,0,85.0),(15,1,19,1,14,0,0,0,0,0,1,0,0,0,0,22.0),(16,1,24,1,12,0,0,0,2,0,0,0,0,0,0,66.0),(17,1,32,1,10,0,0,0,0,0,0,2,0,0,0,30.0),(18,1,53,1,10,0,0,0,0,0,0,1,0,0,0,22.0),(19,1,56,1,1,0,0,0,2,2,0,0,0,0,0,71.0),(20,1,74,1,1,0,0,1,0,0,0,0,0,1,0,17.0),(21,1,67,1,11,0,0,1,2,0,0,1,0,0,1,79.0),(22,1,79,1,12,0,0,0,1,1,0,0,0,0,0,49.0),(23,2,3,1,100,0,1,0,0,0,0,0,1,0,0,134.0),(24,2,11,1,77,2,2,1,0,0,0,0,0,0,0,95.0),(25,2,35,1,37,1,1,1,0,0,0,1,0,0,1,62.0),(26,2,43,1,66,2,1,1,0,0,0,2,0,0,0,98.0),(27,2,52,1,48,1,0,1,0,0,0,0,0,0,0,57.0),(28,2,61,1,39,4,0,0,0,0,0,0,0,0,0,51.0),(29,2,72,1,19,2,0,0,0,0,0,0,1,0,0,37.0),(30,2,6,1,80,4,0,1,0,0,0,1,0,0,1,110.0),(31,2,41,1,74,1,3,1,0,0,0,0,0,0,0,93.0),(32,2,45,1,26,1,0,1,0,0,0,0,0,0,0,31.0),(33,2,63,1,88,5,0,1,0,0,0,0,0,0,0,105.0),(34,2,66,1,45,5,2,0,0,0,0,0,0,0,0,62.0),(35,2,81,1,0,0,0,1,0,0,0,0,0,0,0,2.0),(36,2,20,1,81,2,1,0,0,0,0,1,0,0,0,105.0),(37,2,30,1,32,4,1,0,2,0,0,0,0,0,0,96.0),(38,2,39,1,55,0,1,1,2,2,0,0,0,0,0,135.0),(39,2,68,1,1,0,0,0,0,0,0,0,0,0,0,5.0),(40,2,70,1,60,5,3,1,1,1,0,0,0,0,0,116.0),(41,2,12,1,5,0,0,0,2,0,0,1,0,0,0,67.0),(42,2,16,1,12,0,0,1,0,0,0,0,0,0,0,16.0),(43,2,58,1,2,0,0,0,3,2,0,1,0,0,0,109.0),(44,2,71,1,12,0,0,0,0,0,0,0,0,0,0,16.0),(45,3,4,1,3,0,0,1,0,0,0,0,0,0,0,7.0),(46,3,8,1,10,1,0,1,1,1,0,1,0,0,0,56.0),(47,3,15,1,17,1,0,1,2,0,0,0,0,0,0,72.0),(48,3,17,1,24,2,0,1,0,0,0,1,0,0,0,38.0),(49,3,18,1,31,3,1,1,1,0,0,0,0,0,0,69.0),(50,3,21,1,38,3,1,1,2,1,0,1,0,0,0,117.0),(51,3,22,1,45,4,1,1,0,0,0,0,0,0,0,59.0),(52,3,23,1,52,5,2,1,1,1,0,1,0,0,0,114.0),(53,3,27,1,59,5,2,1,2,0,0,0,0,0,0,130.0),(54,3,36,1,6,0,0,1,0,0,0,1,0,0,0,18.0),(55,3,40,1,13,1,0,1,1,0,0,0,0,0,0,43.0),(56,3,42,1,20,2,0,1,2,1,0,1,0,0,0,92.0),(57,3,51,1,27,2,1,1,0,0,0,0,0,0,0,35.0),(58,3,65,1,34,3,1,1,1,1,0,1,0,0,0,88.0),(59,3,69,1,41,4,1,1,2,0,0,0,0,0,0,105.0),(60,3,75,1,48,4,1,1,0,0,0,1,0,0,0,70.0),(61,3,76,1,55,5,2,1,1,0,0,0,0,0,0,101.0),(62,3,82,1,2,0,0,1,2,1,0,1,0,0,0,72.0),(63,3,87,1,9,0,0,1,0,0,0,0,0,0,0,13.0),(64,3,88,1,16,1,0,1,1,1,0,1,0,0,0,62.0);
