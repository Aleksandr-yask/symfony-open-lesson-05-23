### Установка скелета
Документация: https://symfony.com/doc/current/setup.html

1. Откройте консоль и установите симфони скелетон 
    ```bash
        composer create-project symfony/skeleton:"6.2.*" my_project_directory
        cd my_project_directory
        composer require webapp
   ```
2. Внутри дериктории проекта выполните ```symfony server:start``` если вы используете symfony cli или внутри папки public `php -S 127.0.0.1:8000`
3. Перейдите в браузере по адрессу ```http://127.0.0.1:8000``` и проверьте работоспособность

### Докер
1. Добавьте файл docker-compose с конфигурацией
   ```yaml
   version: '3.7'
   
   services:
      php-fpm:
         build: docker
         container_name: 'phps'
         ports:
            - '9000:9000'
         volumes:
            - ./:/app
         working_dir: /app
   
      nginx:
         image: nginx
         container_name: 'nginxs'
         working_dir: /app
         ports:
            - '8080:80'
         volumes:
            - ./:/app
            - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
   
      postgres:
         image: postgres:15
         ports:
            - 15432:5432
         container_name: 'postgresqls'
         working_dir: /app
         restart: always
         environment:
            POSTGRES_DB: 'twitter'
            POSTGRES_USER: 'user'
            POSTGRES_PASSWORD: 'password'
         volumes:
            - dump:/app/dump
            - postgresql:/var/lib/postgresql/data
   
   volumes:
      dump:
      postgresql:
   ```
2. Создайте папку docker и добавьте туда Dockerfile и nginx.conf
   ```dockerfile
   FROM php:8.1-fpm-alpine
   
   # Install dev dependencies
   RUN apk update \
       && apk upgrade --available \
       && apk add --virtual build-deps \
           autoconf \
           build-base \
           icu-dev \
           libevent-dev \
           openssl-dev \
           zlib-dev \
           libzip \
           libzip-dev \
           zlib \
           zlib-dev \
           bzip2 \
           git \
           libpng \
           libpng-dev \
           libjpeg \
           libjpeg-turbo-dev \
           libwebp-dev \
           freetype \
           freetype-dev \
           postgresql-dev \
           curl \
           wget \
           bash \
           libmemcached-dev
   
   # Install Composer
   RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
   
   # Install PHP extensions
   RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
   RUN docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) \
       intl \
       gd \
       bcmath \
       pdo_pgsql \
       sockets \
       zip \
       pcntl
   RUN pecl channel-update pecl.php.net \
       && pecl install -o -f \
           redis \
           event \
           memcached \
       && rm -rf /tmp/pear \
       && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini \
       && echo "extension=event.so" > /usr/local/etc/php/conf.d/event.ini \
       && echo "extension=memcached.so" > /usr/local/etc/php/conf.d/memcached.ini
   ```
   ```text
   server {
       listen 80;
   
       server_name localhost;
       error_log  /var/log/nginx/error.log;
       access_log /var/log/nginx/access.log;
       root /app/public;
   
       rewrite ^/index\.php/?(.*)$ /$1 permanent;
   
       try_files $uri @rewriteapp;
   
       location @rewriteapp {
           rewrite ^(.*)$ /index.php/$1 last;
       }
   
       # Deny all . files
       location ~ /\. {
           deny all;
       }

       location ~ ^/index\.php(/|$) {
           fastcgi_split_path_info ^(.+\.php)(/.*)$;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           fastcgi_param PATH_INFO $fastcgi_path_info;
           fastcgi_index index.php;
           send_timeout 1800;
           fastcgi_read_timeout 1800;
           fastcgi_pass phps:9000;
       }
   }
   ```
3. Выполните docker-compose up --build и проверьте результат на странице http://localhost:8080/

### Установка необходимых бандлов
Зайдите в докер командой `docker exec -it php bash` и установите необходимые бандлы
```bash
composer require symfony/twig-bundle
composer require symfony/webpack-encore-bundle
```

### Подготовка среды для форм
1. src/Entity/Customer.php 
   ```php
   <?php
   
   namespace App\Entity;
   
   use App\Repository\CustomerRepository;
   use DateTime;
   use Doctrine\ORM\Mapping as ORM;

   #[ORM\Entity(repositoryClass: CustomerRepository::class)]
   class Customer
   {
   #[ORM\Id]
   #[ORM\GeneratedValue]
   #[ORM\Column]
   private ?int $id = null;
   
       #[ORM\Column(name: 'name', nullable: false)]
       private string $name;
   
       #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
       private DateTime $createdAt;
   
       public function __construct()
       {
           $this->createdAt = new DateTime();
       }
   
       public function getId(): ?int
       {
           return $this->id;
       }
   
       public function getName(): string
       {
           return $this->name;
       }
   
       public function setName(string $name): void
       {
           $this->name = $name;
       }
   
       public function getCreatedAt(): DateTime
       {
           return $this->createdAt;
       }
   
       public function setCreatedAt(DateTime $createdAt): void
       {
           $this->createdAt = $createdAt;
       }
   
       public function __toString(): string
       {
           return $this->name;
       }
   }
   ```
2. src/Entity/Order.php
   ```php
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
   
       #[ORM\ManyToOne(targetEntity: Product::class)]
       #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
       private Product $product;
   
       #[ORM\ManyToOne(targetEntity: Restaurant::class)]
       #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id')]
       private Restaurant $restaurant;
   
       public function getId(): ?int
       {
           return $this->id;
       }
   
       /**
        * @return Customer
        */
       public function getCustomer(): Customer
       {
           return $this->customer;
       }
   
       /**
        * @param Customer $customer
        */
       public function setCustomer(Customer $customer): void
       {
           $this->customer = $customer;
       }
   
       /**
        * @return Product
        */
       public function getProduct(): Product
       {
           return $this->product;
       }
   
       /**
        * @param Product $product
        */
       public function setProduct(Product $product): void
       {
           $this->product = $product;
       }
   
       /**
        * @return Restaurant
        */
       public function getRestaurant(): Restaurant
       {
           return $this->restaurant;
       }
   
       /**
        * @param Restaurant $restaurant
        */
       public function setRestaurant(Restaurant $restaurant): void
       {
           $this->restaurant = $restaurant;
       }
   }
   ```
3. src/Entity/Product.php
   ```php
   <?php
   
   namespace App\Entity;
   
   use App\Repository\ProductRepository;
   use Doctrine\ORM\Mapping as ORM;
   
   #[ORM\Entity(repositoryClass: ProductRepository::class)]
   class Product
   {
       #[ORM\Id]
       #[ORM\GeneratedValue]
       #[ORM\Column]
       private ?int $id = null;
   
       #[ORM\Column(name: 'name', nullable: false)]
       private string $name;
   
       public function getId(): ?int
       {
           return $this->id;
       }
   
       public function getName(): string
       {
           return $this->name;
       }
   
       public function setName(string $name): void
       {
           $this->name = $name;
       }
   
       public function __toString(): string
       {
           return $this->name;
       }
   }
   ```
4. src/Entity/Restaurant.php
   ```php
   <?php
   
   namespace App\Entity;
   
   use App\Repository\RestaurantRepository;
   use Doctrine\ORM\Mapping as ORM;
   
   #[ORM\Entity(repositoryClass: RestaurantRepository::class)]
   class Restaurant
   {
       #[ORM\Id]
       #[ORM\GeneratedValue]
       #[ORM\Column]
       private ?int $id = null;
   
       #[ORM\Column(name: 'name', nullable: false)]
       private string $name;
   
       public function getId(): ?int
       {
           return $this->id;
       }
   
       public function getName(): string
       {
           return $this->name;
       }
   
       public function setName(string $name): void
       {
           $this->name = $name;
       }
   }   
   ```
5. И репозитории для них: 

   src/Repository/CustomerRepository.php
   ```php
   <?php
   
   namespace App\Repository;
   
   use App\Entity\Customer;
   use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
   use Doctrine\Persistence\ManagerRegistry;
   
   /**
   * @extends ServiceEntityRepository<Customer>
   *
   * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
   * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
   * @method Customer[]    findAll()
   * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
     class CustomerRepository extends ServiceEntityRepository
     {
     public function __construct(ManagerRegistry $registry)
     {
         parent::__construct($registry, Customer::class);
     }
   
     public function save(Customer $entity, bool $flush = false): void
     {
         $this->getEntityManager()->persist($entity);
   
          if ($flush) {
              $this->getEntityManager()->flush();
          }
     }
   
     public function remove(Customer $entity, bool $flush = false): void
     {
        $this->getEntityManager()->remove($entity);
   
        if ($flush) {
            $this->getEntityManager()->flush();
        }
     }
   }
   ```
   src/Repository/OrderRepository.php
   ```php
   <?php
   
   namespace App\Repository;
   
   use App\Entity\Order;
   use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
   use Doctrine\Persistence\ManagerRegistry;
   
   /**
    * @extends ServiceEntityRepository<Order>
    *
    * @method Order|null find($id, $lockMode = null, $lockVersion = null)
    * @method Order|null findOneBy(array $criteria, array $orderBy = null)
    * @method Order[]    findAll()
    * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    */
   class OrderRepository extends ServiceEntityRepository
   {
       public function __construct(ManagerRegistry $registry)
       {
           parent::__construct($registry, Order::class);
       }
   
       public function save(Order $entity, bool $flush = false): void
       {
           $this->getEntityManager()->persist($entity);
   
           if ($flush) {
               $this->getEntityManager()->flush();
           }
       }
   
       public function remove(Order $entity, bool $flush = false): void
       {
           $this->getEntityManager()->remove($entity);
   
           if ($flush) {
               $this->getEntityManager()->flush();
           }
       }
   }
   ```
   src/Repository/ProductRepository.php
   ```php
   <?php
   
   namespace App\Repository;
   
   use App\Entity\Product;
   use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
   use Doctrine\Persistence\ManagerRegistry;
   
   /**
    * @extends ServiceEntityRepository<Product>
    *
    * @method Product|null find($id, $lockMode = null, $lockVersion = null)
    * @method Product|null findOneBy(array $criteria, array $orderBy = null)
    * @method Product[]    findAll()
    * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    */
   class ProductRepository extends ServiceEntityRepository
   {
       public function __construct(ManagerRegistry $registry)
       {
           parent::__construct($registry, Product::class);
       }
   
       public function save(Product $entity, bool $flush = false): void
       {
           $this->getEntityManager()->persist($entity);
   
           if ($flush) {
               $this->getEntityManager()->flush();
           }
       }
   
       public function remove(Product $entity, bool $flush = false): void
       {
           $this->getEntityManager()->remove($entity);
   
           if ($flush) {
               $this->getEntityManager()->flush();
           }
       }
   }
   ```
   src/Repository/RestaurantRepository.php
   ```php
   <?php
   
   namespace App\Repository;
   
   use App\Entity\Restaurant;
   use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
   use Doctrine\Persistence\ManagerRegistry;
   
   /**
    * @extends ServiceEntityRepository<Restaurant>
    *
    * @method Restaurant|null find($id, $lockMode = null, $lockVersion = null)
    * @method Restaurant|null findOneBy(array $criteria, array $orderBy = null)
    * @method Restaurant[]    findAll()
    * @method Restaurant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    */
   class RestaurantRepository extends ServiceEntityRepository
   {
       public function __construct(ManagerRegistry $registry)
       {
           parent::__construct($registry, Restaurant::class);
       }
   
       public function save(Restaurant $entity, bool $flush = false): void
       {
           $this->getEntityManager()->persist($entity);
   
           if ($flush) {
               $this->getEntityManager()->flush();
           }
       }
   
       public function remove(Restaurant $entity, bool $flush = false): void
       {
           $this->getEntityManager()->remove($entity);
   
           if ($flush) {
               $this->getEntityManager()->flush();
           }
       }
   }
   ```
6. Выполните в консоли контейнера `php bin/console do:mi:diff` и перейдите в папку migrations, там вы увидите новый файл с миграцией
7. Накатите его командой `php bin/console do:mi:mi` и проверьте результат в базе данных
8. Перейдите в файл templates/base.html.twig и добавьте boostrap
   ```html
   {% block head_js %}
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
   {% endblock %}
   ```
9. Установите sass и bootstrap
   ```bash
   apk add yarn
   yarn add sass-loader@^13.0.0 sass --dev
   yarn add bootstrap --dev
   ```
10. И включите sass в webpack.config.js
   ```javascript
    // enables Sass/SCSS support
   .enableSassLoader()
   ```
11. Переименуйте assets/styles/app.css -> assets/styles/app.scss и добавьте туда import бутстрапа
   ```scss
   @import "~bootstrap/scss/bootstrap";
   
   body {
       background-color: lightgray;
   }
   ```
12. Создайте контроллер в src/Controller/IndexController.php
   ```php
   <?php
   
   namespace App\Controller;
   
   use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\Routing\Annotation\Route;
   
   class IndexController extends AbstractController
   {
       #[Route(path: '/test')]
       public function hello(Request $request)
       {
           return $this->render('create_order.html.twig');
       }
   }
   ```
13. Создайте файл `templates/create_order.html.twig`
   ```text
   {% extends 'base.html.twig' %}
   
   {% block body %}
       <div class="container">
           <h2>Hello world</h2>
       </div>
   {% endblock %}
   ```
14. Запустите `yarn dev` и перейдите в браузер по адрессу `http://127.0.0.1:8000/test`, проверьте результат

### Создаем форму
1. Создайте класс src/Form/CreateOrderType.php
   ```php
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
   ```
2. Измените метод контроллера
   ```php
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
       public function test(Request $request)
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
   ```
3. Добавьте код отображения в twig
   ```text
   {% extends 'base.html.twig' %}

   {% block body %}
       <div class="container">
           <h2>Список заказов</h2>
           {{ form_start(form) }}
               {{ form_row(form.customer) }}
               {{ form_row(form.product) }}
               {{ form_row(form.restaurant) }}
               {{ form_row(form.submit) }}
           {{ form_end(form) }}
           <ul class="list-group">
               {% for item in list %}
                   <li class="list-group-item">
                       Заказчик: {{ item.customer.name }}, Продукт: {{ item.product.name }}, Ресторан: {{ item.restaurant.name }}
                   </li>
               {% endfor %}
           </ul>
       </div>
   {% endblock %}
   ```
4. Отредактируйте файл config/packages/twig.yaml
   ```yaml
   twig:
       default_path: '%kernel.project_dir%/templates'
       form_themes: ['bootstrap_5_layout.html.twig']
   when@test:
       twig:
           strict_variables: true
   ```
