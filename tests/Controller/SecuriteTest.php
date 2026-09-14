<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Pages publiques, connexion et protection des espaces par rôle.
 */
class SecuriteTest extends WebTestCase
{
    public function testLesPagesPubliquesSAffichent(): void
    {
        $client = static::createClient();

        foreach (['/', '/login', '/connexion-enfant', '/inscription'] as $url) {
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful($url);
        }
    }

    public function testUnVisiteurEstRedirigeVersLaConnexion(): void
    {
        $client = static::createClient();

        foreach (['/parent', '/enfant', '/admin'] as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/login');
        }
    }

    public function testConnexionParentAvecLeFormulaire(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'parent@digisante.local',
            '_password' => 'parent123',
        ]);

        // Après connexion : accueil, qui redirige vers le tableau de bord parent
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertResponseRedirects('/parent');
    }

    public function testConnexionEnfantRateeRevientSurLaPageEnfant(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion-enfant');

        $client->submitForm('C\'est parti ! 🚀', [
            '_username' => 'lea',
            '_password' => 'mauvais-mot-de-passe',
        ]);

        $this->assertResponseRedirects('/connexion-enfant');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Oups');
    }

    public function testChaqueRoleResteDansSonEspace(): void
    {
        $client = static::createClient();
        $users = static::getContainer()->get(UserRepository::class);

        $client->loginUser($users->findOneBy(['email' => 'parent@digisante.local']));
        $client->request('GET', '/admin/contenus');
        $this->assertResponseStatusCodeSame(403);
        $client->request('GET', '/enfant');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($users->findOneBy(['username' => 'lea']));
        $client->request('GET', '/parent');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($users->findOneBy(['email' => 'admin@digisante.local']));
        $client->request('GET', '/admin/contenus');
        $this->assertResponseIsSuccessful();
    }

    public function testInscriptionDUnParent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inscription');

        $client->submitForm('Créer mon compte', [
            'inscription[email]' => 'nouveau@exemple.fr',
            'inscription[plainPassword][first]' => 'secret123',
            'inscription[plainPassword][second]' => 'secret123',
            'inscription[conditions]' => true,
        ]);

        $this->assertResponseRedirects('/login');
        $parent = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'nouveau@exemple.fr']);
        $this->assertNotNull($parent);
        $this->assertTrue($parent->isParent());
        $this->assertNotSame('secret123', $parent->getPassword(), 'Le mot de passe doit être haché.');
    }
}
