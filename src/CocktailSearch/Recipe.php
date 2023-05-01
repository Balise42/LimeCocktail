<?php


namespace CocktailSearch;


class Recipe {
    public string $yield;
    public array $ingQty = [];
    public array $steps = [];

    public function parseYield( string $s ) {
        $this->yield = $s;
    }

    public function parseIngredients( array $txtFile, array $existingItems, int &$i, string $filename ) {
        while (  $i < count( $txtFile) && trim( strtoupper($txtFile[$i] ) ) !== 'RECIPE:' ) {
            $line = $txtFile[$i];
            if ( trim( $line ) === '' ) {
                $i++;
                continue;
            }
            $toks = explode( ', ', $line );
            $ing = implode( ", ", array_splice( $toks, 0, count( $toks ) - 1 ) );
            $qty = $toks[ count($toks) - 1];
            DataStore::assertExistence( $ing, $existingItems, $i, $filename );
            $this->ingQty[$ing] = $qty;
            $i++;
        }
    }
}
