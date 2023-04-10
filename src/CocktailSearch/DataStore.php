<?php

namespace CocktailSearch;

use RuntimeException;
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
        $store = new DataStore();

        foreach ( $txtFile as $line ) {
            if ( str_starts_with( $line, 'Item:') || str_starts_with( $line, 'Alias:') ) {
                $item = strtoupper( explode( ':', $line, 2 )[1] );
                if ( in_array( $item, $existingItems ) ) {
                    throw new RuntimeException( "Item $item declared twice" );
                }
                $existingItems[] = $item;
            }
        }

        $i = 0;
        while ($i < count( $txtFile ) ) {
            $line = $txtFile[$i];
            if ( $line === '' ) {
                $i++;
                continue;
            }

            $item = Item::fromFlatFileFormat( $txtFile, $i, $existingItems );
            $store->items[ strtoupper( $item->name ) ] = $item;
            foreach ( $item->alias as $alias ) {
                $store->items[ strtoupper( $alias ) ] = $item;
            }
        }

        return $store;
    }

    public function toFlatFile( string $filename ) {
        file_put_contents($filename, '' );
        foreach ( $this->items as $item ) {
            $item->toFlatFileFormat( $filename );
            file_put_contents($filename, PHP_EOL, FILE_APPEND );
        }
    }

    function getSubstitutes( string $ingredient, string $exact, ?string $recipe = null ): array {
        $ings = [ $ingredient, ...$this->items[strtoupper( $ingredient) ]->alias ];
        $ings = array_merge( $ings, $this->getSubsDown( $ingredient) );
        if ( $exact === 'exact' ) {
            return $ings;
        }
        $ings = array_merge( $ings, $this->items[strtoupper( $ingredient) ]->substituteFor );
        $ings = array_merge( $ings, $this->getSubsUp( $ingredient) );

        if ( $recipe !== null ) {
            foreach ( $this->items[strtoupper( $recipe) ]->hasIngredients as $ing ) {
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
        foreach ( $this->items[strtoupper( $ingredient ) ]->isInstanceOf as $up ) {
            $ings = array_merge( $ings, $this->getSubsUp( $up ) );
        }
        foreach ( $this->items[strtoupper( $ingredient )]->isSubclassOf as $up ) {
            $ings = array_merge( $ings, $this->getSubsUp( $up ) );
        }

        return $ings;
    }

    /**
     * @param array $ingredients
     * @return Item[]
     */
    public function getCocktails( array $ingredients, array $exact ): array {
        if ( count( $ingredients ) === 0 ) {
            return [];
        }
        $recipes = [];
        $extendedIngredients = array_map( fn($i, $x): array => $this->getSubstitutes( $i, $x ), $ingredients, $exact );
        foreach ($this->items as $recipe) {
            if ( !in_array('COCKTAIL RECIPE', array_map( 'strtoupper', $recipe->isInstanceOf ) ) ) {
                continue;
            }
            $include = true;
            foreach ( $extendedIngredients as $extendedIngredient ) {
                if (!$this->hasExtendedIngredient( $recipe, $extendedIngredient ) ) {
                    $include = false;
                    break;
                }
            }
            if ( $include ) {
                $recipes[] = $recipe;
            }
        }
        return $recipes;
    }

    private function hasExtendedIngredient( Item $recipe, array $ingredients ): bool {
        $ingUpper = array_map( 'strtoupper', $ingredients );
        foreach ( $recipe->hasIngredients as $recipeIng ) {
            if ( in_array( strtoupper( $recipeIng->ingredient ), $ingUpper ) ) {
                return true;
            }
            foreach ( $recipeIng->suchAs as $such ) {
                if ( in_array( strtoupper( $such ), $ingUpper) ) {
                    return true;
                }
            }
            foreach ( $recipeIng->substituteFor as $sub ) {
                if ( in_array( strtoupper( $sub ), $ingUpper) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function assertExistence( string $elem, array $existingItems, int $i ) {
        if ( !in_array( strtoupper( $elem ), $existingItems ) ) {
            throw new UnexpectedValueException( "$i: Item $elem is not part of the known elements" );
        }
    }

    public function hasItem(string $ing): bool {
        return ( array_key_exists( strtoupper( $ing ), $this->items ) );
    }

    public function getIngredients(): array {
        $res = [];
        foreach ( $this->items as $item ) {
            if ( $this->isIngredient( $item, 0 ) ) {
                $res[$item->name] = $item->name;
                foreach ( $item->alias as $alias) {
                    $res[$alias] = $item->name;
                }
            }
        }
        return $res;
    }

    public function isIngredient( Item $item, int $it ): bool {
        if ( $it > 50 ) {
            throw new RuntimeException( "ETOOMANYLOOPS" );
        }
        if ( in_array( 'INGREDIENT', array_map( 'strtoupper', $item->isInstanceOf ) ) || in_array( 'INGREDIENT', array_map( 'strtoupper', $item->isSubclassOf ) ) ) {
            return true;
        }
        foreach ( $item->isInstanceOf as $inst ) {
            if ( $this->isIngredient( $this->items[strtoupper( $inst )], $it + 1 ) ) {
                return true;
            }
        }
        foreach ( $item->isSubclassOf as $class ) {
            if ( $this->isIngredient( $this->items[strtoupper( $class ) ], $it + 1 ) ) {
                return true;
            }
        }
        return false;
    }

    public function getStore() {
        return $this->items;
    }
}
