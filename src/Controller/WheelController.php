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
    #[Route('/{team}', name: 'wheel', requirements: ['team' => 'sith|trooper|jedi|copilop'])]
    public function index(string $team, EntityManagerInterface $em): Response
    {
        // Prénoms non tirés spécifiques à l'équipe
        $firstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => false, 'team' => $team]);

        // Prénoms déjà tirés spécifiques à l'équipe
        $drawnFirstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => true, 'team' => $team]);

        // Récupérer l'historique des tirages pour l'équipe
        $history = $em->getRepository(DrawHistory::class)->findBy(['team' => $team], ['drawDate' => 'DESC'], 5);

        return $this->render('wheel/index.html.twig', [
            'firstNames' => $firstNames,
            'drawnFirstNames' => $drawnFirstNames,
            'history' => $history,
            'team' => $team,
        ]);
    }

    #[Route('/{team}/spin', name: 'spin_wheel', requirements: ['team' => 'sith|trooper|jedi|copilop'])]
    public function spin(string $team, EntityManagerInterface $em): JsonResponse
    {
        // Récupérer tous les prénoms non tirés pour une équipe spécifique
        $firstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => false, 'team' => $team]);

        if (empty($firstNames)) {
            return new JsonResponse(['message' => 'No more names available'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Choisir un prénom aléatoirement
        $randomFirstName = $firstNames[array_rand($firstNames)];
        $randomFirstName->setDrawn(true);

        // Ajouter à l'historique
        $history = new DrawHistory();
        $history->setName($randomFirstName->getName());
        $history->setTeam($team); // Ajouter l'équipe à l'historique
        $history->setDrawDate(new \DateTime());

        $em->persist($randomFirstName);
        $em->persist($history);
        $em->flush();

        return new JsonResponse(['name' => $randomFirstName->getName()]);
    }

    #[Route('/{team}/add-name', name: 'add_name', methods: ['POST'], requirements: ['team' => 'sith|trooper|jedi|copilop'])]
    public function addName(string $team, Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les données envoyées par la requête
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $team = $data['team'] ?? null;

        if ($name && $team) {
            $firstName = new FirstName();
            $firstName->setName($name);
            $firstName->setDrawn(false); // Par défaut, ce prénom n'est pas encore tiré
            $firstName->setTeam($team); // Assigner le prénom à l'équipe
            $firstName->setCreatedAt(new \DateTimeImmutable());

            $em->persist($firstName);
            $em->flush();

            return new JsonResponse(['success' => true], Response::HTTP_CREATED);
        }

        return new JsonResponse(['success' => false, 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{team}/reAddName/{id}', name: 're_add_name', methods: ['POST'], requirements: ['team' => 'sith|trooper|jedi|copilop'])]
    public function reAddName(string $team, $id, EntityManagerInterface $em): JsonResponse
    {
        $firstName = $em->getRepository(FirstName::class)->findOneBy(['id' => $id, 'team' => $team]);

        if ($firstName) {
            $firstName->setDrawn(false);
            $em->flush();
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/{team}/reAddNameAll', name: 're_add_name_all', methods: ['POST'], requirements: ['team' => 'sith|trooper|jedi|copilop'])]
    public function reAddNameAll(string $team, EntityManagerInterface $em): JsonResponse
    {
        $firstNames = $em->getRepository(FirstName::class)->findBy(['isDrawn' => true, 'team' => $team]);
        foreach ($firstNames as $firstName) {
            $firstName->setDrawn(false);  // Réintégrer tous les prénoms
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig', [
            'team' => null,  // ou une valeur par défaut comme 'home'
        ]);    
    }

    #[Route('/delete-name/{id}', name: 'delete_name', methods: ['POST'])]
    public function deleteName(int $id, EntityManagerInterface $em): JsonResponse
    {
        $firstName = $em->getRepository(FirstName::class)->find($id);
        
        if ($firstName) {
            $em->remove($firstName);
            $em->flush();
    
            return new JsonResponse(['success' => true]);
        }
    
        return new JsonResponse(['success' => false, 'message' => 'Prénom non trouvé.'], Response::HTTP_BAD_REQUEST);
    }
         

}
