<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230324131110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM admin');
        $this->addSql('DELETE FROM comment');
        $this->addSql('DELETE FROM conference');
        $this->addSql('ALTER TABLE admin CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C604B8382;');
        $this->addSql('ALTER TABLE comment CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE conference_id conference_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE conference CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C604B8382 FOREIGN KEY (conference_id) REFERENCES conference (id);');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM admin');
        $this->addSql('DELETE FROM comment');
        $this->addSql('DELETE FROM conference');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C604B8382;');
        $this->addSql('ALTER TABLE admin CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE comment CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE conference_id conference_id INT NOT NULL');
        $this->addSql('ALTER TABLE conference CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C604B8382 FOREIGN KEY (conference_id) REFERENCES conference (id);');
    }
}
