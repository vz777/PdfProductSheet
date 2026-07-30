<?php

namespace PdfProductSheet\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ProductImageQuery;
use Thelia\Model\ProductQuery;

class PdfController extends BaseFrontController
{
    /**
     * Le paramètre {filename} ne sert qu'au nom du fichier téléchargé.
     */
    public function generateAction(Request $request, string $template, string $filename): Response
    {
        $productId = (int) $request->query->get('id', 0);

        if ($productId <= 0) {
            throw new NotFoundHttpException('Missing or invalid product id');
        }

        $product = ProductQuery::create()->findPk($productId);

        if (null === $product) {
            throw new NotFoundHttpException(sprintf('Product %d not found', $productId));
        }

        try {

        $fontsDir = realpath(__DIR__ . '/../Resources/fonts');
Tlog::getInstance()->error(
    $request->getSession()->getLang()->getLocale()
);
        $html = $this->renderRaw(
            $template,
            [
                'product_id' => $productId,
                'fonts_dir' => $fontsDir,
                'product_image_data_uri' => $this->buildProductImageDataUri($productId),
            ],
            $this->getTemplateHelper()->getActivePdfTemplate()
        );
Tlog::getInstance()->error(
    $this->getRequest()->getSession()->getLang()->getLocale()
);
        } catch (\Throwable $e) {
            Tlog::getInstance()->error($e->getMessage());
            throw $e;
        }

        $options = new Options();

        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu');
        
        if ($fontsDir !== false) {
            $options->setFontDir($fontsDir);
            $options->setFontCache($fontsDir);
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');

        try {
            $dompdf->render();
        } catch (\Throwable $e) {
            Tlog::getInstance()->error(sprintf(
                'PdfProductSheet: erreur de rendu Dompdf pour le produit %d : %s',
                $productId,
                $e->getMessage()
            ));
            throw $e;
        }


        $dompdf->getCanvas()->page_script(
            '
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 8;
            $text = "Page " . $PAGE_NUM . " / " . $PAGE_COUNT;
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - 45 - $width;
            $y = $pdf->get_height() - 25;
            $pdf->text($x, $y, $text, $font, $size, array(0.55, 0.54, 0.47));
            '
        );

        $pdfOutput = $dompdf->output();

        $isDownload = 'pdfproductsheet.getpdf' === $request->attributes->get('_route');
        $safeFilename = preg_replace('/[^a-zA-Z0-9\-_]/', '', $filename) ?: 'fiche-produit';

        $locale = $request->getSession()->getLang()->getLocale();

        $productPageUrl = $product->getUrl($locale);

        return new Response(
            $pdfOutput,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    '%s; filename="%s.pdf"',
                    $isDownload ? 'attachment' : 'inline',
                    $safeFilename
                ),
                'Link' => sprintf('<%s>; rel="canonical"', $productPageUrl), 
            ]
        );
    }

    private function buildProductImageDataUri(int $productId, int $maxWidth = 800): ?string
    {
        $productImage = ProductImageQuery::create()
            ->filterByProductId($productId)
            ->filterByVisible(1)
            ->orderByPosition()
            ->findOne();

        if (null === $productImage) {
            Tlog::getInstance()->warning(sprintf(
                'PdfProductSheet: aucune image visible trouvée pour le produit %d',
                $productId
            ));

            return null;
        }

        $baseSourceFilePath = ConfigQuery::read('images_library_path');
        if (null === $baseSourceFilePath) {
            $baseSourceFilePath = THELIA_LOCAL_DIR . 'media' . DS . 'images';
        } else {
            $baseSourceFilePath = THELIA_ROOT . $baseSourceFilePath;
        }

        $sourceFilePath = sprintf('%s/%s/%s', $baseSourceFilePath, 'product', $productImage->getFile());

        if (!is_readable($sourceFilePath)) {
            Tlog::getInstance()->error(sprintf(
                'PdfProductSheet: fichier image introuvable ou illisible : %s',
                $sourceFilePath
            ));

            return null;
        }

        $imageInfo = getimagesize($sourceFilePath);

        if (false === $imageInfo) {
            Tlog::getInstance()->error(sprintf(
                'PdfProductSheet: getimagesize() a échoué sur %s',
                $sourceFilePath
            ));

            return null;
        }

        [$width, $height, $imageType] = $imageInfo;

        $source = match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourceFilePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourceFilePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourceFilePath) : null,
            default => null,
        };

        if (null === $source) {
            $mime = image_type_to_mime_type($imageType);

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sourceFilePath));
        }

        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if (IMAGETYPE_PNG === $imageType) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        ob_start();
        if (IMAGETYPE_PNG === $imageType) {
            imagepng($source);
            $mime = 'image/png';
        } else {
            imagejpeg($source, null, 85);
            $mime = 'image/jpeg';
        }
        $imageData = ob_get_clean();
        imagedestroy($source);

        return 'data:' . $mime . ';base64,' . base64_encode($imageData);
    }
}
