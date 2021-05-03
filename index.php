<?php 

session_start();
require_once("vendor/autoload.php");

$app = new \Slim\Slim();


$app->config('debug', true);

//separar rotas em arqs diferentes não funciona, provav erro ou bloqueio no Apache
/* require_once("site.php");
require_once("admin.php");
require_once("admin-categories.php");
require_once("admin-users.php");
require_once("admin-products.php"); */

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\PageAdmin;   
use \Hcode\Model\User;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;

/////////ROTAS DO SITE////////////////////////////////////////////////////////////////
$app->get('/', function() {

    //carregar produtos na home
    $products = Product::listAll();
    
	$page = new Hcode\Page();
	$page->setTpl("index", [
        "products" => Product::checkList($products)
    ]);

});

$app->get("/categories/:idcategory", function($idcategory) {

	$category = new Category();
	$category->get((int)$idcategory);
	
	$page = new Hcode\Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>Product::checkList($category->getProducts())
	]);
});

$app->get("/products/:desurl", function($desurl) {

	$product = new Product();
	$product->getFromURL($desurl);

	$page = new Page();
	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

$app->get("/cart", function() {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});

$app->get("/cart/:idproduct/add", function($idproduct) {

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1; //qtd no product-details

	for ($i = 0; $i = $qtd; $i++) {

		$cart->addProduct($product);
	}

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/minus", function($idproduct) {

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/remove", function($idproduct) {

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});

$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();
	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

$app->get("/checkout", function() {

	User::verifyLogin(false);
	$cart = Cart::getFromSession();
	$address = new Address();

	$page = new Page();
	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues()

	]);
});

$app->get("/login", function() {

	$page = new Page();
	$page->setTpl("login", [
		'error'=>User::getError()
	]);
});

$app->post("/login", function() {

	try {

		User::login($_POST['login'], $_POST['password']);

	} catch (Exception $ex) {
		
		User::setError($ex->getMessage());
		User::getError();
		User::clearError();
	}

	header("Location: /checkout");
	exit;
});

$app->get("/logout", function() {

	User::logout();

	header("Location: /login");
	exit;
});


/////////ROTAS ADMIN////////////////////////////////////////////////////////////////

$app->get('/admin', function() {

	User::verifyLogin();
    
	$page = new Hcode\PageAdmin();
	$page->setTpl("index");

});

$app->get('/admin/login', function() {
    
	$page = new Hcode\PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("login");

});

$app->post('/admin/login', function() {
    
	//User::login($_POST["login"],$_POST["password"]);
	$usr = new Hcode\Model\User();
	$usr->login($_POST["login"],$_POST["password"]);
	header("Location: /admin");
	exit;

});

$app->get('/admin/logout', function() {

	User::logout();
	header("Location: /admin/login");
	exit;
});

$app->get("/admin/forgot", function() {
	
	$page = new Hcode\PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot");
});

$app->post("/admin/forgot", function() {
	
	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit;
});

$app->get("/admin/forgot/sent", function() {

	$page = new Hcode\PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function() {
	
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Hcode\PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});

$app->post("/admin/forgot/reset", function() {
	$forgot = User::validForgotDecrypt(($_POST["code"]));
	User::setForgotUsed($forgot["idrecovery"]);
	$user = new User();
	$user->get((int)$forgot["iduser"]);
	
	//criptografar nova senha
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	$user->setPassword($_POST["password"]);

	$page = new Hcode\PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-reset-success");
});

/////////ROTAS ADMIN USER////////////////////////////////////////////////////////////////

$app->get("/admin/users", function() {

	User::verifyLogin();
	$users = User::listAll();
	//$page = new Hcode\PageAdmin();
	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users"=>$users
	));
});

$app->get("/admin/users/create", function() {

	User::verifyLogin();
	//$page = new Hcode\PageAdmin();
	$page = new PageAdmin();
	$page->setTpl("users-create");
});

$app->get("/admin/users/:iduser/delete", function($iduser) {

	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$user->delete();

	header("Location: /admin/users");
	exit;
});

$app->get('/admin/users/:iduser', function($iduser){

	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdmin();
	$page ->setTpl("users-update", array(
		"user"=>$user->getValues()
	));
});

$app->post("/admin/users/create", function() {

	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$user->setData($_POST);
	$user->save();

	header("Location: /admin/users");
	exit;
});

$app->post("/admin/users/:iduser", function($iduser) {

	User::verifyLogin();
	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();

	header("Location: /admin/users");
	exit;
});

/////////ROTAS ADMIN PRODUCTS////////////////////////////////////////////////////////////////

$app->get("/admin/products", function() {

    User::verifyLogin();

    $products = Product::listAll();

    $page = new PageAdmin();
    $page->setTpl("products", [
        "products"=>$products
    ]);
});

$app->get("/admin/products/create", function() {

    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl("products-create");
});

$app->post("/admin/products/create", function() {

    User::verifyLogin();

    $product = new Product();
    $product->setData($_POST);
    $product->save();

    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct", function($idproduct) {

    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);

    $page = new PageAdmin();
    $page->setTpl("products-update", [
        'product'=>$product->getValues()
    ]);
});

$app->get("/admin/products/:idproduct/delete", function ($idproduct) {

    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    $product->delete();

    header("Location: /admin/products");
    exit;
});

$app->post("/admin/products/:idproduct", function($idproduct) {

    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    $product->setData($_POST);
    $product->save();

    $product->setPhoto($_FILES["file"]);

    header("Location: /admin/products");
    exit;
});

/////////ROTAS ADMIN CATEGORIES////////////////////////////////////////////////////////////////

$app->get("/admin/categories", function() {
	
	User::verifyLogin();

	$categories=Category::listAll();

	$page = new Hcode\PageAdmin();
	$page->setTpl("categories", [
		'categories'=>$categories
	]);
});

$app->get("/admin/categories/create", function() {
	
	User::verifyLogin();

	$page = new Hcode\PageAdmin();
	$page->setTpl("categories-create");
});

$app->post("/admin/categories/create", function() {
	
	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);
	$category->save();

	header('Location: /admin/categories');
	exit;
});

$app->get("/admin/categories/:idcategory/delete", function($idcategory) {

	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);
	$category->delete();

	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory", function($idcategory) {

	User::verifyLogin();

	$category=new Category();
	$category->get((int)$idcategory);

	$page = new Hcode\PageAdmin();
	$page->setTpl("categories-update", [
		'category'=>$category->getValues()
	]);
});

$app->post("/admin/categories/:idcategory", function($idcategory) {

	User::verifyLogin();

	$category=new Category();
	$category->get((int)$idcategory);
	$category->setData($_POST);
	$category->save();

	//redirect
	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory/products", function($idcategory) {

    User::verifyLogin();

    $category=new Category();
	$category->get((int)$idcategory);

    $page = new PageAdmin();
	$page->setTpl("categories-products", [
        'category'=>$category->getValues(),
        'productsRelated'=>$category->getProducts(),
        'productsNotRelated'=>$category->getProducts(false)
    ]);

});

$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {

    User::verifyLogin();

    $category=new Category();
	$category->get((int)$idcategory);

    $product = new Product();
    $product->get((int)$idproduct);

    $category->addProduct($product);
    
	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {

    User::verifyLogin();

    $category=new Category();
	$category->get((int)$idcategory);

    $product = new Product();
    $product->get((int)$idproduct);

    $category->removeProduct($product);
    
	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

/////////FUNCTIONS.PHP////////////////////////////////////////////////////////////////

function formatPrice(float $vlprice) {

    return number_format($vlprice, 2, ",", ".");
}

function checkLogin($inadmin = true) {

	return User::checkLogin($inadmin);
}

function getUserName() {

	$user = user::getFromSession();
	return $user->getdesperson();
}

$app->run();

 ?>