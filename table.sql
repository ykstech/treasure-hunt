CREATE TABLE treasure_teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_number INT NOT NULL UNIQUE,
    password VARCHAR(20) NOT NULL,
    attempts_left INT NOT NULL DEFAULT 2
);

INSERT INTO treasure_teams (team_number, password, attempts_left) VALUES
(1, '479425',2),
(2, '512915',2),
(3, '471295',2),
(4, '972515',2),
(5, '472915',2),
(6, '492715',2),
(7, '432927',2),
(8, '459637',2);
