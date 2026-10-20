<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Notifications sans nom de personne : réécrit les textes déjà enregistrés avec les nouveaux modèles
 * (les nouvelles notifications sont déjà générées sans nom). Compatible MySQL 8 et MariaDB.
 */
final class Version20261020152841 extends AbstractMigration
{
    /** Réécritures [condition WHERE, nouvelle expression SQL du texte] — utilisées aussi par les tests */
    public const REWRITES = [
        // Nouvelle annonce : "T" publiée par X Y.  →  Nouvelle annonce : "T".
        ["text LIKE 'Nouvelle annonce : %' AND text LIKE '% publiée par %'", "REGEXP_REPLACE(text, ' publiée par .*$', '.')"],
        // Nouveau message de X sur "T".  →  Nouveau message sur "T".
        ["text LIKE 'Nouveau message de % sur \"%'", "REGEXP_REPLACE(text, '^Nouveau message de .* sur \"', 'Nouveau message sur \"')"],
        // Réponse de X sur "T".  →  Nouvelle réponse sur "T".
        ["text LIKE 'Réponse de % sur \"%'", "REGEXP_REPLACE(text, '^Réponse de .* sur \"', 'Nouvelle réponse sur \"')"],
        // "T" vous a été réservé par X Y. …  →  "T" vous a été réservé. …
        ["text LIKE '% vous a été réservé par %'", "REGEXP_REPLACE(text, ' vous a été réservé par [^.]*[.]', ' vous a été réservé.')"],
        // X a confirmé la récupération de "T". +10 crédits !  →  Récupération confirmée pour "T". +10 crédits !
        ["text LIKE '% a confirmé la récupération de %'", "REGEXP_REPLACE(text, '^.* a confirmé la récupération de ', 'Récupération confirmée pour ')"],
        // X a annulé votre réservation pour "T".  →  Réservation annulée par le donneur pour "T".
        ["text LIKE '% a annulé votre réservation pour %'", "REGEXP_REPLACE(text, '^.* a annulé votre réservation pour ', 'Réservation annulée par le donneur pour ')"],
        // X a annulé sa réservation pour "T" : …  →  Réservation annulée pour "T" : …
        ["text LIKE '% a annulé sa réservation pour %'", "REGEXP_REPLACE(text, '^.* a annulé sa réservation pour ', 'Réservation annulée pour ')"],
        // X n'a pas récupéré "T" dans le délai de 10 h : …  →  L'objet "T" n'a pas été récupéré dans le délai de 10 h : …
        ["type = 'reservation-expired' AND text LIKE '% pas récupéré \"%'", "REPLACE(REGEXP_REPLACE(text, '^.* pas récupéré \"', 'L''objet \"'), '\" dans le délai de', '\" n''a pas été récupéré dans le délai de')"],
        // [⚠️] X Y (compte banni) a envoyé …  →  Un compte banni a envoyé …
        ["text LIKE '%(compte banni) a envoyé%'", "REGEXP_REPLACE(text, '^.*[(]compte banni[)] a envoyé', 'Un compte banni a envoyé')"],
        // Nouveau message de support de X Y.  →  Nouveau message de support.
        ["text LIKE 'Nouveau message de support de %'", "'Nouveau message de support.'"],
        // ✅ Votre compte a été réactivé…  →  Votre compte a été réactivé… (emoji décoratif retiré)
        ["text LIKE '✅ %'", "REPLACE(text, '✅ ', '')"],
    ];

    public function getDescription(): string
    {
        return 'Notifications : retrait des noms de personnes dans les textes existants';
    }

    public function up(Schema $schema): void
    {
        foreach (self::REWRITES as [$where, $expr]) {
            $this->addSql("UPDATE notification SET text = $expr WHERE $where");
        }
    }

    public function down(Schema $schema): void
    {
        // Les noms retirés ne sont pas conservés : rien à restaurer.
        $this->warnIf(true, 'Les anciens textes de notification (avec noms) ne peuvent pas être restaurés.');
    }
}
