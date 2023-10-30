<?php
    $timeForPageExecution = microtime(true) - $timeAtPageLoad;
?>
		
		</div>
		<div class="footerBar">
            <a href="/rules/">rules</a> |
            <a href="https://github.com/apollo-dw/omdb">github</a> |
            <a href="https://discord.gg/NwcphppBMG">discord</a> |
            <a href="/descriptors">descriptors</a> |
            <a href="/edit-queue">edit queue</a> |
            <a href="/project-legacy">project legacy</a><br>
            <span style="color:black;"><?php echo $timeForPageExecution; ?>s</span>
		</div>
    </body>
</html>