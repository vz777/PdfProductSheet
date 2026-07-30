# PdfProductSheet

Ce module permet de générer un PDF avec Dompdf de la fiche produit à partir d'un template qui sera cherché dans le template PDF courant.
Les routes sont les mêmes que celles du module [PdfGenerator](https://github.com/roadster31/PdfGenerator/).
Dans le cas peu probable où vous souhaiteriez utiliser les deux modules, changez le nom des routes dans PdfProductSheet.

## fr_FR

## Fonctionnalités

- Génération d'une fiche produit PDF
- Mise à disposition de l'image principale du produit dans les templates PDF
- Ajout d'un en-tête HTTP `Link` de type `canonical` vers la fiche produit
- Polices personalisées à installer dans /ressources/fonts

## Utilisation

## Templating

Le template reçoit une variable supplémentaire :

- `$product_image_data_uri` : contient l'image principale du produit encodée en Data URI (ou `null` si aucune image n'est disponible).

Exemple :

```smarty
{if $product_image_data_uri}
    <img src="{$product_image_data_uri nofilter}" alt="{$TITLE}">
{/if}
```

Si vous voulez utilisez des polices personnalisées, placez les fichiers `.ttf` dans : PdfProductSheet/Resources/fonts/

### Télécharger une fiche produit

```
https://yourshop.tld/getpdf/template/nom-du-produit-123?id=123
```

### Visualiser une fiche produit

```
https://yourshop.tld/viewpdf/template/nom-du-produit-123?id=123
```

Avec :

- `template` : le nom du template PDF (sans l'extension `.html`) ;
- `nom-du-produit-123` : utilisé uniquement comme nom du fichier PDF ;
- `id` : l'identifiant du produit.

## Installation

### Composer

```
composer require vz777/pdf-product-sheet
```

## Prérequis

- Thelia 2.5+
- Extension PHP GD
- Dompdf

## en_US

The module generates product sheet PDFs using Dompdf. The PDF template is searched in the active PDF template directory.
The routes are the same as the PdfGenerator module:
https://github.com/roadster31/PdfGenerator/

If you need to use both modules at the same time, rename the routes in PdfProductSheet.

## Features

- Generate a PDF product sheet from a product page
- Provides the main product image as a Data URI for PDF templates
- Adds a canonical HTTP Link header pointing to the product page

## Usage

## Templating

The template receives an additional variable:

- `$product_image_data_uri`: contains the main product image encoded as a Data URI (or `null` if no image is available).

Example:

```smarty
{if $product_image_data_uri}
    <img src="{$product_image_data_uri nofilter}" alt="{$TITLE}">
{/if}
```

If you want to use custom fonts, place the .ttf files in PdfProductSheet/Resources/fonts/

### Download a product sheet

```
https://yourshop.tld/getpdf/template-name/product-name-123?id=123
```

### Preview a product sheet

```
https://yourshop.tld/viewpdf/template-name/product-name-123?id=123
```

Where:

- `template-name` is the PDF template to render (without the `.html` extension).
- `product-name-123` is only used as the generated PDF filename.
- `id` is the product identifier.

## Installation

### Composer

```
composer require vz777/pdf-product-sheet
```

## Requirements

- Thelia 2.5+
- PHP GD extension
- Dompdf
