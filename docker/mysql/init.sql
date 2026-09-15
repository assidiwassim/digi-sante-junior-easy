-- Exécuté une seule fois, à la toute première création du volume MySQL.
--
-- L'image MySQL ne donne à l'utilisateur `digisante` que les droits sur la base
-- `digisante_junior`. Les tests utilisent une seconde base, `digisante_junior_test` :
-- on autorise donc l'utilisateur à gérer toutes les bases qui commencent par
-- `digisante_junior`.
GRANT ALL PRIVILEGES ON `digisante\_junior%`.* TO 'digisante'@'%';
FLUSH PRIVILEGES;
