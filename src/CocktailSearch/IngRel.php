<?php


namespace CocktailSearch;


use UnexpectedValueException;

class IngRel {
    public string $ingredient;
    public array $suchAs = [];
    public array $substituteFor = [];
    public bool $optional = false;

    public function toFlatFile( string $filename ) {
        file_put_contents( $filename, 'Ing:' . $this->ingredient . PHP_EOL, FILE_APPEND );
        foreach ( $this->suchAs as $such ) {
            file_put_contents( $filename, 'Such:' . $such . PHP_EOL, FILE_APPEND );
        }
        foreach ( $this->substituteFor as $sub ) {
            file_put_contents( $filename, 'Sub:' . $sub . PHP_EOL, FILE_APPEND );
        }
        if ( $this->optional ) {
            file_put_contents( $filename, 'Opt:true' . PHP_EOL, FILE_APPEND );
        }
    }

    public static function fromFlatFileFormat( array $txtFile, int &$i, array $existingItems ): IngRel {
        $ingRel = null;

        while ( $ingRel === null || $i < count( $txtFile) ) {
            $line = $txtFile[$i];
            $toks = array_map('trim', explode(':', $line, 2));
            if ($ingRel === null) {
                if ($toks[0] !== 'Ing') {
                    throw new UnexpectedValueException("$i: Was expecting Ing, got $line");
                }
                DataStore::assertExistence($toks[1], $existingItems, $i);
                $ingRel = new IngRel();
                $ingRel->ingredient = $toks[1];
                $i++;
            } else {
                switch ($toks[0]) {
                    case 'Sub':
                        DataStore::assertExistence( $toks[1], $existingItems, $i );
                        $ingRel->substituteFor[] = $toks[1];
                        $i++;
                        break;
                    case 'Such':
                        DataStore::assertExistence( $toks[1], $existingItems, $i );
                        $ingRel->suchAs[] = $toks[1];
                        $i++;
                        break;
                    case 'Opt':
                        $ingRel->optional = true;
                        $i++;
                        break;
                    default:
                        return $ingRel;
                }
            }
        }
        return $ingRel;
    }

    public function getIngredientDesc() {
        if ( count ( $this->suchAs ) === 0) {
            return $this->ingredient;
        } else {
            return $this->ingredient . " (" . implode(', ', $this->suchAs ) . ")";
        }
    }
}
