<?php

namespace App\Controller;

use App\Entity\FirstName;
use App\Entity\DrawHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WheelController extends AbstractController
{
    #[Route('/', name: 'wheel')]
    public function index(EntityManagerInterface $em): Response
    {
        // Prénoms non tirés
        $firstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => false]);
        //dd($firstNames);

        // Prénoms déjà tirés
        $drawnFirstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => true]);
        //dd($drawnFirstNames);
        // Récupérer l'historique des tirages
        $history = $em->getRepository(DrawHistory::class)->findBy([], ['drawDate' => 'DESC'],5);

        return $this->render('wheel/index.html.twig', [
            'firstNames' => $firstNames,
            'drawnFirstNames' => $drawnFirstNames,
            'history' => $history,
        ]);
    }


    #[Route('/spin', name: 'spin_wheel')]
    public function spin(EntityManagerInterface $em): JsonResponse
    {
        // Récupérer tous les prénoms non tirés
        $firstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => false]);

        if (empty($firstNames)) {
            return new JsonResponse(['message' => 'No more names available'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Choisir un prénom aléatoirement
        $randomFirstName = $firstNames[array_rand($firstNames)];
        $randomFirstName->setDrawn(true);

        // Ajouter à l'historique
        $history = new DrawHistory();
        $history->setName($randomFirstName->getName());
        $history->setDrawDate(new \DateTime());

        $em->persist($randomFirstName);
        $em->persist($history);
        $em->flush();

        return new JsonResponse(['name' => $randomFirstName->getName()]);
    }

    #[Route('/add-name', name: 'add_name', methods: ['POST'])]
    public function addName(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les données JSON envoyées via fetch
        $data = json_decode($request->getContent(), true);  // Décoder les données JSON

        $name = $data['name'] ?? null;  // Récupérer le prénom depuis le JSON

        if ($name) {
            $firstName = new FirstName();
            $firstName->setName($name);
            $firstName->setDrawn(false); // Par défaut, ce prénom n'est pas encore tiré
            $firstName->setCreatedAt(new \DateTimeImmutable());

            $em->persist($firstName);
            $em->flush();

            return new JsonResponse(['success' => true], Response::HTTP_CREATED);
        }

        return new JsonResponse(['success' => false, 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
    }


    #[Route('/reAddName/{id}', name: 're_add_name', methods: ['POST'])]
    public function reAddName($id, EntityManagerInterface $em): JsonResponse
    {
        $firstName = $em->getRepository(FirstName::class)->find($id);
        if ($firstName) {
            $firstName->setDrawn(false);
            $em->flush();
            return new JsonResponse(['success' => true]);
        }
        return new JsonResponse(['success' => false], JsonResponse::HTTP_BAD_REQUEST);
    }
    

    #[Route('/reAddNameAll', name: 're_add_name_all', methods: ['POST'])]
    public function reAddNameAll(EntityManagerInterface $em): JsonResponse
    {
        $firstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => true]);
        foreach ($firstNames as $firstName) {
            $firstName->setDrawn(false);  // Réintégrer tous les prénoms
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
