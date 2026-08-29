<?php
    $timeForPageExecution = microtime(true) - $timeAtPageLoad;
?>

		</div>

        <div class="modal" id="modal">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h2 class="modal-title">
                    Are you sure you want to leave OMDB?
                </h2>
                <div class="modal-body">
                    This URL takes you to <b>https://google.com</b>.
                </div>
                <div class="modal-footer">
                    <button>No</button>
                    <button>Go</button>
                </div>
            </div>
        </div>

		<div class="footerBar">
            <a href="/labs/">labs</a> |
			<a href="https://discord.gg/PWVGrQRq2w" target="_blank">discord</a> |
            <a href="https://github.com/apollo-dw/omdb/" target="_blank">github</a> |
            <a href="/rules/">rules</a> |
            <a href="/descriptors/">descriptors</a> |
            <a href="/edit-queue/">edit queue</a> <br>
            <span style="opacity:0;"><?php echo $timeForPageExecution; ?>s</span>
		</div>
    </body>
</html>
