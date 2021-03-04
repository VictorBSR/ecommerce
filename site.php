<?php

use \Hcode\Page;
use \Hcode\Model\Category;

$app->get('/', function() {
    
	$page = new Hcode\Page();
	$page->setTpl("index");

});

$app->get("/categories/:idcategory", function($idcategory) {

	$category = new Category();
	$category->get((int)$idcategory);
	
	$page = new Hcode\Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>[]
	]);
});

?>