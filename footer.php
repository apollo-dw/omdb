<?php
    $timeForPageExecution = microtime(true) - $timeAtPageLoad;
?>
		
		</div>
		<div class="footerBar">
			omdb made by <a href="https://omdb.nyahh.net/profile/9558549">apollo</a> |
            icon made by <a href="https://omdb.nyahh.net/profile/7081160">olc</a> |
            <a href="/rules/">rules</a> |
            <a href="https://github.com/apollo-dw/omdb">github</a> |
            <a href="https://discord.gg/NwcphppBMG">discord</a> |
            <a href="/descriptors">descriptors</a><br> |
            <a href="/edit-queue">edit queue</a><br> |
            <a href="/project-legacy">project legacy</a><br>
            <span style="color:black;"><?php echo $timeForPageExecution; ?>s</span>
		</div>
    </body>
</html>