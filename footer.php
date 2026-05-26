<?php
    $timeForPageExecution = microtime(true) - $timeAtPageLoad;
?>
		
		</div>
		<div class="footerBar">
            <a href="https://github.com/apollo-dw/omdb/" target="_blank">github</a> |
            <a href="/rules/">rules</a> |
            <a href="/descriptors">descriptors</a> |
            <a href="/edit-queue">edit queue</a>
            <span style="color:black;"><?php echo $timeForPageExecution; ?>s</span>
		</div>
    </body>
</html>