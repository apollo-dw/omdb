<?php
    $timeForPageExecution = microtime(true) - $timeAtPageLoad;
?>
		
		</div>
		<div class="footerBar">
			omdb made by <a href="https://omdb.nyahh.net/profile/9558549">apollo</a> | icon made by <a href="https://omdb.nyahh.net/profile/7081160">olc</a> | <a href="https://github.com/apollo-dw/omdb">github</a> | <a href="https://discord.gg/NwcphppBMG">discord</a><br>
            <span style="color:black;"><?php echo $timeForPageExecution; ?>s</span>
		</div>
    </body>
</html>