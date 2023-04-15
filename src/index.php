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
        <p><input name="ing[]" list="inglist" /> <select name="exact[]"><option value="fuzzy">Fuzzy</option><option value="exact">Exact</option><option value="exclude">Exclude</option></select> </p>
        <p><input name="ing[]" list="inglist" /> <select name="exact[]"><option value="fuzzy">Fuzzy</option><option value="exact">Exact</option><option value="exclude">Exclude</option></select> </p>
        <p><input name="ing[]" list="inglist" /> <select name="exact[]"><option value="fuzzy">Fuzzy</option><option value="exact">Exact</option><option value="exclude">Exclude</option></select> </p>
        <p><input name="ing[]" list="inglist" /> <select name="exact[]"><option value="fuzzy">Fuzzy</option><option value="exact">Exact</option><option value="exclude">Exclude</option></select> </p>
        <p><input name="ing[]" list="inglist" /> <select name="exact[]"><option value="fuzzy">Fuzzy</option><option value="exact">Exact</option><option value="exclude">Exclude</option></select> </p>
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
    $search = [];
    $exact = [];
    foreach ($_GET['ing'] as $i => $ing) {
        if ($ds->hasItem($ing)) {
            $search[] = $ing;
            $exact[] = $_GET['exact'][$i];
        }
    }
    ?>
    <a href="./index.php">&lt;-Back</a><br>
    <p>Cocktails with <?php echo implode( ', ', $search ); ?></p>
    <table style="width:800px">
        <tr>
            <th>Cocktail</th>
            <th>Ingredients</th>
            <th>Source</th>
        </tr>
<?php
    $list = $ds->getCocktails( $search, $exact );
    foreach( $list as $cocktail ):?>
        <tr>
            <td>
            <?php echo $cocktail->name ?>
            </td>
            <td style="width: 60%">
                <?php echo implode( ', ', array_map( fn($i) => $i->getIngredientDesc(), $cocktail->hasIngredients ) ) ?>
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
