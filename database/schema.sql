-- project-A v0.1.0
-- this is a database structure for the idle adventure game
-- stores accounts, characters, game data, and adventure progress

CREATE DATABASE IF NOT EXISTS project_demo;

USE project_demo;

-- 01 users
-- Stores user accounts and login information
-- login_token is used to control active login sessions

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    login_token CHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) AUTO_INCREMENT = 10000;

-- 02 classes
-- Stores character classes and their starting stats
-- _growth values determine stat increases when leveling up

CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),

    base_hp INT NOT NULL,
    base_mp INT NOT NULL,
    base_attack INT NOT NULL,
    base_defense INT NOT NULL,
    base_speed INT NOT NULL DEFAULT 20,

    base_crit_rate DECIMAL(6,5) NOT NULL DEFAULT 0.05,
    base_crit_damage DECIMAL(5,2) NOT NULL DEFAULT 1.50,

    hp_growth INT NOT NULL,
    mp_growth INT NOT NULL,
    attack_growth INT NOT NULL,
    defense_growth INT NOT NULL,
    speed_growth INT NOT NULL DEFAULT 0,

    CHECK (base_hp > 0),
    CHECK (base_mp >= 0),
    CHECK (base_attack > 0),
    CHECK (base_defense >= 0),
    CHECK (base_speed > 0),
    CHECK (base_crit_rate BETWEEN 0 AND 1),
    CHECK (base_crit_damage >= 1),
    CHECK (hp_growth >= 0),
    CHECK (mp_growth >= 0),
    CHECK (attack_growth >= 0),
    CHECK (defense_growth >= 0),
    CHECK (speed_growth >= 0)
);

-- 03 characters
-- Store the player's character and their current progress
-- 

CREATE TABLE characters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    class_id INT NOT NULL,

    name VARCHAR(50) NOT NULL UNIQUE,

    level INT NOT NULL DEFAULT 1,
    exp BIGINT NOT NULL DEFAULT 0,
    gold BIGINT NOT NULL DEFAULT 0,

    current_hp INT NOT NULL,
    current_mp INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),

    CHECK (level >= 1),
    CHECK (exp >= 0),
    CHECK (gold >= 0),
    CHECK (current_hp >= 0),
    CHECK (current_mp >= 0)
);

-- 04 items
-- Stores item definitions, prices, and stat bonuses
-- Type 1: Equipment
-- Type 2: Consumable
-- Type 3: Material

CREATE TABLE items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),

    type_id TINYINT NOT NULL,
    required_level INT NOT NULL DEFAULT 1,

    buy_price INT NOT NULL DEFAULT 0,
    sell_price INT NOT NULL DEFAULT 0,

    equip_slot VARCHAR(30),

    bonus_hp INT NOT NULL DEFAULT 0,
    bonus_mp INT NOT NULL DEFAULT 0,
    bonus_attack INT NOT NULL DEFAULT 0,
    bonus_defense INT NOT NULL DEFAULT 0,
    bonus_speed INT NOT NULL DEFAULT 0,

    bonus_crit_rate DECIMAL(6,5) NOT NULL DEFAULT 0,
    bonus_crit_damage DECIMAL(5,2) NOT NULL DEFAULT 0,

    CHECK (type_id IN (1, 2, 3)),
    CHECK (required_level >= 1),
    CHECK (buy_price >= 0),
    CHECK (sell_price >= 0),
    CHECK (
        (type_id = 1 AND equip_slot IS NOT NULL)
        OR
        (type_id <> 1 AND equip_slot IS NULL)
    )
);

-- 05 inventory_stacks
-- It is used to store the stackable items owned by each character
-- The same items will be stored together in quantity form

CREATE TABLE inventory_stacks (
    character_id INT NOT NULL,
    item_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    PRIMARY KEY (character_id, item_id),

    FOREIGN KEY (character_id) REFERENCES characters(id),
    FOREIGN KEY (item_id) REFERENCES items(id),

    CHECK (quantity > 0)
);

-- 06 equipment_instances
-- Each equipment instance has its own level of enhancement
-- NULL equipped_slot means unequipped

CREATE TABLE equipment_instances (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    character_id INT NOT NULL,
    item_id INT NOT NULL,

    enhancement_level INT NOT NULL DEFAULT 0,
    equipped_slot VARCHAR(30) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (character_id) REFERENCES characters(id),
    FOREIGN KEY (item_id) REFERENCES items(id),

    UNIQUE (character_id, equipped_slot),

    CHECK (enhancement_level >= 0)
);

-- 07 zones
-- encounter size and waiting time

CREATE TABLE zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),

    required_level INT NOT NULL DEFAULT 1,

    min_monsters INT NOT NULL DEFAULT 3,
    max_monsters INT NOT NULL DEFAULT 5,

    encounter_interval DECIMAL(6,2) NOT NULL DEFAULT 3.00,

    CHECK (required_level >= 1),
    CHECK (min_monsters >= 1),
    CHECK (max_monsters >= min_monsters),
    CHECK (encounter_interval >= 0)
);

-- 08 monsters
-- Stores monster stats and basic battle rewards
-- The gold reward is randomly selected within the range of gold_min and gold_max
CREATE TABLE monsters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),

    level INT NOT NULL DEFAULT 1,

    max_hp INT NOT NULL,
    max_mp INT NOT NULL DEFAULT 0,

    attack INT NOT NULL,
    defense INT NOT NULL DEFAULT 0,
    speed INT NOT NULL DEFAULT 10,

    crit_rate DECIMAL(6,5) NOT NULL DEFAULT 0,
    crit_damage DECIMAL(5,2) NOT NULL DEFAULT 1.50,

    exp_reward INT NOT NULL,
    gold_min INT NOT NULL DEFAULT 0,
    gold_max INT NOT NULL DEFAULT 0,

    CHECK (level >= 1),
    CHECK (max_hp > 0),
    CHECK (max_mp >= 0),
    CHECK (attack >= 0),
    CHECK (defense >= 0),
    CHECK (speed > 0),
    CHECK (crit_rate BETWEEN 0 AND 1),
    CHECK (crit_damage >= 1),
    CHECK (exp_reward >= 0),
    CHECK (gold_min >= 0),
    CHECK (gold_max >= gold_min)
);

-- 09 zone_monsters
-- available monsters in each zone
-- higher spawn weight means higher spawn chance

CREATE TABLE zone_monsters (
    zone_id INT NOT NULL,
    monster_id INT NOT NULL,

    spawn_weight INT NOT NULL DEFAULT 1,

    PRIMARY KEY (zone_id, monster_id),

    FOREIGN KEY (zone_id) REFERENCES zones(id),
    FOREIGN KEY (monster_id) REFERENCES monsters(id),

    CHECK (spawn_weight > 0)
);

-- 10 monster_drops
-- Define the items that monsters can drop
-- The drop probability range is from 0 to 1


CREATE TABLE monster_drops (
    monster_id INT NOT NULL,
    item_id INT NOT NULL,

    drop_chance DECIMAL(10,8) NOT NULL,

    min_quantity INT NOT NULL DEFAULT 1,
    max_quantity INT NOT NULL DEFAULT 1,

    PRIMARY KEY (monster_id, item_id),

    FOREIGN KEY (monster_id) REFERENCES monsters(id),
    FOREIGN KEY (item_id) REFERENCES items(id),

    CHECK (drop_chance BETWEEN 0 AND 1),
    CHECK (min_quantity >= 1),
    CHECK (max_quantity >= min_quantity)
);

-- 11 skills
-- Store the effect, power, cooldown time, casting time and mana consumption of the skill

CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),

    effect_type VARCHAR(30) NOT NULL,
    target_type VARCHAR(30) NOT NULL,

    power DECIMAL(8,4) NOT NULL,

    cooldown DECIMAL(6,2) NOT NULL DEFAULT 0,
    cast_time DECIMAL(6,2) NOT NULL DEFAULT 0,

    mp_cost INT NOT NULL DEFAULT 0,

    CHECK (power > 0),
    CHECK (cooldown >= 0),
    CHECK (cast_time >= 0),
    CHECK (mp_cost >= 0)
);

-- 12 class_skills
-- Defines the skills available to each character class and the level required to unlock them

CREATE TABLE class_skills (
    class_id INT NOT NULL,
    skill_id INT NOT NULL,

    required_level INT NOT NULL DEFAULT 1,

    PRIMARY KEY (class_id, skill_id),

    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id),

    CHECK (required_level >= 1)
);

-- 13 monster_skills
-- Defines the skills available to each monster.

CREATE TABLE monster_skills (
    monster_id INT NOT NULL,
    skill_id INT NOT NULL,

    use_weight INT NOT NULL DEFAULT 1,

    PRIMARY KEY (monster_id, skill_id),

    FOREIGN KEY (monster_id) REFERENCES monsters(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id),

    CHECK (use_weight > 0)
);

-- 14 adventure_state
-- Store the current progress of the adventure and battles
-- The timestamp is used to calculate the elapsed time of the adventure
-- state_data is stored in JSON format to hold the complete simulation state
-- States: idle, active, or dead

CREATE TABLE adventure_state (
    character_id INT PRIMARY KEY,
    zone_id INT NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'idle',

    started_at DATETIME(3) NOT NULL,
    last_update_at DATETIME(3) NOT NULL,
    next_encounter_at DATETIME(3) DEFAULT NULL,

    total_kills BIGINT NOT NULL DEFAULT 0,
    total_battles BIGINT NOT NULL DEFAULT 0,

    state_data JSON NOT NULL,

    FOREIGN KEY (character_id) REFERENCES characters(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id),

    CHECK (status IN ('idle', 'active', 'dead')),
    CHECK (total_kills >= 0),
    CHECK (total_battles >= 0)
);