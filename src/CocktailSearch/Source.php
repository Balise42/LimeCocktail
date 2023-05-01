<?php


namespace CocktailSearch;


use UnexpectedValueException;

class Source
{
    private static string $sourceRegex = "/^Ref:([^#]*)#(.*)|Url:(.*)/";
    public string $book;
    public string $page;
    public string $url;
    public string $filename;

    public function toFlatFile( string $filename ) {
        if ( isset($this->book) ) {
            file_put_contents($filename, 'Ref:' . $this->book . '#' . $this->page . PHP_EOL, FILE_APPEND);
        }
        if ( isset($this->url) ) {
            file_put_contents($filename, 'Url:' . $this->url . PHP_EOL, FILE_APPEND);
        }
    }

    public static function fromFlatFileFormat( array $txtFile, int &$i, array $existingItems ): Source {
        $line = $txtFile[$i];
        $source = new Source();
        $matches = [];
        if ( preg_match( self::$sourceRegex, $line, $matches ) ) {
            if ( isset( $matches[3] ) ) {
                $source->url = $matches[3];
            } else {
                DataStore::assertExistence( $matches[1], $existingItems, $i );
                $source->book = $matches[1];
                $source->page = $matches[2];
            }
        } else {
            throw new UnexpectedValueException( "Cannot parse source from $line");
        }
        $i++;
        return $source;
    }
}
