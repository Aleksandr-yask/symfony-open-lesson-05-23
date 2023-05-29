<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateOrderType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $restaurantRepository = $this->entityManager->getRepository(Restaurant::class);
        $productsRepository = $this->entityManager->getRepository(Product::class);

        $builder->add('customer', ChoiceType::class, [
            'choices' => $customerRepository->findAll(),
            'choice_label' => function (?Customer $customer) {
                return $customer ? strtoupper($customer->getName()) : '';
            },
        ])
            ->add('product', ChoiceType::class, [
                'choices' => $productsRepository->findAll(),
                'choice_label' => function (?Product $product) {
                    return $product ? strtoupper($product->getName()) : '';
                },
            ])
            ->add('restaurant', ChoiceType::class, [
                'choices' => $restaurantRepository->findAll(),
                'choice_label' => function (?Restaurant $restaurant) {
                    return $restaurant ? strtoupper($restaurant->getName()) : '';
                },
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'empty_data' => new Order(),
        ]);
    }
}
