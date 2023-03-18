<?php

require "../vendor/autoload.php";

use CocktailSearch\DataStore;

$ds = DataStore::fromJson( '..\data\wikibase.json' );
print_r( $ds->getSubstitutes( 'white rum' ) );

print_r( $ds->getCocktails( ['gin', 'lemon juice' ] ) );

$ds->toFlatFile( '..\data\cocktails.txt' );
$ds2 = DataStore::fromFlatFile( '..\data\cocktails.txt');

print_r( $ds2->getSubstitutes( 'white rum' ) );

print_r( $ds2->getCocktails( ['rum', 'lemon juice' ] ) );


