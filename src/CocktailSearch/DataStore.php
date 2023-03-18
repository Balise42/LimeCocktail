<?php

namespace CocktailSearch;

use UnexpectedValueException;

class DataStore {

    /** @var Item[] */
    private array $items;

    public static function fromJson( string $filename ): DataStore {
        $jsonFile = file_get_contents( $filename );
        $json = json_decode( $jsonFile );

        $jsonItems = [];
        foreach ( $json as $item ) {
            if ( $item->type === 'item' ) {
                $jsonItems[$item->id] = $item;
            }
        }

        $store = new DataStore();
        foreach ( $jsonItems as $jsonItem ) {
            $item = Item::fromJson( $jsonItem, $jsonItems );
            $store->items[$item->name] = $item;
        }

        return $store;
    }

    public static function fromFlatFile( string $filename ): DataStore {
        $txtFile = file( $filename, FILE_IGNORE_NEW_LINES );
        $existingItems = [];
        $haveAllItems = false;
        $i = 0;
        $store = new DataStore();
        while ($i < count( $txtFile ) ) {
            $line = trim( $txtFile[$i] );
            if ( $line === '======' ) {
                $haveAllItems = true;
                $i++;
                continue;
            }
            if ( !$haveAllItems ) {
                $existingItems[] = $line;
                $i++;
                continue;
            }
            if ( $line === '' ) {
                $i++;
                continue;
            }

            $item = Item::fromFlatFileFormat( $txtFile, $i, $existingItems );
            $store->items[$item->name] = $item;
        }

        return $store;
    }

    public function toFlatFile( string $filename ) {
        file_put_contents($filename, '' );
        foreach ( $this->items as $item ) {
            file_put_contents( $filename, $item->name . PHP_EOL, FILE_APPEND );
        }
        file_put_contents( $filename, '======' . PHP_EOL, FILE_APPEND );
        foreach ( $this->items as $item ) {
            $item->toFlatFileFormat( $filename );
            file_put_contents($filename, PHP_EOL, FILE_APPEND );
        }
    }

    function getSubstitutes( string $ingredient, ?string $recipe = null ): array {
        $ings = [ $ingredient ];
        $ings = array_merge( $ings, $this->items[$ingredient]->substituteFor );
        $ings = array_merge( $ings, $this->getSubsUp( $ingredient) );
        $ings = array_merge( $ings, $this->getSubsDown( $ingredient) );

        if ( $recipe !== null ) {
            foreach ( $this->items[$recipe]->hasIngredients as $ing ) {
                if ( $ing->ingredient === $ingredient ) {
                    $ings = array_merge( $ings, $ing->substituteFor, $ing->suchAs );
                }
            }
        }

        return array_unique( $ings );
    }

    private function getSubsDown(string $ingredient) {
        $ings = [ $ingredient ];
        foreach ( $this->items as $item ) {
            if ( in_array( $ingredient, $item->isSubclassOf ) || in_array( $ingredient, $item->isInstanceOf ) ) {
                $ings = array_merge( $ings, $this->getSubsDown( $item->name ) );
            }
        }
        return $ings;
    }

    private function getSubsUp(string $ingredient) {
        $ings = [ $ingredient ];
        foreach ( $this->items[$ingredient]->isInstanceOf as $up ) {
            $ings = array_merge( $ings, $this->getSubsUp( $up ) );
        }
        foreach ( $this->items[$ingredient]->isSubclassOf as $up ) {
            $ings = array_merge( $ings, $this->getSubsUp( $up ) );
        }

        return $ings;
    }

    public function getCocktails( array $ingredients ): array {
        $recipes = [];
        $extendedIngredients = array_map( fn($i): array => $this->getSubstitutes( $i ), $ingredients );
        foreach ($this->items as $recipe) {
            $include = true;
            foreach ( $extendedIngredients as $extendedIngredient ) {
                if (!$this->hasExtendedIngredient( $recipe, $extendedIngredient ) ) {
                    $include = false;
                    break;
                }
            }
            if ( $include ) {
                $recipes[] = [ $recipe->name, $recipe->description, $recipe->source ] ;
            }
        }
        return $recipes;
    }

    private function hasExtendedIngredient( Item $recipe, array $ingredients ): bool {
        foreach ( $recipe->hasIngredients as $recipeIng ) {
            if ( in_array( $recipeIng->ingredient, $ingredients ) ) {
                return true;
            }
        }
        return false;
    }

    public static function assertExistence( string $elem, array $existingItems, int $i ) {
        if ( !in_array( $elem, $existingItems ) ) {
            throw new UnexpectedValueException( "$i: Item $elem is not part of the known elements" );
        }
    }
}
