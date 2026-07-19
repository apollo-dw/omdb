<?php 
    function sendDiscordWebhook(string $webhookURL, string $title, array $fields = [], ?string $url = null, int $color = 0x2F4F4F
    ) {
        $embed = [
            "title" => $title,
            "color" => $color,
            "fields" => []
        ];

        if ($url !== null) {
            $embed["url"] = $url;
        }

        foreach ($fields as $name => $value) {
            $embed["fields"][] = [
                "name" => $name,
                "value" => (string)$value,
                "inline" => true
            ];
        }

        $payload = json_encode([
            "embeds" => [
                $embed
            ]
        ]);

        $ch = curl_init($webhookURL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            error_log("Discord webhook failed: " . curl_error($ch));
        }

        curl_close($ch);
    }