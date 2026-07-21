<?php
    $PageTitle = "Patron";
    require "../base.php";
    require "../header.php";
?>

<div style="width:100%;text-align:center">
    <h1>OMDB Patron</h1>
</div>

<div class="flex-container column-when-mobile-container" style="align-items: center;">
    <div class="flex-child column-when-mobile" style="width:30%; text-align: center;">
        <img src="../../assets/img/omdb-192x192.png" alt="OMDB logo"/>
    </div>
    <div class="flex-child column-when-mobile" style="width:60%;">
        <div style="background-color: darkslategray;padding:1em;">
            <p>
                You can become an OMDB Patron to help support the site! Your contributions will go directly towards the site: server upkeep, upgrades to infrastructure, and helping keeping my lights on. My lights and nothing else.
            </p>
            <p>
                Whenever I mention that I own and manage a site with this many people, I often hear "you should put advertisements on it!". Well, that's never gonna happen. I would never do that to any of you. This site will forever be free to use without any advertisement, but to anyone who wishes to support the site somehow then this option is available. Thank you so much if you choose to do this!
            </p>
        </div>
    </div>  
</div>

<?php
    if (!$loggedIn) {
        die("log in to views this page");    
    }
?>

<?php 
if ($user["IsPatron"] === 1) {
?>
<hr>
<div style="width:100%;text-align:center;margin-top:5em;margin-bottom:5em;background-color: var(--main-theme-patron-pink);color: black;">
    <div style="padding:2em;">
        <span style="font-size:2em; font-weight: bolder;"><i class="icon-heart"></i> </span>
        <p>
            <b>thank you for supporting OMDB!</b> <br />
            you have OMDB patron benefits until <?php echo(new DateTime($user["PatronToDate"]))->format("jS F Y"); ?> <br>
            you have supported OMDB for <?php echo $user["TotalPatronMonths"]; ?> month<?php if ($user["TotalPatronMonths"] !== 1) { echo "s"; } ?>
        </p>
    </div>
</div> 
<?php
}
?>

<hr>

<div style="width:100%;text-align:center;margin-top:5em;margin-bottom:5em;background-color: darkslategray;">
    <div style="padding:2em;">
        <?php
        if ($user["IsPatron"] !== 1) {
        ?>
            <span style="font-size:2em; font-weight: bolder;"><a style="color: var(--main-theme-patron-pink);" href="https://buy.stripe.com/9B67sLdRT7caas7gZPcEw00" target="_blank">Become a Patron</a></span>
        <?php
        } else {
        ?>
            <span style="font-size:2em; font-weight: bolder;"><a style="color: var(--main-theme-patron-pink);" href="https://buy.stripe.com/9B67sLdRT7caas7gZPcEw00" target="_blank">Extend your patronage</a></span>
        <?php
        }
        ?>
        <p>
            get 1 month of benefits per £3.00 // $4.00 // €3.50 <br />
            please get in touch if there are any issues
        </p>
    </div>
</div> 


<hr>

<div style="width:100%;text-align:center">
    <h2>Benefits</h2>
</div>


<div class="flex-container" style="align-items: center; flex-direction: column;">
    <div class="alternating-bg" style="width: 50%; text-align: center;">
        <p>
            <b>Badge</b> <br>
            Display a badge on your profile and comments to show your support
        </p>
    </div>
    <div class="alternating-bg" style="width: 50%; text-align: center;">
        <p>
            <b>Profile theme</b><br> 
            Customize the colour theme of your profile for anyone visiting
        </p>
    </div>
    <div class="alternating-bg" style="width: 50%; text-align: center;">
        <p>
            <b>Discord role</b> <br>
            Get a distinctly <i>DarkSlateGrey-ish</i> role on the OMDB <a href="https://discord.gg/PWVGrQRq2w" target="_blank">Discord</a> (just ping me!)
        </p>
    </div>
    <div class="alternating-bg" style="width: 50%; text-align: center;">
        <p>
            <b>Shoutout on this page</b> <br>
            Get shouted out on this page for your contributions (present and past!)
        </p>
    </div>
    <div class="alternating-bg" style="width: 50%; text-align: center;">
        <p>
            <b>... and maybe more!</b> <br>
            As this patron service continues, more benefits will be added
        </p>
    </div>
</div>

<hr>

<div style="width:100%;text-align:center">
    <h2>OMDB Patrons</h2>
</div>

<?php
    $stmt = $conn->prepare("
        SELECT
            UserID,
            Username,
            TotalPatronMonths,
            PatronToDate
        FROM users
        WHERE TotalPatronMonths > 0
        ORDER BY
            (PatronToDate IS NOT NULL AND PatronToDate > NOW()) DESC,
            TotalPatronMonths DESC
    ");

    $stmt->execute();
    $patrons = $stmt->get_result();
?>

<div class="flex-row-container">
    <?php
    while ($row = $patrons->fetch_assoc()) {
        $isActive = (
            !empty($row["PatronToDate"]) &&
            strtotime($row["PatronToDate"]) > time()
        );

        $patronClass = $isActive ? "pink-background" : "";
        ?>
        <div class="friend-box <?php echo $patronClass; ?>">
            <a href="/profile/<?php echo $row["UserID"]; ?>">
                <div class="profileImage">
                    <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="width:5em;height:5em;"/><br>
                    <?php echo safe_htmlspecialchars($row["Username"], ENT_QUOTES); ?> <br>
                    (<?php echo (int)$row["TotalPatronMonths"]; ?> <?php echo ((int)$row["TotalPatronMonths"] === 1) ? "month" : "months"; ?>)
                </div>
            </a>
        </div>
        <?php
    }
    ?>
</div>

<?php
    require '../footer.php'
?>