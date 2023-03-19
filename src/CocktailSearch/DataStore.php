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
            if ( str_starts_with( $line, 'Item:') ) {
                $item = substr( $line, 5 );
                if ( in_array( $item, $existingItems ) ) {
                    throw new RuntimeException( "Item $item declared twice" );
                }
                $existingItems[] = substr( $line, 5 );
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
            $store->items[$item->name] = $item;
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

    /**
     * @param array $ingredients
     * @return Item[]
     */
    public function getCocktails( array $ingredients ): array {
        if ( count( $ingredients ) === 0 ) {
            return [];
        }
        $recipes = [];
        $extendedIngredients = array_map( fn($i): array => $this->getSubstitutes( $i ), $ingredients );
        foreach ($this->items as $recipe) {
            if ( !in_array('cocktail recipe', $recipe->isInstanceOf ) ) {
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

    public function hasItem(string $ing): bool {
        return ( array_key_exists( $ing, $this->items ) );
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
        if ( in_array( 'ingredient', $item->isInstanceOf ) || in_array( 'ingredient', $item->isSubclassOf)) {
            return true;
        }
        foreach ( $item->isInstanceOf as $inst ) {
            if ( $this->isIngredient( $this->items[$inst], $it + 1 ) ) {
                return true;
            }
        }
        foreach ( $item->isSubclassOf as $class ) {
            if ( $this->isIngredient( $this->items[$class], $it + 1 ) ) {
                return true;
            }
        }
        return false;
    }

    public function getStore() {
        return $this->items;
    }
}
