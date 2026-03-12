INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$xtpUcwdjGxZgA0v77PPKK.aT8SSXUoSIdc1rYIB/ls4ATkg1upwfK');

INSERT INTO `characters` (`id`, `name`, `slug`) VALUES
(1, 'Amanda De Santa', 'amanda-de-santa'),
(2, 'Tracey De Santa', 'tracey-de-santa'),
(3, 'Sapphire', 'sapphire');

INSERT INTO `photos` (`character_id`, `image_url`, `source_url`, `caption`) VALUES
(1, 'https://static.wikia.nocookie.net/gtawiki/images/a/a5/AmandaDeSanta-GTAV.png', 'https://gta.fandom.com/wiki/Amanda_De_Santa', 'Amanda De Santa - GTA V'),
(1, 'https://static.wikia.nocookie.net/gtawiki/images/4/47/AmandaDeSanta-GTAVe.png', 'https://gta.fandom.com/wiki/Amanda_De_Santa', 'Amanda - Enhanced Edition'),
(1, 'https://static.wikia.nocookie.net/gtawiki/images/9/9a/AmandaDeSanta-GTAV-trailer.png', NULL, 'Amanda - Trailer Screenshot'),
(2, 'https://static.wikia.nocookie.net/gtawiki/images/5/5b/TraceyDeSanta-GTAV.png', 'https://gta.fandom.com/wiki/Tracey_De_Santa', 'Tracey De Santa - GTA V'),
(2, 'https://static.wikia.nocookie.net/gtawiki/images/3/3c/TraceyDeSanta-GTAVe.png', 'https://gta.fandom.com/wiki/Tracey_De_Santa', 'Tracey - Enhanced Edition'),
(2, 'https://static.wikia.nocookie.net/gtawiki/images/c/c2/TraceyDeSanta-GTAV-artwork.png', NULL, 'Tracey - Artwork'),
(3, 'https://static.wikia.nocookie.net/gtawiki/images/0/0a/Sapphire-GTAV.png', 'https://gta.fandom.com/wiki/Sapphire', 'Sapphire - GTA V'),
(3, 'https://static.wikia.nocookie.net/gtawiki/images/e/e4/Sapphire-GTAV-2.png', 'https://gta.fandom.com/wiki/Sapphire', 'Sapphire - Scene 2'),
(3, 'https://static.wikia.nocookie.net/gtawiki/images/d/d3/Sapphire-GTAV-3.png', NULL, 'Sapphire - Scene 3');
