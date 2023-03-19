<?php


namespace CocktailSearch;


use UnexpectedValueException;

class Glassware {
    public string $glass;
    public ?string $qualifier = null;
    private static string $glassRegex = "/Glass:([^,]*)(?:, (.*))?/";

    public function toFlatFile( string $filename ) {
        file_put_contents( $filename, 'Glass:' . $this->glass, FILE_APPEND );
        if ( $this->qualifier !== null ) {
            file_put_contents( $filename, ', ' . $this->qualifier, FILE_APPEND );
        }
        file_put_contents( $filename, PHP_EOL, FILE_APPEND );
    }

    public static function fromFlatFileFormat( array $txtFile, int &$i, array $existingItems ): Glassware {
        $line = $txtFile[$i];
        $glass = new Glassware();
        $matches = [];
        if ( preg_match( self::$glassRegex, $line, $matches ) ) {
            DataStore::assertExistence( $matches[1], $existingItems, $i );
            $glass->glass = $matches[1];
            if ( isset ($matches[2]) ) {
                $glass->qualifier = $matches[2];
            }
        } else {
            throw new UnexpectedValueException( "Cannot parse glassware from $line");
        }
        $i++;
        return $glass;
    }
}
