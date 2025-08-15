<?php
$data = json_decode(file_get_contents("php://input"), true);
if (!empty($data['image'])) {
    $img = $data['image'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $fileData = base64_decode($img);
    $fileName = 'uploads/avatar_' . time() . '.png';
    file_put_contents($fileName, $fileData);
    echo json_encode(["status" => "success", "path" => $fileName]);
}
?>
