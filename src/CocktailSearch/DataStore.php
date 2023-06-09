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

    public static function fromFiles( string $filename, ?string $dirname ): DataStore {
        $existingItems = [];
        $store = new DataStore();
        $store->fromFlatFile( $filename, $existingItems );
        $store->fromDir( $dirname, $existingItems );
        return $store;
    }

    private function fromDir( string $dirname, array &$existingItems ): void {
        $scannedDir = array_diff( scandir( $dirname ), array( '..', '.' ) );
        foreach ( $scannedDir as $name ) {
            $f = $dirname . '/' . $name;
            if ( is_dir( $f ) ) {
                $this->fromDir( $f, $existingItems );
            } else {
                $this->fromRecipeFile( $f, $existingItems );
            }
        }
    }

    private function fromRecipeFile( string $filename, array $existingItems ): void {
        $txtFile = file( $filename, FILE_IGNORE_NEW_LINES );
        $item = Item::fromRecipeFile( $txtFile, $existingItems, $filename );
        $this->items[ strtoupper( $item->name) ] = $item;
        foreach ( $item->alias as $alias ) {
            $this->items[ strtoupper( $alias ) ] = $item;
        }
    }

    private function fromFlatFile( string $filename, array &$existingItems ): void {
        $txtFile = file( $filename, FILE_IGNORE_NEW_LINES );

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
            $this->items[ strtoupper( $item->name ) ] = $item;
            foreach ( $item->alias as $alias ) {
                $this->items[ strtoupper( $alias ) ] = $item;
            }
        }
    }

    public function toFlatFile( string $filename ) {
        file_put_contents($filename, '' );
        foreach ( $this->items as $item ) {
            $item->toFlatFileFormat( $filename );
            file_put_contents($filename, PHP_EOL, FILE_APPEND );
        }
    }

    function getSubstitutes( string $ingredient, string $exact, ?string $recipe = null ): array {
        $ingName = $this->itemName( $ingredient );
        $ings = [ $ingName ];
        $ings = array_merge( $ings, $this->getSubsDown( $ingredient ) );
        if ( $exact === 'exact' ) {
            return $ings;
        }

        $ings = array_merge( $ings, $this->getDirectSubs( $ingredient ) );
        $ings = array_merge( $ings, $this->itemNames( $this->items[strtoupper( $ingredient) ]->substituteFor ) );
        $ings = array_merge( $ings, $this->getSubsUp( $ingredient) );

        if ( $recipe !== null ) {
            foreach ( $this->items[strtoupper( $recipe) ]->hasIngredients as $ing ) {
                if ( $ing->ingredient === $ingredient ) {
                    $ings = array_merge( $ings, $this->itemNames( $ing->substituteFor ), $this->itemNames( $ing->suchAs ) );
                }
            }
        }

        return array_unique( $ings );
    }

    private function itemNames( array $ings ): array {
        return array_map( [$this, "itemName"], $ings );
    }

    public function itemName( string $ingredient ): string {
        return $this->items[strtoupper( $ingredient ) ]->name;
    }

    private function getDirectSubs( string $ingredient ) {
        $ingName = $this->itemName( $ingredient );
        $ings = [ $ingName ];
        foreach ( $this->items as $item ) {
            if ( in_array( $ingName, $this->itemNames( $item->substituteFor ) ) ) {
                $ings[] = $item->name;
            }
        }
        return $ings;
    }

    private function getSubsDown(string $ingredient) {
        $ingName = $this->itemName( $ingredient );
        $ings = [ $ingName ];
        foreach ( $this->items as $down ) {
            if ( in_array( $ingName, $this->itemNames( $down->isSubclassOf ) ) || in_array( $ingName, $this->itemNames( $down->isInstanceOf ) ) ) {
                $ings = array_merge( $ings, $this->getSubsDown( $down->name ) );
            }
        }
        return $ings;
    }

    private function getSubsUp(string $ingredient) {
        $ingName = $this->itemName( $ingredient );
        $ings = [ $ingName ];
        foreach ( $this->items[strtoupper( $ingName ) ]->isInstanceOf as $up ) {
            $ings = array_merge( $ings, $this->getSubsUp( $this->itemName( $up ) ) );
        }
        foreach ( $this->items[strtoupper( $ingName )]->isSubclassOf as $up ) {
            $ings = array_merge( $ings, $this->getSubsUp( $this->itemName( $up ) ) );
        }

        return $ings;
    }

    /**
     * @param array $ingredients
     * @param array $exact
     * @param string $type
     * @return Item[]
     */
    public function getRecipes(array $ingredients, array $exact, string $type ): array {
        $recipes = [];
        $extendedIngredients = array_map( fn($i, $x): array => $this->getSubstitutes( $i, $x ), $ingredients, $exact );
        foreach ($this->items as $recipe) {
            if ( !$recipe->hasType( $type, $this ) ) {
                continue;
            }
            if ( !in_array(strtoupper( $type ), array_map( 'strtoupper', $recipe->isInstanceOf ) ) ) {
                continue;
            }
            $include = true;
            foreach ( $extendedIngredients as $i => $extendedIngredient ) {
                if ( ( !$this->hasExtendedIngredient( $recipe, $extendedIngredient ) &&  $exact[$i] !== 'exclude' )
                    || ( $this->hasExtendedIngredient( $recipe, $extendedIngredient ) && $exact[$i] === 'exclude' ) ) {
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
        $ingUpper = array_map( 'strtoupper', $this->itemNames( $ingredients ) );
        foreach ( $recipe->hasIngredients as $recipeIng ) {
            if ( in_array( strtoupper( $this->itemName( $recipeIng->ingredient ) ), $ingUpper ) ) {
                return true;
            }
            foreach ( $recipeIng->suchAs as $such ) {
                if ( in_array( strtoupper( $this->itemName( $such ) ), $ingUpper) ) {
                    return true;
                }
            }
            foreach ( $recipeIng->substituteFor as $sub ) {
                if ( in_array( strtoupper( $this->itemName( $sub ) ), $ingUpper) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function assertExistence( string $elem, array $existingItems, int $i, ?string $file = null ) {
        if ( !in_array( strtoupper( $elem ), $existingItems ) ) {
            throw new UnexpectedValueException( $file !== null ? "$file ": "" . "$i: Item $elem is not part of the known elements" );
        }
    }

    public function hasItem( string $ing ): bool {
        return ( array_key_exists( strtoupper( $ing ), $this->items ) );
    }

    public function getIngredients(): array {
        $res = [];
        foreach ( $this->items as $item ) {
            if ( $item->isIngredient( $this ) ) {
                $res[$item->name] = $item->description;
                foreach ( $item->alias as $alias) {
                    $res[$alias] = $item->description;
                }
            }
        }
        ksort( $res );
        return $res;
    }

    public function getStore() {
        return $this->items;
    }

    public function get( string $s ): Item {
        if ( $this->hasItem( $s ) ) {
            return $this->items[ strtoupper($s) ];
        } else {
            throw new UnexpectedValueException( "$s is not part of the known items in the datastore" );
        }
    }
}
