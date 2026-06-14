<?php

require __DIR__.'/vendor/autoload.php';

// Test PDF parsing
$pdfPath = 'storage/app/public/knowledge-sources/G1JKkeG1ysKlSf5pulZNOxXsXHAAYIL1MWNMc92i.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF file not found at: $pdfPath\n";
    exit(1);
}

try {
    echo "Testing PDF Parser...\n";
    echo "File: $pdfPath\n\n";
    
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($pdfPath);
    
    $text = $pdf->getText();
    
    echo "Extracted text length: " . strlen($text) . " characters\n\n";
    echo "First 500 characters:\n";
    echo substr($text, 0, 500) . "\n\n";
    
    echo "✓ PDF parsing successful!\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
