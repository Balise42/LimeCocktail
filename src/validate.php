<?php

require "../vendor/autoload.php";

use CocktailSearch\DataStore;

$store = DataStore::fromFiles( '../data/cocktails.txt', '../data/recipes' );

$numError = 0;
$numCocktails = 0;
$numIng = 0;
foreach ( $store->getStore() as $item ) {
    if ( $item->description === '' ) {
        echo 'Missing description for ' . $item->name . PHP_EOL;
        $numError++;
    }
    foreach ( $item->hasIngredients as $ing ) {
        if ( !$store->get( $ing->ingredient )->isIngredient( $store ) ) {
            echo 'Missing ingredient definition for ' . $ing->ingredient . PHP_EOL;
            $numError++;
        }
    }

    if ( in_array( 'cocktail recipe', $item->isInstanceOf ) || in_array( 'recipe', $item->isInstanceOf ) ) {
        if ( count ( $item->source ) === 0 ) {
            echo 'Missing source for ' . $item->name . PHP_EOL;
            $numError++;
        }
        foreach ( $item->source as $source ) {
            if ( !isset( $source->url ) && !isset( $source->book ) && !isset( $source->filename ) ) {
                echo 'Invalid source for ' . $item->name . PHP_EOL;
                $numError++;
            }
            if ( isset ( $source->book ) && !isset( $source->page) ) {
                echo 'Invalid page for ' . $item->name . PHP_EOL;
                $numError++;
            }
        }
    }

    if ( count( $item->isInstanceOf ) === 0 && count( $item->isSubclassOf ) === 0 ) {
        if ( !in_array ( $item->name, ['recipe', 'ingredient', 'book', 'glassware'] ) ) {
            echo 'Missing type for ' . $item->name . PHP_EOL;
            $numError++;
        }
    }

    if ( in_array('cocktail recipe', $item->isInstanceOf ) ) {
        $numCocktails++;
    }
    if ( $item->isIngredient( $store ) ) {
        $numIng++;
    }
}

if ( $numError === 0 ) {
    echo "Everything's looking good!" . PHP_EOL;
    echo "$numCocktails cocktails, $numIng ingredients" . PHP_EOL;
}
