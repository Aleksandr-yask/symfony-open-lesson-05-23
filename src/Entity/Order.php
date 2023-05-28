<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
    private Customer $customer;

    #[ORM\ManyToMany(targetEntity: 'Product', mappedBy: 'followers')]
    private Collection $products;

    #[ORM\ManyToOne(targetEntity: Restaurant::class)]
    #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id')]
    private Restaurant $restaurant;

    public function getId(): ?int
    {
        return $this->id;
    }
}
