<?php

namespace App\Tests\Controller;

use App\Repository\JournalEntreeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Journal de l'enfant en 2 étapes, puis écran des conseils.
 */
class JournalTest extends WebTestCase
{
    public function testParcoursCompletDuJournal(): void
    {
        $client = static::createClient();
        $tom = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'tom']);
        $client->loginUser($tom);

        // Les fixtures ont déjà rempli la journée de Tom : on la supprime.
        // (La base est remise en état automatiquement après le test.)
        $journalRepository = static::getContainer()->get(JournalEntreeRepository::class);
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($journalRepository->findAujourdhui($tom->getProfilEnfant()));
        $entityManager->flush();

        // Étape 1 : les écrans
        $client->request('GET', '/enfant/journal');
        $this->assertResponseRedirects('/enfant/journal/etape/1');
        $client->followRedirect();

        $client->submitForm('Suivant : mon corps →', [
            'journal_ecrans[ecranTv]' => '60',
            'journal_ecrans[ecranOrdinateur]' => '45',
            'journal_ecrans[ecranSmartphone]' => '30',
            'journal_ecrans[ecranTablette]' => '0',
            'journal_ecrans[ecranConsole]' => '15',
            'journal_ecrans[ecranAutre]' => '0',
        ]);
        $this->assertResponseRedirects('/enfant/journal/etape/2');
        $client->followRedirect();

        // Étape 2 : les douleurs. « genou » (zone inconnue) et « dos: 9 »
        // (intensité invalide) doivent être ignorés par le contrôleur.
        $client->submitForm('🎉 Terminer mon journal', [
            'journal_douleurs[douleurs]' => json_encode(['cou' => 4, 'yeux' => 2, 'genou' => 3, 'dos' => 9]),
        ]);
        $this->assertResponseRedirects('/enfant/journal/conseils');
        $client->followRedirect();

        // Écran des conseils
        $this->assertSelectorTextContains('.stat-valeur', '2 h 30');
        $this->assertSelectorTextContains('body', 'Tu as dépassé ta limite d\'écran');
        $this->assertSelectorTextContains('body', 'Détends ton cou et tes épaules');
        $this->assertSelectorTextContains('body', 'Un peu de yoga des yeux');

        // En base : un journal, avec seulement les 2 douleurs valides
        $entityManager->clear();
        $journal = $journalRepository->findAujourdhui($tom->getProfilEnfant());
        $this->assertSame(150, $journal->getTotalEcran());
        $this->assertCount(2, $journal->getDouleurs());

        // Un deuxième journal le même jour est impossible
        $client->request('GET', '/enfant/journal/etape/1');
        $this->assertResponseRedirects('/enfant/journal/conseils');
    }

    public function testLEtape2NecessiteLEtape1(): void
    {
        $client = static::createClient();
        $tom = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'tom']);
        $client->loginUser($tom);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove(static::getContainer()->get(JournalEntreeRepository::class)->findAujourdhui($tom->getProfilEnfant()));
        $entityManager->flush();

        $client->request('GET', '/enfant/journal/etape/2');

        $this->assertResponseRedirects('/enfant/journal/etape/1');
    }
}
