<?php

require "../vendor/autoload.php";

use CocktailSearch\DataStore;

$store = DataStore::fromFlatFile( '../data/cocktails.txt' );

$numError = 0;
foreach ( $store->getStore() as $item ) {
    foreach ( $item->hasIngredients as $ing ) {
        if ( !$store->isIngredient( $store->getStore()[$ing->ingredient], 0 ) ) {
            echo 'Missing ingredient definition for ' . $ing->ingredient . PHP_EOL;
            $numError++;
        }
    }
}

if ( $numError === 0 ) {
    echo "Everything's looking good!";
}
