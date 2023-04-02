# LimeCocktail

**CW:** multiple mentions of alcohol; drink responsibly.

LimeCocktail manages cocktail book indexes. Its main feature is to answer questions of type "If I have some limes, and I'm in the mood for something rum-based, what cocktail can I make?".

It supports fuzzy/approximate search when ingredients are declared to be roughly equivalent to one another (for instance, a search for a given orange liqueur may return another orange liqueur that is deemed equivalent to it).

LimeCocktail must be provided with an index in a custom format, as explained below.

## File format

The index file is currently hard-coded to `data/cocktails.txt`. A tiny example is provided with this source code.

Every line is prefixed with a keyword, indicated below. Empty lines are ignored.

* `Item:` - name of the item. Mandatory, unique.
* `Desc:` - description of the item. Mandatory, free string, unique.
* `Alias` - another name for the item. Optional, free string, multiple.
* `Class:` - used for classes of items; indicates the class this item is a subclass of. For instance, `fruit liqueur` could be a subclass of `liqueur`, which would be coded as the `fruit liqueur` item having `Class:liqueur`. Optional, must be in the item list, multiple.
  * Special Classes used in the search: `ingredient`.
* `Type:` - used for specific items; indicates the class this item is an instance of. For instance, `lime` could be an instance of `fruit`, which would be coded as the `lime` item having `Type:fruit`.  Optional, must be in the item list, multiple.
  * Special Types used in the search: `cocktail recipe`, `ingredient` and `recipe`.
* `Sub:` - indicates an item that can be unconditionally substituted for the current item. For instance (please don't judge me), my own base has `Sub:lemon juice` in the `lime juice` entry and `Sub:lime juice` in the `lemon juice` entry. Optional, must be in the item list, multiple.
* `Var:` - indicates that a recipe is a variant of another one. Optional, must be in the item list, multiple.
* `Ing:` - defines an ingredient of a recipe. Optional, must be in the item list, should (transitively) be an instance of or a subclass of the `ingredient` item, multiple.
  * `Sub:` - when following an ingredient declaration, ingredient that can be substituted in that recipe. Optional, must be in the item list, multiple.
  * `Such:` - example of a specific ingredient (a specific brand of vodka for a `vodka` ingredient, for instance). Optional, must be in the item list, multiple.
  * `Opt:` - indicates that an ingredient is optional. The value is ignored, the presence of the `Opt` is enough.
* `Glass` - Glassware for a cocktail. Must be in the item list. Can be qualified with a free string by separating it with a comma (for instance, `tumbler, short`). Optional, multiple.
* `Garn:` - Garnish for a cocktail. Optional, must be in the item list, multiple. 
* `Ref:` - book reference. Composed of the title of the book, a `#` sign, and the page number. The title of the book must be in the item list. Optional, multiple.
* `Url:` - web page reference. Optional, multiple.

### File format example

A small extract of my own recipe index, illustrating all the syntax, is provided in `samples/cocktails-sample.txt`.

## Using LimeCocktail

The most straightforward approach is to run `php -S localhost:80` in the `src/` directory and to point a browser to http://localhost. This displays a form with possible ingredients. `Submit` displays a table of matching cocktails from the cocktail file `data/cocktails.txt` (this his hardcoded for now.) 

**Note:** `php -S` must only be used in development/trusted private networks. 

Otherwise, and in a very non-tested way, setting up a PHP-compatible web server and dropping the `src/` and `data/` directory in a virtual server root is probably the way to go.  
