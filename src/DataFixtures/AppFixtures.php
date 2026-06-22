<?php

namespace App\DataFixtures;

use App\Entity\Document;
use App\Entity\Employee;
use App\Entity\Organization;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Two tenants with overlapping data, so a leak is obvious:
        // logged in as org A you must see A's rows only, never B's.
        foreach (['A', 'B'] as $key) {
            $org = new Organization();
            $org->setTitle("Org $key");
            $manager->persist($org);

            // one team per organization
            $team = new Team();
            $team->setOrganization($org);
            $manager->persist($team);

            // two documents per organization (scoped via organization_id)
            for ($i = 1; $i <= 2; $i++) {
                $document = new Document();
                $document->setOrganization($org);
                $manager->persist($document);
            }

            // two employees per team (scoped via team_id -> EXISTS subquery)
            for ($i = 1; $i <= 2; $i++) {
                $employee = new Employee();
                $employee->setTeam($team);
                $manager->persist($employee);
            }

            // one login user per organization; password is "password"
            $user = new User();
            $user->setEmail(sprintf('user@org-%s.test', strtolower($key)));
            $user->setOrganization($org);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
