<html>
<head>
    <style>
        tbody tr:nth-child(even) {
            background-color: #eee;
        }
    </style>
</head>
<body>

<?php

require "../vendor/autoload.php";

use CocktailSearch\DataStore;

$ds = DataStore::fromFlatFile( '../data/cocktails.txt' );


if( !isset( $_GET['ing'] ) ):?>
    <form method="get">
        <input name="ing[]" list="inglist" />
        <input name="ing[]" list="inglist" />
        <input name="ing[]" list="inglist" />
        <input name="ing[]" list="inglist" />
        <input name="ing[]" list="inglist" />
        <datalist id="inglist">
            <?php foreach ( $ds->getIngredients() as $alias => $ing ):
            echo "<option value=\"$ing\">$alias</option>";
            endforeach;
            ?>
        </datalist>
        <input type="submit">
    </form>

<?php
else:
    ?>
    <a href="index.php">&lt;-Back</a><br>
    <table>
        <tr>
            <th>Cocktail</th>
            <th>Description</th>
            <th>Source</th>
        </tr>
<?php
    $search = [];
    foreach ( $_GET['ing'] as $ing ) {
        if ( $ds->hasItem( $ing ) ) {
            $search[] = $ing;
        }
    }
    $list = $ds->getCocktails( $search );
    foreach( $list as $cocktail ):?>
        <tr>
            <td>
            <?php echo $cocktail->name ?>
            </td>
            <td>
                <?php echo $cocktail->description ?>
            </td>
            <td>
                <?php
                $first = true;
                foreach ( $cocktail->source as $source ) {
                    if ( !$first ) {
                        echo "<br>";
                    }
                    $first = false;
                    if ( isset ($source->book) ) {
                        echo $source->book . ' p' . $source->page;
                    }
                    else {
                        echo "<a href='{$source->url}'>{$source->url}</a>";
                    }
                }
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </table>
<?php endif;?>

</body>
</html>
