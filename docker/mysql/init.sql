-- Exécuté une seule fois, à la toute première création du volume MySQL.
--
-- L'image MySQL ne donne à l'utilisateur `digisante` que les droits sur la base
-- `digisante_easy`. Les tests utilisent une seconde base, `digisante_easy_test` :
-- on autorise donc l'utilisateur à gérer toutes les bases qui commencent par
-- `digisante_easy`.
GRANT ALL PRIVILEGES ON `digisante\_easy%`.* TO 'digisante'@'%';
FLUSH PRIVILEGES;
