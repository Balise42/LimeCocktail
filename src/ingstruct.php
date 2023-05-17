<html>
<head>
</head>
<body>
<ul>

<?php

require "../vendor/autoload.php";

use CocktailSearch\DataStore;

$store = DataStore::fromFiles( '../data/cocktails.txt', '../data/recipes' );

$hierarchy = [];

foreach ( $store->getStore() as $item ) {
    foreach ( array_merge( $item->isInstanceOf, $item->isSubclassOf ) as $super ) {
        $super = $store->itemName( $super );
        if ( !array_key_exists( $super, $hierarchy) ) {
            $hierarchy[$super] = [];
        }
        $hierarchy[$super][] = $item->name;
    }
}

foreach ( $hierarchy as $k => $list ) {
    sort( $list );
    $hierarchy[$k] = array_unique( $list );

}

makeList( 'ingredient', $hierarchy );

function makeList( string $ing, array $hierarchy ) {
    echo "<li>$ing" . PHP_EOL;
    if ( array_key_exists( $ing, $hierarchy) && count( $hierarchy[$ing] ) > 0 ) {
        echo "<ul>" . PHP_EOL;
        foreach ( $hierarchy[$ing] as $sub ) {
            makeList( $sub, $hierarchy );
        }
        echo "</ul>" . PHP_EOL;
    }
    echo "</li>" . PHP_EOL;
}

?>
</ul>
</body>
</html>
