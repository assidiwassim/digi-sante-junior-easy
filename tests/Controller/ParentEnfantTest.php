<?php

namespace App\Tests\Controller;

use App\Entity\Enfant;
use App\Repository\EnfantRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Gestion des enfants par le parent.
 */
class ParentEnfantTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $parent = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'parent@digisante.local']);
        $this->client->loginUser($parent);
    }

    public function testLeTableauDeBordAfficheLesEnfantsDuParent(): void
    {
        $this->client->request('GET', '/parent');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Léa Martin');
    }

    public function testCreationDUnEnfantEtDeSonCompte(): void
    {
        $this->client->request('GET', '/parent/enfants/nouveau');
        $this->client->submitForm('Créer le profil et le compte', [
            'enfant[prenom]' => 'Léa',
            'enfant[nom]' => 'Petit',
            'enfant[dateNaissance]' => (new \DateTimeImmutable('today -10 years'))->format('Y-m-d'),
            'enfant[avatar]' => 'panda',
            'enfant[maxMinutesJour]' => '90',
            'enfant[motDePasse]' => 'secret99',
        ]);

        $this->assertResponseRedirects('/parent/enfants');
        $this->client->followRedirect();
        // « lea » existe déjà : l'identifiant devient « lea2 »
        $this->assertSelectorTextContains('.alert-success', 'lea2');

        $compte = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'lea2']);
        $this->assertSame('Petit', $compte->getProfilEnfant()->getNom());
        $this->assertSame(90, $compte->getProfilEnfant()->getMaxMinutesJour());
    }

    public function testLesErreursDeSaisieSontAffichees(): void
    {
        $this->client->request('GET', '/parent/enfants/nouveau');
        $this->client->submitForm('Créer le profil et le compte', [
            'enfant[prenom]' => '',
            'enfant[nom]' => 'Petit',
            'enfant[dateNaissance]' => '2000-01-01',
            'enfant[avatar]' => 'panda',
            'enfant[maxMinutesJour]' => '90',
            'enfant[motDePasse]' => '123',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Le prénom est obligatoire.');
        $this->assertSelectorTextContains('body', 'réservée aux enfants de 8 à 14 ans');
        $this->assertSelectorTextContains('body', 'au moins 6 caractères');
    }

    public function testUnParentNePeutPasToucherAuxEnfantsDUnAutre(): void
    {
        $noah = $this->trouverEnfant('Noah');

        $this->client->request('GET', '/parent/enfants/'.$noah->getId().'/modifier');
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/parent/enfants/'.$noah->getId().'/supprimer');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuppressionDUnEnfantAvecSonCompte(): void
    {
        $tomId = $this->trouverEnfant('Tom')->getId();

        $crawler = $this->client->request('GET', '/parent/enfants');
        $formulaire = $crawler->filter('form[action="/parent/enfants/'.$tomId.'/supprimer"]')->form();
        $this->client->submit($formulaire);

        $this->assertResponseRedirects('/parent/enfants');
        $this->assertNull(static::getContainer()->get(EnfantRepository::class)->find($tomId));
        $this->assertNull(static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'tom']));
    }

    public function testSuppressionRefuseeSansJetonCsrf(): void
    {
        $tom = $this->trouverEnfant('Tom');

        $this->client->request('POST', '/parent/enfants/'.$tom->getId().'/supprimer', ['_token' => 'faux']);

        $this->assertResponseStatusCodeSame(403);
    }

    private function trouverEnfant(string $prenom): Enfant
    {
        return static::getContainer()->get(EnfantRepository::class)->findOneBy(['prenom' => $prenom]);
    }
}
