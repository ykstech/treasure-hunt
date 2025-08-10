<?php
header('Content-Type: application/json');
session_start();

try {
    $pdo = new PDO(
        "mysql:host=localhost:3307;dbname=treasure_game;charset=utf8",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $response = [
        "success" => false,
        "message" => "",
        "audio" => "",
        "seconds" => 2,
        "showWinPopup" => false
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $team_number = intval($_POST['team_number'] ?? 0);
        $password = trim($_POST['password'] ?? "");

        $stmt = $pdo->prepare("SELECT * FROM treasure_teams WHERE team_number = ?");
        $stmt->execute([$team_number]);
        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($team) {
            if ($team['attempts_left'] > 1) {
                if ($password === $team['password']) {
                    $response["success"] = true;
                    $response["showWinPopup"] = true;
                    $response["message"] = "TEAM: $team_number WIN, By the ancient powers, you have triumphed! The treasure is yours — claim it with honor, brave soul!";
                    $response["audio"] = "win.mp3"; // Path to success audio
                    $response["seconds"] = 10; // Duration for success message
                    $pdo->prepare("UPDATE treasure_teams SET attempts_left = 0 WHERE team_number = ?")
                        ->execute([$team_number]);
                } else {
                    $response["message"] = "Foolish mortal… the key fails you. One last chance remains — use it wisely, or the treasure shall vanish forever!";
                    $response["audio"] = "one-left.mp3";
                    $response["seconds"] = 10; // Duration for wrong password message
                    $pdo->prepare("UPDATE treasure_teams SET attempts_left = attempts_left - 1 WHERE team_number = ?")->execute([$team_number]);
                }
            } else {
                $response["message"] = "Alas… your chances are spent. The treasure is lost to the shadows, and your quest ends here!, TEAM: $team_number";
                $response["audio"] = "fail.mp3";
                $response["seconds"] = 8; // Duration for no attempts left message
            }
        } else {
            $response["message"] = "⚠ Team not found!";
        }
    } else {
        $response["message"] = "Invalid request method.";
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage(),
        "showWinPopup" => false
    ]);
}
