<?php

$router = new Router();

$router->get('dashboard', function () {
    (new DashboardController())->index();
});

$router->get('recipes', function () {
    (new RecipeController())->index();
});

$router->get('recipe_search', function () {
    (new RecipeController())->search();
});
