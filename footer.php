<?php
    $timeForPageExecution = microtime(true) - $timeAtPageLoad;
?>
		
		</div>
		<div class="footerBar">
			<a href="https://discord.gg/PWVGrQRq2w" target="_blank">discord</a> |
            <a href="https://github.com/apollo-dw/omdb/" target="_blank">github</a> |
            <a href="/rules/">rules</a> |
            <a href="/descriptors">descriptors</a> |
            <a href="/labs">Labs</a> |
            <a href="/edit-queue">edit queue</a> <br>
            <span style="color:black;"><?php echo $timeForPageExecution; ?>s</span>
		</div>
    </body>
</html>