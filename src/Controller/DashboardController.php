<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Repository\EmployeeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    // Route name starts with "private_area_" so the Tenant listener enables the filter.
    #[Route('/dashboard', name: 'private_area_dashboard')]
    public function index(DocumentRepository $documents, EmployeeRepository $employees): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'documents' => $documents->findAll(),
            'employees' => $employees->findAll(),
        ]);
    }
}
