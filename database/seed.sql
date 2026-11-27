USE project_demo;

-- Generated from data/*.csv. Do not edit manually.

SET NAMES utf8mb4;

START TRANSACTION;

-- 01 classes

INSERT INTO `classes` (`id`, `name`, `description`, `base_hp`, `base_mp`, `base_attack`, `base_defense`, `base_speed`, `base_crit_rate`, `base_crit_damage`, `hp_growth`, `mp_growth`, `attack_growth`, `defense_growth`, `speed_growth`)
VALUES
(1, 'Warrior', 'A durable melee fighter.', 150, 40, 18, 12, 20, 0.05, 1.50, 15, 4, 3, 3, 0),
(2, 'Mage', 'A powerful spellcaster.', 80, 120, 25, 5, 15, 0.05, 1.50, 8, 12, 5, 1, 0),
(3, 'Rogue', 'A fast and precise attacker.', 100, 60, 15, 7, 30, 0.15, 1.75, 10, 6, 3, 2, 0);

-- 02 items

INSERT INTO `items` (`id`, `name`, `description`, `type_id`, `required_level`, `buy_price`, `sell_price`, `equip_slot`, `bonus_hp`, `bonus_mp`, `bonus_attack`, `bonus_defense`, `bonus_speed`, `bonus_crit_rate`, `bonus_crit_damage`)
VALUES
(1001, 'Wooden Sword', 'A basic training sword.', 1, 1, 50, 20, 'weapon', 0, 0, 5, 0, 0, 0, 0),
(1002, 'Iron Sword', 'A reliable iron sword.', 1, 5, 250, 100, 'weapon', 0, 0, 12, 0, 0, 0, 0),
(1003, 'Steel Sword', 'A well-crafted steel sword.', 1, 10, 800, 320, 'weapon', 0, 0, 25, 0, 0, 0, 0),
(1004, 'Wooden Staff', 'A basic magical staff.', 1, 1, 50, 20, 'weapon', 0, 10, 5, 0, 0, 0, 0),
(1005, 'Crystal Staff', 'A staff containing magical crystals.', 1, 10, 800, 320, 'weapon', 0, 30, 28, 0, 0, 0, 0),
(1006, 'Iron Dagger', 'A lightweight dagger.', 1, 1, 50, 20, 'weapon', 0, 0, 4, 0, 3, 0.02, 0),
(1007, 'Shadow Dagger', 'A dagger designed for critical attacks.', 1, 10, 800, 320, 'weapon', 0, 0, 20, 0, 5, 0.05, 0.10),
(1101, 'Cloth Armor', 'Simple protective clothing.', 1, 1, 60, 24, 'armor', 10, 0, 0, 3, 0, 0, 0),
(1102, 'Leather Armor', 'Lightweight leather protection.', 1, 5, 200, 80, 'armor', 20, 0, 0, 8, 2, 0, 0),
(1103, 'Iron Armor', 'Heavy iron protection.', 1, 10, 600, 240, 'armor', 40, 0, 0, 18, 0, 0, 0),
(1201, 'Leather Boots', 'Basic adventuring boots.', 1, 1, 80, 32, 'boots', 0, 0, 0, 2, 2, 0, 0),
(1202, 'Swift Boots', 'Boots that improve movement speed.', 1, 10, 500, 200, 'boots', 0, 0, 0, 4, 6, 0, 0),
(1301, 'Iron Ring', 'A ring that increases attack.', 1, 5, 200, 80, 'ring', 0, 0, 5, 0, 0, 0, 0),
(1302, 'Ruby Ring', 'A ring containing a red gemstone.', 1, 10, 600, 240, 'ring', 20, 0, 8, 0, 0, 0.03, 0),
(1303, 'Sapphire Ring', 'A ring containing a blue gemstone.', 1, 10, 600, 240, 'ring', 0, 30, 8, 0, 0, 0, 0),
(2001, 'Health Potion', 'Restores 50 HP.', 2, 1, 20, 8, NULL, 0, 0, 0, 0, 0, 0, 0),
(2002, 'Mana Potion', 'Restores 30 MP.', 2, 1, 20, 8, NULL, 0, 0, 0, 0, 0, 0, 0),
(2003, 'Greater Health Potion', 'Restores 200 HP.', 2, 10, 80, 32, NULL, 0, 0, 0, 0, 0, 0, 0),
(2004, 'Greater Mana Potion', 'Restores 100 MP.', 2, 10, 80, 32, NULL, 0, 0, 0, 0, 0, 0, 0),
(3001, 'Slime Gel', 'A sticky monster material.', 3, 1, 0, 2, NULL, 0, 0, 0, 0, 0, 0, 0),
(3002, 'Animal Fur', 'Fur collected from wild animals.', 3, 1, 0, 4, NULL, 0, 0, 0, 0, 0, 0, 0),
(3003, 'Wolf Fang', 'A sharp wolf fang.', 3, 1, 0, 10, NULL, 0, 0, 0, 0, 0, 0, 0),
(3004, 'Spider Silk', 'Silk collected from giant spiders.', 3, 1, 0, 12, NULL, 0, 0, 0, 0, 0, 0, 0),
(3005, 'Bone Fragment', 'A fragment of an undead creature.', 3, 1, 0, 15, NULL, 0, 0, 0, 0, 0, 0, 0),
(3006, 'Iron Ore', 'A piece of unprocessed iron.', 3, 1, 0, 20, NULL, 0, 0, 0, 0, 0, 0, 0),
(3007, 'Magic Crystal', 'A crystal containing magical energy.', 3, 1, 0, 50, NULL, 0, 0, 0, 0, 0, 0, 0),
(3008, 'Dragon Scale', 'A rare scale from an ancient dragon.', 3, 1, 0, 250, NULL, 0, 0, 0, 0, 0, 0, 0);

-- 03 zones

INSERT INTO `zones` (`id`, `name`, `description`, `required_level`, `min_monsters`, `max_monsters`, `encounter_interval`)
VALUES
(1, 'Beginner Plains', 'A peaceful grassland inhabited by weak monsters.', 1, 1, 3, 2.00),
(2, 'Dark Forest', 'A dangerous forest filled with wild creatures.', 5, 2, 4, 3.00),
(3, 'Abandoned Mine', 'An abandoned mine occupied by monsters.', 10, 3, 5, 3.00),
(4, 'Ancient Ruins', 'The remains of an ancient civilization.', 20, 3, 5, 4.00),
(5, 'Dragon''s Lair', 'The home of a powerful ancient dragon.', 30, 1, 1, 5.00);

-- 04 monsters

INSERT INTO `monsters` (`id`, `name`, `description`, `level`, `max_hp`, `max_mp`, `attack`, `defense`, `speed`, `crit_rate`, `crit_damage`, `exp_reward`, `gold_min`, `gold_max`)
VALUES
(1, 'Slime', 'A weak gelatinous creature.', 1, 25, 0, 5, 1, 10, 0, 1.50, 8, 1, 3),
(2, 'Wild Rat', 'A small and aggressive rat.', 2, 30, 0, 7, 2, 18, 0.02, 1.50, 10, 2, 4),
(3, 'Wild Rabbit', 'A surprisingly aggressive rabbit.', 3, 35, 0, 8, 2, 22, 0.03, 1.50, 12, 2, 5),
(4, 'Wolf', 'A fast forest predator.', 5, 80, 0, 18, 6, 25, 0.05, 1.50, 30, 8, 15),
(5, 'Goblin', 'A small humanoid monster.', 6, 100, 20, 20, 8, 18, 0.05, 1.50, 35, 10, 18),
(6, 'Giant Spider', 'A dangerous forest spider.', 8, 120, 20, 24, 8, 20, 0.05, 1.50, 45, 12, 22),
(7, 'Cave Bat', 'A fast creature living underground.', 10, 150, 0, 32, 10, 30, 0.08, 1.50, 60, 18, 30),
(8, 'Skeleton', 'An undead skeletal warrior.', 12, 200, 0, 40, 18, 15, 0.05, 1.50, 80, 25, 40),
(9, 'Stone Golem', 'A heavily armored stone creature.', 15, 400, 0, 50, 35, 8, 0.02, 1.50, 120, 35, 60),
(10, 'Undead Knight', 'An ancient undead warrior.', 20, 650, 50, 75, 45, 20, 0.08, 1.75, 200, 60, 100),
(11, 'Dark Mage', 'A corrupted magical creature.', 22, 450, 200, 100, 20, 22, 0.10, 1.75, 230, 70, 120),
(12, 'Ancient Dragon', 'A legendary and powerful dragon.', 30, 5000, 500, 180, 80, 25, 0.15, 2.00, 2000, 500, 1000);

-- 05 zone_monsters

INSERT INTO `zone_monsters` (`zone_id`, `monster_id`, `spawn_weight`)
VALUES
(1, 1, 50),
(1, 2, 30),
(1, 3, 20),
(2, 4, 40),
(2, 5, 40),
(2, 6, 20),
(3, 7, 40),
(3, 8, 40),
(3, 9, 20),
(4, 10, 60),
(4, 11, 40),
(5, 12, 100);

-- 06 monster_drops

INSERT INTO `monster_drops` (`monster_id`, `item_id`, `drop_chance`, `min_quantity`, `max_quantity`)
VALUES
(1, 3001, 0.80, 1, 3),
(1, 2001, 0.08, 1, 1),
(1, 1001, 0.02, 1, 1),
(2, 3002, 0.60, 1, 2),
(2, 2001, 0.10, 1, 1),
(2, 1006, 0.02, 1, 1),
(3, 3002, 0.70, 1, 3),
(3, 2002, 0.10, 1, 1),
(3, 1201, 0.03, 1, 1),
(4, 3003, 0.60, 1, 2),
(4, 3002, 0.50, 1, 3),
(4, 1102, 0.03, 1, 1),
(5, 3006, 0.40, 1, 2),
(5, 1002, 0.05, 1, 1),
(5, 1301, 0.02, 1, 1),
(6, 3004, 0.75, 1, 3),
(6, 2001, 0.15, 1, 2),
(6, 2002, 0.15, 1, 2),
(7, 3007, 0.15, 1, 1),
(7, 1202, 0.02, 1, 1),
(8, 3005, 0.80, 1, 3),
(8, 1003, 0.03, 1, 1),
(8, 1007, 0.03, 1, 1),
(9, 3006, 0.90, 2, 5),
(9, 3007, 0.20, 1, 2),
(9, 1103, 0.05, 1, 1),
(10, 3005, 0.90, 2, 5),
(10, 3006, 0.50, 1, 3),
(10, 1302, 0.05, 1, 1),
(11, 3007, 0.80, 1, 3),
(11, 1005, 0.05, 1, 1),
(11, 1303, 0.05, 1, 1),
(12, 3008, 1.00, 2, 5),
(12, 3007, 0.80, 3, 8),
(12, 2003, 0.50, 2, 5),
(12, 2004, 0.50, 2, 5);

-- 07 skills

INSERT INTO `skills` (`id`, `name`, `description`, `effect_type`, `target_type`, `power`, `cooldown`, `cast_time`, `mp_cost`)
VALUES
(1, 'Power Strike', 'Deals 150% attack damage.', 'damage', 'enemy', 1.50, 5.00, 0, 5),
(2, 'Whirlwind', 'Deals 100% attack damage to all enemies.', 'damage', 'all_enemies', 1.00, 10.00, 0, 10),
(3, 'Second Wind', 'Restores 25% of maximum HP.', 'heal', 'self', 0.25, 20.00, 0, 10),
(4, 'Fireball', 'Deals 200% attack damage.', 'damage', 'enemy', 2.00, 5.00, 1.00, 10),
(5, 'Arcane Explosion', 'Deals 150% attack damage to all enemies.', 'damage', 'all_enemies', 1.50, 12.00, 1.50, 25),
(6, 'Meditation', 'Restores 20% of maximum MP.', 'restore_mp', 'self', 0.20, 20.00, 0, 0),
(7, 'Quick Stab', 'Deals 130% attack damage.', 'damage', 'enemy', 1.30, 3.00, 0, 4),
(8, 'Assassinate', 'Deals 250% attack damage.', 'damage', 'enemy', 2.50, 10.00, 0, 12),
(9, 'Blade Storm', 'Deals 120% attack damage to all enemies.', 'damage', 'all_enemies', 1.20, 12.00, 0, 15),
(101, 'Heavy Strike', 'A powerful monster attack.', 'damage', 'enemy', 1.50, 8.00, 0, 5),
(102, 'Dark Bolt', 'A concentrated magical attack.', 'damage', 'enemy', 2.00, 6.00, 1.00, 15),
(103, 'Dragon Breath', 'A devastating dragon attack.', 'damage', 'enemy', 2.50, 12.00, 2.00, 30);

-- 08 class_skills

INSERT INTO `class_skills` (`class_id`, `skill_id`, `required_level`)
VALUES
(1, 1, 1),
(1, 2, 5),
(1, 3, 10),
(2, 4, 1),
(2, 5, 5),
(2, 6, 10),
(3, 7, 1),
(3, 8, 5),
(3, 9, 10);

-- 09 monster_skills

INSERT INTO `monster_skills` (`monster_id`, `skill_id`, `use_weight`)
VALUES
(5, 101, 30),
(6, 101, 20),
(10, 101, 40),
(11, 102, 60),
(12, 101, 30),
(12, 103, 70);

COMMIT;
