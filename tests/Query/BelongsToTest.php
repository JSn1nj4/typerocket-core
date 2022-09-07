<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Database\Results;
use TypeRocket\Database\ResultsMeta;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

/**
 * @property int $product_number
 * @property string $title
 * @property VariantTest[]|Results $variants
 */
class ProductTest extends Model
{
    protected $table = 'products';
    protected $idColumn = 'product_number';
    protected $fillable = ['product_number', 'title'];

    public function variants()
    {
        return $this->belongsToMany(VariantTest::class, 'products_variants', 'product_number', 'variant_sku', null, true, 'product_number');
    }
}

/**
 * @property string $sku
 * @property string $barcode
 * @property ProductTest[]|Results $products
 */
class VariantTest extends Model
{
    protected $table = 'variants';
    protected $idColumn = 'sku';
    protected $fillable = ['sku', 'barcode'];

    public function products()
    {
        return $this->belongsToMany(ProductTest::class, 'products_variants', 'variant_sku', 'product_number', null, true, 'sku');
    }
}

/**
 * @property int $id
 * @property string $sku
 * @property int $product_number
 */
class ProductVariantTest extends Model
{
    protected $table = 'products_variants';
}

/**
 * @property int $id
 * @property string $p_number
 * @property string $name
 * @property RolesTest[]|Results $roles
 */
class PeopleTest extends Model
{
    protected $table = 'peoples';
    protected $fillable = ['name', 'p_number'];

    public function roles()
    {
        return $this->belongsToMany(RolesTest::class, 'peoples_roles', 'people_number', 'role_number', null, true, 'p_number', 'r_number');
    }

    public function rolesNameIsAdmin()
    {
        return $this->roles()->where('roles.name', 'admin');
    }

    public function rolesNameIsSubscriber()
    {
        return $this->roles()->where('roles.name', 'subscriber');
    }
}

/**
 * @property int $id
 * @property string $r_number
 * @property string $name
 * @property PeopleTest[]|Results $peoples
 */
class RolesTest extends Model
{
    protected $table = 'roles';
    protected $fillable = ['name', 'r_number'];

    public function peoples()
    {
        return $this->belongsToMany(PeopleTest::class, 'peoples_roles', 'role_number', 'people_number', null, true, 'r_number', 'p_number');
    }

    public function peoplesNameIsKevin()
    {
        return $this->peoples()->where('peoples.name', 'kevin');
    }
}

/**
 * @property int $id
 * @property int $people_number
 * @property int $role_number
 */
class PeoplesRolesTest extends Model
{
    protected $table = 'peoples_roles';
}

class BelongsToTest extends TestCase
{
    public function testBelongsTo()
    {
        $meta = new WPPostMeta();
        $post = $meta->findById(1)->post();
        $sql = $post->getSuspectSQL();
        $expected = "SELECT * FROM `wp_posts` WHERE `ID` = '2' LIMIT 1 OFFSET 0";
        $rel = $post->getRelatedModel();
        $this->assertTrue( $rel instanceof WPPostMeta );
        $this->assertTrue($sql == $expected);
    }

    public function testBelongsEagerLoad()
    {
        $post = new WPPost();

        $numRun = Query::$numberQueriesRun;

        $result = $post->with(['author.meta', 'meta.post'])->findAll([1,2,3])->get();

        foreach ($result as $item) {
            $this->assertTrue( $item->author instanceof WPUser );
            $this->assertTrue( $item->getRelationship('author') instanceof WPUser );
            $this->assertTrue( $item->author->meta instanceof ResultsMeta);
            $this->assertTrue( $item->meta instanceof ResultsMeta);

            foreach ($item->meta as $meta) {
                $this->assertTrue( $meta->post instanceof WPPost);
            }
        }

        $numRun = Query::$numberQueriesRun - $numRun;

        $this->assertTrue( $numRun === 5 );
    }

    public function testProductsVariantsTest()
    {
        /** @var VariantTest $variant */
        $variant = VariantTest::new()->saveAndGet(['sku' => 'ABC', 'barcode' => '987']);

        /** @var ProductTest $product */
        $product = ProductTest::new()->saveAndGet(['product_number' => 123, 'title' => 'product 1']);

        $product->variants()->attach([$variant->sku]);

        /** @var VariantTest[] $pv */
        $pv = $product->variants()->get();

        $this->assertTrue($pv[0] instanceof VariantTest);
        $this->assertTrue($pv[0]->barcode === '987');

        /** @var VariantTest $variant */
        $variant = $variant->load('products');
        $productLoaded = $variant->products[0];

        $this->assertTrue($productLoaded instanceof ProductTest);
        $this->assertTrue(!$productLoaded->sku);
        $this->assertTrue($productLoaded->product_number === '123');
        $this->assertTrue($productLoaded->title === 'product 1');
        $this->assertTrue(!$productLoaded->the_relationship_id);
    }

    public function testPeoplesRolesTest()
    {
        global $wpdb;

        $person2 = PeopleTest::new()->saveAndGet(['p_number' => 100, 'name' => 'kim']);
        RolesTest::new()->saveAndGet(['r_number' => 200, 'name' => 'subscriber']);

        /** @var RolesTest $role */
        $role = RolesTest::new()->saveAndGet(['r_number' => 123, 'name' => 'admin']);
        $role2 = RolesTest::new()->saveAndGet(['r_number' => 100, 'name' => 'reader']);

        /** @var PeopleTest $person */
        $person = PeopleTest::new()->saveAndGet(['p_number' => 987, 'name' => 'kevin']);


        $person->roles()->attach([$role->r_number, $role2]);

        /** @var RolesTest[]|Results $roles */
        $roles = $person->roles()->get();

        $this->assertTrue($roles[0] instanceof RolesTest);
        $this->assertTrue($roles->count() === 2);
        $this->assertTrue($roles[0]->name === 'admin');
        $this->assertTrue($roles[0]->r_number === '123');

        /** @var PeopleTest $person */
        $person = PeopleTest::new()->find(1)->load('roles');
        $this->assertTrue($person->roles === null);

        /** @var PeopleTest $person */
        $person = PeopleTest::new()->find(2)->load('roles');
        $this->assertTrue($person->roles->count() === 2);

        /** @var RolesTest $role */
        $role = $role->load('peoples');
        $peoplesLoaded = $role->peoples[0];

        $this->assertTrue($peoplesLoaded instanceof PeopleTest);
        $this->assertTrue($role->peoples->count() === 1);
        $this->assertTrue($peoplesLoaded->p_number === '987');
        $this->assertTrue($peoplesLoaded->name === 'kevin');
        $this->assertTrue(!$peoplesLoaded->r_number);
        $this->assertTrue(!$peoplesLoaded->the_relationship_id);

        /** @var PeopleTest[]|Results $variants **/
        $persons = PeopleTest::new()->has('roles')->get();
        $this->assertTrue($persons->count() === 1);
        $this->assertTrue($persons->first()->name === 'kevin');

        $person2->roles()->attach([$role2]);

        /** @var PeopleTest[]|Results $variants **/
        $persons = PeopleTest::new()->has('roles')->get();
        $this->assertTrue($persons->count() === 2);

        /** @var PeopleTest[]|Results $peoples */
        $peoples = RolesTest::new()->find(2)->peoplesNameIsKevin()->has('rolesNameIsAdmin')->get();
        $this->assertTrue($peoples->count() === 1);

        /** @var PeopleTest[]|Results $peoples */
        $peoples = RolesTest::new()->find(2)->peoplesNameIsKevin()->has('rolesNameIsSubscriber')->get();
        $this->assertTrue($peoples === null);

        /** @var RolesTest[]|Results $roles */
        $roles = RolesTest::new()->has('peoples')->get();
        $this->assertTrue($roles->count() === 2);

        /** @var RolesTest[]|Results $roles */
        $roles = RolesTest::new()->hasNo('peoples')->get();
        $this->assertTrue($roles->count() === 1);
    }
}