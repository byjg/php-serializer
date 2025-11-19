---
sidebar_position: 11
---

# Integration Examples

This guide provides practical integration examples for using the PHP Serializer library with popular frameworks, ORMs, and libraries.

## Table of Contents

- [ORM Integration](#orm-integration)
- [Framework Integration](#framework-integration)
- [API Integration](#api-integration)
- [Message Queue Integration](#message-queue-integration)
- [Cache Integration](#cache-integration)
- [GraphQL Integration](#graphql-integration)

## ORM Integration

### Doctrine ORM

```php
use Doctrine\ORM\EntityManagerInterface;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class UserService {
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    // Get all users as array
    public function getUsersAsArray(): array {
        $users = $this->em->getRepository(User::class)->findAll();

        return array_map(
            fn($user) => Serialize::from($user)
                ->withIgnoreProperties(['password', 'tokens'])
                ->withStopAtFirstLevel()
                ->toArray(),
            $users
        );
    }

    // Export users to JSON
    public function exportUsersToJson(): string {
        $users = $this->getUsersAsArray();
        return Serialize::from($users)->toJson();
    }

    // Create user from JSON
    public function createUserFromJson(string $json): User {
        $data = json_decode($json, true);

        $user = new User();
        ObjectCopy::copy($data, $user);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    // Update user from array
    public function updateUser(int $id, array $data): User {
        $user = $this->em->find(User::class, $id);

        if (!$user) {
            throw new \Exception('User not found');
        }

        ObjectCopy::copy($data, $user);

        $this->em->flush();

        return $user;
    }
}
```

### Eloquent ORM (Laravel)

```php
use Illuminate\Database\Eloquent\Collection;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class UserTransformer {
    // Transform Eloquent collection to array
    public function transformCollection(Collection $users): array {
        return $users->map(function ($user) {
            return Serialize::from($user)
                ->withIgnoreProperties(['password', 'remember_token'])
                ->toArray();
        })->toArray();
    }

    // Transform to JSON
    public function toJson(Collection $users): string {
        $transformed = $this->transformCollection($users);
        return Serialize::from($transformed)->toJson();
    }

    // Create model from array
    public function fromArray(array $data): User {
        $user = new User();
        ObjectCopy::copy($data, $user);
        return $user;
    }

    // Export to CSV
    public function exportToCsv(Collection $users): string {
        $data = $this->transformCollection($users);
        return Serialize::from($data)->toCsv();
    }
}

// Usage in Laravel Controller
class UserController extends Controller {
    private UserTransformer $transformer;

    public function index() {
        $users = User::all();
        return response()->json(
            Serialize::from($users)
                ->withIgnoreProperties(['password'])
                ->toArray()
        );
    }

    public function export() {
        $users = User::all();
        $csv = (new UserTransformer())->exportToCsv($users);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="users.csv"');
    }
}
```

## Framework Integration

### Symfony

```php
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class UserController extends AbstractController {
    #[Route('/api/users/{id}', methods: ['GET'])]
    public function getUser(int $id, UserRepository $repository): JsonResponse {
        $user = $repository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $data = Serialize::from($user)
            ->withIgnoreProperties(['password', 'apiKey'])
            ->toArray();

        return new JsonResponse($data);
    }

    #[Route('/api/users', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        ObjectCopy::copy($data, $user);

        $em->persist($user);
        $em->flush();

        return new JsonResponse(
            Serialize::from($user)->toArray(),
            201
        );
    }

    #[Route('/api/users/export', methods: ['GET'])]
    public function exportUsers(UserRepository $repository): Response {
        $users = $repository->findAll();

        $csv = Serialize::from($users)
            ->withIgnoreProperties(['password'])
            ->toCsv();

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"'
        ]);
    }
}
```

### Laravel API Resources

```php
use Illuminate\Http\Resources\Json\JsonResource;
use ByJG\Serializer\Serialize;

class UserResource extends JsonResource {
    public function toArray($request) {
        return Serialize::from($this->resource)
            ->withIgnoreProperties(['password', 'remember_token'])
            ->toArray();
    }
}

// Controller usage
class UserController extends Controller {
    public function show(User $user) {
        return new UserResource($user);
    }

    public function index() {
        $users = User::paginate(15);
        return UserResource::collection($users);
    }
}
```

## API Integration

### REST API Client

```php
use GuzzleHttp\Client;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;

class ApiClient {
    private Client $httpClient;
    private string $baseUrl;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->httpClient = new Client(['base_uri' => $baseUrl]);
    }

    // GET request - deserialize response
    public function getUser(int $id): User {
        $response = $this->httpClient->get("/users/{$id}");
        $data = json_decode($response->getBody(), true);

        // API returns snake_case, convert to camelCase
        $user = new User();
        ObjectCopy::copy($data, $user, new SnakeToCamelCase());

        return $user;
    }

    // POST request - serialize request body
    public function createUser(User $user): User {
        $data = Serialize::from($user)
            ->withIgnoreProperties(['id', 'createdAt', 'updatedAt'])
            ->toArray();

        $response = $this->httpClient->post('/users', [
            'json' => $data
        ]);

        $responseData = json_decode($response->getBody(), true);

        ObjectCopy::copy($responseData, $user, new SnakeToCamelCase());

        return $user;
    }

    // Batch export
    public function exportUsers(array $filters = []): string {
        $response = $this->httpClient->get('/users', [
            'query' => $filters
        ]);

        $users = json_decode($response->getBody(), true);

        return Serialize::from($users)->toCsv();
    }
}
```

### REST API Server (Generic)

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ByJG\Serializer\Serialize;

class UserApiHandler {
    // JSON response helper
    private function jsonResponse($data, int $status = 200): ResponseInterface {
        $json = Serialize::from($data)
            ->withIgnoreProperties(['password'])
            ->toJson();

        $response = new Response($status);
        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    // List users
    public function index(ServerRequestInterface $request): ResponseInterface {
        $users = $this->userRepository->findAll();

        return $this->jsonResponse($users);
    }

    // Create user
    public function create(ServerRequestInterface $request): ResponseInterface {
        $data = json_decode($request->getBody()->getContents(), true);

        $user = new User();
        ObjectCopy::copy($data, $user);

        $this->userRepository->save($user);

        return $this->jsonResponse($user, 201);
    }

    // Export to different formats based on Accept header
    public function export(ServerRequestInterface $request): ResponseInterface {
        $users = $this->userRepository->findAll();
        $accept = $request->getHeaderLine('Accept');

        $data = array_map(
            fn($user) => Serialize::from($user)
                ->withIgnoreProperties(['password'])
                ->toArray(),
            $users
        );

        $response = new Response();

        switch ($accept) {
            case 'text/csv':
                $response->getBody()->write(Serialize::from($data)->toCsv());
                return $response->withHeader('Content-Type', 'text/csv');

            case 'application/xml':
                $response->getBody()->write(Serialize::from($data)->toXml());
                return $response->withHeader('Content-Type', 'application/xml');

            case 'application/json':
            default:
                $response->getBody()->write(Serialize::from($data)->toJson());
                return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
```

## Message Queue Integration

### RabbitMQ Integration

```php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class OrderEventPublisher {
    private AMQPStreamConnection $connection;

    public function __construct(AMQPStreamConnection $connection) {
        $this->connection = $connection;
    }

    // Publish order created event
    public function publishOrderCreated(Order $order): void {
        // Serialize order to JSON
        $orderData = Serialize::from($order)
            ->withIgnoreProperties(['internalNotes', 'costPrice'])
            ->toJson();

        $message = new AMQPMessage($orderData, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'timestamp' => time()
        ]);

        $channel = $this->connection->channel();
        $channel->basic_publish($message, 'orders', 'order.created');
    }

    // Consume and deserialize messages
    public function consumeOrderEvents(callable $handler): void {
        $channel = $this->connection->channel();

        $callback = function (AMQPMessage $msg) use ($handler) {
            $data = json_decode($msg->body, true);

            $order = new Order();
            ObjectCopy::copy($data, $order);

            $handler($order);

            $msg->ack();
        };

        $channel->basic_consume('order_queue', '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
```

### Redis Queue

```php
use Predis\Client as RedisClient;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class JobQueue {
    private RedisClient $redis;
    private string $queueName;

    public function __construct(RedisClient $redis, string $queueName = 'jobs') {
        $this->redis = $redis;
        $this->queueName = $queueName;
    }

    // Push job to queue
    public function push(Job $job): void {
        $serialized = Serialize::from($job)->toJson();
        $this->redis->rpush($this->queueName, [$serialized]);
    }

    // Pop job from queue
    public function pop(): ?Job {
        $data = $this->redis->lpop($this->queueName);

        if (!$data) {
            return null;
        }

        $jobData = json_decode($data, true);

        $job = new Job();
        ObjectCopy::copy($jobData, $job);

        return $job;
    }

    // Batch push
    public function pushBatch(array $jobs): void {
        $serialized = array_map(
            fn($job) => Serialize::from($job)->toJson(),
            $jobs
        );

        $this->redis->rpush($this->queueName, $serialized);
    }
}
```

## Cache Integration

### PSR-6 Cache Pool

```php
use Psr\Cache\CacheItemPoolInterface;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class UserCacheService {
    private CacheItemPoolInterface $cache;
    private UserRepository $repository;

    public function __construct(CacheItemPoolInterface $cache, UserRepository $repository) {
        $this->cache = $cache;
        $this->repository = $repository;
    }

    // Cache user data as array
    public function cacheUser(User $user): void {
        $cacheKey = "user_{$user->getId()}";

        // Serialize to array for caching
        $userData = Serialize::from($user)
            ->withIgnoreProperties(['password'])
            ->toArray();

        $item = $this->cache->getItem($cacheKey);
        $item->set($userData);
        $item->expiresAfter(3600); // 1 hour

        $this->cache->save($item);
    }

    // Get user from cache
    public function getUser(int $userId): ?User {
        $cacheKey = "user_{$userId}";
        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            // Cache miss - load from repository
            $user = $this->repository->find($userId);

            if ($user) {
                $this->cacheUser($user);
            }

            return $user;
        }

        // Deserialize from cache
        $user = new User();
        ObjectCopy::copy($item->get(), $user);

        return $user;
    }

    // Invalidate cache
    public function invalidateUser(int $userId): void {
        $cacheKey = "user_{$userId}";
        $this->cache->deleteItem($cacheKey);
    }
}
```

### PSR-16 Simple Cache

```php
use Psr\SimpleCache\CacheInterface;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\ObjectCopy;

class ConfigCache {
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache) {
        $this->cache = $cache;
    }

    // Cache configuration object
    public function cacheConfig(Config $config, int $ttl = 3600): void {
        $data = Serialize::from($config)->toArray();
        $this->cache->set('app_config', $data, $ttl);
    }

    // Get configuration from cache
    public function getConfig(): ?Config {
        $data = $this->cache->get('app_config');

        if ($data === null) {
            return null;
        }

        $config = new Config();
        ObjectCopy::copy($data, $config);

        return $config;
    }

    // Cache multiple items
    public function cacheMultiple(array $items, int $ttl = 3600): void {
        $serialized = [];

        foreach ($items as $key => $item) {
            $serialized[$key] = Serialize::from($item)->toArray();
        }

        $this->cache->setMultiple($serialized, $ttl);
    }
}
```

## GraphQL Integration

```php
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use ByJG\Serializer\Serialize;

class UserType extends ObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'User',
            'description' => 'A user in the system',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'The user ID'
                ],
                'name' => [
                    'type' => Type::string(),
                    'description' => 'The user name'
                ],
                'email' => [
                    'type' => Type::string(),
                    'description' => 'The user email'
                ],
            ],
            'resolveField' => function($user, $args, $context, $info) {
                // Serialize user object to array
                $data = Serialize::from($user)
                    ->withIgnoreProperties(['password'])
                    ->toArray();

                return $data[$info->fieldName] ?? null;
            }
        ]);
    }
}

// Mutation for creating users
class CreateUserMutation {
    public function resolve($root, $args, $context) {
        $user = new User();
        ObjectCopy::copy($args['input'], $user);

        // Save to database
        $context['repository']->save($user);

        return $user;
    }
}
```

## Related Components

- [Advanced Usage](advanced-usage.md) - Performance and security considerations
- [ByJG Ecosystem](byjg-ecosystem.md) - Integration with ByJG components
- [Property Handlers](propertyhandlers.md) - Data transformation
- [Formatters](formatters.md) - Output format customization
