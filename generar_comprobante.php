<?php

require_once('formato_comprobante_4.php');

$folder_pdfs = 'temp_pdfs';

// Crear la carpeta si no existe
if (!is_dir($folder_pdfs)) {
    mkdir($folder_pdfs);
}

// Generar un nombre de archivo único
$filename = uniqid() . '_comprobante.pdf';
$filepath = $folder_pdfs . '/' . $filename;

$data = [
    'filename' => $filepath,
    'telefono' => '53251684980'
];

crear_comprobante($data);

// Asegúrate de que el archivo se creó correctamente
if (file_exists($filepath)) {
    // Obtener la URL del PDF
    // Mostrar el PDF en el navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filepath . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);

} else {
    echo "Error: No se pudo crear el archivo PDF.";
}

?>