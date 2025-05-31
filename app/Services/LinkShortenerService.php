<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LinkShortenerService
{
    const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const DEFAULT_LENGTH = 6;
    
    public function generateShortCode(?string $customSlug = null): string
    {
        if ($customSlug) {
            return $this->processCustomSlug($customSlug);
        }
        
        return $this->generateRandomCode();
    }
    
    private function processCustomSlug(string $slug): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/\s+/', '-', $slug);
        
        // Remove any characters that aren't alphanumeric, hyphens, or underscores
        $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);
        
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim hyphens from beginning and end
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            throw new \InvalidArgumentException('Invalid slug provided');
        }
        
        return $slug;
    }
    
    private function generateRandomCode(): string
    {
        $code = '';
        $maxIndex = strlen(self::ALPHABET) - 1;
        
        for ($i = 0; $i < self::DEFAULT_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $maxIndex)];
        }
        
        return $code;
    }
    
    public function ensureUnique(string $code): bool
    {
        return !DB::table('links')->where('short_code', $code)->exists();
    }
    
    public function generateUniqueCode(?string $customSlug = null): string
    {
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            $code = $this->generateShortCode($customSlug);
            $attempts++;
            
            if ($this->ensureUnique($code)) {
                return $code;
            }
            
            // If custom slug is taken, throw exception
            if ($customSlug) {
                throw new \Exception('This custom URL is already taken');
            }
            
        } while ($attempts < $maxAttempts);
        
        throw new \Exception('Unable to generate unique short code');
    }
}