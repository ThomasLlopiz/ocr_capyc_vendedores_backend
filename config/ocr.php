<?php

return [
    // Binario de Python del venv donde está instalado ocr_cli.py
    'python_bin' => env(
        'OCR_PYTHON_BIN',
        'C:\\Users\\DEPIT-1\\Documents\\OCR\\page-reactocr\\ocr\\.venv\\Scripts\\python.exe'
    ),

    // Ruta absoluta al ocr_cli.py
    'cli_path' => env(
        'OCR_CLI_PATH',
        base_path('../page-reactocr/ocr/ocr_cli.py')
    ),

    // Ruta a la carpeta de PDFs (relativa a base_path de Laravel)
    'pdfs_dir' => env('OCR_PDFS_DIR', 'pdfs'),

    // Ruta donde se mueven los PDFs una vez procesados
    'pdfs_procesados_dir' => env('OCR_PDFS_PROCESADOS_DIR', 'pdfs_procesados'),

    // Timeout del proceso (segundos por PDF)
    'timeout' => (int) env('OCR_TIMEOUT', 120),

    // Tesseract (solo si el PDF no tiene capa de texto)
    'tesseract_bin' => env(
        'OCR_TESSERACT_BIN',
        'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'
    ),

    // TES permitidas al aprobar
    'allowed_tes' => array_filter(
        explode(',', (string) env('OCR_ALLOWED_TES', '507,522,557,551'))
    ),
];