<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\CreateOrderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route(path: '/test')]
    public function hello(Request $request)
    {
        $rep = $this->entityManager->getRepository(Order::class);
        $form = $this->formFactory->create(CreateOrderType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
        }

        return $this->render('create_order.html.twig', [
            'list' => $rep->findAll(),
            'form' => $form->createView(),
        ]);
    }
}
