<html>
<head>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php

require "../vendor/autoload.php";

use CocktailSearch\DataStore;

$ds = DataStore::fromFiles( '../recipes/recipes.txt', '../recipes/txt' );


if( !isset( $_GET['ing'] ) ):?>
    <h1>LimeCocktail</h1>
    <p>Choose ingredients in the fields and indicate if that ingredient must be "approximately there" (fuzzy), "exactly there" (exact) or "not there" (excluded).</p>
    <form method="get">
        <?php for ( $i = 0; $i < 5; $i++ ) { ?>
            <div class="input-group input-group-lg mb-2"><input class="form-select" name="ing[]" list="inglist" /> <select class="form-select" name="exact[]"><option value="fuzzy">Fuzzy</option><option value="exact">Exact</option><option value="exclude">Exclude</option></select> </div>
        <?php } ?>

        <datalist id="inglist">
            <?php foreach ( $ds->getIngredients() as $ing => $desc ):
            echo "<option value=\"$ing\">$desc</option>";
            endforeach;
            ?>
        </datalist>
        <input type="submit" class="btn btn-primary">
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
    <a href="/food/index.php">&lt;-Back</a><br>
    <p>Recipes with <?php echo implode( ', ', $search ); ?></p>
    <table class="recipetable">
        <tr>
            <th>Recipe</th>
            <th>Ingredients</th>
            <th>Source</th>
        </tr>
<?php
    $list = $ds->getRecipes( $search, $exact, 'recipe' );
    foreach($list as $recipe ):?>
        <tr>
            <td class="recipename">
            <?php echo $recipe->name ?>
            </td>
            <td class="inglist">
                <?php echo implode( ', ', array_map( fn($i) => $i->getIngredientDesc(), $recipe->hasIngredients ) ) ?>
            </td>
            <td class="source">
                <?php
                $first = true;
                foreach ($recipe->source as $source ) {
                    if ( !$first ) {
                        echo "<br>";
                    }
                    $first = false;
                    if ( isset ($source->book) ) {
                        echo $source->book . ' p' . $source->page;
                    }
                    else if ( isset ( $source->url ) ) {
                        echo "<a href='{$source->url}'>Original source</a>";
                    } else {
                        echo "<a href='{$source->filename}'>recipe</a>";
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
