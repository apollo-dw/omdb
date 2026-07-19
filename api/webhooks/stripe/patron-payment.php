<?php
    require("../../../base.php");
    $payload = file_get_contents('php://input');
    $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $endpointSecret = $env['STRIPE_PAYMENT_WEBHOOK_SECRET'];

    if (!$payload || !$signatureHeader) {
        http_response_code(400);
        exit();
    }

    $parts = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$key, $value] = explode('=', $part, 2);
        $parts[$key] = $value;
    }

    if (!isset($parts['t'], $parts['v1'])) {
        http_response_code(400);
        exit();
    }

    $timestamp = (int)$parts['t'];
    $signature = $parts['v1'];

    if (abs(time() - $timestamp) > 300) {
        http_response_code(400);
        exit();
    }

    $signedPayload = $timestamp . '.' . $payload;
    $expectedSignature = hash_hmac(
        'sha256',
        $signedPayload,
        $endpointSecret
    );

    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(400);
        exit();
    }

    $event = json_decode($payload);
    if (!$event) {
        http_response_code(400);
        exit();
    }

    if ($event->type !== 'checkout.session.completed') {
        http_response_code(200);
        exit();
    }

    $session = $event->data->object;

    if (($session->payment_status ?? '') !== 'paid') {
        http_response_code(200);
        exit();
    }

    $stmt = $conn->prepare("SELECT StripePaymentID FROM stripe_payments WHERE StripeEventID = ? LIMIT 1");
    $stmt->bind_param("s", $event->id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(200);
        exit();
    }
    $stmt->close();

    $stripeEventID = $event->id;
    $eventType = $event->type;

    $stripeSessionID = $session->id;
    $stripePaymentIntentID = $session->payment_intent ?? null;
    $stripeCustomerID = $session->customer ?? null;

    $amountTotal = $session->amount_total ?? null;
    $currency = strtoupper($session->currency ?? '');
    $paymentStatus = $session->payment_status ?? '';

    $userID = null;
    if (!empty($session->custom_fields)) {
        foreach ($session->custom_fields as $field) {
            if (isset($field->key) && $field->key === 'osuuserid' && isset($field->text->value)) {
                $userID = $field->text->value;
                break;
            }
        }
    }

    if ($userID === null) {
        http_response_code(400);
        exit();
    }

    $isNumeric = is_numeric($userID); 
    if (!$isNumeric) {
        $usernameInput = strtolower(trim($userID)); 
        $stmt = $conn->prepare("SELECT UserID, IsPatron, PatronFromDate, PatronToDate, TotalPatronMonths FROM users WHERE LOWER(Username) = ? LIMIT 1");
        $stmt->bind_param("s", $usernameInput);
    } else {
        if ($userID <= 0) {
            http_response_code(400);
            exit();
        }
        $stmt = $conn->prepare("SELECT UserID, IsPatron, PatronFromDate, PatronToDate, TotalPatronMonths FROM users WHERE UserID = ? LIMIT 1");
        $stmt->bind_param("i", $userID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $result->free();
        $stmt->close();

        if (!$isNumeric) {
            http_response_code(200); 
            exit();
        }

        $username = GetUserNameFromId($userID, $conn);
        $userID = (int)$userID; 

        $insertStmt = $conn->prepare("INSERT INTO users (UserID, Username, IsPatron, TotalPatronMonths) VALUES (?, ?, 0, 0)");
        $insertStmt->bind_param("is", $userID, $username);
        if (!$insertStmt->execute()) {
            http_response_code(500);
            exit();
        }

        $insertStmt->close();
        $user = [
            "IsPatron" => 0,
            "PatronFromDate" => null,
            "PatronToDate" => null,
            "TotalPatronMonths" => 0
        ];
    } else {
        $user = $result->fetch_assoc();
        $userID = (int)$user['UserID'];
        $stmt->close();
    }

    $stmt = $conn->prepare("
        INSERT INTO stripe_payments
        (
            StripeEventID,
            EventType,
            StripeSessionID,
            StripePaymentIntentID,
            StripeCustomerID,
            UserID,
            AmountTotal,
            Currency,
            PaymentStatus,
            Payload
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sssssissss",
        $stripeEventID,
        $eventType,
        $stripeSessionID,
        $stripePaymentIntentID,
        $stripeCustomerID,
        $userID,
        $amountTotal,
        $currency,
        $paymentStatus,
        $payload
    );

    $stmt->execute();
    $stmt->close();

    try{
        sendDiscordWebhook(
            $env['DISCORD_WEBHOOK_STRIPE_PAYMENT'],
            "🎉 New Patron Payment",
            [
                "UserID" => $userID ?? "None",
                "Amount" => number_format($amountTotal / 100, 2) . " " . $currency,
                "Stripe ID" => $event->id
            ],
            "https://omdb.nyahh.net/profile/" . $userID
        );
    } catch (Throwable $e) {
        error_log("discord failure:" . $e->getMessage());
    }

    $patronRates = [
        'GBP' => 300,    // £3.00
        'USD' => 400,    // $4.00
        'EUR' => 350,    // €3.50
        'CAD' => 500,    // C$5.00
        'AUD' => 500,    // A$5.00
        'NZD' => 550,    // NZ$5.50
        'JPY' => 500,    // ¥500
        'CNY' => 2500,   // ¥25.00
        'HKD' => 3000,   // HK$30.00
        'SGD' => 500,    // S$5.00
        'KRW' => 5000,   // ₩5000
        'INR' => 30000,  // ₹300.00
        'CHF' => 350,    // CHF 3.50
        'SEK' => 4000,   // kr40.00
        'NOK' => 4000,   // kr40.00
        'DKK' => 2500,   // kr25.00
    ];

    if (!isset($patronRates[$currency])) {
        http_response_code(200);
        exit();
    }

    $pricePerMonth = $patronRates[$currency];
    if ($pricePerMonth <= 0) {
        http_response_code(200);
        exit("");
    }

    $monthsPurchased = intdiv($amountTotal, $pricePerMonth);
    if ($monthsPurchased <= 0) {
        http_response_code(200);
        exit("");
    }

    if (empty($user["PatronToDate"]) || strtotime($user["PatronToDate"]) < time()) {
        $from = new DateTime();
        $to = new DateTime();

        $to->modify("+{$monthsPurchased} month");

        $patronFromDate = $from->format("Y-m-d H:i:s");
        $patronToDate = $to->format("Y-m-d H:i:s");
    } else {
        $to = new DateTime($user["PatronToDate"]);
        $to->modify("+{$monthsPurchased} month");

        $patronFromDate = $user["PatronFromDate"];
        $patronToDate = $to->format("Y-m-d H:i:s");
    }

    $totalPatronMonths = $user["TotalPatronMonths"] + $monthsPurchased;

    $stmt = $conn->prepare("UPDATE users SET IsPatron = 1, PatronFromDate = ?, PatronToDate = ?, TotalPatronMonths = ? WHERE UserID = ?");
    $stmt->bind_param("ssii", $patronFromDate, $patronToDate, $totalPatronMonths, $userID);
    $stmt->execute();
    $stmt->close();

    http_response_code(200);