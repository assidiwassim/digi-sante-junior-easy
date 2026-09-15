<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Schéma initial : utilisateurs, enfants, journaux, douleurs et contenus.
 */
final class Version20260914214709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial de Digi-Santé Junior';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contenu_bien_etre (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, titre VARCHAR(160) NOT NULL, contenu LONGTEXT NOT NULL, url VARCHAR(500) DEFAULT NULL, declencheur VARCHAR(40) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE douleur_zone (id INT AUTO_INCREMENT NOT NULL, zone VARCHAR(20) NOT NULL, intensite INT NOT NULL, journal_entree_id INT NOT NULL, INDEX IDX_62C775121D166295 (journal_entree_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE enfant (id INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(80) NOT NULL, nom VARCHAR(80) NOT NULL, date_naissance DATE NOT NULL, avatar VARCHAR(20) NOT NULL, max_minutes_jour INT NOT NULL, parent_id INT NOT NULL, compte_id INT NOT NULL, UNIQUE INDEX UNIQ_34B70CA2F2C56620 (compte_id), INDEX IDX_34B70CA2727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE journal_entree (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, ecran_tv INT NOT NULL, ecran_ordinateur INT NOT NULL, ecran_smartphone INT NOT NULL, ecran_tablette INT NOT NULL, ecran_console INT NOT NULL, ecran_autre INT NOT NULL, enfant_id INT NOT NULL, UNIQUE INDEX un_journal_par_jour (enfant_id, date), INDEX IDX_82DB6543450D2529 (enfant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, username VARCHAR(60) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, pays VARCHAR(80) DEFAULT NULL, ville VARCHAR(80) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE douleur_zone ADD CONSTRAINT FK_62C775121D166295 FOREIGN KEY (journal_entree_id) REFERENCES journal_entree (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enfant ADD CONSTRAINT FK_34B70CA2727ACA70 FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enfant ADD CONSTRAINT FK_34B70CA2F2C56620 FOREIGN KEY (compte_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE journal_entree ADD CONSTRAINT FK_82DB6543450D2529 FOREIGN KEY (enfant_id) REFERENCES enfant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE douleur_zone DROP FOREIGN KEY FK_62C775121D166295');
        $this->addSql('ALTER TABLE enfant DROP FOREIGN KEY FK_34B70CA2727ACA70');
        $this->addSql('ALTER TABLE enfant DROP FOREIGN KEY FK_34B70CA2F2C56620');
        $this->addSql('ALTER TABLE journal_entree DROP FOREIGN KEY FK_82DB6543450D2529');
        $this->addSql('DROP TABLE contenu_bien_etre');
        $this->addSql('DROP TABLE douleur_zone');
        $this->addSql('DROP TABLE enfant');
        $this->addSql('DROP TABLE journal_entree');
        $this->addSql('DROP TABLE users');
    }
}
