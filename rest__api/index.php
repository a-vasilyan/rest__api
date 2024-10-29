<?php

try {
    $pdo = new PDO("mysql:host=localhost;dbname=book", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Не удалось подключиться к базе данных 'book' :" . $e->getMessage());
}

$apiUrl = "https://openlibrary.org/search.json?q=books&limit=50";

function fetchFromApi($apiUrl) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die("Ошибка cURL: " . curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}

function saveToDatabase($pdo, $data) {
    $uniqueBooks = [];

    foreach ($data as $item) {
        $id = $item['key'] ?? null;
        $title = $item['title'] ?? null;
        $authors = isset($item['author_name']) ? implode(', ', $item['author_name']) : null;
        $year = $item['first_publish_year'] ?? null;

        if ($title && !in_array($title, $uniqueBooks)) {
            $uniqueBooks[] = $title;

            $stmt = $pdo->prepare("INSERT INTO books (id, title, authors, year) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $title, $authors, $year]);
        }
    }
}

$data = fetchFromApi($apiUrl);

$docs = $data['docs'] ?? [];

saveToDatabase($pdo, $docs);

echo 'Данные успешно получены и сохранены.';
?>
