<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190502190208 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE purchase_token (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, token VARCHAR(32) NOT NULL, transaction_id VARCHAR(32) NOT NULL, price INT UNSIGNED NOT NULL, is_success TINYINT(1) DEFAULT NULL, success_url VARCHAR(255) NOT NULL, failure_url VARCHAR(255) NOT NULL, webhook_url VARCHAR(255) NOT NULL, is_purchased TINYINT(1) NOT NULL, date_created DATETIME NOT NULL, INDEX IDX_8BFC22115F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE merchant (id INT UNSIGNED AUTO_INCREMENT NOT NULL, password VARCHAR(64) DEFAULT NULL, date_created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE purchase_token');
        $this->addSql('DROP TABLE merchant');
    }
}
