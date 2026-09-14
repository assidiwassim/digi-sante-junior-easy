<?php

namespace App\Tests\Service;

use App\Entity\DouleurZone;
use App\Entity\Enfant;
use App\Entity\JournalEntree;
use App\Service\ConseilService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Règles du moteur de conseils.
 *
 * Les journaux sont créés en mémoire (jamais enregistrés) ; le service lit les
 * contenus des fixtures dans la base de test.
 */
class ConseilServiceTest extends KernelTestCase
{
    public function testJourneeSansProblemeDonneUnBravo(): void
    {
        $conseils = $this->getConseils(ecranTv: 60);

        $this->assertCount(1, $conseils);
        $this->assertSame('Super journée !', $conseils[0]['titre']);
        $this->assertSame('vert', $conseils[0]['couleur']);
    }

    public function testPlusDeDeuxHeuresDonneLe202020(): void
    {
        // 150 min : plus de 2 h, mais sous la limite de 180 min
        $conseils = $this->getConseils(ecranTv: 150, limite: 180);

        $this->assertSame(['Repose tes yeux avec le 20-20-20'], array_column($conseils, 'titre'));
        $this->assertSame('La règle du 20-20-20', $conseils[0]['contenu']->getTitre());
    }

    public function testLimiteDepasseeRemplaceLe202020(): void
    {
        // 150 min : plus de 2 h ET au-dessus de la limite de 90 min -> un seul conseil
        $conseils = $this->getConseils(ecranTv: 150, limite: 90);

        $this->assertSame(['Tu as dépassé ta limite d\'écran'], array_column($conseils, 'titre'));
        $this->assertStringContainsString('2 h 30', $conseils[0]['message']);
        $this->assertStringContainsString('1 h 30', $conseils[0]['message']);
    }

    public function testDouleurAuCouAPartirDe3(): void
    {
        $this->assertSame([], $this->titresContenant('Détends', $this->getConseils(douleurs: ['cou' => 2])));
        $this->assertCount(1, $this->titresContenant('Détends', $this->getConseils(douleurs: ['epaule' => 3])));
    }

    public function testDouleurAuxYeuxDonneLeYogaDesYeux(): void
    {
        $conseils = $this->getConseils(douleurs: ['yeux' => 1]);

        $this->assertSame(['Un peu de yoga des yeux'], array_column($conseils, 'titre'));
        $this->assertSame('Le yoga des yeux', $conseils[0]['contenu']->getTitre());
    }

    /** @param array<string, int> $douleurs */
    private function getConseils(int $ecranTv = 0, int $limite = 120, array $douleurs = []): array
    {
        $enfant = new Enfant();
        $enfant->setMaxMinutesJour($limite);

        $journal = new JournalEntree();
        $journal->setEnfant($enfant);
        $journal->setEcranTv($ecranTv);

        foreach ($douleurs as $zone => $intensite) {
            $journal->addDouleur(new DouleurZone($zone, $intensite));
        }

        return static::getContainer()->get(ConseilService::class)->getConseils($journal);
    }

    private function titresContenant(string $texte, array $conseils): array
    {
        return array_filter(array_column($conseils, 'titre'), fn (string $titre) => str_contains($titre, $texte));
    }
}
