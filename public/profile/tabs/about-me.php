<?php
    require_once __DIR__ . '/../../../app/base.php';
    $profileId = GetIntParam("id", null, "What are you trying to do man.");

    $stmt = $conn->prepare("SELECT CustomDescription FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_row();
    $stmt->close();
    $desc = trim($profile[0] ?? "");

    $stmt = $conn->prepare("SELECT IsPrivate FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $isPrivate = (bool)($stmt->get_result()->fetch_row()[0] ?? 0);
    $stmt->close();
    if ($isPrivate) {
        $shouldHide = GetProfilePageHiddenStatus($conn, $profileId, $userId);
        if ($shouldHide) {
            $desc = "[i]This user has a hidden OMDB presence.[/i]";
        }
    }

    if (!$isValidUser) {
        $desc = "[i]User does not have an OMDB profile.[/i]";
    }

    if (!empty($desc)) {
    ?>
        <div style="background-color:var(--main-theme-color-darker);padding:2em;box-sizing:border-box;">
            <?php
                echo ParseCommentLinks($conn, $desc);
            ?>
        </div>
    <?php
    } else {
        if ($profileId == $userId) {
    ?>
        <div style="background-color:var(--main-theme-color-darker);padding:2em;box-sizing:border-box;text-align: center;">
            <?php
                echo "<a href='../settings'><i class='icon-plus'></i> Create a description</a>";
            ?>
        </div>
    <?php
        }
    }
    ?>

