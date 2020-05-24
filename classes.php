<?php

/**
 * Interface Repository
 */
interface Repository
{
    public function create(Entity $entity) :bool;
    public function getByName(string $name) :Entity;
    public function update(Entity $entity) :bool;
}

/**
 * Class DbConfig
 * @property string $host
 * @property string $user
 * @property string $password
 */
class DbConfig
{
    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * DbConfig constructor.
     * @param string $host
     * @param string $user
     * @param string $password
     */
    public function __construct(string $host, string $user, string $password)
    {

    }
}

/**
 * Class DbConnection
 */
class DbConnection
{
    /**
     * @var DbConnection
     */
    private static $connect;

    /**
     * @var DbConfig
     */
    public $config;

    /**
     * DbConnection constructor.
     * @param DbConfig $config
     */
    private function __construct(DbConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param DbConfig $config
     * @return DbConnection
     */
    public static function getConnect(DbConfig $config) :DbConnection
    {
        if (static::$connect !== null) {
            return static::$connect;
        }

        return new static($config);
    }

    private function __clone()
    {

    }
}

/**
 * Class QueryBuilder
 */
class QueryBuilder
{
    /**
     * @var DbConnection
     */
    public static $db;

    /**
     * QueryBuilder constructor.
     */
    public function __construct()
    {
        $config = getenv();

        static::$db = DbConnection::getConnect(new DbConfig($config['host'], $config['user'], $config['pass']));
    }

    /**
     * @param string $sql
     * @return bool
     */
    public static function createBySql(string $sql) :bool
    {
        return self::$db->setSql($sql)->execute();
    }
}

/**
 * Class Entity
 */
class Entity
{
    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        $getter = 'get' . $name;
        if (!method_exists($this, $getter)) {
            throw new Exception('Unknown property');
        }

        return $this->$getter();
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value) :void
    {
        $setter = 'set' . $name;

        if (!property_exists($this, $name)) {
            throw new Exception('Setting unknown property');
        }

        if (!method_exists($this, $setter)) {
            throw new Exception('Property is not writable');
        }
        $this->$setter($value);

        return;
    }
}

/**
 * Class User
 * @property string $name
 */
class User extends Entity
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @return string
     */
    public function getName() :?string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName(string $name) :void
    {
        $this->name = $name;
    }
}

/**
 * Class Post
 *
 * @property string $name
 * @property User $author
 * @property string $post
 */
class Post extends Entity
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var User $author
     */
    private $author;

    /**
     * @var string $post
     */
    private $post;

    /**
     * @return string
     */
    public function getName() :?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name) :void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPost() :?string
    {
        return $this->post;
    }

    /**
     * @param string $post
     */
    public function setPost(string $post) :void
    {
        $this->post = $post;
    }

    /**
     * @return User
     */
    public function getAuthor() :User
    {
        return $this->author;
    }

    /**
     * @param User $author
     */
    public function setAuthor(User $author) :void
    {
        $this->author = $author;
    }
}

/**
 * Class EntityRepository
 * @property QueryBuilder $query
 */
abstract class EntityRepository implements Repository
{
    /**
     * @var QueryBuilder
     */
    public $query;

    /**
     * EntityRepository constructor.
     */
    public function __construct()
    {
        $this->query = new QueryBuilder();
    }
}

/**
 * Class UserRepository
 */
class UserRepository extends EntityRepository
{
    /**
     * @param Entity $entity
     * @return bool
     */
    public function create(Entity $entity): bool
    {
        $sql = "INSERT";
        $this->query::createBySql($sql);
    }

    /**
     * @param string $name
     * @return Entity
     */
    public function getByName(string $name): Entity
    {
        // TODO: Implement getByName() method.
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function update(Entity $entity): bool
    {
        // TODO: Implement updateByName() method.
    }

    /**
     * @param Entity $user
     * @return array|Post[]
     */
    public function getAllPostsByAuthor(Entity $user) :array
    {

    }
}

/**
 * Class PostRepository
 */
class PostRepository extends EntityRepository
{
    /**
     * @param Entity $entity
     * @return bool
     */
    public function create(Entity $entity): bool
    {
    }

    /**
     * @param string $name
     * @return Entity
     */
    public function getByName(string $name): Entity
    {
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function update(Entity $entity): bool
    {
    }

    /**
     * @param Post $post
     * @return User
     */
    public function getAuthor(Post $post) :User
    {
        return $post->author;
    }
}

/**
 * Class App
 * @property Entity $user
 */
class App
{
    /**
     * @var Entity
     */
    public $currentUser;

    /**
     * App constructor.
     */
    public function __construct()
    {
        $this->currentUser = (new UserRepository())->getByName('myName');
    }

    /**
     * create post by current User
     */
    public function createPostByUserAction()
    {
        $repository = new PostRepository();
        $post = new Post();
        $post->name = 'name';
        $post->author = $this->currentUser;
        $post->post = 'post';
        $repository->create($post);
    }

    /**
     * @param Post $post
     * @return User
     */
    public function getAuthorPostAction(Post $post) :User
    {
        $repository = new PostRepository();
        return $repository->getAuthor($post);
    }

    /**
     * @return array|Post[]
     */
    public function getAllPostByUserAction()
    {
        $repository = new UserRepository();
        return $repository->getAllPostsByAuthor($this->currentUser);
    }

    /**
     * @param Post $post
     * @param User $user
     * @return bool
     */
    public function updateAuthorInPostAction(Post $post, User $user) :bool
    {
        $repository = new PostRepository();
        $post->author = $user;
        return $repository->update($post);
    }

}