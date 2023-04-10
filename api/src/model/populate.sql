/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

USE examgenerator;
SET time_zone='+00:00';

START TRANSACTION;

DELETE FROM examquestion;
DELETE FROM question;
DELETE FROM examlevel;
DELETE FROM exam;
DELETE FROM coursechapter;
DELETE FROM teacherteaching;
DELETE FROM courselevelyear;
DELETE FROM course;
DELETE FROM teacher;
DELETE FROM student;
DELETE FROM level;
DELETE FROM universitycourse;
DELETE FROM user;
DELETE FROM account;
DELETE FROM universityyear;
DELETE FROM university;

ALTER TABLE university AUTO_INCREMENT = 1;
ALTER TABLE account AUTO_INCREMENT = 1;
ALTER TABLE universityyear AUTO_INCREMENT = 1;
ALTER TABLE user AUTO_INCREMENT = 1;
ALTER TABLE teacher AUTO_INCREMENT = 1;
ALTER TABLE student AUTO_INCREMENT = 1;
ALTER TABLE universitycourse AUTO_INCREMENT = 1;
ALTER TABLE level AUTO_INCREMENT = 1;
ALTER TABLE course AUTO_INCREMENT = 1;
ALTER TABLE coursechapter AUTO_INCREMENT = 1;
ALTER TABLE exam AUTO_INCREMENT = 1;
ALTER TABLE question AUTO_INCREMENT = 1;


INSERT INTO university (name, domain) VALUES
	('Université de Haute-Alsace', 'uha.fr');

INSERT INTO universityyear (id_univ, year) VALUES
	(1, '2018'),
	(1, '2019'),
	(1, '2020'),
	(1, '2021'),
	(1, '2022');

INSERT INTO account (id_univ, mail, password) VALUES
	(1, 'admin@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'dorian.thivolle@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'arthur.gros@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'jean-charles.armbruster@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'red.oublant@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'some.one@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'joel.heinis@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'), -- password : test
	(1, 'laurent.moalic@uha.fr', '$2y$10$.2e4/qJjfDRsxeKZ5Tc18uqzpHuV.crNf4MzAkaiFgAsZ6MBGJmjO'); -- password : test

INSERT INTO user (id_account, firstname, lastname, uuid) VALUES
	(2, 'Dorian', 'Thivolle', 'e2200381'),
	(3, 'Arthur', 'Gros', 'e1800704'),
	(4, 'Jean-Charles', 'Armbruster', 'e1801864'),
	(5, 'Red', 'Houx blanc', 'e0000001'),
	(6, 'Some', 'One', 'e0000002'),
	(7, 'Joel', 'Heinis', 'p1000000'),
	(8, 'Laurent', 'Moalic', 'p1000001');

INSERT INTO universitycourse (id_univ, name) VALUES
	(1, 'Informatique et Mobilité'),
	(1, 'Miage'),
	(1, 'Droit');

INSERT INTO level (id_universitycourse, name) VALUES
	(1, 'L1'),
	(1, 'L2'),
	(1, 'L3'),
	(1, 'M1'),
	(1, 'M2'),
	(2, 'L1'),
	(2, 'L2'),
	(2, 'L3'),
	(2, 'M1'),
	(2, 'M2');

INSERT INTO student (id_user, id_level, id_year) VALUES
	(1, 4, 5),
	(2, 4, 5),
	(3, 4, 5),
	(4, 4, 4),
	(5, 3, 5);

INSERT INTO teacher (id_user) VALUES
	(6),
	(7);

INSERT INTO course (id_univ, name) VALUES
	(1, 'Architecture N-tiers'),
	(1, 'Synchronisation et communication avancée dans les systèmes'),
	(1, 'Java pour les nuls');

INSERT INTO courselevelyear (id_level, id_course, id_year) VALUES
	(4, 1, 5),
	(4, 2, 5),
	(3, 3, 5),
	(4, 1, 4),
	(4, 2, 4),
	(3, 3, 4),
	(6, 3, 4),
	(9, 1, 5);

INSERT INTO teacherteaching (id_teacher, id_course, id_year) VALUES
	(1, 1, 5),
	(2, 2, 5),
	(1, 1, 4);

INSERT INTO coursechapter (id_course, label, position) VALUES
	(1, 'Les Bases du PHP', 0),
	(1, 'Base de données', 1),
	(1, 'Structure MVC', 2),
	(1, 'Application Programming Interface', 3),
	(1, 'Optimisations de bus', 4),
	(1, 'Pourquoi pas NodeJS ?', 5),
	(2, 'chapitre 1', 0),
	(2, 'chapitre 2', 1),
	(2, 'chapitre 3', 2);

INSERT INTO exam (id_course, name, coeff, type, id_year, date_start, date_end, is_corrected) VALUES
	(1, 'controle continu 1', 		1, '0', 5, '2022-10-05 10:15:00', '2022-10-05 12:15:00', 1),
	(1, 'controle continu 2', 		1, '0', 5, '2022-12-20 10:15:00', '2022-12-20 12:15:00', ''),
	(1, 'controle intermédiaire 1', 2, '1', 5, '2023-01-23 10:15:00', '2023-02-07 12:15:00', ''),
	(1, 'controle intermédiaire 2', 2, '1', 5, '2023-02-20 10:15:00', '2023-03-20 12:15:00', ''),
	(1, 'controle final 1', 		3, '2', 5, '2023-03-07 10:15:00', '2023-03-10 12:15:00', ''),
	(1, 'controle final 9 3/4',		5, '2', 5, '2023-05-20 10:15:00', '2023-05-20 12:15:00', ''),
	--
	(2, 'controle continu 1', 		1, '0', 5, '2022-10-05 10:15:00', '2022-10-05 12:15:00', ''),
	(2, 'controle continu 2', 		1, '0', 5, '2022-12-20 10:15:00', '2022-12-20 12:15:00', ''),
	(2, 'controle intermédiaire 1', 2, '1', 5, '2023-01-23 10:15:00', '2023-02-07 12:15:00', ''),
	(2, 'controle intermédiaire 2', 2, '1', 5, '2023-02-20 10:15:00', '2023-03-20 12:15:00', ''),
	(2, 'controle final 1', 		3, '2', 5, '2023-03-07 10:15:00', '2023-03-10 12:15:00', ''),
	(2, 'controle final 2', 		5, '2', 5, '2023-05-20 10:15:00', '2023-05-20 12:15:00', ''),
	--
	(1, 'controle continu 1', 		1, '0', 4, '2021-10-05 10:15:00', '2021-10-05 12:15:00', ''),
	(1, 'controle continu 2', 		1, '0', 4, '2021-12-20 10:15:00', '2021-12-20 12:15:00', ''),
	(1, 'controle intermédiaire 1', 2, '1', 4, '2022-01-23 10:15:00', '2022-02-07 12:15:00', ''),
	(1, 'controle intermédiaire 2', 2, '1', 4, '2022-02-20 10:15:00', '2022-03-20 12:15:00', ''),
	(1, 'controle final 1', 		3, '2', 4, '2022-03-07 10:15:00', '2022-03-10 12:15:00', ''),
	(1, 'controle final 2', 		5, '2', 4, '2022-05-20 10:15:00', '2022-05-20 12:15:00', ''),
	--
	(2, 'controle continu 1', 		1, '0', 4, '2021-10-05 10:15:00', '2021-10-05 12:15:00', ''),
	(2, 'controle continu 2', 		1, '0', 4, '2021-12-20 10:15:00', '2021-12-20 12:15:00', ''),
	(2, 'controle intermédiaire 1', 2, '1', 4, '2022-01-23 10:15:00', '2022-02-07 12:15:00', ''),
	(2, 'controle intermédiaire 2', 2, '1', 4, '2022-02-20 10:15:00', '2022-03-20 12:15:00', ''),
	(2, 'controle final 1', 		3, '2', 4, '2022-03-07 10:15:00', '2022-03-10 12:15:00', ''),
	(2, 'controle final 2', 		5, '2', 4, '2022-05-20 10:15:00', '2022-05-20 12:15:00', '');

INSERT INTO examlevel (id_level, id_exam) VALUES
	(4, 1),
	(3, 2),
	(4, 3),
	(3, 4),
	(4, 5),
	(3, 6),
	(4, 7),
	(3, 8),
	(4, 9),
	(3, 10),
	(4, 11),
	(3, 12),
	(4, 13),
	(3, 14),
	(4, 15),
	(3, 16),
	(4, 17),
	(3, 18),
	(4, 19),
	(3, 20),
	(4, 21),
	(3, 22),
	(4, 23),
	(3, 24);

INSERT INTO question (id_chapter, state, proposals, answers, type) VALUES
	(1, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',  'les propositions de réponses', 'les vraies réponses', '0'),
	(1, 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?', 'A;B;C;D', '0', '1'),
	(1, 'But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?', 'A;B;C;D', '1;2', '2'),
	(2, 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit.', 'A;B;C;D', '1;3', '2'),
	(2, 'placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum',  '', 'fallait répondre ça', '0'),
	(2, 'On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire', '', 'la super réponse parfaite', '0'),
	(2, 'that they cannot foresee the pain and trouble that are bound to ensue', 'A;B', '1', '1'),
	(2, 'The wise man therefore always holds in these matters', 'A;B;C', '2', '1'),
	(2, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', 'A;B;C;D;F;G', '3', '1'),
	(3, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '', 'fallait répondre ça', '0'),
	(3, 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?', '', 'la super réponse parfaite', '0'),
	(3, 'But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?', 'A;B;C', '0;1', '2'),
	(3, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', 'A;B;C;D', 						'1;3', 							'2'),
	(3, 'But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?', 'A;B', '0', '1'),
	(3, 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit.', 'A;B;C;D', '1;3', '2'),
	(4, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', '', 'fallait répondre ça', '0'),
	(4, 'placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum', '', 'la super réponse parfaite', '0'),
	(4, 'The wise man therefore always holds in these matters', '', 'pas de réponse précise', '0'),
	(4, 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit.', '', 'je sais pas', '0'),
	(4, 'The wise man therefore always holds in these matters', 'A;B;C;D','1;3', '2'),
	(4, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', 'A;B;C;D', '1;3', '2'),
	(5, 'placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum', '', 'fallait répondre ça', '0'),
	(5, 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?', '', 'la super réponse parfaite', '0'),
	(5, 'that they cannot foresee the pain and trouble that are bound to ensue', '', 'Javascript, évidemment', '0'),
	(5, 'The wise man therefore always holds in these matters', '', 'réponse libre', '0'),
	(5, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', '', 'réponse libre', '0'),
	(5, 'that they cannot foresee the pain and trouble that are bound to ensue', '', 'réponse libre', '0'),
	(6, 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?', '', 'fallait répondre ça', '0'),
	(6, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', '', 'la super réponse parfaite', '0'),
	(6, 'On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire', 'A;B;C;D', '1;3', '2'),
	(6, 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit.', 'A;B;C;D', '1;2', '2'),
	(6, 'he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.', 'A;B;C;D', '2;3', '2'),
	(6, 'On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire', 'A;B;C;D', '0;1', '2'),
	(7, 'placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum', 'aucun;sens', 'une réponse', '0');

INSERT INTO examquestion (nb_points, id_exam, id_question) VALUES
	(15, 1,  1),
	(2,  1,  2),
	(2,  1,  3),
	(1,  1,  4),
	--
	(15, 2,  1),
	(2,  2,  2),
	(2,  2,  3),
	(1,  2,  4),
	--
	(15, 3,  1),
	(2,  3,  2),
	(2,  3,  3),
	(1,  3,  4),
	--
	(15, 4,  1),
	(2,  4,  2),
	(2,  4,  3),
	(1,  4,  4),
	--
	(15, 5,  1),
	(2,  5,  2),
	(2,  5,  3),
	(1,  5,  4),
	--
	(15, 6,  1),
	(2,  6,  2),
	(2,  6,  3),
	(1,  6,  4),
	--
	(15, 7,  1),
	(2,  7,  2),
	(2,  7,  3),
	(1,  7,  4),
	--
	(15, 8,  1),
	(2,  8,  2),
	(2,  8,  3),
	(1,  8,  4),
	--
	(15, 9,  1),
	(2,  9,  2),
	(2,  9,  3),
	(1,  9,  4),
	--
	(15, 10,  1),
	(2,  10,  2),
	(2,  10,  3),
	(1,  10,  4),
	--
	(15, 11,  1),
	(2,  11,  2),
	(2,  11,  3),
	(1,  11,  4),
	--
	(15, 12,  1),
	(2,  12,  2),
	(2,  12,  3),
	(1,  12,  4),
	--
	(15, 13,  1),
	(2,  13,  2),
	(2,  13,  3),
	(1,  13,  4),
	--
	(15, 14,  1),
	(2,  14,  2),
	(2,  14,  3),
	(1,  14,  4),
	--
	(15, 15,  1),
	(2,  15,  2),
	(2,  15,  3),
	(1,  15,  4),
	--
	(15, 16,  1),
	(2,  16,  2),
	(2,  16,  3),
	(1,  16,  4),
	--
	(15, 17,  1),
	(2,  17,  2),
	(2,  17,  3),
	(1,  17,  4),
	--
	(15, 18,  1),
	(2,  18,  2),
	(2,  18,  3),
	(1,  18,  4),
	--
	(15, 19,  1),
	(2,  19,  2),
	(2,  19,  3),
	(1,  19,  4),
	--
	(15, 20,  1),
	(2,  20,  2),
	(2,  20,  3),
	(1,  20,  4),
	--
	(15, 21,  1),
	(2,  21,  2),
	(2,  21,  3),
	(1,  21,  4),
	--
	(15, 22,  1),
	(2,  22,  2),
	(2,  22,  3),
	(1,  22,  4),
	--
	(15, 23,  1),
	(2,  23,  2),
	(2,  23,  3),
	(1,  23,  4),
	--
	(15, 24,  1),
	(2,  24,  2),
	(2,  24,  3),
	(1,  24,  4);

COMMIT;

-- TODO : rename to examquestionstudentanswer
INSERT INTO answer (id_student, id_question, id_exam, points, comment, answer) VALUES
	-- STUDENT 1
	(1, 1, 1, 13, 	"Super commentaire qui t'aidera à mieux comprendre ton erreur", "l'audace c'est ça"),
	(1, 2, 1, 0, 	'', '1'),
	(1, 3, 1, 1, 	'un super autre commentaire', '1;2'),
	(1, 4, 1, 1, 	'', '1;3'),
	-- STUDENT 2
	(2, 1, 1, 7, 	'Pas bien de copier sur son voisin', "l'audace c'est ça"),
	(2, 2, 1, 1, 	'', '0'),
	(2, 3, 1, 2, 	'', '1;2'),
	(2, 4, 1, 1, 	'', '1;3'),
	-- STUDENT 3
	(3, 1, 1, 15, 	'', "l'audace c'est exactement pas ça du tout"),
	(3, 2, 1, 0, 	'', '2'),
	(3, 3, 1, 1, 	'', '1;3'),
	(3, 4, 1, 0.5, 	'', '1'),
	-- STUDENT 4
	(4, 1, 1, 0, 	'', ''),
	(4, 2, 1, 0, 	'', ''),
	(4, 3, 1, 0, 	'', ''),
	(4, 4, 1, 0, 	'', ''),

	-- STUDENT 1
	(1, 1, 2, NULL, '', "l'audace c'est ça"),
	(1, 2, 2, NULL, '', '1'),
	(1, 3, 2, NULL, '', '1;2'),
	(1, 4, 2, NULL, '', '1;3'),
	-- STUDENT 2
	(2, 1, 2, NULL, '', "l'audace c'est ça"),
	(2, 2, 2, NULL, '', '0'),
	(2, 3, 2, NULL, '', '1;2'),
	(2, 4, 2, NULL, '', '1;3'),
	-- STUDENT 3
	(3, 1, 2, NULL, '', "l'audace c'est exactement pas ça du tout"),
	(3, 2, 2, NULL, '', '2'),
	(3, 3, 2, NULL, '', '1;3'),
	(3, 4, 2, NULL, '', '1'),
	-- STUDENT 4
	(4, 1, 2, NULL, '', ''),
	(4, 2, 2, NULL, '', ''),
	(4, 3, 2, NULL, '', ''),
	(4, 4, 2, NULL, '', '');