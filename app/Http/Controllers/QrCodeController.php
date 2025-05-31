<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeController extends Controller
{
    public function generate(Link $link, Request $request)
    {
        $format = $request->get('format', 'png');
        $size = $request->get('size', 200);
        
        // Generate the short URL
        $shortUrl = url($link->short_code);
        
        if ($format === 'svg') {
            // Use SVG backend
            $renderer = new ImageRenderer(
                new RendererStyle($size, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $qrCode = $writer->writeString($shortUrl);
            
            return response($qrCode)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'attachment; filename="qr-' . $link->short_code . '.svg"');
        }
        
        // For PNG, use external API (simpler and more reliable)
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($shortUrl);
        $imageData = file_get_contents($qrUrl);
        
        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="qr-' . $link->short_code . '.png"');
    }
    
    public function display(Link $link, Request $request)
    {
        $size = $request->get('size', 150);
        
        // Generate the short URL
        $shortUrl = url($link->short_code);
        
        // Use external API for PNG display
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($shortUrl);
        $imageData = file_get_contents($qrUrl);
            
        return response($imageData)
            ->header('Content-Type', 'image/png');
    }
}