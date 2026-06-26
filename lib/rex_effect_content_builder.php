<?php

class rex_effect_content_builder extends rex_effect_abstract
{
    public function execute()
    {
        $sourcePath = (string) $this->media->getSourcePath();
        $sourceFile = (string) $this->media->getMediaFilename();
        $mimeType = strtolower((string) rex_file::mimeType($sourcePath));
        $extension = strtolower((string) rex_file::extension($sourceFile !== '' ? $sourceFile : $sourcePath));

        // SVG immer unverändert ausliefern (kein Rasterizing im Content-Builder-Effekt).
        if ($mimeType === 'image/svg+xml' || $extension === 'svg') {
            return;
        }

        // PDF nur verarbeiten, wenn pdfout verfügbar ist und die Konvertierung gelingt.
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            if (!$this->convertPdfToImage()) {
                return;
            }
        } elseif (!$this->isSupportedRasterImage($mimeType, $extension)) {
            // Nicht unterstützte Typen unverändert durchreichen.
            return;
        }

        try {
            $this->media->asImage();
        } catch (Throwable) {
            return;
        }

        $ratio = trim((string) ($this->params['ratio'] ?? '16_9'));
        $mode = trim((string) ($this->params['mode'] ?? 'focuspoint'));
        $width = max(1, (int) ($this->params['width'] ?? 1200));
        $allowEnlarge = (string) ($this->params['allow_enlarge'] ?? 'not_enlarge');

        $resize = new rex_effect_resize();
        $resize->setMedia($this->media);
        $resize->setParams([
            'width' => $width,
            'height' => $width,
            'style' => 'maximum',
            'allow_enlarge' => $allowEnlarge,
        ]);
        $resize->execute();

        if ($mode !== 'focuspoint' || $ratio === 'original') {
            return;
        }

        [$ratioW, $ratioH] = $this->resolveRatio($ratio);

        if (class_exists('rex_effect_focuspoint_fit')) {
            $focuspoint = new rex_effect_focuspoint_fit();
            $focuspoint->setMedia($this->media);
            $focuspoint->setParams([
                'width' => $ratioW . 'fr',
                'height' => $ratioH . 'fr',
                'zoom' => '0',
                'meta' => 'med_focuspoint',
                'focus' => '50.0,50.0',
            ]);
            $focuspoint->execute();

            return;
        }

        // Fallback ohne focuspoint-Addon: zentrierter Crop auf das gewünschte Ratio.
        $currentWidth = (int) $this->media->getWidth();
        $currentHeight = (int) $this->media->getHeight();
        if ($currentWidth < 1 || $currentHeight < 1) {
            return;
        }

        $targetWidth = $currentWidth;
        $targetHeight = (int) floor($targetWidth * $ratioH / $ratioW);

        if ($targetHeight > $currentHeight) {
            $targetHeight = $currentHeight;
            $targetWidth = (int) floor($targetHeight * $ratioW / $ratioH);
        }

        $targetWidth = max(1, min($targetWidth, $currentWidth));
        $targetHeight = max(1, min($targetHeight, $currentHeight));

        $crop = new rex_effect_crop();
        $crop->setMedia($this->media);
        $crop->setParams([
            'width' => $targetWidth,
            'height' => $targetHeight,
            'hpos' => 'center',
            'vpos' => 'middle',
            'offset_width' => 0,
            'offset_height' => 0,
        ]);
        $crop->execute();
    }

    public function getName()
    {
        return 'Content Builder';
    }

    public function getParams()
    {
        return [
            [
                'label' => 'Preset',
                'name' => 'preset',
                'type' => 'string',
                'default' => 'starter_cards_16_9',
            ],
            [
                'label' => 'Ratio',
                'name' => 'ratio',
                'type' => 'string',
                'default' => '16_9',
            ],
            [
                'label' => 'Mode',
                'name' => 'mode',
                'type' => 'select',
                'options' => ['focuspoint', 'resize'],
                'default' => 'focuspoint',
            ],
            [
                'label' => 'Width',
                'name' => 'width',
                'type' => 'int',
                'default' => 1200,
            ],
            [
                'label' => 'Allow Enlarge',
                'name' => 'allow_enlarge',
                'type' => 'select',
                'options' => ['enlarge', 'not_enlarge'],
                'default' => 'not_enlarge',
            ],
        ];
    }

    /**
     * @return array{int,int}
     */
    private function resolveRatio(string $ratio): array
    {
        $normalized = str_replace(':', '_', $ratio);
        if (preg_match('/^(\d+)_+(\d+)$/', $normalized, $matches) !== 1) {
            return [16, 9];
        }

        $w = max(1, (int) $matches[1]);
        $h = max(1, (int) $matches[2]);

        return [$w, $h];
    }

    private function isSupportedRasterImage(string $mimeType, string $extension): bool
    {
        $supportedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
            'image/vnd.wap.wbmp',
        ];
        if (in_array($mimeType, $supportedMimeTypes, true)) {
            return true;
        }

        $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'wbmp'];
        return in_array($extension, $supportedExtensions, true);
    }

    private function convertPdfToImage(): bool
    {
        if (!rex_addon::get('pdfout')->isAvailable() || !class_exists('rex_effect_pdf_thumbnail')) {
            return false;
        }

        try {
            $convert = new rex_effect_pdf_thumbnail();
            $convert->setMedia($this->media);
            $convert->setParams([
                'convert_to' => 'jpg',
                'density' => '150',
                'quality' => '85',
                'page' => '1',
                'color' => 'ffffff',
                'gamma' => '1.0',
                'icc_profile' => 'none',
            ]);
            $convert->execute();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
