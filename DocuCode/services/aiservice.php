<?php
require __DIR__ . '/../vendor/autoload.php'; 

use GuzzleHttp\Client;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    $extractPath = 'extracted/';
    $umlDir = 'uml_images/'; // Carpeta donde se guardarán los diagramas

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($extractPath)) mkdir($extractPath, 0777, true);
    if (!is_dir($umlDir)) mkdir($umlDir, 0777, true);

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
                $openaiApiKey = ""; 

                foreach ($files as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    if (in_array($extension, ['php', 'py'])) { 
                        $content = file_get_contents($file);

                        $response = $client->post('https://api.openai.com/v1/chat/completions', [
                            'headers' => [
                                'Authorization' => "Bearer $openaiApiKey",
                                'Content-Type'  => 'application/json',
                            ],
                            'json' => [
                                'model' => 'gpt-4',
                                'messages' => [
                                    ['role' => 'system', 'content' => 'Eres un asistente técnico que genera comentarios breves y diagramas UML en PlantUML.'],
                                    ['role' => 'user', 'content' => "1. Comenta este código de forma breve y sencilla.\n2. Genera un diagrama UML en formato PlantUML basado en el código:\n$content"]
                                ]
                            ]
                        ]);

                        $aiResponse = json_decode($response->getBody(), true);
                        $comment = $aiResponse['choices'][0]['message']['content'] ?? 'No se pudo generar un comentario.';

                        preg_match('/```plantuml(.*?)```/s', $comment, $matches);
                        $plantUML = $matches[1] ?? '';

                        if (!empty($plantUML)) {
                            $encodedPlantUML = encodePlantUML($plantUML);
                            $plantUMLImageURL = "http://www.plantuml.com/plantuml/png/$encodedPlantUML";

                            // Guardar la imagen localmente
                            $imagePath = $umlDir . basename($file, ".$extension") . '.png';
                            file_put_contents($imagePath, file_get_contents($plantUMLImageURL));
                        } else {
                            $imagePath = '';
                        }

                        $fileContents[] = [
                            'name' => basename($file),
                            'content' => $content,
                            'comment' => $comment,
                            'plantuml' => trim($plantUML),
                            'plantuml_image' => $imagePath
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

// Función para codificar el PlantUML en base64 modificada
function encodePlantUML($text) {
    $compressed = gzdeflate($text, 9);
    $encoded = base64_encode($compressed);
    $encoded = strtr($encoded, [
        '+' => '-',
        '/' => '_'
    ]);
    return rtrim($encoded, '=');
}
?>
