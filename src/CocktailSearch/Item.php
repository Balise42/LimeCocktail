<?php


namespace CocktailSearch;


use RuntimeException;
use stdClass;
use UnexpectedValueException;

class Item {
    public string $name;
    public string $description = '';
    /** @var string[] */
    public array $alias = [];
    /** @var string[] */
    public array $isSubclassOf = [];
    /** @var string[] */
    public array $isInstanceOf = [];
    /** @var IngRel[] */
    public array $hasIngredients = [];
    /** @var string[] */
    public array $substituteFor = [];
    /** @var Source[] */
    public array $source = [];
    /** @var string[] */
    public array $variationOf = [];
    /** @var Glassware[] */
    public array $glass = [];
    /** @var string[] */
    public array $garnish = [];

    public static function fromJson( stdClass $jsonItem, array $jsonItems ): Item {
        // TODO would be nicer to not have to pass $jsonItems here
        $item = new Item();


        $item->name = $jsonItem->labels->en->value;
        if ( isset ( $jsonItem->descriptions->en ) ) {
            $item->description = $jsonItem->descriptions->en->value;
        }
        if ( isset ( $jsonItem->aliases->en ) ) {
            foreach ( $jsonItem->aliases->en as $alias ) {
                $item->alias[] = $alias->value;
            }
        }
        if ( isset ( $jsonItem->claims->P2 ) ) {
            foreach ( $jsonItem->claims->P2 as $p2) {
                $item->isSubclassOf[] = $jsonItems[$p2->mainsnak->datavalue->value->id]->labels->en->value;
            }
        }
        if ( isset ( $jsonItem->claims->P1) ) {
            foreach ( $jsonItem->claims->P1 as $p1) {
                $item->isInstanceOf[] = $jsonItems[$p1->mainsnak->datavalue->value->id]->labels->en->value;
            }
        }

        if ( isset ( $jsonItem->claims->P4) ) {
            foreach ( $jsonItem->claims->P4 as $p4 ) {
                $item->substituteFor[] = $jsonItems[$p4->mainsnak->datavalue->value->id]->labels->en->value;
            }
        }

        if ( isset ( $jsonItem->claims->P3) ) {
            foreach ( $jsonItem->claims->P3 as $p3 ) {
                $ingRel = new IngRel();
                $ingRel->ingredient = $jsonItems[$p3->mainsnak->datavalue->value->id]->labels->en->value;
                $item->hasIngredients[] = $ingRel;
                if ( isset( $p3->qualifiers->P8 ) ) {
                    foreach ( $p3->qualifiers->P8 as $p8 ) {
                        $ingRel->suchAs[] = $jsonItems[$p8->datavalue->value->id]->labels->en->value;
                    }
                }
                if ( isset( $p3->qualifiers->P4 ) ) {
                    foreach ( $p3->qualifiers->P4 as $p4 ) {
                        $ingRel->substituteFor[] = $jsonItems[$p4->datavalue->value->id]->labels->en->value;
                    }
                }
                if ( isset ( $p3->qualifiers->P11 ) ) {
                    $ingRel->optional = true;
                }
            }
        }

        if ( isset ( $jsonItem->claims->P5 ) ) {
            foreach ( $jsonItem->claims->P5 as $p5 ) {
                if ( isset($p5->qualifiers->P7 ) ) {
                    foreach ($p5->qualifiers->P7 as $p7) {
                        $source = new Source();
                        $source->book = $jsonItems[$p5->mainsnak->datavalue->value->id]->labels->en->value;
                        $source->page = $p7->datavalue->value;
                        $item->source[] = $source;
                    }
                }
            }
        }

        if ( isset ( $jsonItem->claims->P6 ) ) {
            foreach ( $jsonItem->claims->P6 as $p6 ) {
                $source = new Source();
                $source->url = $p6->mainsnak->datavalue->value;
                $item->source[] = $source;
            }
        }

        if ( isset ( $jsonItem->claims->P9 ) ) {
            foreach ( $jsonItem->claims->P9 as $p9 ) {
                $item->variationOf[] = $jsonItems[$p9->mainsnak->datavalue->value->id]->labels->en->value;
            }
        }

        if ( isset ( $jsonItem->claims->P10 ) ) {
            foreach ( $jsonItem->claims->P10 as $p10 ) {
                $glass = new Glassware();
                $item->glass[] = $glass;
                $glass->glass = $jsonItems[$p10->mainsnak->datavalue->value->id]->labels->en->value;
                if ( isset( $p10->qualifiers->P13 ) ) {
                    $glass->qualifier = $p10->qualifiers->P13[0]->datavalue->value;
                }
            }
        }

        if ( isset ($jsonItem->claims->P12 ) ) {
            foreach ( $jsonItem->claims->P12 as $p12 ) {
                $item->garnish[] = $jsonItems[$p12->mainsnak->datavalue->value->id]->labels->en->value;
            }
        }

        return $item;
    }

    public static function fromFlatFileFormat( array $txtFile, int &$i, array $existingItems ): Item {
        $item = null;

        while ( $item === null || $i < count( $txtFile) ) {
            $line = $txtFile[$i];
            $toks = array_map('trim', explode(':', $line, 2));
            if ($item === null) {
                if ( $toks[0] !== 'Item' ) {
                    throw new UnexpectedValueException("$i: Was expecting Item, got $line");
                }
                DataStore::assertExistence($toks[1], $existingItems, $i);

                $item = new Item();
                $item->name = $toks[1];
                $i++;
            } else {
                switch ( $toks[0] ) {
                    case 'Item':
                        return $item;
                    case 'Desc':
                        $item->description = $toks[1];
                        $i++;
                        break;
                    case 'Class':
                        DataStore::assertExistence($toks[1], $existingItems, $i);
                        $item->isSubclassOf[] = $toks[1];
                        $i++;
                        break;
                    case 'Type':
                        DataStore::assertExistence($toks[1], $existingItems, $i);
                        $item->isInstanceOf[] = $toks[1];
                        $i++;
                        break;
                    case 'Alias':
                        $item->alias[] = $toks[1];
                        $i++;
                        break;
                    case 'Sub':
                        DataStore::assertExistence($toks[1], $existingItems, $i);
                        $item->substituteFor[] = $toks[1];
                        $i++;
                        break;
                    case 'Var':
                        DataStore::assertExistence($toks[1], $existingItems, $i);
                        $item->variationOf[] = $toks[1];
                        $i++;
                        break;
                    case 'Ref':
                    case 'Url':
                        $item->source[] = Source::fromFlatFileFormat( $txtFile, $i, $existingItems );
                        break;
                    case 'Ing':
                        $item->hasIngredients[] = IngRel::fromFlatFileFormat( $txtFile, $i, $existingItems );
                        break;
                    case 'Glass':
                        $item->glass[] = Glassware::fromFlatFileFormat( $txtFile, $i, $existingItems );
                        break;
                    case 'Garn':
                        DataStore::assertExistence( $toks[1], $existingItems, $i );
                        $item->garnish[] = $toks[1];
                        $i++;
                        break;
                    case '':
                        $i++;
                        break;
                    default:
                        throw new UnexpectedValueException( "$i: Unknown token {$toks[0]}");
                }
            }
        }

        return $item;
    }

    public function toFlatFileFormat( string $filename ) {
        file_put_contents( $filename, 'Item:' . $this->name . PHP_EOL, FILE_APPEND );
        file_put_contents( $filename, 'Desc:' . $this->description . PHP_EOL, FILE_APPEND );
        foreach ( $this->alias as $alias ) {
            file_put_contents( $filename, 'Alias:' . $alias . PHP_EOL, FILE_APPEND );
        }
        foreach ( $this->isInstanceOf as $inst ) {
            file_put_contents( $filename, 'Type:' . $inst . PHP_EOL, FILE_APPEND );
        }
        foreach ( $this->isSubclassOf as $subc ) {
            file_put_contents( $filename, 'Class:' . $subc . PHP_EOL, FILE_APPEND );
        }
        foreach ( $this->variationOf as $var ) {
            file_put_contents( $filename, 'Var:' . $var . PHP_EOL, FILE_APPEND );
        }
        foreach ( $this->substituteFor as $sub ) {
            file_put_contents( $filename, 'Sub:' . $sub . PHP_EOL, FILE_APPEND );
        }
        foreach( $this->hasIngredients as $ing ) {
            $ing->toFlatFile( $filename );
        }
        foreach ( $this->glass as $glass ) {
            $glass->toFlatFile( $filename );
        }
        foreach ( $this->garnish as $garnish ) {
            file_put_contents( $filename, 'Garn:' . $garnish . PHP_EOL, FILE_APPEND );
        }
        foreach ( $this->source as $source ) {
            $source->toFlatFile( $filename );
        }
    }

    public function isIngredient( $ds ): bool {
        return $this->hasType( 'INGREDIENT', $ds );
    }

    public function hasType( string $type, DataStore $ds, int $it = 0 ): bool {
        $type = strtoupper( $type );
        if ( $it > 50 ) {
            throw new RuntimeException( "ETOOMANYLOOPS" );
        }
        if ( in_array( $type, array_map( 'strtoupper', $this->isInstanceOf ) ) || in_array( $type, array_map( 'strtoupper', $this->isSubclassOf ) ) ) {
            return true;
        }
        foreach ( $this->isInstanceOf as $inst ) {
            if ( $ds->get( $inst )->hasType( $type, $ds, $it + 1 ) ) {
                return true;
            }
        }
        foreach ( $this->isSubclassOf as $class ) {
            if  ( $ds->get( $class )->hasType( $type, $ds, $it + 1 ) ) {
                return true;
            }
        }
        return false;
    }
}
