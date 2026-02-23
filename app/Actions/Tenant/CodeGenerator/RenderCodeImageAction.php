<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CodeGenerator;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Picqer\Barcode\BarcodeGeneratorPNG;

final class RenderCodeImageAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): string
    {
        if ($payload['type'] === 'qr') {
            $qrCode = new QrCode(
                (string) $payload['value'],
                size: (int) $payload['size'],
                margin: (int) $payload['margin']
            );

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            return $result->getString();
        }

        $generator = new BarcodeGeneratorPNG();

        return $generator->getBarcode(
            (string) $payload['value'],
            $this->barcodeType((string) $payload['format']),
            (int) $payload['scale'],
            (int) $payload['height']
        );
    }

    private function barcodeType(string $format): string
    {
        return match ($format) {
            'C39' => BarcodeGeneratorPNG::TYPE_CODE_39,
            'EAN13' => BarcodeGeneratorPNG::TYPE_EAN_13,
            'EAN8' => BarcodeGeneratorPNG::TYPE_EAN_8,
            'UPC' => BarcodeGeneratorPNG::TYPE_UPC_A,
            default => BarcodeGeneratorPNG::TYPE_CODE_128,
        };
    }
}
