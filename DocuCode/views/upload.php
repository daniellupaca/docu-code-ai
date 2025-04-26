<?php
require __DIR__ . '/../vendor/autoload.php'; // Asegúrate de tener Guzzle para la API de OpenAI

use GuzzleHttp\Client;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    $extractPath = 'extracted/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($extractPath)) mkdir($extractPath, 0777, true);

    $fileName = $_FILES['file']['name'];
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $destination = $uploadDir . basename($fileName);

    if (move_uploaded_file($fileTmpPath, $destination)) {
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileExt === 'rar') {
            $unrarPath = "C:\\Program Files\\WinRAR\\UnRAR.exe"; 
            $command = "\"$unrarPath\" x -o+ \"$destination\" \"$extractPath\"";
            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                function getFiles($dir) {
                    $files = [];
                    $items = array_diff(scandir($dir), ['.', '..']);
                    foreach ($items as $item) {
                        $path = $dir . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($path)) {
                            $files = array_merge($files, getFiles($path));
                        } else {
                            $files[] = $path;
                        }
                    }
                    return $files;
                }

                $files = getFiles($extractPath);
                $fileContents = [];

                $client = new Client();
                $openaiApiKey = ""; // Reemplaza con tu clave de OpenAI

                foreach ($files as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    if (in_array($extension, ['php', 'py'])) { // Solo analiza código PHP y Python
                        $content = file_get_contents($file);

                        // Enviar a OpenAI para comentarios
                        $response = $client->post('https://api.openai.com/v1/chat/completions', [
                            'headers' => [
                                'Authorization' => "Bearer $openaiApiKey",
                                'Content-Type'  => 'application/json',
                            ],
                            'json' => [
                                'model' => 'gpt-4',
                                'messages' => [
                                    ['role' => 'system', 'content' => 'Responde con un comentario breve y sencillo sobre el código.'],
                                    ['role' => 'user', 'content' => "Analiza este código de forma breve:\n$content"]
                                ]
                            ]
                        ]);

                        $aiResponse = json_decode($response->getBody(), true);
                        $comment = $aiResponse['choices'][0]['message']['content'] ?? 'No se pudo generar un comentario.';

                        $fileContents[] = [
                            'name' => basename($file),
                            'content' => $content,
                            'comment' => $comment
                        ];
                    }
                }

                echo json_encode(["status" => "success", "files" => $fileContents]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al descomprimir el archivo."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Solo se permiten archivos RAR."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error al subir el archivo."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No se recibió ningún archivo."]);
}
?>
